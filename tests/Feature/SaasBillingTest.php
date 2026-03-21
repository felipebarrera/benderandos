<?php

namespace Tests\Feature;

use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use App\Models\Central\Plan;
use App\Models\Tenant\SaasCliente;
use App\Models\Tenant\SaasMetrica;
use App\Services\SaasMetricasService;
use App\Jobs\Central\ProcesarCobrosMensuales;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Nota: Estas pruebas tocan tanto Central como Tenant.
        // Asumimos que SaasMetricasService corre en el contexto de un tenant "admin".
    }

    public function test_calculo_mrr_considera_clientes_activos_y_morosos()
    {
        // Simulamos el contexto de tenant para SaasMetricasService
        $tenant = Tenant::create(['id' => 'admin_central']);
        tenancy()->initialize($tenant);

        // Crear clientes con distintos estados
        SaasCliente::create(['nombre' => 'C1', 'estado' => 'activo', 'precio_actual' => 30000, 'industria' => 'Retail']);
        SaasCliente::create(['nombre' => 'C2', 'estado' => 'moroso', 'precio_actual' => 60000, 'industria' => 'Ferretería']);
        SaasCliente::create(['nombre' => 'C3', 'estado' => 'trial',  'precio_actual' => 0,     'industria' => 'Otros']);
        SaasCliente::create(['nombre' => 'C3', 'estado' => 'cancelado', 'precio_actual' => 30000, 'industria' => 'Otros']);

        $service = app(SaasMetricasService::class);
        $metrica = $service->snapshotDiario();

        // MRR debería ser 30000 + 60000 = 90000
        $this->assertEquals(90000, $metrica->mrr);
        $this->assertEquals(1, $metrica->tenants_activos);
        $this->assertEquals(1, $metrica->tenants_morosos);
        
        tenancy()->end();
    }

    public function test_job_procesar_cobros_genera_pagos_pendientes()
    {
        // 1. Setup en contexto Central
        $plan = Plan::create(['nombre' => 'Pro', 'precio_mensual' => 59900]);
        $tenant = Tenant::create(['id' => 't1']);
        
        $sub = Subscription::create([
            'tenant_id' => 't1',
            'plan_id' => $plan->id,
            'estado' => 'activa',
            'inicio' => now()->subMonth(),
            'proximo_cobro' => now()->subDay(),
            'monto_clp' => 59900
        ]);

        // 2. Ejecutar Job
        (new ProcesarCobrosMensuales())->handle();

        // 3. Verificar
        $this->assertDatabaseHas('pago_subscriptions', [
            'subscription_id' => $sub->id,
            'estado' => 'pendiente',
            'monto_clp' => 59900
        ]);

        $sub->refresh();
        $this->assertTrue(Carbon::parse($sub->proximo_cobro)->isFuture());
    }
}
