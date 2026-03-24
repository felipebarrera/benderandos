<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Central\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * DemoAllTenantSeeder
 *
 * Crea el tenant "demo-all" con TODOS los módulos M01–M32 activos.
 * Sirve para que el Spider QA pueda probar todos los endpoints sin
 * restricciones de módulos inactivos.
 *
 * Uso:
 *   php artisan db:seed --class=DemoAllTenantSeeder
 *
 * Re-ejecutar limpio: el cleanup() al inicio es automático e idempotente.
 *
 * Agregar al /etc/hosts si no está:
 *   echo "127.0.0.1 demo-all.localhost" | sudo tee -a /etc/hosts
 */
class DemoAllTenantSeeder extends Seeder
{
    const SLUG = 'demo-all';

    const ALL_MODULES = [
        'M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10',
        'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18', 'M19', 'M20',
        'M21', 'M22', 'M23', 'M24', 'M25', 'M26', 'M27', 'M28', 'M29', 'M30',
        'M31', 'M32',
    ];

    public function run(): void
    {
        $this->command->info('⟳ DemoAllTenantSeeder — iniciando...');

        // ── 0. Limpiar ejecución anterior (idempotente) ──────────────────────
        $this->cleanup();

        // ── 1. Crear tenant ──────────────────────────────────────────────────
        // El observer/hook del modelo Tenant crea la DB automáticamente,
        // igual que hace DemoTenantsSeeder con sus tenants.
        $tenant = Tenant::firstOrCreate(['id' => self::SLUG], [
            'nombre' => 'Demo ALL — Todos los módulos',
            'rut_empresa' => '77.777.777-7',
            'estado' => 'activo',
            'trial_hasta' => now()->addYears(10),
            'whatsapp_admin' => '+56900000000',
            'plan_id' => $this->getPlanId(),
            'data' => json_encode([
                'rubro_config' => [
                    'industria_preset' => 'demo_all',
                    'modulos_activos' => self::ALL_MODULES,
                ],
            ]),
        ]);
        $this->command->info('  ✅ Tenant demo-all creado');

        // ── 2. Dominio ───────────────────────────────────────────────────────
        Domain::firstOrCreate(
        ['domain' => self::SLUG . '.localhost'],
        ['tenant_id' => $tenant->id]
        );
        $this->command->info('  ✅ Dominio demo-all.localhost registrado');

        // ── 3. Suscripción en schema central ─────────────────────────────────
        DB::connection('central')->table('subscriptions')->updateOrInsert(
        ['tenant_id' => self::SLUG],
        [
            'plan_id' => $this->getPlanId(),
            'estado' => 'trial',
            'inicio' => now(),
            'proximo_cobro' => now()->addYears(10),
            'monto_clp' => 0,
            'dias_gracia' => 30,
            'descuento_pct' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]
        );
        $this->command->info('  ✅ Suscripción trial creada');

        // ── 4. Datos dentro del tenant ───────────────────────────────────────
        tenancy()->initialize($tenant);

        // Reutiliza TenantDemoDataSeeder para los datos base (igual que DemoTenantsSeeder)
        $this->call(TenantDemoDataSeeder::class , true, [
            'rubro' => 'demo_all',
            'email' => 'admin@' . self::SLUG . '.cl',
            'modulos' => self::ALL_MODULES,
            'nombre' => 'Demo ALL — Todos los módulos',
        ]);

        // Campos extra de rubros_config que TenantDemoDataSeeder no cubre
        DB::table('rubros_config')->updateOrInsert(['id' => 1], [
            'tiene_renta_hora' => true,
            'tiene_agenda' => true,
            'tiene_delivery' => true,
            'tiene_comandas' => true,
            'tiene_ot' => true,
            'tiene_membresias' => true,
            'tiene_notas_cifradas' => true,
            'tiene_fiado' => true,
            'tiene_fraccionado' => true,
            'tiene_descuento_vol' => true,
            'label_operario' => 'Operario',
            'label_cliente' => 'Cliente',
            'label_cajero' => 'Cajero',
            'label_producto' => 'Producto',
            'label_recurso' => 'Recurso',
            'documento_default' => 'boleta',
            'requiere_rut' => true,
            'accent_color' => '#e040fb',
            'updated_at' => now(),
        ]);
        $this->command->info('  ✅ rubros_config con ' . count(self::ALL_MODULES) . ' módulos activos');

        tenancy()->end();

        // ── Resumen ──────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ demo-all listo para Spider QA');
        $this->command->info('   URL:      http://demo-all.localhost:8000');
        $this->command->info('   Email:    admin@demo-all.cl');
        $this->command->info('   Password: demo1234');
        $this->command->info('   Módulos:  ' . count(self::ALL_MODULES) . '/32 activos');
        $this->command->warn('   /etc/hosts → 127.0.0.1 demo-all.localhost');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    // ── Limpieza idempotente ─────────────────────────────────────────────────
    private function cleanup(): void
    {
        $this->command->info('  ⟳ Limpiando registros anteriores...');

        try {
            tenancy()->end();
        }
        catch (\Throwable) {
        }

        $tenant = Tenant::find(self::SLUG);
        if ($tenant) {
            // 1) Intentar borrado via tenancy (dispara DROP DATABASE/SCHEMA)
            try {
                $tenant->database()->manager()->deleteDatabase($tenant);
                $this->command->info('  ✅ Base de datos del tenant eliminada');
            }
            catch (\Throwable $e) {
                $this->command->warn('  ⚠ deleteDatabase falló (puede que ya no exista): ' . $e->getMessage());
            }

            // 2) delete() del modelo — también intenta DROP, envolverlo por si
            //    el schema/db ya no existe tras el paso anterior
            try {
                $tenant->delete();
                $this->command->info('  ✅ Tenant eliminado del schema central');
            }
            catch (\Throwable $e) {
                $this->command->warn('  ⚠ tenant->delete() falló, limpiando manualmente: ' . $e->getMessage());
                // Fallback: borrar registros directamente sin pasar por el modelo
                DB::connection('central')->table('domains')->where('tenant_id', self::SLUG)->delete();
                DB::connection('central')->table('tenants')->where('id', self::SLUG)->delete();
                $this->command->info('  ✅ Tenant y dominios eliminados manualmente');
            }
        }

        // subscriptions no siempre tiene FK cascade → borrar explícito
        DB::connection('central')
            ->table('subscriptions')
            ->where('tenant_id', self::SLUG)
            ->delete();

        $this->command->info('  ✅ Limpieza completa');
    }

    private function getPlanId(): int
    {
        $plan = DB::connection('central')
            ->table('plans')
            ->orderByDesc('precio_mensual_clp')
            ->first();

        return $plan ? $plan->id : 1;
    }
}