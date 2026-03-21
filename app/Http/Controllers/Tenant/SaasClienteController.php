<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\SaasCliente;
use App\Models\Tenant\SaasPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaasClienteController extends Controller
{
    /**
     * Lista de Tenants (Clientes SaaS). Vista Ejecutivo de Cuenta.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SaasCliente::with('plan', 'ejecutivo')
            ->orderByRaw("CASE WHEN estado = 'moroso' THEN 1 WHEN estado = 'trial' THEN 2 ELSE 3 END")
            ->orderBy('fecha_inicio', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 25)));
    }

    /**
     * Ficha de cliente SaaS (CRM)
     */
    public function show($id): JsonResponse
    {
        $cliente = SaasCliente::with(['plan', 'ejecutivo', 'cobros' => function($q) {
            $q->orderBy('periodo', 'desc')->limit(12);
        }])->findOrFail($id);

        return response()->json($cliente);
    }

    /**
     * Crear un nuevo cliente (generalmente al cerrar un negocio o trial automático)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razon_social'      => 'required|string|max:255',
            'rut'               => 'nullable|string|max:20',
            'industria'         => 'required|string|max:100',
            'contacto_nombre'   => 'required|string|max:255',
            'contacto_whatsapp' => 'required|string|max:20',
            'plan_id'           => 'required|exists:saas_planes,id',
            'ejecutivo_id'      => 'required|exists:users,id',
            'precio_actual'     => 'nullable|integer',
        ]);

        $plan = SaasPlan::findOrFail($data['plan_id']);
        
        $data['estado'] = 'trial';
        $data['fecha_inicio'] = today();
        $data['fecha_trial_fin'] = today()->addDays(30);
        $data['ciclo_facturacion'] = 'mensual';
        $data['precio_actual'] = $data['precio_actual'] ?? $plan->precio_mensual;

        $cliente = SaasCliente::create($data);

        return response()->json(['message' => 'Tenant creado (Trial)', 'cliente' => $cliente], 201);
    }

    /**
     * Actualizar estado o plan
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $cliente = SaasCliente::findOrFail($id);
        
        $data = $request->validate([
            'estado'        => 'nullable|string|in:trial,activo,moroso,suspendido,cancelado',
            'plan_id'       => 'nullable|exists:saas_planes,id',
            'precio_actual' => 'nullable|integer',
            'notas_crm'     => 'nullable|string',
        ]);

        $cliente->update($data);

        return response()->json(['message' => 'Tenant actualizado', 'cliente' => $cliente]);
    }
}
