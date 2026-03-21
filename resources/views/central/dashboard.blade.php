@extends('layouts.central')

@section('title', 'Dashboard')
@section('page-title', 'Métricas Generales')

@section('content')
<div class="kpi-row">
    <div class="kpi">
        <div class="kpi-lbl">Tenants</div>
        <div class="kpi-val">{{ $stats['tenants'] ?? 0 }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-lbl">MRR Global</div>
        <div class="kpi-val">${{ number_format($stats['mrr'] ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="kpi">
        <div class="kpi-lbl">Churn (Mes)</div>
        <div class="kpi-val">{{ $stats['churn'] ?? 0 }}%</div>
    </div>
    <div class="kpi">
      <div class="kpi-lbl">Crecimiento</div>
      <div class="kpi-val">{{ $stats['crecimiento'] ?? 0 }}%</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-hdr">
                <span class="card-title">Rendimiento Histórico (MRR)</span>
            </div>
            <div class="chart-placeholder" style="display:flex; align-items:flex-end; gap:4px; padding:20px;">
                @foreach([40, 60, 45, 90, 75, 55, 80, 100, 70, 85, 95, 110] as $h)
                    <div style="flex:1; background:rgba(224, 64, 251, 0.3); height:{{ $h }}%; border-radius:4px 4px 0 0; border:1px solid rgba(224, 64, 251, 0.4);"></div>
                @endforeach
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:12px; font-family:var(--mono); font-size:9px; color:var(--t3); text-transform:uppercase;">
                <span>Ene</span><span>Feb</span><span>Mar</span><span>Abr</span><span>May</span><span>Jun</span><span>Jul</span><span>Ago</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dic</span>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-hdr">
                <span class="card-title">Acciones Rápidas</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <a href="{{ route('central.tenants.index') }}" class="btn btn-primary" style="width:100%; justify-content:center;">
                  <span>⊞</span> Nuevo Tenant
                </a>
                <button class="btn" style="width:100%; justify-content:center; background:var(--s2); border:1px solid var(--b2); color:var(--tx);">
                  <span>🗂</span> Ver Auditoría
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
