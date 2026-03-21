@extends('layouts.superadmin')

@section('extra_css')
<style>
:root{ --r:8px; }
.sl{font-family:var(--mono);font-size:9px;font-weight:700;letter-spacing:1.5px;
  text-transform:uppercase;color:var(--t3);margin:14px 0 6px 4px}.sl:first-child{margin-top:0}
.f{margin-bottom:8px}
.f label{font-family:var(--mono);font-size:9px;font-weight:700;letter-spacing:1px;
  text-transform:uppercase;color:var(--t2);display:block;margin-bottom:4px}
.f input{width:100%;background:var(--s2);border:1px solid var(--b2);border-radius:var(--r);
  color:var(--tx);font-family:var(--mono);font-size:11px;padding:7px 10px;outline:none}
.f input:focus{border-color:rgba(224,64,251,.4)}
.tog-row{display:flex;align-items:center;justify-content:space-between;padding:4px 0}
.tog-row span{font-size:11px;color:var(--t2)}
.tog{position:relative;width:32px;height:16px;flex-shrink:0}
.tog input{opacity:0;width:0;height:0}
.tsl{position:absolute;inset:0;background:var(--b2);border-radius:8px;cursor:pointer;transition:.2s}
.tsl::before{content:'';position:absolute;width:10px;height:10px;left:3px;bottom:3px;
  background:var(--t2);border-radius:50%;transition:.2s}
.tog input:checked+.tsl{background:rgba(224,64,251,.3)}
.tog input:checked+.tsl::before{background:var(--ac);transform:translateX(16px)}
.sync-box{background:var(--s2);border:1px solid var(--b2);border-radius:var(--r);
  padding:8px 10px;margin-top:6px;font-size:10px}
.sync-lbl{font-family:var(--mono);font-size:9px;color:var(--t2);margin-bottom:6px}
.sync-stats{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}
.sstat{font-family:var(--mono);font-size:10px;padding:2px 8px;border-radius:4px;
  background:rgba(224,64,251,.08);color:var(--ac)}
.btn-run{background:var(--ac);color:#000;width:100%;justify-content:center;margin-top:8px}
.btn-sync{background:rgba(68,136,255,.12);color:var(--info);border:1px solid rgba(68,136,255,.25);
  width:100%;justify-content:center;margin-top:6px;font-size:11px;padding:7px}
.btn-stop{background:rgba(255,63,91,.15);color:var(--err);border:1px solid rgba(255,63,91,.3);
  width:100%;justify-content:center;margin-top:6px;display:none}
.btn-exp{background:rgba(224,64,251,.1);color:var(--ac);border:1px solid rgba(224,64,251,.2);
  width:100%;justify-content:center;margin-top:6px;display:none}
.pw{margin-top:12px;display:none}
.pl{font-family:var(--mono);font-size:9px;color:var(--t2);margin-bottom:4px;display:flex;justify-content:space-between}
.pb{height:4px;background:var(--b2);border-radius:2px;overflow:hidden}
.pf{height:100%;background:var(--ac);border-radius:2px;width:0%;transition:width .3s}
.kpis{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:12px}
.kpi{background:var(--s2);border-radius:var(--r);padding:8px 10px;text-align:center}
.kpi-v{font-family:var(--mono);font-size:20px;font-weight:700}
.kpi-l{font-size:9px;color:var(--t2);margin-top:2px;font-family:var(--mono);text-transform:uppercase}
.kp .kpi-v{color:var(--ok)}.kf .kpi-v{color:var(--err)}.kw .kpi-v{color:var(--warn)}.kb .kpi-v{color:var(--info)}
.spider-layout{display:flex;gap:20px;height: calc(100vh - 100px);overflow:hidden}
.spider-sidebar{width:265px;min-width:265px;background:var(--s1);border:1px solid var(--b1);border-radius:12px;
  display:flex;flex-direction:column;overflow-y:auto;flex-shrink:0;padding:14px 12px}
.spider-main{flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--s1);border:1px solid var(--b1);border-radius:12px}
.tabs{display:flex;border-bottom:1px solid var(--b1);flex-shrink:0;background:var(--s1)}
.tab{padding:10px 16px;font-size:11px;font-weight:600;color:var(--t2);cursor:pointer;
  border-bottom:2px solid transparent;transition:all .15s;font-family:var(--mono)}
.tab:hover{color:var(--tx)}.tab.on{color:var(--ac);border-bottom-color:var(--ac)}
.pane{display:none;flex:1;overflow-y:auto;padding:16px}
.pane.on{display:block}
.ri{display:flex;align-items:flex-start;gap:10px;padding:8px 10px;border-radius:var(--r);
  margin-bottom:4px;font-size:11px;border-left:2px solid transparent;background:var(--s1);border:1px solid var(--b1)}
.ri.pass{border-left-color:var(--ok)}.ri.fail{border-left-color:var(--err);background:rgba(255,63,91,.04)}
.ri.warn{border-left-color:var(--warn)}.ri.auto{border-left-color:var(--info)}
.rist{font-family:var(--mono);font-size:9px;font-weight:700;min-width:40px;text-align:center;
  padding:2px 4px;border-radius:4px;flex-shrink:0;margin-top:1px}
.pass .rist{background:rgba(0,229,160,.1);color:var(--ok)}.fail .rist{background:rgba(255,63,91,.1);color:var(--err)}
.warn .rist{background:rgba(245,197,24,.1);color:var(--warn)}.auto .rist{background:rgba(68,136,255,.1);color:var(--info)}
.rib{flex:1;min-width:0}.rit{font-weight:600;margin-bottom:2px}
.rid{color:var(--t2);font-family:var(--mono);font-size:10px;word-break:break-all}
.riu{color:var(--t3);font-size:9px;font-family:var(--mono)}
.rif{margin-top:4px;padding:4px 8px;background:rgba(245,197,24,.07);border:1px solid rgba(245,197,24,.12);
  border-radius:5px;color:var(--warn);font-size:10px;font-family:var(--mono)}
.ri-src{font-size:9px;color:var(--info);font-family:var(--mono);margin-top:2px}
.bc{background:var(--s1);border:1px solid var(--b1);border-radius:10px;padding:12px;margin-bottom:8px}
.bh{display:flex;align-items:center;gap:6px;margin-bottom:8px;flex-wrap:wrap}
.bid{font-family:var(--mono);font-size:10px;font-weight:700;color:var(--t2)}
.tg{font-size:9px;font-family:var(--mono);padding:2px 7px;border-radius:4px;font-weight:700}
.ta{background:rgba(255,63,91,.1);color:var(--err)}.th{background:rgba(68,136,255,.1);color:var(--info)}
.td{background:rgba(224,64,251,.1);color:var(--ac)}.tu{background:rgba(245,197,24,.1);color:var(--warn)}
.tc{background:rgba(0,229,160,.1);color:var(--ok)}
.tpc{background:rgba(255,63,91,.15);color:var(--err)}.tpa{background:rgba(245,197,24,.1);color:var(--warn)}
.tpm{background:rgba(68,136,255,.1);color:var(--info)}
.bd{font-size:12px;font-weight:600;margin-bottom:4px}
.bdet{font-size:10px;color:var(--t2);font-family:var(--mono);margin-bottom:6px}
.bfix{padding:6px 8px;background:var(--s2);border-radius:6px;font-size:10px;font-family:var(--mono);color:var(--ok)}
.mdb{background:var(--s2);border:1px solid var(--b2);border-radius:var(--r);padding:14px;
  font-family:var(--mono);font-size:10px;color:var(--t2);white-space:pre-wrap;word-break:break-all;
  max-height:58vh;overflow-y:auto;line-height:1.6}
.brow{display:flex;gap:8px;margin-bottom:10px}
.bsm{padding:7px 14px;font-size:11px}
.bcp{background:rgba(0,229,160,.1);color:var(--ok);border:1px solid rgba(0,229,160,.2)}
.bdl{background:rgba(68,136,255,.1);color:var(--info);border:1px solid rgba(68,136,255,.2)}
.empty{text-align:center;padding:48px 24px;color:var(--t3)}
.ei{font-size:28px;margin-bottom:12px;opacity:.4}.et{font-size:14px;font-weight:600;color:var(--t2);margin-bottom:6px}
.ll{font-family:var(--mono);font-size:10px;color:var(--t3);padding:1px 0;border-bottom:1px solid rgba(255,255,255,.02)}
.ll.ok{color:var(--ok)}.ll.err{color:var(--err)}.ll.w{color:var(--warn)}.ll.inf{color:var(--ac)}
.fr{display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap}
.fb{font-size:10px;font-family:var(--mono);padding:3px 9px;border-radius:4px;
  border:1px solid var(--b2);background:none;color:var(--t2);cursor:pointer}
.fb.on{border-color:var(--ac);color:var(--ac);background:rgba(224,64,251,.07)}
.tests-editor{background:var(--s2);border:1px solid var(--b2);border-radius:var(--r);
  padding:12px;font-family:var(--mono);font-size:10px;color:var(--tx);width:100%;
  min-height:300px;resize:vertical;outline:none;line-height:1.6}
.tests-editor:focus{border-color:rgba(224,64,251,.4)}
.diff-pill{font-size:9px;font-family:var(--mono);padding:2px 8px;border-radius:4px;
  background:rgba(0,229,160,.1);color:var(--ok);margin-left:6px}
.diff-pill.new{background:rgba(68,136,255,.12);color:var(--info)}
</style>
@endsection

@section('content')
<div class="page-hdr">
    <div class="page-title">Spider QA v3 — Auto-sync</div>
    <div class="tb-badge" style="border:1px solid var(--ac); padding:2px 10px; font-family:var(--mono); font-size:10px; border-radius:4px; color:var(--ac)">H22 · H23</div>
</div>

<div class="spider-layout">
<aside class="spider-sidebar">
  <div class="sl">Targets</div>
  <div class="f"><label>SuperAdmin URL</label><input id="u-super" value="http://localhost:8000"></div>
  <div class="f"><label>Tenant URL</label><input id="u-tenant" value="http://demo.localhost:8000"></div>
  <div class="f"><label>SA Email</label><input id="sa-email" value="admin@benderand.cl"></div>
  <div class="f"><label>SA Password</label><input type="password" id="sa-pass" value="password"></div>
  <div class="f"><label>Tenant Email</label><input id="t-email" value="admin@benderand.cl"></div>
  <div class="f"><label>Tenant Password</label><input type="password" id="t-pass" value="admin1234"></div>

  <div class="sl">Tests cargados</div>
  <div class="sync-box">
    <div class="sync-lbl" id="sync-status">Sin sincronizar</div>
    <div class="sync-stats" id="sync-stats"></div>
  </div>
  <button class="btn btn-sync" id="btn-sync" onclick="syncTests()">⟳ Sync desde Laravel</button>

  <div class="sl">Fases activas</div>
  <div class="tog-row"><span>Autenticación</span><label class="tog"><input type="checkbox" id="c-auth" checked><span class="tsl"></span></label></div>
  <div class="tog-row"><span>API Endpoints</span><label class="tog"><input type="checkbox" id="c-api" checked><span class="tsl"></span></label></div>
  <div class="tog-row"><span>Permisos / Roles</span><label class="tog"><input type="checkbox" id="c-roles" checked><span class="tsl"></span></label></div>
  <div class="tog-row"><span>Base de datos</span><label class="tog"><input type="checkbox" id="c-db" checked><span class="tsl"></span></label></div>
  <div class="tog-row"><span>Setup Tenant</span><label class="tog"><input type="checkbox" id="c-tenant" checked><span class="tsl"></span></label></div>
  <div class="tog-row"><span>UI / Frontend</span><label class="tog"><input type="checkbox" id="c-ui" checked><span class="tsl"></span></label></div>
  <div class="tog-row"><span>Tests del JSON</span><label class="tog"><input type="checkbox" id="c-json" checked><span class="tsl"></span></label></div>

  <button class="btn btn-run" id="btn-run" onclick="startCrawl()">▶ Iniciar Spider</button>
  <button class="btn btn-stop" id="btn-stop" onclick="stopCrawl()">■ Detener</button>
  <button class="btn btn-exp" id="btn-exp" onclick="st('md')">⬇ Export MD</button>

  <div class="pw" id="pw">
    <div class="pl"><span id="pl">Listo</span><span id="pp">0%</span></div>
    <div class="pb"><div class="pf" id="pf"></div></div>
  </div>
  <div class="kpis">
    <div class="kpi kp"><div class="kpi-v" id="kp">0</div><div class="kpi-l">PASS</div></div>
    <div class="kpi kf"><div class="kpi-v" id="kf">0</div><div class="kpi-l">FAIL</div></div>
    <div class="kpi kw"><div class="kpi-v" id="kw">0</div><div class="kpi-l">WARN</div></div>
    <div class="kpi kb"><div class="kpi-v" id="kb">0</div><div class="kpi-l">BUGS</div></div>
  </div>
</aside>
<div class="spider-main">
  <div class="tabs">
    <div class="tab on" id="tab-r" onclick="st('r')">Resultados</div>
    <div class="tab" id="tab-b" onclick="st('b')">Bugs <span id="bct"></span></div>
    <div class="tab" id="tab-l" onclick="st('l')">Log</div>
    <div class="tab" id="tab-tests" onclick="st('tests')">Tests JSON <span id="json-ct" class="diff-pill new" style="display:none"></span></div>
    <div class="tab" id="tab-md" onclick="st('md')">Export MD</div>
  </div>

  <div class="pane on" id="pane-r">
    <div class="empty" id="er"><div class="ei">◈</div><div class="et">Spider v3 en espera</div>
      <div>Haz clic en "Sync desde Laravel" para cargar los tests, luego ejecuta el spider</div></div>
    <div class="fr" id="fr" style="display:none">
      <button class="fb on" onclick="filt(this,'all')">Todo</button>
      <button class="fb" onclick="filt(this,'fail')">FAIL</button>
      <button class="fb" onclick="filt(this,'pass')">PASS</button>
      <button class="fb" onclick="filt(this,'warn')">WARN</button>
      <button class="fb" onclick="filt(this,'auto')">AUTO</button>
    </div>
    <div id="rl"></div>
  </div>

  <div class="pane" id="pane-b">
    <div class="empty" id="eb"><div class="ei">✓</div><div class="et">Sin bugs</div></div>
    <div id="bl"></div>
  </div>

  <div class="pane" id="pane-l"><div id="ll"></div></div>

  <div class="pane" id="pane-tests">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <div style="flex:1;font-size:12px;color:var(--t2)">
        Editor de <code style="color:var(--ac)">spider_tests.json</code> —
        agrega o edita tests sin tocar el código del spider
      </div>
      <button class="btn bsm" style="background:rgba(0,229,160,.1);color:var(--ok);border:1px solid rgba(0,229,160,.2)" onclick="saveTests()">Guardar</button>
      <button class="btn bsm" style="background:rgba(68,136,255,.1);color:var(--info);border:1px solid rgba(68,136,255,.2)" onclick="syncTests()">⟳ Re-sync</button>
    </div>
    <textarea class="tests-editor" id="tests-editor" placeholder="Haz sync primero para cargar los tests..."></textarea>
    <div style="margin-top:8px;font-size:10px;color:var(--t3)">
      Formato: JSON con secciones http_checks, api_sa_checks, api_tenant_checks, ui_checks.
      Los tests con "auto":true fueron descubiertos automáticamente desde route:list.
      Los tests con "activo":false se omiten en el run.
    </div>
  </div>

  <div class="pane" id="pane-md">
    <div class="empty" id="emd"><div class="ei">⬇</div><div class="et">Sin resultados</div></div>
    <div id="mds" style="display:none">
      <div class="brow">
        <button class="btn bsm bcp" onclick="cpMd()">Copiar MD</button>
        <button class="btn bsm bdl" onclick="dlMd()">Descargar .md</button>
      </div>
      <div class="mdb" id="mdo"></div>
    </div>
  </div>
</div>
</div>
@endsection

@section('extra_js')
<script>
const S={run:false,stop:false,pass:0,fail:0,warn:0,bugs:[],res:[],logs:[],saT:null,tT:null,bseq:1,tests:null}
const g=id=>document.getElementById(id)
const cfg=id=>g(id).value.trim()
const chk=id=>g(id).checked
const sleep=ms=>new Promise(r=>setTimeout(r,ms))

function st(t){
  document.querySelectorAll('.pane').forEach(p=>p.classList.remove('on'))
  document.querySelectorAll('.tab').forEach(tb=>tb.classList.remove('on'))
  g('pane-'+t).classList.add('on'); g('tab-'+t).classList.add('on')
}
function log(msg,type=''){
  const el=g('ll'),d=document.createElement('div')
  d.className='ll'+(type?' '+type:'')
  d.textContent='['+new Date().toLocaleTimeString()+'] '+msg
  el.appendChild(d); el.scrollTop=el.scrollHeight
  S.logs.push({ts:new Date().toISOString(),msg,type})
}
function setP(lbl,pct){ g('pl').textContent=lbl; g('pp').textContent=Math.round(pct)+'%'; g('pf').style.width=pct+'%' }
function updK(){
  g('kp').textContent=S.pass; g('kf').textContent=S.fail
  g('kw').textContent=S.warn; g('kb').textContent=S.bugs.length
  const bc=g('bct'); bc.textContent=S.bugs.length>0?' ('+S.bugs.length+')':''; bc.style.color=S.bugs.length>0?'var(--err)':''
}
function addR(status,title,detail,url,fix,src=''){
  S.res.push({status,title,detail,url,fix,src,ts:new Date().toISOString()})
  if(status==='pass') S.pass++; else if(status==='fail') S.fail++; else if(status==='warn') S.warn++
  updK(); g('er').style.display='none'; g('fr').style.display='flex'
  const el=document.createElement('div')
  el.className='ri '+status; el.dataset.s=status
  el.innerHTML=`<div class="rist">${status.toUpperCase()}</div><div class="rib">
    <div class="rit">${title}</div>
    ${detail?'<div class="rid">'+detail+'</div>':''}
    ${url?'<div class="riu">'+url+'</div>':''}
    ${fix?'<div class="rif">→ '+fix+'</div>':''}
    ${src?'<div class="ri-src">src: '+src+'</div>':''}
  </div>`
  g('rl').appendChild(el); el.scrollIntoView({block:'nearest'})
}
function addBug(tipo,capa,prio,desc,detail,fix,url){
  const id='BUG-SP-'+String(S.bseq++).padStart(3,'0')
  S.bugs.push({id,tipo,capa,prio,desc,detail,fix,url,ts:new Date().toISOString()})
  updK(); g('eb').style.display='none'
  const tc={'E-AUTH':'ta','E-HTTP':'th','E-DATA':'td','E-UI':'tu','E-CONFIG':'tc','E-PERM':'th','E-REDIRECT':'tc'}
  const pc={critico:'tpc',alto:'tpa',medio:'tpm'}
  const el=document.createElement('div'); el.className='bc'
  el.innerHTML=`<div class="bh"><span class="bid">${id}</span>
    <span class="tg ${tc[tipo]||'th'}">${tipo}</span>
    <span class="tg" style="background:rgba(136,136,160,.1);color:var(--t2)">${capa}</span>
    <span class="tg ${pc[prio]||'tpm'}">${prio}</span>
  </div>
  <div class="bd">${desc}</div>
  ${detail?'<div class="bdet">'+detail+'</div>':''}
  ${fix?'<div class="bfix">FIX → '+fix+'</div>':''}`
  g('bl').appendChild(el)
}
function filt(btn,type){
  document.querySelectorAll('.fb').forEach(b=>b.classList.remove('on')); btn.classList.add('on')
  document.querySelectorAll('.ri').forEach(el=>{ el.style.display=(type==='all'||el.dataset.s===type)?'':'none' })
}

// ── SYNC: carga tests desde Laravel /api/spider/sync ─────────────────────────
async function syncTests(){
  const SU=cfg('u-super')
  g('btn-sync').disabled=true; g('btn-sync').textContent='⟳ Sincronizando...'
  g('sync-status').textContent='Sincronizando con Laravel...'
  log('Sync tests desde /api/spider/sync','inf')

  try{
    // Necesita token SA para auth
    if(!S.saT){
      // Intento login silencioso
      const r=await fetch(SU+'/api/superadmin/login',{method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json'},
        body:JSON.stringify({email:cfg('sa-email'),password:cfg('sa-pass')})})
      const d=await r.json()
      if(d.token) S.saT=d.token
    }

    const r=await fetch(SU+'/api/spider/sync',{
      method:'POST',
      headers:{'Content-Type':'application/json','Accept':'application/json',
        ...(S.saT?{'Authorization':'Bearer '+S.saT}:{})}
    })

    if(!r.ok){
      throw new Error('HTTP '+r.status+' — SpiderController no instalado (BUG-010)')
    }

    const d=await r.json()
    S.tests=d.tests

    // Actualizar UI
    const meta=d.tests?._meta||{}
    const total=d.after_total||0
    const added=d.new_tests||0

    g('sync-status').textContent=`${total} tests · última sync ${new Date().toLocaleTimeString()}`
    const statsEl=g('sync-stats')
    statsEl.innerHTML=''
    for(const[sec,tests] of Object.entries(d.tests||{})){
      if(sec==='_meta'||!Array.isArray(tests)||tests.length===0) continue
      const active=tests.filter(t=>t.activo!==false).length
      const pill=document.createElement('span'); pill.className='sstat'
      pill.textContent=sec.replace('_checks','').replace('api_','').replace('_',' ')+': '+active
      statsEl.appendChild(pill)
    }
    if(added>0){
      const np=document.createElement('span'); np.className='sstat'
      np.style.background='rgba(0,229,160,.12)'; np.style.color='var(--ok)'
      np.textContent='+'+added+' nuevos'
      statsEl.appendChild(np)
      g('json-ct').textContent='+'+added; g('json-ct').style.display=''
    }

    // Mostrar en editor
    g('tests-editor').value=JSON.stringify(d.tests,null,2)
    log('Sync OK: '+d.before_total+' → '+d.after_total+' tests (+'+d.new_tests+')')

    if(d.diff && Object.keys(d.diff).length>0){
      log('Nuevas rutas: '+JSON.stringify(d.diff),'ok')
    }
  }catch(e){
    g('sync-status').textContent='Error: '+e.message
    log('Sync error: '+e.message,'err')
    // Fallback: cargar desde /api/spider/tests (solo lectura)
    try{
      const r2=await fetch(SU+'/api/spider/tests',{
        headers:{...(S.saT?{'Authorization':'Bearer '+S.saT}:{}),'Accept':'application/json'}
      })
      if(r2.ok){ S.tests=await r2.json(); g('tests-editor').value=JSON.stringify(S.tests,null,2) }
    }catch(e2){}
  }

  g('btn-sync').disabled=false; g('btn-sync').textContent='⟳ Sync desde Laravel'
}

async function saveTests(){
  const SU=cfg('u-super')
  try{
    const tests=JSON.parse(g('tests-editor').value)
    const r=await fetch(SU+'/api/spider/tests',{
      method:'POST',
      headers:{'Content-Type':'application/json','Accept':'application/json',
        ...(S.saT?{'Authorization':'Bearer '+S.saT}:{})},
      body:JSON.stringify({tests})
    })
    const d=await r.json()
    if(d.ok){ S.tests=tests; log('Tests guardados: '+d.total+' total','ok')
    }else log('Error guardando: '+JSON.stringify(d),'err')
  }catch(e){ log('JSON inválido: '+e.message,'err') }
}

// ── PROBE via backend ─────────────────────────────────────────────────────────
async function probe(url, expected){
  const SU=cfg('u-super')
  try{
    const r=await fetch(SU+'/api/spider/probe?url='+encodeURIComponent(url),{
      headers:{...(S.saT?{'Authorization':'Bearer '+S.saT}:{}),'Accept':'application/json'}
    })
    if(r.ok){ const d=await r.json(); return{code:d.code,via:'proxy'} }
  }catch(e){}
  // Fallback fetch directo (misma origin)
  try{
    const r=await fetch(url,{method:'GET',redirect:'manual'})
    return{code:r.status,via:'direct'}
  }catch(e){}
  return{code:null,via:'none',err:'proxy no disponible'}
}

async function apiPost(url,payload){
  try{
    const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(payload)})
    const d=await r.json(); return{code:r.status,data:d}
  }catch(e){return{code:0,data:null,err:e.message}}
}
async function apiFetch(url,token){
  try{
    const h={'Accept':'application/json'}
    if(token) h['Authorization']='Bearer '+token
    const r=await fetch(url,{headers:h}); return{code:r.status}
  }catch(e){return{code:0,err:e.message}}
}
function codeMatch(actual,expected){
  if(actual===null||actual===undefined) return false
  return String(expected).split('|').some(e=>String(actual)===e)
}

// ── FASES HARDCODED (siempre corren) ─────────────────────────────────────────
async function phaseAuth(){
  if(!chk('c-auth')) return
  log('── AUTH ──','inf'); setP('Autenticación...',5)
  const SU=cfg('u-super'),TU=cfg('u-tenant')
  const sa=await apiPost(SU+'/api/superadmin/login',{email:cfg('sa-email'),password:cfg('sa-pass')})
  if(sa.code && sa.code < 400 && sa.data && sa.data.token){ S.saT=sa.data.token; addR('pass','SA login OK','HTTP '+sa.code,SU+'/api/superadmin/login','','auth')
  }else{ addR('fail','SA login falla','HTTP '+sa.code,SU+'/api/superadmin/login','php artisan db:seed --class=SuperAdminSeeder','auth')
    addBug('E-AUTH','db','critico','SA login no devuelve token','HTTP '+sa.code,'php artisan db:seed --class=SuperAdminSeeder',SU+'/api/superadmin/login') }
  if(S.stop) return
  const t=await apiPost(TU+'/api/login',{email:cfg('t-email'),password:cfg('t-pass')})
  if(t.code && t.code < 400 && t.data && t.data.token){ S.tT=t.data.token; addR('pass','Tenant login OK','HTTP '+t.code,TU+'/api/login','','auth')
  }else{ addR('fail','Tenant login falla','HTTP '+t.code+' '+JSON.stringify(t.data||{}).substring(0,100),TU+'/api/login','bash tests/diagnose_tenant.sh','auth')
    addBug('E-AUTH','db','critico','Tenant login falla','HTTP '+t.code,'bash tests/diagnose_tenant.sh',TU+'/api/login') }
}
async function phaseRoles(){
  if(!chk('c-roles')) return
  log('── ROLES ──','inf'); setP('Permisos...',45)
  const SU=cfg('u-super'),TU=cfg('u-tenant')
  const noAuth=[[TU+'/api/dashboard','Tenant /api/dashboard'],[SU+'/api/superadmin/dashboard','SA /api/superadmin/dashboard']]
  for(const[url,lbl] of noAuth){
    if(S.stop) return
    const r=await apiFetch(url,null)
    if(r.code===401||r.code===403) addR('pass','Sin token: '+lbl+' protegido','HTTP '+r.code,url,'','roles')
    else{ addR('fail','Sin token: '+lbl+' NO protegido','HTTP '+r.code,url,'middleware auth:sanctum faltante','roles')
      addBug('E-PERM','laravel','critico','Ruta sin auth: '+url,'HTTP '+r.code,'Agregar ->middleware("auth:sanctum")',url) }
  }
  if(S.tT){
    const r=await apiFetch(SU+'/api/superadmin/dashboard',S.tT)
    if(r.code===401||r.code===403) addR('pass','Cross-tenant bloqueado','HTTP '+r.code,'','','roles')
    else{ addR('fail','Cross-tenant NO bloqueado','HTTP '+r.code,SU+'/api/superadmin/dashboard','Verificar CentralAuthController','roles')
      addBug('E-PERM','laravel','critico','Token tenant accede a SA','HTTP '+r.code,'CentralAuthController guard',SU+'/api/superadmin/dashboard') }
  }
}
async function phaseDB(){
  if(!chk('c-db')) return
  log('── DB ──','inf'); setP('DB...',60)
  const SU=cfg('u-super')
  if(!S.saT){ addR('warn','DB: sin token SA','Skip — SA login falló'); return }
  try{
    const r=await fetch(SU+'/api/spider/db-check',{headers:{'Authorization':'Bearer '+S.saT,'Accept':'application/json'}})
    if(r.ok){ const d=await r.json()
      ;(d.checks||[]).forEach(c=>{
        addR(c.ok?'pass':'fail','DB: '+c.label,c.detail+(c.fix?' · Fix: '+c.fix:''),'','','db')
        if(!c.ok) addBug('E-DATA','db','alto',c.label,c.detail,c.fix||'')
      }); return }
  }catch(e){}
  addR('warn','DB: /api/spider/db-check no disponible','Instalar SpiderController (BUG-010)','','','db')
}

// ── FASE JSON: corre los tests del spider_tests.json ─────────────────────────
async function phaseJsonTests(){
  if(!chk('c-json')) return
  if(!S.tests){ addR('warn','Tests JSON: no cargados','Haz Sync primero','','','json'); return }
  log('── JSON TESTS ──','inf'); setP('Tests del JSON...',70)
  const SU=cfg('u-super'), TU=cfg('u-tenant')
  const urlMap={super:SU, tenant:TU}

  const sections=['http_checks','api_sa_checks','api_tenant_checks','ui_checks']
  let total=0, done=0

  for(const sec of sections)
    for(const t of S.tests[sec]||[])
      if(t.activo!==false) total++

  for(const sec of sections){
    const tests=(S.tests[sec]||[]).filter(t=>t.activo!==false)
    for(const t of tests){
      if(S.stop) return
      done++
      setP('JSON: '+t.label+' ('+done+'/'+total+')', 70+(done/total)*25)

      const baseUrl=urlMap[t.url_key||'super']||SU
      const url=baseUrl+(t.path||'')

      const isApiAuth = sec==='api_sa_checks'||sec==='api_tenant_checks'
      const token = sec==='api_sa_checks' ? S.saT : sec==='api_tenant_checks' ? S.tT : null

      if(isApiAuth){
        const r=await apiFetch(url, null)
        const expNoAuth=t.expected||401
        if(codeMatch(r.code, String(expNoAuth)))
          addR('pass','['+t.id+'] '+t.label+' sin token','HTTP '+r.code,url,'',sec)
        else{
          addR('fail','['+t.id+'] '+t.label+' sin token','HTTP '+r.code+' (esp '+expNoAuth+')',url,t.fix||'',sec)
          addBug(t.tipo||'E-HTTP',t.capa||'api',t.prio||'medio',t.label+' sin token protección',
            'HTTP '+r.code+' esperado '+expNoAuth,t.fix||'',url)
        }
        if(token && t.expected_with_auth){
          await sleep(50)
          const r2=await apiFetch(url, token)
          if(codeMatch(r2.code, String(t.expected_with_auth||200)))
            addR('pass','['+t.id+'] '+t.label+' con token','HTTP '+r2.code,url,'',sec)
          else{
            addR('fail','['+t.id+'] '+t.label+' con token','HTTP '+r2.code+' (esp '+t.expected_with_auth+')',url,t.fix||'',sec)
            addBug(t.tipo||'E-HTTP',t.capa||'api',t.prio||'medio',t.label+' con token falla',
              'HTTP '+r2.code+' esperado 200',t.fix||'',url)
          }
        }
      }else{
        const p=await probe(url, t.expected||'200')
        if(p.code===null){
          addR('warn','['+t.id+'] '+t.label,'Proxy no disponible: '+p.err,url,'',sec)
        }else if(codeMatch(p.code, String(t.expected||'200'))){
          addR('pass','['+t.id+'] '+t.label+' HTTP '+p.code+(p.via?' via '+p.via:''),url,'',sec)
        }else{
          addR('fail','['+t.id+'] '+t.label+' HTTP '+p.code+' (esp '+t.expected+')',url,t.fix||'',sec)
          addBug(t.tipo||'E-HTTP',t.capa||'ui',t.prio||'bajo',t.label,
            'HTTP '+p.code+' esperado '+t.expected,t.fix||'',url)
        }
      }
      await sleep(80)
    }
  }
}

async function phaseTenant(){
  if(!chk('c-tenant')) return
  log('── TENANT ──','inf'); setP('Setup tenant...', 95)
  const TU=cfg('u-tenant')
  const p=await probe(TU+'/login',200)
  if(p.code===null) addR('warn','Tenant /login — proxy no disponible',p.err||'',TU+'/login','bash tests/diagnose_tenant.sh')
  else if(p.code===200) addR('pass','Tenant /login carga','HTTP '+p.code,TU+'/login','','tenant')
  else{ addR('fail','Tenant /login no carga','HTTP '+p.code,TU+'/login','echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts','tenant')
    addBug('E-CONFIG','config','critico','Tenant URL no alcanzable','HTTP '+p.code,'echo "127.0.0.1 demo.localhost" | sudo tee -a /etc/hosts',TU) }
}

async function phaseUI(){
  if(!chk('c-ui')) return
  log('── UI ──','inf'); setP('UI...',98)
  const SU=cfg('u-super')
  try{
    const r=await fetch(SU+'/superadmin')
    const html=await r.text()
    ;[['login-email','Input email'],['doLogin','Función doLogin'],['login-overlay','Overlay login']].forEach(([sel,lbl])=>{
      if(html.includes(sel)) addR('pass','SA UI: '+lbl,'"'+sel+'" OK',SU+'/superadmin','','ui')
      else{ addR('fail','SA UI: '+lbl,'"'+sel+'" NO encontrado',SU+'/superadmin','Revisar superadmin.blade.php','ui')
        addBug('E-UI','ui','medio',lbl+' faltante','Selector "'+sel+'" no en HTML','Revisar superadmin.blade.php',SU+'/superadmin') }
    })
  }catch(e){ addR('warn','SA UI: no verificable',e.message,SU+'/superadmin','','ui') }
}

function genMd(){
  const now=new Date(), d=now.toISOString().split('T')[0], t=now.toLocaleTimeString()
  const SU=cfg('u-super'), TU=cfg('u-tenant')
  const total=S.pass+S.fail+S.warn, pct=total>0?Math.round(S.pass*100/total):0
  const meta=S.tests?._meta||{}

  let md=`# BenderAnd Spider QA v3 — Reporte
**Generado:** ${d} ${t}
**SuperAdmin:** ${SU} | **Tenant:** ${TU}
**Tests JSON:** ${meta.total_tests||'no cargado'} tests · última sync: ${meta.last_sync||'—'}

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
  if(!S.bugs.length){ md+=`> ✅ Sin bugs.\n\n`
  }else{
    const ord={critico:0,alto:1,medio:2,bajo:3}
    ;[...S.bugs].sort((a,b)=>(ord[a.prio]||3)-(ord[b.prio]||3)).forEach(b=>{
      const em=b.prio==='critico'?'🔴':b.prio==='alto'?'🟠':b.prio==='medio'?'🟡':'⚪'
      md+=`### ${em} ${b.id} — ${b.desc}\n\n| Campo | Valor |\n|---|---|\n`
      md+=`| **Tipo** | \`${b.tipo}\` |\n| **Capa** | \`${b.capa}\` |\n| **Prioridad** | ${b.prio} |\n`
      md+=`| **URL** | \`${b.url||'—'}\` |\n| **Detectado** | ${b.ts} |\n\n`
      if(b.detail) md+=`**Detalle:** ${b.detail}\n\n`
      md+=`**Fix:**\n\`\`\`bash\n${b.fix||'# Analizar causa raíz'}\n\`\`\`\n\n---\n\n`
    })
  }

  md+=`## Todos los Checks (${S.res.length})\n\n`
  S.res.forEach(r=>{
    const s=r.status==='pass'?'✅':r.status==='fail'?'❌':'⚠️'
    md+=`${s} **${r.title}**`
    if(r.detail) md+=` — ${r.detail}`
    if(r.url) md+=`\n   \`${r.url}\``
    if(r.fix) md+=`\n   → ${r.fix}`
    md+='\n\n'
  })
  return md
}

function cpMd(){ navigator.clipboard.writeText(g('mdo').textContent).then(()=>{const b=document.querySelector('.bcp');b.textContent='¡Copiado!';setTimeout(()=>b.textContent='Copiar MD',2000)}) }
function dlMd(){
  const a=document.createElement('a')
  a.href=URL.createObjectURL(new Blob([g('mdo').textContent],{type:'text/markdown'}))
  a.download='benderand_spider_'+new Date().toISOString().split('T')[0]+'.md'; a.click()
}

async function startCrawl(){
  if(S.run) return
  Object.assign(S,{run:true,stop:false,pass:0,fail:0,warn:0,bugs:[],res:[],logs:[],saT:null,tT:null,bseq:1})
  ;['rl','bl','ll'].forEach(id=>g(id).innerHTML='')
  ;['er','eb'].forEach(id=>g(id).style.display='')
  g('mds').style.display='none'; g('emd').style.display=''; g('fr').style.display='none'
  updK(); g('btn-run').disabled=true; g('btn-stop').style.display='flex'
  g('btn-exp').style.display='none'; g('pw').style.display='block'; g('pf').style.background='var(--ac)'
  log('Spider v3 iniciado','inf'); st('r')
  try{
    await phaseAuth();    if(S.stop) return done()
    await sleep(100)
    await phaseRoles();   if(S.stop) return done()
    await sleep(100)
    await phaseDB();      if(S.stop) return done()
    await sleep(100)
    await phaseJsonTests(); if(S.stop) return done()
    await sleep(100)
    await phaseTenant();  if(S.stop) return done()
    await sleep(100)
    await phaseUI()
  }catch(e){ log('Error: '+e.message,'err'); addR('warn','Error',e.message) }
  done()
}
function done(){
  S.run=false; S.stop=false; g('btn-run').disabled=false; g('btn-stop').style.display='none'
  const total=S.pass+S.fail+S.warn, pct=total>0?Math.round(S.pass*100/total):0
  setP('Completado — '+pct+'% OK',100); g('pf').style.background=S.fail>0?'var(--err)':'var(--ok)'
  log('Terminado: '+S.pass+' PASS · '+S.fail+' FAIL · '+S.bugs.length+' bugs',S.fail>0?'err':'ok')
  const md=genMd(); g('mdo').textContent=md; g('mds').style.display='block'; g('emd').style.display='none'
  g('btn-exp').style.display='flex'
  if(S.bugs.length>0) st('b')
}
function stopCrawl(){ S.stop=true; log('Detenido','w'); done() }

window.addEventListener('load', async ()=>{
  const stored=localStorage.getItem('sa_token')
  if(stored){ S.saT=stored; await syncTests() }
})
</script>
@endsection
