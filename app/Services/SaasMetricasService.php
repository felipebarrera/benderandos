<?php

namespace App\Services;

use App\Models\Tenant\SaasCliente;
use App\Models\Tenant\SaasMetrica;
use App\Models\Tenant\SaasPlan;
use Illuminate\Support\Facades\DB;

class SaasMetricasService
{
    /**
     * Calcula y guarda las métricas del día actual.
     * Esto debería correr en un Job programado a medianoche.
     */
    public function snapshotDiario(): SaasMetrica
    {
        $hoy = today();

        // Tenants activos = excluyendo trial, cancelados y suspendidos
        $activos = SaasCliente::where('estado', 'activo')->count();
        $trials = SaasCliente::where('estado', 'trial')->count();
        $morosos = SaasCliente::where('estado', 'moroso')->count();

        // MRR = Suma del 'precio_actual' de todos los activos + morosos (siguen facturando hasta suspenderse)
        $mrr = SaasCliente::whereIn('estado', ['activo', 'moroso'])->sum('precio_actual');
        $arr = $mrr * 12;

        // Nuevos vs Cancelados en el mes en curso
        $nuevosMes = SaasCliente::whereYear('created_at', $hoy->year)
            ->whereMonth('created_at', $hoy->month)->count();
            
        $canceladosMes = SaasCliente::where('estado', 'cancelado')
            ->whereYear('updated_at', $hoy->year)
            ->whereMonth('updated_at', $hoy->month)->count();

        $totalAInicioDeMes = SaasCliente::whereIn('estado', ['activo', 'moroso', 'suspendido'])
            ->where('created_at', '<', $hoy->copy()->startOfMonth())->count();

        $churnRate = $totalAInicioDeMes > 0 ? round(($canceladosMes / $totalAInicioDeMes) * 100, 2) : 0;
        $arpu = $activos > 0 ? round($mrr / $activos) : 0;

        return SaasMetrica::updateOrCreate(
            ['fecha' => $hoy],
            [
                'mrr'             => $mrr,
                'arr'             => $arr,
                'tenants_activos' => $activos,
                'tenants_trial'   => $trials,
                'tenants_morosos' => $morosos,
                'nuevos_mes'      => $nuevosMes,
                'cancelados_mes'  => $canceladosMes,
                'churn_rate'      => $churnRate,
                'arpu'            => $arpu,
                // LTV = ARPU / Churn Rate (simplificado). Si es 0, estimamos 24 meses (basado en industria)
                'ltv_promedio'    => $churnRate > 0 ? round($arpu / ($churnRate / 100)) : ($arpu * 24),
            ]
        );
    }

    /**
     * Devuelve la data para los gráficos del dashboard ejecutivo
     */
    public function getDashboardData(): array
    {
        $ultima = SaasMetrica::latest('fecha')->first() ?? $this->snapshotDiario();

        // Distribución de planes
        $planes = DB::table('saas_clientes')
            ->join('saas_planes', 'saas_clientes.plan_id', '=', 'saas_planes.id')
            ->select('saas_planes.nombre', DB::raw('count(*) as cantidad'), DB::raw('sum(saas_clientes.precio_actual) as mrr'))
            ->whereIn('saas_clientes.estado', ['activo', 'moroso'])
            ->groupBy('saas_planes.id', 'saas_planes.nombre')
            ->get();

        // Distribución de rubros/industrias
        $rubros = SaasCliente::select('industria', DB::raw('count(*) as cantidad'))
            ->whereIn('estado', ['activo', 'moroso', 'trial'])
            ->groupBy('industria')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();

        // MRR últimos 6 meses para gráfico
        $historico = SaasMetrica::orderBy('fecha', 'desc')->limit(180)->get()->groupBy(function($item) {
            return $item->fecha->format('Y-m'); // Agrupar por mes
        })->map(function($mes) {
            return $mes->last(); // Tomar el último día del mes
        })->take(6)->values();

        return [
            'kpis' => $ultima,
            'distribucion_planes' => $planes,
            'distribucion_rubros' => $rubros,
            'grafico_mrr' => $historico->reverse()->values() // cronológico
        ];
    }
}
