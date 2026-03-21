@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Reportes</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <input type="date" id="fechaDesde" style="min-width:140px;" class="mono">
            <span style="color:var(--t2);">—</span>
            <input type="date" id="fechaHasta" style="min-width:140px;" class="mono">
            <button class="btn btn-primary btn-sm" onclick="cargarReporte()">Consultar</button>
        </div>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid var(--b1);padding-bottom:12px;overflow-x:auto;">
        <button class="filter-pill active" onclick="setTab('ventas', this)">Ventas</button>
        <button class="filter-pill" onclick="setTab('compras', this)">Compras</button>
        <button class="filter-pill" onclick="setTab('inventario', this)">Inventario</button>
        <button class="filter-pill" onclick="setTab('deudas', this)">Deudas</button>
    </div>

    <div id="reporteContent">
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3>Selecciona un período</h3>
            <p>Escoge las fechas y presiona Consultar</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
let tabActual = 'ventas';

// Defaults: mes actual
const hoy = new Date();
document.getElementById('fechaDesde').value = `${hoy.getFullYear()}-${String(hoy.getMonth()+1).padStart(2,'0')}-01`;
document.getElementById('fechaHasta').value = hoy.toISOString().split('T')[0];

function setTab(tab, btn) {
    tabActual = tab;
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    cargarReporte();
}

async function cargarReporte() {
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    const el = document.getElementById('reporteContent');
    el.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';

    try {
        if (tabActual === 'ventas') {
            const data = await api('GET', `/api/ventas?desde=${desde}&hasta=${hasta}&estado=confirmada`);
            const ventas = data.data ?? data;
            const total = ventas.reduce((s, v) => s + (v.total ?? 0), 0);
            el.innerHTML = `
                <div class="kpi-grid" style="margin-bottom:20px;">
                    <div class="kpi-card">
                        <div class="kpi-label">Total Ventas</div>
                        <div class="kpi-value mono">${fmt(total)}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">N° transacciones</div>
                        <div class="kpi-value">${ventas.length}</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">Ticket Promedio</div>
                        <div class="kpi-value mono">${fmt(ventas.length ? total / ventas.length : 0)}</div>
                    </div>
                </div>
                <div class="card" style="padding:0;">
                    <div style="display:flex;justify-content:flex-end;padding:12px 16px;border-bottom:1px solid var(--b1);">
                        <button class="btn btn-sm btn-secondary" onclick="exportarCSV()">↓ Exportar CSV</button>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Tipo Pago</th><th class="num">Total</th></tr></thead>
                            <tbody>${ventas.map(v => `
                                <tr>
                                    <td class="mono">#${String(v.id).padStart(4,'0')}</td>
                                    <td style="font-size:12px;color:var(--t2);">${new Date(v.created_at).toLocaleDateString('es-CL')}</td>
                                    <td>${v.cliente?.nombre ?? '—'}</td>
                                    <td style="text-transform:capitalize;">${v.tipo_pago ?? '—'}</td>
                                    <td class="num mono">${fmt(v.total)}</td>
                                </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>`;
        } else if (tabActual === 'compras') {
            const data = await api('GET', `/api/compras?desde=${desde}&hasta=${hasta}`);
            const compras = data.data ?? data;
            el.innerHTML = `<div class="card" style="padding:0;"><div class="table-wrap"><table>
                <thead><tr><th>Fecha</th><th>Proveedor</th><th>N° Factura</th><th>Items</th><th class="num">Total</th></tr></thead>
                <tbody>${compras.map(c => `<tr>
                    <td style="font-size:12px;">${new Date(c.created_at).toLocaleDateString('es-CL')}</td>
                    <td>${c.proveedor ?? '—'}</td>
                    <td class="mono">${c.numero_factura ?? '—'}</td>
                    <td>${c.items_count ?? '—'}</td>
                    <td class="num mono">${fmt(c.total)}</td>
                </tr>`).join('')}</tbody></table></div></div>`;
        } else if (tabActual === 'inventario') {
            const data = await api('GET', '/api/productos');
            const prods = data.data ?? data;
            el.innerHTML = `<div class="card" style="padding:0;"><div class="table-wrap"><table>
                <thead><tr><th>Nombre</th><th>Tipo</th><th class="num">Stock</th><th class="num">Mín</th><th class="num">Costo</th><th class="num">Valor Stock</th></tr></thead>
                <tbody>${prods.map(p => {
                    const bajo = (p.stock??0) <= (p.stock_minimo??0) && p.tipo !== 'servicio';
                    return `<tr>
                        <td style="font-weight:500;">${p.nombre}</td>
                        <td><span class="type-badge ${p.tipo==='servicio' ? 'type-servicio' : (p.tipo==='renta' ? 'type-renta' : 'type-stock')}">${p.tipo?.replace('_',' ')}</span></td>
                        <td class="num mono${bajo ? ' text-err' : ''}">${p.stock ?? '∞'}</td>
                        <td class="num mono" style="color:var(--t2)">${p.stock_minimo ?? 0}</td>
                        <td class="num mono">${fmt(p.costo ?? 0)}</td>
                        <td class="num mono">${fmt((p.stock??0)*(p.costo??0))}</td>
                    </tr>`;
                }).join('')}</tbody></table></div></div>`;
        } else {
            el.innerHTML = `<div class="card"><div class="empty-state" style="padding:40px 0;"><h3>Módulo de Deudas</h3><p>Ver en sección Clientes</p></div></div>`;
        }
    } catch(e) {
        el.innerHTML = `<div class="card"><p style="color:var(--err);text-align:center;padding:20px;">Error: ${e.message}</p></div>`;
    }
}

function exportarCSV() { toast('Exportación CSV próximamente', 'info'); }
</script>
@endpush
@endsection
