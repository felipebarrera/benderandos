@extends('tenant.layout')

@section('content')
<div class="page active">
  <div class="page-header">
    <div>
      <div class="page-title">Clientes</div>
      <div class="page-sub" id="clientCount">Cargando clientes...</div>
    </div>
    @can('gestionar-clientes')
    <button class="btn btn-primary" onclick="abrirModalCliente()">+ Nuevo cliente</button>
    @endcan
  </div>
  
  <div class="filter-bar">
    <div class="search-wrap" style="flex:1;max-width:300px">
      <span class="search-icon">🔍</span>
      <input type="text" id="cSearch" placeholder="Buscar por nombre, RUT..." oninput="filtrarClientes()">
    </div>
  </div>
  
  <div class="card p-0">
    <div class="table-wrap">
      <table id="clientesTbl">
        <thead>
          <tr>
            <th>Nombre y RUT</th>
            <th>Giro</th>
            <th>Contacto</th>
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

<!-- Modal Cliente -->
<div class="modal-overlay" id="mCliente">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title" id="mClientTtl">Nuevo Cliente</span>
      <button class="modal-close" data-close-modal="mCliente">✕</button>
    </div>
    <div class="modal-body">
      <form id="clientForm" onsubmit="guardarCliente(event)">
        <input type="hidden" id="mcId">
        
        <div class="field">
          <label class="label">RUT *</label>
          <input type="text" id="mcRut" required placeholder="Ej. 12.345.678-9">
        </div>

        <div class="field">
          <label class="label">Nombre *</label>
          <input type="text" id="mcNombre" required placeholder="Nombre completo o Empresa">
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="field">
          <div>
            <label class="label">Giro</label>
            <input type="text" id="mcGiro" placeholder="Opcional">
          </div>
          <div>
            <label class="label">Teléfono</label>
            <input type="text" id="mcTel" placeholder="+56 9...">
          </div>
        </div>
        
        <div class="field">
          <label class="label">Email</label>
          <input type="email" id="mcEmail" placeholder="contacto@empresa.cl">
        </div>

        <div class="field">
          <label class="label">Dirección</label>
          <input type="text" id="mcDir" placeholder="Calle, Número, Ciudad">
        </div>
        
        <div class="modal-foot">
          <button type="button" class="btn btn-secondary" onclick="closeModal('mCliente')">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarClient">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
let clientesList = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarClientes();
});

async function cargarClientes() {
    try {
        const res = await api('GET', '/api/clientes?per_page=100');
        clientesList = res.data || res;
        renderClientes(clientesList);
    } catch(e) {
        toast('Error cargando clientes', 'err');
    }
}

function filtrarClientes() {
    const q = document.getElementById('cSearch').value.toLowerCase();
    
    let docs = clientesList.filter(c => {
        return !q || c.nombre.toLowerCase().includes(q) || (c.rut||'').toLowerCase().includes(q);
    });
    renderClientes(docs);
}

function renderClientes(lista) {
    document.getElementById('clientCount').textContent = `${lista.length} clientes`;
    const tbody = document.querySelector('#clientesTbl tbody');
    if(!lista.length) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--t2)">No hay clientes</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(c => {
        return `
        <tr>
            <td>
                <div style="font-weight:600;font-size:13px">${c.nombre}</div>
                <div class="mono" style="font-size:10px;color:var(--t2)">${c.rut || 'Sin RUT'}</div>
            </td>
            <td><span class="badge badge-gray">${c.giro || '—'}</span></td>
            <td>
                <div style="font-size:12px">${c.telefono || '—'}</div>
                <div style="font-size:11px;color:var(--t2)">${c.email || '—'}</div>
            </td>
            <td>
                ${['admin','super_admin','cajero'].includes(window.AppConfig.rol) ? 
                `<button class="btn btn-secondary btn-sm" onclick='editarCliente(${JSON.stringify(c).replace(/'/g, "&apos;")})'>✏️</button>` : '—'}
            </td>
        </tr>
        `;
    }).join('');
}

function abrirModalCliente() {
    document.getElementById('clientForm').reset();
    document.getElementById('mcId').value = '';
    document.getElementById('mClientTtl').textContent = 'Nuevo Cliente';
    openModal('mCliente');
}

function editarCliente(c) {
    document.getElementById('mClientTtl').textContent = 'Editar Cliente';
    document.getElementById('mcId').value = c.id;
    document.getElementById('mcRut').value = c.rut || '';
    document.getElementById('mcNombre').value = c.nombre;
    document.getElementById('mcGiro').value = c.giro || '';
    document.getElementById('mcTel').value = c.telefono || '';
    document.getElementById('mcEmail').value = c.email || '';
    document.getElementById('mcDir').value = c.direccion || '';
    openModal('mCliente');
}

async function guardarCliente(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarClient');
    btn.disabled = true;
    
    const id = document.getElementById('mcId').value;
    const payload = {
        rut: document.getElementById('mcRut').value || null,
        nombre: document.getElementById('mcNombre').value,
        giro: document.getElementById('mcGiro').value || null,
        telefono: document.getElementById('mcTel').value || null,
        email: document.getElementById('mcEmail').value || null,
        direccion: document.getElementById('mcDir').value || null
    };
    
    try {
        const url = id ? `/api/clientes/${id}` : '/api/clientes';
        const method = id ? 'PUT' : 'POST';
        await api(method, url, payload);
        toast('✓ Cliente guardado correctamente');
        closeModal('mCliente');
        cargarClientes();
    } catch(err) {
        toast(err.message || 'Error al guardar', 'err');
    } finally {
        btn.disabled = false;
    }
}
</script>
@endpush
@endsection
