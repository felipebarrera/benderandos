@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Recursos Humanos</div>
            <div class="page-sub">Empleados, asistencia, vacaciones y liquidaciones</div>
        </div>
        <button class="btn btn-primary" onclick="abrirModalEmpleado()">+ Empleado</button>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">Empleados Activos</div>
            <div class="kpi-value" style="color:var(--accent);" id="kpi-empleados">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Presentes Hoy</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-presentes">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Atrasos Hoy</div>
            <div class="kpi-value" style="color:var(--warn);" id="kpi-atrasos">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Solicitudes Pend.</div>
            <div class="kpi-value" style="color:var(--err);" id="kpi-solicitudes">0</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--b2);">
        <button class="btn btn-secondary tab-btn active" data-tab="empleados" onclick="switchTab('empleados', this)">Empleados</button>
        <button class="btn btn-secondary tab-btn" data-tab="asistencia" onclick="switchTab('asistencia', this)">Asistencia Hoy</button>
        <button class="btn btn-secondary tab-btn" data-tab="vacaciones" onclick="switchTab('vacaciones', this)">Vacaciones</button>
        <button class="btn btn-secondary tab-btn" data-tab="liquidaciones" onclick="switchTab('liquidaciones', this)">Liquidaciones</button>
    </div>

    {{-- Tab: Empleados --}}
    <div class="tab-content" id="tab-empleados">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Nombre</th><th>RUT</th><th>Cargo</th><th>Contrato</th><th class="num">Sueldo Base</th><th>AFP</th><th>Salud</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="empleadosBody">
                        <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Asistencia --}}
    <div class="tab-content" id="tab-asistencia" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Empleado</th><th>Entrada</th><th>Salida</th><th class="num">Horas</th><th class="num">Atraso</th><th class="num">Extra</th></tr>
                    </thead>
                    <tbody id="asistenciaBody">
                        <tr><td colspan="6" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Vacaciones --}}
    <div class="tab-content" id="tab-vacaciones" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Empleado</th><th>Desde</th><th>Hasta</th><th>Días</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="vacacionesBody">
                        <tr><td colspan="6" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Liquidaciones --}}
    <div class="tab-content" id="tab-liquidaciones" style="display:none;">
        <div class="card">
            <div style="display:flex; gap:10px; margin-bottom:16px; align-items:center;">
                <button class="btn btn-secondary" onclick="generarMasivo()">⚡ Generar Masivo</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Empleado</th><th>Período</th><th class="num">Haberes</th><th class="num">Dctos</th><th class="num">Líquido</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="liquidacionesBody">
                        <tr><td colspan="6" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Empleado -->
<div class="modal-overlay" id="mEmpleado">
    <div class="modal" style="max-width:800px">
        <div class="modal-head">
            <span class="modal-title" id="mEmpTtl">Nuevo Empleado</span>
            <button class="modal-close" data-close-modal="mEmpleado">✕</button>
        </div>
        <div class="modal-body">
            <form id="empForm" onsubmit="guardarEmpleado(event)">
                <input type="hidden" id="meId">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="field">
                        <label class="label">Nombre Completo *</label>
                        <input type="text" id="meNombre" required>
                    </div>
                    <div class="field">
                        <label class="label">RUT *</label>
                        <input type="text" id="meRut" required placeholder="12.345.678-9">
                    </div>
                    <div class="field">
                        <label class="label">Email</label>
                        <input type="email" id="meEmail">
                    </div>
                    <div class="field">
                        <label class="label">Teléfono</label>
                        <input type="text" id="meTel">
                    </div>
                    <div class="field">
                        <label class="label">Cargo</label>
                        <input type="text" id="meCargo">
                    </div>
                    <div class="field">
                        <label class="label">Sueldo Base *</label>
                        <input type="number" id="meSueldo" required>
                    </div>
                    <div class="field">
                        <label class="label">Tipo Contrato</label>
                        <select id="meContrato">
                            <option value="indefinido">Indefinido</option>
                            <option value="plazo_fijo">Plazo Fijo</option>
                            <option value="honorarios">Honorarios</option>
                            <option value="part_time">Part Time</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="label">Fecha Ingreso *</label>
                        <input type="date" id="meIngreso" required>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('mEmpleado')">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEmp">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarDashboard();
    cargarEmpleados();
    cargarAsistencia();
    cargarVacaciones();
    cargarLiquidaciones();
});

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

async function cargarDashboard() {
    try {
        const res = await api('GET', '/api/rrhh/dashboard');
        document.getElementById('kpi-empleados').textContent = res.kpis.empleados_activos || 0;
        document.getElementById('kpi-presentes').textContent = res.kpis.presentes_hoy || 0;
        document.getElementById('kpi-atrasos').textContent = res.kpis.atrasos_hoy || 0;
        document.getElementById('kpi-solicitudes').textContent = (res.kpis.vacaciones_pendientes || 0) + (res.kpis.permisos_pendientes || 0);
    } catch(e) { console.error('Error dashboard rrhh', e); }
}

async function cargarEmpleados() {
    try {
        const res = await api('GET', '/api/rrhh/empleados?per_page=50');
        const emps = res.data || [];
        const tbody = document.getElementById('empleadosBody');
        if (!emps.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--t2);">Sin empleados</td></tr>'; return; }
        tbody.innerHTML = emps.map(e => `
            <tr>
                <td style="font-weight:600;">${e.nombre}</td>
                <td class="mono">${e.rut || '-'}</td>
                <td>${e.cargo || '-'}</td>
                <td>${e.tipo_contrato}</td>
                <td class="num" style="color:var(--accent);">${fmt(e.sueldo_base)}</td>
                <td>${e.afp || '-'}</td>
                <td>${e.salud || 'FONASA'}</td>
                <td><span class="badge ${e.activo ? 'badge-green' : 'badge-gray'}">${e.activo ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick='editarEmpleado(${JSON.stringify(e).replace(/'/g, "&apos;")})'>✏️</button>
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando empleados', e); }
}

async function cargarAsistencia() {
    try {
        const asis = await api('GET', '/api/rrhh/asistencia/hoy');
        const tbody = document.getElementById('asistenciaBody');
        if (!asis.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin registros hoy</td></tr>'; return; }
        tbody.innerHTML = asis.map(a => `
            <tr>
                <td style="font-weight:600;">${a.empleado?.nombre || '-'}</td>
                <td>${a.hora_entrada || '-'}</td>
                <td>${a.hora_salida || '<span style="color:var(--t2)">Pendiente</span>'}</td>
                <td class="num">${a.horas_trabajadas || '-'}h</td>
                <td class="num" style="color:${a.minutos_atraso > 0 ? 'var(--err)' : 'var(--ok)'};">${a.minutos_atraso} min</td>
                <td class="num">${a.minutos_extra} min</td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando asistencia', e); }
}

async function cargarVacaciones() {
    try {
        const res = await api('GET', '/api/rrhh/vacaciones?per_page=25');
        const vacs = res.data || [];
        const tbody = document.getElementById('vacacionesBody');
        if (!vacs.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin solicitudes</td></tr>'; return; }
        const estadoColors = { pendiente: 'badge-orange', aprobada: 'badge-green', rechazada: 'badge-red', cancelada: 'badge-gray' };
        tbody.innerHTML = vacs.map(v => `
            <tr>
                <td style="font-weight:600;">${v.empleado?.nombre || '-'}</td>
                <td>${v.fecha_inicio?.substring(0,10)}</td>
                <td>${v.fecha_fin?.substring(0,10)}</td>
                <td class="num">${v.dias_solicitados}</td>
                <td><span class="badge ${estadoColors[v.estado]}">${v.estado}</span></td>
                <td>
                    ${v.estado === 'pendiente' ? `
                        <button class="btn btn-secondary btn-sm" onclick="resolverVac(${v.id},'aprobada')">✓</button>
                        <button class="btn btn-secondary btn-sm" onclick="resolverVac(${v.id},'rechazada')">✗</button>
                    ` : ''}
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando vacaciones', e); }
}

async function cargarLiquidaciones() {
    try {
        const res = await api('GET', '/api/rrhh/liquidaciones?per_page=25');
        const liqs = res.data || [];
        const tbody = document.getElementById('liquidacionesBody');
        if (!liqs.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin liquidaciones</td></tr>'; return; }
        const meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        tbody.innerHTML = liqs.map(l => `
            <tr>
                <td style="font-weight:600;">${l.empleado?.nombre || '-'}</td>
                <td>${meses[l.mes]} ${l.anio}</td>
                <td class="num">${fmt(l.total_haberes)}</td>
                <td class="num" style="color:var(--err);">${fmt(l.total_descuentos)}</td>
                <td class="num" style="color:var(--ok); font-weight:700;">${fmt(l.sueldo_liquido)}</td>
                <td><span class="badge ${l.estado === 'pagada' ? 'badge-green' : 'badge-orange'}">${l.estado}</span></td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando liquidaciones', e); }
}

async function resolverVac(id, estado) {
    try {
        const res = await api('POST', `/api/rrhh/vacaciones/${id}/resolver`, { estado });
        toast(res.message);
        cargarVacaciones();
        cargarDashboard();
    } catch(e) { toast('Error', 'err'); }
}

async function generarMasivo() {
    const now = new Date();
    const anio = now.getFullYear();
    const mes = now.getMonth() + 1;
    try {
        const res = await api('POST', '/api/rrhh/liquidaciones/masivo', { anio, mes });
        toast(res.message);
        cargarLiquidaciones();
    } catch(e) { toast('Error generando liquidaciones', 'err'); }
}

function abrirModalEmpleado() {
    document.getElementById('empForm').reset();
    document.getElementById('meId').value = '';
    document.getElementById('mEmpTtl').textContent = 'Nuevo Empleado';
    openModal('mEmpleado');
}

function editarEmpleado(e) {
    document.getElementById('mEmpTtl').textContent = 'Editar Empleado';
    document.getElementById('meId').value = e.id;
    document.getElementById('meNombre').value = e.nombre;
    document.getElementById('meRut').value = e.rut || '';
    document.getElementById('meEmail').value = e.email || '';
    document.getElementById('meTel').value = e.telefono || '';
    document.getElementById('meCargo').value = e.cargo || '';
    document.getElementById('meSueldo').value = e.sueldo_base;
    document.getElementById('meContrato').value = e.tipo_contrato;
    document.getElementById('meIngreso').value = e.fecha_ingreso?.substring(0, 10);
    openModal('mEmpleado');
}

async function guardarEmpleado(ev) {
    ev.preventDefault();
    const btn = document.getElementById('btnGuardarEmp');
    btn.disabled = true;
    const id = document.getElementById('meId').value;
    const payload = {
        nombre: document.getElementById('meNombre').value,
        rut: document.getElementById('meRut').value,
        email: document.getElementById('meEmail').value,
        telefono: document.getElementById('meTel').value,
        cargo: document.getElementById('meCargo').value,
        sueldo_base: parseInt(document.getElementById('meSueldo').value),
        tipo_contrato: document.getElementById('meContrato').value,
        fecha_ingreso: document.getElementById('meIngreso').value,
    };
    try {
        const url = id ? `/api/rrhh/empleados/${id}` : '/api/rrhh/empleados';
        const method = id ? 'PUT' : 'POST';
        await api(method, url, payload);
        toast('✓ Empleado guardado');
        closeModal('mEmpleado');
        cargarEmpleados();
        cargarDashboard();
    } catch(e) {
        toast(e.message || 'Error al guardar', 'err');
    } finally {
        btn.disabled = false;
    }
}
</script>
@endpush
@endsection
