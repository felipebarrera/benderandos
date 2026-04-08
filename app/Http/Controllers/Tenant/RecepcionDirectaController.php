<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RecepcionDirecta;
use App\Models\Tenant\ItemRecepcionDirecta;
use App\Models\Tenant\Producto;
use App\Models\Tenant\MovimientoStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecepcionDirectaController extends Controller
{
    /**
     * GET /api/recepciones-directas
     * Lista con filtros. Para caja: ?estado=pendiente_pago
     */
    public function index(Request $request): JsonResponse
    {
        $q = RecepcionDirecta::with(['proveedor', 'usuario', 'items.producto'])
            ->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $q->where('estado', $request->estado);
        }

        return response()->json($q->paginate($request->input('per_page', 30)));
    }

    /**
     * GET /api/recepciones-directas/pendientes-count
     * Badge para caja
     */
    public function pendientesCount(): JsonResponse
    {
        $count = RecepcionDirecta::where('estado', 'pendiente_pago')->count();
        return response()->json(['count' => $count]);
    }

    /**
     * POST /api/recepciones-directas
     * Operario crea borrador
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'proveedor_id'     => 'nullable|integer|exists:proveedores,id',
            'proveedor_nombre' => 'nullable|string|max:255',
            'notas'            => 'nullable|string',
        ]);

        $recepcion = RecepcionDirecta::create([
            ...$data,
            'usuario_id' => $request->user()->id,
            'estado'     => 'borrador',
        ]);

        return response()->json($recepcion, 201);
    }

    /**
     * POST /api/recepciones-directas/{id}/items
     * Agregar o actualizar producto en la recepción
     */
    public function agregarItem(Request $request, int $id): JsonResponse
    {
        $recepcion = RecepcionDirecta::findOrFail($id);

        if (!in_array($recepcion->estado, ['borrador'])) {
            return response()->json(['message' => 'Solo se pueden modificar recepciones en borrador'], 422);
        }

        $data = $request->validate([
            'producto_id'    => 'required|integer|exists:productos,id',
            'cantidad'       => 'required|numeric|min:0.001',
            'costo_unitario' => 'nullable|integer|min:0',
        ]);

        $item = ItemRecepcionDirecta::updateOrCreate(
            ['recepcion_id' => $id, 'producto_id' => $data['producto_id']],
            [
                'cantidad'       => $data['cantidad'],
                'costo_unitario' => $data['costo_unitario'] ?? 0,
                'costo_total'    => ($data['costo_unitario'] ?? 0) * $data['cantidad'],
            ]
        );

        // Recalcular total de la recepción
        $this->recalcularTotal($recepcion);

        return response()->json($item->load('producto'), 201);
    }

    /**
     * DELETE /api/recepciones-directas/{id}/items/{itemId}
     */
    public function quitarItem(int $id, int $itemId): JsonResponse
    {
        $recepcion = RecepcionDirecta::findOrFail($id);
        if ($recepcion->estado !== 'borrador') {
            return response()->json(['message' => 'No modificable'], 422);
        }

        ItemRecepcionDirecta::where('recepcion_id', $id)->findOrFail($itemId)->delete();
        $this->recalcularTotal($recepcion);

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/recepciones-directas/{id}/cerrar
     * Operario termina de contar → pasa a pendiente_pago
     */
    public function cerrar(Request $request, int $id): JsonResponse
    {
        $recepcion = RecepcionDirecta::with('items')->findOrFail($id);

        if ($recepcion->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden cerrar borradores'], 422);
        }

        if ($recepcion->items->isEmpty()) {
            return response()->json(['message' => 'Agrega al menos un producto'], 422);
        }

        $recepcion->update([
            'estado'     => 'pendiente_pago',
            'cerrada_at' => now(),
            'notas'      => $request->notas ?? $recepcion->notas,
        ]);

        return response()->json($recepcion->fresh('items.producto', 'proveedor'));
    }

    /**
     * POST /api/recepciones-directas/{id}/pagar
     * Caja aprueba: sube stock + registra movimientos + marca pagada
     */
    public function pagar(Request $request, int $id): JsonResponse
    {
        $recepcion = RecepcionDirecta::with('items.producto')->findOrFail($id);

        if ($recepcion->estado !== 'pendiente_pago') {
            return response()->json(['message' => 'Solo se pueden pagar recepciones pendientes'], 422);
        }

        $data = $request->validate([
            'tipo_pago'        => 'required|in:efectivo,transferencia,credito,cheque',
            'numero_documento' => 'nullable|string|max:100',
            'monto_total'      => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($recepcion, $data, $request) {
            // Subir stock de cada item
            foreach ($recepcion->items as $item) {
                $producto = $item->producto;
                $stockAntes = (float) $producto->cantidad;
                $stockNuevo = $stockAntes + (float) $item->cantidad;

                $producto->update(['cantidad' => $stockNuevo]);

                MovimientoStock::create([
                    'producto_id'   => $producto->id,
                    'tipo'          => 'recepcion_directa',
                    'cantidad'      => $item->cantidad,
                    'stock_antes'   => $stockAntes,
                    'stock_despues' => $stockNuevo,
                    'usuario_id'    => $request->user()->id,
                    'notas'         => "Recepción #{$recepcion->id} - {$recepcion->proveedor_nombre}",
                ]);
            }

            // Actualizar costo de productos si se ingresó
            foreach ($recepcion->items as $item) {
                if ($item->costo_unitario > 0) {
                    $item->producto->update(['costo' => $item->costo_unitario]);
                }
            }

            $recepcion->update([
                'estado'           => 'pagada',
                'aprobado_por'     => $request->user()->id,
                'tipo_pago'        => $data['tipo_pago'],
                'numero_documento' => $data['numero_documento'] ?? null,
                'monto_total'      => $data['monto_total'] ?? $recepcion->monto_total,
                'pagada_at'        => now(),
            ]);
        });

        return response()->json($recepcion->fresh('items.producto', 'proveedor', 'aprobadoPor'));
    }

    /**
     * POST /api/recepciones-directas/{id}/anular
     */
    public function anular(int $id): JsonResponse
    {
        $recepcion = RecepcionDirecta::findOrFail($id);
        if ($recepcion->estado === 'pagada') {
            return response()->json(['message' => 'No se puede anular una recepción ya pagada'], 422);
        }
        $recepcion->update(['estado' => 'anulada']);
        return response()->json(['ok' => true]);
    }

    // ── Privado ──────────────────────────────────────────

    private function recalcularTotal(RecepcionDirecta $recepcion): void
    {
        $total = ItemRecepcionDirecta::where('recepcion_id', $recepcion->id)->sum('costo_total');
        $recepcion->update(['monto_total' => $total]);
    }
}