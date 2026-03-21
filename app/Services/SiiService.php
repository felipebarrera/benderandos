<?php

namespace App\Services;

use App\Models\Tenant\ConfigSii;
use App\Models\Tenant\DteEmitido;
use App\Models\Tenant\Venta;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SiiService
{
    private ?ConfigSii $config;

    public function __construct()
    {
        $this->config = ConfigSii::first();
    }

    /**
     * Emitir Boleta Electrónica (tipo 39)
     */
    public function emitirBoleta(Venta $venta): DteEmitido
    {
        return $this->emitirDte($venta, DteEmitido::BOLETA);
    }

    /**
     * Emitir Factura Electrónica (tipo 33)
     */
    public function emitirFactura(Venta $venta): DteEmitido
    {
        return $this->emitirDte($venta, DteEmitido::FACTURA);
    }

    /**
     * Emitir Nota de Crédito Electrónica (tipo 61)
     */
    public function emitirNotaCredito(DteEmitido $dteOriginal, string $motivo): DteEmitido
    {
        if (!$this->config) {
            throw new \RuntimeException('Configuración SII no encontrada. Configure el módulo SII primero.');
        }

        $folio = $this->config->consumirFolio(DteEmitido::NOTA_CREDITO);

        // Construir XML de nota de crédito
        $xml = $this->buildNotaCreditoXml($dteOriginal, $folio, $motivo);

        $dte = DteEmitido::create([
            'venta_id'               => $dteOriginal->venta_id,
            'tipo_dte'               => DteEmitido::NOTA_CREDITO,
            'folio'                  => $folio,
            'fecha_emision'          => now()->toDateString(),
            'rut_receptor'           => $dteOriginal->rut_receptor,
            'razon_social_receptor'  => $dteOriginal->razon_social_receptor,
            'monto_neto'             => $dteOriginal->monto_neto,
            'monto_iva'              => $dteOriginal->monto_iva,
            'monto_total'            => $dteOriginal->monto_total,
            'xml'                    => $xml,
            'estado_sii'             => 'pendiente',
            'dte_referencia_id'      => $dteOriginal->id,
            'motivo_nc'              => $motivo,
        ]);

        // Enviar al SII (o simular en certificación)
        $this->enviarAlSii($dte);

        return $dte;
    }

    /**
     * Consultar estado de un DTE en el SII
     */
    public function consultarEstado(DteEmitido $dte): string
    {
        if (!$this->config || $this->config->esCertificacion()) {
            // En certificación, simular aceptación después de enviar
            if ($dte->estado_sii === 'enviado') {
                $dte->update(['estado_sii' => 'ACE']);
                return 'ACE';
            }
            return $dte->estado_sii;
        }

        // En producción: llamar a API de LibreDTE
        try {
            $estado = $this->consultarEstadoSiiReal($dte);
            $dte->update(['estado_sii' => $estado]);
            return $estado;
        } catch (\Exception $e) {
            Log::error("SiiService: Error consultando estado DTE {$dte->id}: " . $e->getMessage());
            return $dte->estado_sii;
        }
    }

    /**
     * Generar URL del PDF del DTE
     */
    public function generarPdf(DteEmitido $dte): string
    {
        // En certificación: generar PDF simple
        $pdfUrl = "/storage/dte/dte_{$dte->tipo_dte}_{$dte->folio}.pdf";
        $dte->update(['pdf_url' => $pdfUrl]);
        return $pdfUrl;
    }

    /**
     * Resumen de DTEs del día para dashboard
     */
    public function getResumenDiario(): array
    {
        return [
            'boletas_hoy'     => DteEmitido::boletas()->delDia()->count(),
            'facturas_hoy'    => DteEmitido::facturas()->delDia()->count(),
            'nc_hoy'          => DteEmitido::getNotasCredito()->delDia()->count(),
            'neto_boletas'    => DteEmitido::boletas()->delDia()->sum('monto_neto'),
            'neto_facturas'   => DteEmitido::facturas()->delDia()->sum('monto_neto'),
            'total_emitido'   => DteEmitido::delDia()->sum('monto_total'),
            'pendientes_sii'  => DteEmitido::pendientes()->count(),
            'aceptados_sii'   => DteEmitido::aceptados()->delDia()->count(),
            'rechazados_hoy'  => DteEmitido::delDia()->whereIn('estado_sii', ['REC', 'REP'])->count(),
        ];
    }

    /**
     * Libro de ventas mensual
     */
    public function getLibroVentas(int $mes, int $anio): array
    {
        $dtes = DteEmitido::delMes($mes, $anio)
            ->whereIn('tipo_dte', [DteEmitido::BOLETA, DteEmitido::FACTURA])
            ->orderBy('folio')
            ->get();

        $totales = [
            'cantidad'   => $dtes->count(),
            'neto'       => $dtes->sum('monto_neto'),
            'iva'        => $dtes->sum('monto_iva'),
            'total'      => $dtes->sum('monto_total'),
            'boletas'    => $dtes->where('tipo_dte', DteEmitido::BOLETA)->count(),
            'facturas'   => $dtes->where('tipo_dte', DteEmitido::FACTURA)->count(),
        ];

        return [
            'periodo'  => "{$anio}-" . str_pad($mes, 2, '0', STR_PAD_LEFT),
            'dtes'     => $dtes,
            'totales'  => $totales,
        ];
    }

    // --- Private helpers ---

    private function emitirDte(Venta $venta, int $tipoDte): DteEmitido
    {
        if (!$this->config) {
            throw new \RuntimeException('Configuración SII no encontrada. Configure el módulo SII primero.');
        }

        $venta->load('cliente', 'items.producto');

        $folio = $this->config->consumirFolio($tipoDte);

        // Calcular montos con IVA (Chile 19%)
        $montoTotal = $venta->total;
        $montoNeto  = (int) round($montoTotal / 1.19);
        $montoIva   = $montoTotal - $montoNeto;

        // Datos del receptor
        $rutReceptor   = $venta->cliente?->rut ?? '66666666-6';
        $razonReceptor = $venta->cliente?->nombre ?? 'Público General';

        // Construir XML del DTE
        $xml = $this->buildDteXml($venta, $tipoDte, $folio, $montoNeto, $montoIva, $montoTotal);

        $dte = DteEmitido::create([
            'venta_id'               => $venta->id,
            'tipo_dte'               => $tipoDte,
            'folio'                  => $folio,
            'fecha_emision'          => now()->toDateString(),
            'rut_receptor'           => $rutReceptor,
            'razon_social_receptor'  => $razonReceptor,
            'monto_neto'             => $montoNeto,
            'monto_iva'              => $montoIva,
            'monto_total'            => $montoTotal,
            'xml'                    => $xml,
            'estado_sii'             => 'pendiente',
        ]);

        // Enviar al SII (o simular en certificación)
        $this->enviarAlSii($dte);

        Log::info("DTE emitido: Tipo {$tipoDte}, Folio {$folio}, Venta #{$venta->id}");

        return $dte;
    }

    private function enviarAlSii(DteEmitido $dte): void
    {
        if (!$this->config || $this->config->esCertificacion()) {
            // En certificación: simular envío exitoso
            $dte->update([
                'estado_sii' => 'enviado',
                'track_id'   => 'CERT-' . Str::random(10),
            ]);
            return;
        }

        // En producción: usar API de LibreDTE
        try {
            $response = $this->enviarDteSiiReal($dte);
            $dte->update([
                'estado_sii' => 'enviado',
                'track_id'   => $response['track_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("SiiService: Error enviando DTE al SII: " . $e->getMessage());
            $dte->update(['estado_sii' => 'error']);
        }
    }

    private function buildDteXml(Venta $venta, int $tipoDte, int $folio, int $neto, int $iva, int $total): string
    {
        $tipoNombre = DteEmitido::tiposLabel()[$tipoDte] ?? 'DTE';
        $fecha = now()->format('Y-m-d');
        $rutEmisor = $this->config->rut_empresa ?? '76000000-0';
        $razonEmisor = $this->config->razon_social ?? 'Empresa Demo';
        $rutReceptor = $venta->cliente?->rut ?? '66666666-6';
        $razonReceptor = $venta->cliente?->nombre ?? 'Público General';

        $detalles = '';
        foreach ($venta->items as $item) {
            $detalles .= "
        <Detalle>
            <NmbItem>{$item->producto->nombre}</NmbItem>
            <QtyItem>{$item->cantidad}</QtyItem>
            <PrcItem>{$item->precio_unitario}</PrcItem>
            <MontoItem>{$item->total_item}</MontoItem>
        </Detalle>";
        }

        return "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<DTE version=\"1.0\">
    <Documento ID=\"DTE-{$tipoDte}-{$folio}\">
        <Encabezado>
            <IdDoc>
                <TipoDTE>{$tipoDte}</TipoDTE>
                <Folio>{$folio}</Folio>
                <FchEmis>{$fecha}</FchEmis>
            </IdDoc>
            <Emisor>
                <RUTEmisor>{$rutEmisor}</RUTEmisor>
                <RznSoc>{$razonEmisor}</RznSoc>
                <GiroEmis>{$this->config->giro}</GiroEmis>
                <Acteco>{$this->config->acteco}</Acteco>
                <DirOrigen>{$this->config->direccion}</DirOrigen>
                <CmnaOrigen>{$this->config->comuna}</CmnaOrigen>
                <CiudadOrigen>{$this->config->ciudad}</CiudadOrigen>
            </Emisor>
            <Receptor>
                <RUTRecep>{$rutReceptor}</RUTRecep>
                <RznSocRecep>{$razonReceptor}</RznSocRecep>
            </Receptor>
            <Totales>
                <MntNeto>{$neto}</MntNeto>
                <TasaIVA>19</TasaIVA>
                <IVA>{$iva}</IVA>
                <MntTotal>{$total}</MntTotal>
            </Totales>
        </Encabezado>{$detalles}
    </Documento>
</DTE>";
    }

    private function buildNotaCreditoXml(DteEmitido $dteOriginal, int $folio, string $motivo): string
    {
        $fecha = now()->format('Y-m-d');
        $rutEmisor = $this->config->rut_empresa ?? '76000000-0';

        return "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<DTE version=\"1.0\">
    <Documento ID=\"DTE-61-{$folio}\">
        <Encabezado>
            <IdDoc>
                <TipoDTE>61</TipoDTE>
                <Folio>{$folio}</Folio>
                <FchEmis>{$fecha}</FchEmis>
            </IdDoc>
            <Emisor>
                <RUTEmisor>{$rutEmisor}</RUTEmisor>
            </Emisor>
            <Receptor>
                <RUTRecep>{$dteOriginal->rut_receptor}</RUTRecep>
                <RznSocRecep>{$dteOriginal->razon_social_receptor}</RznSocRecep>
            </Receptor>
            <Totales>
                <MntNeto>{$dteOriginal->monto_neto}</MntNeto>
                <TasaIVA>19</TasaIVA>
                <IVA>{$dteOriginal->monto_iva}</IVA>
                <MntTotal>{$dteOriginal->monto_total}</MntTotal>
            </Totales>
        </Encabezado>
        <Referencia>
            <TpoDocRef>{$dteOriginal->tipo_dte}</TpoDocRef>
            <FolioRef>{$dteOriginal->folio}</FolioRef>
            <FchRef>{$dteOriginal->fecha_emision->format('Y-m-d')}</FchRef>
            <CodRef>1</CodRef>
            <RazonRef>{$motivo}</RazonRef>
        </Referencia>
    </Documento>
</DTE>";
    }

    /**
     * Placeholder para integración real con API LibreDTE en producción
     */
    private function enviarDteSiiReal(DteEmitido $dte): array
    {
        // TODO: Integrar con libredte-api-client-php
        // $client = new \libredte\api_client\ApiClient($this->config->libredte_hash);
        // $response = $client->post('/dte/documentos/emitir', [...]);
        return ['track_id' => 'PROD-' . Str::random(10)];
    }

    private function consultarEstadoSiiReal(DteEmitido $dte): string
    {
        // TODO: Integrar con libredte-api-client-php
        return 'ACE';
    }
}
