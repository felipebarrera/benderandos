<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\MarketingService;
use Illuminate\Http\Request;

class QrLandingController extends Controller
{
    public function __construct(
        private MarketingService $marketingService
    ) {}

    public function scan(string $uuid, Request $request)
    {
        $campana = $this->marketingService->registrarEscaneo($uuid, $request);

        if (!$campana) {
            return response('QR no encontrado', 404);
        }

        // Si no está activa (por fecha o límite)
        if (!$campana->activa) {
            return view('tenant.marketing.qr_expirado', compact('campana'));
        }

        // Redireccionar según el tipo de acción
        return match ($campana->tipo_accion) {
            'abrir_whatsapp' => redirect("https://wa.me/?text=" . urlencode($campana->mensaje_whatsapp ?? 'Hola! Escaneé su código QR.')),
            'link_externo', 'encuesta' => redirect($campana->link_destino ?? '/'),
            default => view('tenant.marketing.qr_cupon', compact('campana')) // Mostrar cupón
        };
    }
}
