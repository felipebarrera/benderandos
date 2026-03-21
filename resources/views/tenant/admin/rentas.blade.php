@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Panel de Rentas</div>
            <div class="page-sub">Unidades activas y disponibles</div>
        </div>
        <div style="display:flex;gap:10px;">
            <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:var(--s2);border:1px solid var(--b1);border-radius:var(--r);">
                <span class="dot dot-green"></span><span style="font-size:13px;">Libre</span>
                <span class="dot dot-red" style="margin-left:12px;"></span><span style="font-size:13px;">Ocupado</span>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="loadRentas()">↻ Actualizar</button>
        </div>
    </div>

    <!-- Resumen rápido -->
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">Unidades libres</div>
            <div class="kpi-value text-ok" id="kpiLibres">—</div>
        </div>
        <div class="kpi-card" style="border-color:var(--err)">
            <div class="kpi-label">Ocupadas</div>
            <div class="kpi-value text-err" id="kpiOcupadas">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Por vencer (&lt;30min)</div>
            <div class="kpi-value text-warn" id="kpiPorVencer">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Vencidas</div>
            <div class="kpi-value text-err" id="kpiVencidas">—</div>
        </div>
    </div>

    <!-- Grid de unidades -->
    <div class="renta-grid" id="rentaGrid">
        <div class="card" style="grid-column:1/-1;text-align:center;padding:32px;color:var(--t2);">
            <span class="spinner" style="display:inline-block;"></span>
        </div>
    </div>
</div>

<!-- MODAL RENTA EN CURSO -->
<div class="modal-overlay" id="modalRentaDetalle">
    <div class="modal" style="max-width:400px;">
        <div class="modal-head">
            <span class="modal-title" id="rentaDetalleTitle">Renta en Curso</span>
            <button class="modal-close" data-close-modal="modalRentaDetalle">✕</button>
        </div>
        <div class="modal-body" id="rentaDetalleBody"></div>
        <div class="modal-foot">
            <button class="btn btn-secondary btn-sm" onclick="extenderRenta()">+ Extender</button>
            <button class="btn btn-primary" onclick="devolverRenta()">Devolver Unidad</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let rentasData = [];
let rentaActiva = null;
let timers = {};

// Listen for WebSocket Broadcasts
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.Echo !== 'undefined' && window.AppConfig && window.AppConfig.tenantId) {
        window.Echo.private('tenant.' + window.AppConfig.tenantId)
            .listen('Tenant\\\\RentaVenciendo', (e) => {
                console.log('WS Event RentaVenciendo', e);
                loadRentas();
            });
    }
});

async function loadRentas() {
    try {
        const data = await api('GET', '/api/rentas/panel');
        rentasData = data;
        const libres = rentasData.filter(r => r.estado === 'libre').length;
        const ocupadas = rentasData.filter(r => r.estado === 'ocupado' && (r.renta?.estado === 'activa' || r.renta?.estado === 'extendida')).length;
        const porVencer = rentasData.filter(r => r.estado === 'ocupado' && r.minutos_restantes !== null && r.minutos_restantes > 0 && r.minutos_restantes <= 30).length;
        const vencidas = rentasData.filter(r => r.renta?.estado === 'vencida' || (r.estado==='ocupado' && r.minutos_restantes === 0)).length;

        document.getElementById('kpiLibres').textContent = libres;
        document.getElementById('kpiOcupadas').textContent = ocupadas;
        document.getElementById('kpiPorVencer').textContent = porVencer;
        document.getElementById('kpiVencidas').textContent = vencidas;

        renderGrid();
    } catch(e) {
        toast('Error cargando rentas', 'err');
    }
}

function renderGrid() {
    const grid = document.getElementById('rentaGrid');
    if (!rentasData.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="width:48px;height:48px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><h3>Sin unidades</h3><p>No hay productos de tipo renta</p></div>`;
        return;
    }
    grid.innerHTML = rentasData.map(r => {
        const libre = r.estado === 'libre';
        const vencida = r.renta?.estado === 'vencida' || (!libre && r.minutos_restantes === 0);
        
        return `
        <div class="renta-unit ${libre ? 'libre' : 'ocupado'}" 
            style="${vencida ? 'border-color:var(--warn);' : ''}"
            onclick="verRenta(${r.id})"
            ${!libre ? 'style="cursor:pointer;"' : ''}>
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--t2);">${r.nombre ?? 'Unidad'}</div>
            <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin:8px 0;">
                <span class="dot ${libre ? 'dot-green' : (vencida ? '' : 'dot-red')}" ${vencida ? 'style="background:var(--warn);box-shadow:0 0 6px var(--warn)"' : ''}></span>
                <span style="font-size:12px;font-weight:600;color:${libre ? 'var(--ok)' : (vencida ? 'var(--warn)' : 'var(--err)')}">
                    ${libre ? 'LIBRE' : (vencida ? 'VENCIDA' : 'OCUPADA')}
                </span>
            </div>
            ${!libre ? `<div class="renta-timer" id="timer-${r.id}" style="color:${vencida ? 'var(--warn)' : 'var(--text)'}">--:--</div>` : ''}
            ${r.renta?.cliente?.nombre ? `<div style="font-size:11px;color:var(--t2);margin-top:6px;">${r.renta.cliente.nombre}</div>` : ''}
        </div>`;
    }).join('');

    // Iniciar timers countdown
    Object.values(timers).forEach(clearInterval);
    timers = {};
    rentasData.filter(r => r.estado === 'ocupado' && r.renta?.fin_programado).forEach(r => {
        timers[r.id] = setInterval(() => {
            const el = document.getElementById(`timer-${r.id}`);
            if (!el) { clearInterval(timers[r.id]); return; }
            const diff = new Date(r.renta.fin_programado) - new Date();
            if (diff <= 0) {
                el.textContent = '00:00';
                el.style.color = 'var(--warn)';
                return;
            }
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            el.textContent = h > 0 
                ? `${h}h ${String(m).padStart(2,'0')}m`
                : `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            el.style.color = diff < 600000 ? 'var(--warn)' : 'var(--text)';
        }, 1000);
    });
}

function verRenta(id) {
    const r = rentasData.find(x => x.id === id);
    if (!r || r.estado === 'libre' || !r.renta) return;
    rentaActiva = r.renta;
    document.getElementById('rentaDetalleTitle').textContent = r.nombre ?? 'Renta';
    document.getElementById('rentaDetalleBody').innerHTML = `
        <div style="display:grid;gap:12px;">
            <div>
                <div class="label">Cliente</div>
                <div style="margin-top:4px;font-weight:500;">${r.renta.cliente?.nombre ?? '—'}</div>
            </div>
            <div>
                <div class="label">Inicio real</div>
                <div class="mono" style="margin-top:4px;">${new Date(r.renta.inicio_real).toLocaleString('es-CL')}</div>
            </div>
            <div>
                <div class="label">Fin programado</div>
                <div class="mono" style="margin-top:4px;">${new Date(r.renta.fin_programado).toLocaleString('es-CL')}</div>
            </div>
            <div>
                <div class="label">Cargos Extra</div>
                <div class="mono text-accent" style="margin-top:4px;font-size:20px;">${fmt(r.renta.cargo_extra ?? 0)}</div>
            </div>
        </div>`;
    openModal('modalRentaDetalle');
}

async function extenderRenta() {
    if (!rentaActiva) return;
    const mins = prompt('¿Cuántos minutos extender?', '30');
    if (!mins) return;
    const cargo = prompt('¿Cargo adicional extra?', '2000');
    if (cargo === null) return;
    try {
        await api('POST', `/api/rentas/${rentaActiva.id}/extender`, { 
            minutos_extra: parseInt(mins),
            cargo: parseInt(cargo)
        });
        toast('Renta extendida', 'ok');
        closeModal('modalRentaDetalle');
        loadRentas();
    } catch(e) { toast(e.message || 'Error', 'err'); }
}

async function devolverRenta() {
    if (!rentaActiva) return;
    if (!confirm('¿Confirmar finalización y devolver recurso?')) return;
    try {
        await api('POST', `/api/rentas/${rentaActiva.id}/devolver`);
        toast('Recurso devuelto', 'ok');
        closeModal('modalRentaDetalle');
        loadRentas();
    } catch(e) { toast(e.message || 'Error', 'err'); }
}

// Autorefresh cada 30s
loadRentas();
setInterval(loadRentas, 30000);
</script>
@endpush
@endsection
