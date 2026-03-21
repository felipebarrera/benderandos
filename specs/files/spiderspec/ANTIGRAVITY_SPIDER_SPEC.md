# BenderAnd Spider QA v3 — Spec de Protección
**Fecha:** 2026-03-16
**CRÍTICO: Leer antes de tocar spider_v3.html**

---

## ADVERTENCIA PARA EL AGENTE

**NO reemplazar, simplificar ni reescribir `spider_v3.html` ni `spider_v2.html`.**
La versión original tiene 703 líneas. Cualquier versión menor a 600 líneas es una degradación.

El agente anterior generó una versión de ~120 líneas que perdió:
- Fases: auth, roles, DB, tenant, UI, JSON tests
- Export MD para antigravity
- Editor de tests JSON con save/sync
- Panel de bugs clasificados por tipo/prioridad
- Filtros PASS/FAIL/WARN/AUTO
- Botón stop, progress bar, log completo
- Sistema de tokens SA + tenant
- Probe via backend proxy (evita CORS)

**Si necesitas modificar el spider, edita SOLO la sección indicada y mantén todo lo demás.**

---

## Funciones que DEBEN existir en spider_v3.html

```javascript
// Obligatorias — si alguna falta = archivo corrupto
syncTests()      // POST /api/spider/sync → carga tests desde Laravel route:list
saveTests()      // POST /api/spider/tests → persiste ediciones del JSON
probe(url, exp)  // GET /api/spider/probe → HTTP check via backend (evita CORS)
apiPost(url, p)  // POST directo para auth
apiFetch(url, t) // GET con token para APIs
phaseAuth()      // login SA + tenant → obtiene S.saT y S.tT
phaseRoles()     // verifica permisos sin token y cross-tenant
phaseDB()        // GET /api/spider/db-check
phaseJsonTests() // corre tests del spider_tests.json (sección api_sa + api_tenant + http)
phaseTenant()    // verifica URLs del tenant via probe
phaseUI()        // verifica DOM del superadmin
genMd()          // genera reporte MD completo para antigravity
startCrawl()     // orquesta todas las fases
done()           // finaliza, genera MD, muestra bugs
stopCrawl()      // detiene el crawl
```

## Tabs que DEBEN existir

```
Resultados  → pane-r  (con filtros PASS/FAIL/WARN/AUTO)
Bugs        → pane-b  (cards con tipo/capa/prioridad/fix)
Log         → pane-l  (líneas con timestamp y color)
Tests JSON  → pane-tests (editor textarea + botón Guardar + Re-sync)
Export MD   → pane-md (textarea con botón Copiar + Descargar .md)
```

## Estado global que DEBE existir

```javascript
const S = {
  run: false,    // crawl en ejecución
  stop: false,   // señal de stop
  pass: 0,       // contador PASS
  fail: 0,       // contador FAIL
  warn: 0,       // contador WARN
  bugs: [],      // lista de bugs detectados
  res: [],       // lista de resultados
  logs: [],      // lista de logs
  saT: null,     // token SuperAdmin
  tT: null,      // token Tenant
  bseq: 1,       // secuencia de bug IDs
  tests: null    // datos del spider_tests.json cargado
}
```

---

## Secciones que SÍ puede modificar el agente

### Agregar una nueva fase al crawl
Agregar DESPUÉS de `await phaseUI()` en `startCrawl()`:
```javascript
await phaseNueva();
```

Y definir la función:
```javascript
async function phaseNueva(){
  if(!chk('c-nueva')) return
  log('── NUEVA ──','inf')
  // ... lógica ...
}
```

### Agregar un toggle de fase en el sidebar
Agregar en el `<aside class="sidebar">` después de los toggles existentes:
```html
<div class="tog-row"><span>Nueva fase</span>
  <label class="tog"><input type="checkbox" id="c-nueva" checked><span class="tsl"></span></label>
</div>
```

### Agregar un tipo de test nuevo al JSON
Editar `spider_tests.json` directamente o via el editor en la tab "Tests JSON".
No requiere tocar `spider_v3.html`.

### Agregar un endpoint nuevo al SpiderController
Agregar en `SpiderController.php` y registrar en `routes/api.php`.
No requiere tocar `spider_v3.html`.

---

## Cómo instalar el spider correcto (703 líneas)

```bash
# El archivo correcto está en outputs como spider_v3.html (703 líneas)
# Verificar antes de instalar:
wc -l ~/Downloads/spider_v3.html
# Esperado: ~703

# Si el archivo tiene < 600 líneas = versión degradada — NO instalar
# Usar la versión original del output del sistema

# Instalar:
docker cp ~/Downloads/spider_v3.html \
  benderandos_app:/app/resources/views/superadmin/spider.blade.php

# Verificar:
curl -s http://localhost:8000/superadmin/spider | grep -c "genMd\|phaseAuth\|syncTests\|pane-tests"
# Esperado: 4 (las 4 funciones críticas presentes)
```

---

## Verificación de integridad del spider

```bash
# Contar funciones críticas en el archivo instalado
docker exec benderandos_app grep -c \
  "phaseAuth\|phaseRoles\|phaseDB\|phaseJsonTests\|phaseTenant\|phaseUI\|genMd\|syncTests\|saveTests\|stopCrawl" \
  /app/resources/views/superadmin/spider.blade.php

# Esperado: 10 (una por función)
# Si es menor = archivo degradado — reemplazar con el original
```

---

## Comparación versión correcta vs versión degradada

| Feature | v3 correcta (703L) | Degradada (120L) |
|---|---|---|
| Fases de test | 6 fases | 2 fases (db + probe básico) |
| Auth SA + tenant | ✅ | ❌ |
| Cross-tenant check | ✅ | ❌ |
| Export MD antigravity | ✅ | ❌ |
| Editor tests JSON | ✅ | ❌ |
| Panel de bugs | ✅ | ❌ |
| Filtros resultados | ✅ | ❌ |
| Botón stop | ✅ | ❌ |
| Progress bar | ✅ | ❌ |
| Auto-sync al cargar | ✅ | ❌ |
| Tokens SA + tenant | ✅ | ❌ solo sa |
| Probe via backend | ✅ | ✅ (básico) |

---
*BenderAnd Spider QA v3 — Spec de Protección · 2026-03-16*
