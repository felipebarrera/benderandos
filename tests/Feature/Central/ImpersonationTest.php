<?php

namespace Tests\Feature\Central;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_impersonate_tenant(): void
    {
        // 1. Setup Admin y Tenant
        $superAdmin = \App\Models\User::factory()->create();
        $tenant = \App\Models\Central\Tenant::create([
            'id' => 'target-tenant', 
            'nombre' => 'Target Corp'
        ]);
        $tenant->domains()->create(['domain' => 'target-tenant.localhost']);
        
        // Disparar migraciones del tenant MANUALMENTE en el test 
        \Illuminate\Support\Facades\Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);
        
        // Crear usuario y ROL dentro del tenant
        tenancy()->initialize($tenant);
        
        \Illuminate\Support\Facades\DB::table('roles')->insert([
            'id' => 1,
            'nombre' => 'Administrador',
            'etiqueta' => 'admin',
            'permisos' => json_encode(['*'])
        ]);
        
        \App\Models\Tenant\Usuario::create([
            'nombre' => 'Admin Tenant',
            'email' => 'admin@target-corp.cl',
            'clave_hash' => bcrypt('123'),
            'role_id' => 1,
            'rol' => 'admin'
        ]);
        tenancy()->end();
        
        $this->actingAs($superAdmin);

        // 2. Ejecutar impersonar via API
        $response = $this->postJson("/api/central/tenants/{$tenant->id}/impersonar");
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'url', 'message']);
        
        $token = $response->json('token');

        // 3. Verificar AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'tenant_id' => $tenant->id,
            'accion' => 'impersonar'
        ]);

        // 4. Verificar que el token funciona en el tenant
        tenancy()->initialize($tenant);
        
        // El token viene con el prefijo plano pero Laravel lo valida hasheado
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('http://target-tenant.test/auth/me'); 
        
        $response->assertStatus(200);
        $response->assertJsonPath('id', 1); // El primer usuario creado en el tenant (admin)
        
        tenancy()->end();
    }
}
