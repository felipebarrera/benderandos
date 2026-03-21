<?php

namespace Tests\Feature\Central;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TenantSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_suspend_and_reactivate_tenant(): void
    {
        // 1. Setup Admin y Tenant
        $admin = \App\Models\User::factory()->create();
        $tenant = \App\Models\Central\Tenant::create(['id' => 'suspend-me', 'nombre' => 'Suspend Corp', 'estado' => 'activo']);
        $tenant->domains()->create(['domain' => 'suspend-me.test']);
        
        $this->actingAs($admin);

        // 2. Suspender via API
        $response = $this->putJson("/api/central/tenants/{$tenant->id}/suspender");
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'estado' => 'suspendido'
        ]);

        // 3. Verificar acceso bloqueado (Simulando estar en el tenant)
        // Necesitamos inicializar tenencia para que el middleware actúe
        tenancy()->initialize($tenant);
        
        $response = $this->postJson('http://suspend-me.test/auth/login', ['email' => 'any@test.com', 'password' => '123']);
        // El middleware CheckTenantStatus debería abortar con 403
        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Esta cuenta ha sido suspendida. Contacte con soporte.']);
        
        tenancy()->end();

        // 4. Reactivar via API
        $response = $this->putJson("/api/central/tenants/{$tenant->id}/reactivar");
        $response->assertStatus(200);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'estado' => 'activo'
        ]);
        
        // 5. Verificar acceso permitido
        tenancy()->initialize($tenant);
        
        \Illuminate\Support\Facades\DB::table('roles')->insert([
            'id' => 1,
            'nombre' => 'Administrador',
            'etiqueta' => 'admin',
            'permisos' => json_encode(['*'])
        ]);

        // Creamos un usuario en el tenant para simular sesion
        $tenantUser = \App\Models\Tenant\Usuario::create([
            'nombre' => 'Test',
            'email' => 'test@corp.cl',
            'clave_hash' => bcrypt('123'),
            'role_id' => 1,
            'rol' => 'admin'
        ]);
        $this->actingAs($tenantUser, 'sanctum');

        $response = $this->getJson('http://suspend-me.test/auth/me'); // Endpoint de auth de tenant
        $response->assertStatus(200);
        tenancy()->end();
    }
}
