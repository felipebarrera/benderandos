# Crear tenants demo para todas las industrias

## Objetivo

Crear 7 tenants de prueba, uno por industria, con datos suficientes para que todos los módulos del spider respondan correctamente y para poder probar la UI de cada rubro.

---

## Tenants a crear

| Industria | Slug | Dominio | Email admin |
|---|---|---|---|
| Legal / Abogado | `demo-legal` | `demo-legal.localhost` | `admin@demo-legal.cl` |
| Pádel / Deporte | `demo-padel` | `demo-padel.localhost` | `admin@demo-padel.cl` |
| Motel / Hospedaje | `demo-motel` | `demo-motel.localhost` | `admin@demo-motel.cl` |
| Abarrotes / Almacén | `demo-abarrotes` | `demo-abarrotes.localhost` | `admin@demo-abarrotes.cl` |
| Ferretería / Mayorista | `demo-ferreteria` | `demo-ferreteria.localhost` | `admin@demo-ferreteria.cl` |
| Médico / Clínica | `demo-medico` | `demo-medico.localhost` | `admin@demo-medico.cl` |
| SaaS / BenderAnd | `demo-saas` | `demo-saas.localhost` | `admin@demo-saas.cl` |

Contraseña unificada para todos: `demo1234`

---

## Seeder: `DemoTenantsSeeder`

Crear `database/seeders/DemoTenantsSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Domain;

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
```

---

## Seeder de datos por tenant: `TenantDemoDataSeeder`

Crear `database/seeders/TenantDemoDataSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Empleado;

class TenantDemoDataSeeder extends Seeder
{
    public function run(
        string $rubro,
        string $email,
        array $modulos,
        string $nombre
    ): void {
        // 1. Usuario admin
        Usuario::firstOrCreate(['email' => $email], [
            'nombre'   => 'Admin Demo',
            'password' => Hash::make('demo1234'),
            'rol'      => 'admin',
            'activo'   => true,
        ]);

        // 2. Config de rubro
        \DB::table('rubros_config')->updateOrInsert(
            ['id' => 1],
            [
                'rubro'           => $rubro,
                'nombre_negocio'  => $nombre,
                'modulos_activos' => json_encode($modulos),
                'tiene_stock_fisico' => in_array($rubro, ['abarrotes','ferreteria','padel','motel']) ? 1 : 0,
                'tiene_renta'     => in_array($rubro, ['motel','padel']) ? 1 : 0,
                'tiene_servicios' => 1,
            ]
        );

        // 3. Clientes de prueba
        for ($i = 1; $i <= 5; $i++) {
            Cliente::firstOrCreate(['email' => "cliente{$i}@demo.cl"], [
                'nombre'   => "Cliente Demo {$i}",
                'telefono' => "+5691234567{$i}",
                'rut'      => "12345678-{$i}",
            ]);
        }

        // 4. Productos (si aplica)
        if (in_array($rubro, ['abarrotes','ferreteria','padel','motel'])) {
            $productos = [
                ['nombre' => 'Producto Demo 1', 'precio' => 10000, 'stock' => 100, 'sku' => 'DEMO-001'],
                ['nombre' => 'Producto Demo 2', 'precio' => 25000, 'stock' => 50,  'sku' => 'DEMO-002'],
                ['nombre' => 'Producto Demo 3', 'precio' => 5000,  'stock' => 200, 'sku' => 'DEMO-003'],
            ];
            foreach ($productos as $p) {
                Producto::firstOrCreate(['sku' => $p['sku']], $p + [
                    'tipo'   => 'producto',
                    'activo' => true,
                ]);
            }
        }

        // 5. Proveedor (para módulo compras)
        Proveedor::firstOrCreate(['email' => 'proveedor@demo.cl'], [
            'nombre'   => 'Proveedor Demo',
            'telefono' => '+56912345678',
            'rut'      => '76543210-K',
        ]);

        // 6. Empleado (para módulo RRHH)
        if (class_exists(Empleado::class)) {
            Empleado::firstOrCreate(['email' => 'empleado@demo.cl'], [
                'nombre'       => 'Empleado Demo',
                'cargo'        => 'Operario',
                'sueldo_base'  => 500000,
                'fecha_inicio' => now()->subMonths(6),
                'activo'       => true,
            ]);
        }

        // 7. Config SII mínima
        if (\Schema::hasTable('config_sii')) {
            \DB::table('config_sii')->updateOrInsert(
                ['id' => 1],
                [
                    'rut_empresa'  => '76123456-7',
                    'nombre_empresa' => $nombre,
                    'ambiente'     => 'certificacion',
                    'activo'       => false, // desactivado por defecto en demo
                ]
            );
        }

        // 8. Config bot mínima
        if (\Schema::hasTable('config_bot')) {
            \DB::table('config_bot')->updateOrInsert(
                ['id' => 1],
                [
                    'telefono'   => '+56912345678',
                    'activo'     => false,
                    'nombre_bot' => 'Bot Demo',
                ]
            );
        }
    }
}
```

---

## Ejecutar

```bash
# Dentro del contenedor:
docker exec benderandos_app php artisan db:seed --class=DemoTenantsSeeder

# Verificar tenants creados:
docker exec benderandos_app php artisan tinker --execute="echo App\Models\Tenant::count() . ' tenants';"
```

---

## /etc/hosts — agregar dominios demo

En la máquina host (y en el contenedor si aplica):

```bash
# Agregar al /etc/hosts del host:
echo "127.0.0.1 demo-legal.localhost" | sudo tee -a /etc/hosts
echo "127.0.0.1 demo-padel.localhost" | sudo tee -a /etc/hosts
echo "127.0.0.1 demo-motel.localhost" | sudo tee -a /etc/hosts
echo "127.0.0.1 demo-abarrotes.localhost" | sudo tee -a /etc/hosts
echo "127.0.0.1 demo-ferreteria.localhost" | sudo tee -a /etc/hosts
echo "127.0.0.1 demo-medico.localhost" | sudo tee -a /etc/hosts
echo "127.0.0.1 demo-saas.localhost" | sudo tee -a /etc/hosts
# Mantener el demo original:
echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts
```

---

## Actualizar Spider para usar tenant demo completo

En el sidebar del Spider QA, cambiar el **Tenant URL** de:
```
http://demo.localhost:8000
```
a:
```
http://demo-ferreteria.localhost:8000
```

Ferretería es el tenant más completo (tiene stock, compras, proveedores, delivery, RRHH). Con ese tenant el spider debería pasar la mayoría de los checks.

O alternativamente, el Spider puede testear contra cada tenant por separado según el módulo. Para eso el JSON necesitaría un campo `tenant_key` además de `url_key`.

---

## Criterio de aceptación

- `php artisan db:seed --class=DemoTenantsSeeder` corre sin errores
- Cada tenant responde en su dominio: `http://demo-ferreteria.localhost:8000/api/dashboard` devuelve 401 sin token
- Con token devuelve 200
- Spider con Tenant URL = `demo-ferreteria.localhost:8000` pasa >85% de checks
