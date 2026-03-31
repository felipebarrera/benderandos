<?php
namespace App\Console\Commands\Agenda;

use Illuminate\Console\Command;
use App\Services\AgendaAutoRegistroService;

class InicializarRecursosCommand extends Command
{
    protected $signature   = 'agenda:init-recursos {--tenant= : Slug del tenant específico}';
    protected $description = 'Crea AgendaRecursos automáticos para operarios y productos de renta (M08)';

    public function handle(AgendaAutoRegistroService $svc): int
    {
        $tenantSlug = $this->option('tenant');

        if ($tenantSlug) {
            $tenants = [\App\Models\Central\Tenant::find($tenantSlug)];
        } else {
            $tenants = \App\Models\Central\Tenant::all();
        }

        foreach ($tenants as $tenant) {
            if (!$tenant) continue;
            $this->line("→ Procesando tenant: {$tenant->id}");
            tenancy()->initialize($tenant);

            if (!$svc->m08Activo()) {
                $this->warn("  M08 no activo en {$tenant->id} — saltando");
                tenancy()->end();
                continue;
            }

            $r = $svc->inicializarTenant();
            $this->info("  ✓ {$r['operarios_registrados']} operarios, {$r['productos_registrados']} productos de renta");
            tenancy()->end();
        }

        $this->info('Listo.');
        return self::SUCCESS;
    }
}
