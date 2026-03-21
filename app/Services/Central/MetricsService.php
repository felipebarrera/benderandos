<?php

namespace App\Services\Central;

use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    public function mrr(): int
    {
        return Subscription::where('estado', 'activa')
            ->sum('monto_clp');
    }

    public function mrrPorMes(): array
    {
        return DB::select("
            SELECT
                DATE_TRUNC('month', proximo_cobro) AS mes,
                SUM(monto_clp) AS mrr
            FROM subscriptions
            WHERE estado = 'activa'
            GROUP BY mes
            ORDER BY mes DESC
            LIMIT 12
        ");
    }

    public function tenantsActivos(): int
    {
        return Tenant::where('estado', 'activo')->count();
    }

    public function churnMesActual(): int
    {
        return Subscription::where('estado', 'cancelada')
            ->whereMonth('cancelada_at', now()->month)
            ->whereYear('cancelada_at', now()->year)
            ->count();
    }

    public function churnRate(): float
    {
        $activosInicio = Tenant::where('estado', 'activo')
            ->where('created_at', '<', now()->startOfMonth())
            ->count();
            
        if ($activosInicio === 0) return 0;

        $canceladosMes = Subscription::where('estado', 'cancelada')
            ->whereMonth('cancelada_at', now()->month)
            ->whereYear('cancelada_at', now()->year)
            ->count();

        return round(($canceladosMes / $activosInicio) * 100, 2);
    }

    public function crecimientoMensual(): array
    {
        return Tenant::selectRaw("DATE_TRUNC('month', created_at) as mes, COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupByRaw("DATE_TRUNC('month', created_at)")
            ->orderBy('mes')
            ->get()
            ->toArray();
    }

    public function crecimientoRate(): float
    {
        $total = Tenant::count();
        if ($total === 0) return 0;
        
        $nuevosMes = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        return round(($nuevosMes / $total) * 100, 1);
    }
}
