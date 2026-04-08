@extends('tenant.layout')

@section('content')
<div class="page active">
  <div class="page-header">
    <div>
      <div class="page-title">Productos</div>
      <div class="page-sub" id="prodCount">Cargando catálogo...</div>
    </div>
    @can('gestionar-productos')
    <button class="btn btn-primary" onclick="abrirModalProducto()">+ Nuevo producto</button>
    @endcan
  </div>
  
  <div class="filter-bar">
    <div class="search-wrap" style="flex:1;max-width:300px">
      <span class="search-icon">🔍</span>
      <input type="text" id="pSearch" placeholder="Buscar producto..." oninput="filtrarProds()">
    </div>
    <select class="btn btn-secondary" id="pFamilia" onchange="filtrarProds()">
      <option value="">Todas las familias</option>
    </select>
  </div>
  
  <div class="card p-0">
    <div class="table-wrap">
      <table id="prodTbl">
        <thead>
          <tr>
            <th>Nombre y SKU</th>
            <th>Familia</th>
            <th class="num">Precio</th>
            <th class="num">Stock</th>
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

<!-- Modal Producto Exclusivo para Blade -->
<div class="modal-overlay" id="mProd">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title" id="mProdTtl">Nuevo Producto</span>
      <button class="modal-close" data-close-modal="mProd">✕</button>
    </div>
    <div class="modal-body">
      <form id="prodForm" onsubmit="guardarProducto(event)">
        <input type="hidden" id="mpId">
        
        <div class="field">
          <label class="label">Nombre *</label>
          <input type="text" id="mpNombre" required placeholder="Ej. Coca-Cola 3L">
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="field">
          <div>
            <label class="label">Código/SKU</label>
            <input type="text" id="mpSku" class="mono">
          </div>
          <div>
            <label class="label">Familia</label>
            <input type="text" id="mpFamilia" placeholder="Bebidas">
          </div>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px" class="field">
          <div>
            <label class="label">Costo Neto</label>
            <input type="number" id="mpCosto" class="mono" min="0" oninput="calcMargen()">
          </div>
          <div>
            <label class="label">Precio Venta</label>
            <input type="number" id="mpPrecio" class="mono" min="0" required oninput="calcMargen()">
          </div>
          <div>
            <label class="label">Stock Actual</label>
            <input type="number" id="mpStock" class="mono">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="field">
          <div>
            <label class="label">Stock Mínimo</label>
            <input type="number" id="mpStockMin" class="mono">
          </div>
          <div>
            <label class="label">Margen Bruto</label>
            <div id="mpMargen" style="padding:10px 0;font-family:var(--mono);color:var(--accent);font-weight:700">-%</div>
          </div>
        </div>
        
        <div class="modal-foot">
          <button type="button" class="btn btn-secondary" onclick="closeModal('mProd')">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarProd">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
let catalogo = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarProductos();
});

async function cargarProductos() {
    try {
        const res = await api('GET', '/api/productos?per_page=100');
        catalogo = res.data || res;
        
        // Poblar Familias unicas
        const fs = [...new Set(catalogo.map(p => p.familia).filter(f => f))];
        const fsel = document.getElementById('pFamilia');
        fs.forEach(f => fsel.insertAdjacentHTML('beforeend', `<option value="${f}">${f}</option>`));
        
        renderProds(catalogo);
    } catch(e) {
        toast('Error cargando catálogo', 'err');
    }
}

function filtrarProds() {
    const q = document.getElementById('pSearch').value.toLowerCase();
    const f = document.getElementById('pFamilia').value;
    
    let docs = catalogo.filter(p => {
        const mQ = !q || p.nombre.toLowerCase().includes(q) || (p.codigo||'').toLowerCase().includes(q);
        const mF = !f || p.familia === f;
        return mQ && mF;
    });
    renderProds(docs);
}

function renderProds(lista) {
    document.getElementById('prodCount').textContent = `${lista.length} ítems`;
    const tbody = document.querySelector('#prodTbl tbody');
    if(!lista.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--t2)">No hay productos</td></tr>';
        return;
    }
    
    tbody.innerHTML = lista.map(p => {
        const qty = parseFloat(p.cantidad) || 0;
        const qtyMin = parseFloat(p.cantidad_minima) || 0;
        const stockCls = qty <= qtyMin ? "text-err" : "";
        return `
        <tr>
            <td>
                <div style="font-weight:600;font-size:13px">${p.nombre}</div>
                <div class="mono" style="font-size:10px;color:var(--t2)">${p.codigo || '—'}</div>
            </td>
            <td><span class="badge badge-gray">${p.familia || 'General'}</span></td>
            <td class="num" style="color:var(--accent);font-weight:700">${fmt(p.valor_venta)}</td>
            <td class="num ${stockCls}">${p.tipo_producto === 'servicio' ? '—' : qty}</td>
            <td>
                ${['admin','super_admin','bodega'].includes(window.AppConfig.rol) ? 
                `<button class="btn btn-secondary btn-sm" onclick='editarProd(${JSON.stringify(p).replace(/'/g, "&apos;")})'>✏️</button>` : '—'}
            </td>
        </tr>
        `;
    }).join('');
}

function calcMargen() {
    const pt = parseFloat(document.getElementById('mpCosto').value)||0;
    const pv = parseFloat(document.getElementById('mpPrecio').value)||0;
    if(pv > 0) {
        const mg = ((pv-pt)/pv)*100;
        document.getElementById('mpMargen').textContent = Math.round(mg) + '%';
    } else {
        document.getElementById('mpMargen').textContent = '-%';
    }
}

function abrirModalProducto() {
    document.getElementById('prodForm').reset();
    document.getElementById('mpId').value = '';
    document.getElementById('mProdTtl').textContent = 'Nuevo Producto';
    document.getElementById('mpMargen').textContent = '-%';
    openModal('mProd');
}

function editarProd(p) {
    document.getElementById('mProdTtl').textContent = 'Editar Producto';
    document.getElementById('mpId').value = p.id;
    document.getElementById('mpNombre').value = p.nombre;
    document.getElementById('mpSku').value = p.codigo || '';
    document.getElementById('mpFamilia').value = p.familia || '';
    document.getElementById('mpCosto').value = p.costo || '';
    document.getElementById('mpPrecio').value = p.valor_venta || '';
    document.getElementById('mpStock').value = p.cantidad || 0;
    document.getElementById('mpStockMin').value = p.cantidad_minima || 0;
    calcMargen();
    openModal('mProd');
}

async function guardarProducto(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarProd');
    btn.disabled = true;
    
    const id = document.getElementById('mpId').value;
    const payload = {
        nombre: document.getElementById('mpNombre').value,
        codigo: document.getElementById('mpSku').value || null,
        familia: document.getElementById('mpFamilia').value || null,
        costo: parseFloat(document.getElementById('mpCosto').value) || 0,
        valor_venta: parseFloat(document.getElementById('mpPrecio').value) || 0,
        cantidad: parseInt(document.getElementById('mpStock').value) || 0,
        cantidad_minima: parseInt(document.getElementById('mpStockMin').value) || 0,
        tipo_producto: 'stock_fisico'
    };
    
    try {
        const url = id ? `/api/productos/${id}` : '/api/productos';
        const method = id ? 'PUT' : 'POST';
        await api(method, url, payload);
        toast('✓ Producto guardado correctamente');
        closeModal('mProd');
        cargarProductos();
    } catch(err) {
        toast(err.message || 'Error al guardar', 'err');
    } finally {
        btn.disabled = false;
    }
}
</script>
@endpush
@endsection
