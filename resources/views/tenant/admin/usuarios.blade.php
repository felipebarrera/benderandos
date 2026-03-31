@extends('tenant.layout')

@section('content')
<div class="page active" id="pg-usuarios">
  <div class="page-header">
    <div>
      <div class="page-title">Usuarios</div>
      <div class="page-sub">Accesos al sistema ERP</div>
    </div>
    @can('gestionar-usuarios')
    <button class="btn btn-primary" onclick="abrirModalUsuario()">+ Nuevo usuario</button>
    @endcan
  </div>
  
  <div class="card p-0">
    <div class="table-wrap">
      <table id="usuariosTbl">
        <thead>
          <tr>
            <th>Nombre y Login</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
           <!-- JS renders this -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Usuario -->
<div class="modal-overlay" id="mUsuario">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title" id="mUsTtl">Nuevo Usuario</span>
      <button class="modal-close" data-close-modal="mUsuario">✕</button>
    </div>
    <div class="modal-body">
      <form id="usForm" onsubmit="guardarUsuario(event)">
        <input type="hidden" id="muId">
        
        <div class="field">
          <label class="label">Nombre completo *</label>
          <input type="text" id="muNombre" required>
        </div>

        <div class="field">
          <label class="label">Login / Email *</label>
          <input type="text" id="muLogin" required>
        </div>

        <div class="field">
          <label class="label" id="muPassLabel">Contraseña *</label>
          <input type="password" id="muPass" required>
        </div>
        
        <div class="field">
          <label class="label">Rol de sistema *</label>
          <select id="muRol" required>
             <option value="admin">Administrador (Total)</option>
             <option value="cajero">Cajero (POS y Ventas)</option>
             <option value="bodega">Bodega (Inventario y Compras)</option>
             <option value="operario">Operario (Visualización)</option>
          </select>
        </div>

        <div class="field" id="fieldRecurso" style="background:rgba(0,229,160,0.05); padding:10px; border-radius:8px; border:1px solid rgba(0,229,160,0.15); margin-top:10px;">
          <label class="label">Vincular a Agenda (M08)</label>
          <select id="muRecurso">
            <option value="">-- No vincular --</option>
          </select>
          <div style="font-size:10px; color:var(--accent); margin-top:4px;">Permite al usuario ver "Mi Agenda" si es profesional.</div>
        </div>
        
        <div class="modal-foot">
          <button type="button" class="btn btn-secondary" onclick="closeModal('mUsuario')">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarUs">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
let usersList = [];
let recursosList = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();
    cargarRecursos();
});

async function cargarRecursos() {
    try {
        const res = await api('GET', '/api/agenda/recursos');
        recursosList = res || [];
        const sel = document.getElementById('muRecurso');
        sel.innerHTML = '<option value="">-- No vincular --</option>' + 
            recursosList.map(r => `<option value="${r.id}">${r.nombre} (${r.especialidad || 'General'})</option>`).join('');
    } catch(e) { console.error('Error cargando recursos', e); }
}

async function cargarUsuarios() {
    try {
        const res = await api('GET', '/api/usuarios?per_page=100');
        usersList = res.data || res;
        renderUsuarios(usersList);
    } catch(e) {
        toast('Error cargando usuarios', 'err');
    }
}

function renderUsuarios(lista) {
    const tbody = document.querySelector('#usuariosTbl tbody');
    if(!lista.length) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--t2)">No hay usuarios</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(u => {
        const bdgColor = u.rol === 'admin' ? 'badge-purple' : u.rol === 'cajero' ? 'badge-blue' : 'badge-gray';
        const stColor = u.activo ? 'badge-green' : 'badge-red';
        const stText = u.activo ? 'Activo' : 'Desactivado';
        
        const linkedRecurso = u.agenda_recurso ? `<div style="font-size:10px;color:var(--accent)">📅 ${u.agenda_recurso.nombre}</div>` : '';
        
        return `
        <tr>
            <td>
                <div style="font-weight:600;font-size:13px">${u.nombre}</div>
                <div class="mono" style="font-size:10px;color:var(--t2)">${u.email}</div>
                ${linkedRecurso}
            </td>
            <td><span class="badge ${bdgColor}" style="text-transform:uppercase">${u.rol}</span></td>
            <td><span class="badge ${stColor}">${stText}</span></td>
            <td>
                ${['admin','super_admin'].includes(window.AppConfig.rol) ? 
                `<button class="btn btn-secondary btn-sm" onclick='editarUsuario(${JSON.stringify(u).replace(/'/g, "&apos;")})'>✏️</button>
                <button class="btn btn-danger btn-sm" onclick='toggleEstado(${u.id}, ${!u.activo})'>${u.activo ? 'Desactivar' : 'Activar'}</button>` : '—'}
            </td>
        </tr>
        `;
    }).join('');
}

function abrirModalUsuario() {
    document.getElementById('usForm').reset();
    document.getElementById('muId').value = '';
    document.getElementById('muPass').required = true;
    document.getElementById('muPassLabel').textContent = 'Contraseña *';
    document.getElementById('mUsTtl').textContent = 'Nuevo Usuario';
    openModal('mUsuario');
}

function editarUsuario(u) {
    document.getElementById('mUsTtl').textContent = 'Editar Usuario';
    document.getElementById('muId').value = u.id;
    document.getElementById('muNombre').value = u.nombre;
    document.getElementById('muLogin').value = u.email;
    document.getElementById('muPass').value = '';
    document.getElementById('muPass').required = false;
    document.getElementById('muPassLabel').textContent = 'Contraseña (dejar en blanco para mantener)';
    document.getElementById('muRol').value = u.rol;
    document.getElementById('muRecurso').value = u.agenda_recurso ? u.agenda_recurso.id : '';
    openModal('mUsuario');
}

async function guardarUsuario(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarUs');
    btn.disabled = true;
    
    const id = document.getElementById('muId').value;
    const pwd = document.getElementById('muPass').value;
    
    const payload = {
        nombre: document.getElementById('muNombre').value,
        email: document.getElementById('muLogin').value,
        rol: document.getElementById('muRol').value,
        recurso_id: document.getElementById('muRecurso').value || null
    };
    
    if (pwd) payload.password = pwd;
    
    try {
        const url = id ? `/api/usuarios/${id}` : '/api/usuarios';
        const method = id ? 'PUT' : 'POST';
        await api(method, url, payload);
        toast('✓ Usuario guardado');
        closeModal('mUsuario');
        cargarUsuarios();
    } catch(err) {
        toast(err.message || 'Error al guardar', 'err');
    } finally {
        btn.disabled = false;
    }
}

async function toggleEstado(id, activo) {
    try {
        // Asumiendo que update soporta update directo de activo via JSON
        await api('PUT', `/api/usuarios/${id}`, { activo: activo });
        toast('✓ Estado actualizado');
        cargarUsuarios();
    } catch(e) {
        toast('No se pudo cambiar estado', 'err');
    }
}
</script>
@endpush
@endsection
