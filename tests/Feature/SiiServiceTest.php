<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant\ConfigSii;
use App\Models\Tenant\DteEmitido;
use App\Models\Tenant\Venta;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Producto;
use App\Services\SiiService;
use App\Jobs\EmitirDteJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class SiiServiceTest extends TestCase
{
    // NOTE: These tests require tenancy context. In a real environment,
    // they should be run inside a tenant context using stancl/tenancy test helpers.
    // For now, these serve as integration test scaffolding.

    /**
     * Test que SiiService requiere configuración
     */
    public function test_emitir_boleta_sin_config_lanza_excepcion(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        $service = new SiiService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuración SII no encontrada');

        $venta = new Venta(['total' => 10000]);
        $service->emitirBoleta($venta);
    }

    /**
     * Test que emitir boleta en certificación genera DTE con track_id CERT-
     */
    public function test_emitir_boleta_certificacion_genera_dte(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        // Crear config en modo certificación
        ConfigSii::create([
            'rut_empresa'    => '76000000-0',
            'razon_social'   => 'Test SpA',
            'giro'           => 'Venta al por menor',
            'acteco'         => '523110',
            'direccion'      => 'Test 123',
            'comuna'         => 'Santiago',
            'ciudad'         => 'Santiago',
            'ambiente'       => 'certificacion',
            'documento_default' => 'boleta',
        ]);

        $cliente = Cliente::create(['nombre' => 'Test Cliente', 'rut' => '12345678-9']);
        $producto = Producto::create([
            'nombre' => 'Producto Test',
            'valor_venta' => 5000,
            'costo' => 2000,
            'cantidad' => 100,
        ]);

        $venta = Venta::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => 1,
            'estado' => 'pagada',
            'total' => 5000,
        ]);
        $venta->items()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 5000,
            'costo_unitario' => 2000,
            'total_item' => 5000,
        ]);

        $service = new SiiService();
        $dte = $service->emitirBoleta($venta);

        $this->assertInstanceOf(DteEmitido::class, $dte);
        $this->assertEquals(DteEmitido::BOLETA, $dte->tipo_dte);
        $this->assertEquals('enviado', $dte->estado_sii);
        $this->assertStringStartsWith('CERT-', $dte->track_id);
        $this->assertEquals(1, $dte->folio);
        $this->assertNotNull($dte->xml);
        $this->assertStringContains('<TipoDTE>39</TipoDTE>', $dte->xml);
    }

    /**
     * Test que emitir factura usa tipo 33
     */
    public function test_emitir_factura_usa_tipo_33(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        ConfigSii::create([
            'rut_empresa' => '76000000-0',
            'razon_social' => 'Test SpA',
            'giro' => 'Test',
            'direccion' => 'Test',
            'comuna' => 'Test',
            'ciudad' => 'Test',
            'ambiente' => 'certificacion',
            'documento_default' => 'factura',
        ]);

        $venta = Venta::factory()->create(['total' => 10000]);

        $service = new SiiService();
        $dte = $service->emitirFactura($venta);

        $this->assertEquals(DteEmitido::FACTURA, $dte->tipo_dte);
        $this->assertStringContains('<TipoDTE>33</TipoDTE>', $dte->xml);
    }

    /**
     * Test que nota de crédito referencia DTE original
     */
    public function test_emitir_nota_credito_referencia_original(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        ConfigSii::create([
            'rut_empresa' => '76000000-0',
            'razon_social' => 'Test SpA',
            'giro' => 'Test',
            'direccion' => 'Test',
            'comuna' => 'Test',
            'ciudad' => 'Test',
            'ambiente' => 'certificacion',
            'documento_default' => 'boleta',
        ]);

        $venta = Venta::factory()->create(['total' => 10000]);
        $service = new SiiService();
        $boleta = $service->emitirBoleta($venta);

        $nc = $service->emitirNotaCredito($boleta, 'Anulación de venta');

        $this->assertEquals(DteEmitido::NOTA_CREDITO, $nc->tipo_dte);
        $this->assertEquals($boleta->id, $nc->dte_referencia_id);
        $this->assertEquals('Anulación de venta', $nc->motivo_nc);
        $this->assertStringContains('<RazonRef>Anulación de venta</RazonRef>', $nc->xml);
    }

    /**
     * Test que consultar estado en certificación pasa a ACE
     */
    public function test_consultar_estado_certificacion_acepta(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        ConfigSii::create([
            'rut_empresa' => '76000000-0',
            'razon_social' => 'Test SpA',
            'giro' => 'Test',
            'direccion' => 'Test',
            'comuna' => 'Test',
            'ciudad' => 'Test',
            'ambiente' => 'certificacion',
            'documento_default' => 'boleta',
        ]);

        $venta = Venta::factory()->create(['total' => 5000]);
        $service = new SiiService();
        $dte = $service->emitirBoleta($venta);

        $this->assertEquals('enviado', $dte->estado_sii);

        $estado = $service->consultarEstado($dte);
        $this->assertEquals('ACE', $estado);
        $this->assertEquals('ACE', $dte->fresh()->estado_sii);
    }

    /**
     * Test resumen diario
     */
    public function test_resumen_diario_contiene_claves(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        $service = new SiiService();
        $resumen = $service->getResumenDiario();

        $this->assertArrayHasKey('boletas_hoy', $resumen);
        $this->assertArrayHasKey('facturas_hoy', $resumen);
        $this->assertArrayHasKey('total_emitido', $resumen);
        $this->assertArrayHasKey('pendientes_sii', $resumen);
    }

    /**
     * Test que confirmar venta despacha EmitirDteJob
     */
    public function test_confirmar_venta_despacha_emitir_dte_job(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        Queue::fake();

        // Simular confirmación de venta...
        // $ventaService->confirmar($venta, [...], $usuario);

        Queue::assertPushed(EmitirDteJob::class);
    }

    /**
     * Test libro de ventas mensual estructura
     */
    public function test_libro_ventas_estructura_correcta(): void
    {
        $this->markTestSkipped('Requires tenant context with migrations applied');

        $service = new SiiService();
        $libro = $service->getLibroVentas(3, 2026);

        $this->assertArrayHasKey('periodo', $libro);
        $this->assertArrayHasKey('dtes', $libro);
        $this->assertArrayHasKey('totales', $libro);
        $this->assertEquals('2026-03', $libro['periodo']);
        $this->assertArrayHasKey('cantidad', $libro['totales']);
        $this->assertArrayHasKey('neto', $libro['totales']);
        $this->assertArrayHasKey('iva', $libro['totales']);
        $this->assertArrayHasKey('total', $libro['totales']);
    }
}
