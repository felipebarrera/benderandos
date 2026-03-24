<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;

class SpiderController extends Controller
{
    private string $testsPath = '/app/tests/spider_tests.json';
    private string $syncScript = '/app/tests/sync_spider_tests.sh';

    public function index()
    {
        return view('central.spider');
    }

    /**
     * @deprecated Spider v4 — browser-side fetch replaces server-side probe.
     * Kept for backward compatibility but no longer called from spider.blade.php.
     */
    public function probe(Request $request)
    {
        $url = $request->query('url');
        if (!$url)
            return response()->json(['code' => 200, 'via' => 'proxy']);

        if (!filter_var($url, FILTER_VALIDATE_URL))
            return response()->json(['code' => 0, 'error' => 'URL inválida'], 422);

        $host = parse_url($url, PHP_URL_HOST);
        if (!\in_array($host, ['localhost', '127.0.0.1', 'demo.localhost']) &&
        !str_ends_with($host, '.localhost'))
            return response()->json(['code' => 0, 'error' => 'Solo URLs locales'], 403);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(8)
                ->withoutVerifying()
                ->withHeaders(['Accept' => 'text/html'])
                ->get($url);

            return response()->json([
                'code' => $response->status(),
                'via' => 'proxy'
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'error' => $e->getMessage(),
                'via' => 'none'
            ]);
        }
    }

    public function dbCheck(Request $request)
    {
        $checks = [];
        $checks[] = ['label' => 'super_admins tiene registros', 'ok' => DB::connection('central')->table('super_admins')->count() > 0,
            'detail' => DB::connection('central')->table('super_admins')->count() . ' registros', 'fix' => 'php artisan db:seed --class=SuperAdminSeeder'];
        $checks[] = ['label' => 'plan_modulos con datos', 'ok' => DB::connection('central')->table('plan_modulos')->count() > 0,
            'detail' => DB::connection('central')->table('plan_modulos')->count() . ' módulos', 'fix' => 'php artisan db:seed --class=PlanModulosSeeder'];
        $checks[] = ['label' => 'Tabla tenants accesible', 'ok' => true,
            'detail' => DB::connection('central')->table('tenants')->count() . ' tenants', 'fix' => ''];
        $br = DB::connection('central')->selectOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_name='bug_reports' AND table_schema='public'");
        $checks[] = ['label' => 'Tabla bug_reports existe', 'ok' => ($br->c ?? 0) > 0,
            'detail' => 'Sistema de tracking', 'fix' => 'Ver BUG-008 en MD maestro'];
        $checks[] = ['label' => 'Dominios registrados', 'ok' => DB::connection('central')->table('domains')->count() > 0,
            'detail' => DB::connection('central')->table('domains')->count() . ' dominios', 'fix' => 'Crear dominio para tenant demo'];
        return response()->json(['checks' => $checks]);
    }

    public function syncTests(Request $request)
    {
        if (!file_exists($this->syncScript))
            return response()->json(['ok' => false, 'error' => 'sync_spider_tests.sh no encontrado',
                'fix' => 'cp sync_spider_tests.sh /app/tests/'], 404);

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

        // Agregar tenant_slug recomendado al response
        $recommendedTenant = $this->getRecommendedTenant();

        return response()->json([
            'ok' => $p->isSuccessful(),
            'output' => $p->getOutput(),
            'before_total' => $bc,
            'after_total' => $ac,
            'new_tests' => $ac - $bc,
            'diff' => $diff,
            'tests' => $after,
            'tenant_slug' => $recommendedTenant['slug'],
            'tenant_url' => $recommendedTenant['url']
        ]);
    }

    /**
     * Retorna el tenant slug recomendado para testing
     * Usa hardcoded demo-ferreteria porque tiene más módulos activos
     * y evita problemas de cross-schema relationships
     */
    private function getRecommendedTenant(): array
    {
        // Hardcodeado: demo-ferreteria tiene 12+ módulos activos
        // y es el tenant más completo para pruebas del Spider QA
        return [
            'slug' => 'demo-ferreteria',
            'domain' => 'demo-ferreteria.localhost',
            'url' => 'http://demo-ferreteria.localhost:8000',
            'nombre' => 'Ferretería Demo',
            'modulos_activos_count' => 12 // Estimado basado en BENDERAND_KNOWLEDGE_MASTER.md
        ];
    }

    public function getTests(Request $request)
    {
        return response()->json($this->readTests());
    }

    public function saveTests(Request $request)
    {
        $data = $request->validate(['tests' => 'required|array']);
        $current = $this->readTests();
        foreach ($data['tests'] as $section => $tests)
            if (is_array($tests))
                $current[$section] = $tests;
        $current['_meta']['updated'] = now()->toISOString();
        file_put_contents($this->testsPath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return response()->json(['ok' => true, 'total' => $this->countTests($current)]);
    }

    public function generateToken(Request $request)
    {
        $token = $request->user('super_admin')->createToken('spider-session')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    private function readTests(): array
    {
        if (!file_exists($this->testsPath))
            return ['_meta' => ['version' => '1.0', 'total_tests' => 0],
                'http_checks' => [], 'api_sa_checks' => [], 'api_tenant_checks' => [],
                'auth_checks' => [], 'db_checks' => [], 'ui_checks' => []];
        return json_decode(file_get_contents($this->testsPath), true) ?? [];
    }

    private function countTests(array $data): int
    {
        $t = 0;
        foreach (['http_checks', 'api_sa_checks', 'api_tenant_checks', 'auth_checks', 'db_checks', 'ui_checks'] as $s)
            $t += count($data[$s] ?? []);
        return $t;
    }

    /**
     * Retorna el tenant slug recomendado para testing del Spider QA
     * GET /api/spider/tenant-slug
     */
    public function getTenantSlug(Request $request)
    {
        $recommended = $this->getRecommendedTenant();

        return response()->json([
            'tenant_slug' => $recommended['slug'],
            'domain' => $recommended['domain'],
            'url' => $recommended['url'],
            'nombre' => $recommended['nombre'],
            'modulos_activos_count' => $recommended['modulos_activos_count'],
            'note' => 'Hardcoded recommendation - cross-schema relationships not supported'
        ]);
    }
}