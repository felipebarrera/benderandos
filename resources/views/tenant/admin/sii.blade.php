@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Facturación Electrónica SII</div>
            <div class="page-sub">Dashboard de documentos tributarios, libro de ventas y configuración</div>
        </div>
        <button class="btn btn-primary" id="btnEmitirManual" onclick="mostrarModalEmitir()">+ Emitir DTE Manual</button>
    </div>

    {{-- KPIs del Día --}}
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">Boletas Hoy</div>
            <div class="kpi-value" style="color:var(--accent);" id="kpi-boletas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Facturas Hoy</div>
            <div class="kpi-value" style="color:var(--warn);" id="kpi-facturas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Emitido</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-total">$0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Pendientes SII</div>
            <div class="kpi-value" style="color:var(--err);" id="kpi-pendientes">0</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--b2); padding-bottom:0;">
        <button class="btn btn-secondary tab-btn active" data-tab="dtes" onclick="switchTab('dtes', this)">DTEs Emitidos</button>
        <button class="btn btn-secondary tab-btn" data-tab="libro" onclick="switchTab('libro', this)">Libro de Ventas</button>
        <button class="btn btn-secondary tab-btn" data-tab="config" onclick="switchTab('config', this)">Configuración SII</button>
    </div>

    {{-- Tab: DTEs Emitidos --}}
    <div class="tab-content" id="tab-dtes">
        <div class="card">
            <div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
                <select id="filtroTipo" onchange="cargarDtes()" style="max-width:200px;">
                    <option value="">Todos los tipos</option>
                    <option value="39">Boleta (39)</option>
                    <option value="33">Factura (33)</option>
                    <option value="61">Nota Crédito (61)</option>
                </select>
                <select id="filtroEstado" onchange="cargarDtes()" style="max-width:200px;">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="enviado">Enviado</option>
                    <option value="ACE">Aceptado (ACE)</option>
                    <option value="REC">Rechazado (REC)</option>
                    <option value="REP">Reparos (REP)</option>
                </select>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tipo</th>
                            <th>Receptor</th>
                            <th>RUT</th>
                            <th class="num">Neto</th>
                            <th class="num">IVA</th>
                            <th class="num">Total</th>
                            <th>Estado SII</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="dtesTableBody">
                        <tr><td colspan="10" style="text-align:center; padding:30px; color:var(--t2);">Cargando DTEs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Libro de Ventas --}}
    <div class="tab-content" id="tab-libro" style="display:none;">
        <div class="card">
            <div style="display:flex; gap:10px; align-items:center; margin-bottom:20px;">
                <select id="libroMes" style="max-width:150px;">
                    <option value="1">Enero</option>
                    <option value="2">Febrero</option>
                    <option value="3" selected>Marzo</option>
                    <option value="4">Abril</option>
                    <option value="5">Mayo</option>
                    <option value="6">Junio</option>
                    <option value="7">Julio</option>
                    <option value="8">Agosto</option>
                    <option value="9">Septiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                </select>
                <input type="number" id="libroAnio" value="2026" style="max-width:100px;">
                <button class="btn btn-secondary" onclick="cargarLibro()">Consultar</button>
                <button class="btn btn-secondary" onclick="exportarCSV()">⬇ Exportar CSV</button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px; margin-bottom:20px;">
                <div style="padding:14px; background:var(--s2); border-radius:8px; text-align:center;">
                    <div style="font-size:11px; color:var(--t2); text-transform:uppercase;">Documentos</div>
                    <div style="font-size:22px; font-weight:700; color:var(--accent);" id="libro-cantidad">0</div>
                </div>
                <div style="padding:14px; background:var(--s2); border-radius:8px; text-align:center;">
                    <div style="font-size:11px; color:var(--t2); text-transform:uppercase;">Neto</div>
                    <div style="font-size:22px; font-weight:700;" id="libro-neto">$0</div>
                </div>
                <div style="padding:14px; background:var(--s2); border-radius:8px; text-align:center;">
                    <div style="font-size:11px; color:var(--t2); text-transform:uppercase;">IVA</div>
                    <div style="font-size:22px; font-weight:700;" id="libro-iva">$0</div>
                </div>
                <div style="padding:14px; background:var(--s2); border-radius:8px; text-align:center;">
                    <div style="font-size:11px; color:var(--t2); text-transform:uppercase;">Total</div>
                    <div style="font-size:22px; font-weight:700; color:var(--ok);" id="libro-total">$0</div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Folio</th><th>Tipo</th><th>RUT</th><th>Razón Social</th><th class="num">Neto</th><th class="num">IVA</th><th class="num">Total</th><th>Fecha</th></tr>
                    </thead>
                    <tbody id="libroTableBody">
                        <tr><td colspan="8" style="text-align:center; color:var(--t2);">Seleccione mes y año</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Configuración SII --}}
    <div class="tab-content" id="tab-config" style="display:none;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
            <div class="card">
                <h3 style="margin-bottom:16px; font-size:16px; font-weight:600;">Datos del Contribuyente</h3>
                <form id="siiConfigForm">
                    <div class="field">
                        <label class="label">RUT Empresa</label>
                        <input type="text" name="rut_empresa" id="cfg_rut" placeholder="76.000.000-0">
                    </div>
                    <div class="field">
                        <label class="label">Razón Social</label>
                        <input type="text" name="razon_social" id="cfg_razon" placeholder="Mi Empresa SpA">
                    </div>
                    <div class="field">
                        <label class="label">Giro</label>
                        <input type="text" name="giro" id="cfg_giro" placeholder="Venta al por menor">
                    </div>
                    <div class="field">
                        <label class="label">Código Actividad Económica</label>
                        <input type="text" name="acteco" id="cfg_acteco" placeholder="523110">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div class="field">
                            <label class="label">Dirección</label>
                            <input type="text" name="direccion" id="cfg_dir" placeholder="Av. Principal 123">
                        </div>
                        <div class="field">
                            <label class="label">Comuna</label>
                            <input type="text" name="comuna" id="cfg_comuna" placeholder="Santiago">
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">Ciudad</label>
                        <input type="text" name="ciudad" id="cfg_ciudad" placeholder="Santiago">
                    </div>
                    <div class="field">
                        <label class="label">Email para DTEs</label>
                        <input type="email" name="email_dte" id="cfg_email" placeholder="facturacion@miempresa.cl">
                    </div>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-bottom:16px; font-size:16px; font-weight:600;">Configuración Técnica</h3>
                <div class="field">
                    <label class="label">Ambiente</label>
                    <select id="cfg_ambiente">
                        <option value="certificacion">🧪 Certificación (Pruebas)</option>
                        <option value="produccion">🟢 Producción (Real)</option>
                    </select>
                </div>
                <div class="field">
                    <label class="label">Documento por Defecto</label>
                    <select id="cfg_doc_default">
                        <option value="boleta">Boleta Electrónica (39)</option>
                        <option value="factura">Factura Electrónica (33)</option>
                    </select>
                </div>
                <div class="field">
                    <label class="label">API Key LibreDTE</label>
                    <input type="password" id="cfg_hash" placeholder="••••••••••••">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div class="field">
                        <label class="label">Fecha Resolución SII</label>
                        <input type="text" id="cfg_res_fecha" placeholder="2026-01-15">
                    </div>
                    <div class="field">
                        <label class="label">N° Resolución</label>
                        <input type="number" id="cfg_res_num" placeholder="0">
                    </div>
                </div>
                <div style="margin-top:20px; padding:14px; background:var(--s2); border-radius:8px; border-left:3px solid var(--warn);">
                    <div style="font-size:12px; color:var(--warn); font-weight:600;">⚠ Ambiente actual: <span id="ambienteLabel">Certificación</span></div>
                    <div style="font-size:11px; color:var(--t2); margin-top:4px;">En certificación los DTEs se simulan. Configure producción solo con certificado digital real.</div>
                </div>
                <button class="btn btn-primary" style="margin-top:16px; width:100%;" onclick="guardarConfig()">Guardar Configuración</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarDashboard();
    cargarDtes();
    cargarConfig();
});

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

const tiposLabel = { 33: 'Factura', 39: 'Boleta', 61: 'N. Crédito' };

function estadoBadge(e) {
    const colors = { pendiente: 'badge-gray', enviado: 'badge-blue', ACE: 'badge-green', REC: 'badge-red', REP: 'badge-orange', error: 'badge-red' };
    return `<span class="badge ${colors[e] || 'badge-gray'}">${e}</span>`;
}

async function cargarDashboard() {
    try {
        const res = await api('GET', '/api/sii/dashboard');
        document.getElementById('kpi-boletas').textContent = res.resumen.boletas_hoy || 0;
        document.getElementById('kpi-facturas').textContent = res.resumen.facturas_hoy || 0;
        document.getElementById('kpi-total').textContent = fmt(res.resumen.total_emitido || 0);
        document.getElementById('kpi-pendientes').textContent = res.resumen.pendientes_sii || 0;
    } catch (e) {
        console.error('Error cargando dashboard SII', e);
    }
}

async function cargarDtes() {
    const tipo = document.getElementById('filtroTipo').value;
    const estado = document.getElementById('filtroEstado').value;
    let url = '/api/sii/dtes?per_page=20';
    if (tipo) url += `&tipo=${tipo}`;
    if (estado) url += `&estado=${estado}`;

    try {
        const res = await api('GET', url);
        const dtes = res.data || [];
        const tbody = document.getElementById('dtesTableBody');

        if (!dtes.length) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align:center; color:var(--t2); padding:30px;">No hay DTEs emitidos</td></tr>';
            return;
        }

        tbody.innerHTML = dtes.map(d => `
            <tr>
                <td class="mono">#${d.folio}</td>
                <td><span class="badge badge-blue">${tiposLabel[d.tipo_dte] || d.tipo_dte}</span></td>
                <td>${d.razon_social_receptor || '-'}</td>
                <td class="mono">${d.rut_receptor || '-'}</td>
                <td class="num">${fmt(d.monto_neto)}</td>
                <td class="num">${fmt(d.monto_iva)}</td>
                <td class="num" style="color:var(--accent);">${fmt(d.monto_total)}</td>
                <td>${estadoBadge(d.estado_sii)}</td>
                <td>${d.fecha_emision}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="consultarEstado(${d.id})">↻</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        console.error('Error cargando DTEs', e);
    }
}

async function consultarEstado(dteId) {
    try {
        const res = await api('POST', `/api/sii/consultar-estado/${dteId}`);
        toast(`Estado actualizado: ${res.estado}`);
        cargarDtes();
        cargarDashboard();
    } catch (e) {
        toast('Error consultando estado', 'err');
    }
}

async function cargarLibro() {
    const mes = document.getElementById('libroMes').value;
    const anio = document.getElementById('libroAnio').value;

    try {
        const res = await api('GET', `/api/sii/libro-ventas?mes=${mes}&anio=${anio}`);
        document.getElementById('libro-cantidad').textContent = res.totales.cantidad;
        document.getElementById('libro-neto').textContent = fmt(res.totales.neto);
        document.getElementById('libro-iva').textContent = fmt(res.totales.iva);
        document.getElementById('libro-total').textContent = fmt(res.totales.total);

        const tbody = document.getElementById('libroTableBody');
        const dtes = res.dtes || [];

        if (!dtes.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:var(--t2);">Sin documentos para este período</td></tr>';
            return;
        }

        tbody.innerHTML = dtes.map(d => `
            <tr>
                <td class="mono">#${d.folio}</td>
                <td>${tiposLabel[d.tipo_dte] || d.tipo_dte}</td>
                <td class="mono">${d.rut_receptor || '-'}</td>
                <td>${d.razon_social_receptor || '-'}</td>
                <td class="num">${fmt(d.monto_neto)}</td>
                <td class="num">${fmt(d.monto_iva)}</td>
                <td class="num" style="color:var(--accent);">${fmt(d.monto_total)}</td>
                <td>${d.fecha_emision}</td>
            </tr>
        `).join('');
    } catch (e) {
        toast('Error cargando libro de ventas', 'err');
    }
}

async function cargarConfig() {
    try {
        const cfg = await api('GET', '/api/sii/config');
        if (cfg) {
            document.getElementById('cfg_rut').value = cfg.rut_empresa || '';
            document.getElementById('cfg_razon').value = cfg.razon_social || '';
            document.getElementById('cfg_giro').value = cfg.giro || '';
            document.getElementById('cfg_acteco').value = cfg.acteco || '';
            document.getElementById('cfg_dir').value = cfg.direccion || '';
            document.getElementById('cfg_comuna').value = cfg.comuna || '';
            document.getElementById('cfg_ciudad').value = cfg.ciudad || '';
            document.getElementById('cfg_email').value = cfg.email_dte || '';
            document.getElementById('cfg_ambiente').value = cfg.ambiente || 'certificacion';
            document.getElementById('cfg_doc_default').value = cfg.documento_default || 'boleta';
            document.getElementById('cfg_res_fecha').value = cfg.resolucion_fecha || '';
            document.getElementById('cfg_res_num').value = cfg.resolucion_numero || '';
            document.getElementById('ambienteLabel').textContent =
                cfg.ambiente === 'produccion' ? '🟢 Producción' : '🧪 Certificación';
        }
    } catch (e) {
        console.log('Sin configuración SII previa');
    }
}

async function guardarConfig() {
    const data = {
        rut_empresa:       document.getElementById('cfg_rut').value,
        razon_social:      document.getElementById('cfg_razon').value,
        giro:              document.getElementById('cfg_giro').value,
        acteco:            document.getElementById('cfg_acteco').value,
        direccion:         document.getElementById('cfg_dir').value,
        comuna:            document.getElementById('cfg_comuna').value,
        ciudad:            document.getElementById('cfg_ciudad').value,
        email_dte:         document.getElementById('cfg_email').value,
        ambiente:          document.getElementById('cfg_ambiente').value,
        documento_default: document.getElementById('cfg_doc_default').value,
        libredte_hash:     document.getElementById('cfg_hash').value || undefined,
        resolucion_fecha:  document.getElementById('cfg_res_fecha').value,
        resolucion_numero: document.getElementById('cfg_res_num').value || null,
    };

    try {
        const res = await api('PUT', '/api/sii/config', data);
        toast(res.message || 'Configuración guardada');
        cargarConfig();
    } catch (e) {
        toast(e.message || 'Error guardando configuración', 'err');
    }
}

function exportarCSV() {
    toast('Función de exportación CSV en desarrollo');
}

function mostrarModalEmitir() {
    toast('Seleccione una venta desde el historial para emitir DTE manual');
}
</script>
@endpush
@endsection
