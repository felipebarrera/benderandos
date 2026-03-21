<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RubroConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigRubroController extends Controller
{
    /**
     * Obtener la configuración actual del rubro
     */
    public function index(): JsonResponse
    {
        $config = RubroConfig::first();

        if (!$config) {
            // Si no existe, devolver uno por defecto (retail)
            $config = RubroConfig::create([
                'industria_preset' => 'retail',
                'industria_nombre' => 'Retail / Abarrotes',
                'modulos_activos' => ['M01', 'M02', 'M03', 'M04', 'M11', 'M12', 'M17', 'M18', 'M20', 'M24', 'M25', 'M32'],
            ]);
        }

        return response()->json($config);
    }

    /**
     * Actualizar la configuración actual
     */
    public function update(Request $request): JsonResponse
    {
        $config = RubroConfig::firstOrFail();
        
        $config->update($request->all());

        return response()->json([
            'message' => 'Configuración actualizada con éxito',
            'config'  => $config
        ]);
    }

    /**
     * Activar/Desactivar un módulo específico
     */
    public function toggleModulo(Request $request, string $moduloId): JsonResponse
    {
        $config = RubroConfig::firstOrFail();
        $modulos = $config->modulos_activos;

        if (in_array($moduloId, $modulos)) {
            // Desactivar (evitar desactivar M01 que es base)
            if ($moduloId === 'M01') {
                return response()->json(['message' => 'El módulo M01 es base y no puede ser desactivado'], 422);
            }
            $modulos = array_values(array_diff($modulos, [$moduloId]));
            $mensaje = "Módulo {$moduloId} desactivado";
        } else {
            // Activar
            $modulos[] = $moduloId;
            $mensaje = "Módulo {$moduloId} activado";
        }

        $config->update(['modulos_activos' => array_unique($modulos)]);

        return response()->json([
            'message' => $mensaje,
            'modulos' => $config->modulos_activos
        ]);
    }

    /**
     * Aplicar un preset de industria completo
     */
    public function aplicarPreset(string $presetId): JsonResponse
    {
        // En una implementación real, buscaríamos en una tabla maestra o archivo de config.
        // Por ahora, como el seeder ya pobló la tabla con todos los presets, 
        // simplemente buscamos el que tenga ese industria_preset y lo copiamos al registro activo (id=1).
        
        $preset = RubroConfig::where('industria_preset', $presetId)->first();

        if (!$preset) {
            return response()->json(['message' => 'Preset de industria no encontrado'], 404);
        }

        $activo = RubroConfig::first() ?: new RubroConfig();
        
        // Copiamos los atributos del preset al registro activo
        $data = $preset->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        $activo->fill($data);
        $activo->save();

        return response()->json([
            'message' => "Preset '{$presetId}' aplicado con éxito",
            'config'  => $activo
        ]);
    }
}
