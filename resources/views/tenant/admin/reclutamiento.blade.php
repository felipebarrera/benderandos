@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Reclutamiento y Talento</div>
            <div class="page-sub">Ofertas de empleo, postulaciones y entrevistas</div>
        </div>
        <button class="btn btn-primary" onclick="toast('Crear oferta UI en desarrollo')">+ Nueva Oferta</button>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">Ofertas Activas</div>
            <div class="kpi-value" style="color:var(--accent);" id="kpi-ofertas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Nuevas Postulaciones</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-nuevas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Entrevistas Semana</div>
            <div class="kpi-value" style="color:var(--warn);" id="kpi-entrevistas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">En Pipeline</div>
            <div class="kpi-value" style="color:var(--t1);" id="kpi-pipeline">0</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--b2);">
        <button class="btn btn-secondary tab-btn active" data-tab="pipeline" onclick="switchTab('pipeline', this)">Postulaciones</button>
        <button class="btn btn-secondary tab-btn" data-tab="ofertas" onclick="switchTab('ofertas', this)">Ofertas de Empleo</button>
    </div>

    {{-- Tab: Postulaciones Pipeline --}}
    <div class="tab-content" id="tab-pipeline">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Candidato</th><th>Oferta</th><th>Email / Teléfono</th><th>Estado Pipeline</th><th>Fecha</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="postulacionesBody">
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--t2);">Cargando postulaciones...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Ofertas --}}
    <div class="tab-content" id="tab-ofertas" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Título / Cargo</th><th>Modalidad</th><th>Sueldo</th><th>Postulantes Act.</th><th>Estado</th><th>Cierre</th></tr>
                    </thead>
                    <tbody id="ofertasBody">
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--t2);">Cargando ofertas...</td></tr>
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
    cargarPostulaciones();
    cargarOfertas();
});

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

async function cargarDashboard() {
    try {
        const res = await api('GET', '/api/reclutamiento/dashboard');
        document.getElementById('kpi-ofertas').textContent = res.kpis.ofertas_activas || 0;
        document.getElementById('kpi-nuevas').textContent = res.kpis.postulaciones_nuevas || 0;
        document.getElementById('kpi-entrevistas').textContent = res.kpis.entrevistas_semana || 0;
        document.getElementById('kpi-pipeline').textContent = res.kpis.candidatos_pipeline || 0;
    } catch(e) { console.error('Error dashboard reclutamiento', e); }
}

async function cargarPostulaciones() {
    try {
        const res = await api('GET', '/api/reclutamiento/postulaciones?per_page=50');
        const posts = res.data || [];
        const tbody = document.getElementById('postulacionesBody');
        if (!posts.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin postulaciones</td></tr>'; return; }
        
        const badges = {
            recibida: 'badge-gray', preseleccionada: 'badge-blue', entrevista: 'badge-orange',
            evaluacion: 'badge-indigo', oferta: 'badge-green', contratada: 'badge-green', descartada: 'badge-red'
        };

        tbody.innerHTML = posts.map(p => `
            <tr>
                <td style="font-weight:600;">${p.nombre}</td>
                <td>${p.oferta?.titulo || '-'}</td>
                <td style="font-size:12px;">${p.email}<br><span style="color:var(--t2)">${p.telefono||'-'}</span></td>
                <td><span class="badge ${badges[p.estado] || 'badge-gray'}">${p.estado.toUpperCase()}</span></td>
                <td>${p.created_at?.substring(0,10)}</td>
                <td style="display:flex; gap:4px;">
                    <select class="input-sm" style="width:120px;" onchange="avanzarPipeline(${p.id}, this)">
                        <option value="">Avanzar a...</option>
                        <option value="preseleccionada" ${p.estado==='recibida'?'':'disabled'}>Preseleccionar</option>
                        <option value="entrevista">Entrevista</option>
                        <option value="evaluacion">Evaluación</option>
                        <option value="oferta">Oferta</option>
                        <option value="contratada">Contratar (Auto Empleado)</option>
                        <option value="descartada" style="color:red;">Descartar</option>
                    </select>
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando postulaciones', e); }
}

async function cargarOfertas() {
    try {
        const res = await api('GET', '/api/reclutamiento/ofertas?per_page=50');
        const ofertas = res.data || [];
        const tbody = document.getElementById('ofertasBody');
        if (!ofertas.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin ofertas</td></tr>'; return; }
        
        tbody.innerHTML = ofertas.map(o => `
            <tr>
                <td>
                    <div style="font-weight:600;">${o.titulo}</div>
                    <div style="font-size:11px; color:var(--t2);"><a href="/empleo/${o.slug}" target="_blank">/empleo/${o.slug}</a></div>
                </td>
                <td>${o.modalidad} - ${o.jornada}</td>
                <td>${o.sueldo_min ? fmt(o.sueldo_min) + ' - ' + fmt(o.sueldo_max) : 'A convenir'}</td>
                <td class="num" style="color:var(--accent); font-weight:bold;">${o.postulantes_activos || 0}</td>
                <td><span class="badge ${o.estado==='publicada'?'badge-green':(o.estado==='borrador'?'badge-gray':'badge-orange')}">${o.estado.toUpperCase()}</span></td>
                <td>${o.fecha_cierre || 'Sin límite'}</td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando ofertas', e); }
}

async function avanzarPipeline(id, selectElement) {
    const estado = selectElement.value;
    if (!estado) return;

    if (!confirm(`¿Mover a ${estado}? ${estado === 'contratada' ? 'Se creará un perfil de empleado automáticamente.' : ''} Se enviará notificación por WhatsApp/Email.`)) {
        selectElement.value = '';
        return;
    }

    try {
        const res = await api('POST', `/api/reclutamiento/postulaciones/${id}/mover`, { estado });
        toast('Postulación actualizada: ' + res.postulacion.estado);
        cargarPostulaciones();
        cargarDashboard();
    } catch(e) {
        toast('Error. Revisa que el flujo sea válido (recibida > preseleccionada > entrevista...).', 'err');
        selectElement.value = '';
    }
}
</script>
@endpush
@endsection
