<?php

namespace App\Services;

use App\Models\Tenant\CampanaMarketing;
use App\Models\Tenant\QrCampana;
use Illuminate\Support\Facades\Request;

class MarketingService
{
    /**
     * Crear un nuevo QR para una campaña usando la API gratuita de QuickChart
     * que soporta logos en el centro y es muy rápida.
     */
    public function crearQr(CampanaMarketing $campana, string $ubicacion = null): QrCampana
    {
        $qr = $campana->qrs()->create([
            'ubicacion_fisica' => $ubicacion,
        ]);

        // URL pública de landing/tracking
        // Ej: https://tenant.benderand.cl/qr/u1234abcd
        $trackingUrl = url("/qr/{$qr->uuid}");

        // Para QuickChart API: https://quickchart.io/documentation/qr-codes/
        // Opcional: logo del tenant en el centro si lo hubiere
        $baseApi = "https://quickchart.io/qr?size=500&margin=2";
        $finalUrl = $baseApi . "&text=" . urlencode($trackingUrl);

        $qr->update(['qr_url' => $finalUrl]);

        return $qr;
    }

    /**
     * Registrar un escaneo de QR y devolver los datos de la campaña para la vista
     */
    public function registrarEscaneo(string $uuid, \Illuminate\Http\Request $request): ?CampanaMarketing
    {
        $qr = QrCampana::with('campana')->where('uuid', $uuid)->first();
        if (!$qr) return null;

        $ua = $request->header('User-Agent');
        $device = 'desktop';
        if (preg_match('/Mobi|Android|iPhone|iPad/i', $ua)) {
            $device = 'mobile';
        }

        $qr->escaneos()->create([
            'ip_address'    => $request->ip(),
            'user_agent'    => substr($ua, 0, 255),
            'device_type'   => $device,
            'fecha_escaneo' => now(),
        ]);

        return $qr->campana;
    }

    /**
     * Obtener métricas para el dashboard de marketing
     */
    public function getDashboard(): array
    {
        $campanasActivas = CampanaMarketing::where('estado', 'activa')->count();
        $totalEscaneos = \App\Models\Tenant\EscaneoQr::count();
        $totalConversiones = \App\Models\Tenant\EscaneoQr::where('convertido', true)->count();
        
        $tasaConversion = $totalEscaneos > 0 ? round(($totalConversiones / $totalEscaneos) * 100, 1) : 0;

        return [
            'campanas_activas'   => $campanasActivas,
            'total_escaneos'     => $totalEscaneos,
            'conversiones'       => $totalConversiones,
            'tasa_conversion'    => $tasaConversion,
        ];
    }
}
