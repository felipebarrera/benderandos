<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Venta;
use App\Services\VentaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse; // Added for JsonResponse return types

class BotApiController extends Controller
{

    /**
     * Consultar stock de un producto por SKU.
     */
    public function stock(string $sku): JsonResponse
    {
        $producto = Producto::where('codigo', $sku)->first();

        if (! $producto || $producto->estado !== 'activo') {
            return response()->json(['error' => 'Producto no encontrado o inactivo'], 404);
        }

        return response()->json([
            'sku' => $producto->codigo,
            'nombre' => $producto->nombre,
            'stock_actual' => $producto->cantidad,
            'unidad_medida' => $producto->unidad_medida,
        ]);
    }

    /**
     * Consultar precio de un producto por SKU.
     */
    public function precio(string $sku): JsonResponse
    {
        $producto = Producto::where('codigo', $sku)->first();

        if (! $producto || $producto->estado !== 'activo') {
            return response()->json(['error' => 'Producto no encontrado o inactivo'], 404);
        }

        return response()->json([
            'sku'    => $producto->codigo,
            'nombre'  => $producto->nombre,
            'precio'  => $producto->valor_venta
        ]);
    }

    /**
     * Consultar datos de un cliente por teléfono.
     */
    public function cliente($telefono)
    {
        $cliente = Cliente::where('telefono', 'LIKE', "%$telefono%")->first();
        if (!$cliente) return response()->json(['message' => 'Cliente no encontrado'], 404);

        return response()->json([
            'id'     => $cliente->id,
            'nombre' => $cliente->nombre,
            'email'  => $cliente->email,
            'rut'    => $cliente->rut
        ]);
    }

    /**
     * Crear un pedido desde el bot.
     */
    public function crearPedido(Request $request, VentaService $ventaService)
    {
        $data = $request->validate([
            'telefono' => 'required',
            'items'    => 'required|array',
            'items.*.sku'      => 'required|exists:productos,codigo',
            'items.*.cantidad' => 'required|numeric|min:0.1',
        ]);

        try {
            return DB::transaction(function() use ($data, $ventaService) {
                $cliente = Cliente::firstOrCreate(
                    ['telefono' => $data['telefono']],
                    ['nombre' => 'Cliente WhatsApp ' . substr($data['telefono'], -4)]
                );

                $venta = Venta::create([
                    'cliente_id' => $cliente->id,
                    'estado'     => 'remota_pendiente',
                    'total'      => 0,
                    'tipo'       => 'venta',
                ]);

                foreach ($data['items'] as $item) {
                    $producto = Producto::where('codigo', $item['sku'])->first();

                    if ($producto && $producto->estado === 'activo') {
                        // Aquí idealmente validar stock
                        $ventaService->agregarItem($venta, [
                            'producto_id' => $producto->id,
                            'cantidad' => $item['cantidad']
                        ]);
                    } else {
                        // Optionally handle inactive or not found products in the order
                        // For now, we just skip them or could throw an error
                        throw new \Exception("Producto con SKU {$item['sku']} no encontrado o inactivo.");
                    }
                }

                return response()->json([
                    'message'  => 'Pedido creado exitosamente',
                    'venta_id' => $venta->id,
                    'total'    => $venta->total,
                    'estado'   => $venta->estado
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear pedido: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Consultar estado de un pedido.
     */
    public function estadoPedido($id)
    {
        $venta = Venta::find($id);
        if (!$venta) return response()->json(['message' => 'Pedido no encontrado'], 404);

        return response()->json([
            'id'     => $venta->id,
            'estado' => $venta->estado,
            'total'  => $venta->total
        ]);
    }

    /**
     * Retorna datos del portal público para context del bot.
     */
    public function portalData()
    {
        $config = \App\Models\Tenant\RubroConfig::first();
        return response()->json([
            'nombre'      => tenant()->nombre,
            'descripcion' => $config->portal_descripcion,
            'horario'     => $config->portal_horario,
            'telefono'    => $config->portal_telefono,
            'direccion'   => $config->portal_direccion,
            'whatsapp'    => $config->portal_whatsapp_numero,
            'telegram'    => $config->portal_telegram_url,
        ]);
    }
    /**
     * Consultar disponibilidad de agenda para el bot.
     */
    public function disponibilidad(Request $request, \App\Services\AgendaService $svc)
    {
        $data = $request->validate([
            'recurso_id' => 'nullable|exists:agenda_recursos,id',
            'fecha'      => 'nullable|date_format:Y-m-d',
        ]);

        $fecha = $data['fecha'] ?? now()->toDateString();
        $agenda = $svc->getAgendaDia($fecha, $data['recurso_id'] ?? null);

        return response()->json($agenda);
    }

    /**
     * Reservar cita desde el bot.
     */
    public function reservarCita(Request $request, \App\Services\AgendaService $svc)
    {
        $data = $request->validate([
            'telefono'           => 'required',
            'nombre'             => 'required|string',
            'agenda_recurso_id'  => 'required|exists:agenda_recursos,id',
            'agenda_servicio_id' => 'required|exists:agenda_servicios,id',
            'fecha'              => 'required|date_format:Y-m-d',
            'hora_inicio'        => 'required|date_format:H:i',
        ]);

        try {
            $srv = \App\Models\Tenant\AgendaServicio::find($data['agenda_servicio_id']);
            $horaFin = \Carbon\Carbon::parse($data['hora_inicio'])->addMinutes($srv->duracion_min)->format('H:i');

            $cita = $svc->crearCita([
                'agenda_recurso_id'  => $data['agenda_recurso_id'],
                'agenda_servicio_id' => $data['agenda_servicio_id'],
                'fecha'              => $data['fecha'],
                'hora_inicio'        => $data['hora_inicio'],
                'hora_fin'           => $horaFin,
                'paciente_nombre'    => $data['nombre'],
                'paciente_telefono'  => $data['telefono'],
                'origen'             => 'bot',
            ]);

            return response()->json([
                'message' => 'Cita reservada exitosamente',
                'cita_id' => $cita->id,
                'fecha'   => $cita->fecha,
                'hora'    => substr($cita->hora_inicio, 0, 5),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al reservar: ' . $e->getMessage()], 500);
        }
    }
}
