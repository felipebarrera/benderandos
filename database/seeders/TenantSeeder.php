<?php

namespace Database\Seeders;

use App\Models\Tenant\Role;
use App\Models\Tenant\TipoPago;
use App\Models\Tenant\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Tipos de pago por defecto
        $tiposPago = ['Efectivo', 'Débito', 'Crédito', 'Transferencia'];
        foreach ($tiposPago as $nombre) {
            TipoPago::firstOrCreate(['nombre' => $nombre]);
        }

        // Roles base (Hito 2)
        $roles = [
            ['nombre' => 'admin',    'etiqueta' => 'Administrador', 'permisos' => ['*']],
            ['nombre' => 'cajero',   'etiqueta' => 'Cajero',        'permisos' => ['ver:productos', 'crear:venta', 'confirmar:venta', 'ver:clientes', 'crear:cliente']],
            ['nombre' => 'operario', 'etiqueta' => 'Vendedor',      'permisos' => ['ver:productos', 'agregar:item-venta', 'ver:clientes']],
            ['nombre' => 'bodega',   'etiqueta' => 'Bodeguero',     'permisos' => ['ver:productos', 'editar:stock', 'ver:compras', 'crear:compra']],
        ];

        foreach ($roles as $data) {
            Role::firstOrCreate(
                ['nombre' => $data['nombre']],
                ['etiqueta' => $data['etiqueta'], 'permisos' => $data['permisos']]
            );
        }

        // Rol admin para asignar al usuario
        $rolAdmin = Role::where('nombre', 'admin')->first();

        // Usuario admin inicial
        Usuario::firstOrCreate(
            ['email' => 'admin@benderand.cl'],
            [
                'nombre'    => 'Administrador',
                'clave_hash' => Hash::make('admin1234'),
                'rol'       => 'admin',
                'role_id'   => $rolAdmin?->id,
                'activo'    => true,
            ]
        );
    }
}
