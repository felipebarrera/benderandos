<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\PlanModulo;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuloPlanController extends Controller
{
    /**
     * List all modules with their price and calculated MRR contribution.
     */
    public function index(): JsonResponse
    {
        $modulos = PlanModulo::all();
        $subscriptions = Subscription::where('estado', 'activa')->get();

        $modulosArray = $modulos->map(function ($modulo) use ($subscriptions) {
            $activeCount = $subscriptions->filter(function ($sub) use ($modulo) {
                return in_array($modulo->modulo_id, $sub->modulos_activos ?? []);
            })->count();

            return [
                'modulo_id' => $modulo->modulo_id,
                'nombre' => $modulo->nombre,
                'precio_mensual' => $modulo->precio_mensual,
                'tenants_activos' => $activeCount,
                'mrr_aporte' => $activeCount * $modulo->precio_mensual,
            ];
        });

        $mrrTotal = $modulosArray->sum('mrr_aporte');

        return response()->json([
            'modulos' => $modulosArray,
            'mrr_modules_total' => $mrrTotal,
        ]);
    }

    /**
     * Update the price of a module.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'precio_mensual' => 'required|integer|min:0',
        ]);

        $modulo = PlanModulo::findOrFail($id);
        
        // Log to history here initially (simplification)
        DB::table('plan_modulos_historial')->insert([
            'modulo_id' => $modulo->modulo_id,
            'precio_anterior' => $modulo->precio_mensual,
            'precio_nuevo' => $request->precio_mensual,
            'cambiado_por' => auth()->id() ?? 1, // Fallback if no auth 
            'aplica_desde' => now()->addMonth(), // Standard policy
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modulo->update(['precio_mensual' => $request->precio_mensual]);

        return response()->json(['message' => 'Precio actualizado correctamente.', 'modulo' => $modulo]);
    }

    /**
     * Simulate impact of price change.
     */
    public function impacto(string $id, Request $request): JsonResponse
    {
        $nuevoPrecio = $request->query('nuevo_precio', 0);
        $modulo = PlanModulo::findOrFail($id);
        
        $subscriptions = Subscription::where('estado', 'activa')->get();
        $activeCount = $subscriptions->filter(function ($sub) use ($modulo) {
            return in_array($modulo->modulo_id, $sub->modulos_activos ?? []);
        })->count();

        $mrrActual = $activeCount * $modulo->precio_mensual;
        $mrrNuevo = $activeCount * $nuevoPrecio;

        return response()->json([
            'modulo' => $modulo->nombre,
            'tenants_afectados' => $activeCount,
            'mrr_actual' => $mrrActual,
            'mrr_nuevo' => $mrrNuevo,
            'diferencia_mrr' => $mrrNuevo - $mrrActual,
        ]);
    }
}
