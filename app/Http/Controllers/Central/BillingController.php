<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Central\Subscription;
use App\Models\Central\PagoSubscription;
use App\Models\Central\Plan;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function indexWeb()
    {
        $planes = Plan::all();
        $suscripciones = Subscription::with('tenant', 'plan')->paginate(10);
        $pagos = PagoSubscription::with('subscription.tenant')->orderBy('created_at', 'desc')->paginate(10);

        return view('central.billing.index', [
            'title' => 'Facturación SaaS',
            'planes' => $planes,
            'suscripciones' => $suscripciones,
            'pagos' => $pagos
        ]);
    }

    public function suscripciones()
    {
        return Subscription::with('tenant', 'plan')->paginate(15);
    }

    public function historialPagos()
    {
        return PagoSubscription::with('subscription.tenant')->orderBy('created_at', 'desc')->paginate(20);
    }

    // =================== PLAN MANAGEMENT ===================

    public function planesIndex()
    {
        return view('central.planes.index', [
            'planes' => Plan::all(),
            'title' => 'Gestión de Planes'
        ]);
    }

    public function modulosIndex()
    {
        return view('central.modulos.index', [
            'modulos' => \App\Models\Central\PlanModulo::all(),
            'title' => 'Gestión de Módulos'
        ]);
    }

    public function planStore(Request $request)
    {
        $data = $request->validate([
            'nombre'             => 'required|string',
            'precio_mensual_clp' => 'required|integer',
            'max_usuarios'       => 'required|integer',
            'max_productos'      => 'required|integer',
            'features'           => 'nullable|array'
        ]);
        Plan::create($data);
        return redirect()->route('central.billing.index')->with('success', 'Plan creado');
    }

    public function planUpdate(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update($request->only(['nombre','precio_mensual_clp','max_usuarios','max_productos','features']));
        return redirect()->route('central.billing.index')->with('success', 'Plan actualizado');
    }

    public function planDestroy($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();
        return redirect()->route('central.billing.index')->with('success', 'Plan eliminado');
    }

    // =================== SUBSCRIPTION CONTROL ===================

    public function activar($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->update(['estado' => 'activa']);
        return response()->json(['ok' => true]);
    }

    public function suspender($id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->update(['estado' => 'vencida']);
        return response()->json(['ok' => true]);
    }

    public function subscriptionStore(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => 'required',
            'plan_id'   => 'required',
            'estado'    => 'required|string',
        ]);
        Subscription::create($data);
        return redirect()->route('central.billing.index')->with('success', 'Suscripción creada');
    }

    public function subscriptionUpdate(Request $request, $id)
    {
        $sub = Subscription::findOrFail($id);
        $sub->update($request->only(['plan_id', 'estado', 'trial_termina']));
        return redirect()->route('central.billing.index')->with('success', 'Suscripción actualizada');
    }

    // =================== MODULE MANAGEMENT ===================

    public function moduloStore(Request $request)
    {
        $data = $request->validate([
            'modulo_id'      => 'required|string|unique:plan_modulos,modulo_id',
            'nombre'         => 'required|string',
            'descripcion'    => 'nullable|string',
            'precio_mensual' => 'required|integer',
            'es_base'        => 'boolean',
            'requiere'       => 'nullable|array',
            'activo'         => 'boolean',
        ]);
        \App\Models\Central\PlanModulo::create($data);
        return redirect()->route('central.modulos.index')->with('success', 'Módulo creado');
    }

    public function moduloUpdate(Request $request, $id)
    {
        $modulo = \App\Models\Central\PlanModulo::findOrFail($id);
        $data = $request->validate([
            'nombre'         => 'required|string',
            'descripcion'    => 'nullable|string',
            'precio_mensual' => 'required|integer',
            'es_base'        => 'boolean',
            'requiere'       => 'nullable|array',
            'activo'         => 'boolean',
        ]);
        $modulo->update($data);
        return redirect()->route('central.modulos.index')->with('success', 'Módulo actualizado');
    }

    public function moduloDestroy($id)
    {
        $modulo = \App\Models\Central\PlanModulo::findOrFail($id);
        $modulo->delete();
        return redirect()->route('central.modulos.index')->with('success', 'Módulo eliminado');
    }
}
