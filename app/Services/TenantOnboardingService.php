<?php

namespace App\Services;

use App\Models\Central\Tenant;
use App\Models\Tenant\Role;
use App\Models\Tenant\Usuario;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    /**
     * Crea un tenant, asocia su dominio, migra su base de datos
     * y crea el usuario admin inicial.
     *
     * @param array $datos
     * @return array
     * @throws \Exception
     */
    public function crear(array $datos): array
    {
        $slug = Str::slug($datos['nombre_empresa']);

        // Evitar duplicados del slug, añadiendo sufijo rand si es necesario
        if (Tenant::where('id', $slug)->exists()) {
            $slug .= '-' . Str::random(4);
        }

        // Envolvemos la creación en una transacción local sobre base central
        $tenant = DB::transaction(function () use ($datos, $slug) {
            
            // 1. Crear Tenant (Stancl Tenancy detectará esto y creará el schema automáticamente)
            $tenant = Tenant::create([
                'id'             => $slug,
                'nombre'         => $datos['nombre_empresa'],
                'slug'           => $slug,
                'rut_empresa'    => $datos['rut_empresa'] ?? null,
                'whatsapp_admin' => $datos['whatsapp_admin'],
                'email_admin'    => $datos['email_admin'],
                'estado'         => 'trial',
                'trial_hasta'    => now()->addDays(30),
                'rubro_config'   => $this->getRubroConfig($datos['rubro']),
            ]);

            // 2. Asociar dominio (ej. miempresa.benderand.cl)
            $tenant->domains()->create([
                'domain' => "{$slug}.benderand.cl",
            ]);

            // 3. Forzar corrida síncrona de las migraciones
            Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);

            // 4. Poblar datos básicos (Seeder)
            Artisan::call('tenants:seed', [
                '--tenants' => [$tenant->id],
                '--class'   => 'TenantSeeder' // Asegúrate de que este seeder exista en database/seeders/TenantSeeder.php
            ]);

            // 5. Inyectar el Administrador Inicial en la BD del nuevo Tenant
            tenancy()->initialize($tenant);

            $rolAdmin = Role::where('nombre', 'admin')->first();

            Usuario::updateOrCreate(
                ['email' => $datos['email_admin']],
                [
                    'nombre'     => $datos['nombre_admin'],
                    'whatsapp'   => $datos['whatsapp_admin'],
                    'clave_hash' => $datos['password_admin'], // Ya asume que es recibida (desde el JSON/bot, o generar un Hash::make)
                    'rol'        => 'admin',
                    'role_id'    => $rolAdmin?->id,
                    'activo'     => true,
                ]
            );

            tenancy()->end();

            return $tenant;
        });

        return [
            'slug'       => $tenant->id,
            'url'        => "https://{$tenant->id}.benderand.cl",
            'estado'     => 'trial',
            'dias_trial' => 30,
        ];
    }

    /**
     * Mapeo de opciones requeridas para el Rubro
     */
    private function getRubroConfig(string $rubro): array
    {
        $configs = [
            'retail' => ['usar_balanza' => true, 'usar_cajero' => true],
            'motel'  => ['usar_rentas' => true, 'mostrar_recepcion' => true],
            'padel'  => ['usar_rentas' => true, 'usar_canchas' => true],
            'medico' => ['usar_agendas' => true],
        ];

        return $configs[$rubro] ?? ['default' => true];
    }
}
