<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Central\PlanModulo;
use App\Models\Central\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MiPlanController extends Controller
{
    /**
     * Get the tenant's current plan info.
     */
    public function index(): JsonResponse
    {
        $suscripcion = Subscription::where('tenant_id', tenant('id'))->latest()->first();
        if (!$suscripcion) {
            return response()->json(['error' => 'No subscription found'], 404);
        }

        $modulosActivos = $suscripcion->modulos_activos ?? [];
        $modulosData = PlanModulo::whereIn('modulo_id', $modulosActivos)->get();

        $totalMensual = $modulosData->sum('precio_mensual');
        
        // Ensure to include base fee logic if defined
        $tarifaBase = 19990; 
        $costoFinal = $tarifaBase + $modulosData->where('es_base', false)->sum('precio_mensual');

        return response()->json([
            'suscripcion' => $suscripcion,
            'modulos_activos' => $modulosData,
            'tarifa_base' => $tarifaBase,
            'total_mensual' => $costoFinal,
        ]);
    }

    /**
     * List available modules to activate (not active yet).
     */
    public function disponibles(): JsonResponse
    {
        $suscripcion = Subscription::where('tenant_id', tenant('id'))->latest()->first();
        $modulosActivos = $suscripcion->modulos_activos ?? [];
        
        $disponibles = PlanModulo::whereNotIn('modulo_id', $modulosActivos)
            ->where('activo', true)
            ->where('es_base', false)
            ->get();

        return response()->json(['disponibles' => $disponibles]);
    }
    
    /**
     * Preview the price implications of activating/deactivating a module.
     */
    public function preview($id): JsonResponse
    {
        $suscripcion = Subscription::where('tenant_id', tenant('id'))->latest()->first();
        $modulosActivos = collect($suscripcion->modulos_activos ?? []);
        
        $modulo = PlanModulo::findOrFail($id);
        $totalActual = $this->calculateTotal($modulosActivos->toArray());
        
        $nuevoArray = $modulosActivos->contains($id)
            ? $modulosActivos->reject(fn($v) => $v === $id)->toArray()
            : $modulosActivos->push($id)->toArray();

        $nuevoTotal = $this->calculateTotal($nuevoArray);

        return response()->json([
            'modulo' => $modulo,
            'total_actual' => $totalActual,
            'total_nuevo' => $nuevoTotal,
            'diferencia' => $nuevoTotal - $totalActual,
        ]);
    }

    /**
     * Activate a module.
     */
    public function activar(Request $request, $id): JsonResponse
    {
        $suscripcion = Subscription::where('tenant_id', tenant('id'))->latest()->first();
        $modulosArray = $suscripcion->modulos_activos ?? [];
        
        if (!in_array($id, $modulosArray)) {
            $modulosArray[] = $id;
            $nuevoTotal = $this->calculateTotal($modulosArray);
            
            $suscripcion->update([
                'modulos_activos' => $modulosArray,
                'precio_calculado' => $nuevoTotal,
            ]);
            
            // Sync with backend local rubro config (tenant scope)
            \App\Models\Tenant\RubroConfig::first()->update(['modulos_activos' => $modulosArray]);
        }

        return response()->json(['message' => 'Modulo activado', 'modulos' => $modulosArray]);
    }

    /**
     * Deactivate a module.
     */
    public function desactivar(Request $request, $id): JsonResponse
    {
        $suscripcion = Subscription::where('tenant_id', tenant('id'))->latest()->first();
        $modulosArray = array_values(array_filter($suscripcion->modulos_activos ?? [], fn($v) => $v !== $id));
        
        $nuevoTotal = $this->calculateTotal($modulosArray);
        
        $suscripcion->update([
            'modulos_activos' => $modulosArray,
            'precio_calculado' => $nuevoTotal,
        ]);
        
        \App\Models\Tenant\RubroConfig::first()->update(['modulos_activos' => $modulosArray]);

        return response()->json(['message' => 'Modulo desactivado', 'modulos' => $modulosArray]);
    }
    
    // Internal helper
    private function calculateTotal(array $modulosActivos): int
    {
        if (empty($modulosActivos)) return 19990;
        $modulosData = PlanModulo::whereIn('modulo_id', $modulosActivos)->where('es_base', false)->get();
        return 19990 + $modulosData->sum('precio_mensual');
    }
}
