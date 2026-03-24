<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Central\Tenant;

class ActivateTenantModules extends Command
{
    protected $signature = 'tenant:activate-modules {tenant} {modules*}';
    protected $description = 'Activate modules for a specific tenant';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        $modules = $this->argument('modules');
        
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' no existe");
            return 1;
        }
        
        $tenant->run(function() use ($modules) {
            $config = \App\Models\Tenant\RubroConfig::first();
            
            if (!$config) {
                $this->error("RubroConfig no existe para este tenant");
                return 1;
            }
            
            $current = $config->modulos_activos ?? [];
            $new = array_unique(array_merge($current, $modules));
            $config->modulos_activos = $new;
            $config->save();
            
            $this->info("Módulos activados para {$config->industria_preset}:");
            $this->line(json_encode($new, JSON_PRETTY_PRINT));
        });
        
        return 0;
    }
}
