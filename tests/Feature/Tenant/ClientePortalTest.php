<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Cliente;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Venta;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientePortalTest extends TestCase
{
    protected $tenant;
    protected $usuario;
    protected $cliente;
    protected $producto;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup simple para tenant de prueba
        $this->tenant = Tenant::first() ?? Tenant::create(['id' => 'test-portal']);
        if (!$this->tenant->domains()->exists()) {
            $this->tenant->domains()->create(['domain' => 'portal.test']);
        }
        
        tenancy()->initialize($this->tenant);

        // Crear usuario cliente de prueba
        $this->usuario = Usuario::create([
            'nombre' => 'Juan Cliente',
            'email' => 'juan@cliente.cl',
            'clave_hash' => Hash::make('password'),
            'rol' => 'cliente',
            'activo' => true
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Juan Cliente',
            'email' => 'juan@cliente.cl',
            'usuario_id' => $this->usuario->id
        ]);

        $this->producto = Producto::create([
            'nombre' => 'Producto de Prueba',
            'precio' => 10000,
            'stock' => 50,
            'activo' => true
        ]);
        
        tenancy()->end();
    }

    public function test_cliente_puede_loguearse_en_portal()
    {
        $response = $this->post("http://portal.test/portal/login", [
            'login' => 'juan@cliente.cl',
            'password' => 'password'
        ]);

        $response->assertRedirect(route('portal.catalogo'));
        $this->assertAuthenticatedAs($this->usuario);
    }

    public function test_cliente_puede_ver_catalogo()
    {
        $this->actingAs($this->usuario);
        
        $response = $this->get("http://portal.test/portal/catalogo");

        $response->assertStatus(200);
        $response->assertSee('Producto de Prueba');
    }

    public function test_cliente_puede_crear_pedido_remoto()
    {
        $this->actingAs($this->usuario);

        $response = $this->post("http://portal.test/portal/pedido", [
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 2]
            ],
            'tipo_entrega' => 'retiro'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $this->cliente->id,
            'estado' => 'remota_pendiente',
            'total' => 20000
        ], 'pgsql');
    }

    public function test_cliente_puede_iniciar_pago_webpay()
    {
        $this->actingAs($this->usuario);

        $venta = Venta::create([
            'cliente_id' => $this->cliente->id,
            'estado' => 'remota_pendiente',
            'total' => 20000,
            'origen' => 'web'
        ]);

        $response = $this->post("http://portal.test/portal/pedido/{$venta->uuid}/pagar");

        $response->assertStatus(200);
        $response->assertJsonStructure(['url', 'token']);
    }
}
