@extends('layouts.central')

@section('title', 'Spider QA')
@section('page-title', 'Spider QA v5 — Tenant Selector')

@push('styles')
<style>
  :root {
    --r: 8px;
  }

  /* ── LAYOUT ── */
  .f {
    margin-bottom: 8px
  }

  .f label {
    font-family: var(--mono);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--t2);
    display: block;
    margin-bottom: 4px
  }

  .f input,
  .f select {
    width: 100%;
    background: var(--s2);
    border: 1px solid var(--b2);
    border-radius: var(--r);
    color: var(--tx);
    font-family: var(--mono);
    font-size: 11px;
    padding: 7px 10px;
    outline: none
  }

  .f input:focus,
  .f select:focus {
    border-color: rgba(224, 64, 251, .4)
  }

  .f select option {
    background: var(--s2);
    color: var(--tx)
  }

  /* ── TENANT CARD ── */
  .tenant-card {
    background: var(--s2);
    border: 1px solid var(--b2);
    border-radius: var(--r);
    padding: 10px;
    margin-top: 6px;
    display: none
  }

  .tenant-card.visible {
    display: block
  }

  .tc-name {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 4px
  }

  .tc-slug {
    font-family: var(--mono);
    font-size: 10px;
    color: var(--ac);
    margin-bottom: 6px
  }

  .tc-estado {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 9px;
    font-family: var(--mono);
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    margin-bottom: 6px
  }

  .tc-estado.activo {
    background: rgba(0, 229, 160, .12);
    color: var(--ok)
  }

  .tc-estado.trial {
    background: rgba(245, 197, 24, .12);
    color: var(--warn)
  }

  .tc-estado.suspendido {
    background: rgba(255, 63, 91, .12);
    color: var(--err)
  }

  .tc-modulos {
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
    margin-top: 4px
  }

  .tc-mod {
    font-family: var(--mono);
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 3px;
    background: rgba(224, 64, 251, .08);
    color: var(--ac)
  }

  .tc-mod.inactivo {
    background: rgba(136, 136, 160, .08);
    color: var(--t3)
  }

  .tc-rubro {
    font-size: 10px;
    color: var(--t2);
    margin-bottom: 4px
  }

  /* ── TOGGLE ── */
  .sl {
    font-family: var(--mono);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--t3);
    margin: 14px 0 6px 4px
  }

  .sl:first-child {
    margin-top: 0
  }

  .tog-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 0
  }

  .tog-row span {
    font-size: 11px;
    color: var(--t2)
  }

  .tog {
    position: relative;
    width: 32px;
    height: 16px;
    flex-shrink: 0
  }

  .tog input {
    opacity: 0;
    width: 0;
    height: 0
  }

  .tsl {
    position: absolute;
    inset: 0;
    background: var(--b2);
    border-radius: 8px;
    cursor: pointer;
    transition: .2s
  }

  .tsl::before {
    content: '';
    position: absolute;
    width: 10px;
    height: 10px;
    left: 3px;
    bottom: 3px;
    background: var(--t2);
    border-radius: 50%;
    transition: .2s
  }

  .tog input:checked+.tsl {
    background: rgba(224, 64, 251, .3)
  }

  .tog input:checked+.tsl::before {
    background: var(--ac);
    transform: translateX(16px)
  }

  /* ── SYNC BOX ── */
  .sync-box {
    background: var(--s2);
    border: 1px solid var(--b2);
    border-radius: var(--r);
    padding: 8px 10px;
    margin-top: 6px;
    font-size: 10px
  }

  .sync-lbl {
    font-family: var(--mono);
    font-size: 9px;
    color: var(--t2);
    margin-bottom: 6px
  }

  .sync-stats {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 4px
  }

  .sstat {
    font-family: var(--mono);
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 4px;
    background: rgba(224, 64, 251, .08);
    color: var(--ac)
  }

  /* ── BUTTONS ── */
  .btn-run {
    background: var(--ac);
    color: #000;
    width: 100%;
    justify-content: center;
    margin-top: 8px
  }

  .btn-sync {
    background: rgba(68, 136, 255, .12);
    color: var(--info);
    border: 1px solid rgba(68, 136, 255, .25);
    width: 100%;
    justify-content: center;
    margin-top: 6px;
    font-size: 11px;
    padding: 7px
  }

  .btn-stop {
    background: rgba(255, 63, 91, .15);
    color: var(--err);
    border: 1px solid rgba(255, 63, 91, .3);
    width: 100%;
    justify-content: center;
    margin-top: 6px;
    display: none
  }

  .btn-exp {
    background: rgba(224, 64, 251, .1);
    color: var(--ac);
    border: 1px solid rgba(224, 64, 251, .2);
    width: 100%;
    justify-content: center;
    margin-top: 6px;
    display: none
  }

  .btn-refresh-tenants {
    background: none;
    border: 1px solid var(--b2);
    border-radius: var(--r);
    color: var(--t2);
    font-family: var(--mono);
    font-size: 9px;
    padding: 3px 8px;
    cursor: pointer;
    margin-left: 4px;
    vertical-align: middle;
    transition: all .15s
  }

  .btn-refresh-tenants:hover {
    border-color: var(--ac);
    color: var(--ac)
  }

  /* ── PROGRESS ── */
  .pw {
    margin-top: 12px;
    display: none
  }

  .pl {
    font-family: var(--mono);
    font-size: 9px;
    color: var(--t2);
    margin-bottom: 4px;
    display: flex;
    justify-content: space-between
  }

  .pb {
    height: 4px;
    background: var(--b2);
    border-radius: 2px;
    overflow: hidden
  }

  .pf {
    height: 100%;
    background: var(--ac);
    border-radius: 2px;
    width: 0%;
    transition: width .3s
  }

  /* ── KPIS ── */
  .spider-kpis {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-top: 12px
  }

  .spider-kpi {
    background: var(--s2);
    border-radius: var(--r);
    padding: 8px 10px;
    text-align: center
  }

  .spider-kpi-v {
    font-family: var(--mono);
    font-size: 20px;
    font-weight: 700
  }

  .spider-kpi-l {
    font-size: 9px;
    color: var(--t2);
    margin-top: 2px;
    font-family: var(--mono);
    text-transform: uppercase
  }

  .kp .spider-kpi-v {
    color: var(--ok)
  }

  .kf .spider-kpi-v {
    color: var(--err)
  }

  .kw .spider-kpi-v {
    color: var(--warn)
  }

  .kb .spider-kpi-v {
    color: var(--info)
  }

  /* ── LAYOUT ── */
  .spider-layout {
    display: flex;
    gap: 20px;
    height: calc(100vh - 120px);
    overflow: hidden
  }

  .spider-sidebar {
    width: 280px;
    min-width: 280px;
    background: var(--s1);
    border: 1px solid var(--b1);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    flex-shrink: 0;
    padding: 14px 12px
  }

  .spider-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--s1);
    border: 1px solid var(--b1);
    border-radius: 12px
  }

  /* ── TABS ── */
  .tabs {
    display: flex;
    border-bottom: 1px solid var(--b1);
    flex-shrink: 0;
    background: var(--s1)
  }

  .tab {
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 600;
    color: var(--t2);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all .15s;
    font-family: var(--mono)
  }

  .tab:hover {
    color: var(--tx)
  }

  .tab.on {
    color: var(--ac);
    border-bottom-color: var(--ac)
  }

  /* ── PANES ── */
  .pane {
    display: none;
    flex: 1;
    overflow-y: auto;
    padding: 16px
  }

  .pane.on {
    display: block
  }

  /* ── RESULT ITEMS ── */
  .ri {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--r);
    margin-bottom: 4px;
    font-size: 11px;
    background: var(--s1);
    border: 1px solid var(--b1)
  }

  .ri.pass {
    border-left: 2px solid var(--ok)
  }

  .ri.fail {
    border-left: 2px solid var(--err);
    background: rgba(255, 63, 91, .04)
  }

  .ri.warn {
    border-left: 2px solid var(--warn)
  }

  .rist {
    font-family: var(--mono);
    font-size: 9px;
    font-weight: 700;
    min-width: 40px;
    text-align: center;
    padding: 2px 4px;
    border-radius: 4px;
    flex-shrink: 0;
    margin-top: 1px
  }

  .pass .rist {
    background: rgba(0, 229, 160, .1);
    color: var(--ok)
  }

  .fail .rist {
    background: rgba(255, 63, 91, .1);
    color: var(--err)
  }

  .warn .rist {
    background: rgba(245, 197, 24, .1);
    color: var(--warn)
  }

  .rib {
    flex: 1;
    min-width: 0
  }

  .rit {
    font-weight: 600;
    margin-bottom: 2px
  }

  .rid {
    color: var(--t2);
    font-family: var(--mono);
    font-size: 10px;
    word-break: break-all
  }

  .riu {
    color: var(--t3);
    font-size: 9px;
    font-family: var(--mono)
  }

  .rif {
    margin-top: 4px;
    padding: 4px 8px;
    background: rgba(245, 197, 24, .07);
    border: 1px solid rgba(245, 197, 24, .12);
    border-radius: 5px;
    color: var(--warn);
    font-size: 10px;
    font-family: var(--mono)
  }

  .ri-src {
    font-size: 9px;
    color: var(--info);
    font-family: var(--mono);
    margin-top: 2px
  }

  /* ── BUG CARDS ── */
  .bc {
    background: var(--s1);
    border: 1px solid var(--b1);
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 8px
  }

  .bh {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    flex-wrap: wrap
  }

  .bid {
    font-family: var(--mono);
    font-size: 10px;
    font-weight: 700;
    color: var(--t2)
  }

  .tg {
    font-size: 9px;
    font-family: var(--mono);
    padding: 2px 7px;
    border-radius: 4px;
    font-weight: 700
  }

  .ta {
    background: rgba(255, 63, 91, .1);
    color: var(--err)
  }

  .th {
    background: rgba(68, 136, 255, .1);
    color: var(--info)
  }

  .td {
    background: rgba(224, 64, 251, .1);
    color: var(--ac)
  }

  .tu {
    background: rgba(245, 197, 24, .1);
    color: var(--warn)
  }

  .tc {
    background: rgba(0, 229, 160, .1);
    color: var(--ok)
  }

  .tpc {
    background: rgba(255, 63, 91, .15);
    color: var(--err)
  }

  .tpa {
    background: rgba(245, 197, 24, .1);
    color: var(--warn)
  }

  .tpm {
    background: rgba(68, 136, 255, .1);
    color: var(--info)
  }

  .bd {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 4px
  }

  .bdet {
    font-size: 10px;
    color: var(--t2);
    font-family: var(--mono);
    margin-bottom: 6px
  }

  .bfix {
    padding: 6px 8px;
    background: var(--s2);
    border-radius: 6px;
    font-size: 10px;
    font-family: var(--mono);
    color: var(--ok)
  }

  /* ── MD ── */
  .mdb {
    background: var(--s2);
    border: 1px solid var(--b2);
    border-radius: var(--r);
    padding: 14px;
    font-family: var(--mono);
    font-size: 10px;
    color: var(--t2);
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 58vh;
    overflow-y: auto;
    line-height: 1.6
  }

  .brow {
    display: flex;
    gap: 8px;
    margin-bottom: 10px
  }

  .bcp {
    background: rgba(0, 229, 160, .1);
    color: var(--ok);
    border: 1px solid rgba(0, 229, 160, .2)
  }

  .bdl {
    background: rgba(68, 136, 255, .1);
    color: var(--info);
    border: 1px solid rgba(68, 136, 255, .2)
  }

  /* ── FILTERS ── */
  .spider-fr {
    display: flex;
    gap: 6px;
    margin-bottom: 10px;
    flex-wrap: wrap
  }

  .spider-fb {
    font-size: 10px;
    font-family: var(--mono);
    padding: 3px 9px;
    border-radius: 4px;
    border: 1px solid var(--b2);
    background: none;
    color: var(--t2);
    cursor: pointer
  }

  .spider-fb.on {
    border-color: var(--ac);
    color: var(--ac);
    background: rgba(224, 64, 251, .07)
  }

  /* ── TESTS EDITOR ── */
  .tests-editor {
    background: var(--s2);
    border: 1px solid var(--b2);
    border-radius: var(--r);
    padding: 12px;
    font-family: var(--mono);
    font-size: 10px;
    color: var(--tx);
    width: 100%;
    min-height: 300px;
    resize: vertical;
    outline: none;
    line-height: 1.6
  }

  .tests-editor:focus {
    border-color: rgba(224, 64, 251, .4)
  }

  /* ── EMPTY STATES ── */
  .empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--t3)
  }

  .ei {
    font-size: 28px;
    margin-bottom: 12px;
    opacity: .4
  }

  .et {
    font-size: 14px;
    font-weight: 600;
    color: var(--t2);
    margin-bottom: 6px
  }

  .ll {
    font-family: var(--mono);
    font-size: 10px;
    color: var(--t3);
    padding: 1px 0;
    border-bottom: 1px solid rgba(255, 255, 255, .02)
  }

  .ll.ok {
    color: var(--ok)
  }

  .ll.err {
    color: var(--err)
  }

  .ll.w {
    color: var(--warn)
  }

  .ll.inf {
    color: var(--ac)
  }

  /* ── MÓDULOS ACTIVOS BADGE ── */
  .mod-summary {
    font-size: 9px;
    font-family: var(--mono);
    color: var(--t2);
    padding: 5px 8px;
    background: var(--s2);
    border-radius: 5px;
    margin-top: 6px;
    line-height: 1.7
  }

  .mod-summary strong {
    color: var(--ac)
  }

  /* ── TENANT SELECTOR HEADER ── */
  .tenant-selector-hdr {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px
  }

  .tenant-selector-hdr label {
    font-family: var(--mono);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--t2)
  }

  .badge-all {
    font-size: 8px;
    font-family: var(--mono);
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 3px;
    background: rgba(224, 64, 251, .15);
    color: var(--ac)
  }
</style>
@endpush

@section('content')
<div class="page-hdr">
  <div class="page-title">Spider QA v5</div>
  <div class="tb-badge">H25 · Tenant Selector</div>
</div>

<div class="spider-layout">
  <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
  <aside class="spider-sidebar">

    <div class="sl">SuperAdmin</div>
    <div class="f">
      <label>URL</label>
      <input id="u-super" value="{{ url('/') }}">
    </div>
    <div class="f">
      <label>SA Password</label>
      <input type="password" id="sa-pass" value="password" autocomplete="new-password">
    </div>

    {{-- ── TENANT SELECTOR ── --}}
    <div class="sl">
      <div class="tenant-selector-hdr">
        <label>Tenant objetivo</label>
        <button class="btn-refresh-tenants" onclick="loadTenants()" title="Recargar desde DB">⟳</button>
      </div>
    </div>

    <div class="f">
      <select id="tenant-select" onchange="onTenantChange(this.value)">
        <option value="">⟳ Cargando tenants...</option>
      </select>
    </div>

    {{-- Card con info del tenant seleccionado --}}
    <div class="tenant-card" id="tenant-card">
      <div class="tc-name" id="tc-name">—</div>
      <div class="tc-slug" id="tc-slug">—</div>
      <div class="tc-rubro" id="tc-rubro">—</div>
      <span class="tc-estado" id="tc-estado">—</span>
      <div class="mod-summary" id="tc-mod-summary">Sin módulos cargados</div>
    </div>

    {{-- Credenciales del tenant (ocultas, se llenan automáticamente) --}}
    <div class="f" style="margin-top:6px">
      <label>Tenant Email</label>
      <input id="t-email" value="admin@demo-ferreteria.cl" autocomplete="off">
    </div>
    <div class="f">
      <label>Tenant Password</label>
      <input type="password" id="t-pass" value="demo1234" autocomplete="new-password">
    </div>

    <div class="sl">Tests cargados</div>
    <div class="sync-box">
      <div class="sync-lbl" id="sync-status">Sin sincronizar</div>
      <div class="sync-stats" id="sync-stats"></div>
    </div>
    <button class="btn btn-sync" id="btn-sync" onclick="syncTests()">⟳ Sync desde Laravel</button>

    <div class="sl">Fases activas</div>
    <div class="tog-row"><span>Autenticación</span><label class="tog"><input type="checkbox" id="c-auth" checked><span
          class="tsl"></span></label></div>
    <div class="tog-row"><span>API Endpoints</span><label class="tog"><input type="checkbox" id="c-api" checked><span
          class="tsl"></span></label></div>
    <div class="tog-row"><span>Permisos / Roles</span><label class="tog"><input type="checkbox" id="c-roles"
          checked><span class="tsl"></span></label></div>
    <div class="tog-row"><span>Base de datos</span><label class="tog"><input type="checkbox" id="c-db" checked><span
          class="tsl"></span></label></div>
    <div class="tog-row"><span>Setup Tenant</span><label class="tog"><input type="checkbox" id="c-tenant" checked><span
          class="tsl"></span></label></div>
    <div class="tog-row"><span>UI / Frontend</span><label class="tog"><input type="checkbox" id="c-ui" checked><span
          class="tsl"></span></label></div>
    <div class="tog-row"><span>Tests del JSON</span><label class="tog"><input type="checkbox" id="c-json" checked><span
          class="tsl"></span></label></div>
    <div class="tog-row">
      <span>Solo módulos activos</span>
      <label class="tog"><input type="checkbox" id="c-only-active" checked><span class="tsl"></span></label>
    </div>

    <button class="btn btn-run" id="btn-run" onclick="startCrawl()">▶ Iniciar Spider</button>
    <button class="btn btn-stop" id="btn-stop" onclick="stopCrawl()">■ Detener</button>
    <button class="btn btn-exp" id="btn-exp" onclick="st('md')">⬇ Export MD</button>

    <div class="pw" id="pw">
      <div class="pl"><span id="pl">Listo</span><span id="pp">0%</span></div>
      <div class="pb">
        <div class="pf" id="pf"></div>
      </div>
    </div>

    <div class="spider-kpis">
      <div class="spider-kpi kp">
        <div class="spider-kpi-v" id="kp">0</div>
        <div class="spider-kpi-l">PASS</div>
      </div>
      <div class="spider-kpi kf">
        <div class="spider-kpi-v" id="kf">0</div>
        <div class="spider-kpi-l">FAIL</div>
      </div>
      <div class="spider-kpi kw">
        <div class="spider-kpi-v" id="kw">0</div>
        <div class="spider-kpi-l">WARN</div>
      </div>
      <div class="spider-kpi kb">
        <div class="spider-kpi-v" id="kb">0</div>
        <div class="spider-kpi-l">BUGS</div>
      </div>
    </div>
  </aside>

  <!-- ═══════════════════ MAIN ═══════════════════ -->
  <div class="spider-main">
    <div class="tabs">
      <div class="tab on" id="tab-r" onclick="st('r')">Resultados</div>
      <div class="tab" id="tab-b" onclick="st('b')">Bugs <span id="bct"></span></div>
      <div class="tab" id="tab-l" onclick="st('l')">Log</div>
      <div class="tab" id="tab-tests" onclick="st('tests')">Tests JSON <span id="json-ct"
          style="display:none; margin-left:6px; font-family:var(--mono); font-size:10px; color:var(--info)"></span>
      </div>
      <div class="tab" id="tab-md" onclick="st('md')">Export MD</div>
    </div>

    <div class="pane on" id="pane-r">
      <div class="empty" id="er">
        <div class="ei">◈</div>
        <div class="et">Spider v5 en espera</div>
        <div>Selecciona un tenant → Sync → Ejecuta</div>
      </div>
      <div class="spider-fr" id="fr" style="display:none">
        <button class="spider-fb on" onclick="filt(this,'all')">Todo</button>
        <button class="spider-fb" onclick="filt(this,'fail')">FAIL</button>
        <button class="spider-fb" onclick="filt(this,'pass')">PASS</button>
        <button class="spider-fb" onclick="filt(this,'warn')">WARN</button>
      </div>
      <div id="rl"></div>
    </div>

    <div class="pane" id="pane-b">
      <div class="empty" id="eb">
        <div class="ei">✓</div>
        <div class="et">Sin bugs</div>
      </div>
      <div id="bl"></div>
    </div>

    <div class="pane" id="pane-l">
      <div id="ll"></div>
    </div>

    <div class="pane" id="pane-tests">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <div style="flex:1;font-size:12px;color:var(--t2)">
          Editor de <code style="color:var(--ac)">spider_tests.json</code>
        </div>
        <button class="btn btn-primary" onclick="saveTests()">Guardar</button>
        <button class="btn" style="background:var(--s2);border:1px solid var(--b2);color:var(--tx)"
          onclick="syncTests()">⟳ Re-sync</button>
      </div>
      <textarea class="tests-editor" id="tests-editor" placeholder="Haz sync primero..."></textarea>
    </div>

    <div class="pane" id="pane-md">
      <div class="empty" id="emd">
        <div class="ei">⬇</div>
        <div class="et">Sin resultados</div>
      </div>
      <div id="mds" style="display:none">
        <div class="brow">
          <button class="btn bcp" onclick="cpMd()">Copiar MD</button>
          <button class="btn bdl" onclick="dlMd()">Descargar .md</button>
        </div>
        <div class="mdb" id="mdo"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // ══════════════════════════════════════════════════════════
  // STATE
  // ══════════════════════════════════════════════════════════
  const S = {
    run: false, stop: false,
    pass: 0, fail: 0, warn: 0,
    bugs: [], res: [], logs: [],
    saT: null, tT: null, bseq: 1,
    tests: null,
    // tenant actual seleccionado
    tenant: null   // { slug, nombre, estado, rubro, modulos_activos: [], url, email, password }
  }

  const g = id => document.getElementById(id)
  const cfg = id => g(id)?.value?.trim() || ''
  const chk = id => g(id)?.checked
  const sleep = ms => new Promise(r => setTimeout(r, ms))

  // ══════════════════════════════════════════════════════════
  // UI HELPERS
  // ══════════════════════════════════════════════════════════
  function st(t) {
    document.querySelectorAll('.pane').forEach(p => p.classList.remove('on'))
    document.querySelectorAll('.tab').forEach(tb => tb.classList.remove('on'))
    g('pane-' + t).classList.add('on')
    g('tab-' + t).classList.add('on')
  }

  function log(msg, type = '') {
    const el = g('ll'), d = document.createElement('div')
    d.className = 'll' + (type ? ' ' + type : '')
    d.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg
    el.appendChild(d); el.scrollTop = el.scrollHeight
    S.logs.push({ ts: new Date().toISOString(), msg, type })
  }

  function setP(lbl, pct) {
    g('pl').textContent = lbl
    g('pp').textContent = Math.round(pct) + '%'
    g('pf').style.width = pct + '%'
  }

  function updK() {
    g('kp').textContent = S.pass
    g('kf').textContent = S.fail
    g('kw').textContent = S.warn
    g('kb').textContent = S.bugs.length
    const bc = g('bct')
    bc.textContent = S.bugs.length > 0 ? ' (' + S.bugs.length + ')' : ''
    bc.style.color = S.bugs.length > 0 ? 'var(--err)' : ''
  }

  function addR(status, title, detail, url, fix, src = '') {
    S.res.push({ status, title, detail, url, fix, src, ts: new Date().toISOString() })
    if (status === 'pass') S.pass++
    else if (status === 'fail') S.fail++
    else if (status === 'warn') S.warn++
    updK()
    g('er').style.display = 'none'
    g('fr').style.display = 'flex'
    const el = document.createElement('div')
    el.className = 'ri ' + status
    el.dataset.s = status
    el.innerHTML = `<div class="rist">${status.toUpperCase()}</div><div class="rib">
      <div class="rit">${title}</div>
      ${detail ? '<div class="rid">' + detail + '</div>' : ''}
      ${url ? '<div class="riu">' + url + '</div>' : ''}
      ${fix ? '<div class="rif">→ ' + fix + '</div>' : ''}
      ${src ? '<div class="ri-src">src: ' + src + '</div>' : ''}
    </div>`
    g('rl').appendChild(el)
    el.scrollIntoView({ block: 'nearest' })
  }

  function addBug(tipo, capa, prio, desc, detail, fix, url) {
    const id = 'BUG-SP-' + String(S.bseq++).padStart(3, '0')
    S.bugs.push({ id, tipo, capa, prio, desc, detail, fix, url, ts: new Date().toISOString() })
    updK()
    g('eb').style.display = 'none'
    const tc = { 'E-AUTH': 'ta', 'E-HTTP': 'th', 'E-DATA': 'td', 'E-UI': 'tu', 'E-CONFIG': 'tc', 'E-PERM': 'th', 'E-REDIRECT': 'tc' }
    const pc = { critico: 'tpc', alto: 'tpa', medio: 'tpm' }
    const el = document.createElement('div')
    el.className = 'bc'
    el.innerHTML = `<div class="bh">
      <span class="bid">${id}</span>
      <span class="tg ${tc[tipo] || 'th'}">${tipo}</span>
      <span class="tg" style="background:rgba(136,136,160,.1);color:var(--t2)">${capa}</span>
      <span class="tg ${pc[prio] || 'tpm'}">${prio}</span>
    </div>
    <div class="bd">${desc}</div>
    ${detail ? '<div class="bdet">' + detail + '</div>' : ''}
    ${fix ? '<div class="bfix">FIX → ' + fix + '</div>' : ''}`
    g('bl').appendChild(el)
  }

  function filt(btn, type) {
    document.querySelectorAll('.spider-fb').forEach(b => b.classList.remove('on'))
    btn.classList.add('on')
    document.querySelectorAll('.ri').forEach(el => {
      el.style.display = (type === 'all' || el.dataset.s === type) ? '' : 'none'
    })
  }

  // ══════════════════════════════════════════════════════════
  // TENANT LOADER — carga tenants desde API Laravel
  // ══════════════════════════════════════════════════════════
  async function getSaToken() {
    if (S.saT) return S.saT
    try {
      const r = await fetch('{{ route('central.spider.token') }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json'
        }
      })
      const d = await r.json()
      if (d.token) S.saT = d.token
    } catch (e) { }
    return S.saT
  }

  async function loadTenants() {
    const sel = g('tenant-select')
    sel.innerHTML = '<option value="">⟳ Cargando...</option>'
    g('tenant-card').classList.remove('visible')

    await getSaToken()

    try {
      const SU = cfg('u-super')
      // Intentar endpoint de tenants del superadmin
      const r = await fetch(SU + '/api/superadmin/tenants?per_page=50', {
        headers: { 'Authorization': 'Bearer ' + S.saT, 'Accept': 'application/json' }
      })

      if (!r.ok) throw new Error('HTTP ' + r.status)
      const d = await r.json()

      // Compatibilidad: puede venir como array o como { data: [...] }
      const list = Array.isArray(d) ? d : (d.data || d.tenants || [])

      sel.innerHTML = '<option value="">— Seleccionar tenant —</option>'

      // Opción especial "demo-all" al principio si existe
      const allTenant = list.find(t => t.id === 'demo-all' || t.slug === 'demo-all')
      if (allTenant) {
        const opt = document.createElement('option')
        opt.value = JSON.stringify(buildTenantObj(allTenant))
        opt.textContent = '★ demo-all (TODOS los módulos)'
        opt.style.color = 'var(--ac)'
        sel.appendChild(opt)
      }

      // Resto de tenants
      list.forEach(t => {
        if (t.id === 'demo-all' || t.slug === 'demo-all') return // ya está arriba
        const opt = document.createElement('option')
        opt.value = JSON.stringify(buildTenantObj(t))
        const slug = t.id || t.slug || '?'
        const estado = t.estado || t.status || 'activo'
        const ic = estado === 'activo' ? '●' : estado === 'trial' ? '◑' : '○'
        opt.textContent = ic + ' ' + (t.nombre || t.name || slug) + ' · ' + slug
        sel.appendChild(opt)
      })

      log('Tenants cargados: ' + list.length, 'ok')

      // Auto-seleccionar demo-all si existe, si no demo-ferreteria
      const preferido = list.find(t => t.id === 'demo-all') ||
        list.find(t => t.id === 'demo-ferreteria') ||
        list[0]
      if (preferido) {
        const targetVal = JSON.stringify(buildTenantObj(preferido))
        // Encontrar la opción con ese value
        for (const opt of sel.options) {
          if (opt.value === targetVal) { sel.value = targetVal; break }
        }
        if (sel.value) onTenantChange(sel.value)
      }

    } catch (e) {
      log('Error cargando tenants: ' + e.message + ' — usando hardcoded', 'w')
      sel.innerHTML = buildHardcodedOptions()
      // Auto-seleccionar demo-ferreteria
      sel.value = sel.options[1]?.value || ''
      if (sel.value) onTenantChange(sel.value)
    }
  }

  // Construye el objeto tenant normalizado desde la respuesta de la API
  function buildTenantObj(t) {
    const slug = t.id || t.slug || t.tenant_id || 'demo'
    const nombre = t.nombre || t.name || slug
    const estado = t.estado || t.status || 'activo'
    const rubro = t.rubro_config?.industria_preset || t.industria || t.rubro || '—'
    // modulos_activos puede venir desde rubro_config.modulos_activos o como campo directo
    const modulos = t.rubro_config?.modulos_activos ||
      t.modulos_activos ||
      t.active_modules || []

    return {
      slug,
      nombre,
      estado,
      rubro,
      modulos_activos: Array.isArray(modulos) ? modulos : [],
      url: 'http://' + slug + '.localhost:8000',
      email: 'admin@' + slug + '.cl',
      password: 'demo1234'
    }
  }

  // Fallback hardcoded si la API falla
  function buildHardcodedOptions() {
    const tenants = [
      { slug: 'demo-all', nombre: '★ demo-all (TODOS)', estado: 'activo', rubro: 'completo', modulos: ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10', 'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18', 'M19', 'M20', 'M21', 'M22', 'M23', 'M24', 'M25', 'M26', 'M27', 'M28', 'M29', 'M30', 'M31', 'M32'] },
      { slug: 'demo-ferreteria', nombre: 'Ferretería Demo', estado: 'activo', rubro: 'ferreteria', modulos: ['M01', 'M02', 'M03', 'M04', 'M07', 'M11', 'M17', 'M18', 'M19', 'M20', 'M24', 'M26', 'M32'] },
      { slug: 'demo-legal', nombre: 'Legal Demo', estado: 'activo', rubro: 'legal', modulos: ['M01', 'M07', 'M08', 'M09', 'M10', 'M20', 'M21', 'M32'] },
      { slug: 'demo-padel', nombre: 'Pádel Demo', estado: 'activo', rubro: 'padel', modulos: ['M01', 'M03', 'M05', 'M06', 'M08', 'M17', 'M30', 'M32'] },
      { slug: 'demo-motel', nombre: 'Motel Demo', estado: 'activo', rubro: 'motel', modulos: ['M01', 'M03', 'M05', 'M06', 'M14'] },
      { slug: 'demo-abarrotes', nombre: 'Abarrotes Demo', estado: 'activo', rubro: 'abarrotes', modulos: ['M01', 'M02', 'M03', 'M04', 'M11', 'M12', 'M17', 'M18', 'M20', 'M24', 'M25', 'M32'] },
      { slug: 'demo-medico', nombre: 'Médico Demo', estado: 'activo', rubro: 'medico', modulos: ['M01', 'M07', 'M08', 'M09', 'M10', 'M20', 'M21', 'M32'] },
      { slug: 'demo-saas', nombre: 'SaaS Demo', estado: 'activo', rubro: 'saas', modulos: ['M01', 'M07', 'M20', 'M21', 'M22', 'M23', 'M24', 'M25', 'M27', 'M31', 'M32'] },
    ]
    return '<option value="">— Seleccionar tenant —</option>' +
      tenants.map(t => {
        const obj = { slug: t.slug, nombre: t.nombre, estado: t.estado, rubro: t.rubro, modulos_activos: t.modulos, url: 'http://' + t.slug + '.localhost:8000', email: 'admin@' + t.slug + '.cl', password: 'demo1234' }
        return `<option value='${JSON.stringify(obj)}'>${t.nombre} · ${t.slug}</option>`
      }).join('')
  }

  // Cuando cambia el select
  function onTenantChange(val) {
    if (!val) { g('tenant-card').classList.remove('visible'); S.tenant = null; return }
    try {
      const t = JSON.parse(val)
      S.tenant = t

      // Llenar inputs
      g('u-tenant').value = t.url
      g('t-email').value = t.email
      g('t-pass').value = t.password

      // Llenar card
      g('tc-name').textContent = t.nombre
      g('tc-slug').textContent = t.url
      g('tc-rubro').textContent = 'Rubro: ' + t.rubro

      const estEl = g('tc-estado')
      estEl.textContent = t.estado.toUpperCase()
      estEl.className = 'tc-estado ' + t.estado

      // Módulos
      renderModSummary(t.modulos_activos)

      g('tenant-card').classList.add('visible')
      log('Tenant seleccionado: ' + t.slug + ' · ' + t.modulos_activos.length + ' módulos', 'inf')
    } catch (e) {
      log('Error parseando tenant: ' + e.message, 'err')
    }
  }

  // Mapa módulo → path de API para filtrar tests por módulos activos
  const MOD_PATH_MAP = {
    M05: 'rentas', M06: 'rentas', M08: 'agenda', M09: 'honorarios', M10: 'notas',
    M11: 'deudas', M12: 'encargos', M13: 'delivery', M14: 'rentas', M15: 'comandas',
    M16: 'recetas', M17: 'bot', M18: 'compras', M19: 'inventario', M20: 'sii',
    M21: 'rrhh', M22: 'liquidaciones', M23: 'reclutamiento', M24: 'marketing',
    M25: 'portal', M26: 'descuentos', M27: 'sucursales', M28: 'ot', M29: 'historial',
    M30: 'membresias', M31: 'saas', M32: 'crm'
  }

  function renderModSummary(mods) {
    const el = g('tc-mod-summary')
    if (!mods || mods.length === 0) { el.textContent = 'Sin módulos cargados'; return }
    const ALL_MODS = ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10',
      'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18', 'M19', 'M20',
      'M21', 'M22', 'M23', 'M24', 'M25', 'M26', 'M27', 'M28', 'M29', 'M30', 'M31', 'M32']
    el.innerHTML = '<strong>' + mods.length + '/' + ALL_MODS.length + ' módulos</strong>  ' +
      ALL_MODS.map(m => {
        const active = mods.includes(m)
        return `<span style="font-family:var(--mono);font-size:9px;padding:1px 4px;border-radius:3px;margin:1px;display:inline-block;${active ? 'background:rgba(224,64,251,.1);color:var(--ac)' : 'color:var(--t3)'}">${m}</span>`
      }).join('')
  }

  // Filtra tests del JSON según módulos activos del tenant
  function filterTestsByModules(tests) {
    if (!chk('c-only-active') || !S.tenant) return tests
    const mods = S.tenant.modulos_activos
    if (!mods || mods.length === 0) return tests
    return tests.filter(t => {
      if (!t.modulo) return true  // sin módulo → siempre ejecutar
      return mods.includes(t.modulo)
    })
  }

  // ══════════════════════════════════════════════════════════
  // SYNC
  // ══════════════════════════════════════════════════════════
  async function syncTests() {
    const SU = cfg('u-super')
    g('btn-sync').disabled = true
    g('btn-sync').textContent = '⟳ Sincronizando...'
    g('sync-status').textContent = 'Sincronizando con Laravel...'
    log('Sync tests desde /api/spider/sync', 'inf')

    await getSaToken()

    try {
      const r = await fetch(SU + '/api/spider/sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json', 'Accept': 'application/json',
          ...(S.saT ? { 'Authorization': 'Bearer ' + S.saT } : {})
        }
      })
      if (!r.ok) throw new Error('HTTP ' + r.status)
      const d = await r.json()
      S.tests = d.tests

      const total = d.after_total || 0
      const added = d.new_tests || 0
      g('sync-status').textContent = `${total} tests · ${new Date().toLocaleTimeString()}`

      const statsEl = g('sync-stats')
      statsEl.innerHTML = ''
      for (const [sec, tests] of Object.entries(d.tests || {})) {
        if (sec === '_meta' || !Array.isArray(tests) || tests.length === 0) continue
        const active = tests.filter(t => t.activo !== false).length
        const pill = document.createElement('span')
        pill.className = 'sstat'
        pill.textContent = sec.replace('_checks', '').replace('api_', '').replace('_', ' ') + ': ' + active
        statsEl.appendChild(pill)
      }
      if (added > 0) { g('json-ct').textContent = '+' + added; g('json-ct').style.display = '' }
      g('tests-editor').value = JSON.stringify(d.tests, null, 2)
      log('Sync OK: ' + total + ' tests', 'ok')
    } catch (e) {
      g('sync-status').textContent = 'Error: ' + e.message
      log('Sync error: ' + e.message, 'err')
    }

    g('btn-sync').disabled = false
    g('btn-sync').textContent = '⟳ Sync desde Laravel'
  }

  async function saveTests() {
    const SU = cfg('u-super')
    try {
      const tests = JSON.parse(g('tests-editor').value)
      const r = await fetch(SU + '/api/spider/tests', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json', 'Accept': 'application/json',
          ...(S.saT ? { 'Authorization': 'Bearer ' + S.saT } : {})
        },
        body: JSON.stringify({ tests })
      })
      const d = await r.json()
      if (d.ok) { S.tests = tests; log('Tests guardados: ' + d.total, 'ok') }
      else log('Error guardando: ' + JSON.stringify(d), 'err')
    } catch (e) { log('JSON inválido: ' + e.message, 'err') }
  }

  // ══════════════════════════════════════════════════════════
  // FETCH HELPERS
  // ══════════════════════════════════════════════════════════
  async function checkUrl(url) {
    try {
      const ctrl = new AbortController()
      const tm = setTimeout(() => ctrl.abort(), 4000)
      const r = await fetch(url, { method: 'GET', redirect: 'follow', signal: ctrl.signal })
      clearTimeout(tm); return r.status
    } catch (e) { return 0 }
  }

  async function checkUrlWithMethod(url, method = 'GET') {
    try {
      const ctrl = new AbortController()
      const tm = setTimeout(() => ctrl.abort(), 4000)
      const opts = { method: method.toUpperCase(), redirect: 'follow', signal: ctrl.signal, headers: { 'Accept': 'text/html' } }
      if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(opts.method)) {
        opts.headers['Content-Type'] = 'application/json'; opts.body = '{}'
      }
      const r = await fetch(url, opts); clearTimeout(tm); return r.status
    } catch (e) { return 0 }
  }

  async function apiPost(url, payload) {
    try {
      const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
      const d = await r.json(); return { code: r.status, data: d }
    } catch (e) { return { code: 0, data: null, err: e.message } }
  }

  async function apiFetch(url, token, method = 'GET') {
    try {
      const h = { 'Accept': 'application/json' }
      if (token) h['Authorization'] = 'Bearer ' + token
      const r = await fetch(url, { method, headers: h }); return { code: r.status }
    } catch (e) { return { code: 0, err: e.message } }
  }

  function codeMatch(actual, expected) {
    if (actual === null || actual === undefined) return false
    return String(expected).split('|').some(e => String(actual) === e)
  }

  // ══════════════════════════════════════════════════════════
  // FASES
  // ══════════════════════════════════════════════════════════
  async function phaseAuth() {
    if (!chk('c-auth')) return
    log('── AUTH ──', 'inf'); setP('Autenticación...', 5)
    const SU = cfg('u-super')
    const TU = S.tenant?.url || cfg('u-tenant')

    // SA token desde sesión
    if (!S.saT) {
      try {
        const r = await fetch('{{ route('central.spider.token') }}', {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        })
        const d = await r.json()
        if (d.token) { S.saT = d.token; addR('pass', 'SA token OK (sesión activa)', '', '', '', 'auth') }
        else addR('fail', 'SA token falla', JSON.stringify(d), '', '', 'auth')
      } catch (e) { addR('fail', 'SA token error', e.message, '', '', 'auth') }
    } else {
      addR('pass', 'SA token ya disponible', '', '', '', 'auth')
    }

    if (S.stop) return

    // Tenant login
    const email = cfg('t-email') || (S.tenant?.email || 'admin@demo-ferreteria.cl')
    const pass = cfg('t-pass') || (S.tenant?.password || 'demo1234')
    const t = await apiPost(TU + '/api/login', { email, password: pass })

    if (t.code && t.code < 400 && t.data?.token) {
      S.tT = t.data.token
      addR('pass', 'Tenant login OK — ' + (S.tenant?.slug || '?'), 'HTTP ' + t.code, TU + '/api/login', '', 'auth')
    } else {
      addR('fail', 'Tenant login falla', 'HTTP ' + t.code + ' ' + JSON.stringify(t.data || {}).substring(0, 80), TU + '/api/login', 'Verificar credenciales', 'auth')
      addBug('E-AUTH', 'db', 'critico', 'Tenant login falla', 'HTTP ' + t.code, 'php artisan db:seed --class=TenantDemoDataSeeder', TU + '/api/login')
    }
  }

  async function phaseRoles() {
    if (!chk('c-roles')) return
    log('── ROLES ──', 'inf'); setP('Permisos...', 45)
    const SU = cfg('u-super')
    const TU = S.tenant?.url || cfg('u-tenant')

    const noAuth = [
      [TU + '/api/dashboard', 'Tenant /api/dashboard'],
      [SU + '/api/superadmin/dashboard', 'SA /api/superadmin/dashboard']
    ]
    for (const [url, lbl] of noAuth) {
      if (S.stop) return
      const r = await apiFetch(url, null)
      if (r.code === 401 || r.code === 403)
        addR('pass', 'Sin token: ' + lbl + ' protegido', 'HTTP ' + r.code, url, '', 'roles')
      else {
        addR('fail', 'Sin token: ' + lbl + ' NO protegido', 'HTTP ' + r.code, url, 'middleware auth:sanctum faltante', 'roles')
        addBug('E-PERM', 'laravel', 'critico', 'Ruta sin auth: ' + url, 'HTTP ' + r.code, '->middleware("auth:sanctum")', url)
      }
    }
    if (S.tT) {
      const r = await apiFetch(SU + '/api/superadmin/dashboard', S.tT)
      if (r.code === 401 || r.code === 403)
        addR('pass', 'Cross-tenant bloqueado', 'HTTP ' + r.code, '', '', 'roles')
      else {
        addR('fail', 'Cross-tenant NO bloqueado', 'HTTP ' + r.code, SU + '/api/superadmin/dashboard', 'Verificar CentralAuthController', 'roles')
        addBug('E-PERM', 'laravel', 'critico', 'Token tenant accede a SA', 'HTTP ' + r.code, 'CentralAuthController guard', SU + '/api/superadmin/dashboard')
      }
    }
  }

  async function phaseDB() {
    if (!chk('c-db')) return
    log('── DB ──', 'inf'); setP('DB...', 60)
    const SU = cfg('u-super')
    if (!S.saT) { addR('warn', 'DB: sin token SA', 'Skip'); return }
    try {
      const r = await fetch(SU + '/api/spider/db-check', {
        headers: { 'Authorization': 'Bearer ' + S.saT, 'Accept': 'application/json' }
      })
      if (r.ok) {
        const d = await r.json()
          ; (d.checks || []).forEach(c => {
            addR(c.ok ? 'pass' : 'fail', 'DB: ' + c.label, c.detail + (c.fix ? ' · Fix: ' + c.fix : ''), '', '', 'db')
            if (!c.ok) addBug('E-DATA', 'db', 'alto', c.label, c.detail, c.fix || '')
          })
        return
      }
    } catch (e) { }
    addR('warn', 'DB: /api/spider/db-check no disponible', 'Instalar SpiderController', '', '', 'db')
  }

  async function phaseJsonTests() {
    if (!chk('c-json')) return
    if (!S.tests) { addR('warn', 'Tests JSON: no cargados', 'Haz Sync primero', '', '', 'json'); return }
    log('── JSON TESTS (v5 — filtrado por módulos) ──', 'inf'); setP('Tests del JSON...', 70)

    const SU = cfg('u-super')
    const TU = S.tenant?.url || cfg('u-tenant') || 'http://demo-ferreteria.localhost:8000'
    const urlMap = { super: SU, tenant: TU }
    const BATCH = 10

    const sections = ['http_checks', 'api_sa_checks', 'api_tenant_checks', 'ui_checks']
    let total = 0, done = 0

    // Pre-count respetando filtro de módulos
    for (const sec of sections) {
      const raw = (S.tests[sec] || []).filter(t => t.activo !== false)
      const filtered = filterTestsByModules(raw)
      total += filtered.length
    }

    for (const sec of sections) {
      const raw = (S.tests[sec] || []).filter(t => t.activo !== false)
      const tests = filterTestsByModules(raw)
      const isApiAuth = sec === 'api_sa_checks' || sec === 'api_tenant_checks'

      if (isApiAuth) {
        for (const t of tests) {
          if (S.stop) return
          done++
          const label = t.label || t.desc || t.id
          const expected = t.expected || t.expect || '200'
          const baseUrl = urlMap[t.url_key || 'super'] || SU
          const url = baseUrl + (t.path || '')
          const token = sec === 'api_sa_checks' ? S.saT : S.tT
          const method = t.method || 'GET'

          // Sin token
          const r = await apiFetch(url, null, method)
          const expNoAuth = expected || 401
          if (codeMatch(r.code, String(expNoAuth)))
            addR('pass', '[' + t.id + '] ' + label + ' sin token', 'HTTP ' + r.code, url, '', sec)
          else {
            addR('fail', '[' + t.id + '] ' + label + ' sin token', 'HTTP ' + r.code + ' (esp ' + expNoAuth + ')', url, t.fix || '', sec)
            addBug(t.tipo || 'E-HTTP', t.capa || 'api', t.prio || 'medio',
              label + ' sin token protección', 'HTTP ' + r.code + ' esp ' + expNoAuth, t.fix || '', url)
          }

          // Con token
          if (token && t.expected_with_auth) {
            await sleep(50)
            const r2 = await apiFetch(url, token, method)
            // Considerar módulo inactivo (403) como pass si no está en módulos activos
            const expWithAuth = t.expected_with_auth
            const isModInactive = t.modulo && S.tenant?.modulos_activos?.length > 0 && !S.tenant.modulos_activos.includes(t.modulo)
            const effectiveExp = isModInactive ? '403' : String(expWithAuth)

            if (codeMatch(r2.code, effectiveExp))
              addR('pass', '[' + t.id + '] ' + label + ' con token', 'HTTP ' + r2.code + (isModInactive ? ' (módulo inactivo, esperado)' : ''), url, '', sec)
            else {
              addR('fail', '[' + t.id + '] ' + label + ' con token', 'HTTP ' + r2.code + ' (esp ' + effectiveExp + ')', url, t.fix || '', sec)
              addBug(t.tipo || 'E-HTTP', t.capa || 'api', t.prio || 'medio',
                label + ' con token falla', 'HTTP ' + r2.code + ' esp ' + effectiveExp, t.fix || '', url)
            }
          }

          setP('Tests del JSON...', 70 + Math.round(done / total * 25))
          await sleep(30)
        }
      } else {
        for (let i = 0; i < tests.length; i += BATCH) {
          if (S.stop) return
          const batch = tests.slice(i, i + BATCH)
          const results = await Promise.all(batch.map(async t => {
            const label = t.label || t.desc || t.id
            const expected = t.expected || t.expect || '200'
            const baseUrl = urlMap[t.url_key || 'super'] || SU
            const url = baseUrl + (t.path || '')
            const method = t.method || 'GET'
            const code = await checkUrlWithMethod(url, method)
            return { t, label, expected, url, code }
          }))
          for (const { t, label, expected, url, code } of results) {
            done++
            if (code === 0) {
              addR('fail', '[' + t.id + '] ' + label, 'HTTP 0 — no alcanzable', url, t.fix || 'Verificar DNS/hosts', sec)
              addBug(t.tipo || 'E-HTTP', t.capa || 'ui', t.prio || 'bajo', label, 'HTTP 0', t.fix || 'Verificar /etc/hosts', url)
            } else if (codeMatch(code, String(expected))) {
              addR('pass', '[' + t.id + '] ' + label, 'HTTP ' + code, url, '', sec)
            } else {
              addR('fail', '[' + t.id + '] ' + label, 'HTTP ' + code + ' (esp ' + expected + ')', url, t.fix || '', sec)
              addBug(t.tipo || 'E-HTTP', t.capa || 'ui', t.prio || 'bajo', label, 'HTTP ' + code + ' esp ' + expected, t.fix || '', url)
            }
          }
          setP('Tests del JSON...', 70 + Math.round(done / total * 25))
        }
      }
    }
  }

  async function phaseTenant() {
    if (!chk('c-tenant')) return
    log('── TENANT ──', 'inf'); setP('Setup tenant...', 95)
    const TU = S.tenant?.url || cfg('u-tenant')
    const code = await checkUrl(TU + '/login')
    if (code === 200 || code === 302)
      addR('pass', 'Tenant /login carga — ' + (S.tenant?.slug || TU), 'HTTP ' + code, TU + '/login', '', 'tenant')
    else if (code === 0) {
      addR('fail', 'Tenant no alcanzable', 'HTTP 0 — DNS o red', TU + '/login', 'Agregar al /etc/hosts', 'tenant')
      addBug('E-CONFIG', 'config', 'critico', 'Tenant URL no alcanzable', 'HTTP 0', 'echo "127.0.0.1 ' + (S.tenant?.slug || 'demo') + '.localhost" | sudo tee -a /etc/hosts', TU)
    } else {
      addR('fail', 'Tenant /login error', 'HTTP ' + code, TU + '/login', 'Verificar rutas', 'tenant')
      addBug('E-CONFIG', 'config', 'critico', 'Tenant login HTTP ' + code, 'Esperado 200 o 302', 'Verificar rutas', TU)
    }
  }

  async function phaseUI() {
    if (!chk('c-ui')) return
    log('── UI ──', 'inf'); setP('UI...', 98)
    const SU = cfg('u-super')
    try {
      const r = await fetch(SU + '/central')
      const html = await r.text()
        ;[['tb-brand', 'Brand B&'], ['nav-item', 'Menú lateral'], ['sidebar', 'Sidebar presente']].forEach(([sel, lbl]) => {
          if (html.includes(sel)) addR('pass', 'Central UI: ' + lbl, '"' + sel + '" OK', SU + '/central', '', 'ui')
          else {
            addR('fail', 'Central UI: ' + lbl, '"' + sel + '" NO encontrado', SU + '/central', 'Revisar layouts/central.blade.php', 'ui')
            addBug('E-UI', 'ui', 'medio', lbl + ' faltante', '"' + sel + '" no en HTML', 'Revisar layouts/central.blade.php', SU + '/central')
          }
        })
    } catch (e) { addR('warn', 'Central UI: no verificable', e.message, SU + '/central', '', 'ui') }
  }

  // ══════════════════════════════════════════════════════════
  // MD EXPORT — solo FAILs + bugs
  // ══════════════════════════════════════════════════════════
  function genMd() {
    const now = new Date()
    const d = now.toISOString().split('T')[0]
    const t = now.toLocaleTimeString()
    const TU = S.tenant?.url || cfg('u-tenant')
    const SU = cfg('u-super')
    const meta = S.tests?._meta || {}
    const total = S.pass + S.fail + S.warn
    const pct = total > 0 ? Math.round(S.pass * 100 / total) : 0

    let md = `# BenderAnd Spider QA v5 — Reporte
**Generado:** ${d} ${t}
**SuperAdmin:** ${SU}
**Tenant:** ${S.tenant?.nombre || '?'} · \`${S.tenant?.slug || '?'}\` · ${TU}
**Módulos activos:** ${S.tenant?.modulos_activos?.join(', ') || '—'}
**Tests JSON:** ${meta.total_tests || '?'} tests · última sync: ${meta.updated || '—'}

---

## Resumen

| | |
|---|---|
| Total checks | ${total} |
| ✅ PASS | ${S.pass} |
| ❌ FAIL | ${S.fail} |
| ⚠️ WARN | ${S.warn} |
| 🐛 Bugs | ${S.bugs.length} |
| Tasa éxito | ${pct}% |

`
    if (S.fail === 0) md += `> ✅ Sin fallos — sistema limpio.\n\n`
    else md += `> ❌ ${S.fail} checks fallidos requieren atención.\n\n`

    md += `---\n\n## Bugs Detectados (${S.bugs.length})\n\n`
    if (!S.bugs.length) {
      md += `> ✅ Sin bugs.\n\n`
    } else {
      const ord = { critico: 0, alto: 1, medio: 2, bajo: 3 }
        ;[...S.bugs].sort((a, b) => (ord[a.prio] || 3) - (ord[b.prio] || 3)).forEach(b => {
          const em = b.prio === 'critico' ? '🔴' : b.prio === 'alto' ? '🟠' : b.prio === 'medio' ? '🟡' : '⚪'
          md += `### ${em} ${b.id} — ${b.desc}\n\n| Campo | Valor |\n|---|---|\n`
          md += `| **Tipo** | \`${b.tipo}\` |\n| **Capa** | \`${b.capa}\` |\n| **Prioridad** | ${b.prio} |\n`
          md += `| **URL** | \`${b.url || '—'}\` |\n| **Detectado** | ${b.ts} |\n\n`
          if (b.detail) md += `**Detalle:** ${b.detail}\n\n`
          md += `**Fix:**\n\`\`\`bash\n${b.fix || '# Analizar causa raíz'}\n\`\`\`\n\n---\n\n`
        })
    }

    // Solo FAILs
    const fails = S.res.filter(r => r.status === 'fail' || r.status === 'warn')
    if (fails.length) {
      md += `## Checks Fallidos (${fails.length})\n\n`
      fails.forEach(r => {
        const s = r.status === 'fail' ? '❌' : '⚠️'
        md += `${s} **${r.title}**`
        if (r.detail) md += ` — ${r.detail}`
        if (r.url) md += `\n   \`${r.url}\``
        if (r.fix) md += `\n   → ${r.fix}`
        md += '\n\n'
      })
    } else {
      md += `## Checks Fallidos\n\n> ✅ Sin FAILs.\n\n`
    }
    return md
  }

  function cpMd() {
    navigator.clipboard.writeText(g('mdo').textContent).then(() => {
      const b = document.querySelector('.bcp')
      b.textContent = '¡Copiado!'; setTimeout(() => b.textContent = 'Copiar MD', 2000)
    })
  }

  function dlMd() {
    const slug = S.tenant?.slug || 'spider'
    const a = document.createElement('a')
    a.href = URL.createObjectURL(new Blob([g('mdo').textContent], { type: 'text/markdown' }))
    a.download = 'spider_' + slug + '_' + new Date().toISOString().split('T')[0] + '.md'
    a.click()
  }

  // ══════════════════════════════════════════════════════════
  // CRAWL ORCHESTRATOR
  // ══════════════════════════════════════════════════════════
  async function startCrawl() {
    if (S.run) return
    if (!S.tenant) { log('Selecciona un tenant primero', 'err'); return }

    Object.assign(S, { run: true, stop: false, pass: 0, fail: 0, warn: 0, bugs: [], res: [], logs: [], saT: S.saT, tT: null, bseq: 1 })
      ;['rl', 'bl', 'll'].forEach(id => g(id).innerHTML = '')
      ;['er', 'eb'].forEach(id => g(id).style.display = '')
    g('mds').style.display = 'none'; g('emd').style.display = ''
    g('fr').style.display = 'none'
    updK()
    g('btn-run').disabled = true; g('btn-stop').style.display = 'flex'
    g('btn-exp').style.display = 'none'; g('pw').style.display = 'block'
    g('pf').style.background = 'var(--ac)'
    log('Spider v5 — tenant: ' + S.tenant.slug + ' · módulos: ' + S.tenant.modulos_activos.length, 'inf')
    st('r')

    try {
      await phaseAuth(); if (S.stop) return crawlDone()
      await sleep(100)
      await phaseRoles(); if (S.stop) return crawlDone()
      await sleep(100)
      await phaseDB(); if (S.stop) return crawlDone()
      await sleep(100)
      await phaseJsonTests(); if (S.stop) return crawlDone()
      await sleep(100)
      await phaseTenant(); if (S.stop) return crawlDone()
      await sleep(100)
      await phaseUI()
    } catch (e) {
      log('Error: ' + e.message, 'err')
      addR('warn', 'Error inesperado', e.message)
    }
    crawlDone()
  }

  function crawlDone() {
    S.run = false; S.stop = false
    g('btn-run').disabled = false; g('btn-stop').style.display = 'none'
    const total = S.pass + S.fail + S.warn
    const pct = total > 0 ? Math.round(S.pass * 100 / total) : 0
    setP('Completado — ' + pct + '% OK', 100)
    g('pf').style.background = S.fail > 0 ? 'var(--err)' : 'var(--ok)'
    log('Terminado: ' + S.pass + ' PASS · ' + S.fail + ' FAIL · ' + S.bugs.length + ' bugs', S.fail > 0 ? 'err' : 'ok')
    const md = genMd()
    g('mdo').textContent = md; g('mds').style.display = 'block'; g('emd').style.display = 'none'
    g('btn-exp').style.display = 'flex'
    if (S.bugs.length > 0) st('b')
  }

  function stopCrawl() { S.stop = true; log('Detenido', 'w'); crawlDone() }

  // ══════════════════════════════════════════════════════════
  // INIT
  // ══════════════════════════════════════════════════════════
  window.addEventListener('load', async () => {
    await getSaToken()
    await loadTenants()
    if (S.saT) await syncTests()
  })
</script>
@endpush