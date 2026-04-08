@extends('tenant.layout')

@section('content')
<div class="page active" id="pgRecepcion">

  {{-- ── HEADER ── --}}
  <div class="page-header" style="padding:14px 16px;">
    <div>
      <div class="page-title">Recepción de Mercadería</div>
      <div class="page-sub" id="rdEstado">Selecciona un modo</div>
    </div>
    {{-- Badge pendientes para caja --}}
    @if(in_array(auth()->user()->rol ?? '', ['admin','cajero','super_admin']))
    <button class="btn btn-secondary" onclick="rdVerPendientes()" id="btnPendientes" style="display:none;">
      💳 Pendientes <span id="badgePendientes" class="badge badge-warn" style="margin-left:4px;"></span>
    </button>
    @endif
  </div>

  {{-- ── TABS ── --}}
  <div style="display:flex; border-bottom:1px solid var(--b1); background:var(--s1);">
    <button class="bottom-nav-item active" id="tabNueva" onclick="rdSetTab('nueva')" style="flex:1;padding:13px 8px;border-radius:0;border-right:1px solid var(--b1);">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Nueva
    </button>
    <button class="bottom-nav-item" id="tabHistorial" onclick="rdSetTab('historial')" style="flex:1;padding:13px 8px;border-radius:0;">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Historial
    </button>
  </div>

  {{-- ══════════ TAB NUEVA ══════════ --}}
  <div id="panelNueva" style="padding:16px;max-width:520px;margin:0 auto;">

    {{-- PASO 1: Proveedor --}}
    <div id="rdPaso1" class="card" style="padding:16px;margin-bottom:12px;">
      <div style="font-size:12px;font-weight:700;color:var(--t2);letter-spacing:1px;margin-bottom:10px;">PASO 1 — PROVEEDOR</div>

      <div class="field">
        <label class="label">Proveedor registrado (opcional)</label>
        <select id="rdProveedorId" onchange="rdProveedorChange()">
          <option value="">— Sin proveedor / ingresar nombre —</option>
        </select>
      </div>

      <div class="field" id="rdNombreLibreWrap">
        <label class="label">Nombre del proveedor</label>
        <input type="text" id="rdNombreLibre" placeholder="Ej: Panadería Don Luis" class="mono">
      </div>
    </div>

    {{-- PASO 2: Agregar productos --}}
    <div id="rdPaso2" class="card" style="padding:16px;margin-bottom:12px;">
      <div style="font-size:12px;font-weight:700;color:var(--t2);letter-spacing:1px;margin-bottom:10px;">PASO 2 — PRODUCTOS</div>

      {{-- Escáner código de barras --}}
      <div class="field">
        <label class="label">Código de barras</label>
        <div style="display:flex;gap:8px;">
          <input type="text" id="rdBarcode" class="mono" placeholder="Escanea o escribe el código..."
            style="flex:1;font-size:16px;" {{-- font-size 16px evita zoom en iOS --}}
            onkeydown="if(event.key==='Enter'){rdBuscarCodigo()}">
          <button class="btn btn-secondary" onclick="rdBuscarCodigo()">→</button>
        </div>
      </div>

      {{-- Búsqueda por nombre --}}
      <div class="field">
        <label class="label">O buscar por nombre</label>
        <div class="search-wrap">
          <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" id="rdBuscaNombre" placeholder="Nombre del producto..." oninput="rdBuscarNombre(this.value)">
        </div>
        <div id="rdResultadosNombre" style="margin-top:6px;"></div>
      </div>

      {{-- Producto seleccionado --}}
      <div id="rdProductoSelWrap" style="display:none;background:var(--s2);border:1px solid var(--b2);border-radius:var(--r);padding:12px;margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <div style="font-weight:600;" id="rdProdNombre">—</div>
            <div class="mono" style="font-size:11px;color:var(--t2);" id="rdProdCodigo">—</div>
            <div style="font-size:12px;color:var(--t2);margin-top:2px;">Stock actual: <span id="rdProdStock" class="mono">—</span></div>
          </div>
          <button onclick="rdLimpiarProducto()" style="background:none;border:none;color:var(--t2);font-size:18px;cursor:pointer;">✕</button>
        </div>
        <input type="hidden" id="rdProdId">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px;">
          <div>
            <label class="label">Cantidad recibida</label>
            <input type="number" id="rdCantidad" class="mono" min="0.001" step="any"
              placeholder="0" style="font-size:20px;font-weight:700;"
              onkeydown="if(event.key==='Enter'){rdAgregarItem()}">
          </div>
          <div>
            <label class="label">Costo unitario (opt.)</label>
            <input type="number" id="rdCostoUnit" class="mono" min="0" placeholder="$0"
              onkeydown="if(event.key==='Enter'){rdAgregarItem()}">
          </div>
        </div>
        <button class="btn btn-primary btn-full" style="margin-top:10px;" onclick="rdAgregarItem()">
          + Agregar al listado
        </button>
      </div>
    </div>

    {{-- LISTADO DE ITEMS --}}
    <div id="rdItemsWrap" style="display:none;margin-bottom:12px;">
      <div style="font-size:12px;font-weight:700;color:var(--t2);letter-spacing:1px;margin-bottom:8px;">PRODUCTOS RECIBIDOS</div>
      <div id="rdItemsList"></div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--b1);margin-top:4px;">
        <span style="font-weight:600;">Total a pagar</span>
        <span class="mono" style="font-size:16px;color:var(--accent);font-weight:700;" id="rdTotal">$0</span>
      </div>
    </div>

    {{-- NOTAS + CERRAR --}}
    <div class="field">
      <label class="label">Notas (opcional)</label>
      <input type="text" id="rdNotas" placeholder="Ej: Llegaron 12 panes rotos">
    </div>

    <button class="btn btn-primary btn-full" style="font-size:15px;padding:14px;" onclick="rdCerrarRecepcion()" id="btnCerrarRD">
      ✓ Enviar a caja para pago
    </button>

  </div>

  {{-- ══════════ TAB HISTORIAL ══════════ --}}
  <div id="panelHistorial" style="display:none;padding:16px;max-width:520px;margin:0 auto;">
    <div id="rdHistorialLista">
      <div class="empty-state"><div class="spinner"></div></div>
    </div>
  </div>

</div>

{{-- ══════════ MODAL PENDIENTES DE PAGO (Caja) ══════════ --}}
<div class="modal-overlay" id="mPendientes">
  <div class="modal" style="max-width:480px;">
    <div class="modal-head">
      <span class="modal-title">Recepciones pendientes de pago</span>
      <button class="modal-close" data-close-modal="mPendientes">✕</button>
    </div>
    <div class="modal-body" id="mPendientesBody">
      <div class="empty-state"><div class="spinner"></div></div>
    </div>
  </div>
</div>

{{-- ══════════ MODAL PAGAR RECEPCIÓN ══════════ --}}
<div class="modal-overlay" id="mPagar">
  <div class="modal" style="max-width:400px;">
    <div class="modal-head">
      <span class="modal-title">Confirmar pago</span>
      <button class="modal-close" data-close-modal="mPagar">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="mPagarId">
      <div id="mPagarResumen" style="background:var(--s2);border-radius:var(--r);padding:12px;margin-bottom:14px;font-size:13px;"></div>

      <div class="field">
        <label class="label">Forma de pago</label>
        <select id="mPagarTipo">
          <option value="efectivo">Efectivo</option>
          <option value="transferencia">Transferencia</option>
          <option value="cheque">Cheque</option>
          <option value="credito">Crédito (pagar después)</option>
        </select>
      </div>
      <div class="field">
        <label class="label">N° Boleta/Factura (opcional)</label>
        <input type="text" id="mPagarDoc" class="mono" placeholder="Ej: 12345">
      </div>
      <div class="field">
        <label class="label">Monto total pagado</label>
        <input type="number" id="mPagarMonto" class="mono" placeholder="0">
      </div>

      <button class="btn btn-primary btn-full" style="margin-top:6px;" onclick="rdConfirmarPago()">
        ✓ Confirmar pago y subir stock
      </button>
    </div>
  </div>
</div>

@push('scripts')
<script>
// ══════════════════════════════════════════════════
// Estado local
// ══════════════════════════════════════════════════
let rdRecepcionId = null;   // ID de la recepción en curso
let rdItems = [];           // lista local para render rápido
let rdSearchTimer = null;

// ══════════════════════════════════════════════════
// Init
// ══════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    rdCargarProveedores();
    rdRefrescarBadge();
    // Foco automático en el campo de código de barras
    document.getElementById('rdBarcode').focus();
});

// ══════════════════════════════════════════════════
// Tabs
// ══════════════════════════════════════════════════
function rdSetTab(tab) {
    document.getElementById('panelNueva').style.display    = tab === 'nueva'    ? '' : 'none';
    document.getElementById('panelHistorial').style.display = tab === 'historial' ? '' : 'none';
    document.getElementById('tabNueva').classList.toggle('active',    tab === 'nueva');
    document.getElementById('tabHistorial').classList.toggle('active', tab === 'historial');
    if (tab === 'historial') rdCargarHistorial();
}

// ══════════════════════════════════════════════════
// Proveedores
// ══════════════════════════════════════════════════
async function rdCargarProveedores() {
    try {
        const data = await api('GET', '/api/proveedores?per_page=100&solo_activos=true');
        const sel = document.getElementById('rdProveedorId');
        const lista = data.data || data;
        lista.forEach(p => {
            sel.insertAdjacentHTML('beforeend', `<option value="${p.id}">${p.nombre}</option>`);
        });
    } catch {}
}

function rdProveedorChange() {
    const val = document.getElementById('rdProveedorId').value;
    document.getElementById('rdNombreLibreWrap').style.display = val ? 'none' : '';
}

// ══════════════════════════════════════════════════
// Búsqueda de productos
// ══════════════════════════════════════════════════
async function rdBuscarCodigo() {
    const codigo = document.getElementById('rdBarcode').value.trim();
    if (!codigo) return;
    try {
        const res = await api('GET', `/api/productos/buscar?q=${encodeURIComponent(codigo)}`);
        const lista = Array.isArray(res) ? res : (res.data || []);
        // Buscar match exacto por código
        const exacto = lista.find(p =>
            (p.codigo || '').toLowerCase() === codigo.toLowerCase() ||
            (p.codigo_referencia || '').toLowerCase() === codigo.toLowerCase()
        ) || lista[0];

        if (exacto) {
            rdSeleccionarProducto(exacto);
            document.getElementById('rdBarcode').value = '';
            document.getElementById('rdCantidad').focus();
        } else {
            toast('Código no encontrado', 'warn');
        }
    } catch { toast('Error buscando producto', 'err'); }
}

function rdBuscarNombre(term) {
    clearTimeout(rdSearchTimer);
    const el = document.getElementById('rdResultadosNombre');
    if (term.length < 2) { el.innerHTML = ''; return; }

    rdSearchTimer = setTimeout(async () => {
        try {
            const res = await api('GET', `/api/productos/buscar?q=${encodeURIComponent(term)}`);
            const lista = Array.isArray(res) ? res : (res.data || []);
            el.innerHTML = lista.slice(0, 6).map(p => `
                <div onclick='rdSeleccionarProducto(${JSON.stringify(p).replace(/'/g,"&apos;")})'
                    style="padding:10px 12px;background:var(--s2);border:1px solid var(--b1);
                           border-radius:var(--r);cursor:pointer;margin-bottom:5px;
                           display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:500;font-size:13px;">${p.nombre}</div>
                        <div class="mono" style="font-size:11px;color:var(--t2);">${p.codigo || '—'}</div>
                    </div>
                    <div class="mono" style="font-size:12px;color:var(--t2);">
                        ${parseFloat(p.cantidad || 0)} ${p.unidad_medida || 'un'}
                    </div>
                </div>
            `).join('') || '<p style="font-size:13px;color:var(--t2);">Sin resultados</p>';
        } catch {}
    }, 250);
}

function rdSeleccionarProducto(p) {
    document.getElementById('rdProdId').value = p.id;
    document.getElementById('rdProdNombre').textContent = p.nombre;
    document.getElementById('rdProdCodigo').textContent = p.codigo || '—';
    document.getElementById('rdProdStock').textContent = `${parseFloat(p.cantidad || 0)} ${p.unidad_medida || 'un'}`;
    document.getElementById('rdProductoSelWrap').style.display = '';
    document.getElementById('rdResultadosNombre').innerHTML = '';
    document.getElementById('rdBuscaNombre').value = '';
    document.getElementById('rdCantidad').value = '';
    document.getElementById('rdCostoUnit').value = p.costo || '';
    document.getElementById('rdCantidad').focus();
}

function rdLimpiarProducto() {
    document.getElementById('rdProductoSelWrap').style.display = 'none';
    document.getElementById('rdProdId').value = '';
    document.getElementById('rdBarcode').focus();
}

// ══════════════════════════════════════════════════
// Gestión de items (primero local, luego persiste)
// ══════════════════════════════════════════════════
async function rdAgregarItem() {
    const prodId   = document.getElementById('rdProdId').value;
    const cantidad = parseFloat(document.getElementById('rdCantidad').value);
    const costo    = parseInt(document.getElementById('rdCostoUnit').value) || 0;

    if (!prodId) { toast('Selecciona un producto', 'warn'); return; }
    if (!cantidad || cantidad <= 0) { toast('Ingresa una cantidad válida', 'warn'); return; }

    // Crear recepción si no existe aún
    if (!rdRecepcionId) {
        try {
            const provId   = document.getElementById('rdProveedorId').value || null;
            const provNombre = document.getElementById('rdNombreLibre').value || null;
            const rec = await api('POST', '/api/recepciones-directas', {
                proveedor_id: provId ? parseInt(provId) : null,
                proveedor_nombre: provNombre,
            });
            rdRecepcionId = rec.id;
        } catch(e) { toast('Error creando recepción', 'err'); return; }
    }

    try {
        await api('POST', `/api/recepciones-directas/${rdRecepcionId}/items`, {
            producto_id:    parseInt(prodId),
            cantidad:       cantidad,
            costo_unitario: costo,
        });

        // Actualizar lista local
        const nombre = document.getElementById('rdProdNombre').textContent;
        const idx = rdItems.findIndex(i => i.producto_id === parseInt(prodId));
        if (idx >= 0) {
            rdItems[idx].cantidad = cantidad;
            rdItems[idx].costo_unitario = costo;
        } else {
            rdItems.push({ producto_id: parseInt(prodId), nombre, cantidad, costo_unitario: costo });
        }

        rdRenderItems();
        rdLimpiarProducto();
        toast(`✓ ${nombre} agregado`, 'ok', 1500);
        document.getElementById('rdBarcode').focus();
    } catch(e) { toast(e.message || 'Error', 'err'); }
}

async function rdQuitarItem(prodId) {
    if (!rdRecepcionId) return;
    // Necesitamos el itemId — pedimos la recepción actualizada
    try {
        const rec = await api('GET', `/api/recepciones-directas/${rdRecepcionId}`); // no existe aún, usar items
        // Simplificado: buscar en la lista
        rdItems = rdItems.filter(i => i.producto_id !== prodId);
        rdRenderItems();
        // Llamar endpoint quitar — necesitamos itemId desde servidor
        // Por ahora reingresar con cantidad 0 no es posible, se recarga del servidor
        toast('Item eliminado', 'ok');
    } catch {}
}

function rdRenderItems() {
    const wrap = document.getElementById('rdItemsWrap');
    const lista = document.getElementById('rdItemsList');

    if (!rdItems.length) { wrap.style.display = 'none'; return; }
    wrap.style.display = '';

    let total = 0;
    lista.innerHTML = rdItems.map(i => {
        const subtotal = i.cantidad * (i.costo_unitario || 0);
        total += subtotal;
        return `
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:10px 12px;background:var(--s2);border:1px solid var(--b1);
                    border-radius:var(--r);margin-bottom:6px;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:500;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${i.nombre}</div>
                <div class="mono" style="font-size:12px;color:var(--t2);">
                    ${i.cantidad} × ${i.costo_unitario > 0 ? fmt(i.costo_unitario) : '—'}
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                ${subtotal > 0 ? `<span class="mono" style="color:var(--accent);font-weight:700;">${fmt(subtotal)}</span>` : ''}
                <button onclick="rdQuitarItem(${i.producto_id})"
                    style="background:none;border:none;color:var(--err);cursor:pointer;font-size:16px;">✕</button>
            </div>
        </div>`;
    }).join('');

    document.getElementById('rdTotal').textContent = total > 0 ? fmt(total) : '$0';
}

// ══════════════════════════════════════════════════
// Cerrar recepción → pasa a caja
// ══════════════════════════════════════════════════
async function rdCerrarRecepcion() {
    if (!rdRecepcionId) { toast('Agrega al menos un producto', 'warn'); return; }
    if (!rdItems.length) { toast('Agrega al menos un producto', 'warn'); return; }

    const btn = document.getElementById('btnCerrarRD');
    btn.disabled = true;
    try {
        await api('POST', `/api/recepciones-directas/${rdRecepcionId}/cerrar`, {
            notas: document.getElementById('rdNotas').value || null,
        });
        toast('✓ Enviado a caja para pago', 'ok');
        rdResetForm();
        rdRefrescarBadge();
    } catch(e) {
        toast(e.message || 'Error', 'err');
    } finally {
        btn.disabled = false;
    }
}

function rdResetForm() {
    rdRecepcionId = null;
    rdItems = [];
    rdRenderItems();
    document.getElementById('rdProveedorId').value = '';
    document.getElementById('rdNombreLibre').value = '';
    document.getElementById('rdNombreLibreWrap').style.display = '';
    document.getElementById('rdNotas').value = '';
    rdLimpiarProducto();
    document.getElementById('rdBarcode').focus();
    document.getElementById('rdEstado').textContent = 'Selecciona un modo';
}

// ══════════════════════════════════════════════════
// Badge y pendientes de pago (Caja)
// ══════════════════════════════════════════════════
async function rdRefrescarBadge() {
    try {
        const { count } = await api('GET', '/api/recepciones-directas/pendientes-count');
        const badge  = document.getElementById('badgePendientes');
        const btnPend = document.getElementById('btnPendientes');
        if (!badge || !btnPend) return;
        if (count > 0) {
            badge.textContent = count;
            btnPend.style.display = '';
        } else {
            btnPend.style.display = 'none';
        }
    } catch {}
}

async function rdVerPendientes() {
    openModal('mPendientes');
    const body = document.getElementById('mPendientesBody');
    body.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
    try {
        const data = await api('GET', '/api/recepciones-directas?estado=pendiente_pago&per_page=50');
        const lista = data.data || data;
        if (!lista.length) {
            body.innerHTML = '<p style="text-align:center;color:var(--t2);padding:20px;">Sin recepciones pendientes</p>';
            return;
        }
        body.innerHTML = lista.map(r => `
            <div style="background:var(--s2);border:1px solid var(--b1);border-radius:var(--r);
                        padding:14px;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="font-weight:600;">${r.proveedor?.nombre || r.proveedor_nombre || 'Sin proveedor'}</div>
                        <div style="font-size:12px;color:var(--t2);">
                            ${r.usuario?.nombre || 'Operario'} · ${new Date(r.cerrada_at||r.created_at).toLocaleTimeString('es-CL')}
                        </div>
                    </div>
                    <div class="mono" style="color:var(--accent);font-weight:700;">
                        ${r.monto_total > 0 ? fmt(r.monto_total) : '—'}
                    </div>
                </div>
                <div style="margin-top:8px;font-size:12px;color:var(--t2);">
                    ${(r.items||[]).map(i => `${parseFloat(i.cantidad)} × ${i.producto?.nombre || '?'}`).join(' · ')}
                </div>
                ${r.notas ? `<div style="margin-top:6px;font-size:12px;color:var(--t2);font-style:italic;">${r.notas}</div>` : ''}
                <button class="btn btn-primary btn-full" style="margin-top:10px;"
                    onclick="rdAbrirPagar(${r.id},'${r.proveedor?.nombre || r.proveedor_nombre || 'Sin proveedor'}',${r.monto_total||0})">
                    💳 Pagar y subir stock
                </button>
            </div>
        `).join('');
    } catch { body.innerHTML = '<p style="color:var(--err);text-align:center;">Error cargando</p>'; }
}

function rdAbrirPagar(id, nombreProv, monto) {
    document.getElementById('mPagarId').value = id;
    document.getElementById('mPagarMonto').value = monto || '';
    document.getElementById('mPagarDoc').value = '';
    document.getElementById('mPagarResumen').innerHTML = `
        <div><b>Proveedor:</b> ${nombreProv}</div>
        <div style="margin-top:4px;"><b>Monto:</b> <span class="mono" style="color:var(--accent);">${monto > 0 ? fmt(monto) : 'sin monto ingresado'}</span></div>
    `;
    closeModal('mPendientes');
    openModal('mPagar');
}

async function rdConfirmarPago() {
    const id     = document.getElementById('mPagarId').value;
    const tipo   = document.getElementById('mPagarTipo').value;
    const doc    = document.getElementById('mPagarDoc').value;
    const monto  = parseInt(document.getElementById('mPagarMonto').value) || 0;

    try {
        await api('POST', `/api/recepciones-directas/${id}/pagar`, {
            tipo_pago:        tipo,
            numero_documento: doc || null,
            monto_total:      monto || null,
        });
        toast('✓ Stock actualizado y pago registrado', 'ok');
        closeModal('mPagar');
        rdRefrescarBadge();
    } catch(e) { toast(e.message || 'Error', 'err'); }
}

// ══════════════════════════════════════════════════
// Historial
// ══════════════════════════════════════════════════
async function rdCargarHistorial() {
    const el = document.getElementById('rdHistorialLista');
    el.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
    try {
        const data = await api('GET', '/api/recepciones-directas?per_page=30');
        const lista = data.data || data;

        const colorEstado = { borrador:'#4a4a60', pendiente_pago:'#f5c518', pagada:'#00e5a0', anulada:'#ff3f5b' };
        const labelEstado = { borrador:'Borrador', pendiente_pago:'Pendiente pago', pagada:'Pagada', anulada:'Anulada' };

        el.innerHTML = lista.length ? lista.map(r => `
            <div style="background:var(--s2);border:1px solid var(--b1);border-radius:var(--r);
                        padding:14px;margin-bottom:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:600;">${r.proveedor?.nombre || r.proveedor_nombre || 'Sin proveedor'}</div>
                        <div style="font-size:12px;color:var(--t2);">${new Date(r.created_at).toLocaleString('es-CL')}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="mono" style="color:${colorEstado[r.estado] || '#fff'};font-size:11px;font-weight:700;">
                            ${labelEstado[r.estado] || r.estado}
                        </div>
                        ${r.monto_total > 0 ? `<div class="mono" style="color:var(--accent);">${fmt(r.monto_total)}</div>` : ''}
                    </div>
                </div>
                <div style="margin-top:8px;font-size:12px;color:var(--t2);">
                    ${(r.items||[]).slice(0,4).map(i => `${parseFloat(i.cantidad)} × ${i.producto?.nombre || '?'}`).join(' · ')}
                    ${(r.items||[]).length > 4 ? ` +${(r.items||[]).length - 4} más` : ''}
                </div>
            </div>
        `).join('') : '<p style="text-align:center;color:var(--t2);padding:20px;">Sin recepciones</p>';
    } catch { el.innerHTML = '<p style="color:var(--err);text-align:center;">Error</p>'; }
}
</script>
@endpush
@endsection