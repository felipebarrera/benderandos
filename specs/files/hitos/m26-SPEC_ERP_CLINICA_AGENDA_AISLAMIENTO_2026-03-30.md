# SPEC — Módulo Clínica / Agenda Universal + Aislamiento por Profesional + Onboarding Admin
**Sistema:** ERP Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Sanctum
**Fecha:** 2026-03-30
**Módulos requeridos:** M01 M07 M08 M09 M10 M20 M21 M32
**Industrias que usan M08 Agenda:** Médico · Dentista · Abogados · Salón/Spa · Pádel · Gimnasio · Veterinaria
**Depende de:** H1 venta base completado, migraciones tenant corriendo, RubroConfig existente

---

## OBJETIVO

Implementar tres cosas en una sola sesión:

1. **Aislamiento por profesional** — cada usuario con `rol=medico` (o cualquier profesional) ve ÚNICAMENTE sus propias citas, pacientes asignados y notas clínicas. Es imposible ver datos de otro profesional. Esto aplica a nivel de DB (Global Scope Eloquent), middleware (Policy) y frontend (menú dinámico).

2. **Módulo M08 Agenda universal** — la agenda funciona igual para médicos, dentistas, abogados, estilistas, instructores de gym y cualquier rubro que active M08. Las etiquetas cambian (paciente/cliente/caso, consulta/sesión/hora), la lógica de aislamiento es la misma.

3. **Onboarding guiado en panel admin** — al primer login de un tenant clínica, aparece un drawer lateral con checklist de primeros pasos (agregar profesionales, configurar servicios, primera cita de prueba, SII, WhatsApp). Se puede cerrar y reabrir. El progreso se persiste en `onboarding_progress` del tenant.

---

## CONTEXTO TÉCNICO

### Roles en rubros con agenda

```
admin           → Ve toda la agenda, todas las citas, métricas globales. NO ve notas cifradas.
recepcionista   → Ve agenda de todos los profesionales, hace check-in, cobra. NO ve notas.
medico          → Ve SOLO sus citas y las notas cifradas de sus propios pacientes.
cajero          → Alias de recepcionista en este contexto.
```

### Stack activo

```
Container  : benderandos_app (php:8.4-cli-alpine — usar sh, NO bash)
DB         : benderandos_pg (postgres:16)
Ejecutar   : docker exec benderandos_app sh -c "cd /app && <cmd>"
Tenancy    : stancl/tenancy v3 · PostgreSQLSchemaManager · schema por tenant
Auth       : Laravel Sanctum · campo contraseña: clave_hash (NO password)
Modelo usr : App\Models\Tenant\Usuario · tabla: users · rol: string
```

---

## PARTE 1: MODELO DE DATOS

### 1.1 Migraciones tenant — columnas requeridas

```bash
php artisan make:migration add_medico_id_to_citas_table --path=database/migrations/tenant
php artisan make:migration create_notas_clinicas_table --path=database/migrations/tenant
php artisan make:migration create_profesionales_config_table --path=database/migrations/tenant
php artisan make:migration create_onboarding_progress_table --path=database/migrations/tenant
php artisan tenants:migrate
```

### 1.2 `add_medico_id_to_citas_table`

```php
public function up(): void
{
    Schema::table('citas', function (Blueprint $table) {
        // FK al usuario profesional asignado a esta cita
        $table->unsignedBigInteger('medico_id')->nullable()->after('cliente_id');
        $table->foreign('medico_id')->references('id')->on('users')->nullOnDelete();

        // Estado enriquecido (confirmada|espera|en_consulta|atendida|cancelada|no_show)
        $table->string('estado', 30)->default('confirmada')->change();

        // Notas previas (NO cifradas — son instrucciones de recepción)
        $table->text('observaciones_recepcion')->nullable();
    });
}
```

### 1.3 `create_notas_clinicas_table`

```php
public function up(): void
{
    Schema::create('notas_clinicas', function (Blueprint $table) {
        $table->id();

        // Paciente (FK clientes)
        $table->unsignedBigInteger('cliente_id')->index();
        $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();

        // Cita asociada (opcional — puede ser nota suelta)
        $table->unsignedBigInteger('cita_id')->nullable();
        $table->foreign('cita_id')->references('id')->on('citas')->nullOnDelete();

        // Autor de la nota — SIEMPRE el profesional, nunca admin/recep
        $table->unsignedBigInteger('medico_id')->index();
        $table->foreign('medico_id')->references('id')->on('users');

        // Contenido cifrado (el ERP cifra con AES-256 antes de guardar)
        // La columna guarda el texto cifrado en base64
        $table->text('contenido_cifrado');

        // IV del cifrado (generado por nota, único)
        $table->string('iv_hex', 64);

        // Tipo para categorizar sin revelar contenido
        // 'anamnesis'|'diagnostico'|'indicaciones'|'antecedentes'|'evolucion'|'otro'
        $table->string('tipo', 30)->default('anamnesis');

        // Fecha de la consulta a la que corresponde (puede diferir de created_at)
        $table->date('fecha_consulta');

        // Visibilidad: solo el profesional puede ver sus notas
        // Este campo es redundante con medico_id pero ayuda a auditoría
        $table->boolean('solo_autor')->default(true);

        $table->timestamps();
        $table->softDeletes(); // Las notas no se borran definitivamente

        // Índice compuesto para consultas frecuentes
        $table->index(['cliente_id', 'medico_id']);
        $table->index(['medico_id', 'fecha_consulta']);
    });
}
```

### 1.4 `create_profesionales_config_table`

```php
// Configuración por profesional dentro del tenant
// Permite al admin personalizar cada usuario médico/profesional
public function up(): void
{
    Schema::create('profesionales_config', function (Blueprint $table) {
        $table->id();

        // FK al usuario del tenant
        $table->unsignedBigInteger('usuario_id')->unique();
        $table->foreign('usuario_id')->references('id')->on('users')->cascadeOnDelete();

        // Presentación pública
        $table->string('especialidad', 100)->nullable();      // "Medicina General", "Ortodoncia"
        $table->string('titulo', 100)->nullable();             // "Dr.", "Dra.", "Lic.", ""
        $table->string('codigo_prestador', 50)->nullable();    // Para SII honorarios
        $table->string('color_agenda', 7)->default('#3dd9eb'); // Color en vista calendario

        // Horario de atención (JSON por día)
        // {"lunes":{"inicio":"09:00","fin":"18:00","activo":true},...}
        $table->json('horario')->nullable();

        // Duración default de cita en minutos
        $table->unsignedSmallInteger('duracion_cita_min')->default(30);

        // Intervalo entre citas en minutos (para autocompletar agenda)
        $table->unsignedSmallInteger('intervalo_min')->default(30);

        // Máximo de citas por día
        $table->unsignedSmallInteger('max_citas_dia')->nullable();

        // Visibilidad en el portal de agendamiento web (M25)
        $table->boolean('visible_portal')->default(true);

        // El profesional puede ver agenda de otros (false por defecto = aislamiento total)
        $table->boolean('puede_ver_agenda_global')->default(false);

        // Servicios que ofrece este profesional (array de producto IDs)
        $table->json('servicios_ids')->nullable();

        $table->timestamps();
    });
}
```

### 1.5 `create_onboarding_progress_table`

```php
public function up(): void
{
    Schema::create('onboarding_progress', function (Blueprint $table) {
        $table->id();

        // Cada step tiene un ID fijo (ver PARTE 4)
        $table->string('step_id', 50)->unique();

        // Estados: pendiente|en_progreso|completado|saltado
        $table->string('estado', 20)->default('pendiente');

        // Quién lo completó y cuándo
        $table->unsignedBigInteger('completado_por')->nullable();
        $table->timestampTz('completado_at')->nullable();

        // Datos extra del step (ej: cuántos profesionales se agregaron)
        $table->json('meta')->nullable();

        $table->timestamps();
    });
}
```

---

## PARTE 2: MODELOS ELOQUENT

### 2.1 `App\Models\Tenant\Cita.php` — con Global Scope de aislamiento

```php
<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'cliente_id', 'medico_id', 'fecha', 'hora_inicio', 'hora_fin',
        'estado', 'tipo_servicio', 'producto_id', 'precio',
        'observaciones_recepcion', 'canal_origen',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // ── AISLAMIENTO CENTRAL ────────────────────────────────────────
    // Este scope se aplica automáticamente en TODAS las queries
    // Un médico nunca puede ver citas de otro médico
    protected static function booted(): void
    {
        static::addGlobalScope('profesional_scope', function (Builder $query) {
            $user = auth('sanctum')->user();

            // Solo aplicar si hay usuario autenticado y es profesional
            if ($user && $user->rol === 'medico') {
                $query->where('citas.medico_id', $user->id);
            }

            // Recepcionista y admin ven todo — no se aplica scope
        });
    }
    // ──────────────────────────────────────────────────────────────

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'medico_id');
    }

    public function notas(): HasMany
    {
        // Las notas también tienen su propio scope de aislamiento
        return $this->hasMany(NotaClinica::class, 'cita_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    // Scope manual para cuando admin quiere filtrar por profesional
    public function scopeDelProfesional(Builder $query, int $medicoId): Builder
    {
        return $query->where('medico_id', $medicoId);
    }

    // Scope para agenda del día
    public function scopeDelDia(Builder $query, string $fecha): Builder
    {
        return $query->whereDate('fecha', $fecha)->orderBy('hora_inicio');
    }
}
```

### 2.2 `App\Models\Tenant\NotaClinica.php` — aislamiento estricto

```php
<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaClinica extends Model
{
    use SoftDeletes;

    protected $table = 'notas_clinicas';

    // NUNCA exponer contenido_cifrado en serialización accidental
    protected $hidden = ['contenido_cifrado', 'iv_hex'];

    protected $fillable = [
        'cliente_id', 'cita_id', 'medico_id',
        'contenido_cifrado', 'iv_hex', 'tipo', 'fecha_consulta', 'solo_autor',
    ];

    protected $casts = [
        'fecha_consulta' => 'date',
        'solo_autor' => 'boolean',
    ];

    // ── AISLAMIENTO NOTAS ─────────────────────────────────────────
    // Las notas solo las ve el autor (medico_id == usuario autenticado)
    // Admin y recepcionista NO pueden ver el contenido cifrado
    // Este scope no tiene excepciones — ni el admin ve notas ajenas
    protected static function booted(): void
    {
        static::addGlobalScope('nota_autor_scope', function (Builder $query) {
            $user = auth('sanctum')->user();

            if ($user) {
                // Solo el autor puede acceder a sus notas
                // Esto aplica para TODOS los roles incluyendo admin
                $query->where('notas_clinicas.medico_id', $user->id);
            }
        });
    }
    // ──────────────────────────────────────────────────────────────

    public function cliente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function medico(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'medico_id');
    }
}
```

### 2.3 `App\Models\Tenant\ProfesionalConfig.php`

```php
<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ProfesionalConfig extends Model
{
    protected $table = 'profesionales_config';

    protected $fillable = [
        'usuario_id', 'especialidad', 'titulo', 'codigo_prestador',
        'color_agenda', 'horario', 'duracion_cita_min', 'intervalo_min',
        'max_citas_dia', 'visible_portal', 'puede_ver_agenda_global', 'servicios_ids',
    ];

    protected $casts = [
        'horario' => 'array',
        'servicios_ids' => 'array',
        'visible_portal' => 'boolean',
        'puede_ver_agenda_global' => 'boolean',
    ];

    // Sin Global Scope — admin siempre ve config de todos los profesionales
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
```

---

## PARTE 3: POLICIES — SEGUNDA CAPA DE SEGURIDAD

Los Global Scopes son la primera capa. Las Policies son la segunda:
si alguien bypasea el scope (por ejemplo vía `withoutGlobalScopes()`), la Policy lo bloquea.

### 3.1 Crear policies

```bash
php artisan make:policy CitaPolicy --model=Tenant/Cita
php artisan make:policy NotaClinicaPolicy --model=Tenant/NotaClinica
```

### 3.2 `App\Policies\CitaPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Tenant\Cita;
use App\Models\Tenant\Usuario;

class CitaPolicy
{
    // Ver una cita individual
    public function view(Usuario $user, Cita $cita): bool
    {
        // Admin y recepcionista ven todas
        if (in_array($user->rol, ['admin', 'super_admin', 'recepcionista', 'cajero'])) {
            return true;
        }

        // Médico solo ve las suyas
        if ($user->rol === 'medico') {
            return $cita->medico_id === $user->id;
        }

        return false;
    }

    // Crear cita — recepcionista y admin siempre pueden
    // Médico solo puede crear en su propia agenda
    public function create(Usuario $user): bool
    {
        return in_array($user->rol, ['admin', 'super_admin', 'recepcionista', 'cajero', 'medico']);
    }

    // Actualizar — mismas reglas que view
    public function update(Usuario $user, Cita $cita): bool
    {
        return $this->view($user, $cita);
    }

    // Cancelar — recepcionista y admin
    public function cancel(Usuario $user, Cita $cita): bool
    {
        if (in_array($user->rol, ['admin', 'super_admin', 'recepcionista'])) {
            return true;
        }
        return false;
    }
}
```

### 3.3 `App\Policies\NotaClinicaPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Tenant\NotaClinica;
use App\Models\Tenant\Usuario;

class NotaClinicaPolicy
{
    // Ver una nota: SOLO el autor
    // Sin excepción — ni admin, ni recepcionista
    public function view(Usuario $user, NotaClinica $nota): bool
    {
        return $nota->medico_id === $user->id;
    }

    // Crear nota: solo profesionales
    public function create(Usuario $user): bool
    {
        return $user->rol === 'medico';
    }

    // Actualizar nota: solo el autor, y solo notas propias recientes (< 24h)
    public function update(Usuario $user, NotaClinica $nota): bool
    {
        return $nota->medico_id === $user->id
            && $nota->created_at->diffInHours(now()) < 24;
    }

    // Las notas nunca se eliminan definitivamente (SoftDelete)
    public function delete(Usuario $user, NotaClinica $nota): bool
    {
        return false; // Auditoría regulatoria — nadie puede eliminar
    }
}
```

### 3.4 Registrar policies en `AuthServiceProvider`

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    \App\Models\Tenant\Cita::class        => \App\Policies\CitaPolicy::class,
    \App\Models\Tenant\NotaClinica::class => \App\Policies\NotaClinicaPolicy::class,
];
```

---

## PARTE 4: SERVICIO DE CIFRADO NOTAS

```php
// app/Services/NotaCifradaService.php

<?php

namespace App\Services;

class NotaCifradaService
{
    private string $masterKey;

    public function __construct()
    {
        // La clave maestra viene de .env — nunca del tenant
        // APP_NOTES_KEY debe ser 32 bytes en hex (64 chars)
        $this->masterKey = hex2bin(config('app.notes_key'));
    }

    // Cifrar antes de guardar en DB
    public function cifrar(string $texto): array
    {
        $iv = random_bytes(16);
        $cifrado = openssl_encrypt(
            $texto,
            'aes-256-cbc',
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return [
            'contenido_cifrado' => base64_encode($cifrado),
            'iv_hex'            => bin2hex($iv),
        ];
    }

    // Descifrar al leer — solo en contexto autorizado (Policy ya validó)
    public function descifrar(string $cifradoBase64, string $ivHex): string
    {
        $cifrado = base64_decode($cifradoBase64);
        $iv = hex2bin($ivHex);

        $texto = openssl_decrypt(
            $cifrado,
            'aes-256-cbc',
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($texto === false) {
            throw new \RuntimeException('Error al descifrar nota clínica');
        }

        return $texto;
    }
}
```

```bash
# Agregar a .env:
# APP_NOTES_KEY=<64 caracteres hex — generado con: openssl rand -hex 32>
```

---

## PARTE 5: CONTROLADOR AGENDA (M08)

```bash
php artisan make:controller Api/Tenant/AgendaController --api
```

```php
// app/Http/Controllers/Tenant/AgendaController.php

<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Cita;
use App\Models\Tenant\ProfesionalConfig;
use App\Models\Tenant\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgendaController extends Controller
{
    // GET /api/agenda?fecha=2026-03-31&medico_id=2
    // Médico: ignora medico_id (Global Scope ya filtra)
    // Admin/Recepcionista: puede pasar medico_id para filtrar
    public function index(Request $request): JsonResponse
    {
        $fecha = $request->get('fecha', today()->toDateString());
        $query = Cita::with(['cliente', 'medico', 'producto'])
            ->delDia($fecha);

        // Admin/recep puede filtrar por profesional específico
        $user = auth('sanctum')->user();
        if (
            in_array($user->rol, ['admin', 'recepcionista', 'cajero'])
            && $request->has('medico_id')
        ) {
            $query->where('medico_id', $request->integer('medico_id'));
        }

        $citas = $query->get();

        return response()->json([
            'fecha'  => $fecha,
            'citas'  => $citas,
            'total'  => $citas->count(),
        ]);
    }

    // GET /api/agenda/profesionales — lista de profesionales para el selector
    // Médico: solo se ve a sí mismo
    // Admin/Recep: ve todos
    public function profesionales(): JsonResponse
    {
        $user = auth('sanctum')->user();

        if ($user->rol === 'medico') {
            $profesionales = Usuario::where('id', $user->id)
                ->with('profesionalConfig')
                ->get();
        } else {
            $profesionales = Usuario::where('rol', 'medico')
                ->orWhere(function ($q) {
                    $q->whereHas('profesionalConfig');
                })
                ->with('profesionalConfig')
                ->orderBy('nombre')
                ->get();
        }

        return response()->json($profesionales);
    }

    // GET /api/agenda/semana?desde=2026-03-31&medico_id=2
    public function semana(Request $request): JsonResponse
    {
        $desde = $request->get('desde', today()->startOfWeek()->toDateString());
        $hasta = now()->parse($desde)->addDays(6)->toDateString();

        $query = Cita::with(['cliente', 'medico'])
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')
            ->orderBy('hora_inicio');

        $user = auth('sanctum')->user();
        if (
            in_array($user->rol, ['admin', 'recepcionista'])
            && $request->has('medico_id')
        ) {
            $query->where('medico_id', $request->integer('medico_id'));
        }

        return response()->json([
            'desde' => $desde,
            'hasta' => $hasta,
            'citas' => $query->get()->groupBy('fecha'),
        ]);
    }

    // POST /api/agenda/citas — crear cita
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Cita::class);

        $validated = $request->validate([
            'cliente_id'              => 'required|exists:clientes,id',
            'medico_id'               => 'required|exists:users,id',
            'fecha'                   => 'required|date|after_or_equal:today',
            'hora_inicio'             => 'required|date_format:H:i',
            'hora_fin'                => 'required|date_format:H:i|after:hora_inicio',
            'producto_id'             => 'nullable|exists:productos,id',
            'observaciones_recepcion' => 'nullable|string|max:500',
        ]);

        // Médico solo puede crear en su propia agenda
        $user = auth('sanctum')->user();
        if ($user->rol === 'medico' && $validated['medico_id'] !== $user->id) {
            return response()->json(['error' => 'No puedes crear citas en la agenda de otro profesional'], 403);
        }

        // Verificar conflicto de horario
        $conflicto = Cita::withoutGlobalScopes()
            ->where('medico_id', $validated['medico_id'])
            ->where('fecha', $validated['fecha'])
            ->where('estado', '!=', 'cancelada')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('hora_inicio', [$validated['hora_inicio'], $validated['hora_fin']])
                  ->orWhereBetween('hora_fin', [$validated['hora_inicio'], $validated['hora_fin']]);
            })
            ->exists();

        if ($conflicto) {
            return response()->json(['error' => 'Ya existe una cita en ese horario'], 409);
        }

        $cita = Cita::create([
            ...$validated,
            'estado'        => 'confirmada',
            'canal_origen'  => 'admin',
        ]);

        return response()->json($cita->load(['cliente', 'medico']), 201);
    }

    // PUT /api/agenda/citas/{cita}/estado
    public function updateEstado(Request $request, Cita $cita): JsonResponse
    {
        $this->authorize('update', $cita);

        $validated = $request->validate([
            'estado' => 'required|in:confirmada,espera,en_consulta,atendida,cancelada,no_show',
            'motivo_cancelacion' => 'nullable|string|max:300',
        ]);

        $cita->update($validated);

        return response()->json($cita->fresh(['cliente', 'medico']));
    }
}
```

### 5.1 Controlador Notas Clínicas

```bash
php artisan make:controller Api/Tenant/NotaClinicaController --api
```

```php
// app/Http/Controllers/Tenant/NotaClinicaController.php

<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\NotaClinica;
use App\Services\NotaCifradaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotaClinicaController extends Controller
{
    public function __construct(private NotaCifradaService $notaService) {}

    // GET /api/notas-clinicas?cliente_id=5
    // Solo devuelve notas del médico autenticado (Global Scope + Policy)
    public function index(Request $request): JsonResponse
    {
        $this->authorize('create', NotaClinica::class); // Solo profesionales

        $notas = NotaClinica::where('cliente_id', $request->integer('cliente_id'))
            ->orderByDesc('fecha_consulta')
            ->get()
            ->map(fn($n) => [
                'id'            => $n->id,
                'tipo'          => $n->tipo,
                'fecha_consulta' => $n->fecha_consulta,
                'cita_id'       => $n->cita_id,
                // Contenido se descifra aquí, en el servidor
                // NUNCA se manda el cifrado al frontend
                'contenido'     => $this->notaService->descifrar($n->contenido_cifrado, $n->iv_hex),
                'created_at'    => $n->created_at,
            ]);

        return response()->json($notas);
    }

    // POST /api/notas-clinicas
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', NotaClinica::class);

        $validated = $request->validate([
            'cliente_id'     => 'required|exists:clientes,id',
            'cita_id'        => 'nullable|exists:citas,id',
            'contenido'      => 'required|string|min:5|max:10000',
            'tipo'           => 'required|in:anamnesis,diagnostico,indicaciones,antecedentes,evolucion,otro',
            'fecha_consulta' => 'required|date',
        ]);

        $user = auth('sanctum')->user();

        // Cifrar antes de guardar
        ['contenido_cifrado' => $cifrado, 'iv_hex' => $iv] =
            $this->notaService->cifrar($validated['contenido']);

        $nota = NotaClinica::create([
            'cliente_id'        => $validated['cliente_id'],
            'cita_id'           => $validated['cita_id'] ?? null,
            'medico_id'         => $user->id,
            'contenido_cifrado' => $cifrado,
            'iv_hex'            => $iv,
            'tipo'              => $validated['tipo'],
            'fecha_consulta'    => $validated['fecha_consulta'],
            'solo_autor'        => true,
        ]);

        return response()->json(['id' => $nota->id, 'tipo' => $nota->tipo], 201);
    }
}
```

---

## PARTE 6: RUTAS (routes/tenant.php)

```php
// Agregar al grupo de rutas autenticadas del tenant:

Route::middleware(['auth:sanctum', 'check.module:M08'])->group(function () {

    // Agenda — lectura filtrada por rol automáticamente
    Route::get('/api/agenda',                    [AgendaController::class, 'index']);
    Route::get('/api/agenda/semana',             [AgendaController::class, 'semana']);
    Route::get('/api/agenda/profesionales',      [AgendaController::class, 'profesionales']);
    Route::post('/api/agenda/citas',             [AgendaController::class, 'store']);
    Route::put('/api/agenda/citas/{cita}/estado',[AgendaController::class, 'updateEstado']);

    // Config de profesionales — solo admin
    Route::middleware('role:admin')->group(function () {
        Route::get('/api/profesionales-config',         [ProfesionalConfigController::class, 'index']);
        Route::post('/api/profesionales-config',        [ProfesionalConfigController::class, 'store']);
        Route::put('/api/profesionales-config/{id}',    [ProfesionalConfigController::class, 'update']);
    });
});

// Notas clínicas — M10 requerido + solo profesionales
Route::middleware(['auth:sanctum', 'check.module:M10'])->group(function () {
    Route::get('/api/notas-clinicas',   [NotaClinicaController::class, 'index']);
    Route::post('/api/notas-clinicas',  [NotaClinicaController::class, 'store']);
});

// Onboarding — solo admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/api/onboarding/progress',          [OnboardingController::class, 'index']);
    Route::post('/api/onboarding/steps/{step}/completar', [OnboardingController::class, 'completar']);
    Route::post('/api/onboarding/steps/{step}/saltar',    [OnboardingController::class, 'saltar']);
});
```

---

## PARTE 7: CONTROLADOR ONBOARDING

```bash
php artisan make:controller Api/Tenant/OnboardingController
```

```php
<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\OnboardingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    // Steps definidos en orden — ID fijo, NO cambia entre versiones
    private const STEPS = [
        [
            'id'          => 'agregar_profesionales',
            'titulo'      => 'Agrega tus profesionales',
            'descripcion' => 'Crea los usuarios médicos o profesionales que atenderán en tu clínica.',
            'accion_url'  => '/admin/usuarios',
            'duracion_min' => 3,
            'requerido'   => true,
        ],
        [
            'id'          => 'configurar_horarios',
            'titulo'      => 'Configura horarios de atención',
            'descripcion' => 'Define los días y horas en que cada profesional atiende.',
            'accion_url'  => '/admin/profesionales-config',
            'duracion_min' => 5,
            'requerido'   => true,
        ],
        [
            'id'          => 'crear_servicios',
            'titulo'      => 'Crea tus servicios',
            'descripcion' => 'Agrega las consultas, procedimientos y exámenes que ofreces.',
            'accion_url'  => '/admin/productos',
            'duracion_min' => 5,
            'requerido'   => true,
        ],
        [
            'id'          => 'primera_cita',
            'titulo'      => 'Agenda tu primera cita de prueba',
            'descripcion' => 'Prueba el flujo completo: agenda una cita y haz check-in.',
            'accion_url'  => '/agenda',
            'duracion_min' => 2,
            'requerido'   => false,
        ],
        [
            'id'          => 'configurar_sii',
            'titulo'      => 'Configura facturación SII',
            'descripcion' => 'Sube tu certificado digital para emitir boletas de honorarios automáticas.',
            'accion_url'  => '/admin/sii',
            'duracion_min' => 10,
            'requerido'   => false,
        ],
        [
            'id'          => 'activar_whatsapp',
            'titulo'      => 'Activa el bot de WhatsApp',
            'descripcion' => 'Los pacientes podrán agendar horas directamente por WhatsApp.',
            'accion_url'  => '/admin/whatsapp',
            'duracion_min' => 5,
            'requerido'   => false,
        ],
        [
            'id'          => 'portal_pacientes',
            'titulo'      => 'Comparte tu portal web',
            'descripcion' => 'Tus pacientes pueden ver historial y agendar online desde tu sitio.',
            'accion_url'  => '/admin/config',
            'duracion_min' => 2,
            'requerido'   => false,
        ],
    ];

    public function index(): JsonResponse
    {
        $completados = OnboardingProgress::whereIn('estado', ['completado', 'saltado'])
            ->get()
            ->keyBy('step_id');

        $steps = array_map(function ($step) use ($completados) {
            $progreso = $completados->get($step['id']);
            return [
                ...$step,
                'estado'         => $progreso?->estado ?? 'pendiente',
                'completado_at'  => $progreso?->completado_at,
            ];
        }, self::STEPS);

        $totalRequeridos = count(array_filter(self::STEPS, fn($s) => $s['requerido']));
        $completadosReq  = count(array_filter($steps, fn($s) => $s['requerido'] && $s['estado'] === 'completado'));

        return response()->json([
            'steps'               => $steps,
            'total'               => count(self::STEPS),
            'completados'         => count(array_filter($steps, fn($s) => $s['estado'] === 'completado')),
            'requeridos_total'    => $totalRequeridos,
            'requeridos_ok'       => $completadosReq,
            'onboarding_completo' => $completadosReq >= $totalRequeridos,
        ]);
    }

    public function completar(string $step): JsonResponse
    {
        $user = auth('sanctum')->user();

        OnboardingProgress::updateOrCreate(
            ['step_id' => $step],
            [
                'estado'          => 'completado',
                'completado_por'  => $user->id,
                'completado_at'   => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function saltar(string $step): JsonResponse
    {
        OnboardingProgress::updateOrCreate(
            ['step_id' => $step],
            ['estado' => 'saltado']
        );

        return response()->json(['ok' => true]);
    }
}
```

---

## PARTE 8: TABLA DE PERMISOS POR ROL (referencia UI y backend)

| Acción | admin | recepcionista | medico |
|---|---|---|---|
| Ver agenda de TODOS | ✅ | ✅ | ❌ (solo la suya) |
| Ver agenda propia | ✅ | ✅ | ✅ |
| Crear cita para cualquier profesional | ✅ | ✅ | ❌ |
| Crear cita en agenda propia | ✅ | ✅ | ✅ |
| Cancelar cita | ✅ | ✅ | ❌ |
| Check-in paciente | ✅ | ✅ | ❌ |
| Ver notas clínicas propias | ✅ (solo si es autor) | ❌ | ✅ (solo las suyas) |
| Ver notas clínicas de otro médico | ❌ NUNCA | ❌ NUNCA | ❌ NUNCA |
| Crear nota clínica | ❌ | ❌ | ✅ |
| Ver lista de pacientes | ✅ | ✅ | ✅ (solo sus pacientes) |
| Cobrar / emitir honorarios | ✅ | ✅ | ❌ |
| Ver métricas globales | ✅ | ❌ | ❌ |
| Configurar profesionales | ✅ | ❌ | ❌ |
| Onboarding | ✅ | ❌ | ❌ |

---

## PARTE 9: AGENDA UNIVERSAL — ADAPTACIÓN POR RUBRO

El módulo M08 adapta sus etiquetas según `rubros_config.industria_preset`.
No hay código diferente — solo etiquetas distintas.

```php
// app/Services/AgendaLabelService.php

class AgendaLabelService
{
    public static function labels(string $industria): array
    {
        return match($industria) {
            'medico', 'clinica', 'dentista' => [
                'cliente'     => 'Paciente',
                'profesional' => 'Médico',
                'cita'        => 'Consulta',
                'nota'        => 'Historia clínica',
                'hora'        => 'Hora médica',
            ],
            'abogados', 'legal' => [
                'cliente'     => 'Cliente',
                'profesional' => 'Abogado',
                'cita'        => 'Reunión',
                'nota'        => 'Expediente',
                'hora'        => 'Sesión',
            ],
            'salon', 'spa', 'estetica' => [
                'cliente'     => 'Cliente',
                'profesional' => 'Estilista',
                'cita'        => 'Reserva',
                'nota'        => 'Notas',
                'hora'        => 'Turno',
            ],
            'padel', 'deportes', 'gimnasio' => [
                'cliente'     => 'Socio',
                'profesional' => 'Instructor',
                'cita'        => 'Clase',
                'nota'        => 'Notas',
                'hora'        => 'Turno',
            ],
            'veterinaria' => [
                'cliente'     => 'Tutor',
                'profesional' => 'Veterinario',
                'cita'        => 'Consulta',
                'nota'        => 'Ficha médica',
                'hora'        => 'Hora',
            ],
            default => [
                'cliente'     => 'Cliente',
                'profesional' => 'Profesional',
                'cita'        => 'Cita',
                'nota'        => 'Nota',
                'hora'        => 'Hora',
            ],
        };
    }
}
```

---

## PARTE 10: COMANDOS DE EJECUCIÓN (en orden)

```bash
# 1. Crear migraciones
docker exec benderandos_app sh -c "cd /app && \
  php artisan make:migration add_medico_id_to_citas_table --path=database/migrations/tenant && \
  php artisan make:migration create_notas_clinicas_table --path=database/migrations/tenant && \
  php artisan make:migration create_profesionales_config_table --path=database/migrations/tenant && \
  php artisan make:migration create_onboarding_progress_table --path=database/migrations/tenant"

# 2. Correr migraciones en todos los tenants
docker exec benderandos_app sh -c "cd /app && php artisan tenants:migrate"

# 3. Crear modelos
docker exec benderandos_app sh -c "cd /app && \
  php artisan make:model Tenant/NotaClinica && \
  php artisan make:model Tenant/ProfesionalConfig && \
  php artisan make:model Tenant/OnboardingProgress"

# 4. Crear policies
docker exec benderandos_app sh -c "cd /app && \
  php artisan make:policy CitaPolicy --model=Tenant/Cita && \
  php artisan make:policy NotaClinicaPolicy --model=Tenant/NotaClinica"

# 5. Crear servicios
docker exec benderandos_app sh -c "cd /app && \
  php artisan make:service NotaCifradaService && \
  php artisan make:service AgendaLabelService"

# 6. Crear controladores
docker exec benderandos_app sh -c "cd /app && \
  php artisan make:controller Api/Tenant/AgendaController --api && \
  php artisan make:controller Api/Tenant/NotaClinicaController --api && \
  php artisan make:controller Api/Tenant/OnboardingController && \
  php artisan make:controller Api/Tenant/ProfesionalConfigController --api"

# 7. Generar key para notas
docker exec benderandos_app sh -c "openssl rand -hex 32"
# → Copiar el resultado y agregar a .env como APP_NOTES_KEY=<resultado>

# 8. Limpiar caches
docker exec benderandos_app sh -c "cd /app && php artisan optimize:clear"

# 9. Verificar rutas
docker exec benderandos_app sh -c "cd /app && php artisan route:list --path=api/agenda"
```

---

## VERIFICACIÓN — estado correcto tras implementar

El módulo está bien implementado cuando:

- `GET /api/agenda` con token de médico devuelve solo SUS citas, sin importar qué parámetros envíe
- `GET /api/agenda?medico_id=99` con token de médico sigue devolviendo solo sus citas (el scope ignora el parámetro)
- `GET /api/notas-clinicas?cliente_id=5` con token de médico A no devuelve notas escritas por médico B para ese mismo paciente
- `POST /api/notas-clinicas` con token de recepcionista devuelve 403
- `GET /api/agenda/profesionales` con token de médico devuelve solo su propio perfil
- `GET /api/agenda/profesionales` con token de admin/recepcionista devuelve todos
- El onboarding en `GET /api/onboarding/progress` muestra los 7 steps con estado correcto
- Las etiquetas de agenda cambian según `industria_preset` en rubros_config
- Conflicto de horario en `POST /api/agenda/citas` devuelve 409
- Después de `POST /api/onboarding/steps/agregar_profesionales/completar`, el step aparece como completado
- `php artisan tenants:migrate` no lanza errores en ningún tenant

---

*BenderAnd ERP · Spec Módulo Clínica + Agenda Universal + Aislamiento · 2026-03-30*
*Antigravity implementa este spec sin preguntas adicionales.*
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · AES-256-CBC notas*
