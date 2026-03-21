<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Central\Subscription;
use App\Models\Tenant\RubroConfig;

class CheckModuleAccess
{
    /**
     * Map of endpoint segments/prefixes to their required module ID.
     */
    const MODULE_GATES = [
        'rentas'         => 'M05',
        'rentas.hora'    => 'M06',
        'agenda'         => 'M08',
        'honorarios'     => 'M09',
        'notas'          => 'M10',
        'deudas'         => 'M11',
        'encargos'       => 'M12',
        'delivery'       => 'M13',
        'recursos'       => 'M14',
        'comandas'       => 'M15',
        'recetas'        => 'M16',
        'bot'            => 'M17', // WhatsApp
        'compras'        => 'M18',
        'inventario'     => 'M19',
        'dte'            => 'M20',
        'rrhh'           => 'M21',
        'liquidaciones'  => 'M22',
        'reclutamiento'  => 'M23',
        'qr'             => 'M24',
        'portal'         => 'M25',
        'membresias'     => 'M30',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $modulo = null): Response
    {
        // If a specific module isn't passed as a parameter, try to infer it from the route name or path
        if (!$modulo) {
            foreach (self::MODULE_GATES as $segment => $modId) {
                if ($request->is("api/{$segment}*") || $request->is("{$segment}*")) {
                    $modulo = $modId;
                    break;
                }
            }
        }

        // If this route doesn't require a specific module, allow access
        if (!$modulo) {
            return $next($request);
        }

        // 1. Verify that the module is in the tenant's active list
        $config = RubroConfig::first(); // Assumes we are inside tenant context
        
        if (!$config || !is_array($config->modulos_activos) || !in_array($modulo, $config->modulos_activos)) {
            return response()->json([
                'error'   => 'modulo_no_activo',
                'message' => 'Este módulo no está activo en tu plan.',
                'modulo'  => $modulo,
                'accion'  => 'activar_desde_config' 
            ], 403);
        }

        // 2. Verify that the subscription is up to date (or in trial/grace)
        $suscripcion = Subscription::where('tenant_id', tenant('id'))->latest()->first();

        if (!$suscripcion) {
            return response()->json(['error' => 'no_subscription', 'message' => 'Suscripción no encontrada.'], 402);
        }

        if (!$suscripcion->puedeOperar()) {
            return response()->json([
                'error'   => 'suscripcion_vencida',
                'message' => 'Tu suscripción requiere pago para continuar.',
                'dias_gracia_restantes' => $suscripcion->diasGraciaRestantes(),
                'link_pago' => $suscripcion->linkPago()
            ], 402);
        }

        return $next($request);
    }
}
