<?php

namespace App\Http\Controllers\Api\Internal\Bot;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;

class TenantPhoneController extends Controller
{
    /**
     * Buscar tenant por número de WhatsApp administrativo
     * Endpoint interno para el bot Node.js
     * 
     * GET /api/internal/bot/tenant-by-phone/{phone}
     * Headers: Authorization: Bearer {jwt}, X-Bot-Token: {shared_secret}
     */
    public function findByPhone(Request $request, string $phone)
    {
        // Normalizar número
        $normalizedPhone = preg_replace('/[\s\-\+\(\)]/', '', $phone);

        // Buscar tenant (ajustar campo según tu schema real)
        $tenant = Tenant::where('whatsapp_admin', $normalizedPhone)
            ->orWhere('whatsapp_admin', 'like', "%{$normalizedPhone}")
            ->first();

        if (!$tenant) {
            return response()->json(['message' => 'Tenant no encontrado'], 404);
        }

        // Retornar solo datos seguros para el bot
        return response()->json([
            'tenant' => [
                'uuid' => $tenant->uuid,
                'id' => $tenant->id,
                'nombre' => $tenant->nombre,
                'slug' => $tenant->id, // stancl/tenancy
                'rubro' => $tenant->rubro_config['industria_preset'] ?? null,
                'estado' => $tenant->estado,
                'whatsapp_admin' => $tenant->whatsapp_admin,
                'modulos_activos' => $tenant->rubro_config['modulos_activos'] ?? [],
                'config_bot' => $tenant->rubro_config['bot_config'] ?? null,
            ]
        ]);
    }
}