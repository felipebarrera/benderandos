@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Marketing QR</div>
            <div class="page-sub">Campañas dinámicas, generación de códigos web y analíticas</div>
        </div>
        <button class="btn btn-primary" onclick="nuevaCampana()">+ Nueva Campaña</button>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">Campañas Activas</div>
            <div class="kpi-value" style="color:var(--accent);" id="kpi-activas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Escaneos</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-escaneos">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Conversiones POS</div>
            <div class="kpi-value" style="color:var(--t1);" id="kpi-conversiones">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Tasa de Conversión</div>
            <div class="kpi-value" style="color:var(--warn);" id="kpi-tasa">0%</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--b2);">
        <button class="btn btn-secondary tab-btn active" data-tab="campanas" onclick="switchTab('campanas', this)">Campañas</button>
        <button class="btn btn-secondary tab-btn" data-tab="escaneos" onclick="switchTab('escaneos', this)">Registro de Escaneos</button>
    </div>

    {{-- Tab: Campañas --}}
    <div class="tab-content" id="tab-campanas">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Campaña</th><th>Tipo de Acción</th><th>Código POS</th><th>Métricas</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="campanasBody">
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Escaneos --}}
    <div class="tab-content" id="tab-escaneos" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Fecha</th><th>Campaña</th><th>Ubicación QR</th><th>Dispositivo</th><th>IP</th><th>Conversión POS</th></tr>
                    </thead>
                    <tbody id="escaneosBody">
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--t2);">Cargando...</td></tr>
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
    cargarCampanas();
    cargarEscaneos();
});

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

async function cargarDashboard() {
    try {
        const res = await api('GET', '/api/marketing/dashboard');
        document.getElementById('kpi-activas').textContent = res.kpis.campanas_activas || 0;
        document.getElementById('kpi-escaneos').textContent = res.kpis.total_escaneos || 0;
        document.getElementById('kpi-conversiones').textContent = res.kpis.conversiones || 0;
        document.getElementById('kpi-tasa').textContent = (res.kpis.tasa_conversion || 0) + '%';
    } catch(e) { console.error('Error dashboard', e); }
}

async function cargarCampanas() {
    try {
        const res = await api('GET', '/api/marketing/campanas?per_page=50');
        const camps = res.data || [];
        const tbody = document.getElementById('campanasBody');
        if (!camps.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin campañas</td></tr>'; return; }
        
        tbody.innerHTML = camps.map(c => `
            <tr>
                <td style="font-weight:600;">${c.nombre}</td>
                <td><span class="badge badge-gray">${c.tipo_accion.replace('_', ' ').toUpperCase()}</span></td>
                <td class="mono" style="font-weight:bold; color:var(--accent);">${c.codigo_pos || '-'}</td>
                <td style="font-size:12px;">
                    Usos: ${c.usos_actuales} / ${c.limite_usos || '∞'}<br>
                    QRs: ${c.qrs_count || 0}
                </td>
                <td><span class="badge ${c.estado==='activa'?'badge-green':(c.estado==='pausada'?'badge-orange':'badge-gray')}">${c.estado}</span></td>
                <td style="display:flex; gap:4px;">
                    <button class="btn btn-secondary btn-sm" title="Generar QR" onclick="mostrarQrs(${c.id})"><svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg></button>
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando campañas', e); }
}

async function cargarEscaneos() {
    try {
        const res = await api('GET', '/api/marketing/escaneos?per_page=50');
        const escaneos = res.data || [];
        const tbody = document.getElementById('escaneosBody');
        if (!escaneos.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin escaneos</td></tr>'; return; }
        
        tbody.innerHTML = escaneos.map(e => `
            <tr>
                <td>${e.fecha_escaneo}</td>
                <td style="font-weight:600;">${e.qr?.campana?.nombre || '-'}</td>
                <td>${e.qr?.ubicacion_fisica || 'General'}</td>
                <td><span class="badge badge-gray">${e.device_type}</span></td>
                <td class="mono" style="font-size:12px; color:var(--t2);">${e.ip_address}</td>
                <td>
                    ${e.convertido 
                        ? '<span class="badge badge-green">Sí (Venta #'+e.venta_id+')</span>' 
                        : '<span class="badge badge-red">No</span>'}
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando escaneos', e); }
}

async function mostrarQrs(id) {
    try {
        const campana = await api('GET', '/api/marketing/campanas/' + id);
        let htmlContent = '<div style="display:flex; flex-wrap:wrap; gap:10px;">';
        
        if (campana.qrs && campana.qrs.length > 0) {
            campana.qrs.forEach(qr => {
                htmlContent += `
                    <div style="border:1px solid var(--b2); padding:10px; border-radius:8px; text-align:center;">
                        <img src="${qr.qr_url}" alt="QR" style="max-width:200px; display:block; margin:0 auto;">
                        <div style="margin-top:8px; font-weight:bold; font-size:14px;">Ubicación: ${qr.ubicacion_fisica || 'General'}</div>
                        <div style="font-size:12px; color:var(--t2); margin-top:4px;">Links generados por QuickChart.io</div>
                        <a href="${qr.qr_url}" target="_blank" class="btn btn-secondary btn-sm" style="margin-top:8px;">Descargar</a>
                    </div>
                `;
            });
        }
        htmlContent += '</div>';

        const dialog = document.createElement('dialog');
        dialog.className = 'modal-card';
        dialog.style.display = 'block';
        dialog.style.position = 'fixed';
        dialog.style.top = '10%';
        dialog.style.maxWidth = '800px';
        dialog.style.zIndex = '9999';
        
        dialog.innerHTML = `
            <h3>QRs para ${campana.nombre}</h3>
            ${htmlContent}
            <div style="margin-top:16px;">
                <input type="text" id="nuevaUbic" placeholder="Nueva ubicación física (ej: Mesa 5)" class="input" style="width:200px;">
                <button class="btn btn-primary" onclick="window.generarNuevoQr(${id}, document.getElementById('nuevaUbic').value, this)">Generar Nuevo QR</button>
            </div>
            <button class="btn btn-secondary" style="margin-top:16px; width:100%;" onclick="this.closest('dialog').remove()">Cerrar</button>
        `;
        document.body.appendChild(dialog);
    } catch(e) { console.error(e); toast('Error al cargar QRs', 'err'); }
}

window.generarNuevoQr = async function(id, ubicacion) {
    try {
        await api('POST', `/api/marketing/campanas/${id}/qrs`, { ubicacion_fisica: ubicacion });
        toast('QR generado exitosamente');
        document.querySelector('dialog').remove();
        mostrarQrs(id); // Reload modal
        cargarCampanas();
    } catch(e) { toast('Error', 'err'); }
}

async function nuevaCampana() {
    const nombre = prompt('Nombre de la campaña (ej: Descuento Verano):');
    if(!nombre) return;
    try {
        await api('POST', '/api/marketing/campanas', {
            nombre,
            descripcion: 'Campaña automática',
            tipo_accion: 'descuento_porcentaje',
            valor_descuento: 10,
            fecha_inicio: new Date().toISOString().split('T')[0],
            estado: 'activa'
        });
        toast('Campaña creada. Edita sus detalles desde el backend si es necesario.');
        cargarCampanas();
        cargarDashboard();
    } catch(e) { toast('Error al crear campaña', 'err'); }
}
</script>
@endpush
@endsection
