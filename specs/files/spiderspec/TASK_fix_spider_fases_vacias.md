# Fix: Spider QA no ejecuta fases al presionar "Iniciar"

## Síntoma

Al presionar "▶ Iniciar Spider" el botón se deshabilita y aparece "■ Detener", pero inmediatamente termina sin ejecutar ningún check. Los contadores PASS/FAIL/WARN/BUGS quedan en 0.

## Causa raíz

En la vista actual `central/spider.blade.php`, la función `startCrawl()` está incompleta. Solo tiene el setup inicial y llama a `done()` directamente, sin ejecutar ninguna fase:

```javascript
// Estado actual — ROTO:
async function startCrawl(){
  if(S.run) return
  Object.assign(S,{run:true,stop:false,pass:0,fail:0,warn:0,bugs:[],res:[],logs:[],bseq:1})
  ;['rl','bl','ll'].forEach(id=>g(id).innerHTML='')
  g('btn-run').disabled=true; g('btn-stop').style.display='flex'
  g('pw').style.display='block'; log('Spider v3 iniciado','inf'); st('r')
  // ← VACÍO — faltan todas las fases
  done()  // ← llama done() inmediatamente
}
```

Además faltan las funciones de las fases: `phaseAuth`, `phaseRoles`, `phaseDB`, `phaseJsonTests`, `phaseTenant`, `phaseUI`, y las helpers `probe`, `apiPost`, `apiFetch`, `codeMatch`, `genMd`, `saveTests`.

Al migrar de superadmin a central, el JS del spider se truncó — solo se pegó el esqueleto pero no el cuerpo completo.

## Fix

Reemplazar el `<script>` completo de `resources/views/central/spider.blade.php` con el JS original del spider. El JS completo está en `specs/files/superadmin.html` (la versión con Spider embebido) y debe incluir:

### Funciones faltantes a restaurar

**1. `phaseAuth()`** — login SA + login tenant:
```javascript
async function phaseAuth(){
  if(!chk('c-auth')) return
  log('── AUTH ──','inf'); setP('Autenticación...',5)
  const SU=cfg('u-super'),TU=cfg('u-tenant')
  const sa=await apiPost(SU+'/api/superadmin/login',{email:cfg('sa-email'),password:cfg('sa-pass')})
  if(sa.code && sa.code < 400 && sa.data && sa.data.token){
    S.saT=sa.data.token; addR('pass','SA login OK','HTTP '+sa.code,SU+'/api/superadmin/login','','auth')
  }else{
    addR('fail','SA login falla','HTTP '+sa.code,SU+'/api/superadmin/login','php artisan db:seed --class=SuperAdminSeeder','auth')
    addBug('E-AUTH','db','critico','SA login no devuelve token','HTTP '+sa.code,'php artisan db:seed --class=SuperAdminSeeder',SU+'/api/superadmin/login')
  }
  if(S.stop) return
  const t=await apiPost(TU+'/api/login',{email:cfg('t-email'),password:cfg('t-pass')})
  if(t.code && t.code < 400 && t.data && t.data.token){
    S.tT=t.data.token; addR('pass','Tenant login OK','HTTP '+t.code,TU+'/api/login','','auth')
  }else{
    addR('fail','Tenant login falla','HTTP '+t.code,TU+'/api/login','bash tests/diagnose_tenant.sh','auth')
    addBug('E-AUTH','db','critico','Tenant login falla','HTTP '+t.code,'bash tests/diagnose_tenant.sh',TU+'/api/login')
  }
}
```

**2. `phaseRoles()`** — verificar protección de rutas sin token

**3. `phaseDB()`** — llamar a `/api/spider/db-check`

**4. `phaseJsonTests()`** — iterar sobre `S.tests` y ejecutar cada check

**5. `phaseTenant()`** — verificar que el dominio tenant responde

**6. `phaseUI()`** — verificar elementos en el HTML de central

**7. Helpers:**
```javascript
async function probe(url, expected){ /* proxy via /api/spider/probe o fetch directo */ }
async function apiPost(url, payload){ /* fetch POST */ }
async function apiFetch(url, token){ /* fetch GET con Authorization */ }
function codeMatch(actual, expected){ /* compara código HTTP */ }
function genMd(){ /* genera reporte markdown */ }
async function saveTests(){ /* POST a /api/spider/tests */ }
```

**8. `startCrawl()` completo:**
```javascript
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
    await phaseAuth();      if(S.stop) return done()
    await sleep(100)
    await phaseRoles();     if(S.stop) return done()
    await sleep(100)
    await phaseDB();        if(S.stop) return done()
    await sleep(100)
    await phaseJsonTests(); if(S.stop) return done()
    await sleep(100)
    await phaseTenant();    if(S.stop) return done()
    await sleep(100)
    await phaseUI()
  }catch(e){ log('Error: '+e.message,'err'); addR('warn','Error',e.message) }
  done()
}
```

**9. `done()` completo:**
```javascript
function done(){
  S.run=false; S.stop=false; g('btn-run').disabled=false; g('btn-stop').style.display='none'
  const total=S.pass+S.fail+S.warn, pct=total>0?Math.round(S.pass*100/total):0
  setP('Completado — '+pct+'% OK',100)
  g('pf').style.background=S.fail>0?'var(--err)':'var(--ok)'
  log('Terminado: '+S.pass+' PASS · '+S.fail+' FAIL · '+S.bugs.length+' bugs',S.fail>0?'err':'ok')
  const md=genMd(); g('mdo').textContent=md
  g('mds').style.display='block'; g('emd').style.display='none'
  g('btn-exp').style.display='flex'
  if(S.bugs.length>0) st('b')
}
```

## Nota sobre phaseAuth y el prompt de contraseña

`phaseAuth` sigue usando `email` + `password` en el fetch para el login de SA. Esto es necesario para el test de autenticación, pero activa el gestor de contraseñas del navegador.

**Solución:** En `phaseAuth`, obtener el token SA usando el endpoint `/central/spider/token` (sesión Laravel) en vez del login con credenciales, y solo usar las credenciales del formulario para testear el login de **tenant** (que es el que realmente necesita testear auth):

```javascript
async function phaseAuth(){
  if(!chk('c-auth')) return
  log('── AUTH ──','inf'); setP('Autenticación...',5)
  const SU=cfg('u-super'), TU=cfg('u-tenant')

  // SA: obtener token desde sesión Laravel (sin enviar password)
  if(!S.saT){
    try{
      const r=await fetch('/central/spider/token',{
        method:'POST',
        headers:{
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept':'application/json'
        }
      })
      const d=await r.json()
      if(d.token){ S.saT=d.token; addR('pass','SA token OK (sesión activa)','','','','auth') }
      else{ addR('fail','SA token falló',JSON.stringify(d),'','','auth') }
    }catch(e){ addR('fail','SA token error',e.message,'','','auth') }
  }

  if(S.stop) return

  // Tenant: login real para testear autenticación
  const t=await apiPost(TU+'/api/login',{email:cfg('t-email'),password:cfg('t-pass')})
  if(t.code && t.code < 400 && t.data && t.data.token){
    S.tT=t.data.token; addR('pass','Tenant login OK','HTTP '+t.code,TU+'/api/login','','auth')
  }else{
    addR('fail','Tenant login falla','HTTP '+t.code,TU+'/api/login','bash tests/diagnose_tenant.sh','auth')
    addBug('E-AUTH','db','critico','Tenant login falla','HTTP '+t.code,'bash tests/diagnose_tenant.sh',TU+'/api/login')
  }
}
```

Esto también resuelve el bug del prompt de contraseña reportado anteriormente.

## Archivo a modificar

`resources/views/central/spider.blade.php` — reemplazar el bloque `<script>` completo con el JS restaurado del archivo `specs/files/superadmin.html` (sección spider), aplicando el cambio de `phaseAuth` descrito arriba.

## Criterio de aceptación

- Presionar "▶ Iniciar Spider" ejecuta las fases secuencialmente con progreso visible
- Los contadores PASS/FAIL/WARN/BUGS se actualizan durante la ejecución
- Al terminar aparece el reporte en la pestaña "Export MD"
- Si hay bugs, cambia automáticamente a la pestaña "Bugs"
- No aparece prompt de guardar contraseña en el navegador
