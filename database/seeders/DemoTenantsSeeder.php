<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Central\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

class DemoTenantsSeeder extends Seeder
{
    public function run(): void
    {
        $industrias = [
            [
                'id'       => 'demo-legal',
                'slug'     => 'demo-legal',
                'nombre'   => 'Bufete Demo Legal',
                'rubro'    => 'legal',
                'dominio'  => 'demo-legal.localhost',
                'email'    => 'admin@demo-legal.cl',
                'modulos'  => ['M01','M07','M08','M09','M10','M20','M21','M32'],
            ],
            [
                'id'       => 'demo-padel',
                'slug'     => 'demo-padel',
                'nombre'   => 'Club Pádel Demo',
                'rubro'    => 'padel',
                'dominio'  => 'demo-padel.localhost',
                'email'    => 'admin@demo-padel.cl',
                'modulos'  => ['M01','M05','M06','M03','M08','M17','M30','M32'],
            ],
            [
                'id'       => 'demo-motel',
                'slug'     => 'demo-motel',
                'nombre'   => 'Motel Demo',
                'rubro'    => 'motel',
                'dominio'  => 'demo-motel.localhost',
                'email'    => 'admin@demo-motel.cl',
                'modulos'  => ['M01','M05','M06','M03','M14'],
            ],
            [
                'id'       => 'demo-abarrotes',
                'slug'     => 'demo-abarrotes',
                'nombre'   => 'Almacén Demo',
                'rubro'    => 'abarrotes',
                'dominio'  => 'demo-abarrotes.localhost',
                'email'    => 'admin@demo-abarrotes.cl',
                'modulos'  => ['M01','M02','M03','M04','M11','M12','M17','M18','M20','M24','M25','M32'],
            ],
            [
                'id'       => 'demo-ferreteria',
                'slug'     => 'demo-ferreteria',
                'nombre'   => 'Ferretería Demo',
                'rubro'    => 'ferreteria',
                'dominio'  => 'demo-ferreteria.localhost',
                'email'    => 'admin@demo-ferreteria.cl',
                'modulos'  => ['M01','M02','M03','M04','M07','M11','M17','M18','M19','M20','M24','M26','M32'],
            ],
            [
                'id'       => 'demo-medico',
                'slug'     => 'demo-medico',
                'nombre'   => 'Clínica Demo',
                'rubro'    => 'medico',
                'dominio'  => 'demo-medico.localhost',
                'email'    => 'admin@demo-medico.cl',
                'modulos'  => ['M01','M07','M08','M09','M10','M20','M21','M32'],
            ],
            [
                'id'       => 'demo-saas',
                'slug'     => 'demo-saas',
                'nombre'   => 'BenderAnd Demo SaaS',
                'rubro'    => 'saas',
                'dominio'  => 'demo-saas.localhost',
                'email'    => 'admin@demo-saas.cl',
                'modulos'  => ['M01','M07','M20','M21','M22','M23','M24','M25','M27','M31','M32'],
            ],
        ];

        foreach ($industrias as $data) {
            // Crear tenant si no existe
            $tenant = Tenant::firstOrCreate(['id' => $data['id']], [
                'nombre' => $data['nombre'],
                'estado' => 'activo',
            ]);

            // Crear dominio
            Domain::firstOrCreate(['domain' => $data['dominio']], [
                'tenant_id' => $tenant->id,
            ]);

            // Inicializar tenant e insertar datos
            tenancy()->initialize($tenant);

            // Aplicar preset del rubro
            $this->call(TenantDemoDataSeeder::class, true, [
                'rubro'   => $data['rubro'],
                'email'   => $data['email'],
                'modulos' => $data['modulos'],
                'nombre'  => $data['nombre'],
            ]);

            tenancy()->end();
        }
    }
}
