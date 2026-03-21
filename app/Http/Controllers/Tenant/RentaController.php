<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Renta;
use App\Services\RentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentaController extends Controller
{
    public function __construct(
        private RentaService $rentaService
    ) {}

    /**
     * GET /api/rentas/panel
     * Devuelve todos los productos tipo 'renta' y su estado actual (ocupado o libre).
     */
    public function panel(): JsonResponse
    {
        $productos = Producto::where('tipo_producto', 'renta')->get()->map(function ($p) {
            $rentaActiva = Renta::with('cliente')
                ->where(fn($q) => $q->where('producto_id', $p->id))
                ->where(fn($q) => $q->where('estado', 'activa'))
                ->first();

            return [
                'id'                => $p->id,
                'nombre'            => $p->nombre,
                'estado'            => $rentaActiva ? 'ocupado' : 'libre',
                'renta'             => $rentaActiva,
                'minutos_restantes' => ($rentaActiva instanceof Renta && $rentaActiva->fin_programado)
                    ? max(0, (int) now()->diffInMinutes($rentaActiva->fin_programado))
                    : null,
            ];
        });

        return response()->json($productos);
    }

    /**
     * POST /api/rentas/{id}/extender
     */
    public function extender(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'minutos_extra' => 'required|integer|min:1',
            'cargo'         => 'required|integer|min:0',
        ]);

        $renta = Renta::where(fn($q) => $q->where('estado', 'activa'))->findOrFail($id);

        $renta = $this->rentaService->extenderRenta(
            $renta,
            $request->minutos_extra,
            $request->cargo
        );

        return response()->json($renta);
    }

    /**
     * POST /api/rentas/{id}/devolver
     */
    public function devolver(Request $request, int $id): JsonResponse
    {
        $renta = Renta::where(fn($q) => $q->whereIn('estado', ['activa', 'extendida', 'vencida']))->findOrFail($id);

        $renta = $this->rentaService->devolverRenta($renta);

        return response()->json($renta);
    }
}
