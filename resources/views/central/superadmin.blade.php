@extends('layouts.superadmin')

@section('content')
<!-- LOGIN OVERLAY -->
<div id="login-overlay" class="overlay" style="display:none">
  <div class="login-card">
    <div style="text-align:center;margin-bottom:24px">
      <div style="font-family:var(--mono);font-weight:700;font-size:24px;color:var(--ac)">B&</div>
      <div style="font-size:13px;color:var(--t2);margin-top:4px">Super Admin Access</div>
    </div>
    <div class="form-group">
      <label class="form-lbl">Email</label>
      <input type="email" id="login-email" class="form-input" placeholder="admin@benderand.cl">
    </div>
    <div class="form-group">
      <label class="form-lbl">Password</label>
      <input type="password" id="login-password" class="form-input" placeholder="••••••••">
    </div>
    <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px" onclick="doLogin()">Entrar</button>
  </div>
</div>

<div class="page active" id="page-dashboard">
  <div class="page-hdr"><div class="page-title">Dashboard</div></div>
  <div class="kpi-row" id="dashboard-kpis"></div>
  <div class="card">
    <div class="card-hdr"><span class="card-title">MRR History</span></div>
    <div class="chart-placeholder" id="mrr-chart"></div>
  </div>
</div>

<div class="page" id="page-tenants">
    <div class="page-hdr"><div class="page-title">Tenants</div></div>
    <div class="card"><table class="tbl" id="tbl-tenants"></table></div>
</div>

<div class="page" id="page-modulos">
    <div class="page-hdr">
        <div class="page-title">Módulos & Pricing (MRR)</div>
        <div><button class="btn btn-primary" onclick="loadModulos()">Refrescar</button></div>
    </div>
    <div class="card" style="margin-bottom:20px;">
        <table class="tbl" id="tbl-modulos">
            <thead><tr><th>Módulo</th><th>Tipo</th><th>Activos</th><th>Precio Normal</th><th>MRR Total</th><th>Acciones</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="page" id="page-spider" style="height: calc(100vh - 120px); padding: 0;">
    <iframe src="/superadmin/spider" style="width: 100%; height: 100%; border: none; border-radius: 8px;"></iframe>
</div>

<!-- MODAL SIMULADOR IMPACTO PRECIO -->
<div id="modal-impacto" class="overlay" style="display:none">
  <div class="login-card" style="max-width:480px;">
    <h3 style="margin-bottom:12px; font-size:16px;">Modificar Precio de Módulo</h3>
    <div style="font-size:13px; color:var(--t2); margin-bottom:20px;" id="impacto-modulo-name">Módulo X</div>
    
    <div class="form-group">
      <label class="form-lbl">Nuevo Precio Mensual (CLP)</label>
      <input type="number" id="input-nuevo-precio" class="form-input" placeholder="Ej: 9990" oninput="simularImpacto()">
    </div>

    <div style="background:var(--s2); border:1px solid var(--b2); border-radius:8px; padding:16px; margin-bottom:20px;">
        <h4 style="font-size:11px; margin-bottom:10px; color:var(--t3); text-transform:uppercase; letter-spacing:1px; font-weight:700;">Simulación de Impacto</h4>
        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
            <span style="font-size:13px; color:var(--t2);">MRR Actual (este módulo):</span>
            <span style="font-size:13px; font-weight:600;" id="impacto-mrr-actual">$0</span>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
            <span style="font-size:13px; color:var(--t2);">MRR Proyectado:</span>
            <span style="font-size:13px; font-weight:600; color:var(--info);" id="impacto-mrr-nuevo">$0</span>
        </div>
        <hr style="border-color:var(--b2); margin:12px 0;">
        <div style="display:flex; justify-content:space-between;">
            <span style="font-size:13px; font-weight:600;">Diferencia:</span>
            <span style="font-size:14px; font-weight:700; color:var(--ok);" id="impacto-diff">+$0</span>
        </div>
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end;">
      <button class="btn" style="background:var(--s2); border:1px solid var(--b2); color:white;" onclick="cerrarModalImpacto()">Cancelar</button>
      <button class="btn btn-primary" id="btn-save-precio" onclick="guardarNuevoPrecio()">Guardar Nuevo Precio</button>
    </div>
  </div>
</div>
@endsection

@section('extra_js')
<script>
let token = localStorage.getItem('sa_token');

async function api(path, method='GET', body=null) {
    const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;
    try {
        const url = path.startsWith('/api') ? path : `/api/superadmin${path}`;
        const res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : null });
        if (res.status === 401) { showLogin(); return null; }
        return await res.json();
    } catch(e) { console.error(e); return null; }
}

function showLogin() { document.getElementById('login-overlay').style.display = 'flex'; }

async function doLogin() {
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const res = await api('/login', 'POST', { email, password });
    if (res && res.token) {
        token = res.token;
        localStorage.setItem('sa_token', token);
        document.getElementById('login-overlay').style.display = 'none';
        init();
    } else { alert('Error de login'); }
}

function logout() { localStorage.removeItem('sa_token'); location.replace('/central/login'); }

function goTo(page) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById(`page-${page}`).classList.add('active');
    if (page === 'tenants') loadTenants();
    if (page === 'modulos') loadModulos();
}

async function loadTenants() {
    const data = await api('/tenants');
    if (!data) return;
    const tbl = document.getElementById('tbl-tenants');
    const items = data.data || data;
    tbl.innerHTML = `<thead><tr><th>Nombre</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>${items.map(t => `<tr>
            <td>${t.nombre || t.id}</td>
            <td><span class="badge" style="background:var(--s2); padding:2px 8px; border-radius:4px; font-size:11px;">${t.estado}</span></td>
            <td><button class="btn" style="background:var(--s2); border:1px solid var(--b2); color:white; padding:4px 8px; font-size:11px;" onclick="impersonar('${t.id}')">Impersonar</button></td>
        </tr>`).join('')}</tbody>`;
}

async function impersonar(id) {
    const res = await api(`/tenants/${id}/impersonar`, 'POST');
    if (res && res.token) {
        window.open(`${res.url}/auth/callback?token=${res.token}`, '_blank');
    }
}

let currentEditModule = null;

async function loadModulos() {
    const data = await api('/api/central/plan/modulos');
    if (!data) return;
    
    const tbl = document.querySelector('#tbl-modulos tbody');
    const formatClp = (num) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(num);

    tbl.innerHTML = data.map(mod => `
        <tr>
            <td style="font-weight:600;">${mod.nombre} <div style="font-size:10px; color:var(--t2); font-weight:normal; font-family:var(--mono);">[${mod.modulo_id}]</div></td>
            <td>${mod.es_base ? '<span class="badge" style="background:var(--s2); padding:2px 8px; border-radius:4px; font-size:11px;">Base</span>' : 'Adicional'}</td>
            <td>${mod.activos_count} Tenants</td>
            <td style="font-family:var(--mono);">${formatClp(mod.precio_mensual)}/mes</td>
            <td style="font-family:var(--mono); color:var(--ac); font-weight:600;">${formatClp(mod.mrr_total)}</td>
            <td>
                <button class="btn" style="background:var(--s2); border:1px solid var(--b2); padding:6px 10px; color:var(--tx); font-size:11px;" onclick="openEditPrecio('${mod.modulo_id}', '${mod.nombre}', ${mod.precio_mensual}, ${mod.mrr_total}, ${mod.activos_count})">Editar Precio</button>
            </td>
        </tr>
    `).join('');
}

function openEditPrecio(id, nombre, precioBase, mrrActual, count) {
    currentEditModule = { id, nombre, precioBase, mrrActual, count };
    document.getElementById('impacto-modulo-name').innerText = `ID: ${id} - ${nombre}`;
    document.getElementById('input-nuevo-precio').value = precioBase;
    simularImpacto();
    document.getElementById('modal-impacto').style.display = 'flex';
}

function cerrarModalImpacto() {
    document.getElementById('modal-impacto').style.display = 'none';
    currentEditModule = null;
}

async function simularImpacto() {
    if (!currentEditModule) return;
    const formatClp = (num) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(num);

    const inputVal = parseInt(document.getElementById('input-nuevo-precio').value || 0);
    
    const mrrActual = currentEditModule.mrrActual;
    const mrrNuevo = inputVal * currentEditModule.count;
    const diff = mrrNuevo - mrrActual;

    document.getElementById('impacto-mrr-actual').innerText = formatClp(mrrActual);
    document.getElementById('impacto-mrr-nuevo').innerText = formatClp(mrrNuevo);
    
    const diffEl = document.getElementById('impacto-diff');
    diffEl.innerText = diff > 0 ? `+${formatClp(diff)}` : formatClp(diff);
    diffEl.style.color = diff > 0 ? 'var(--ok)' : (diff < 0 ? 'var(--err)' : 'var(--t2)');
}

async function guardarNuevoPrecio() {
    if (!currentEditModule) return;
    const precio = parseInt(document.getElementById('input-nuevo-precio').value);
    
    document.getElementById('btn-save-precio').innerText = 'Guardando...';
    document.getElementById('btn-save-precio').disabled = true;

    const res = await api(`/api/central/plan/modulos/${currentEditModule.id}`, 'PUT', { precio_mensual: precio });
    
    document.getElementById('btn-save-precio').innerText = 'Guardar Nuevo Precio';
    document.getElementById('btn-save-precio').disabled = false;

    if (res && res.message) {
        alert(res.message);
        cerrarModalImpacto();
        loadModulos();
        init();
    } else {
        alert("Error al actualizar precio");
    }
}

async function init() {
    if (!token) { showLogin(); return; }
    const data = await api('/dashboard');
    if (data) {
        document.getElementById('dashboard-kpis').innerHTML = `
            <div class="kpi"><div class="kpi-lbl">Tenants</div><div class="kpi-val">${data.tenants_activos}</div></div>
            <div class="kpi"><div class="kpi-lbl">MRR Global Plan Base</div><div class="kpi-val">$${(data.mrr).toLocaleString()}</div></div>
            <div class="kpi"><div class="kpi-lbl">Churn</div><div class="kpi-val">${data.churn_rate}%</div></div>
        `;
    }
}

window.onload = init;
</script>
@endsection
