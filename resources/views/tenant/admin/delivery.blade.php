@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Delivery y Logística</div>
            <div class="page-sub">Control de entregas, repartidores y zonas de envío</div>
        </div>
        <button class="btn btn-primary" onclick="toast('Las entregas se crean automáticamente al confirmar ventas con envío')">ℹ Info</button>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">Entregas Activas</div>
            <div class="kpi-value" style="color:var(--warn);" id="kpi-activas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Pendientes</div>
            <div class="kpi-value" style="color:var(--err);" id="kpi-pendientes">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Entregadas Hoy</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-entregadas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Tiempo Promedio</div>
            <div class="kpi-value" style="color:var(--accent);" id="kpi-tiempo">-</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--b2);">
        <button class="btn btn-secondary tab-btn active" data-tab="entregas" onclick="switchTab('entregas', this)">Entregas</button>
        <button class="btn btn-secondary tab-btn" data-tab="repartidores" onclick="switchTab('repartidores', this)">Repartidores</button>
        <button class="btn btn-secondary tab-btn" data-tab="zonas" onclick="switchTab('zonas', this)">Zonas de Envío</button>
    </div>

    {{-- Tab: Entregas --}}
    <div class="tab-content" id="tab-entregas">
        <div class="card">
            <div style="display:flex; gap:10px; margin-bottom:16px;">
                <select id="filtroEstado" onchange="cargarEntregas()" style="max-width:200px;">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="asignada">Asignada</option>
                    <option value="en_preparacion">En Preparación</option>
                    <option value="en_camino">En Camino</option>
                    <option value="entregada">Entregada</option>
                    <option value="fallida">Fallida</option>
                </select>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Dirección</th><th>Cliente</th><th>Repartidor</th><th>Estado</th><th class="num">Costo</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="entregasBody">
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Repartidores --}}
    <div class="tab-content" id="tab-repartidores" style="display:none;">
        <div class="card">
            <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
                <button class="btn btn-secondary" onclick="toast('Formulario de repartidor en desarrollo')">+ Repartidor</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Nombre</th><th>Teléfono</th><th>Vehículo</th><th>Patente</th><th>Entregas Activas</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="repartidoresBody">
                        <tr><td colspan="6" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Zonas de Envío --}}
    <div class="tab-content" id="tab-zonas" style="display:none;">
        <div class="card">
            <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
                <button class="btn btn-secondary" onclick="toast('Formulario de zona en desarrollo')">+ Zona</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Zona</th><th>Código</th><th class="num">Costo Envío</th><th>T. Estimado</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="zonasBody">
                        <tr><td colspan="5" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
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
    cargarEntregas();
    cargarRepartidores();
    cargarZonas();
});

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

const estadoColors = {
    pendiente: 'badge-gray', asignada: 'badge-blue', en_preparacion: 'badge-orange',
    en_camino: 'badge-orange', entregada: 'badge-green', fallida: 'badge-red', cancelada: 'badge-gray'
};
const estadoLabels = {
    pendiente: '⏳ Pendiente', asignada: '👤 Asignada', en_preparacion: '📦 Preparando',
    en_camino: '🚗 En Camino', entregada: '✅ Entregada', fallida: '❌ Fallida'
};

async function cargarDashboard() {
    try {
        const res = await api('GET', '/api/delivery/dashboard');
        document.getElementById('kpi-activas').textContent = res.kpis.activas || 0;
        document.getElementById('kpi-pendientes').textContent = res.kpis.pendientes || 0;
        document.getElementById('kpi-entregadas').textContent = res.kpis.entregadas_hoy || 0;
        document.getElementById('kpi-tiempo').textContent = res.kpis.tiempo_promedio ? res.kpis.tiempo_promedio + ' min' : '-';
    } catch(e) { console.error('Error dashboard delivery', e); }
}

async function cargarEntregas() {
    const estado = document.getElementById('filtroEstado').value;
    let url = '/api/delivery/entregas?per_page=25';
    if (estado) url += `&estado=${estado}`;
    try {
        const res = await api('GET', url);
        const entregas = res.data || [];
        const tbody = document.getElementById('entregasBody');
        if (!entregas.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--t2);">Sin entregas</td></tr>';
            return;
        }
        tbody.innerHTML = entregas.map(e => `
            <tr>
                <td class="mono">#${e.id}</td>
                <td style="max-width:200px;">${e.direccion_entrega}</td>
                <td>${e.venta?.cliente?.nombre || '-'}</td>
                <td>${e.repartidor?.nombre || '<span style="color:var(--t2)">Sin asignar</span>'}</td>
                <td><span class="badge ${estadoColors[e.estado]}">${estadoLabels[e.estado] || e.estado}</span></td>
                <td class="num">${fmt(e.costo_envio)}</td>
                <td>
                    ${e.estado === 'pendiente' ? `<button class="btn btn-secondary btn-sm" onclick="asignarRepartidor(${e.id})">👤 Asignar</button>` : ''}
                    ${['asignada','en_preparacion','en_camino'].includes(e.estado) ? `<button class="btn btn-secondary btn-sm" onclick="avanzarEstado(${e.id},'${e.estado}')">▶ Avanzar</button>` : ''}
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando entregas', e); }
}

async function cargarRepartidores() {
    try {
        const reps = await api('GET', '/api/delivery/repartidores');
        const tbody = document.getElementById('repartidoresBody');
        if (!reps.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin repartidores</td></tr>';
            return;
        }
        tbody.innerHTML = reps.map(r => `
            <tr>
                <td style="font-weight:600;">${r.nombre}</td>
                <td>${r.telefono || '-'}</td>
                <td>${r.vehiculo || '-'}</td>
                <td class="mono">${r.patente || '-'}</td>
                <td class="num">${r.entregas_count || 0}</td>
                <td><span class="badge ${r.disponible && r.activo ? 'badge-green' : 'badge-gray'}">${r.disponible && r.activo ? '✅ Disponible' : '🔴 No disponible'}</span></td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando repartidores', e); }
}

async function cargarZonas() {
    try {
        const zonas = await api('GET', '/api/delivery/zonas');
        const tbody = document.getElementById('zonasBody');
        if (!zonas.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--t2);">Sin zonas de envío</td></tr>';
            return;
        }
        tbody.innerHTML = zonas.map(z => `
            <tr>
                <td style="font-weight:600;">${z.nombre}</td>
                <td class="mono">${z.codigo}</td>
                <td class="num" style="color:var(--accent);">${fmt(z.costo_envio)}</td>
                <td>${z.tiempo_estimado_min} min</td>
                <td><span class="badge ${z.activa ? 'badge-green' : 'badge-gray'}">${z.activa ? 'Activa' : 'Inactiva'}</span></td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando zonas', e); }
}

async function asignarRepartidor(entregaId) {
    // Obtener repartidores disponibles
    const reps = await api('GET', '/api/delivery/repartidores');
    const disponibles = reps.filter(r => r.disponible && r.activo);
    if (!disponibles.length) { toast('No hay repartidores disponibles', 'err'); return; }
    // Asignar al primer disponible (simplificado)
    try {
        const res = await api('POST', `/api/delivery/entregas/${entregaId}/asignar`, { repartidor_id: disponibles[0].id });
        toast(res.message);
        cargarEntregas();
        cargarDashboard();
    } catch(e) { toast('Error asignando repartidor', 'err'); }
}

async function avanzarEstado(id, estadoActual) {
    const siguiente = { asignada: 'en_preparacion', en_preparacion: 'en_camino', en_camino: 'entregada' };
    const nuevoEstado = siguiente[estadoActual];
    if (!nuevoEstado) return;
    try {
        const res = await api('POST', `/api/delivery/entregas/${id}/estado`, { estado: nuevoEstado });
        toast(res.message);
        cargarEntregas();
        cargarDashboard();
    } catch(e) { toast('Error cambiando estado', 'err'); }
}
</script>
@endpush
@endsection
