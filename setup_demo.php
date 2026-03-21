<?php
// Seed corregido con columnas reales del schema tenantdemo
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\Central\Tenant;

$tenant = Tenant::find('demo');
if (!$tenant) { echo "Tenant 'demo' no existe\n"; exit(1); }

tenancy()->initialize($tenant);

echo "Sembrando en schema tenantdemo...\n\n";

// Usuarios
$usuarios = [
    ['nombre' => 'Admin Demo',    'email' => 'admin@demo.cl',    'rut' => '11.111.111-1', 'rol' => 'admin',    'clave_hash' => Hash::make('admin123'),    'activo' => true],
    ['nombre' => 'Cajero Demo',   'email' => 'cajero@demo.cl',   'rut' => '22.222.222-2', 'rol' => 'cajero',   'clave_hash' => Hash::make('cajero123'),   'activo' => true],
    ['nombre' => 'Operario Demo', 'email' => 'operario@demo.cl', 'rut' => '33.333.333-3', 'rol' => 'operario', 'clave_hash' => Hash::make('operario123'), 'activo' => true],
];
foreach ($usuarios as $u) {
    \App\Models\Tenant\Usuario::firstOrCreate(['email' => $u['email']], $u);
    echo "  ✓ Usuario: {$u['nombre']}\n";
}

// Limpiar productos del seed anterior (que usó columnas incorrectas)
\Illuminate\Support\Facades\DB::table('productos')->truncate();

// Productos con columnas reales
$productos = [
    ['codigo' => 'P001', 'nombre' => 'Coca Cola 1.5L',     'tipo_producto' => 'stock_fisico', 'familia' => 'Bebidas',   'valor_venta' => 1490, 'costo' => 800,  'cantidad' => 50,  'cantidad_minima' => 5,  'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'P002', 'nombre' => 'Pan Marraqueta',      'tipo_producto' => 'stock_fisico', 'familia' => 'Panadería', 'valor_venta' => 150,  'costo' => 80,   'cantidad' => 200, 'cantidad_minima' => 20, 'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'P003', 'nombre' => 'Leche 1L',            'tipo_producto' => 'stock_fisico', 'familia' => 'Lácteos',  'valor_venta' => 990,  'costo' => 600,  'cantidad' => 30,  'cantidad_minima' => 10, 'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'P004', 'nombre' => 'Aceite Vegetal 1L',   'tipo_producto' => 'stock_fisico', 'familia' => 'Abarrotes','valor_venta' => 2490, 'costo' => 1500, 'cantidad' => 15,  'cantidad_minima' => 5,  'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'P005', 'nombre' => 'Arroz El Olivar 1kg', 'tipo_producto' => 'stock_fisico', 'familia' => 'Abarrotes','valor_venta' => 1290, 'costo' => 700,  'cantidad' => 40,  'cantidad_minima' => 10, 'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'P006', 'nombre' => 'Bolsa Plástica',      'tipo_producto' => 'stock_fisico', 'familia' => 'Insumos',  'valor_venta' => 10,   'costo' => 5,    'cantidad' => 500, 'cantidad_minima' => 50, 'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'P007', 'nombre' => 'Yoghurt Frutado',     'tipo_producto' => 'stock_fisico', 'familia' => 'Lácteos',  'valor_venta' => 590,  'costo' => 300,  'cantidad' => 2,   'cantidad_minima' => 5,  'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'S001', 'nombre' => 'Delivery Zona Norte', 'tipo_producto' => 'servicio',     'familia' => 'Servicios','valor_venta' => 2000, 'costo' => 0,    'cantidad' => 0,   'cantidad_minima' => 0,  'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'R001', 'nombre' => 'Cancha Pádel 1',      'tipo_producto' => 'renta',        'familia' => 'Rentas',   'valor_venta' => 8000, 'costo' => 0,    'cantidad' => 1,   'cantidad_minima' => 0,  'estado' => 'activo', 'unidad_medida' => 'un'],
    ['codigo' => 'R002', 'nombre' => 'Cancha Pádel 2',      'tipo_producto' => 'renta',        'familia' => 'Rentas',   'valor_venta' => 8000, 'costo' => 0,    'cantidad' => 1,   'cantidad_minima' => 0,  'estado' => 'activo', 'unidad_medida' => 'un'],
];
foreach ($productos as $p) {
    \App\Models\Tenant\Producto::create($p);
    echo "  ✓ Producto: [{$p['codigo']}] {$p['nombre']}\n";
}

// Clientes
$clientes = [
    ['nombre' => 'Juan Pérez',     'rut' => '12.345.678-9', 'telefono' => '+56912345678', 'email' => 'juan@demo.cl'],
    ['nombre' => 'María González', 'rut' => '98.765.432-1', 'telefono' => '+56987654321', 'email' => 'maria@demo.cl'],
    ['nombre' => 'Carlos López',   'rut' => '15.555.555-5', 'telefono' => '+56955555555'],
];
foreach ($clientes as $c) {
    \App\Models\Tenant\Cliente::firstOrCreate(['rut' => $c['rut']], $c);
    echo "  ✓ Cliente: {$c['nombre']}\n";
}

tenancy()->end();
echo "\nSeed completado.\n";
