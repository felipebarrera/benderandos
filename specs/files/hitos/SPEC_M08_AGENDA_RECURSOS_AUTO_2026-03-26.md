# SPEC — M08 Agenda: Automatización de Recursos, Autoregistro Operarios y Panel Personal
**Sistema:** BenderAnd ERP · Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Vanilla JS + Blade
**Fecha:** 2026-03-26
**Container:** `benderandos_app` · código montado en `/app`
**Depende de:** M08 DB+Service+Controller ya implementados (H25 completo)

---

## OBJETIVO

Implementar **tres automatizaciones encadenadas** sobre el módulo M08 ya existente:

1. **Auto-recurso de operario**: cuando un usuario con rol `operario` (o `cajero`) existe en un tenant con M08 activo, se crea automáticamente un `AgendaRecurso` vinculado a ese usuario — sin intervención del admin.
2. **Auto-recurso de producto renta**: cuando un `Producto` con `tipo_producto = 'renta'` existe en un tenant con M08 activo, se crea un `AgendaRecurso` de tipo `recurso` vinculado a ese producto — para que los clientes puedan reservarlo desde el landing.
3. **Panel personal del operario**: cada usuario con M08 vinculado ve en su sidebar un menú "Mi Agenda" que le permite gestionar sus propios horarios, crear bloqueos y ver/editar sus citas — sin acceso al resto de la agenda.

---

## PARTE 1 — MIGRACIONES

### 1.1 Agregar columna `producto_id` a `agenda_recursos`

```bash
docker exec benderandos_app sh -c "cd /app && php artisan make:migration \
  add_producto_id_to_agenda_recursos_table \
  --path=database/migrations/tenant"
```

Contenido de la migración:

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agenda_recursos', function (Blueprint $table) {
            // FK al producto de renta que representa este recurso
            $table->unsignedBigInteger('producto_id')->nullable()->after('usuario_id');
            // Auto-creado: indica que fue generado automáticamente (no manual)
            $table->boolean('auto_creado')->default(false)->after('producto_id');
            // Horario heredado: si true, usa horarios del tenant (AgendaConfig)
            $table->boolean('hereda_horario_tenant')->default(false)->after('auto_creado');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_recursos', function (Blueprint $table) {
            $table->dropColumn(['producto_id','auto_creado','hereda_horario_tenant']);
        });
    }
};
```

### 1.2 Ejecutar en todos los tenants

```bash
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate \
  --path=database/migrations/tenant"
```

---

## PARTE 2 — MODELO AgendaRecurso (actualizar)

### Archivo: `app/Models/Tenant/AgendaRecurso.php`

Agregar al `$fillable` y relaciones:

```php
<?php
namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class AgendaRecurso extends Model
{
    protected $table = 'agenda_recursos';

    protected $fillable = [
        'nombre', 'tipo', 'especialidad', 'color', 'orden',
        'usuario_id', 'producto_id',
        'auto_creado', 'hereda_horario_tenant', 'activo',
    ];

    protected $casts = [
        'auto_creado'             => 'boolean',
        'hereda_horario_tenant'   => 'boolean',
        'activo'                  => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────────

    /** Usuario del sistema vinculado (operario/profesional) */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /** Producto de renta vinculado */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /** Servicios ofrecidos por este recurso */
    public function servicios()
    {
        return $this->hasMany(AgendaServicio::class, 'agenda_recurso_id')
                    ->where('activo', true);
    }

    /** Horarios operativos del recurso */
    public function horarios()
    {
        return $this->hasMany(AgendaHorario::class, 'agenda_recurso_id');
    }

    /** Citas agendadas a este recurso */
    public function citas()
    {
        return $this->hasMany(AgendaCita::class, 'agenda_recurso_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopeProfesionales($q)
    {
        return $q->where('tipo', 'profesional')->whereNotNull('usuario_id');
    }

    public function scopeParaUsuario($q, int $usuarioId)
    {
        return $q->where('usuario_id', $usuarioId);
    }
}
```

---

## PARTE 3 — SERVICIO DE AUTO-REGISTRO

### Archivo NUEVO: `app/Services/AgendaAutoRegistroService.php`

Este servicio centraliza **toda la lógica de creación automática** de recursos.
Se llama desde Observers y desde el comando artisan.

```php
<?php
namespace App\Services;

use App\Models\Tenant\AgendaRecurso;
use App\Models\Tenant\AgendaHorario;
use App\Models\Tenant\AgendaConfig;
use App\Models\Tenant\RubroConfig;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;

class AgendaAutoRegistroService
{
    /**
     * Verifica si el tenant actual tiene M08 activo.
     */
    public function m08Activo(): bool
    {
        $config = RubroConfig::first();
        return $config && in_array('M08', $config->modulos_activos ?? []);
    }

    // ═══════════════════════════════════════════════════════════════
    // OPERARIOS → RECURSOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea o reactiva un AgendaRecurso para un usuario operario/cajero.
     * Si ya existe (por usuario_id), no duplica — solo reactiva si estaba inactivo.
     *
     * Retorna el recurso (nuevo o existente).
     */
    public function registrarOperario(Usuario $usuario): ?AgendaRecurso
    {
        if (!$this->m08Activo()) return null;
        if (!in_array($usuario->rol, ['operario', 'cajero', 'admin'])) return null;

        // Buscar por usuario_id primero
        $recurso = AgendaRecurso::where('usuario_id', $usuario->id)->first();

        if ($recurso) {
            // Ya existe: reactivar si estaba inactivo
            if (!$recurso->activo) {
                $recurso->update(['activo' => true]);
            }
            return $recurso;
        }

        // Crear nuevo recurso
        $color = $this->colorPorIndice(
            AgendaRecurso::where('tipo', 'profesional')->count()
        );

        $recurso = AgendaRecurso::create([
            'nombre'                  => $usuario->nombre,
            'tipo'                    => 'profesional',
            'especialidad'            => $this->especialidadPorRubro(),
            'color'                   => $color,
            'orden'                   => AgendaRecurso::max('orden') + 1,
            'usuario_id'              => $usuario->id,
            'auto_creado'             => true,
            'hereda_horario_tenant'   => true,
            'activo'                  => true,
        ]);

        // Crear horarios por defecto (L-V 09-18, S-D cerrado)
        $this->crearHorariosDefecto($recurso);

        return $recurso;
    }

    /**
     * Desactiva el recurso cuando el operario se desactiva o elimina.
     * NO elimina — conserva historial de citas.
     */
    public function desactivarOperario(int $usuarioId): void
    {
        AgendaRecurso::where('usuario_id', $usuarioId)
                     ->where('auto_creado', true)
                     ->update(['activo' => false]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTOS RENTA → RECURSOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea o reactiva un AgendaRecurso para un producto de tipo renta.
     */
    public function registrarProductoRenta(Producto $producto): ?AgendaRecurso
    {
        if (!$this->m08Activo()) return null;
        if ($producto->tipo_producto !== 'renta') return null;

        $recurso = AgendaRecurso::where('producto_id', $producto->id)->first();

        if ($recurso) {
            if (!$recurso->activo) {
                $recurso->update(['activo' => true, 'nombre' => $producto->nombre]);
            }
            return $recurso;
        }

        $config   = RubroConfig::first();
        $labelRec = $config?->label_recurso ?? 'Recurso';

        $recurso = AgendaRecurso::create([
            'nombre'                => $producto->nombre,
            'tipo'                  => 'recurso',
            'especialidad'          => $labelRec,
            'color'                 => '#00c4ff',
            'orden'                 => AgendaRecurso::max('orden') + 1,
            'producto_id'           => $producto->id,
            'auto_creado'           => true,
            'hereda_horario_tenant' => true,
            'activo'                => true,
        ]);

        // Crear servicio automático: "Reserva de {nombre}" con precio del producto
        $recurso->servicios()->create([
            'nombre'       => 'Reserva de ' . $producto->nombre,
            'duracion_min' => 60,  // default 1 hora; admin puede cambiar
            'precio'       => $producto->valor_venta ?? 0,
            'activo'       => true,
        ]);

        $this->crearHorariosDefecto($recurso);

        return $recurso;
    }

    /**
     * Desactiva el recurso cuando el producto se desactiva o elimina.
     */
    public function desactivarProductoRenta(int $productoId): void
    {
        AgendaRecurso::where('producto_id', $productoId)
                     ->where('auto_creado', true)
                     ->update(['activo' => false]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ACTIVACIÓN DEL MÓDULO M08 EN UN TENANT EXISTENTE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Cuando M08 se activa en un tenant que ya tenía operarios y productos renta,
     * retroactivamente crea recursos para todos ellos.
     *
     * Llamar desde ConfigRubroController::activarModulo() cuando módulo = M08.
     */
    public function inicializarTenant(): array
    {
        $resultados = [
            'operarios_registrados' => 0,
            'productos_registrados' => 0,
        ];

        // Registrar todos los operarios/cajeros activos
        $operarios = Usuario::whereIn('rol', ['operario','cajero','admin'])
                           ->where('activo', true)
                           ->get();

        foreach ($operarios as $u) {
            if ($this->registrarOperario($u)) {
                $resultados['operarios_registrados']++;
            }
        }

        // Registrar todos los productos de renta activos
        $productos = Producto::where('tipo_producto', 'renta')
                            ->where('estado', 'activo')
                            ->get();

        foreach ($productos as $p) {
            if ($this->registrarProductoRenta($p)) {
                $resultados['productos_registrados']++;
            }
        }

        // Crear AgendaConfig por defecto si no existe
        AgendaConfig::firstOrCreate([], [
            'titulo_landing'          => config('tenancy.tenant_name', 'Nuestros servicios'),
            'descripcion_landing'     => 'Reserva tu hora online fácilmente.',
            'landing_publico_activo'  => true,
            'confirmacion_wa_activa'  => false,
            'recordatorio_activo'     => true,
            'recordatorio_horas_antes'=> 24,
            'requiere_telefono'       => true,
            'requiere_email'          => false,
            'color_primario'          => '#00e5a0',
        ]);

        return $resultados;
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea horarios por defecto: L-V 09:00-18:00, S 09:00-14:00, D cerrado.
     * Si el recurso hereda_horario_tenant, lee AgendaConfig para el horario.
     */
    private function crearHorariosDefecto(AgendaRecurso $recurso): void
    {
        $config = AgendaConfig::first();

        // Intentar leer horario del tenant si existe
        $hIni  = $config?->horario_inicio ?? '09:00';
        $hFin  = $config?->horario_fin    ?? '18:00';
        $sDur  = $config?->duracion_slot_min ?? 30;

        $horarios = [
            ['dia_semana' => 1, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 2, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 3, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 4, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 5, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 6, 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 7, 'hora_inicio' => '09:00', 'hora_fin' => '18:00', 'activo' => 0, 'duracion_slot_min' => $sDur],
        ];

        foreach ($horarios as $h) {
            AgendaHorario::updateOrCreate(
                ['agenda_recurso_id' => $recurso->id, 'dia_semana' => $h['dia_semana']],
                $h
            );
        }
    }

    /** Paleta de 8 colores vibrantes para los recursos auto-creados */
    private function colorPorIndice(int $i): string
    {
        $palette = [
            '#00e5a0', '#7c6af7', '#00c4ff', '#ff6b35',
            '#3dd9eb', '#f5c518', '#e040fb', '#ff3f5b',
        ];
        return $palette[$i % count($palette)];
    }

    /** Especialidad por defecto según el rubro del tenant */
    private function especialidadPorRubro(): string
    {
        $config = RubroConfig::first();
        $preset = $config?->industria_preset ?? '';
        return match(true) {
            in_array($preset, ['medico','clinica'])           => 'Médico General',
            in_array($preset, ['dentista'])                   => 'Odontología',
            in_array($preset, ['abogados','legal'])           => 'Abogado',
            in_array($preset, ['padel','canchas','deportes']) => 'Cancha',
            in_array($preset, ['salon','spa'])                => 'Estilista',
            in_array($preset, ['veterinaria'])                => 'Veterinario',
            default                                           => 'Profesional',
        };
    }
}
```

---

## PARTE 4 — OBSERVERS

### 4.1 Observer de Usuario

```bash
docker exec benderandos_app sh -c "cd /app && php artisan make:observer \
  Tenant/UsuarioAgendaObserver --model=Tenant/Usuario"
```

Contenido: `app/Observers/Tenant/UsuarioAgendaObserver.php`

```php
<?php
namespace App\Observers\Tenant;

use App\Models\Tenant\Usuario;
use App\Services\AgendaAutoRegistroService;

class UsuarioAgendaObserver
{
    private AgendaAutoRegistroService $svc;

    public function __construct(AgendaAutoRegistroService $svc)
    {
        $this->svc = $svc;
    }

    /**
     * Cuando se crea un usuario → registrarlo como recurso si M08 activo.
     */
    public function created(Usuario $usuario): void
    {
        $this->svc->registrarOperario($usuario);
    }

    /**
     * Cuando se actualiza → re-evaluar.
     * Si cambió de rol a operario → registrar.
     * Si cambió de operario a otro rol (o se desactivó) → desactivar recurso.
     */
    public function updated(Usuario $usuario): void
    {
        $roles_agenda = ['operario', 'cajero', 'admin'];

        if ($usuario->wasChanged('activo') && !$usuario->activo) {
            $this->svc->desactivarOperario($usuario->id);
            return;
        }

        if (in_array($usuario->rol, $roles_agenda)) {
            $this->svc->registrarOperario($usuario);
        } else {
            $this->svc->desactivarOperario($usuario->id);
        }
    }

    /**
     * Cuando se elimina un usuario → desactivar recurso (conservar historial).
     */
    public function deleted(Usuario $usuario): void
    {
        $this->svc->desactivarOperario($usuario->id);
    }
}
```

### 4.2 Observer de Producto

```bash
docker exec benderandos_app sh -c "cd /app && php artisan make:observer \
  Tenant/ProductoAgendaObserver --model=Tenant/Producto"
```

Contenido: `app/Observers/Tenant/ProductoAgendaObserver.php`

```php
<?php
namespace App\Observers\Tenant;

use App\Models\Tenant\Producto;
use App\Services\AgendaAutoRegistroService;

class ProductoAgendaObserver
{
    private AgendaAutoRegistroService $svc;

    public function __construct(AgendaAutoRegistroService $svc)
    {
        $this->svc = $svc;
    }

    public function created(Producto $producto): void
    {
        $this->svc->registrarProductoRenta($producto);
    }

    public function updated(Producto $producto): void
    {
        if ($producto->tipo_producto === 'renta' && $producto->estado === 'activo') {
            $this->svc->registrarProductoRenta($producto);
        } else {
            $this->svc->desactivarProductoRenta($producto->id);
        }
    }

    public function deleted(Producto $producto): void
    {
        $this->svc->desactivarProductoRenta($producto->id);
    }
}
```

### 4.3 Registrar Observers en `app/Providers/AppServiceProvider.php`

```php
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;
use App\Observers\Tenant\UsuarioAgendaObserver;
use App\Observers\Tenant\ProductoAgendaObserver;

public function boot(): void
{
    // Solo registrar observers dentro de contexto tenant
    // (los modelos Tenant solo existen cuando stancl inicializó el schema)
    if (app()->bound('currentTenant')) {
        Usuario::observe(UsuarioAgendaObserver::class);
        Producto::observe(ProductoAgendaObserver::class);
    }
}
```

> **Nota de implementación:** si `app()->bound('currentTenant')` no funciona en tu versión de stancl/tenancy v3, usar `tenancy()->initialized()` o envolver en un try/catch.

---

## PARTE 5 — ACTIVACIÓN DE M08 EN TENANT EXISTENTE

### Modificar `app/Http/Controllers/Tenant/ConfigRubroController.php`

En el método que activa módulos (probablemente `activarModulo` o `toggle`), agregar:

```php
use App\Services\AgendaAutoRegistroService;

// Dentro del método que activa un módulo:
public function activarModulo(Request $r, string $moduloId)
{
    // ... lógica existente de activación ...

    // Si se activó M08, inicializar recursos automáticamente
    if ($moduloId === 'M08') {
        $resultado = app(AgendaAutoRegistroService::class)->inicializarTenant();
        // Incluir en la respuesta para mostrar al admin
        return response()->json([
            'modulo'   => $moduloId,
            'activado' => true,
            'agenda'   => $resultado,
            'mensaje'  => "M08 activado. Se registraron {$resultado['operarios_registrados']} profesionales y {$resultado['productos_registrados']} recursos de renta.",
        ]);
    }

    // ... resto de la respuesta ...
}
```

### También en el endpoint `POST /api/config/modulos-rubro/{id}/toggle`:

```php
// Después de activar el módulo en modulos_activos:
if ($moduloId === 'M08' && $activando) {
    app(AgendaAutoRegistroService::class)->inicializarTenant();
}
```

---

## PARTE 6 — COMANDO ARTISAN (retroactivo)

Para tenants que ya tienen operarios/productos antes de activar M08:

```bash
docker exec benderandos_app sh -c "cd /app && php artisan make:command \
  Agenda/InicializarRecursosCommand"
```

Contenido: `app/Console/Commands/Agenda/InicializarRecursosCommand.php`

```php
<?php
namespace App\Console\Commands\Agenda;

use Illuminate\Console\Command;
use App\Services\AgendaAutoRegistroService;

class InicializarRecursosCommand extends Command
{
    protected $signature   = 'agenda:init-recursos {--tenant= : Slug del tenant específico}';
    protected $description = 'Crea AgendaRecursos automáticos para operarios y productos de renta (M08)';

    public function handle(AgendaAutoRegistroService $svc): int
    {
        $tenantSlug = $this->option('tenant');

        if ($tenantSlug) {
            $tenants = [\App\Models\Central\Tenant::find($tenantSlug)];
        } else {
            $tenants = \App\Models\Central\Tenant::all();
        }

        foreach ($tenants as $tenant) {
            $this->line("→ Procesando tenant: {$tenant->id}");
            tenancy()->initialize($tenant);

            if (!$svc->m08Activo()) {
                $this->warn("  M08 no activo en {$tenant->id} — saltando");
                tenancy()->end();
                continue;
            }

            $r = $svc->inicializarTenant();
            $this->info("  ✓ {$r['operarios_registrados']} operarios, {$r['productos_registrados']} productos de renta");
            tenancy()->end();
        }

        $this->info('Listo.');
        return self::SUCCESS;
    }
}
```

**Ejecutar:**
```bash
# Todos los tenants
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos"

# Solo un tenant
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos --tenant=demo-medico"
```

---

## PARTE 7 — RUTAS NUEVAS (agregar a routes/tenant.php)

Dentro del grupo `middleware(['check.module:M08'])` existente, agregar:

```php
// ── MI AGENDA (operario personal) ───────────────────────────────────
Route::prefix('api/agenda/mi')->group(function () {
    // Panel personal del operario: su propio recurso
    Route::get('/recurso',              [AgendaController::class, 'miRecurso']);
    Route::get('/dia',                  [AgendaController::class, 'miDia']);
    Route::get('/semana',               [AgendaController::class, 'miSemana']);
    Route::put('/horarios',             [AgendaController::class, 'misHorarios']);
    Route::post('/bloqueo',             [AgendaController::class, 'crearBloqueo']);
    Route::delete('/bloqueo/{id}',      [AgendaController::class, 'eliminarBloqueo']);
    Route::get('/citas',                [AgendaController::class, 'misCitas']);
    Route::put('/citas/{id}/estado',    [AgendaController::class, 'cambiarEstadoMia']);
    Route::put('/citas/{id}/notas',     [AgendaController::class, 'actualizarNotasMia']);
});

// Vista POS de mi agenda personal
Route::get('/pos/mi-agenda', [AgendaController::class, 'miAgendaIndex'])
     ->name('pos.mi-agenda');
```

---

## PARTE 8 — MÉTODOS NUEVOS EN AgendaController

Agregar en `app/Http/Controllers/Tenant/AgendaController.php`:

```php
use App\Services\AgendaAutoRegistroService;

// ── PANEL PERSONAL DEL OPERARIO ─────────────────────────────────────

/**
 * GET /pos/mi-agenda — Vista Blade personal del operario
 */
public function miAgendaIndex()
{
    $usuario = auth()->user();
    $recurso = AgendaRecurso::where('usuario_id', $usuario->id)->first();

    // Si no tiene recurso todavía, crearlo automáticamente
    if (!$recurso) {
        $recurso = app(AgendaAutoRegistroService::class)->registrarOperario($usuario);
    }

    return view('tenant.pos.mi-agenda', compact('recurso', 'usuario'));
}

/**
 * GET /api/agenda/mi/recurso — El AgendaRecurso del usuario logueado
 */
public function miRecurso()
{
    $usuario = auth()->user();
    $recurso = AgendaRecurso::with(['horarios','servicios'])
        ->where('usuario_id', $usuario->id)
        ->first();

    if (!$recurso) {
        $recurso = app(AgendaAutoRegistroService::class)->registrarOperario($usuario);
        $recurso->load(['horarios','servicios']);
    }

    return response()->json($recurso);
}

/**
 * GET /api/agenda/mi/dia?fecha=YYYY-MM-DD — Agenda del día solo del operario
 */
public function miDia(Request $r)
{
    $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
    $fecha   = $r->query('fecha', today()->toDateString());
    return response()->json($this->agendaService->getAgendaDia($fecha, [$recurso->id]));
}

/**
 * GET /api/agenda/mi/semana?fecha=YYYY-MM-DD — Semana del operario
 */
public function miSemana(Request $r)
{
    $recurso  = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
    $fechaRef = $r->query('fecha', today()->toDateString());
    $inicio   = \Carbon\Carbon::parse($fechaRef)->startOfWeek();
    $dias     = [];
    for ($i = 0; $i < 7; $i++) {
        $f      = $inicio->copy()->addDays($i)->toDateString();
        $dias[] = [
            'fecha' => $f,
            'citas' => $this->agendaService->getAgendaDia($f, [$recurso->id]),
        ];
    }
    return response()->json($dias);
}

/**
 * PUT /api/agenda/mi/horarios — El operario edita sus propios horarios
 */
public function misHorarios(Request $r)
{
    $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
    $r->validate(['horarios' => 'required|array']);

    foreach ($r->horarios as $h) {
        AgendaHorario::updateOrCreate(
            ['agenda_recurso_id' => $recurso->id, 'dia_semana' => $h['dia_semana']],
            [
                'hora_inicio'      => $h['hora_inicio'],
                'hora_fin'         => $h['hora_fin'],
                'activo'           => $h['activo'] ?? true,
                'duracion_slot_min'=> $h['duracion_slot_min'] ?? 30,
            ]
        );
    }

    return response()->json(['ok' => true, 'horarios' => $recurso->fresh()->horarios]);
}

/**
 * POST /api/agenda/mi/bloqueo — Crear bloqueo personal
 */
public function crearBloqueo(Request $r)
{
    $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
    $r->validate([
        'fecha_inicio'  => 'required|date',
        'fecha_fin'     => 'nullable|date|after_or_equal:fecha_inicio',
        'hora_inicio'   => 'nullable|date_format:H:i',
        'hora_fin'      => 'nullable|date_format:H:i',
        'motivo'        => 'nullable|string|max:200',
    ]);

    $bloqueo = \App\Models\Tenant\AgendaBloqueo::create([
        'agenda_recurso_id' => $recurso->id,
        'fecha_inicio'      => $r->fecha_inicio,
        'fecha_fin'         => $r->fecha_fin ?? $r->fecha_inicio,
        'hora_inicio'       => $r->hora_inicio,
        'hora_fin'          => $r->hora_fin,
        'motivo'            => $r->motivo ?? 'Bloqueo personal',
    ]);

    return response()->json($bloqueo, 201);
}

/**
 * DELETE /api/agenda/mi/bloqueo/{id}
 */
public function eliminarBloqueo(int $id)
{
    $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
    $bloqueo = \App\Models\Tenant\AgendaBloqueo::where('id', $id)
        ->where('agenda_recurso_id', $recurso->id)
        ->firstOrFail();
    $bloqueo->delete();
    return response()->json(['ok' => true]);
}

/**
 * GET /api/agenda/mi/citas?estado=&fecha_desde=&fecha_hasta=
 */
public function misCitas(Request $r)
{
    $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();

    $query = AgendaCita::where('agenda_recurso_id', $recurso->id)
        ->with(['servicio:id,nombre','cliente:id,nombre,telefono'])
        ->orderBy('fecha')->orderBy('hora_inicio');

    if ($r->estado) {
        $query->where('estado', $r->estado);
    }
    if ($r->fecha_desde) {
        $query->where('fecha', '>=', $r->fecha_desde);
    }
    if ($r->fecha_hasta) {
        $query->where('fecha', '<=', $r->fecha_hasta);
    }

    return response()->json($query->get());
}

/**
 * PUT /api/agenda/mi/citas/{id}/estado — El operario cambia estado de su cita
 */
public function cambiarEstadoMia(Request $r, int $id)
{
    $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
    $cita    = AgendaCita::where('id', $id)
        ->where('agenda_recurso_id', $recurso->id)
        ->firstOrFail();

    $r->validate(['estado' => 'required|in:pendiente,confirmada,en_curso,completada,cancelada']);
    $cita->update(['estado' => $r->estado]);
    return response()->json($cita->fresh());
}

/**
 * PUT /api/agenda/mi/citas/{id}/notas — El operario actualiza notas internas
 */
public function actualizarNotasMia(Request $r, int $id)
{
    $recurso = AgendaRecurso::where('usuario_id', auth()->id())->firstOrFail();
    $cita    = AgendaCita::where('id', $id)
        ->where('agenda_recurso_id', $recurso->id)
        ->firstOrFail();

    $r->validate(['notas_internas' => 'nullable|string|max:2000']);
    $cita->update(['notas_internas' => $r->notas_internas]);
    return response()->json(['ok' => true]);
}
```

---

## PARTE 9 — LAYOUT: MENÚ SIDEBAR PARA OPERARIOS

### `resources/views/tenant/layout.blade.php` — agregar en la sección nav de operario

En el sidebar, **dentro del bloque que ya existe para el rol operario**, agregar antes de "Stock & Ventas":

```blade
{{-- MI AGENDA (M08 + usuario vinculado a recurso) --}}
@if(in_array('M08', $modulosActivos ?? []))
    @php
        $tieneAgendaPersonal = \App\Models\Tenant\AgendaRecurso::where('usuario_id', auth()->id())->exists();
    @endphp
    @if($tieneAgendaPersonal)
    <div class="nav-section-lbl">Mi Agenda</div>
    <a href="/pos/mi-agenda"
       class="nav-link-item {{ request()->is('pos/mi-agenda*') ? 'nav-active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      Mi Agenda
    </a>
    <a href="/pos/mi-agenda#horarios"
       class="nav-link-item {{ request()->is('pos/mi-agenda*') && request()->fragment() === 'horarios' ? 'nav-active' : '' }}">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Mis Horarios
    </a>
    @endif
@endif
```

**Para el bottom nav móvil**, agregar junto a los otros ítems del operario:

```blade
@if(in_array('M08', $modulosActivos ?? []))
    @if(\App\Models\Tenant\AgendaRecurso::where('usuario_id', auth()->id())->exists())
    <a href="/pos/mi-agenda" class="mobile-nav-item {{ request()->is('pos/mi-agenda*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Mi Agenda
    </a>
    @endif
@endif
```

---

## PARTE 10 — VISTA: `pos/mi-agenda.blade.php`

### Crear: `resources/views/tenant/pos/mi-agenda.blade.php`

Esta es la vista **personal del operario**. Tiene 4 tabs: Hoy · Semana · Horarios · Bloqueos.

```blade
@extends('tenant.layout')
@section('title', 'Mi Agenda')

@section('content')
<div style="max-width:1100px; margin:0 auto; padding:24px 16px;">

{{-- HEADER --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
        <h1 style="font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:20px; letter-spacing:1px; text-transform:uppercase; color:#e8e8f0; margin:0;">
            Mi Agenda
        </h1>
        @if($recurso)
        <div style="margin-top:6px; display:flex; align-items:center; gap:8px;">
            <div style="width:10px;height:10px;border-radius:50%;background:{{ $recurso->color ?? '#00e5a0' }};"></div>
            <span style="font-size:13px; color:#7878a0;">{{ $recurso->nombre }}</span>
            @if($recurso->especialidad)
            <span style="font-family:'IBM Plex Mono',monospace; font-size:11px; color:#3a3a55; text-transform:uppercase;">
                · {{ $recurso->especialidad }}
            </span>
            @endif
        </div>
        @else
        <div style="font-size:13px; color:#f5c518; margin-top:4px;">
            ⚠️ Tu perfil de agenda no está configurado aún. El administrador debe activar M08.
        </div>
        @endif
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="irTab('bloqueos')" style="background:#18181e;border:1px solid #2a2a3a;color:#7878a0;padding:8px 16px;border-radius:8px;font-size:12px;cursor:pointer;">
            🚫 Agregar Bloqueo
        </button>
        @if($recurso && $recurso->auto_creado)
        <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:#3a3a55;padding:8px 12px;background:#111115;border:1px solid #1e1e28;border-radius:8px;align-self:center;">
            AUTO
        </div>
        @endif
    </div>
</div>

@if(!$recurso)
<div style="background:#18181e;border:1px solid #f5c518;border-radius:12px;padding:20px;text-align:center;color:#f5c518;">
    Sin acceso de agenda configurado. Contacta al administrador.
</div>
@else

{{-- TABS --}}
<div style="display:flex; border-bottom:1px solid #1e1e28; margin-bottom:0; gap:0;">
    @foreach([['hoy','Hoy'],['semana','Semana'],['horarios','Mis Horarios'],['bloqueos','Bloqueos']] as [$tab,$lbl])
    <button id="tab-{{ $tab }}" onclick="irTab('{{ $tab }}')"
        style="padding:10px 20px; font-size:13px; font-weight:600; color:#7878a0; border:none;
               background:transparent; cursor:pointer; border-bottom:2px solid transparent;
               transition:all .15s; white-space:nowrap;">
        {{ $lbl }}
    </button>
    @endforeach
</div>

{{-- PANEL HOY --}}
<div id="panel-hoy" class="ma-panel" style="padding:20px 0;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <button onclick="cambiarFecha(-1)" style="background:#18181e;border:1px solid #2a2a3a;color:#7878a0;padding:6px 12px;border-radius:6px;cursor:pointer;">‹</button>
            <span id="fechaHoyLabel" style="font-family:'IBM Plex Mono',monospace; font-size:14px; font-weight:600; color:#e8e8f0;"></span>
            <button onclick="cambiarFecha(1)" style="background:#18181e;border:1px solid #2a2a3a;color:#7878a0;padding:6px 12px;border-radius:6px;cursor:pointer;">›</button>
            <button onclick="irHoy()" style="background:rgba(0,229,160,.1);border:1px solid rgba(0,229,160,.3);color:#00e5a0;padding:6px 12px;border-radius:6px;font-size:11px;cursor:pointer;">Hoy</button>
        </div>
        <button onclick="abrirModalNuevaCita()"
            style="background:#00e5a0;color:#000;padding:8px 16px;border-radius:8px;font-weight:700;font-size:12px;cursor:pointer;border:none;">
            + Nueva Cita
        </button>
    </div>
    <div id="listaCitasHoy" style="display:flex;flex-direction:column;gap:8px;">
        <div style="text-align:center;padding:40px;color:#3a3a55;">Cargando...</div>
    </div>
</div>

{{-- PANEL SEMANA --}}
<div id="panel-semana" class="ma-panel" style="display:none; padding:20px 0; overflow-x:auto;">
    <div id="gridSemana" style="display:grid; grid-template-columns:repeat(7,1fr); gap:8px; min-width:700px;">
        <div style="text-align:center;padding:40px;color:#3a3a55;grid-column:1/-1;">Cargando...</div>
    </div>
</div>

{{-- PANEL HORARIOS --}}
<div id="panel-horarios" class="ma-panel" style="display:none; padding:20px 0;">
    <p style="color:#7878a0;font-size:13px;margin-bottom:20px;">
        Define los días y horas en que estás disponible. Los pacientes podrán reservar solo en estos horarios.
    </p>
    <div style="max-width:560px;">
        <div id="horariosForm" style="display:flex;flex-direction:column;gap:8px;">
            <div style="text-align:center;padding:40px;color:#3a3a55;">Cargando horarios...</div>
        </div>
        <button onclick="guardarMisHorarios()"
            style="margin-top:20px;background:#00e5a0;color:#000;padding:10px 24px;border-radius:8px;font-weight:700;cursor:pointer;border:none;font-size:13px;">
            Guardar Horarios
        </button>
    </div>
</div>

{{-- PANEL BLOQUEOS --}}
<div id="panel-bloqueos" class="ma-panel" style="display:none; padding:20px 0;">
    <p style="color:#7878a0;font-size:13px;margin-bottom:20px;">
        Bloquea días u horas específicas (vacaciones, reuniones, etc.).
        Ningún cliente podrá reservar en esos horarios.
    </p>
    <div style="display:flex; gap:16px; flex-wrap:wrap;">
        {{-- Formulario nuevo bloqueo --}}
        <div style="flex:0 0 320px; background:#111115; border:1px solid #1e1e28; border-radius:12px; padding:20px;">
            <h3 style="font-size:13px;font-weight:700;margin-bottom:16px;font-family:'IBM Plex Mono',monospace;text-transform:uppercase;color:#7878a0;letter-spacing:1px;">
                Nuevo Bloqueo
            </h3>
            <div style="margin-bottom:12px;">
                <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Desde</label>
                <input type="date" id="bFechaIni" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Hasta (vacío = solo ese día)</label>
                <input type="date" id="bFechaFin" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
            </div>
            <div style="display:flex;gap:8px;margin-bottom:12px;">
                <div style="flex:1">
                    <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Hora Inicio</label>
                    <input type="time" id="bHoraIni" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
                </div>
                <div style="flex:1">
                    <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Hora Fin</label>
                    <input type="time" id="bHoraFin" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Motivo</label>
                <input type="text" id="bMotivo" placeholder="Ej: Vacaciones, Reunión..." style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
            </div>
            <button onclick="guardarBloqueo()"
                style="width:100%;background:#00e5a0;color:#000;padding:10px;border-radius:8px;font-weight:700;cursor:pointer;border:none;font-size:13px;">
                Bloquear
            </button>
        </div>
        {{-- Lista de bloqueos activos --}}
        <div style="flex:1;min-width:280px;">
            <h3 style="font-size:13px;font-weight:700;margin-bottom:16px;font-family:'IBM Plex Mono',monospace;text-transform:uppercase;color:#7878a0;letter-spacing:1px;">
                Bloqueos Activos
            </h3>
            <div id="listaBloqueos" style="display:flex;flex-direction:column;gap:8px;">
                <div style="text-align:center;padding:20px;color:#3a3a55;">Cargando...</div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL NUEVA CITA --}}
<div id="modalNuevaCita" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.8);display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#111115;border:1px solid #2a2a3a;border-radius:16px;width:100%;max-width:420px;padding:24px;">
        <h3 style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:700;text-transform:uppercase;margin-bottom:20px;">Nueva Cita Manual</h3>
        <div style="margin-bottom:12px;">
            <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Nombre del Paciente</label>
            <input type="text" id="ncNombre" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
        </div>
        <div style="margin-bottom:12px;">
            <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Teléfono</label>
            <input type="tel" id="ncTelefono" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
        </div>
        <div style="display:flex;gap:8px;margin-bottom:12px;">
            <div style="flex:1">
                <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Fecha</label>
                <input type="date" id="ncFecha" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
            </div>
            <div style="flex:1">
                <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Hora Inicio</label>
                <input type="time" id="ncHoraIni" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
            </div>
        </div>
        <div style="margin-bottom:20px;">
            <label style="font-size:10px;color:#7878a0;text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:4px;">Servicio</label>
            <select id="ncServicio" style="width:100%;background:#18181e;border:1px solid #2a2a3a;border-radius:8px;padding:8px 12px;color:#e8e8f0;font-size:13px;">
                <option value="">Sin servicio específico</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button onclick="cerrarModalNuevaCita()" style="flex:1;background:#18181e;border:1px solid #2a2a3a;color:#7878a0;padding:10px;border-radius:8px;cursor:pointer;">Cancelar</button>
            <button onclick="crearCitaManual()" style="flex:2;background:#00e5a0;color:#000;padding:10px;border-radius:8px;font-weight:700;cursor:pointer;border:none;">Crear Cita</button>
        </div>
    </div>
</div>

@endif {{-- fin if($recurso) --}}

</div>

<style>
.ma-panel { min-height:200px; }
.cita-card {
    background:#111115; border:1px solid #1e1e28; border-radius:10px;
    padding:14px 16px; cursor:pointer; transition:border-color .15s;
}
.cita-card:hover { border-color:#2a2a3a; }
.cita-estado-badge {
    font-family:'IBM Plex Mono',monospace; font-size:10px; font-weight:700;
    text-transform:uppercase; padding:2px 8px; border-radius:4px;
}
.cita-estado-pendiente    { background:rgba(245,197,24,.1);  color:#f5c518;  border:1px solid rgba(245,197,24,.3); }
.cita-estado-confirmada   { background:rgba(0,229,160,.1);   color:#00e5a0;  border:1px solid rgba(0,229,160,.3); }
.cita-estado-en_curso     { background:rgba(0,196,255,.1);   color:#00c4ff;  border:1px solid rgba(0,196,255,.3); }
.cita-estado-completada   { background:rgba(100,100,120,.1); color:#7878a0;  border:1px solid rgba(100,100,120,.3); }
.cita-estado-cancelada    { background:rgba(255,63,91,.1);   color:#ff3f5b;  border:1px solid rgba(255,63,91,.3); }
.dia-col { background:#111115; border:1px solid #1e1e28; border-radius:10px; padding:10px; min-height:120px; }
.dia-col-hoy { border-color:rgba(0,229,160,.4); }
.bloqueo-item { background:#18181e; border:1px solid #2a2a3a; border-radius:8px; padding:12px 14px; display:flex; justify-content:space-between; align-items:center; }
</style>

<script>
const RECURSO_ID = {{ $recurso?->id ?? 'null' }};
const RECURSO_COLOR = '{{ $recurso?->color ?? "#00e5a0" }}';
let fechaActual = new Date();

// ── TABS ─────────────────────────────────────────────────
function irTab(tab) {
    ['hoy','semana','horarios','bloqueos'].forEach(t => {
        document.getElementById(`panel-${t}`).style.display = t === tab ? 'block' : 'none';
        const btn = document.getElementById(`tab-${t}`);
        if (!btn) return;
        btn.style.color = t === tab ? '#00e5a0' : '#7878a0';
        btn.style.borderBottomColor = t === tab ? '#00e5a0' : 'transparent';
    });
    if (tab === 'hoy')      cargarCitasHoy();
    if (tab === 'semana')   cargarSemana();
    if (tab === 'horarios') cargarHorarios();
    if (tab === 'bloqueos') cargarBloqueos();
}

// ── FECHA ─────────────────────────────────────────────────
function fmtFecha(d) {
    return d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'long' });
}
function toISO(d) { return d.toISOString().split('T')[0]; }

function cambiarFecha(delta) {
    fechaActual.setDate(fechaActual.getDate() + delta);
    cargarCitasHoy();
}
function irHoy() { fechaActual = new Date(); cargarCitasHoy(); }

// ── CITAS HOY ──────────────────────────────────────────────
async function cargarCitasHoy() {
    if (!RECURSO_ID) return;
    const el = document.getElementById('listaCitasHoy');
    const lbl = document.getElementById('fechaHoyLabel');
    lbl.textContent = fmtFecha(fechaActual);
    el.innerHTML = '<div style="text-align:center;padding:30px;color:#3a3a55;">Cargando...</div>';

    try {
        const data = await api('GET', `/api/agenda/mi/dia?fecha=${toISO(fechaActual)}`);
        const citas = Array.isArray(data) ? data : (data.citas ?? []);
        if (!citas.length) {
            el.innerHTML = `<div style="text-align:center;padding:30px;color:#3a3a55;font-size:13px;">Sin citas para este día</div>`;
            return;
        }
        el.innerHTML = citas.map(c => citaCard(c)).join('');
    } catch(e) {
        el.innerHTML = `<div style="color:#ff3f5b;text-align:center;padding:20px;font-size:13px;">${e.message}</div>`;
    }
}

function citaCard(c) {
    const estados = {
        pendiente:  ['⏳','cita-estado-pendiente'],
        confirmada: ['✅','cita-estado-confirmada'],
        en_curso:   ['▶️','cita-estado-en_curso'],
        completada: ['✔','cita-estado-completada'],
        cancelada:  ['✗','cita-estado-cancelada'],
    };
    const [icon, cls] = estados[c.estado] ?? ['?','cita-estado-pendiente'];
    return `
    <div class="cita-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div style="font-weight:700;font-size:14px;color:#e8e8f0;">${c.paciente_nombre}</div>
                <div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#7878a0;margin-top:2px;">
                    ${c.hora_inicio} – ${c.hora_fin}
                    ${c.servicio ? '· ' + c.servicio.nombre : ''}
                </div>
                ${c.paciente_telefono ? `<div style="font-size:11px;color:#3a3a55;margin-top:2px;">📞 ${c.paciente_telefono}</div>` : ''}
            </div>
            <span class="cita-estado-badge ${cls}">${icon} ${c.estado}</span>
        </div>
        <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap;">
            ${accionesCita(c)}
        </div>
        ${c.notas_internas ? `<div style="margin-top:8px;font-size:11px;color:#7878a0;background:#18181e;border-radius:6px;padding:6px 10px;">${c.notas_internas}</div>` : ''}
    </div>`;
}

function accionesCita(c) {
    const btns = [];
    const s = (label, estado) =>
        `<button onclick="cambiarEstadoCita(${c.id},'${estado}')"
            style="background:#18181e;border:1px solid #2a2a3a;color:#7878a0;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;">${label}</button>`;

    if (c.estado === 'pendiente')  btns.push(s('✅ Confirmar','confirmada'));
    if (c.estado === 'confirmada') btns.push(s('▶️ Iniciar','en_curso'));
    if (c.estado === 'en_curso')   btns.push(s('✔ Completar','completada'));
    if (!['completada','cancelada'].includes(c.estado)) {
        btns.push(s('✗ Cancelar','cancelada'));
    }
    btns.push(`<button onclick="editarNotas(${c.id})"
        style="background:#18181e;border:1px solid #2a2a3a;color:#7878a0;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;">📝 Notas</button>`);
    return btns.join('');
}

async function cambiarEstadoCita(id, estado) {
    try {
        await api('PUT', `/api/agenda/mi/citas/${id}/estado`, { estado });
        cargarCitasHoy();
        window.toast && toast(`Estado: ${estado}`, 'ok', 1500);
    } catch(e) { alert(e.message); }
}

function editarNotas(id) {
    const notas = prompt('Notas internas (solo las ves tú):');
    if (notas === null) return;
    api('PUT', `/api/agenda/mi/citas/${id}/notas`, { notas_internas: notas })
        .then(() => cargarCitasHoy())
        .catch(e => alert(e.message));
}

// ── SEMANA ───────────────────────────────────────────────
async function cargarSemana() {
    if (!RECURSO_ID) return;
    const grid = document.getElementById('gridSemana');
    grid.innerHTML = '<div style="text-align:center;padding:30px;color:#3a3a55;grid-column:1/-1;">Cargando...</div>';

    try {
        const data = await api('GET', `/api/agenda/mi/semana?fecha=${toISO(fechaActual)}`);
        const hoy  = toISO(new Date());
        grid.innerHTML = '';

        const dias = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
        (data || []).forEach((dia, i) => {
            const esHoy = dia.fecha === hoy;
            const citas = dia.citas ?? [];
            const col   = document.createElement('div');
            col.className = `dia-col${esHoy ? ' dia-col-hoy' : ''}`;

            const dLabel = new Date(dia.fecha + 'T12:00:00').toLocaleDateString('es-CL', { day:'numeric', month:'short' });
            col.innerHTML = `
                <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;font-weight:700;text-transform:uppercase;
                            color:${esHoy ? '#00e5a0' : '#3a3a55'};margin-bottom:8px;">
                    ${dias[i]} ${dLabel}
                </div>
                ${citas.length ? citas.map(c => `
                    <div style="background:#18181e;border:1px solid #2a2a3a;border-radius:6px;padding:6px 8px;margin-bottom:4px;">
                        <div style="font-size:11px;font-weight:600;color:#e8e8f0;">${c.paciente_nombre}</div>
                        <div style="font-family:'IBM Plex Mono',monospace;font-size:10px;color:#7878a0;">${c.hora_inicio}</div>
                    </div>`).join('')
                : `<div style="font-size:11px;color:#3a3a55;text-align:center;padding:12px 0;">Libre</div>`}
            `;
            grid.appendChild(col);
        });
    } catch(e) {
        grid.innerHTML = `<div style="color:#ff3f5b;grid-column:1/-1;text-align:center;">${e.message}</div>`;
    }
}

// ── HORARIOS ─────────────────────────────────────────────
const DIAS_SEMANA = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

async function cargarHorarios() {
    if (!RECURSO_ID) return;
    const el = document.getElementById('horariosForm');
    try {
        const r = await api('GET', '/api/agenda/mi/recurso');
        const hs = r.horarios ?? [];
        const map = {};
        hs.forEach(h => map[h.dia_semana] = h);

        el.innerHTML = DIAS_SEMANA.map((dia, i) => {
            const dNum = i + 1;
            const h = map[dNum] || { hora_inicio: '09:00', hora_fin: '18:00', activo: 0, duracion_slot_min: 30 };
            return `
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:10px 12px;background:#111115;border:1px solid #1e1e28;border-radius:8px;">
                <div style="display:flex;align-items:center;gap:10px;min-width:100px;">
                    <input type="checkbox" id="ha-${dNum}" ${h.activo ? 'checked' : ''}
                        style="width:16px;height:16px;accent-color:#00e5a0;cursor:pointer;">
                    <span style="font-size:13px;font-weight:500;color:#e8e8f0;">${dia}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="time" id="hi-${dNum}" value="${h.hora_inicio}"
                        style="background:#18181e;border:1px solid #2a2a3a;border-radius:6px;
                               padding:4px 8px;color:#e8e8f0;font-size:12px;font-family:'IBM Plex Mono',monospace;">
                    <span style="color:#3a3a55;font-size:11px;">–</span>
                    <input type="time" id="hf-${dNum}" value="${h.hora_fin}"
                        style="background:#18181e;border:1px solid #2a2a3a;border-radius:6px;
                               padding:4px 8px;color:#e8e8f0;font-size:12px;font-family:'IBM Plex Mono',monospace;">
                    <input type="number" id="hs-${dNum}" value="${h.duracion_slot_min}" min="5" max="120" step="5"
                        style="background:#18181e;border:1px solid #2a2a3a;border-radius:6px;
                               padding:4px 6px;color:#e8e8f0;font-size:11px;width:50px;text-align:center;">
                    <span style="font-size:10px;color:#3a3a55;">min</span>
                </div>
            </div>`;
        }).join('');
    } catch(e) {
        el.innerHTML = `<div style="color:#ff3f5b;text-align:center;">${e.message}</div>`;
    }
}

async function guardarMisHorarios() {
    const horarios = [];
    for (let d = 1; d <= 7; d++) {
        horarios.push({
            dia_semana:       d,
            activo:           document.getElementById(`ha-${d}`).checked ? 1 : 0,
            hora_inicio:      document.getElementById(`hi-${d}`).value,
            hora_fin:         document.getElementById(`hf-${d}`).value,
            duracion_slot_min: parseInt(document.getElementById(`hs-${d}`).value) || 30,
        });
    }
    try {
        await api('PUT', '/api/agenda/mi/horarios', { horarios });
        window.toast && toast('Horarios guardados', 'ok', 1500);
    } catch(e) { alert(e.message); }
}

// ── BLOQUEOS ─────────────────────────────────────────────
async function cargarBloqueos() {
    // Obtenemos citas con estado "bloqueado" o simplemente la lista de bloqueos
    const el = document.getElementById('listaBloqueos');
    el.innerHTML = '<div style="text-align:center;padding:20px;color:#3a3a55;">Cargando...</div>';
    try {
        // Reutilizamos la vista de slots del recurso filtrada por "bloqueos"
        // Llamamos directamente al endpoint de citas con estado no válido para bloqueos
        // Como no hay endpoint dedicado de bloqueos en mi agenda, cargamos desde la agenda de recursos
        const r = await api('GET', `/api/agenda/recursos`);
        const recurso = (r || []).find(x => x.id === RECURSO_ID);
        const bloqueos = recurso?.bloqueos ?? [];
        if (!bloqueos.length) {
            el.innerHTML = '<div style="text-align:center;color:#3a3a55;font-size:13px;padding:20px;">Sin bloqueos activos</div>';
            return;
        }
        el.innerHTML = bloqueos.map(b => `
            <div class="bloqueo-item">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#e8e8f0;">${b.motivo || 'Bloqueo'}</div>
                    <div style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:#7878a0;margin-top:2px;">
                        ${b.fecha_inicio} ${b.hora_inicio ? '· ' + b.hora_inicio + '–' + b.hora_fin : '(todo el día)'}
                    </div>
                </div>
                <button onclick="eliminarBloqueo(${b.id})"
                    style="background:rgba(255,63,91,.1);border:1px solid rgba(255,63,91,.3);color:#ff3f5b;
                           padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;">Eliminar</button>
            </div>`).join('');
    } catch(e) {
        el.innerHTML = `<div style="color:#ff3f5b;text-align:center;">${e.message}</div>`;
    }
}

async function guardarBloqueo() {
    const data = {
        fecha_inicio: document.getElementById('bFechaIni').value,
        fecha_fin:    document.getElementById('bFechaFin').value || null,
        hora_inicio:  document.getElementById('bHoraIni').value || null,
        hora_fin:     document.getElementById('bHoraFin').value || null,
        motivo:       document.getElementById('bMotivo').value || null,
    };
    if (!data.fecha_inicio) { alert('La fecha de inicio es obligatoria'); return; }
    try {
        await api('POST', '/api/agenda/mi/bloqueo', data);
        window.toast && toast('Bloqueo creado', 'ok', 1500);
        ['bFechaIni','bFechaFin','bHoraIni','bHoraFin','bMotivo'].forEach(id => {
            document.getElementById(id).value = '';
        });
        cargarBloqueos();
    } catch(e) { alert(e.message); }
}

async function eliminarBloqueo(id) {
    if (!confirm('¿Eliminar este bloqueo?')) return;
    try {
        await api('DELETE', `/api/agenda/mi/bloqueo/${id}`);
        cargarBloqueos();
    } catch(e) { alert(e.message); }
}

// ── MODAL NUEVA CITA ─────────────────────────────────────
function abrirModalNuevaCita() {
    document.getElementById('modalNuevaCita').style.display = 'flex';
    document.getElementById('ncFecha').value = toISO(fechaActual);
    // Cargar servicios del recurso en el select
    api('GET', '/api/agenda/mi/recurso').then(r => {
        const sel = document.getElementById('ncServicio');
        (r.servicios || []).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `${s.nombre} (${s.duracion_min}min · $${s.precio?.toLocaleString('es-CL')})`;
            sel.appendChild(opt);
        });
    });
}

function cerrarModalNuevaCita() {
    document.getElementById('modalNuevaCita').style.display = 'none';
}

async function crearCitaManual() {
    const servId = parseInt(document.getElementById('ncServicio').value) || null;
    let horaFin = document.getElementById('ncHoraIni').value;
    // Calcular hora_fin desde la duración del servicio si está disponible
    // Por ahora, 30 min por defecto si no hay servicio
    if (!horaFin) { alert('La hora es obligatoria'); return; }
    const [h, m] = horaFin.split(':').map(Number);
    const end = new Date(0, 0, 0, h, m + 30);
    horaFin = `${String(end.getHours()).padStart(2,'0')}:${String(end.getMinutes()).padStart(2,'0')}`;

    const payload = {
        agenda_recurso_id:  RECURSO_ID,
        agenda_servicio_id: servId,
        fecha:              document.getElementById('ncFecha').value,
        hora_inicio:        document.getElementById('ncHoraIni').value,
        hora_fin:           horaFin,
        paciente_nombre:    document.getElementById('ncNombre').value,
        paciente_telefono:  document.getElementById('ncTelefono').value,
        estado:             'confirmada',
    };
    if (!payload.paciente_nombre) { alert('El nombre es obligatorio'); return; }
    try {
        await api('POST', '/api/agenda/citas', payload);
        cerrarModalNuevaCita();
        cargarCitasHoy();
        window.toast && toast('Cita creada', 'ok', 1500);
    } catch(e) { alert(e.message); }
}

// ── INIT ────────────────────────────────────────────────
irTab('hoy');
</script>
@endsection
```

---

## PARTE 11 — SERVICIOS EN ENDPOINT EXISTENTE (agregar a `getRecursos`)

En `AgendaController::getRecursos()`, asegurarse de incluir `servicios` y `bloqueos` en el `with()`:

```php
public function getRecursos()
{
    $recursos = AgendaRecurso::with(['horarios', 'servicios', 'bloqueos'])
        ->where('activo', true)
        ->orderBy('orden')
        ->get();
    return response()->json($recursos);
}
```

Si `AgendaBloqueo` no está en la relación de `AgendaRecurso`, agregar:

```php
// En AgendaRecurso.php
public function bloqueos()
{
    return $this->hasMany(AgendaBloqueo::class, 'agenda_recurso_id')
                ->where('fecha_fin', '>=', now()->toDateString())
                ->orderBy('fecha_inicio');
}
```

---

## PARTE 12 — LANDING PÚBLICO: incluir productos de renta

En `AgendaController::publicRecursos()`, ya devuelve todos los recursos activos.
Los productos de renta quedan expuestos automáticamente porque son `AgendaRecurso` con `tipo = 'recurso'`.
El landing los renderiza igual que a los profesionales.

Para que el label sea correcto en el landing, modificar levemente la vista pública
`resources/views/public/agenda.blade.php` — el título del paso 1:

```html
<!-- Cambiar "Elige tu profesional" por algo dinámico -->
<h2 class="step-title">¿Con quién quieres reservar?</h2>
<p class="step-subtitle">Selecciona un profesional o recurso disponible</p>
```

Y en el JS del landing, en el renderizado de cada tarjeta:

```javascript
// Mostrar diferente ícono según tipo
const icono = r.tipo === 'profesional' ? '👤' : '📦';
```

---

## PARTE 13 — SEEDER DEMO (actualizar AgendaDemoSeeder)

En `AgendaDemoSeeder`, agregar llamada al servicio de auto-registro para validar que funciona:

```php
// Al final del run():
$svc = new \App\Services\AgendaAutoRegistroService();
$resultado = $svc->inicializarTenant();
$this->command->info("Auto-registro: {$resultado['operarios_registrados']} operarios, {$resultado['productos_registrados']} productos de renta");
```

---

## PARTE 14 — COMANDOS DE EJECUCIÓN COMPLETOS

```bash
# 1. Ejecutar migración nueva en todos los tenants
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate \
  --path=database/migrations/tenant"

# 2. Inicializar recursos automáticamente en tenants con M08
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos \
  --tenant=demo-medico"
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos \
  --tenant=demo-padel"
docker exec benderandos_app sh -c "cd /app && php artisan agenda:init-recursos \
  --tenant=demo-legal"

# 3. Verificar que se crearon recursos
docker exec -it benderandos_app sh -c "cd /app && php artisan tinker"
# >>> tenancy()->initialize(App\Models\Central\Tenant::find('demo-medico'))
# >>> App\Models\Tenant\AgendaRecurso::all()->pluck('nombre','id')

# 4. Limpiar caches
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"

# 5. Verificar rutas mi-agenda
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=mi-agenda"
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=api/agenda/mi"
```

---

## RESUMEN DE ARCHIVOS

| Acción | Archivo |
|---|---|
| NUEVA MIGRACIÓN | `database/migrations/tenant/XXXX_add_producto_id_to_agenda_recursos_table.php` |
| MODIFICAR | `app/Models/Tenant/AgendaRecurso.php` — campos + relaciones nuevas |
| NUEVO SERVICIO | `app/Services/AgendaAutoRegistroService.php` |
| NUEVO OBSERVER | `app/Observers/Tenant/UsuarioAgendaObserver.php` |
| NUEVO OBSERVER | `app/Observers/Tenant/ProductoAgendaObserver.php` |
| MODIFICAR | `app/Providers/AppServiceProvider.php` — registrar observers |
| NUEVO COMANDO | `app/Console/Commands/Agenda/InicializarRecursosCommand.php` |
| MODIFICAR | `app/Http/Controllers/Tenant/AgendaController.php` — métodos `mi*` |
| MODIFICAR | `app/Http/Controllers/Tenant/ConfigRubroController.php` — hook M08 |
| MODIFICAR | `routes/tenant.php` — rutas `/api/agenda/mi/*` + `/pos/mi-agenda` |
| MODIFICAR | `resources/views/tenant/layout.blade.php` — menú "Mi Agenda" condicional |
| CREAR VISTA | `resources/views/tenant/pos/mi-agenda.blade.php` — panel personal operario |
| MODIFICAR | `database/seeders/Tenant/AgendaDemoSeeder.php` — llamar init |

---

## FLUJO COMPLETO (end-to-end)

```
1. Admin activa M08 en Config
   → ConfigRubroController llama AgendaAutoRegistroService::inicializarTenant()
   → Se crean AgendaRecurso para todos los operarios activos
   → Se crean AgendaRecurso para todos los productos con tipo_producto='renta'
   → Se crea AgendaConfig por defecto
   → Se crean horarios L-V 09-18 por defecto para cada recurso

2. Admin crea nuevo operario
   → UsuarioAgendaObserver::created() dispara
   → AgendaAutoRegistroService::registrarOperario() crea el recurso automáticamente
   → Operario ya tiene menú "Mi Agenda" en su sidebar

3. Admin crea producto con tipo_producto='renta'
   → ProductoAgendaObserver::created() dispara
   → AgendaAutoRegistroService::registrarProductoRenta() crea recurso + servicio auto
   → El recurso aparece en el landing público para reservar

4. Operario entra a /pos/mi-agenda
   → Ve sus citas del día con acciones (confirmar, iniciar, completar)
   → Puede ver su semana completa
   → Puede editar sus horarios disponibles
   → Puede crear bloqueos (vacaciones, reuniones)
   → Puede crear citas manualmente

5. Cliente va a /agenda (landing público)
   → Ve lista de profesionales Y recursos de renta
   → Selecciona, elige servicio, fecha, slot disponible
   → Deja nombre y teléfono → cita creada
   → El operario la ve en su panel al instante
```

---

*BenderAnd ERP · SPEC M08 Agenda Auto-Recursos · 2026-03-26 · Antigravity*
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Vanilla JS + Blade*
*Container: benderandos_app · /app · observers + service + vista mi-agenda*
