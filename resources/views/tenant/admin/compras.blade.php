@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Compras y Proveedores</div>
            <div class="page-sub">Gestión de proveedores, órdenes de compra y recepciones</div>
        </div>
        <div style="display:flex; gap:8px;">
            <button class="btn btn-secondary" onclick="mostrarModalProveedor()">+ Proveedor</button>
            <button class="btn btn-primary" onclick="mostrarModalOC()">+ Orden de Compra</button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">OC Pendientes</div>
            <div class="kpi-value" style="color:var(--warn);" id="kpi-pendientes">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">OC del Mes</div>
            <div class="kpi-value" style="color:var(--accent);" id="kpi-oc-mes">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Mes</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-total">$0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Alertas Stock</div>
            <div class="kpi-value" style="color:var(--err);" id="kpi-alertas">0</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--b2);">
        <button class="btn btn-secondary tab-btn active" data-tab="oc" onclick="switchTab('oc', this)">Órdenes de Compra</button>
        <button class="btn btn-secondary tab-btn" data-tab="proveedores" onclick="switchTab('proveedores', this)">Proveedores</button>
        <button class="btn btn-secondary tab-btn" data-tab="alertas" onclick="switchTab('alertas', this)">Alertas Stock</button>
    </div>

    {{-- Tab: Órdenes de Compra --}}
    <div class="tab-content" id="tab-oc">
        <div class="card">
            <div style="display:flex; gap:10px; margin-bottom:16px;">
                <select id="filtroEstadoOC" onchange="cargarOC()" style="max-width:200px;">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="autorizada">Autorizada</option>
                    <option value="enviada">Enviada</option>
                    <option value="parcial">Parcial</option>
                    <option value="completa">Completa</option>
                    <option value="anulada">Anulada</option>
                </select>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Código</th><th>Proveedor</th><th>Estado</th><th class="num">Total</th><th>Entrega</th><th>Creada</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="ocTableBody">
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Proveedores --}}
    <div class="tab-content" id="tab-proveedores" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Nombre</th><th>RUT</th><th>Contacto</th><th>Teléfono</th><th>Plazo Pago</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="provTableBody">
                        <tr><td colspan="6" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Alertas Stock --}}
    <div class="tab-content" id="tab-alertas" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Producto</th><th class="num">Stock Actual</th><th class="num">Stock Mínimo</th><th>Proveedor</th><th class="num">Sugerido</th><th>Acción</th></tr>
                    </thead>
                    <tbody id="alertasTableBody">
                        <tr><td colspan="6" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarDashboard();
    cargarOC();
    cargarProveedores();
    cargarAlertas();
});

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

const estadoColors = {
    borrador: 'badge-gray', autorizada: 'badge-blue', enviada: 'badge-orange',
    parcial: 'badge-orange', completa: 'badge-green', anulada: 'badge-red'
};

async function cargarDashboard() {
    try {
        const res = await api('GET', '/api/compras/dashboard');
        document.getElementById('kpi-pendientes').textContent = res.kpis.oc_pendientes || 0;
        document.getElementById('kpi-oc-mes').textContent = res.kpis.oc_mes || 0;
        document.getElementById('kpi-total').textContent = fmt(res.kpis.total_mes || 0);
        document.getElementById('kpi-alertas').textContent = res.kpis.alertas_stock || 0;
    } catch (e) { console.error('Error dashboard compras', e); }
}

async function cargarOC() {
    const estado = document.getElementById('filtroEstadoOC').value;
    let url = '/api/ordenes-compra?per_page=20';
    if (estado) url += `&estado=${estado}`;
    try {
        const res = await api('GET', url);
        const ocs = res.data || [];
        const tbody = document.getElementById('ocTableBody');
        if (!ocs.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--t2);">Sin órdenes de compra</td></tr>';
            return;
        }
        tbody.innerHTML = ocs.map(oc => `
            <tr>
                <td class="mono">${oc.codigo}</td>
                <td>${oc.proveedor?.nombre || '-'}</td>
                <td><span class="badge ${estadoColors[oc.estado]}">${oc.estado}</span></td>
                <td class="num" style="color:var(--accent);">${fmt(oc.total)}</td>
                <td>${oc.fecha_entrega_esperada || '-'}</td>
                <td>${oc.created_at?.substring(0,10)}</td>
                <td>
                    ${oc.estado === 'borrador' ? `<button class="btn btn-secondary btn-sm" onclick="autorizarOC(${oc.id})">✓ Autorizar</button>` : ''}
                    ${oc.estado === 'autorizada' ? `<button class="btn btn-secondary btn-sm" onclick="enviarOC(${oc.id})">📤 Enviar</button>` : ''}
                    ${['enviada','parcial'].includes(oc.estado) ? `<button class="btn btn-secondary btn-sm" onclick="toast('Abra detalle para registrar recepción')">📦 Recibir</button>` : ''}
                </td>
            </tr>
        `).join('');
    } catch (e) { console.error('Error cargando OC', e); }
}

async function cargarProveedores() {
    try {
        const res = await api('GET', '/api/proveedores?per_page=50');
        const provs = res.data || [];
        const tbody = document.getElementById('provTableBody');
        if (!provs.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin proveedores registrados</td></tr>';
            return;
        }
        tbody.innerHTML = provs.map(p => `
            <tr>
                <td style="font-weight:600;">${p.nombre}</td>
                <td class="mono">${p.rut || '-'}</td>
                <td>${p.contacto_nombre || '-'}</td>
                <td>${p.telefono || '-'}</td>
                <td>${p.plazo_pago_dias} días</td>
                <td><span class="badge ${p.activo ? 'badge-green' : 'badge-gray'}">${p.activo ? 'Activo' : 'Inactivo'}</span></td>
            </tr>
        `).join('');
    } catch (e) { console.error('Error cargando proveedores', e); }
}

async function cargarAlertas() {
    try {
        const alertas = await api('GET', '/api/compras/alertas-stock');
        const tbody = document.getElementById('alertasTableBody');
        if (!alertas.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--ok);">✓ Sin alertas de stock bajo</td></tr>';
            return;
        }
        tbody.innerHTML = alertas.map(a => `
            <tr>
                <td style="font-weight:600;">${a.nombre}</td>
                <td class="num" style="color:${a.stock_actual <= 0 ? 'var(--err)' : 'var(--warn)'};">${a.stock_actual}</td>
                <td class="num">${a.stock_minimo}</td>
                <td>${a.proveedor || '<span style="color:var(--t2)">Sin proveedor</span>'}</td>
                <td class="num" style="color:var(--accent);">${a.sugerido}</td>
                <td>${a.proveedor_id ? `<button class="btn btn-secondary btn-sm" onclick="toast('Crear OC automática en desarrollo')">+ OC Auto</button>` : '-'}</td>
            </tr>
        `).join('');
    } catch (e) { console.error('Error cargando alertas', e); }
}

async function autorizarOC(id) {
    try {
        const res = await api('POST', `/api/ordenes-compra/${id}/autorizar`);
        toast(res.message);
        cargarOC();
        cargarDashboard();
    } catch (e) { toast('Error autorizando OC', 'err'); }
}

async function enviarOC(id) {
    try {
        const res = await api('POST', `/api/ordenes-compra/${id}/enviar`);
        toast(res.message);
        cargarOC();
    } catch (e) { toast('Error enviando OC', 'err'); }
}

function mostrarModalProveedor() { toast('Formulario de nuevo proveedor en desarrollo'); }
function mostrarModalOC() { toast('Formulario de nueva OC en desarrollo'); }
</script>
@endpush
@endsection
