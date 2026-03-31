@extends('tenant.layout')

@push('head')
<style>
/* ============================================================
   MOBILE-FIRST CORRECTO (migración de pos_v3.html)
   ============================================================ */
:root {
  --bg:      #08080a;
  --s1:      #111115;
  --s2:      #18181e;
  --s3:      #1e1e28;
  --b1:      #1e1e28;
  --b2:      #2a2a3a;
  --tx:      #e8e8f0;
  --t2:      #7878a0;
  --t3:      #3a3a55;
  --ac:      #00e5a0;
  --ac2:     #00b87c;
  --warn:    #f5c518;
  --err:     #ff3f5b;
  --info:    #4488ff;
  --mono:    'IBM Plex Mono', monospace;
  --sans:    'IBM Plex Sans', sans-serif;
  --topbar:  0px;  /* Se usa la nav del layout app-shell */
  --strip:   64px; /* cajón colapsado */
}

/* Redefinir área de workspace dentro del main app-shell de Laravel */
.workspace {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
  height: calc(100vh - 56px); /* Altura de la topnav en mobile */
  background: var(--bg);
}

.products-area {
  flex: 1; display: flex; flex-direction: column;
  overflow: hidden; min-height: 0;
  padding-bottom: var(--strip); /* espacio real debajo para cajón */
}

.search-bar {
  padding: 9px 12px; background: var(--s1); border-bottom: 1px solid var(--b1);
  display: flex; gap: 8px; align-items: center; flex-shrink: 0;
}
.search-wrap { flex: 1; position: relative; }
.s-ico {
  position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
  font-size: 15px; color: var(--t2); pointer-events: none;
}
.search-input {
  width: 100%; background: var(--s2); border: 1.5px solid var(--b2); border-radius: 8px;
  color: var(--tx); font-family: var(--mono); font-size: 15px;
  padding: 9px 10px 9px 34px; outline: none; -webkit-appearance: none;
  transition: border-color .15s;
}
.search-input:focus { border-color: var(--ac); }
.search-input::placeholder { color: var(--t3); font-size: 11px; }

.clear-btn {
  background: var(--s2); border: 1.5px solid var(--b2); border-radius: 8px;
  color: var(--t2); font-size: 12px; padding: 9px 12px;
  cursor: pointer; flex-shrink: 0; line-height: 1;
}
.clear-btn:active { border-color: var(--ac); color: var(--ac); }

.pills {
  display: flex; gap: 6px; padding: 8px 12px; overflow-x: auto;
  flex-shrink: 0; background: var(--s1); border-bottom: 1px solid var(--b1);
  scrollbar-width: none;
}
.pills::-webkit-scrollbar { display: none; }
.pill {
  flex-shrink: 0; padding: 5px 12px; border-radius: 20px; font-size: 10px;
  font-weight: 700; font-family: var(--mono); letter-spacing: .5px;
  cursor: pointer; border: 1px solid var(--b2); background: var(--s2);
  color: var(--t2); transition: all .12s; -webkit-tap-highlight-color: transparent;
}
.pill.on { background: rgba(0,229,160,.1); border-color: var(--ac); color: var(--ac); }

.prods-scroll {
  flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch;
  padding: 8px; display: flex; flex-direction: column; gap: 4px;
}
.prods-scroll::-webkit-scrollbar { width: 4px; }
.prods-scroll::-webkit-scrollbar-thumb { background: var(--b2); border-radius: 2px;}

.prod {
  background: var(--s2); border: 1px solid var(--b1); border-radius: 8px;
  display: flex; align-items: stretch; cursor: pointer; overflow: hidden;
  -webkit-tap-highlight-color: transparent; transition: border-color .1s;
}
.prod:active { border-color: var(--ac); background:rgba(0,229,160,.03); }
.prod.nostock { opacity: .3; pointer-events: none; }
.prod-body { flex: 1; min-width: 0; padding: 10px 12px; }
.prod-tipo {
  font-family: var(--mono); font-size: 9px; font-weight: 700;
  letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 2px;
}
.prod-nombre { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prod-codigo { font-family: var(--mono); font-size: 10px; color: var(--t3); margin-top: 2px; }
.prod-right { display: flex; flex-direction: column; align-items: flex-end; justify-content: center; padding: 10px 8px; gap: 4px; flex-shrink: 0; }
.prod-precio { font-family: var(--mono); font-size: 13px; font-weight: 700; color:var(--tx); }
.prod-stk { font-family: var(--mono); font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px; }
.ok { color: var(--ac); background: rgba(0,229,160,.1); }
.low { color: var(--warn); background: rgba(245,197,24,.1); }
.zer { color: var(--err); background: rgba(255,63,91,.1); }

.prod-add {
  width: 42px; min-width: 42px; background: none; border: none;
  border-left: 1px solid var(--b1); color: var(--t2); font-size: 22px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  -webkit-tap-highlight-color: transparent; transition: color .1s, background .1s;
}
.prod-add:active { color: var(--ac); background: rgba(0,229,160,.07); }
.no-res { padding: 48px 16px; text-align: center; font-family: var(--mono); font-size: 11px; color: var(--t3); }

/* CAJÓN BOTTOM mobile */
.drawer {
  position: absolute; left: 0; right: 0; bottom: 0; background: var(--s1);
  border-top: 1px solid var(--b2); display: flex; flex-direction: column;
  z-index: 100; transition: height .32s cubic-bezier(.4,0,.2,1); height: var(--strip);
  overflow: hidden;
}
.drawer.open { height: 85%; }
.strip {
  height: var(--strip); min-height: var(--strip); display: flex; align-items: center;
  padding: 0 14px; gap: 12px; cursor: pointer; -webkit-tap-highlight-color: transparent;
  flex-shrink: 0; position: relative; border-bottom:1px solid var(--b1);
}
.strip::before {
  content: ''; position: absolute; top: 8px; left: 50%; transform: translateX(-50%);
  width: 36px; height: 4px; border-radius: 2px; background: var(--b2);
}
.strip-icon { font-size: 16px; color: var(--t2); }
.strip-label { font-family: var(--mono); font-size: 10px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--t2); }
.strip-count { font-family: var(--mono); font-size: 11px; font-weight: 700; color: var(--ac); background: rgba(0,229,160,.1); border: 1px solid rgba(0,229,160,.2); padding: 3px 8px; border-radius: 4px; min-width: 28px; text-align: center; }
.strip-total { margin-left: auto; font-family: var(--mono); font-size: 17px; font-weight: 700; color: var(--tx); }
.strip-chevron { font-size: 11px; color: var(--t2); transition: transform .3s; }
.drawer.open .strip-chevron { transform: rotate(180deg); }

.drawer-body { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0; }

/* SECCIÓN CLIENTE */
.czone { padding: 10px 14px; border-bottom: 1px solid var(--b1); flex-shrink: 0; }
.czone-lbl { font-family: var(--mono); font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--t3); margin-bottom: 6px; }
.rut-row { display: flex; gap: 6px; }
.rut-input {
  flex: 1; background: var(--s2); border: 1.5px solid var(--b2); border-radius: 8px; color: var(--tx); font-family: var(--mono);
  font-size: 14px; padding: 8px 10px; outline: none; -webkit-appearance: none; min-width: 0; transition: border-color .15s;
}
.rut-input:focus { border-color: var(--ac); }
.rut-input::placeholder { color: var(--t3); font-size: 11px; }
.rut-input.err { border-color: var(--err); }
.rut-btn {
  background: var(--s2); border: 1.5px solid var(--b2); border-radius: 8px; color: var(--t2); font-family: var(--sans);
  font-size: 12px; font-weight: 600; padding: 8px 14px; cursor: pointer; flex-shrink: 0; transition: all .12s;
}
.rut-btn:active { border-color: var(--ac); color: var(--ac); }
.rut-btn.loading { opacity: .5; pointer-events: none; }

.client-chip {
  display: none; align-items: center; gap: 8px; margin-top: 8px; padding: 7px 10px; background: rgba(255,255,255,.05); border: 1px solid var(--b2); border-radius: 8px;
}
.client-chip.show { display: flex; }
.chip-rut { font-family: var(--mono); font-size: 10px; color: var(--t2); }
.chip-nombre { font-size: 13px; font-weight: 500; color: var(--ac); flex: 1; }
.chip-x { background: none; border: none; color: var(--t2); font-size: 14px; cursor: pointer; line-height: 1; padding: 2px 4px; }
.rut-msg {
  font-size: 11px; font-family: var(--mono); margin-top: 6px; display: none; padding: 5px 8px; border-radius: 6px;
}
.rut-msg.ok { color: var(--ac); background: rgba(0,229,160,.07); display: block; }
.rut-msg.err { color: var(--err); background: rgba(255,63,91,.07); display: block; }

/* ITEMS */
.cart-list { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 4px 8px; min-height: 0; }
.cart-list::-webkit-scrollbar { width: 2px; }
.cart-list::-webkit-scrollbar-thumb { background: var(--b2); }
.cart-empty { padding: 32px 16px; text-align: center; font-family: var(--mono); font-size: 11px; color: var(--t3); }

.ci { display: grid; grid-template-columns: 1fr auto; grid-template-rows: auto auto; gap: 2px 6px; padding: 9px 6px; border-bottom: 1px solid var(--b1); }
.ci:last-child { border-bottom: none; }
.ci-name { grid-column:1; grid-row:1; font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color:var(--tx);}
.ci-sub { grid-column:2; grid-row:1; font-family: var(--mono); font-size: 13px; font-weight: 700; text-align: right; white-space: nowrap; color:var(--tx);}
.ci-ctrl { grid-column:1; grid-row:2; display: flex; align-items: center; gap: 0; }
.ci-unit { grid-column:2; grid-row:2; font-family: var(--mono); font-size: 10px; color: var(--t2); text-align: right; white-space: nowrap; align-self: center; }

.qbtn { width: 28px; height: 28px; background: var(--s3); border: 1px solid var(--b2); border-radius: 6px; color: var(--tx); font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; -webkit-tap-highlight-color: transparent; transition: all .1s; }
.qbtn:active { border-color: var(--ac); color: var(--ac); }
.qnum { font-family: var(--mono); font-size: 12px; font-weight: 600; width: 30px; text-align: center; color:var(--tx);}
.ci-del { background: none; border: none; color: var(--t3); font-size: 13px; cursor: pointer; padding: 4px 6px; margin-left: 2px; transition: color .12s; line-height: 1; }
.ci-del:active { color: var(--err); }

/* TOTALES + PAGO */
.carro-foot { border-top: 1px solid var(--b1); flex-shrink: 0; background:var(--s1); }
.totals { padding: 8px 14px 4px; }
.tot-row { display: flex; justify-content: space-between; align-items: center; padding: 3px 0; }
.tot-lbl { font-size: 11px; color: var(--t2); }
.tot-val { font-family: var(--mono); font-size: 12px; font-weight: 600; color:var(--tx);}
.tot-row.grand .tot-lbl { font-size: 14px; font-weight: 700; color: var(--tx); }
.tot-row.grand .tot-val { font-size: 20px; font-weight: 700; color: var(--tx); }

.disc-row { display: flex; gap: 6px; padding: 4px 0; }
.disc-input { flex: 1; background: var(--s2); border: 1.5px solid var(--b2); border-radius: 7px; color: var(--tx); font-family: var(--mono); font-size: 13px; padding: 6px 10px; outline: none; min-width: 0; transition: border-color .15s; }
.disc-input:focus { border-color: var(--ac); }
.disc-input::placeholder { color: var(--t3); font-size: 10px; }
.disc-sel { background: var(--s2); border: 1.5px solid var(--b2); border-radius: 7px; color: var(--tx); font-family: var(--mono); font-size: 12px; padding: 6px 8px; outline: none; cursor: pointer; flex-shrink: 0; }

.pay-zone { padding: 8px 12px 12px; }
.pay-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 5px; margin-bottom: 8px; }
.pbtn { background: var(--s2); border: 1.5px solid var(--b2); border-radius: 7px; color: var(--t2); font-family: var(--sans); font-size: 11px; font-weight: 600; padding: 8px 4px; cursor: pointer; -webkit-tap-highlight-color: transparent; transition: all .12s; }
.pbtn.on { background: rgba(0,229,160,.1); border-color: var(--ac); color: var(--ac); }

.btn-cobrar { width: 100%; height: 52px; background: var(--ac); border: none; border-radius: 10px; color: #000; font-family: var(--sans); font-size: 15px; font-weight: 700; cursor: pointer; letter-spacing: .3px; transition: filter .15s, opacity .15s; -webkit-tap-highlight-color: transparent; }
.btn-cobrar:disabled { opacity: .2; cursor: not-allowed; }
.btn-cobrar:not(:disabled):active { filter: brightness(.88); }

/* OVERLAY CAJÓN */
.overlay { position: absolute; inset: 0; background: rgba(0,0,0,.55); z-index: 90; display: none; pointer-events: none; }
.overlay.show { display: block; pointer-events: auto; }

/* MODAL COBRO */
.modal-wrap { position: fixed; inset: 0; background: rgba(0,0,0,.75); z-index: 300; display: none; align-items: flex-end; justify-content: center; }
.modal-wrap.show { display: flex; }
.modal-pos { background: var(--s1); border: 1px solid var(--b2); border-radius: 16px 16px 0 0; width: 100%; max-width: 480px; padding-bottom: 24px; animation: slideUp .25s ease; position:relative; z-index: 301; }
@keyframes slideUp { from { transform:translateY(32px); opacity:0; } to { transform:translateY(0); opacity:1; } }
.modal-handle { width: 36px; height: 4px; background: var(--b2); border-radius: 2px; margin: 10px auto 0; }
.modal-hdr { display: flex; align-items: center; padding: 14px 20px 12px; border-bottom: 1px solid var(--b1); }
.modal-ttl { font-family: var(--mono); font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--t2); flex: 1; margin:0;}
.modal-importe { font-family: var(--mono); font-size: 24px; font-weight: 700; color: var(--ac); }
.modal-x { background: none; border: none; color: var(--t2); font-size: 18px; cursor: pointer; padding: 4px 6px; margin-left: 12px; line-height: 1; }
.modal-cuerpo { padding: 16px 20px 0; }
.m-lbl { font-family: var(--mono); font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--t3); margin-bottom: 6px; }
.m-input { width: 100%; background: var(--s2); border: 1.5px solid var(--b2); border-radius: 10px; color: var(--tx); font-family: var(--mono); font-size: 22px; font-weight: 600; padding: 10px 14px; outline: none; margin-bottom: 10px; transition: border-color .15s; }
.m-input:focus { border-color: var(--ac); }

.vuelto-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: rgba(0,229,160,.06); border: 1px solid rgba(0,229,160,.14); border-radius: 8px; margin-bottom: 14px; }
.vuelto-lbl { font-size: 12px; color: var(--t2); }
.vuelto-val { font-family: var(--mono); font-size: 20px; font-weight: 700; color: var(--ac); }
.btn-confirmar { width: 100%; height: 54px; background: var(--ac); border: none; border-radius: 10px; color: #000; font-family: var(--sans); font-size: 15px; font-weight: 700; cursor: pointer; transition: filter .15s; display:flex; justify-content:center; align-items:center;}
.btn-confirmar:active { filter: brightness(.88); }
.btn-confirmar:disabled { opacity:.5; cursor:not-allowed; }

/* DESKTOP ≥ 768px */
@media (min-width: 768px) {
  .workspace { flex-direction: row; height: calc(100vh - 56px); } /* 56 nav desktop */
  .products-area { flex: 1; padding-bottom: 0; }
  .drawer { position: static !important; height: auto !important; width: 360px; min-width: 360px; border-top: none; border-left: 1px solid var(--b2); transition: none; overflow: hidden; }
  .strip { cursor: default; }
  .strip::before, .strip-chevron { display: none; }
  .drawer-body { display: flex !important; margin-bottom: 60px; } /* Ajuste scroll desktop - barra nav baja */
  .overlay { display: none !important; }
  .prods-scroll { display: grid; grid-template-columns: repeat(2,1fr); align-content: start; }
  .prod { flex-direction: column; }
  .prod-right { flex-direction: row; align-items: center; padding: 8px 10px 10px; gap: 6px; border-top:1px solid var(--b1); }
  .prod-precio { flex: 1; }
  .prod-add { width: 100%; min-width: auto; height: 34px; border-left: none; border-top: 1px solid var(--b1); }
  
  .modal-wrap { align-items: center; }
  .modal-pos { border-radius: 16px; margin-bottom:0;}
  .modal-handle { display: none; }
}
@media (min-width: 1100px) {
  .drawer { width: 400px; min-width: 400px; }
  .prods-scroll { grid-template-columns: repeat(3,1fr); }
}
</style>
@endpush

@section('content')
<!-- WORKSPACE POS V3 -->
<div class="workspace">

  <!-- OVERLAY (cierra cajón mobile) -->
  <div class="overlay" id="overlay" onclick="closeDrawer()"></div>

  <!-- ── PRODUCTOS ── -->
  <section class="products-area">
    <div class="search-bar">
      <div class="search-wrap">
        <span class="s-ico">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </span>
        <input class="search-input" id="searchInput" type="text"
          inputmode="search" autocomplete="off" spellcheck="false"
          placeholder="Nombre · código"
          oninput="applyFilters()">
      </div>
      <button class="clear-btn" onclick="clearSearch()">✕</button>
    </div>

    <!-- Barra de familias dinámica via JS -->
    <div class="pills" id="pillsBar"></div>

    <div class="prods-scroll" id="prodsList"></div>
  </section>

  <!-- ── CAJÓN / PANEL CARRO ── -->
  <div class="drawer" id="drawer">
    <div class="strip" id="strip" onclick="toggleDrawer()">
      <span class="strip-icon">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
      </span>
      <span class="strip-label">Carrito</span>
      <span class="strip-count" id="sCount">0</span>
      <span class="strip-total" id="sTotal">$0</span>
      <span class="strip-chevron" id="sChev">▲</span>
    </div>

    <div class="drawer-body" id="drawerBody" style="display:none">
      
      <!-- Cliente por RUT -->
      <div class="czone">
        <div class="czone-lbl">Identificar {{ $rubroConfig->label_cliente ?? 'cliente' }}</div>
        <div class="rut-row">
          <input class="rut-input mono" id="rutInput" type="text"
            inputmode="numeric" placeholder="12.345.678-9"
            oninput="fmtRut(this)"
            onkeydown="if(event.key==='Enter')buscarCliente()">
          <button class="rut-btn" id="rutBtn" onclick="buscarCliente()">Buscar</button>
        </div>
        <div class="rut-msg" id="rutMsg"></div>
        <div class="client-chip" id="clientChip">
          <span class="chip-rut" id="chipRut"></span>
          <span class="chip-nombre" id="chipNombre"></span>
          <button class="chip-x" onclick="limpiarCliente()">✕</button>
        </div>
      </div>

      <!-- Items -->
      <div class="cart-list" id="cartList"></div>

      <!-- Pie -->
      <div class="carro-foot">
        <div class="totals">
          <div class="tot-row">
            <span class="tot-lbl">Subtotal</span>
            <span class="tot-val" id="vSub">$0</span>
          </div>
          <div class="disc-row">
            <input class="disc-input mono" id="discVal" type="number"
              inputmode="decimal" placeholder="Descuento (opcional)" min="0" oninput="recalc()">
            <select class="disc-sel" id="discType" onchange="recalc()">
              <option value="pct">%</option>
              <option value="fix">$</option>
            </select>
          </div>
          <div class="tot-row" id="rowDisc" style="display:none">
            <span class="tot-lbl text-warn">Descuento aplicado</span>
            <span class="tot-val text-warn" id="vDisc" style="color:var(--warn)">-$0</span>
          </div>
          <div class="tot-row grand">
            <span class="tot-lbl">Total Neto</span>
            <span class="tot-val" id="vTotal">$0</span>
          </div>
        </div>
        
        <div class="pay-zone">
          <div class="pay-grid">
            <button class="pbtn on" onclick="selPay(this,'efectivo')">Efectivo</button>
            <button class="pbtn"    onclick="selPay(this,'debito')">Débito</button>
            <button class="pbtn"    onclick="selPay(this,'credito')">Crédito</button>
            <button class="pbtn"    onclick="selPay(this,'transferencia')">Transferencia</button>
          </div>
          <button class="btn-cobrar" id="btnCobrar" disabled onclick="abrirModal()">
            Cobrar →
          </button>
        </div>
      </div>
      
    </div>
  </div>
</div>

<!-- MODAL COBRO POS V3 -->
<div class="modal-wrap" id="modalWrap" onclick="cerrarModal(event)">
  <div class="modal-pos" onclick="event.stopPropagation()">
    <div class="modal-handle"></div>
    <div class="modal-hdr">
      <span class="modal-ttl">Confirmar pago</span>
      <span class="modal-importe mono" id="mTotal">$0</span>
      <button class="modal-x" onclick="cerrarModal()">✕</button>
    </div>
    <div class="modal-cuerpo">
      <div class="m-lbl" id="mAmountLbl">Monto recibido</div>
      <!-- se requiere value para envio de venta -->
      <input class="m-input mono" id="mAmount" type="number"
        inputmode="numeric" placeholder="$0" oninput="calcVuelto()">
      <div class="vuelto-row" id="vueltoRow" style="display:none">
        <span class="vuelto-lbl">Vuelto</span>
        <span class="vuelto-val mono" id="vueltoVal">$0</span>
      </div>
      
      <!-- Notas ocultas al pago principal para ahorrar UX, pero integrables -->
      <div style="margin-bottom:12px;">
         <input type="text" id="notaVenta" class="rut-input" style="font-size:12px; padding:6px 10px;" placeholder="Nota opcional de venta...">
      </div>

      <button class="btn-confirmar" id="btnConfirmarVentaFinal" onclick="confirmarVenta()">
        Confirmar venta →
      </button>
    </div>
  </div>
</div>

<!-- MANTENEMOS EL MODAL ORIGINAL PARA TICKETS (Ya modelado en UI base app-shell) -->
<div class="modal-overlay" id="modalTicket">
    <div class="modal" style="max-width:380px;">
        <div class="modal-head">
            <span class="modal-title">✓ Venta Exitosa</span>
            <button class="modal-close" data-close-modal="modalTicket" onclick="nuevaVenta()">✕</button>
        </div>
        <div class="modal-body">
            <div id="ticketPreview" style="font-family:'IBM Plex Mono',monospace; font-size:12px; background:white; color:#111; padding:20px 16px; border-radius:8px; line-height:1.8;">
            </div>
        </div>
        <div class="modal-foot" style="display:flex; justify-content:space-between;">
            <div>
               <button class="btn btn-secondary btn-sm" onclick="printTicket()">🖨</button>
            </div>
            <button class="btn btn-primary" onclick="nuevaVenta()">Nueva Venta</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* ── DEPENDENCIAS DB ── */
// Inyectar datos de la base al objeto global de javascript
const PRODS = @json($productos ?? []);
const TIPO_COLOR = {renta:'var(--warn)', servicio:'var(--info)'};
const TIPO_LABEL = {
    renta: '{{ $rubroConfig->label_recurso ?? "ARRIENDO" }}'.toUpperCase(), 
    servicio: 'SERVICIO'
};

let cart       = [];
let payMethod  = 'efectivo';
let clienteActual = null;
let totalActual= 0;
let familyAct  = null;
let drawerOpen = false;

// Helpers UX Mobile/Desktop
const isDesktop  = () => window.innerWidth >= 768;

/* ── DOM Init ── */
document.addEventListener('DOMContentLoaded', () => {
    buildPills();
    renderProds(PRODS);
    if (isDesktop()) showDrawerBody(true);
});

/* ── CAJÓN MOBILE ── */
function toggleDrawer() {
  if (isDesktop()) return;
  drawerOpen ? closeDrawer() : openDrawer();
}
function openDrawer() {
  drawerOpen = true;
  document.getElementById('drawer').classList.add('open');
  document.getElementById('overlay').classList.add('show');
  showDrawerBody(true);
}
function closeDrawer() {
  drawerOpen = false;
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}
function showDrawerBody(show) { document.getElementById('drawerBody').style.display = show ? 'flex' : 'none'; }
window.addEventListener('resize', () => {
  if (isDesktop()) {
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
    showDrawerBody(true);
    drawerOpen = false;
  } else { showDrawerBody(drawerOpen); }
});

/* ── PINTAR FAMILIAS DINAMICO ── */
function buildPills() {
  const fams = [...new Set(PRODS.map(p => p.familia).filter(f => f))];
  const bar  = document.getElementById('pillsBar');
  bar.innerHTML = '<button class="pill on" onclick="filterFamily(this, null)">Todas</button>';
  fams.forEach(f => {
    const b = document.createElement('button');
    b.className = 'pill'; b.textContent = f;
    b.onclick = () => filterFamily(b, f);
    bar.appendChild(b);
  });
}
function filterFamily(btn, fam) {
  document.querySelectorAll('#pillsBar .pill').forEach(p => p.classList.remove('on'));
  btn.classList.add('on');
  familyAct = fam; applyFilters();
}

/* ── PRODUCTOS GRID ── */
function applyFilters() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  let list = PRODS;
  if (familyAct) list = list.filter(p => p.familia === familyAct);
  if (q) list = list.filter(p => p.nombre.toLowerCase().includes(q) || (p.codigo||'').toLowerCase().includes(q));
  renderProds(list);
}
function clearSearch() { document.getElementById('searchInput').value = ''; applyFilters(); document.getElementById('searchInput').focus(); }

function renderProds(list) {
  const el = document.getElementById('prodsList');
  if (!list.length) { el.innerHTML = `<div class="no-res">Sin resultados</div>`; return; }
  el.innerHTML = list.map(p => {
    const min = p.stock_minimo || 0;
    const ss  = p.tipo !== 'servicio' && p.stock === 0;
    const sc  = p.stock != null ? (p.stock===0 ? 'zer' : p.stock<=min ? 'low' : 'ok') : '';
    const st  = p.stock != null ? (p.stock===0 ? 'SIN STOCK' : p.stock+' uds') : '';
    const th  = (p.tipo && p.tipo!=='stock_fisico') ? `<div class="prod-tipo" style="color:${TIPO_COLOR[p.tipo]||'var(--ac2)'}">${TIPO_LABEL[p.tipo] || p.tipo.toUpperCase()}</div>` : '';
    const thProd = p.tipo === 'stock_fisico' ? `<div class="prod-tipo" style="color:var(--ac2)">{{ $rubroConfig->label_producto ?? 'Producto' }}</div>`.toUpperCase() : '';
    
    return `
    <div class="prod${ss?' nostock':''}" onclick="addItem(${p.id})">
      <div class="prod-body">${th}${thProd}<div class="prod-nombre">${p.nombre}</div><div class="prod-codigo">${p.codigo||''}</div></div>
      <div class="prod-right">
        <div class="prod-precio text-tx">${fmt(p.valor_venta ?? p.precio ?? 0)}</div>
        ${p.tipo!=='servicio' && st ? `<span class="prod-stk ${sc}">${st}</span>` : ''}
      </div>
      <button class="prod-add" onclick="event.stopPropagation();addItem(${p.id})">+</button>
    </div>`;
  }).join('');
}

/* ── CARRO / LÓGICA CORE ── */
function addItem(id) {
  const p = PRODS.find(x => x.id === id); if (!p) return;
  const pPrecio = p.valor_venta ?? p.precio ?? 0;
  
  let addQty = 1;
  let extData = {};

  if (p.tipo === 'fraccionado' || p.fraccionable) {
      const q = prompt(`Ingrese cantidad para ${p.nombre} (ej: 0.5):`, "1");
      if (q === null) return;
      addQty = parseFloat(q);
      if (isNaN(addQty) || addQty <= 0) { toast('Cantidad inválida', 'err'); return; }
  } else if (p.tipo === 'renta') {
      const horas = prompt(`¿Cuántas horas de arriendo para ${p.nombre}?`, "1");
      if (horas === null) return;
      const parsedH = parseFloat(horas);
      if (isNaN(parsedH) || parsedH <= 0) { toast('Horas inválidas', 'err'); return; }
      
      const now = new Date();
      // Fix timezone offset for local ISO string
      const tzOffset = now.getTimezoneOffset() * 60000;
      const end = new Date(now.getTime() + parsedH * 3600000);
      extData.inicio_renta = (new Date(now - tzOffset)).toISOString().slice(0, 19).replace('T', ' ');
      extData.fin_renta = (new Date(end - tzOffset)).toISOString().slice(0, 19).replace('T', ' ');
      addQty = parsedH; 
  }

  const ex = cart.find(x => x.id === id);
  if (ex && p.tipo !== 'renta') {
     ex.qty += addQty;
  } else {
     // Para las rentas, permitimos agregar otro registro en el carrito (o usar el mismo si se desease).
     // Aqui lo forzamos como único mediante su timestamp interno si se repite o simplemente lo agregamos.
     cart.push({...p, qty: addQty, cart_index: Date.now(), ...extData});
  }
  
  renderCart();
  toast(`+ ${p.nombre.substring(0,32)}`);
  if (!isDesktop() && cart.length === 1) openDrawer();
}
function delItem(cartIndexOrId) { 
  // Usa cart_index si existe, sino id (retrocompat)
  cart = cart.filter(x => (x.cart_index ? x.cart_index !== cartIndexOrId : x.id !== cartIndexOrId)); 
  renderCart(); 
}
function chgQty(cartIndexOrId, d) {
  const i = cart.find(x => (x.cart_index ? x.cart_index === cartIndexOrId : x.id === cartIndexOrId));
  if (i) { 
     i.qty = Math.max(0.001, i.qty + d); 
     renderCart(); 
  }
}
function renderCart() {
  const el = document.getElementById('cartList');
  if (!cart.length) { el.innerHTML = `<div class="cart-empty">Añade productos</div>`; recalc(); updateStrip(); return; }
  
  el.innerHTML = cart.map(i => `
    <div class="ci">
      <div class="ci-name">${i.nombre}</div>
      <div class="ci-sub">${fmt((i.valor_venta ?? i.precio ?? 0) * i.qty)}</div>
      <div class="ci-ctrl">
        <button class="qbtn" onclick="chgQty(${i.cart_index||i.id},-1)">−</button>
        <span class="qnum">${Number.isInteger(i.qty) ? i.qty : i.qty.toFixed(2)}</span>
        <button class="qbtn" onclick="chgQty(${i.cart_index||i.id},1)">+</button>
        <button class="ci-del" onclick="delItem(${i.cart_index||i.id})">✕</button>
      </div>
      <div class="ci-unit">${fmt(i.valor_venta ?? i.precio ?? 0)} c/u</div>
    </div>`).join('');
  recalc(); updateStrip();
}

/* ── TOTALES Y MODAL ── */
function recalc() {
  const sub  = cart.reduce((s,i) => s + (i.valor_venta ?? i.precio ?? 0)*i.qty, 0);
  const dv   = parseFloat(document.getElementById('discVal').value) || 0;
  const dt   = document.getElementById('discType').value;
  const disc = dt==='pct' ? sub*(dv/100) : Math.min(dv,sub);
  const tot  = Math.max(0, sub-disc);
  totalActual = tot;

  document.getElementById('vSub').textContent  = fmt(sub);
  document.getElementById('vDisc').textContent = `-${fmt(disc)}`;
  document.getElementById('vTotal').textContent= fmt(tot);
  document.getElementById('rowDisc').style.display = disc>0 ? 'flex' : 'none';

  const btn = document.getElementById('btnCobrar');
  btn.disabled    = !cart.length;
  btn.textContent = cart.length ? `Cobrar ${fmt(tot)} →` : 'Cobrar →';
  return tot;
}
function updateStrip() {
  const qty = cart.reduce((s,i) => s+i.qty, 0);
  document.getElementById('sCount').textContent = qty;
  document.getElementById('sTotal').textContent = fmt(totalActual);
}

function selPay(el, type) {
  document.querySelectorAll('.pbtn').forEach(b => b.classList.remove('on'));
  el.classList.add('on');
  payMethod = type;
  document.getElementById('mAmount').value = '';
  document.getElementById('vueltoRow').style.display = 'none';
  document.getElementById('mAmountLbl').textContent = type === 'efectivo' ? 'Monto recibido' : 'Referencia opcional';
}

function abrirModal() {
  if (!cart.length) return;
  document.getElementById('mTotal').textContent = fmt(totalActual);
  document.getElementById('mAmount').value = '';
  document.getElementById('vueltoRow').style.display = 'none';
  document.getElementById('modalWrap').classList.add('show');
  setTimeout(() => document.getElementById('mAmount').focus(), 80);
}
function cerrarModal(e) {
  if (e && e.target !== document.getElementById('modalWrap')) return;
  document.getElementById('modalWrap').classList.remove('show');
}
function calcVuelto() {
  if (payMethod !== 'efectivo') return;
  const monto  = parseFloat(document.getElementById('mAmount').value) || 0;
  const vuelto = monto - totalActual;
  const row    = document.getElementById('vueltoRow');
  if (monto > 0) {
    row.style.display = 'flex';
    document.getElementById('vueltoVal').textContent = fmt(Math.max(0, vuelto));
    document.getElementById('vueltoVal').style.color = vuelto < 0 ? 'var(--err)' : 'var(--ac)';
  } else { row.style.display = 'none'; }
}

/* ── CONFIRMACIÓN BACKEND LARAVEL SANCTUM ── */
async function confirmarVenta() {
    if (cart.length === 0) return;
    const btn = document.getElementById('btnConfirmarVentaFinal');
    btn.disabled = true; btn.innerHTML = '<span class="spinner" style="display:inline-block;"></span>';

    const dv = parseFloat(document.getElementById('discVal').value) || 0;
    const dt = document.getElementById('discType').value;

    const payloadVenta = {
        cliente_id: clienteActual?.id ?? null,
    };

    try {
        // 1. Crear venta abierta
        const resVenta = await api('POST', '/api/ventas', payloadVenta);
        if (!resVenta.id) throw new Error("No se pudo crear la venta");

        // 2. Agregar items uno por uno (o en bloque si el backend lo soportara, pero usa /items)
        for (const i of cart) {
            const itemData = {
                producto_id: i.id,
                cantidad: i.qty,
                precio_unitario: i.valor_venta ?? i.precio ?? 0,
                notas_item: null
            };
            if (i.inicio_renta) itemData.inicio_renta = i.inicio_renta;
            if (i.fin_renta) itemData.fin_renta = i.fin_renta;

            await api('POST', `/api/ventas/${resVenta.id}/items`, itemData);
        }

        // 3. Confirmar (pagar)
        // Mapeo básico de tipo_pago_id (1=efectivo, 2=debito, 3=credito, 4=transferencia)
        // (Ajustar según DB, temporalmente enviamos null si no coinciden)
        const paymentMap = { 'efectivo': 1, 'debito': 2, 'credito': 3, 'transferencia': 4 };

        const confirmRes = await api('POST', `/api/ventas/${resVenta.id}/confirmar`, {
            tipo_pago_id: paymentMap[payMethod] || null,
            descuento_monto: dt === 'fix' ? dv : 0,
            descuento_pct: dt === 'pct' ? dv : 0,
            es_deuda: false, // Opcional, implementar si se necesita fiar
            notas: document.getElementById('notaVenta')?.value || null
        });

        cerrarModal();
        renderTicket(confirmRes);
        openModal('modalTicket');
        
        cart = [];
        document.getElementById('discVal').value = '';
        document.getElementById('notaVenta').value = '';
        renderCart();
        if (!isDesktop()) closeDrawer();
        
    } catch (e) {
        toast(e.message || 'Error registrando venta', 'err');
    } finally {
        btn.disabled = false; btn.textContent = 'Confirmar Venta →';
    }
}

/* ── CLIENTE EN VIVO (Buscador API Sanctum) ── */
let buscarTimer = null;
function fmtRut(inp) {
  let v = inp.value.replace(/[^0-9kK]/g,'');
  if (v.length > 1) inp.value = v.slice(0,-1).replace(/\B(?=(\d{3})+(?!\d))/g,'.') + '-' + v.slice(-1).toUpperCase();
}
function showRutMsg(txt, cls) {
  const msg = document.getElementById('rutMsg');
  msg.textContent = txt; msg.className = `rut-msg ${cls}`;
}
function limpiarCliente() {
  clienteActual = null;
  document.getElementById('clientChip').classList.remove('show');
  document.getElementById('rutInput').value = '';
  document.getElementById('rutMsg').className = 'rut-msg';
  document.getElementById('rutInput').classList.remove('err');
}
async function buscarCliente() {
  const inp = document.getElementById('rutInput');
  const btn = document.getElementById('rutBtn');
  const msg = document.getElementById('rutMsg');
  const rut = inp.value.trim();
  if(!rut) return;

  btn.textContent = '...'; btn.classList.add('loading');
  inp.classList.remove('err'); msg.className = 'rut-msg';
  
  try {
     const req = await api('GET', `/api/clientes?q=${encodeURIComponent(rut)}&per_page=1`);
     const res = req.data?.[0] || req[0];
     
     if (res) {
         clienteActual = res;
         inp.value = '';
         document.getElementById('chipRut').textContent = res.rut || 'Sin Rut';
         document.getElementById('chipNombre').textContent = res.nombre;
         document.getElementById('clientChip').classList.add('show');
         toast('✓ ' + res.nombre);
     } else {
         inp.classList.add('err');
         showRutMsg('No encontrado (Cliente Nuevo)', 'err');
     }
  } catch (e) {
     showRutMsg('Ocurrió un error en DB', 'err');
  } finally {
     btn.textContent = 'Buscar'; btn.classList.remove('loading');
  }
}

/* ── TICKET RENDER LÓGICA (Reaprovechada) ── */
function renderTicket(venta) {
    const emp = '{{ tenancy()->tenant->nombre ?? "BenderAnd" }}';
    const cajero = '{{ auth()->user()->nombre ?? "Cajero" }}';
    const fecha = new Date().toLocaleString('es-CL');
    const total = fmt(venta.total ?? 0);
    const cliente = clienteActual ? clienteActual.nombre : '--';
    const pago = (payMethod || 'efectivo').toUpperCase();
    
    let items = '';
    const itemsList = venta.items ?? cart;
    itemsList.forEach(i => {
        const nombre = (i.producto?.nombre ?? i.nombre ?? '').substring(0, 18).padEnd(18);
        const qty = parseFloat(i.cantidad ?? i.qty ?? 0);
        const precio = parseFloat(i.precio_unitario ?? i.valor_venta ?? i.precio ?? 0);
        const sub = qty * precio;
        
        items += `${nombre} ${qty.toFixed(1).padStart(4)} x ${String(Math.round(precio)).padStart(6)} = ${fmt(sub).padStart(8)}\n`;
    });
    const folio = venta.id ? String(venta.id).padStart(6, '0') : 'Borrador';
    
    document.getElementById('ticketPreview').innerHTML = `
<div style="text-align:center; font-weight:700; font-size:14px;">${emp}</div>
<div style="text-align:center; font-size:11px; color:#666;">${fecha}</div>
<div style="text-align:center; font-size:11px; color:#666;">Folio: #${folio}</div>
<div style="margin:10px 0; border-top:1px dashed #aaa;"></div>
<div style="font-size:11px; color:#333;">
  <div><b>Cliente:</b> ${cliente}</div>
  <div><b>Pago:</b> ${pago}</div>
  <div><b>Cajero:</b> ${cajero}</div>
</div>
<div style="margin:10px 0; border-top:1px dashed #aaa;"></div>
<pre style="font-family:inherit; white-space:pre-wrap; font-size:11px; margin:0;">${items}</pre>
<div style="margin:10px 0; border-top:1px dashed #aaa;"></div>
<div style="display:flex;justify-content:space-between;font-weight:700;font-size:14px;"><span>TOTAL</span><span>${total}</span></div>
<div style="text-align:center; font-size:10px; color:#888; margin-top:14px;">¡Gracias por su compra!</div>`;
}
function printTicket() {
    const contenido = document.getElementById('ticketPreview').innerHTML;
    const w = window.open('', '_blank', 'width=400,height=600');
    w.document.write(`<html><head><style>body{font-family:'IBM Plex Mono',monospace;font-size:12px;padding:20px;}</style></head><body>${contenido}</body></html>`);
    w.document.close();
    w.print();
}
function nuevaVenta() {
    closeModal('modalTicket');
    limpiarCliente();
    cart = []; renderCart();
}

/* --- UTILS --- */
function fmt(n) { return '$' + Math.round(n).toLocaleString('es-CL'); }
</script>
@endpush
@endsection
