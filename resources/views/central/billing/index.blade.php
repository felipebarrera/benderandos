@extends('layouts.central')

@section('title', 'Billing')
@section('page-title', 'Recaudación y Planes')

@section('content')
<div class="page-hdr">
    <div class="page-title">Planes de Suscripción</div>
    <div class="fr">
        <button class="btn btn-primary">Crear Nuevo Plan</button>
    </div>
</div>

<div class="kpi-row">
    @foreach($planes as $plan)
    <div class="kpi">
        <div class="kpi-lbl">{{ $plan->nombre }}</div>
        <div class="kpi-val">${{ number_format($plan->precio_mensual_clp, 0, ',', '.') }}</div>
        <div style="font-size:10px; color:var(--t2); margin-top:8px; font-family:var(--mono)">
            Users: {{ $plan->max_usuarios }} | Slots: {{ $plan->max_productos }}
        </div>
        <button class="btn btn-secondary btn-sm" style="width:100%">Editar</button>
    </div>
    @endforeach
</div>

<div class="page-hdr" style="margin-top:20px">
    <div class="page-title">Suscripciones Recientes</div>
</div>

<div class="card" style="padding:0">
    <table class="tbl">
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Plan</th>
                <th>Estado</th>
                <th>Vence</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suscripciones as $sub)
            <tr>
                <td style="font-weight:600">{{ $sub->tenant->nombre ?? 'N/A' }}</td>
                <td style="color:var(--info); font-weight:600">{{ $sub->plan->nombre ?? 'Custom' }}</td>
                <td>
                    @php
                        $estadoColor = match($sub->estado) {
                            'activa'    => ['color' => 'var(--ok)',   'bg' => 'rgba(0,229,160,.1)'],
                            'trial'     => ['color' => 'var(--warn)',  'bg' => 'rgba(245,197,24,.1)'],
                            'gracia'    => ['color' => 'var(--warn)',  'bg' => 'rgba(245,197,24,.1)'],
                            'vencida'   => ['color' => 'var(--err)',   'bg' => 'rgba(255,63,91,.1)'],
                            'cancelada' => ['color' => 'var(--t2)',    'bg' => 'rgba(136,136,160,.1)'],
                            default     => ['color' => 'var(--t2)',    'bg' => 'var(--s2)'],
                        };
                    @endphp
                    <span class="badge" style="color:{{ $estadoColor['color'] }}; background:{{ $estadoColor['bg'] }}">
                        {{ strtoupper($sub->estado) }}
                    </span>
                </td>
                <td style="color:var(--t2); font-family:var(--mono)">
                    {{ $sub->trial_termina ? \Carbon\Carbon::parse($sub->trial_termina)->format('d/m/Y') : '∞' }}
                </td>
                <td>
                    @if($sub->estado == 'activa')
                    <button onclick="changeStatus({{ $sub->id }}, 'suspender')" class="btn btn-ghost" style="color:var(--err)">Suspender</button>
                    @else
                    <button onclick="changeStatus({{ $sub->id }}, 'activar')" class="btn btn-secondary btn-sm" style="color:var(--ok)">Activar</button>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center; padding:40px; color:var(--t3); font-style:italic">No hay suscripciones registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($suscripciones->hasPages())
    <div style="padding:16px; border-top:1px solid var(--b1);"> {{ $suscripciones->links() }} </div>
    @endif
</div>

<div class="page-hdr" style="margin-top:40px">
    <div class="page-title">Historial de Recaudación</div>
</div>

<div class="card" style="padding:0">
    <table class="tbl">
        <thead>
            <tr>
                <th>IDC</th>
                <th>Tenant</th>
                <th>Monto</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pagos as $pago)
            <tr>
                <td style="font-family:var(--mono); color:var(--t3)">#{{ substr($pago->id, 0, 8) }}</td>
                <td style="font-weight:600">{{ $pago->subscription->tenant->nombre ?? 'N/A' }}</td>
                <td style="color:var(--ok); font-weight:700">${{ number_format($pago->monto, 0, ',', '.') }}</td>
                <td style="color:var(--t2); font-family:var(--mono)">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; padding:40px; color:var(--t3); font-style:italic">No se han registrado pagos aún.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($pagos->hasPages())
    <div style="padding:16px; border-top:1px solid var(--b1);"> {{ $pagos->links() }} </div>
    @endif
</div>

<script>
async function changeStatus(id, action) {
    if(!confirm(`¿Desea ${action} esta suscripción?`)) return;
    try {
        const res = await fetch(`/central/billing/subscription/${id}/${action}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        });
        if(res.ok) {
            location.reload();
        } else {
            alert("Error al procesar la solicitud");
        }
    } catch(e) { console.error(e); }
}
</script>
@endsection
