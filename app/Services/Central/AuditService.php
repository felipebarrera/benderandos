<?php

namespace App\Services\Central;

use App\Models\Central\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Registra una acción en el log de auditoría central.
     * Detecta automáticamente si el actor es un SuperAdmin o un usuario de Tenant.
     */
    public static function log(string $accion, array $detalles = [], ?string $tenantId = null): void
    {
        $ip = request()->ip();
        $actorType = 'user';
        $actorEmail = null;
        $userId = null;

        // 1. Intentar detectar SuperAdmin ( Landlord )
        if (Auth::guard('super_admin')->check()) {
            $user = Auth::guard('super_admin')->user();
            $actorType = 'super_admin';
            $actorEmail = $user->email;
            $userId = null; // No usamos user_id de central para evitar conflictos de FK en contextos mixtos
        } 
        // 2. Intentar detectar usuario autenticado estándar ( Tenant context )
        elseif (Auth::check()) {
            $user = Auth::user();
            $actorType = 'user';
            $userId = $user->id;
        }

        AuditLog::create([
            'user_id'     => $userId,
            'actor_type'  => $actorType,
            'actor_email' => $actorEmail,
            'tenant_id'   => $tenantId ?? tenant('id'),
            'accion'      => $accion,
            'ip'          => $ip,
            'detalles'    => $detalles,
        ]);
    }
}
