<?php

namespace App\Http\Controllers\Api\Internal\Bot;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantPhoneController extends Controller
{
    /**
     * Buscar tenant por número de WhatsApp administrativo
     * Endpoint interno para el bot Node.js
     */
    public function findByPhone(Request $request, string $phone)
    {
        Log::info('[ERP] Buscando tenant por teléfono', ['phone' => $phone]);

        // Normalizar número (quitar +, espacios, guiones)
        $normalizedPhone = preg_replace('/[\s\-\+\(\)]/', '', $phone);
        $last8 = substr($normalizedPhone, -8); // últimos 8 dígitos

        // Buscar tenant por múltiples campos posibles
        $tenant = Tenant::query()
            ->where(function ($q) use ($last8) {
            // Intentar con campos comunes
            $q->where('whatsapp_admin', 'like', "%{$last8}%")
                ->orWhere('id', 'like', "%{$last8}%");
        })
            ->first();

        if (!$tenant) {
            Log::warning('[ERP] Tenant no encontrado', ['phone' => $phone, 'normalized' => $normalizedPhone]);
            return response()->json([
                'message' => 'Tenant no encontrado',
                'phone_received' => $phone,
                'normalized' => $normalizedPhone
            ], 404);
        }

        Log::info('[ERP] Tenant encontrado', ['tenant_id' => $tenant->id, 'slug' => $tenant->id ?? $tenant->slug]);

        // Retornar datos seguros para el bot
        return response()->json([
            'tenant' => [
                'uuid' => $tenant->uuid ?? 'uuid-' . $tenant->id,
                'id' => $tenant->id,
                'nombre' => $tenant->nombre ?? $tenant->name ?? $tenant->business_name ?? 'Negocio',
                'slug' => $tenant->id ?? $tenant->slug ?? 'tenant-' . $tenant->id, // stancl/tenancy usa id como slug
                'rubro' => $tenant->rubro_config['industria_preset'] ?? $tenant->industry ?? 'general',
                'estado' => $tenant->estado ?? $tenant->status ?? 'active',
                'whatsapp_admin' => $tenant->whatsapp_admin ?? $tenant->whatsapp ?? $tenant->phone,
                'modulos_activos' => $tenant->rubro_config['modulos_activos'] ?? [],
                'config_bot' => $tenant->rubro_config['bot_config'] ?? $tenant->config ?? null,
            ]
        ]);
    }
}