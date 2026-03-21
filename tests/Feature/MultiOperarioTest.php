<?php

namespace Tests\Feature;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Venta;
use App\Models\Tenant\Role;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiOperarioTest extends TestCase
{
    protected $tenant;
    protected $roles = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup tenant de prueba
        $this->tenant = Tenant::first() ?? Tenant::create(['id' => 'test-multi']);
        if (!$this->tenant->domains()->exists()) {
            $this->tenant->domains()->create(['domain' => 'multi.test']);
        }
        
        tenancy()->initialize($this->tenant);

        // Crear roles básicos si no existen y mapearlos
        $roleNames = ['admin', 'cajero', 'operario'];
        foreach ($roleNames as $name) {
            $this->roles[$name] = Role::firstOrCreate(['nombre' => $name], [
                'etiqueta' => ucfirst($name),
                'permisos' => [$name === 'admin' ? '*' : 'crear-venta']
            ]);
        }
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    public function test_dos_operarios_no_pueden_modificar_venta_simultaneamente()
    {
        // 1. Setup: Cliente, 2 Operarios (Juan y Pedro) y un Producto
        $cliente = Cliente::create([
            'nombre' => 'Cliente Test',
            'rut' => '12345678-9'
        ]);

        $juan = Usuario::create([
            'nombre' => 'Juan',
            'email' => "juan_".uniqid()."@test.com",
            'clave_hash' => Hash::make('password'),
            'rol' => 'operario',
            'role_id' => $this->roles['operario']->id,
            'activo' => true
        ]);

        $pedro = Usuario::create([
            'nombre' => 'Pedro',
            'email' => "pedro_".uniqid()."@test.com",
            'clave_hash' => Hash::make('password'),
            'rol' => 'operario',
            'role_id' => $this->roles['operario']->id,
            'activo' => true
        ]);

        $producto = Producto::create([
            'nombre' => 'Producto 1',
            'valor_venta' => 1000,
            'cantidad' => 10,
            'estado' => 'activo'
        ]);
        
        // 2. Juan crea una venta abierta para el cliente
        $venta = Venta::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $juan->id,
            'estado' => 'abierta'
        ]);

        // 3. Pedro intenta agregar un item a la venta (debería poder si está abierta)
        $this->actingAs($pedro)
            ->post("http://multi.test/api/ventas/{$venta->id}/items", [
                'producto_id' => $producto->id,
                'cantidad' => 1
            ])->assertStatus(201);

        // 4. Un admin toma la venta (en_caja)
        $admin = Usuario::create([
            'nombre' => 'Admin',
            'email' => "admin_".uniqid()."@test.com",
            'clave_hash' => Hash::make('password'),
            'rol' => 'admin',
            'role_id' => $this->roles['admin']->id,
            'activo' => true
        ]);

        $this->actingAs($admin)
            ->put("http://multi.test/api/ventas/{$venta->id}/estado", ['id' => $venta->id])
            ->assertStatus(200);

        // 5. Pedro intenta agregar otro item a la misma venta (debería fallar porque está en_caja)
        $this->actingAs($pedro)
            ->post("http://multi.test/api/ventas/{$venta->id}/items", [
                'producto_id' => $producto->id,
                'cantidad' => 1
            ])->assertStatus(404);
    }

    public function test_codigo_rapido_del_cliente_es_unico()
    {
        $c1 = Cliente::create(['nombre' => 'C1', 'rut' => 'R1_'.uniqid()]);
        $c2 = Cliente::create(['nombre' => 'C2', 'rut' => 'R2_'.uniqid()]);
        
        $this->assertNotNull($c1->codigo_rapido);
        $this->assertNotNull($c2->codigo_rapido);
        $this->assertNotEquals($c1->codigo_rapido, $c2->codigo_rapido);
    }
}
