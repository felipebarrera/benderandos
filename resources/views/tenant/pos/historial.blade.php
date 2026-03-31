@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Historial de Ventas</div>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <div class="search-wrap" style="min-width:220px;">
                <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="searchVenta" placeholder="Folio, {{ strtolower($rubroConfig->label_clientes) }}..." oninput="filtrarVentas()">
            </div>
            <select id="filtroEstado" onchange="filtrarVentas()" class="mono" style="min-width:130px;">
                <option value="">Todos los estados</option>
                <option value="confirmada">Confirmada</option>
                <option value="pendiente">Pendiente</option>
                <option value="en_caja">En caja</option>
                <option value="anulada">Anulada</option>
            </select>
        </div>
    </div>

    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>{{ $rubroConfig->label_clientes }}</th>
                        <th>{{ $rubroConfig->label_operarios }}</th>
                        <th>Tipo Pago</th>
                        <th class="num">Items</th>
                        <th class="num">Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ventasTabla">
                    <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--t2);">
                        <span class="spinner" style="display:inline-block;"></span>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DETALLE VENTA -->
<div class="modal-overlay" id="modalVenta">
    <div class="modal" style="max-width:520px;">
        <div class="modal-head">
            <span class="modal-title" id="modalVentaTitle">Venta</span>
            <button class="modal-close" data-close-modal="modalVenta">✕</button>
        </div>
        <div class="modal-body" id="modalVentaBody">
            <div class="empty-state"><div class="spinner"></div></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-danger btn-sm" id="btnAnular">Anular Venta</button>
            <button class="btn btn-secondary" data-close-modal="modalVenta">Cerrar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let ventas = [];
let ventaDetailId = null;

async function cargarVentas() {
    try {
        const data = await api('GET', '/api/ventas');
        ventas = data.data ?? data;
        renderVentas();
    } catch(e) { toast('Error cargando ventas', 'err'); }
}

function filtrarVentas() {
    renderVentas();
}

function renderVentas() {
    const term = document.getElementById('searchVenta').value.toLowerCase();
    const estado = document.getElementById('filtroEstado').value;
    const lista = ventas.filter(v => {
        const matchTerm = !term || String(v.id).includes(term) || (v.cliente?.nombre ?? '').toLowerCase().includes(term);
        const matchEstado = !estado || v.estado === estado;
        return matchTerm && matchEstado;
    });

    const estadoBadge = {
        confirmada: 'badge-green', pendiente: 'badge-yellow',
        en_caja: 'badge-blue', anulada: 'badge-red',
        remota_pendiente: 'badge-purple'
    };
    const tbody = document.getElementById('ventasTabla');
    if (!lista.length) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--t2);">Sin ventas</td></tr>`;
        return;
    }
    tbody.innerHTML = lista.map(v => `
        <tr>
            <td class="mono" style="font-size:12px;">#${String(v.id).padStart(4,'0')}</td>
            <td style="font-size:12px;color:var(--t2);">${new Date(v.created_at).toLocaleString('es-CL')}</td>
            <td>${v.cliente?.nombre ?? '<span style="color:var(--t2)">—</span>'}</td>
            <td style="color:var(--t2);font-size:12px;">${v.usuario?.nombre ?? v.cajero?.nombre ?? '—'}</td>
            <td style="text-transform:capitalize;">${v.tipo_pago?.nombre ?? (typeof v.tipo_pago === 'string' ? v.tipo_pago : '—')}</td>
            <td class="num">${v.items_count ?? v.items?.length ?? '—'}</td>
            <td class="num mono">${fmt(v.total)}</td>
            <td><span class="badge ${estadoBadge[v.estado] ?? 'badge-gray'}">${v.estado?.replace('_',' ') ?? '—'}</span></td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="verVenta(${v.id})">Detalle</button>
            </td>
        </tr>`).join('');
}

async function verVenta(id) {
    ventaDetailId = id;
    openModal('modalVenta');
    document.getElementById('modalVentaTitle').textContent = `Venta #${String(id).padStart(4,'0')}`;
    document.getElementById('modalVentaBody').innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
    try {
        const v = await api('GET', `/api/ventas/${id}`);
        document.getElementById('btnAnular').style.display = v.estado === 'confirmada' ? '' : 'none';
        document.getElementById('btnAnular').onclick = () => anularVenta(id);
        document.getElementById('modalVentaBody').innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div>
                    <div class="label">{{ $rubroConfig->label_clientes }}</div>
                    <div style="margin-top:4px;">${v.cliente?.nombre ?? '—'}</div>
                </div>
                <div>
                    <div class="label">Tipo de Pago</div>
                    <div style="margin-top:4px;text-transform:capitalize;">${v.tipo_pago?.nombre ?? (typeof v.tipo_pago === 'string' ? v.tipo_pago : '—')}</div>
                </div>
            </div>
            <div class="label" style="margin-bottom:8px;">Items</div>
            <div class="table-wrap" style="margin-bottom:16px;">
                <table>
                    <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num">P.Unit</th><th class="num">Subtotal</th></tr></thead>
                    <tbody>
                        ${(v.items ?? []).map(i => `
                        <tr>
                            <td>${i.producto?.nombre ?? '—'}</td>
                            <td class="num mono">${parseFloat(i.cantidad).toLocaleString('es-CL')}</td>
                            <td class="num mono">${fmt(i.precio_unitario)}</td>
                            <td class="num mono">${fmt(i.total_item || (i.cantidad * i.precio_unitario))}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
            <div class="total-row grand">
                <span>TOTAL</span>
                <span>${fmt(v.total)}</span>
            </div>
            ${v.notas ? `<div class="card card-sm" style="margin-top:12px;border-color:var(--b2);color:var(--t2);font-size:12px;">📝 ${v.notas}</div>` : ''}`;
    } catch(e) {
        document.getElementById('modalVentaBody').innerHTML = `<p style="color:var(--err);">Error cargando venta</p>`;
    }
}

async function anularVenta(id) {
    if (!confirm('¿Anular esta venta? Se restituirá el stock.')) return;
    try {
        await api('POST', `/api/ventas/${id}/anular`);
        toast('Venta anulada', 'ok');
        closeModal('modalVenta');
        cargarVentas();
    } catch(e) {
        toast(e.message || 'Error', 'err');
    }
}

cargarVentas();
</script>
@endpush
@endsection
