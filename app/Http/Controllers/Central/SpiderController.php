<?php
namespace App\Http\Controllers\Central;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

/**
 * SpiderController — QA Spider v5
 *
 * Movido a Central/ para aislar del panel public y evitar
 * que el navegador exponga campos password en otras rutas.
 *
 * Namespace: App\Http\Controllers\Central\SpiderController
 * Registrar en routes/web.php:
 *   use App\Http\Controllers\Central\SpiderController;
 */
class SpiderController extends Controller
{
    private string $testsPath = '/app/tests/spider_tests.json';
    private string $syncScript = '/app/tests/sync_spider_tests.sh';

    /** Vista principal del Spider QA */
    public function index()
    {
        return view('central.spider');
    }

    /**
     * Genera token Sanctum para el SuperAdmin logueado.
     * POST /central/spider/token
     */
    public function generateToken(Request $request)
    {
        $token = $request->user('super_admin')->createToken('spider-session')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    /**
     * Lista de tenants con módulos activos — para el selector del Spider.
     * GET /api/spider/tenants
     *
     * Respuesta:
     * [
     *   {
     *     "id": "demo-all",
     *     "nombre": "Demo ALL",
     *     "estado": "activo",
     *     "rubro": "demo_all",
     *     "modulos_activos": ["M01", "M02", ...],
     *     "url": "http://demo-all.localhost:8000",
     *     "email": "admin@demo-all.cl"
     *   },
     *   ...
     * ]
     */
    public function listTenants(Request $request)
    {
        $tenants = DB::connection('central')
            ->table('tenants')
            ->select('id', 'nombre', 'estado', 'rubro_config', 'data')
            ->orderByRaw("CASE WHEN id = 'demo-all' THEN 0 ELSE 1 END")
            ->orderBy('nombre')
            ->get();

        $result = $tenants->map(function ($t) {
            $rubroConfig = is_string($t->rubro_config)
                ? json_decode($t->rubro_config, true)
                : (array)$t->rubro_config;

            $modulos = $rubroConfig['modulos_activos'] ?? [];
            if (is_string($modulos)) {
                $modulos = json_decode($modulos, true) ?? [];
            }

            // Intentar obtener módulos desde rubros_config del schema del tenant
            // como fallback (si no vienen en la columna del central)
            if (empty($modulos)) {
                try {
                    $tenant = \App\Models\Central\Tenant::find($t->id);
                    if ($tenant) {
                        tenancy()->initialize($tenant);
                        $rc = DB::table('rubros_config')->first();
                        if ($rc) {
                            $m = is_string($rc->modulos_activos)
                                ? json_decode($rc->modulos_activos, true)
                                : $rc->modulos_activos;
                            if (is_array($m))
                                $modulos = $m;
                        }
                        tenancy()->end();
                    }
                }
                catch (\Throwable $e) {
                // silenciar — no todos los tenants tienen schema inicializado
                }
            }

            return [
            'id' => $t->id,
            'slug' => $t->id,
            'nombre' => $t->nombre ?? $t->id,
            'estado' => $t->estado ?? 'activo',
            'rubro' => $rubroConfig['industria_preset'] ?? '—',
            'modulos_activos' => $modulos,
            'url' => 'http://' . $t->id . '.localhost:8000',
            'email' => 'admin@' . $t->id . '.cl',
            'password' => 'demo1234',
            ];
        });

        return response()->json($result);
    }

    /**
     * Health check de base de datos central.
     * GET /api/spider/db-check
     */
    public function dbCheck(Request $request)
    {
        $checks = [];

        $checks[] = [
            'label' => 'super_admins tiene registros',
            'ok' => DB::connection('central')->table('super_admins')->count() > 0,
            'detail' => DB::connection('central')->table('super_admins')->count() . ' registros',
            'fix' => 'php artisan db:seed --class=SuperAdminSeeder',
        ];

        $checks[] = [
            'label' => 'plan_modulos con datos',
            'ok' => DB::connection('central')->table('plan_modulos')->count() > 0,
            'detail' => DB::connection('central')->table('plan_modulos')->count() . ' módulos',
            'fix' => 'php artisan db:seed --class=PlanModulosSeeder',
        ];

        $tenantCount = DB::connection('central')->table('tenants')->count();
        $demoAll = DB::connection('central')->table('tenants')->where('id', 'demo-all')->exists();

        $checks[] = [
            'label' => 'Tabla tenants accesible',
            'ok' => true,
            'detail' => $tenantCount . ' tenants' . ($demoAll ? ' · demo-all ✓' : ' · demo-all FALTA'),
            'fix' => $demoAll ? '' : 'php artisan db:seed --class=DemoAllTenantSeeder',
        ];

        $checks[] = [
            'label' => 'Tenant demo-all existe (Spider completo)',
            'ok' => $demoAll,
            'detail' => $demoAll ? 'demo-all disponible — todos los módulos activos' : 'demo-all NO existe — Spider no puede probar todos los módulos',
            'fix' => 'php artisan db:seed --class=DemoAllTenantSeeder',
        ];

        $br = DB::connection('central')
            ->selectOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_name='bug_reports' AND table_schema='public'");
        $checks[] = [
            'label' => 'Tabla bug_reports existe',
            'ok' => ($br->c ?? 0) > 0,
            'detail' => 'Sistema de tracking de bugs',
            'fix' => 'Ver H24 en MD maestro',
        ];

        $checks[] = [
            'label' => 'Dominios registrados',
            'ok' => DB::connection('central')->table('domains')->count() > 0,
            'detail' => DB::connection('central')->table('domains')->count() . ' dominios',
            'fix' => 'Crear dominio para tenant demo',
        ];

        return response()->json(['checks' => $checks]);
    }

    /**
     * Sincroniza spider_tests.json desde las rutas de Laravel.
     * POST /api/spider/sync
     */
    public function syncTests(Request $request)
    {
        if (!file_exists($this->syncScript)) {
            return response()->json([
                'ok' => false,
                'error' => 'sync_spider_tests.sh no encontrado',
                'fix' => 'cp sync_spider_tests.sh /app/tests/',
            ], 404);
        }

        $before = $this->readTests();
        $bc = $this->countTests($before);

        $p = new Process(['bash', $this->syncScript]);
        $p->setTimeout(30);
        $p->run();

        $after = $this->readTests();
        $ac = $this->countTests($after);

        $diff = [];
        foreach (['http_checks', 'api_sa_checks', 'api_tenant_checks', 'auth_checks', 'db_checks', 'ui_checks'] as $s) {
            $b = count($before[$s] ?? []);
            $a = count($after[$s] ?? []);
            if ($a !== $b)
                $diff[$s] = ['before' => $b, 'after' => $a, 'added' => $a - $b];
        }

        return response()->json([
            'ok' => $p->isSuccessful(),
            'output' => $p->getOutput(),
            'before_total' => $bc,
            'after_total' => $ac,
            'new_tests' => $ac - $bc,
            'diff' => $diff,
            'tests' => $after,
            'tenant_slug' => 'demo-all',
            'tenant_url' => 'http://demo-all.localhost:8000',
        ]);
    }

    /** GET /api/spider/tests */
    public function getTests(Request $request)
    {
        return response()->json($this->readTests());
    }

    /** POST /api/spider/tests */
    public function saveTests(Request $request)
    {
        $data = $request->validate(['tests' => 'required|array']);
        $current = $this->readTests();
        foreach ($data['tests'] as $section => $tests) {
            if (is_array($tests))
                $current[$section] = $tests;
        }
        $current['_meta']['updated'] = now()->toISOString();
        file_put_contents($this->testsPath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return response()->json(['ok' => true, 'total' => $this->countTests($current)]);
    }

    // ── PRIVADOS ────────────────────────────────────────────────────────────────

    private function readTests(): array
    {
        if (!file_exists($this->testsPath)) {
            return [
                '_meta' => ['version' => '5.0', 'total_tests' => 0],
                'http_checks' => [],
                'api_sa_checks' => [],
                'api_tenant_checks' => [],
                'auth_checks' => [],
                'db_checks' => [],
                'ui_checks' => [],
            ];
        }
        return json_decode(file_get_contents($this->testsPath), true) ?? [];
    }

    private function countTests(array $data): int
    {
        $t = 0;
        foreach (['http_checks', 'api_sa_checks', 'api_tenant_checks', 'auth_checks', 'db_checks', 'ui_checks'] as $s) {
            $t += count($data[$s] ?? []);
        }
        return $t;
    }

    /**
     * @deprecated Spider v4 — reemplazado por browser-side fetch.
     * Mantenido por compatibilidad.
     */
    public function probe(Request $request)
    {
        return response()->json(['code' => 200, 'via' => 'deprecated — use browser-side fetch']);
    }
}