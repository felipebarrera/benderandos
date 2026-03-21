<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Venta;
use App\Models\Tenant\Deuda;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientePortalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Usuario Cliente
        $usuario = Usuario::updateOrCreate(
            ['email' => 'cliente@test.com'],
            [
                'nombre' => 'Juan Pérez Test',
                'clave_hash' => Hash::make('password123'),
                'rol' => 'cliente',
                'activo' => true,
            ]
        );

        // 2. Crear Cliente vinculado
        $cliente = Cliente::updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'nombre' => $usuario->nombre,
                'rut' => '12.345.678-9',
                'email' => $usuario->email,
                'telefono' => '+56912345678',
            ]
        );

        // 3. Asegurar productos con stock
        if (Producto::count() == 0) {
            Producto::create([
                'nombre' => 'Producto de Prueba 1',
                'descripcion' => 'Descripción del producto 1',
                'valor_venta' => 10000,
                'cantidad' => 50,
                'estado' => 'activo',
                'tipo_producto' => 'stock_fisico',
            ]);
            Producto::create([
                'nombre' => 'Producto de Prueba 2',
                'descripcion' => 'Descripción del producto 2',
                'valor_venta' => 5000,
                'cantidad' => 20,
                'estado' => 'activo',
                'tipo_producto' => 'stock_fisico',
            ]);
        }

        // 4. Crear una venta pagada (historial)
        $ventaPagada = Venta::create([
            'uuid' => (string) Str::uuid(),
            'cliente_id' => $cliente->id,
            'usuario_id' => 1, // Admin
            'estado' => 'pagada',
            'total' => 15000,
            'pagado_at' => now()->subDays(2),
        ]);

        // 5. Crear una venta pendiente de pago (deuda)
        $ventaPendiente = Venta::create([
            'uuid' => (string) Str::uuid(),
            'cliente_id' => $cliente->id,
            'usuario_id' => 1,
            'estado' => 'remota_pendiente',
            'total' => 10000,
        ]);

        Deuda::create([
            'venta_id' => $ventaPendiente->id,
            'cliente_id' => $cliente->id,
            'valor' => 10000,
            'pagada' => false,
            'vencimiento_at' => now()->addDays(5),
        ]);
    }
}
