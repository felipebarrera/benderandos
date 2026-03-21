<?php

namespace Tests\Feature\Central;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mrr_calculation_is_correct(): void
    {
        // 1. Crear usuario admin y autenticar
        $admin = \App\Models\User::factory()->create();
        $this->actingAs($admin);

        // 2. Crear planes
        $planBasico = \App\Models\Central\Plan::create([
            'nombre' => 'Basico',
            'precio_mensual_clp' => 20000,
            'max_usuarios' => 5,
            'max_productos' => 100
        ]);

        $planPro = \App\Models\Central\Plan::create([
            'nombre' => 'Pro',
            'precio_mensual_clp' => 50000,
            'max_usuarios' => 10,
            'max_productos' => 500
        ]);

        // 3. Crear Tenants y Suscripciones activas
        $tenant1 = \App\Models\Central\Tenant::create(['id' => 'tenant1', 'nombre' => 'T1', 'estado' => 'activo']);
        $tenant2 = \App\Models\Central\Tenant::create(['id' => 'tenant2', 'nombre' => 'T2', 'estado' => 'activo']);

        \App\Models\Central\Subscription::create([
            'tenant_id' => $tenant1->id,
            'plan_id' => $planBasico->id,
            'estado' => 'activa',
            'monto_clp' => 20000,
            'inicio' => now(),
            'proximo_cobro' => now()->addMonth(),
        ]);

        \App\Models\Central\Subscription::create([
            'tenant_id' => $tenant2->id,
            'plan_id' => $planPro->id,
            'estado' => 'activa',
            'monto_clp' => 50000,
            'inicio' => now(),
            'proximo_cobro' => now()->addMonth(),
        ]);

        // 4. Llamar al endpoint
        $response = $this->getJson('/api/central/metrics');

        // 5. Assert (20000 + 50000 = 70000)
        $response->assertStatus(200);
        $response->assertJsonPath('mrr', 70000);
        $response->assertJsonPath('tenants_activos', 2);
    }
}
