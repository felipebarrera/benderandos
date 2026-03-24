<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Central\Tenant;

class TenantDomainSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            'demo-legal',
            'demo-padel',
            'demo-motel',
            'demo-abarrotes',
            'demo-ferreteria',
            'demo-medico',
            'demo-saas',
            'demo', // Tenant genérico para Spider QA
        ];

        foreach ($tenants as $tenantId) {
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->command->warn("Tenant {$tenantId} no existe");
                continue;
            }

            // Registrar dominio principal
            $domain = "{$tenantId}.localhost";

            $existingDomain = $tenant->domains()
                ->where('domain', $domain)
                ->first();

            if (!$existingDomain) {
                $tenant->domains()->create([
                    'domain' => $domain,
                    'priority' => 1,
                ]);
                $this->command->info("Dominio {$domain} registrado para tenant {$tenantId}");
            }
        }
    }
}