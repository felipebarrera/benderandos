<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Central\Tenant;

class OnboardingWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test full onboarding via bot webhooks.
     */
    public function test_webhook_can_create_a_tenant(): void
    {
        $payload = [
            'step'           => 'complete',
            'nombre_empresa' => 'Test Onboarding Corp',
            'rubro'          => 'retail',
            'rut_empresa'    => '11222333-4',
            'whatsapp_admin' => '56988776655',
            'email_admin'    => 'admin@testonboarding.cl',
            'password_admin' => 'password123',
            'nombre_admin'   => 'Admin Test'
        ];

        $response = $this->postJson('/webhook/whatsapp/onboarding', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure(['url', 'estado', 'dias_trial']);
        
        $this->assertDatabaseCount('tenants', 1);
        $createdTenant = Tenant::first();
        $this->assertNotNull($createdTenant);

        // Cleanup
        $createdTenant->delete();
    }
}
