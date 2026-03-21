<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Proveedor;
use App\Models\Tenant\Empleado;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TenantDemoDataSeeder extends Seeder
{
    public function run(
        string $rubro = '',
        string $email = '',
        array $modulos = [],
        string $nombre = ''
    ): void {
        // 1. Usuario admin
        Usuario::updateOrCreate(['email' => $email], [
            'nombre'     => 'Admin Demo',
            'clave_hash' => Hash::make('demo1234'),
            'rol'        => 'admin',
            'activo'     => true,
        ]);

        // 2. Config de rubro
        DB::table('rubros_config')->updateOrInsert(
            ['id' => 1],
            [
                'industria_preset'   => $rubro,
                'industria_nombre'   => $nombre,
                'modulos_activos'    => json_encode($modulos),
                'tiene_stock_fisico' => in_array($rubro, ['abarrotes','ferreteria','padel','motel']) ? 1 : 0,
                'tiene_renta'        => in_array($rubro, ['motel','padel']) ? 1 : 0,
                'tiene_servicios'    => 1,
            ]
        );

        // 3. Clientes de prueba
        for ($i = 1; $i <= 5; $i++) {
            Cliente::updateOrCreate(['email' => "cliente{$i}@demo.cl"], [
                'nombre'   => "Cliente Demo {$i}",
                'telefono' => "+5691234567{$i}",
                'rut'      => "12345678-{$i}",
            ]);
        }

        // 4. Productos (si aplica)
        if (in_array($rubro, ['abarrotes','ferreteria','padel','motel'])) {
            $productos = [
                ['nombre' => 'Producto Demo 1', 'valor_venta' => 10000, 'cantidad' => 100, 'codigo' => 'DEMO-001'],
                ['nombre' => 'Producto Demo 2', 'valor_venta' => 25000, 'cantidad' => 50,  'codigo' => 'DEMO-002'],
                ['nombre' => 'Producto Demo 3', 'valor_venta' => 5000,  'cantidad' => 200, 'codigo' => 'DEMO-003'],
            ];
            foreach ($productos as $p) {
                Producto::updateOrCreate(['codigo' => $p['codigo']], $p + [
                    'tipo_producto' => 'stock_fisico',
                    'estado'        => 'activo',
                ]);
            }
        }

        // 5. Proveedor (para módulo compras)
        Proveedor::updateOrCreate(['rut' => '76543210-K'], [
            'nombre'   => 'Proveedor Demo',
            'telefono' => '+56912345678',
            'email'    => 'proveedor@demo.cl',
            'activo'   => true,
        ]);

        // 6. Empleado (para módulo RRHH)
        if (Schema::hasTable('empleados')) {
            Empleado::updateOrCreate(['rut' => '19123456-7'], [
                'nombre'        => 'Empleado Demo',
                'email'         => 'empleado@demo.cl',
                'cargo'         => 'Operario',
                'sueldo_base'   => 500000,
                'fecha_ingreso' => now()->subMonths(6),
                'activo'        => true,
            ]);
        }

        // 7. Config SII mínima
        if (Schema::hasTable('config_sii')) {
            DB::table('config_sii')->updateOrInsert(
                ['id' => 1],
                [
                    'rut_empresa'  => '76123456-7',
                    'razon_social' => $nombre,
                    'ambiente'     => 'certificacion',
                ]
            );
        }

        // 8. Config bot mínima
        if (Schema::hasTable('bot_config')) {
            DB::table('bot_config')->updateOrInsert(
                ['id' => 1],
                [
                    'whatsapp_numero' => '+56912345678',
                    'activo'          => false,
                    'nombre_bot'      => 'Bot Demo',
                ]
            );
        }
    }
}
