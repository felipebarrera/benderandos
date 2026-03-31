<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\OnboardingProgress;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Venta;
use App\Models\Tenant\ProfesionalConfig;
use App\Models\Tenant\RubroConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnboardingController extends Controller
{
    /**
     * GET /api/universal/onboarding/progress
     */
    public function progress()
    {
        $this->detectarCompletados();

        try {
            // Re-seed if necessary to match active modules
            $this->seedDynamicDefaults();
            
            $steps = OnboardingProgress::orderBy('id')->get();

            $total = $steps->count();
            $completados = $steps->whereIn('estado', ['completado', 'saltado'])->count();

            return response()->json([
                'steps'               => $steps,
                'total'               => $total,
                'completados'         => $completados,
                'onboarding_completo' => $total > 0 && $completados >= $total,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'steps'               => [],
                'total'               => 0,
                'completados'         => 0,
                'onboarding_completo' => false,
                'error'               => $e->getMessage()
            ]);
        }
    }

    public function completeStep($id)
    {
        $step = OnboardingProgress::where('step_id', $id)->firstOrFail();
        $step->update([
            'estado'        => 'completado',
            'completado_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function saltarStep($id)
    {
        $step = OnboardingProgress::where('step_id', $id)->firstOrFail();
        $step->update([
            'estado'        => 'saltado',
            'completado_at' => now(),
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * Build dynamic step list based on active modules
     */
    private function seedDynamicDefaults(): void
    {
        $config = RubroConfig::first();
        $modulos = $config->modulos_activos ?? [];

        $targetSteps = [
            'crear_cuenta' => ['titulo' => 'Crear tu cuenta', 'desc' => 'Registro inicial completado'],
        ];

        // Industry Neutral Base Steps
        $targetSteps['primer_producto'] = ['titulo' => 'Agregar primer producto', 'desc' => 'Crea un producto o servicio en tu catálogo'];
        $targetSteps['primer_cliente']  = ['titulo' => 'Registrar un cliente', 'desc' => 'Registra al menos un cliente'];
        $targetSteps['primera_venta']   = ['titulo' => 'Realizar primera venta', 'desc' => 'Completa una venta desde el POS'];

        // Module Specific Steps
        if (in_array('M08', $modulos)) {
            $targetSteps['agregar_profesionales'] = ['titulo' => 'Configurar Profesionales', 'desc' => 'Agrega al menos un profesional a tu equipo'];
            $targetSteps['configurar_horarios']    = ['titulo' => 'Definir Horarios', 'desc' => 'Configura los horarios de atención'];
            $targetSteps['primera_cita']           = ['titulo' => 'Agendar Cita', 'desc' => 'Crea tu primera cita en la agenda'];
        }

        if (in_array('M20', $modulos)) {
            $targetSteps['configurar_sii'] = ['titulo' => 'Facturación SII', 'desc' => 'Conecta tu certificado digital'];
        }

        if (in_array('M17', $modulos) || in_array('M21', $modulos)) {
            $targetSteps['activar_whatsapp'] = ['titulo' => 'Activar WhatsApp', 'desc' => 'Conecta tu número para recibir pedidos'];
        }

        // 1. Delete steps that are no longer relevant (module deactivated)
        OnboardingProgress::whereNotIn('step_id', array_keys($targetSteps))->delete();

        // 2. Create or Update existing steps
        foreach ($targetSteps as $sid => $data) {
            OnboardingProgress::updateOrCreate(
                ['step_id' => $sid],
                [
                    'titulo'      => $data['titulo'],
                    'descripcion' => $data['desc'],
                    // 'estado' preserves current value if exists
                ]
            );
        }
    }

    private function detectarCompletados(): void
    {
        try {
            $checks = [
                'crear_cuenta' => true,
                'primer_producto' => Producto::exists(),
                'primer_cliente' => Cliente::exists(),
                'primera_venta' => Venta::where('estado', 'completada')->exists(),
                'agregar_profesionales' => Usuario::whereIn('rol', ['admin', 'operario', 'profesional'])->count() > 1,
                'configurar_horarios' => DB::table('agenda_horarios')->where('activo', true)->exists() 
                                      || ProfesionalConfig::whereNotNull('horario_json')->exists(),
                'primera_cita' => DB::table('agenda_citas')->exists(),
                'configurar_sii' => DB::table('sii_configs')->where('activo', true)->exists(),
                'activar_whatsapp' => DB::table('config_bots')->where('activo', true)->exists(),
            ];

            foreach ($checks as $stepId => $isComplete) {
                if ($isComplete) {
                    OnboardingProgress::where('step_id', $stepId)
                        ->where('estado', 'pendiente')
                        ->update([
                            'estado'        => 'completado',
                            'completado_at' => now(),
                        ]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
