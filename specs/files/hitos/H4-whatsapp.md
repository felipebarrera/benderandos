# H4 — WhatsApp Onboarding + Notificaciones
**BenderAnd POS · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ Pendiente · Duración estimada: 2 semanas · Requiere: H1 completo*

> Alcance: Nuevo tenant se registra 100% desde WhatsApp sin intervención del equipo.
> Notificaciones automáticas al cliente y al admin del tenant.

---

## Entregables

- [ ] Webhook `POST /webhook/whatsapp/onboarding` → crea schema + migra + seed + admin
- [ ] `WhatsAppService` → llama al bot externo para enviar mensajes
- [ ] `Job SendWhatsAppNotification` en queue con retry y backoff
- [ ] Notificaciones: comprobante venta, encargo listo, deuda pendiente, stock crítico, trial expira, renta venciendo, pedido remoto
- [ ] `GET /webhook/whatsapp/check-slug` para validar nombre antes de crear
- [ ] OTP WhatsApp para verificar número de admin
- [ ] Pedido remoto desde bot: `POST /webhook/whatsapp/pedido-remoto`

---

## Webhook onboarding

```php
// routes/webhook.php
Route::post('/webhook/whatsapp/onboarding', [WhatsAppOnboardingController::class, 'handle']);
Route::get('/webhook/whatsapp/check-slug', [WhatsAppOnboardingController::class, 'checkSlug']);
Route::post('/webhook/whatsapp/otp', [WhatsAppOnboardingController::class, 'generarOtp']);
Route::post('/webhook/whatsapp/verificar-otp', [WhatsAppOnboardingController::class, 'verificarOtp']);
Route::get('/webhook/whatsapp/pedido/{uuid}', [WhatsAppPedidoController::class, 'estado']);
Route::post('/webhook/whatsapp/pedido-remoto', [WhatsAppPedidoController::class, 'crear']);
```

Todos los webhooks validan:
```php
// Middleware
$token = $request->header('X-Bot-Token');
abort_unless($token === config('services.whatsapp_bot.token'), 401);
```

---

## Crear tenant desde webhook

```php
// app/Services/TenantOnboardingService.php
public function crear(array $datos): array
{
    $slug = Str::slug($datos['nombre_empresa']);

    // Verificar disponibilidad
    abort_if(Tenant::where('slug', $slug)->exists(), 422, json_encode([
        'error' => 'nombre_empresa',
        'message' => 'Ya existe una empresa con ese nombre. ¿Deseas otro nombre?',
    ]));

    $tenant = DB::transaction(function () use ($datos, $slug) {
        // Crear tenant en schema public
        $tenant = Tenant::create([
            'uuid'          => Str::uuid(),
            'nombre'        => $datos['nombre_empresa'],
            'slug'          => $slug,
            'rut_empresa'   => $datos['rut_empresa'] ?? null,
            'whatsapp_admin'=> $datos['whatsapp_admin'],
            'estado'        => 'trial',
            'trial_hasta'   => now()->addDays(30),
            'rubro_config'  => $this->getRubroConfig($datos['rubro']),
        ]);

        // Crear dominio para stancl/tenancy
        $tenant->domains()->create(['domain' => $slug . '.benderand.cl']);

        // stancl/tenancy crea el schema y corre las migraciones
        tenancy()->initialize($tenant);
        Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);

        // Seed: tipos_pago, primer usuario admin
        Artisan::call('tenants:seed', [
            '--class' => 'TenantInitialSeeder',
            '--tenants' => [$tenant->id],
        ]);

        // Crear usuario admin
        Usuario::create([
            'nombre'      => $datos['nombre_admin'],
            'email'       => $datos['email_admin'],
            'whatsapp'    => $datos['whatsapp_admin'],
            'clave_hash'  => Hash::make($datos['password'] ?? Str::random(12)),
            'rol'         => 'admin',
        ]);

        return $tenant;
    });

    return [
        'slug'       => $slug,
        'url'        => "https://{$slug}.benderand.cl",
        'estado'     => 'trial',
        'dias_trial' => 30,
    ];
}
```

---

## WhatsAppService

```php
// app/Services/WhatsAppService.php
public function enviar(string $numero, string $mensaje): bool
{
    try {
        $response = Http::withToken(config('services.whatsapp_bot.token'))
            ->timeout(5)
            ->post(config('services.whatsapp_bot.url') . '/send', [
                'to'      => $this->formatearNumero($numero),
                'message' => $mensaje,
                'type'    => 'text',
            ]);
        return $response->successful();
    } catch (\Exception $e) {
        Log::warning('WhatsApp send failed', ['numero' => $numero, 'error' => $e->getMessage()]);
        return false;
    }
}

private function formatearNumero(string $numero): string
{
    $numero = preg_replace('/\D/', '', $numero);
    if (!str_starts_with($numero, '56')) {
        $numero = '56' . ltrim($numero, '0');
    }
    return $numero;
}
```

---

## Job SendWhatsAppNotification

```bash
php artisan make:job SendWhatsAppNotification
```

```php
class SendWhatsAppNotification implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public string $tipo,
        public mixed $modelo
    ) {}

    public function handle(WhatsAppService $wa): void
    {
        $mensaje = match($this->tipo) {
            'comprobante'      => $wa->buildComprobante($this->modelo),
            'encargo_listo'    => $wa->buildEncargoListo($this->modelo),
            'deuda_pendiente'  => $wa->buildDeudaPendiente($this->modelo),
            'stock_critico'    => $wa->buildStockCritico($this->modelo),
            'pedido_remoto'    => $wa->buildPedidoRemoto($this->modelo),
            'renta_venciendo'  => $wa->buildRentaVenciendo($this->modelo),
            'trial_expira'     => $wa->buildTrialExpira($this->modelo),
            default            => null,
        };

        $numero = $this->modelo->whatsapp ?? $this->modelo->cliente?->whatsapp ?? null;

        if ($mensaje && $numero) {
            $wa->enviar($numero, $mensaje);
        }
    }
}
```

---

## Crons automáticos (scheduler)

```php
// bootstrap/app.php
Schedule::call(function () {
    // Deudas con más de 7 días sin pago
    Deuda::where('estado', 'pendiente')
        ->where('created_at', '<=', now()->subDays(7))
        ->with('cliente')
        ->each(fn($d) => SendWhatsAppNotification::dispatch('deuda_pendiente', $d));
})->dailyAt('09:00');

Schedule::call(function () {
    // Trial expirando: avisar a 7, 3 y 1 día antes
    Tenant::whereIn('estado', ['trial'])
        ->whereIn(DB::raw("EXTRACT(DAY FROM (trial_hasta - NOW()))"), [7, 3, 1])
        ->each(fn($t) => SendWhatsAppNotification::dispatch('trial_expira', $t));
})->dailyAt('10:00');
```

---

## Checklist de verificación H4

**Onboarding:**
- [ ] `check-slug?nombre=Ferretería Don Pedro` → responde `{ "disponible": true, "slug_sugerido": "ferreteria-don-pedro" }`
- [ ] Webhook onboarding crea schema PostgreSQL `tenant_abc123`
- [ ] Migraciones del tenant corren automáticamente
- [ ] Seed inicial: tipos_pago + usuario admin creado
- [ ] `rubro_config` se inicializa con preset correcto según rubro enviado
- [ ] Subdominio `ferreteria-don-pedro.benderand.cl` responde en 30 segundos

**Notificaciones:**
- [ ] Comprobante se envía al confirmar venta si cliente tiene WhatsApp
- [ ] Encargo listo → WA cuando admin cambia estado a `llegó`
- [ ] Deuda pendiente → WA a los 7 días (cron 09:00)
- [ ] Stock crítico → WA al admin cuando stock ≤ cantidad_minima
- [ ] Trial expira → WA a los 7, 3 y 1 día antes
- [ ] Renta venciendo → WA cuando quedan 10 min
- [ ] Job con 3 reintentos, backoff de 30s entre intentos
- [ ] Fallo en WA no detiene el flujo de la venta (fire-and-forget)

**Pedido remoto:**
- [ ] `POST /webhook/whatsapp/pedido-remoto` crea venta con estado `remota_pendiente`
- [ ] Si producto no existe → 404 con código del producto
- [ ] Admin ve pedidos remotos en dashboard panel
