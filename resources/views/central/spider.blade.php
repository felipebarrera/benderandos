@extends('layouts.central')

@section('title', 'Spider QA')
@section('page-title', 'Spider QA v4 — Browser-side')

@push('styles')
<style>
  :root {
    --r: 8px;
  }

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

  .f input {
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

  .f input:focus {
    border-color: rgba(224, 64, 251, .4)
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

  .spider-layout {
    display: flex;
    gap: 20px;
    height: calc(100vh - 120px);
    overflow: hidden
  }

  .spider-sidebar {
    width: 265px;
    min-width: 265px;
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

  .pane {
    display: none;
    flex: 1;
    overflow-y: auto;
    padding: 16px
  }

  .pane.on {
    display: block
  }

  .ri {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 10px;
    border-radius: var(--r);
    margin-bottom: 4px;
    font-size: 11px;
    border-left: 2px solid transparent;
    background: var(--s1);
    border: 1px solid var(--b1)
  }

  .ri.pass {
    border-left-color: var(--ok)
  }

  .ri.fail {
    border-left-color: var(--err);
    background: rgba(255, 63, 91, .04)
  }

  .ri.warn {
    border-left-color: var(--warn)
  }

  .ri.auto {
    border-left-color: var(--info)
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

  .auto .rist {
    background: rgba(68, 136, 255, .1);
    color: var(--info)
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

  .bsm {
    padding: 7px 14px;
    font-size: 11px
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
</style>
@endpush

@section('content')
<div class="page-hdr">
  <div class="page-title">Spider QA v4 — Browser-side</div>
  <div class="tb-badge">H22 · H23</div>
</div>

<div class="spider-layout">
  <aside class="spider-sidebar">
    <div class="sl">Targets</div>
    <div class="f"><label>SuperAdmin URL</label><input id="u-super" value="{{ url('/') }}"></div>
    <div class="f">
      <label>Tenant URL</label>
      <input id="u-tenant" type="text" value="http://demo-ferreteria.localhost:8000" />
      <div id="tenant-info" style="font-size:9px;color:var(--t2);margin-top:4px">
        <!-- Se llena dinámicamente con JS -->
      </div>
    </div>
    <div class="f"><label>SA Email</label><input id="sa-email" value="admin@benderand.cl" autocomplete="off"></div>
    <div class="f"><label>SA Password</label><input type="password" id="sa-pass" value="password"
        autocomplete="new-password"></div>
    <div class="f"><label>Tenant Email</label><input id="t-email" value="admin@benderand.cl" autocomplete="off"></div>
    <div class="f"><label>Tenant Password</label><input type="password" id="t-pass" value="admin1234"
        autocomplete="new-password"></div>

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
  <div class="spider-main">
    <div class="tabs">
      <div class="tab on" id="tab-r" onclick="st('r')">Resultados</div>
      <div class="tab" id="tab-b" onclick="st('b')">Bugs <span id="bct"></span></div>
      <div class="tab" id="tab-l" onclick="st('l')">Log</div>
      <div class="tab" id="tab-tests" onclick="st('tests')">Tests JSON <span id="json-ct" class="badge"
          style="display:none; margin-left:6px; background:var(--info)"></span></div>
      <div class="tab" id="tab-md" onclick="st('md')">Export MD</div>
    </div>

    <div class="pane on" id="pane-r">
      <div class="empty" id="er">
        <div class="ei">◈</div>
        <div class="et">Spider v4 en espera</div>
        <div>Haz clic en "Sync desde Laravel" para cargar los tests, luego ejecuta el spider</div>
      </div>
      <div class="spider-fr" id="fr" style="display:none">
        <button class="spider-fb on" onclick="filt(this,'all')">Todo</button>
        <button class="spider-fb" onclick="filt(this,'fail')">FAIL</button>
        <button class="spider-fb" onclick="filt(this,'pass')">PASS</button>
        <button class="spider-fb" onclick="filt(this,'warn')">WARN</button>
        <button class="spider-fb" onclick="filt(this,'auto')">AUTO</button>
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
          Editor de <code style="color:var(--ac)">spider_tests.json</code> —
          agrega o edita tests sin tocar el código del spider
        </div>
        <button class="btn btn-primary" onclick="saveTests()">Guardar</button>
        <button class="btn" style="background:var(--s2); border:1px solid var(--b2); color:var(--tx)"
          onclick="syncTests()">⟳ Re-sync</button>
      </div>
      <textarea class="tests-editor" id="tests-editor"
        placeholder="Haz sync primero para cargar los tests..."></textarea>
    </div>

    <div class="pane" id="pane-md">
      <div class="empty" id="emd">
        <div class="ei">⬇</div>
        <div class="et">Sin resultados</div>
      </div>
      <div id="mds" style="display:none">
        <div class="brow">
          <button class="btn btn-primary bcp" onclick="cpMd()">Copiar MD</button>
          <button class="btn bdl" style="background:var(--s2); border:1px solid var(--b2); color:var(--tx)"
            onclick="dlMd()">Descargar .md</button>
        </div>
        <div class="mdb" id="mdo"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const S = { run: false, stop: false, pass: 0, fail: 0, warn: 0, bugs: [], res: [], logs: [], saT: null, tT: null, bseq: 1, tests: null }
  const g = id => document.getElementById(id)
  const cfg = id => g(id).value.trim()
  const chk = id => g(id).checked
  const sleep = ms => new Promise(r => setTimeout(r, ms))

  function st(t) {
    document.querySelectorAll('.pane').forEach(p => p.classList.remove('on'))
    document.querySelectorAll('.tab').forEach(tb => tb.classList.remove('on'))
    g('pane-' + t).classList.add('on'); g('tab-' + t).classList.add('on')
  }
  function log(msg, type = '') {
    const el = g('ll'), d = document.createElement('div')
    d.className = 'll' + (type ? ' ' + type : '')
    d.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg
    el.appendChild(d); el.scrollTop = el.scrollHeight
    S.logs.push({ ts: new Date().toISOString(), msg, type })
  }
  function setP(lbl, pct) { g('pl').textContent = lbl; g('pp').textContent = Math.round(pct) + '%'; g('pf').style.width = pct + '%' }

  function updK() {
    g('kp').textContent = S.pass; g('kf').textContent = S.fail
    g('kw').textContent = S.warn; g('kb').textContent = S.bugs.length
    const bc = g('bct'); bc.textContent = S.bugs.length > 0 ? ' (' + S.bugs.length + ')' : ''; bc.style.color = S.bugs.length > 0 ? 'var(--err)' : ''
  }

  function addR(status, title, detail, url, fix, src = '') {
    S.res.push({ status, title, detail, url, fix, src, ts: new Date().toISOString() })
    if (status === 'pass') S.pass++; else if (status === 'fail') S.fail++; else if (status === 'warn') S.warn++
    updK(); g('er').style.display = 'none'; g('fr').style.display = 'flex'
    const el = document.createElement('div')
    el.className = 'ri ' + status; el.dataset.s = status
    el.innerHTML = `<div class="rist">${status.toUpperCase()}</div><div class="rib">
    <div class="rit">${title}</div>
    ${detail ? '<div class="rid">' + detail + '</div>' : ''}
    ${url ? '<div class="riu">' + url + '</div>' : ''}
    ${fix ? '<div class="rif">→ ' + fix + '</div>' : ''}
    ${src ? '<div class="ri-src">src: ' + src + '</div>' : ''}
  </div>`
    g('rl').appendChild(el); el.scrollIntoView({ block: 'nearest' })
  }

  function addBug(tipo, capa, prio, desc, detail, fix, url) {
    const id = 'BUG-SP-' + String(S.bseq++).padStart(3, '0')
    S.bugs.push({ id, tipo, capa, prio, desc, detail, fix, url, ts: new Date().toISOString() })
    updK(); g('eb').style.display = 'none'
    const tc = { 'E-AUTH': 'ta', 'E-HTTP': 'th', 'E-DATA': 'td', 'E-UI': 'tu', 'E-CONFIG': 'tc', 'E-PERM': 'th', 'E-REDIRECT': 'tc' }
    const pc = { critico: 'tpc', alto: 'tpa', medio: 'tpm' }
    const el = document.createElement('div'); el.className = 'bc'
    el.innerHTML = `<div class="bh"><span class="bid">${id}</span>
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
    document.querySelectorAll('.spider-fb').forEach(b => b.classList.remove('on')); btn.classList.add('on')
    document.querySelectorAll('.ri').forEach(el => { el.style.display = (type === 'all' || el.dataset.s === type) ? '' : 'none' })
  }

  // ── SYNC: carga tests desde Laravel /api/spider/sync ─────────────────────────
  public function syncTests(Request $request) {
    if (!file_exists($this -> syncScript))
      return response() -> json(['ok'=> false, 'error'=> 'sync_spider_tests.sh no encontrado',
        'fix'=> 'cp sync_spider_tests.sh /app/tests/'], 404);

    $before = $this -> readTests();
    $bc = $this -> countTests($before);

    $p = new Process(['bash', $this -> syncScript]);
    $p -> setTimeout(30); $p -> run();

    $after = $this -> readTests();
    $ac = $this -> countTests($after);

    $diff = [];
    foreach(['http_checks', 'api_sa_checks', 'api_tenant_checks', 'auth_checks', 'db_checks', 'ui_checks'] as $s) {
      $b = count($before[$s] ?? []); $a = count($after[$s] ?? []);
      if ($a !== $b) $diff[$s] = ['before'=> $b, 'after'=> $a, 'added'=> $a - $b];
    }

    // Usar método simplificado que no requiere cross-schema relationships
    $recommendedTenant = $this -> getRecommendedTenant();

    return response() -> json([
      'ok'=> $p -> isSuccessful(),
      'output'=> $p -> getOutput(),
      'before_total'=> $bc,
      'after_total'=> $ac,
      'new_tests'=> $ac - $bc,
      'diff'=> $diff,
      'tests'=> $after,
      'tenant_slug' => $recommendedTenant['slug'],
      'tenant_url' => $recommendedTenant['url']
    ]);
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
      if (d.ok) {
        S.tests = tests; log('Tests guardados: ' + d.total + ' total', 'ok')
      } else log('Error guardando: ' + JSON.stringify(d), 'err')
    } catch (e) { log('JSON inválido: ' + e.message, 'err') }
  }

  // ── BROWSER-SIDE FETCH (Spider v4) ────────────────────────────────────────────
  // Direct fetch from browser — no server-side probe needed.
  // The browser resolves DNS exactly like the end user would.
  async function checkUrl(url) {
    try {
      const ctrl = new AbortController()
      const tm = setTimeout(() => ctrl.abort(), 4000)
      const r = await fetch(url, { method: 'GET', redirect: 'follow', signal: ctrl.signal })
      clearTimeout(tm)
      return r.status
    } catch (e) { return 0 }
  }
  async function checkUrlAuth(url, token) {
    try {
      const ctrl = new AbortController()
      const tm = setTimeout(() => ctrl.abort(), 4000)
      const r = await fetch(url, { headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }, signal: ctrl.signal })
      clearTimeout(tm)
      return r.status
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

  // ── FASES ────────────────────────────────────────────────────────────────────
  async function phaseAuth() {
    if (!chk('c-auth')) return
    log('── AUTH ──', 'inf'); setP('Autenticación...', 5)
    const SU = cfg('u-super'), TU = cfg('u-tenant')

    // SA: obtener token desde sesión Laravel (sin enviar password)
    if (!S.saT) {
      try {
        const r = await fetch('{{ route('central.spider.token') }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
          }
        })
        const d = await r.json()
        if (d.token) { S.saT = d.token; addR('pass', 'SA token OK (sesión activa)', '', '', '', 'auth') }
        else { addR('fail', 'SA token falla', JSON.stringify(d), '', '', 'auth') }
      } catch (e) { addR('fail', 'SA token error', e.message, '', '', 'auth') }
    }

    if (S.stop) return

    // Tenant: login real para testear autenticación
    const t = await apiPost(TU + '/api/login', { email: cfg('t-email'), password: cfg('t-pass') })
    if (t.code && t.code < 400 && t.data && t.data.token) {
      S.tT = t.data.token; addR('pass', 'Tenant login OK', 'HTTP ' + t.code, TU + '/api/login', '', 'auth')
    } else {
      addR('fail', 'Tenant login falla', 'HTTP ' + t.code + ' ' + JSON.stringify(t.data || {}).substring(0, 100), TU + '/api/login', 'bash tests/diagnose_tenant.sh', 'auth')
      addBug('E-AUTH', 'db', 'critico', 'Tenant login falla', 'HTTP ' + t.code, 'bash tests/diagnose_tenant.sh', TU + '/api/login')
    }
  }

  async function phaseRoles() {
    if (!chk('c-roles')) return
    log('── ROLES ──', 'inf'); setP('Permisos...', 45)
    const SU = cfg('u-super'), TU = cfg('u-tenant')
    const noAuth = [[TU + '/api/dashboard', 'Tenant /api/dashboard'], [SU + '/api/superadmin/dashboard', 'SA /api/superadmin/dashboard']]
    for (const [url, lbl] of noAuth) {
      if (S.stop) return
      const r = await apiFetch(url, null)
      if (r.code === 401 || r.code === 403) addR('pass', 'Sin token: ' + lbl + ' protegido', 'HTTP ' + r.code, url, '', 'roles')
      else {
        addR('fail', 'Sin token: ' + lbl + ' NO protegido', 'HTTP ' + r.code, url, 'middleware auth:sanctum faltante', 'roles')
        addBug('E-PERM', 'laravel', 'critico', 'Ruta sin auth: ' + url, 'HTTP ' + r.code, 'Agregar ->middleware("auth:sanctum")', url)
      }
    }
    if (S.tT) {
      const r = await apiFetch(SU + '/api/superadmin/dashboard', S.tT)
      if (r.code === 401 || r.code === 403) addR('pass', 'Cross-tenant bloqueado', 'HTTP ' + r.code, '', '', 'roles')
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
    if (!S.saT) { addR('warn', 'DB: sin token SA', 'Skip — SA login falló'); return }
    try {
      const r = await fetch(SU + '/api/spider/db-check', { headers: { 'Authorization': 'Bearer ' + S.saT, 'Accept': 'application/json' } })
      if (r.ok) {
        const d = await r.json()
          ; (d.checks || []).forEach(c => {
            addR(c.ok ? 'pass' : 'fail', 'DB: ' + c.label, c.detail + (c.fix ? ' · Fix: ' + c.fix : ''), '', '', 'db')
            if (!c.ok) addBug('E-DATA', 'db', 'alto', c.label, c.detail, c.fix || '')
          }); return
      }
    } catch (e) { }
    addR('warn', 'DB: /api/spider/db-check no disponible', 'Instalar SpiderController', '', '', 'db')
  }
  // Helper para construir URL de tenant con slug configurable
  function buildTenantUrl(path, slug = 'demo-ferreteria') {
    return `http://${slug}.localhost:8000${path}`
  }

  // En la inicialización, leer tenant_slug desde config o usar default
  async function initSpider() {
    // Intentar obtener tenant_slug desde el backend
    try {
      const r = await fetch('{{ route('central.spider.token') }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json'
        }
      });
      const d = await r.json();
      if (d.tenant_slug) {
        // Guardar en configuración
        g('u-tenant').value = buildTenantUrl('', d.tenant_slug);
      }
    } catch (e) { }

    // Fallback a demo-ferreteria.localhost
    if (!g('u-tenant').value) {
      g('u-tenant').value = 'http://demo-ferreteria.localhost:8000';
    }
  }
  // ── JSON TESTS — browser-side with batched parallelism ───────────────────────
  async function phaseJsonTests() {
    if (!chk('c-json')) return
    if (!S.tests) { addR('warn', 'Tests JSON: no cargados', 'Haz Sync primero', '', '', 'json'); return }
    log('── JSON TESTS (browser-side v4) ──', 'inf'); setP('Tests del JSON...', 70)

    const SU = cfg('u-super')
    // FIX: Usar demo-ferreteria.localhost en lugar de demo.localhost
    const TU = cfg('u-tenant') || 'http://demo-ferreteria.localhost:8000'

    const urlMap = {
      super: SU,
      tenant: TU  // Ahora apunta a demo-ferreteria.localhost por defecto
    }

    const BATCH = 10
    const sections = ['http_checks', 'api_sa_checks', 'api_tenant_checks', 'ui_checks']
    let total = 0, done = 0

    // Contar tests activos
    for (const sec of sections)
      for (const t of S.tests[sec] || [])
        if (t.activo !== false) total++

    for (const sec of sections) {
      const tests = (S.tests[sec] || []).filter(t => t.activo !== false)
      const isApiAuth = sec === 'api_sa_checks' || sec === 'api_tenant_checks'

      if (isApiAuth) {
        // API auth checks — sequential (need per-check token logic)
        for (const t of tests) {
          if (S.stop) return
          done++
          const label = t.label || t.desc || t.id
          const expected = t.expected || t.expect || '200'
          const baseUrl = urlMap[t.url_key || 'super'] || SU
          const url = baseUrl + (t.path || '')
          const token = sec === 'api_sa_checks' ? S.saT : S.tT
          const method = t.method || 'GET'

          // Without auth
          const r = await apiFetch(url, null, method)
          const expNoAuth = expected || 401
          if (codeMatch(r.code, String(expNoAuth)))
            addR('pass', '[' + t.id + '] ' + label + ' sin token', 'HTTP ' + r.code, url, '', sec)
          else {
            addR('fail', '[' + t.id + '] ' + label + ' sin token', 'HTTP ' + r.code + ' (esp ' + expNoAuth + ')', url, t.fix || '', sec)
            addBug(t.tipo || 'E-HTTP', t.capa || 'api', t.prio || 'medio', label + ' sin token protección',
              'HTTP ' + r.code + ' esperado ' + expNoAuth, t.fix || '', url)
          }
          // With auth
          if (token && t.expected_with_auth) {
            await sleep(50)
            const r2 = await apiFetch(url, token, method)
            if (codeMatch(r2.code, String(t.expected_with_auth || 200)))
              addR('pass', '[' + t.id + '] ' + label + ' con token', 'HTTP ' + r2.code, url, '', sec)
            else {
              addR('fail', '[' + t.id + '] ' + label + ' con token', 'HTTP ' + r2.code + ' (esp ' + t.expected_with_auth + ')', url, t.fix || '', sec)
              addBug(t.tipo || 'E-HTTP', t.capa || 'api', t.prio || 'medio', label + ' con token falla',
                'HTTP ' + r2.code + ' esperado ' + t.expected_with_auth, t.fix || '', url)
            }
          }
          setP('Tests del JSON...', 70 + Math.round(done / total * 25))
          await sleep(30)
        }
      } else {
        // HTTP/UI checks — batched parallel via browser fetch (Spider v4)
        for (let i = 0; i < tests.length; i += BATCH) {
          if (S.stop) return
          const batch = tests.slice(i, i + BATCH)
          const results = await Promise.all(batch.map(async t => {
            const label = t.label || t.desc || t.id
            const expected = t.expected || t.expect || '200'
            const baseUrl = urlMap[t.url_key || 'super'] || SU
            const url = baseUrl + (t.path || '')
            const code = await checkUrl(url)
            return { t, label, expected, url, code }
          }))
          for (const { t, label, expected, url, code } of results) {
            done++
            if (code === 0) {
              addR('fail', '[' + t.id + '] ' + label, 'HTTP 0 — no alcanzable', url, t.fix || 'Verificar DNS/hosts', sec)
              addBug(t.tipo || 'E-HTTP', t.capa || 'ui', t.prio || 'bajo', label,
                'HTTP 0 — no alcanzable', t.fix || 'Verificar DNS/hosts', url)
            } else if (codeMatch(code, String(expected))) {
              addR('pass', '[' + t.id + '] ' + label, 'HTTP ' + code, url, '', sec)
            } else {
              addR('fail', '[' + t.id + '] ' + label, 'HTTP ' + code + ' (esp ' + expected + ')', url, t.fix || '', sec)
              addBug(t.tipo || 'E-HTTP', t.capa || 'ui', t.prio || 'bajo', label,
                'HTTP ' + code + ' esperado ' + expected, t.fix || '', url)
            }
          }
          setP('Tests del JSON...', 70 + Math.round(done / total * 25))
        }
      }
    }
  }

  async function phaseTenant() {
    if (!chk('c-tenant')) return
    log('── TENANT (browser-side v4) ──', 'inf'); setP('Setup tenant...', 95)
    const TU = cfg('u-tenant')
    const code = await checkUrl(TU + '/login')
    if (code === 200 || code === 302) addR('pass', 'Tenant /login carga', 'HTTP ' + code, TU + '/login', '', 'tenant')
    else if (code === 0) {
      addR('fail', 'Tenant /login no alcanzable', 'HTTP 0 — DNS o red', TU + '/login', 'echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts', 'tenant')
      addBug('E-CONFIG', 'config', 'critico', 'Tenant URL no alcanzable', 'HTTP 0', 'echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts', TU)
    }
    else {
      addR('fail', 'Tenant /login error', 'HTTP ' + code, TU + '/login', 'Verificar rutas de tenant', 'tenant')
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
            addBug('E-UI', 'ui', 'medio', lbl + ' faltante', 'Selector "' + sel + '" no en HTML', 'Revisar layouts/central.blade.php', SU + '/central')
          }
        })
    } catch (e) { addR('warn', 'Central UI: no verificable', e.message, SU + '/central', '', 'ui') }
  }

  function genMd() {
    const now = new Date(), d = now.toISOString().split('T')[0], t = now.toLocaleTimeString()
    const SU = cfg('u-super'), TU = cfg('u-tenant')
    const total = S.pass + S.fail + S.warn, pct = total > 0 ? Math.round(S.pass * 100 / total) : 0
    const meta = S.tests?._meta || {}

    let md = `# BenderAnd Spider QA v4 — Reporte
**Generado:** ${d} ${t}
**SuperAdmin:** ${SU} | **Tenant:** ${TU}
**Tests JSON:** ${meta.total_tests || 'no cargado'} tests · última sync: ${meta.last_sync || '—'}

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

---

## Bugs Detectados (${S.bugs.length})

`
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

    md += `## Todos los Checks (${S.res.length})\n\n`
    S.res.forEach(r => {
      const s = r.status === 'pass' ? '✅' : r.status === 'fail' ? '❌' : '⚠️'
      md += `${s} **${r.title}**`
      if (r.detail) md += ` — ${r.detail}`
      if (r.url) md += `\n   \`${r.url}\``
      if (r.fix) md += `\n   → ${r.fix}`
      md += '\n\n'
    })
    return md
  }

  function cpMd() { navigator.clipboard.writeText(g('mdo').textContent).then(() => { const b = document.querySelector('.bcp'); b.textContent = '¡Copiado!'; setTimeout(() => b.textContent = 'Copiar MD', 2000) }) }

  function dlMd() {
    const a = document.createElement('a')
    a.href = URL.createObjectURL(new Blob([g('mdo').textContent], { type: 'text/markdown' }))
    a.download = 'benderand_spider_' + new Date().toISOString().split('T')[0] + '.md'; a.click()
  }

  async function startCrawl() {
    if (S.run) return
    Object.assign(S, { run: true, stop: false, pass: 0, fail: 0, warn: 0, bugs: [], res: [], logs: [], saT: null, tT: null, bseq: 1 })
      ;['rl', 'bl', 'll'].forEach(id => g(id).innerHTML = '')
      ;['er', 'eb'].forEach(id => g(id).style.display = '')
    g('mds').style.display = 'none'; g('emd').style.display = ''; g('fr').style.display = 'none'
    updK(); g('btn-run').disabled = true; g('btn-stop').style.display = 'flex'
    g('btn-exp').style.display = 'none'; g('pw').style.display = 'block'; g('pf').style.background = 'var(--ac)'
    log('Spider v4 iniciado (browser-side)', 'inf'); st('r')
    try {
      await phaseAuth(); if (S.stop) return done()
      await sleep(100)
      await phaseRoles(); if (S.stop) return done()
      await sleep(100)
      await phaseDB(); if (S.stop) return done()
      await sleep(100)
      await phaseJsonTests(); if (S.stop) return done()
      await sleep(100)
      await phaseTenant(); if (S.stop) return done()
      await sleep(100)
      await phaseUI()
    } catch (e) { log('Error: ' + e.message, 'err'); addR('warn', 'Error', e.message) }
    done()
  }
  function done() {
    S.run = false; S.stop = false; g('btn-run').disabled = false; g('btn-stop').style.display = 'none'
    const total = S.pass + S.fail + S.warn, pct = total > 0 ? Math.round(S.pass * 100 / total) : 0
    setP('Completado — ' + pct + '% OK', 100); g('pf').style.background = S.fail > 0 ? 'var(--err)' : 'var(--ok)'
    log('Terminado: ' + S.pass + ' PASS · ' + S.fail + ' FAIL · ' + S.bugs.length + ' bugs', S.fail > 0 ? 'err' : 'ok')
    const md = genMd(); g('mdo').textContent = md; g('mds').style.display = 'block'; g('emd').style.display = 'none'
    g('btn-exp').style.display = 'flex'
    if (S.bugs.length > 0) st('b')
  }
  function stopCrawl() { S.stop = true; log('Detenido', 'w'); done() }

  window.addEventListener('load', async () => {
    // Intentar obtener tenant_slug desde el backend
    try {
      const r = await fetch('/api/spider/tenant-slug', {
        headers: {
          'Accept': 'application/json',
          ...(S.saT ? { 'Authorization': 'Bearer ' + S.saT } : {})
        }
      });
      if (r.ok) {
        const d = await r.json();
        g('u-tenant').value = d.url;
        g('tenant-info').textContent = `✅ ${d.nombre} · ${d.modulos_activos_count} módulos activos`;
        g('tenant-info').style.color = 'var(--ok)';
      }
    } catch (e) {
      g('tenant-info').textContent = '⚠️ Usando demo-ferreteria.localhost por defecto';
      g('tenant-info').style.color = 'var(--warn)';
    }
    // Al cargar, intentar obtener token desde sesión
    if (!S.saT) {
      try {
        const r_token = await fetch('{{ route('central.spider.token') }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
          }
        });
        const d_token = await r_token.json();
        if (d_token.token) {
          S.saT = d_token.token;
          await syncTests();
        }
      } catch (e) { }
    }
  })
</script>
@endpush