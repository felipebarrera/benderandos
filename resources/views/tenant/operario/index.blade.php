@extends('tenant.layout')

@section('content')
<div class="pos-shell">
    <!-- Panel izquierdo: Vender -->
    <div class="pos-catalog" style="padding:0;">
        <!-- Tabs internos -->
        <div style="display:flex; border-bottom:1px solid var(--b1); background:var(--s1);">
            <button class="bottom-nav-item" id="tabVender" onclick="setOperarioTab('vender')" style="flex:1;padding:14px 8px;border-radius:0;border-right:1px solid var(--b1);">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Vender
            </button>
            <button class="bottom-nav-item" id="tabStock" onclick="setOperarioTab('stock')" style="flex:1;padding:14px 8px;border-radius:0;border-right:1px solid var(--b1);">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Stock
            </button>
            <button class="bottom-nav-item" id="tabMis" onclick="setOperarioTab('mis')" style="flex:1;padding:14px 8px;border-radius:0;">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Mis ventas
            </button>
        </div>

        <!-- Tab Vender -->
        <div id="panelVender" class="pos-catalog" style="flex:1;">
            <div style="padding:14px 16px; border-bottom:1px solid var(--b1); background:var(--s1); position:sticky; top:0; z-index:10;">
                <div class="search-wrap">
                    <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" id="prodBusca" placeholder="Buscar producto..." autofocus oninput="filtrarOp(this.value)">
                </div>
            </div>
            <div style="padding:16px; overflow-y:auto; flex:1;">
                <div class="product-grid" id="opProductGrid">
                    @forelse($productos ?? [] as $prod)
                    <div class="product-card" onclick="agregarCarroOp({ id:{{ $prod->id }}, nombre:{{ json_encode($prod->nombre) }}, precio:{{ $prod->precio }}, stock:{{ $prod->stock ?? 0 }} })">
                        <div class="product-img">
                            <span style="font-size:28px;opacity:.3;">{{ mb_substr($prod->nombre, 0, 1) }}</span>
                        </div>
                        <div class="product-body">
                            <div class="product-name">{{ $prod->nombre }}</div>
                            <div class="product-price">${{ number_format($prod->precio, 0, ',', '.') }}</div>
                            @if(isset($prod->stock))
                            <div class="product-stock {{ $prod->stock <= ($prod->stock_minimo ?? 0) ? 'low' : '' }}">
                                {{ $prod->stock }} disp.
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="empty-state" style="grid-column:1/-1;">
                        <h3>Sin productos</h3>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Tab Stock -->
        <div id="panelStock" style="display:none; padding:20px; overflow-y:auto;">
            <div style="max-width:440px; margin:0 auto;">
                <div class="field">
                    <label class="label">Buscar Producto</label>
                    <div class="search-wrap">
                        <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="ajusteSearch" placeholder="Nombre, código..." oninput="buscarParaAjuste()">
                    </div>
                </div>
                <div id="ajusteResultados" style="margin-bottom:20px;"></div>

                <div id="ajusteForm" class="card" style="display:none;">
                    <div style="font-weight:600; font-size:15px; margin-bottom:14px;" id="ajusteProdNombre">Producto</div>
                    <input type="hidden" id="ajusteProdIdOp">
                    <div class="field">
                        <label class="label">Motivo</label>
                        <select id="ajusteMotivo">
                            <option value="entrada">Llegada mercadería</option>
                            <option value="merma">Merma / Pérdida</option>
                            <option value="ajuste">Ajuste manual</option>
                            <option value="devolucion">Devolución</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="label">Cantidad (+/-)</label>
                        <input type="number" id="ajusteCantidad" class="mono" placeholder="Ej: 10 / -5">
                    </div>
                    <button class="btn btn-primary btn-full" onclick="aplicarAjusteOp()">Aplicar Ajuste</button>
                </div>
            </div>

            <!-- Log del día -->
            <div class="divider" style="margin-top:24px;"></div>
            <div style="font-weight:600; margin-bottom:12px;">Movimientos del día</div>
            <div id="logMovimientos" class="empty-state" style="padding:20px 0;">
                <div class="spinner"></div>
            </div>
        </div>

        <!-- Tab Mis Ventas -->
        <div id="panelMis" style="display:none; padding:20px; overflow-y:auto;">
            <div id="misVentasLista" class="empty-state" style="padding:20px 0;">
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <!-- Carro (igual que POS) -->
    <div class="pos-cart">
        <div class="pos-cart-header">
            <span style="font-weight:700; font-size:15px;">Carro</span>
            <div style="display:flex; gap:10px;">
                <span class="badge badge-blue" id="opCartCount">0</span>
                <button class="btn btn-sm btn-secondary" onclick="opVaciar()">Vaciar</button>
            </div>
        </div>
        <div class="pos-cart-items" id="opCartItems">
            <div class="empty-state" style="padding:40px 20px;" id="opCartEmpty">
                <svg fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="width:36px;height:36px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <p style="font-size:13px;color:var(--t2);margin-top:8px;">Sin items</p>
            </div>
        </div>
        <div class="pos-cart-footer">
            <div class="total-row grand">
                <span>Total</span>
                <span id="opTotal">$0</span>
            </div>
            <div class="payment-pills" id="opPayPills" style="margin:12px 0;">
                <button class="payment-pill selected" data-m="efectivo" onclick="opSelectMetodo(this)">Efectivo</button>
                <button class="payment-pill" data-m="debito" onclick="opSelectMetodo(this)">Débito</button>
                <button class="payment-pill" data-m="transferencia" onclick="opSelectMetodo(this)">Transfer</button>
            </div>
            <button class="btn-cobrar" onclick="opConfirmarVenta()">COBRAR</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let opCarro = [];
let opMetodo = 'efectivo';

function setOperarioTab(tab) {
    ['vender','stock','mis'].forEach(t => {
        document.getElementById(`panel${t.charAt(0).toUpperCase()+t.slice(1)}`).style.display = t === tab ? 'flex' : 'none';
        document.getElementById(`tab${t.charAt(0).toUpperCase()+t.slice(1)}`).classList.toggle('active', t === tab);
    });
    if (tab === 'stock') cargarLogMovimientos();
    if (tab === 'mis') cargarMisVentas();
}

// -- Carro Operario
function filtrarOp(term) {
    document.querySelectorAll('#opProductGrid .product-card').forEach(c => {
        c.style.display = c.querySelector('.product-name').textContent.toLowerCase().includes(term.toLowerCase()) ? '' : 'none';
    });
}
function agregarCarroOp(prod) {
    const ex = opCarro.find(i => i.id === prod.id);
    if (ex) ex.qty++;
    else opCarro.push({...prod, qty:1});
    renderCarroOp();
    toast(prod.nombre, 'ok', 1000);
}
function renderCarroOp() {
    const el = document.getElementById('opCartItems');
    const empty = document.getElementById('opCartEmpty');
    document.getElementById('opCartCount').textContent = opCarro.reduce((s,i)=>s+i.qty,0);
    if (!opCarro.length) { empty.style.display='flex'; el.innerHTML=''; el.appendChild(empty); return; }
    empty.style.display='none';
    const total = opCarro.reduce((s,i)=>s+i.precio*i.qty,0);
    document.getElementById('opTotal').textContent = fmt(total);
    el.innerHTML = opCarro.map((i,idx) => `
        <div class="cart-item">
            <div style="flex:1;min-width:0;"><div class="cart-item-name">${i.nombre}</div>
            <div class="cart-item-sub mono">${fmt(i.precio)}</div></div>
            <div class="qty-control">
                <button class="qty-btn" onclick="opQty(${idx},-1)">−</button>
                <span class="qty-val">${i.qty}</span>
                <button class="qty-btn" onclick="opQty(${idx},1)">+</button>
            </div>
            <span class="mono" style="min-width:68px;text-align:right;color:var(--accent);font-weight:700;">${fmt(i.precio*i.qty)}</span>
        </div>`).join('');
}
function opQty(i,d){ opCarro[i].qty+=d; if(opCarro[i].qty<=0) opCarro.splice(i,1); renderCarroOp(); }
function opVaciar(){ opCarro=[]; renderCarroOp(); }
function opSelectMetodo(btn){
    document.querySelectorAll('#opPayPills .payment-pill').forEach(p=>p.classList.remove('selected'));
    btn.classList.add('selected'); opMetodo = btn.dataset.m;
}

async function opConfirmarVenta() {
    if (!opCarro.length) { toast('Agrega productos', 'warn'); return; }
    try {
        const res = await api('POST', '/api/ventas', {
            tipo_pago: opMetodo,
            items: opCarro.map(i => ({ producto_id: i.id, cantidad: i.qty }))
        });
        await api('POST', `/api/ventas/${res.id}/confirmar`, { tipo_pago: opMetodo });
        toast('Venta registrada', 'ok');
        opCarro = [];
        renderCarroOp();
    } catch(e) { toast(e.message || 'Error', 'err'); }
}

// -- Ajuste Stock
function buscarParaAjuste() {
    const term = document.getElementById('ajusteSearch').value.toLowerCase();
    const resultados = document.getElementById('ajusteResultados');
    if (!term) { resultados.innerHTML = ''; return; }
    // Filter from window products if available
    const todos = @json($productos ?? []);
    const filtrados = todos.filter(p => p.nombre?.toLowerCase().includes(term));
    resultados.innerHTML = filtrados.slice(0,5).map(p => `
        <div onclick="seleccionarAjuste(${p.id}, '${p.nombre}', ${p.stock ?? 0})" 
            style="padding:10px 14px;background:var(--s2);border:1px solid var(--b1);border-radius:var(--r);cursor:pointer;margin-bottom:6px;">
            <div style="font-weight:500;">${p.nombre}</div>
            <div class="mono text-muted" style="font-size:12px;">Stock: ${p.stock ?? 0}</div>
        </div>`).join('') || '<p style="color:var(--t2);font-size:13px;">Sin resultados</p>';
}

function seleccionarAjuste(id, nombre, stock) {
    document.getElementById('ajusteProdIdOp').value = id;
    document.getElementById('ajusteProdNombre').textContent = `${nombre} (Stock: ${stock})`;
    document.getElementById('ajusteResultados').innerHTML = '';
    document.getElementById('ajusteSearch').value = '';
    document.getElementById('ajusteForm').style.display = '';
}

async function aplicarAjusteOp() {
    const id = document.getElementById('ajusteProdIdOp').value;
    const body = {
        cantidad: parseFloat(document.getElementById('ajusteCantidad').value) || 0,
        motivo: document.getElementById('ajusteMotivo').value,
    };
    try {
        await api('POST', `/api/productos/${id}/ajuste-stock`, body);
        toast('Stock ajustado', 'ok');
        document.getElementById('ajusteForm').style.display = 'none';
        document.getElementById('ajusteCantidad').value = '';
        cargarLogMovimientos();
    } catch(e) { toast(e.message || 'Error','err'); }
}

async function cargarLogMovimientos() {
    const el = document.getElementById('logMovimientos');
    try {
        // Approximate: show recent products
        el.innerHTML = '<p style="color:var(--t2);font-size:13px;text-align:center;">Movimientos registrados en el inventario aparecerán aquí</p>';
    } catch { }
}

async function cargarMisVentas() {
    const el = document.getElementById('misVentasLista');
    try {
        const data = await api('GET', '/api/ventas?limit=20');
        const ventas = data.data ?? data;
        if (!ventas.length) { el.innerHTML = '<p style="color:var(--t2);font-size:13px;text-align:center;">Sin ventas hoy</p>'; return; }
        el.className = '';
        el.innerHTML = ventas.map(v => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--s2);border:1px solid var(--b1);border-radius:var(--r);margin-bottom:8px;">
                <div>
                    <span class="mono" style="font-size:12px;">#${String(v.id).padStart(4,'0')}</span>
                    <span style="font-size:12px;color:var(--t2);margin-left:8px;">${new Date(v.created_at).toLocaleTimeString('es-CL')}</span>
                </div>
                <span class="mono text-accent">${fmt(v.total)}</span>
            </div>`).join('');
    } catch { el.innerHTML = '<p style="color:var(--err);text-align:center;">Error</p>'; }
}

// Init
setOperarioTab('vender');
renderCarroOp();
</script>
@endpush
@endsection
