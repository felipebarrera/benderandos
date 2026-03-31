<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Venta;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    public function __construct(
        private VentaService $ventaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ventas = Venta::with(['cliente', 'usuario', 'cajero', 'tipoPago'])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 30));

        return response()->json($ventas);
    }

    public function show($id): JsonResponse
    {
        $venta = Venta::with(['items.producto', 'cliente', 'usuario', 'cajero', 'tipoPago'])
            ->findOrFail($id);

        return response()->json($venta);
    }

    /**
     * POST /api/ventas — Crear venta abierta
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'nullable|integer|exists:clientes,id',
        ]);

        $venta = $this->ventaService->crear(
            $request->user(),
            $request->cliente_id
        );

        return response()->json($venta, 201);
    }

    /**
     * GET /api/ventas/por-cliente?rut=...&codigo=...
     * Busca venta abierta del cliente por RUT o código rápido.
     */
    public function porCliente(Request $request): JsonResponse
    {
        $cliente = null;

        if ($request->filled('rut')) {
            $cliente = Cliente::where('rut', $request->rut)->first();
        } elseif ($request->filled('codigo')) {
            $cliente = Cliente::where('codigo_rapido', (int) $request->codigo)->first();
        }

        if (! $cliente) {
            return response()->json(['cliente' => null, 'venta' => null]);
        }

        $venta = Venta::where('cliente_id', $cliente->id)
            ->whereIn('estado', ['abierta', 'en_caja'])
            ->with(['items.producto', 'items.operario'])
            ->latest()
            ->first();

        return response()->json([
            'cliente' => $cliente,
            'venta'   => $venta,
        ]);
    }

    /**
     * PUT /api/ventas/{id}/estado — Cajero toma la venta (en_caja)
     */
    public function tomarVenta(Request $request, $id): JsonResponse
    {
        $venta = Venta::where('estado', 'abierta')->findOrFail($id);

        $venta->update([
            'estado'   => 'en_caja',
            'cajero_id' => $request->user()->id,
        ]);

        return response()->json($venta->fresh()->load('items.producto'));
    }

    /**
     * POST /api/ventas/{id}/items — Agregar item
     */
    public function agregarItem(Request $request, $id): JsonResponse
    {
        $request->validate([
            'producto_id'     => 'required|integer|exists:productos,id',
            'cantidad'        => 'nullable|numeric|min:0.001',
            'precio_unitario' => 'nullable|integer|min:0',
            'operario_id'     => 'nullable|integer|exists:users,id',
            'notas_item'      => 'nullable|string',
            'inicio_renta'    => 'nullable|date',
            'fin_renta'       => 'nullable|date|after:inicio_renta',
        ]);

        $venta = Venta::where('estado', 'abierta')->findOrFail($id);

        $item = $this->ventaService->agregarItem($venta, $request->all());

        return response()->json($item, 201);
    }

    /**
     * DELETE /api/ventas/{ventaId}/items/{itemId} — Quitar item
     */
    public function quitarItem($ventaId, $itemId): JsonResponse
    {
        $venta = Venta::where('estado', 'abierta')->findOrFail($ventaId);
        $this->ventaService->quitarItem($venta, $itemId);

        return response()->json($venta->fresh()->load('items'));
    }

    /**
     * POST /api/ventas/{id}/confirmar — Pagar/fiar venta
     */
    public function confirmar(Request $request, $id): JsonResponse
    {
        $request->validate([
            'tipo_pago_id'     => 'nullable|integer|exists:tipos_pago,id',
            'descuento_monto'  => 'nullable|integer|min:0',
            'descuento_pct'    => 'nullable|numeric|min:0|max:100',
            'es_deuda'         => 'nullable|boolean',
            'numero_documento' => 'nullable|string',
            'tipo_documento'   => 'nullable|string',
            'notas'            => 'nullable|string',
            'qr_code_pos'      => 'nullable|string',
            'qr_escaneo_uuid'  => 'nullable|string',
        ]);

        $venta = Venta::whereIn('estado', ['abierta', 'en_caja'])->with('items')->findOrFail($id);
        
        $datos = $request->all();

        // --- H15 Lógica Marketing QR ---
        if (!empty($datos['qr_code_pos'])) {
            $campana = \App\Models\Tenant\CampanaMarketing::where('codigo_pos', $datos['qr_code_pos'])->first();
            if ($campana && $campana->activa) {
                $subtotal = $venta->items->sum('total_item');
                $descuentoMarketing = 0;

                if ($campana->tipo_accion === 'descuento_porcentaje') {
                    $descuentoMarketing = round($subtotal * ($campana->valor_descuento / 100));
                } elseif ($campana->tipo_accion === 'descuento_fijo') {
                    $descuentoMarketing = min($subtotal, $campana->valor_descuento);
                } elseif ($campana->tipo_accion === 'dos_por_uno') {
                    if ($venta->items->count() >= 2) {
                        $minPriceItem = $venta->items->min(fn($i) => $i->precio_unitario);
                        $descuentoMarketing = $minPriceItem;
                    }
                }
                
                if ($descuentoMarketing > 0) {
                    $datos['descuento_monto'] = ($datos['descuento_monto'] ?? 0) + $descuentoMarketing;
                    $campana->increment('usos_actuales');
                    
                    // Registrar conversión
                    if (!empty($datos['qr_escaneo_uuid'])) {
                        $escaneo = \App\Models\Tenant\EscaneoQr::whereHas('qr', fn($q) => $q->where('uuid', $datos['qr_escaneo_uuid']))
                            ->latest('fecha_escaneo')->first();
                        if ($escaneo && !$escaneo->convertido) {
                            $escaneo->update(['convertido' => true, 'venta_id' => $venta->id]);
                        }
                    }
                }
            }
        }

        $resultado = $this->ventaService->confirmar(
            $venta,
            $datos,
            $request->user()
        );

        return response()->json($resultado);
    }

    /**
     * POST /api/ventas/{id}/anular — Anular venta
     */
    public function anular(Request $request, $id): JsonResponse
    {
        $venta = Venta::whereIn('estado', ['pagada', 'fiada'])->findOrFail($id);

        $resultado = $this->ventaService->anular($venta, $request->user());

        return response()->json($resultado);
    }
}
