<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use App\Services\Central\AuditService;
use App\Models\Tenant\Usuario; // Modelo de usuario DENTRO del tenant
use Illuminate\Support\Facades\Auth;

class TenantManageController extends Controller
{
    public function indexWeb(Request $request)
    {
        $query = Tenant::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('id', 'ilike', "%{$search}%")
                  ->orWhere('rut_empresa', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('estado') && $request->estado != '') {
            $query->where(fn($q) => $q->where('estado', $request->estado));
        }

        $tenants = $query->paginate(15)->withQueryString();

        return view('central.tenants.index', [
            'title' => 'Gestión de Tenants',
            'tenants' => $tenants
        ]);
    }

    /**
     * Listado paginado de Tenants
     */
    public function index(Request $request)
    {
        $query = Tenant::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('id', 'ilike', "%{$search}%")
                  ->orWhere('rut_empresa', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('estado')) {
            $query->where(fn($q) => $q->where('estado', $request->estado));
        }

        return $query->paginate(15);
    }

    /**
     * Detalle específico
     */
    public function show(Tenant $tenant)
    {
        return $tenant;
    }

    /**
     * Suspender tenant (cortar el acceso).
     */
    public function suspender(Request $request, Tenant $tenant)
    {
        // 1. Revocar todos los tokens del tenant
        tenancy()->initialize($tenant);
        \Laravel\Sanctum\PersonalAccessToken::query()->delete();
        tenancy()->end();

        // 2. Cambiar estado
        $anterior = $tenant->estado;
        $tenant->update(['estado' => 'suspendido']);

        // 3. Log
        AuditService::log('suspender_tenant', [
            'estado_anterior' => $anterior,
            'nuevo_estado' => 'suspendido'
        ], $tenant->id);

        return response()->json(['message' => 'Tenant suspendido y tokens revocados exitosamente', 'tenant' => $tenant]);
    }

    public function suspenderWeb(Tenant $tenant)
    {
        $tenant->update(['estado' => 'suspendido']);
        return back()->with('success', "Tenant {$tenant->nombre} suspendido correctamente.");
    }

    /**
     * Reactivar Tenant.
     */
    public function reactivar(Request $request, Tenant $tenant)
    {
        $anterior = $tenant->estado;
        $tenant->update(['estado' => 'activo']);

        AuditService::log('reactivar_tenant', [
            'estado_anterior' => $anterior,
            'nuevo_estado' => 'activo'
        ], $tenant->id);

        return response()->json(['message' => 'Tenant reactivado exitosamente', 'tenant' => $tenant]);
    }

    public function reactivarWeb(Tenant $tenant)
    {
        $tenant->update(['estado' => 'activo']);
        return back()->with('success', "Tenant {$tenant->nombre} reactivado correctamente.");
    }

    public function usuarios(Tenant $tenant)
    {
        tenancy()->initialize($tenant);
        $usuarios = Usuario::select('id', 'nombre', 'email', 'rol')->get();
        tenancy()->end();

        return response()->json($usuarios);
    }

    /**
     * Login como un usuario específico del Tenant
     */
    public function impersonar(Request $request, Tenant $tenant)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);

        $superAdmin = Auth::user();

        // 1. Log de auditoría
        AuditService::log('impersonar', [
            'super_admin_email' => $superAdmin?->email,
            'tenant_name' => $tenant->nombre,
            'user_id_destino' => $request->user_id
        ], $tenant->id);

        // 2. Transición al Contexto del Tenant para emitir el Token
        tenancy()->initialize($tenant);
        
        $user = Usuario::find($request->user_id);

        if (!$user) {
            tenancy()->end();
            return response()->json(['message' => 'Usuario no encontrado en este tenant.'], 422);
        }

        // Crear token temporal
        $token = $user->createToken('superadmin-impersonate', ['*'], now()->addHour())->plainTextToken;
        
        /** @var \Stancl\Tenancy\Database\Models\Domain|null $domainRecord */
        $domainRecord = $tenant->domains()->first();
        $domain = $domainRecord ? $domainRecord->domain : null;
        
        tenancy()->end();

        if (!$domain) {
            return response()->json(['message' => 'El tenant no tiene un dominio configurado.'], 422);
        }

        $scheme = request()->getScheme();
        $port = request()->getPort();
        $incluirPuerto = ($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443);
        $url = "{$scheme}://{$domain}" . ($incluirPuerto ? ":{$port}" : "");

        return response()->json([
            'token' => $token,
            'url' => $url,
            'message' => 'Token de impersonación generado'
        ]);
    }
}
