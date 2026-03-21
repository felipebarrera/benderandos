# BenderAnd — Spider QA v3 para Antigravity
**Fecha:** 2026-03-16
**Scope:** Instalar Spider v3 + SpiderController + sync automático de tests

---

## Identidad del agente

Eres un agente senior de desarrollo para BenderAnd ERP. Tu tarea es instalar el sistema Spider QA v3 completo — el crawl de pruebas automático con auto-sync desde route:list de Laravel. Ejecuta cada paso en orden, verifica antes de continuar, y reporta al final.

---

## Stack de referencia

```
Laravel 11 · PHP 8.2 · stancl/tenancy v3 · PostgreSQL 16
App container:  benderandos_app  ruta: /app
DB container:   benderandos_pg   user: benderand  db: benderand
Redis:          benderandos_redis
Host trabajo:   ~/trabajo/proyectos/src/benderandos
```

## Comandos Docker

```bash
docker exec benderandos_app php artisan [cmd]
docker exec benderandos_app php artisan tinker --execute="[código]"
docker exec benderandos_pg psql -U benderand -d benderand -c "[SQL]"
docker exec benderandos_app tail -100 /app/storage/logs/laravel.log
docker cp [archivo_host] benderandos_app:/app/[ruta_destino]
```

---

## TAREA 1 — Instalar SpiderController en Laravel

### Archivo: `app/Http/Controllers/SpiderController.php`

```bash
docker cp ~/trabajo/proyectos/src/benderandos/tests/SpiderController.php \
  benderandos_app:/app/app/Http/Controllers/SpiderController.php
```

**Verificar que el archivo llegó:**
```bash
docker exec benderandos_app head -5 /app/app/Http/Controllers/SpiderController.php
# Esperado: <?php namespace App\Http\Controllers;
```

**Si docker cp no funciona, crear el archivo directo:**
```bash
docker exec benderandos_app bash -c "cat > /app/app/Http/Controllers/SpiderController.php << 'PHP'
<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class SpiderController extends Controller
{
    private string \$testsPath  = '/app/tests/spider_tests.json';
    private string \$syncScript = '/app/tests/sync_spider_tests.sh';

    public function probe(Request \$request)
    {
        \$url = \$request->query('url', '');
        if (!filter_var(\$url, FILTER_VALIDATE_URL))
            return response()->json(['code' => 0, 'error' => 'URL inválida'], 422);
        \$host = parse_url(\$url, PHP_URL_HOST);
        if (!\in_array(\$host, ['localhost','127.0.0.1','demo.localhost']) &&
            !str_ends_with(\$host, '.localhost'))
            return response()->json(['code' => 0, 'error' => 'Solo URLs locales'], 403);
        \$p = new Process(['curl','-s','-o','/dev/null','-w','%{http_code}','--max-time','8','-L',\$url]);
        \$p->run();
        return response()->json(['code' => (int) trim(\$p->getOutput()), 'via' => 'proxy']);
    }

    public function dbCheck(Request \$request)
    {
        \$checks = [];
        \$checks[] = ['label'=>'super_admins tiene registros','ok'=>\DB::table('super_admins')->count()>0,
            'detail'=>\DB::table('super_admins')->count().' registros','fix'=>'php artisan db:seed --class=SuperAdminSeeder'];
        \$checks[] = ['label'=>'plan_modulos con datos','ok'=>\DB::table('plan_modulos')->count()>0,
            'detail'=>\DB::table('plan_modulos')->count().' módulos','fix'=>'php artisan db:seed --class=PlanModulosSeeder'];
        \$checks[] = ['label'=>'Tabla tenants accesible','ok'=>true,
            'detail'=>\DB::table('tenants')->count().' tenants','fix'=>''];
        \$br = \DB::selectOne(\"SELECT COUNT(*) as c FROM information_schema.tables WHERE table_name='bug_reports' AND table_schema='public'\");
        \$checks[] = ['label'=>'Tabla bug_reports existe','ok'=>(\$br->c??0)>0,
            'detail'=>'Sistema de tracking','fix'=>'Ver BUG-008 en MD maestro'];
        \$checks[] = ['label'=>'Dominios registrados','ok'=>\DB::table('domains')->count()>0,
            'detail'=>\DB::table('domains')->count().' dominios','fix'=>'Crear dominio para tenant demo'];
        return response()->json(['checks' => \$checks]);
    }

    public function syncTests(Request \$request)
    {
        if (!file_exists(\$this->syncScript))
            return response()->json(['ok'=>false,'error'=>'sync_spider_tests.sh no encontrado',
                'fix'=>'cp sync_spider_tests.sh /app/tests/'],404);
        \$before = \$this->readTests();
        \$bc = \$this->countTests(\$before);
        \$p = new Process(['bash', \$this->syncScript]);
        \$p->setTimeout(30); \$p->run();
        \$after = \$this->readTests();
        \$ac = \$this->countTests(\$after);
        \$diff = [];
        foreach (['http_checks','api_sa_checks','api_tenant_checks','auth_checks','db_checks','ui_checks'] as \$s) {
            \$b = count(\$before[\$s]??[]); \$a = count(\$after[\$s]??[]);
            if (\$a !== \$b) \$diff[\$s] = ['before'=>\$b,'after'=>\$a,'added'=>\$a-\$b];
        }
        return response()->json(['ok'=>\$p->isSuccessful(),'output'=>\$p->getOutput(),
            'before_total'=>\$bc,'after_total'=>\$ac,'new_tests'=>\$ac-\$bc,'diff'=>\$diff,'tests'=>\$after]);
    }

    public function getTests(Request \$request)
    {
        return response()->json(\$this->readTests());
    }

    public function saveTests(Request \$request)
    {
        \$data = \$request->validate(['tests' => 'required|array']);
        \$current = \$this->readTests();
        foreach (\$data['tests'] as \$section => \$tests)
            if (is_array(\$tests)) \$current[\$section] = \$tests;
        \$current['_meta']['updated'] = now()->toISOString();
        file_put_contents(\$this->testsPath, json_encode(\$current, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        return response()->json(['ok'=>true,'total'=>\$this->countTests(\$current)]);
    }

    private function readTests(): array
    {
        if (!file_exists(\$this->testsPath))
            return ['_meta'=>['version'=>'1.0','total_tests'=>0],
                'http_checks'=>[],'api_sa_checks'=>[],'api_tenant_checks'=>[],
                'auth_checks'=>[],'db_checks'=>[],'ui_checks'=>[]];
        return json_decode(file_get_contents(\$this->testsPath), true) ?? [];
    }

    private function countTests(array \$data): int
    {
        \$t = 0;
        foreach (['http_checks','api_sa_checks','api_tenant_checks','auth_checks','db_checks','ui_checks'] as \$s)
            \$t += count(\$data[\$s]??[]);
        return \$t;
    }
}
PHP"
```

---

## TAREA 2 — Registrar rutas del Spider en `routes/api.php`

```bash
# Buscar el grupo auth:sanctum existente en api.php
docker exec benderandos_app grep -n "auth:sanctum\|middleware" /app/routes/api.php | head -20
```

**Agregar las rutas spider dentro del grupo `auth:sanctum` existente:**
```bash
docker exec benderandos_app bash -c "
# Leer el archivo actual
cat /app/routes/api.php | grep -c 'spider'
"
# Si devuelve 0 = rutas spider no registradas todavía
```

**Agregar al final de `routes/api.php` antes del último `});`:**
```php
// Spider QA — solo superadmin autenticado
Route::middleware(['auth:sanctum'])->prefix('spider')->group(function () {
    Route::get('/probe',    [\App\Http\Controllers\SpiderController::class, 'probe']);
    Route::get('/db-check', [\App\Http\Controllers\SpiderController::class, 'dbCheck']);
    Route::post('/sync',    [\App\Http\Controllers\SpiderController::class, 'syncTests']);
    Route::get('/tests',    [\App\Http\Controllers\SpiderController::class, 'getTests']);
    Route::post('/tests',   [\App\Http\Controllers\SpiderController::class, 'saveTests']);
});
```

```bash
# Aplicar con tinker (forma segura sin tocar el archivo manualmente):
docker exec benderandos_app bash -c "
echo \"
Route::middleware(['auth:sanctum'])->prefix('spider')->group(function () {
    Route::get('/probe',    [\\\App\\\Http\\\Controllers\\\SpiderController::class, 'probe']);
    Route::get('/db-check', [\\\App\\\Http\\\Controllers\\\SpiderController::class, 'dbCheck']);
    Route::post('/sync',    [\\\App\\\Http\\\Controllers\\\SpiderController::class, 'syncTests']);
    Route::get('/tests',    [\\\App\\\Http\\\Controllers\\\SpiderController::class, 'getTests']);
    Route::post('/tests',   [\\\App\\\Http\\\Controllers\\\SpiderController::class, 'saveTests']);
});
\" >> /app/routes/api.php
"

# Limpiar cache de rutas
docker exec benderandos_app php artisan route:clear
docker exec benderandos_app php artisan config:clear
```

**Verificar rutas registradas:**
```bash
docker exec benderandos_app php artisan route:list | grep spider
# Esperado: GET /api/spider/probe, GET /api/spider/db-check, POST /api/spider/sync, etc.
```

---

## TAREA 3 — Instalar vista Blade del Spider

```bash
# Crear carpeta si no existe
docker exec benderandos_app mkdir -p /app/resources/views/superadmin

# Copiar spider_v3.html como vista Blade
docker cp ~/trabajo/proyectos/src/benderandos/tests/spider_v3.html \
  benderandos_app:/app/resources/views/superadmin/spider.blade.php

# Verificar
docker exec benderandos_app ls -la /app/resources/views/superadmin/
```

**Agregar ruta web en `routes/web.php`:**
```bash
# Verificar si ya existe
docker exec benderandos_app grep -n "spider" /app/routes/web.php

# Si no existe, agregar:
docker exec benderandos_app bash -c "
echo \"Route::get('/superadmin/spider', function() {
    return view('superadmin.spider');
});\" >> /app/routes/web.php
"

docker exec benderandos_app php artisan route:clear
```

**Verificar:**
```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/superadmin/spider
# Esperado: 200
```

---

## TAREA 4 — Instalar scripts de testing en el host

```bash
cd ~/trabajo/proyectos/src/benderandos

# sync_spider_tests.sh (auto-descubre rutas desde route:list)
# diagnose_tenant.sh   (diagnostica y repara el tenant)
# gen_credentials.sh   (genera credentials.env)
# helpers_v2.sh        (funciones base H22/H23)
# run_all_v2.sh        (suite completa)

chmod +x tests/*.sh

# Crear carpeta tests/results si no existe
mkdir -p tests/results tests/errors tests/report

# Agregar credentials.env a .gitignore
grep -q "credentials.env" .gitignore || echo "tests/credentials.env" >> .gitignore
grep -q "spider_tests.json" .gitignore || echo "# tests/spider_tests.json" >> .gitignore

# Primer sync para generar spider_tests.json
bash tests/sync_spider_tests.sh
```

**Verificar que se generó spider_tests.json:**
```bash
cat tests/spider_tests.json | python3 -c "
import json,sys
d=json.load(sys.stdin)
print('Total tests:', d['_meta']['total_tests'])
print('Rutas SA:', len(d.get('api_sa_checks',[])))
print('Rutas Tenant:', len(d.get('api_tenant_checks',[])))
print('HTTP checks:', len(d.get('http_checks',[])))
"
```

---

## TAREA 5 — Agregar link Spider QA en sidebar del superadmin

**Buscar el archivo HTML del superadmin:**
```bash
docker exec benderandos_app find /app -name "superadmin.html" -not -path "*/vendor/*" 2>/dev/null
# También puede estar en:
ls ~/trabajo/proyectos/src/benderandos/specs/files/superadmin.html
```

**Agregar en la sección nav (buscar el bloque .nav-section):**
```bash
# Verificar si ya tiene el link
grep -n "spider\|Spider" ~/trabajo/proyectos/src/benderandos/specs/files/superadmin.html

# Si no tiene, agregar el link en el sidebar nav
# Buscar la línea con "nav-section-lbl" y agregar después:
```

```html
<!-- Agregar en specs/files/superadmin.html dentro del .nav-section Principal -->
<a class="nav-item" href="/superadmin/spider">
  <span class="nav-icon">◈</span> Spider QA
</a>
```

```bash
# Aplicar con sed (buscar el nav-item de Dashboard y agregar el Spider después):
sed -i 's|<a class="nav-item active" onclick="goTo(.dashboard.)">◈ Dashboard</a>|<a class="nav-item active" onclick="goTo(\x27dashboard\x27)">◈ Dashboard</a>\n      <a class="nav-item" href="/superadmin/spider"><span>◈</span> Spider QA</a>|' \
  ~/trabajo/proyectos/src/benderandos/specs/files/superadmin.html

# Verificar
grep -n "Spider\|spider" ~/trabajo/proyectos/src/benderandos/specs/files/superadmin.html
```

---

## TAREA 6 — Generar credentials.env inicial

```bash
cd ~/trabajo/proyectos/src/benderandos
bash tests/gen_credentials.sh

# Verificar contenido
cat tests/credentials.env

# Esperado: SA_URL, SA_EMAIL, TENANT_1_URL, TENANT_1_USER_1_EMAIL, SA_TOKEN, etc.
```

---

## TAREA 7 — Correr suite completa y verificar

```bash
cd ~/trabajo/proyectos/src/benderandos

# Primero diagnóstico del tenant (fase 0 con auto-fix)
bash tests/diagnose_tenant.sh

# Luego suite completa
bash tests/run_all.sh

# Ver resultado
cat tests/report/$(date +%Y-%m-%d).md
```

**Si hay FAILs, el reporte de antigravity se genera automáticamente en:**
```bash
ls tests/report/antigravity_*.md | tail -1
```

---

## TAREA 8 — Test E2E del Spider desde el browser

```bash
# Obtener token SA
SA_TOKEN=$(curl -s -X POST http://localhost:8000/api/superadmin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@benderand.cl","password":"password"}' | grep -oP '"token":"\K[^"]+')

# Probar cada endpoint del SpiderController
echo "probe:"
curl -s "http://localhost:8000/api/spider/probe?url=http://localhost:8000/superadmin" \
  -H "Authorization: Bearer $SA_TOKEN" | python3 -m json.tool

echo "db-check:"
curl -s "http://localhost:8000/api/spider/db-check" \
  -H "Authorization: Bearer $SA_TOKEN" | python3 -m json.tool

echo "sync:"
curl -s -X POST "http://localhost:8000/api/spider/sync" \
  -H "Authorization: Bearer $SA_TOKEN" \
  -H "Content-Type: application/json" | python3 -c "
import json,sys
d=json.load(sys.stdin)
print('OK:', d.get('ok'))
print('Before:', d.get('before_total'))
print('After:', d.get('after_total'))
print('New:', d.get('new_tests'))
"

echo "tests:"
curl -s "http://localhost:8000/api/spider/tests" \
  -H "Authorization: Bearer $SA_TOKEN" | python3 -c "
import json,sys
d=json.load(sys.stdin)
print('Total tests:', d['_meta'].get('total_tests',0))
"
```

---

## TAREA 9 — Verificar Spider en browser

```bash
# Abrir en browser
echo "Abrir: http://localhost:8000/superadmin/spider"

# O verificar con lightpanda (sintaxis correcta — posicional sin --url)
lightpanda fetch --dump html http://localhost:8000/superadmin/spider | grep -c "Spider QA\|btn-sync"
# Esperado: número > 0
```

---

## Estado esperado al terminar todas las tareas

```bash
# Verificaciones finales rápidas
echo "=== Estado final Spider QA ==="

echo -n "1. SpiderController existe: "
docker exec benderandos_app test -f /app/app/Http/Controllers/SpiderController.php && echo "OK" || echo "FALTA"

echo -n "2. Rutas spider registradas: "
docker exec benderandos_app php artisan route:list 2>/dev/null | grep -c "spider" | xargs echo "rutas"

echo -n "3. Vista spider.blade.php: "
docker exec benderandos_app test -f /app/resources/views/superadmin/spider.blade.php && echo "OK" || echo "FALTA"

echo -n "4. spider_tests.json: "
test -f ~/trabajo/proyectos/src/benderandos/tests/spider_tests.json && echo "OK" || echo "FALTA"

echo -n "5. credentials.env: "
test -f ~/trabajo/proyectos/src/benderandos/tests/credentials.env && echo "OK" || echo "FALTA"

echo -n "6. Spider page HTTP: "
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/superadmin/spider

echo -n "7. probe endpoint: "
SA_TOKEN=$(curl -s -X POST http://localhost:8000/api/superadmin/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@benderand.cl","password":"password"}' | grep -oP '"token":"\K[^"]+')
curl -s "http://localhost:8000/api/spider/probe?url=http://localhost:8000/superadmin" \
  -H "Authorization: Bearer $SA_TOKEN" | grep -o '"code":[0-9]*'

echo ""
echo "=== Si todo OK = Spider QA v3 operativo ==="
```

---

## Flujo completo del sistema (referencia)

```
DESARROLLO:
  Nueva ruta Laravel → spider detecta en próximo sync automático → nuevo test en JSON

EJECUCIÓN:
  run_all.sh
    FASE 0: diagnose_tenant.sh (verifica + repara tenant)
            gen_credentials.sh (regenera credentials.env)
    FASE 1-7: helpers_v2.sh (auth, api, roles, db, tenant, ui, json-tests)
    FINAL: generate_report → si FAILs → export antigravity_FECHA.md

BROWSER (spider_v3.html en /superadmin/spider):
  ⟳ Sync → POST /api/spider/sync → sync_spider_tests.sh → route:list → spider_tests.json
  ▶ Iniciar → fases auth+roles+db+json+tenant+ui → resultados en tiempo real
  ⬇ Export MD → archivo listo para pegar en antigravity

ANTIGRAVITY:
  Pegar tests/report/antigravity_FECHA.md → agente resuelve bugs → cierra en bug_reports
  Próximo run_all.sh detecta los fixes → PASS en lugar de FAIL
```

---

## Archivos del sistema (resumen)

```
tests/
├── spider_v3.html            ← araña QA (copiar a resources/views/superadmin/spider.blade.php)
├── SpiderController.php      ← copiar a app/Http/Controllers/
├── sync_spider_tests.sh      ← auto-descubre rutas desde route:list
├── spider_tests.json         ← registro de tests (auto-generado + editable)
├── diagnose_tenant.sh        ← fase 0: diagnostica y repara tenant
├── gen_credentials.sh        ← genera credentials.env desde DB real
├── helpers_v2.sh             ← funciones base H22/H23 (lightpanda posicional)
├── run_all.sh                ← suite completa (run_all_v3.sh renombrado)
├── credentials.env           ← [gitignored] URLs + usuarios + tokens
├── results/
│   ├── summary.log           ← PASS/FAIL por TC
│   └── tenant_config.env     ← TENANT_ID, TENANT_DB, TENANT_URL reales
└── report/
    ├── YYYY-MM-DD.md         ← reporte del día
    └── antigravity_FECHA.md  ← export para agente IA (auto-generado si hay FAILs)
```

---

## Reporte esperado del agente

Al terminar todas las tareas:

```
RESUMEN SPIDER QA V3:
- TAREA 1 SpiderController: INSTALADO
- TAREA 2 Rutas /api/spider/*: REGISTRADAS (5 rutas)
- TAREA 3 Vista spider.blade.php: INSTALADA — HTTP 200 verificado
- TAREA 4 Scripts tests/: INSTALADOS — spider_tests.json generado con N tests
- TAREA 5 Link Spider en sidebar: AGREGADO
- TAREA 6 credentials.env: GENERADO
- TAREA 7 Suite run_all.sh: X/Y PASS
- TAREA 8 E2E endpoints: probe OK · db-check OK · sync OK (+N tests)
- TAREA 9 Browser: spider_v3.html carga con sync automático

Auto-sync funcionando: SÍ/NO
Tests en JSON: N total (SA: X · Tenant: Y · HTTP: Z)
Sistema Spider QA v3: OPERATIVO
```

---
*BenderAnd Spider QA v3 — Antigravity Install Guide · 2026-03-16*
