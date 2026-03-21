# BenderAnd ERP — Completar Hitos Incompletos (H2–H7)

**Fecha:** 16 de Marzo de 2026  
**Propósito:** Tareas exactas para cerrar cada hito parcial antes de avanzar a H8+  
**Estado de entrada:** H0 ✅ H1 ✅ H2 ✅* H17 ✅ — resto parcial o sin iniciar  
**Nota:** El sistema de billing por módulo está en `HITO_BILLING_MODULOS.md` — este doc no lo repite.

---

## Orden de Ejecución Recomendado

```
H2 QA  →  H3 completo  →  H5 conectar frontend  →  H6 portal cliente  →  H4 WA test  →  H7 rubros_config
```

La lógica: H3 desbloquea motel/pádel (casos de uso de alto valor), H5 conecta el superadmin que ya existe,  
H6 activa el self-service del cliente, H4 cierra el loop de notificaciones, H7 habilita el multi-industria real.

---

## H2 — Multi-Operario + Roles
**Estado actual:** ✅ Backend completo — ⚠️ QA multi-operario pendiente  
**Lo que falta:** 1 tarea

---

### T2.1 — Test multi-operario simultáneo
**Esfuerzo:** medio día  
**Qué probar:**

```
Escenario: 2 operarios (Juan y Pedro) en el mismo tenant, misma venta

1. Juan abre venta #X, agrega ítem A
2. Pedro intenta agregar ítem B a la misma venta #X por RUT del cliente
3. Verificar: venta en estado 'en_caja' → Pedro no puede modificar mientras Juan está activo
4. Juan confirma (PUT /ventas/{id}/estado → confirmado)
5. Pedro puede crear nueva venta para el mismo cliente
6. Verificar: el código rápido de cada operario es único y no colisiona
7. Verificar: Dashboard muestra ventas de ambos operarios separadas por usuario
```

**Archivos a revisar:**
- `VentaController::tomarVenta()` — lógica de bloqueo
- `VentaController::porCliente()` — consulta por RUT
- `CheckRole` middleware — que los roles son respetados

**Criterio de éxito:** 2 usuarios simultáneos no pisan la misma venta y cada uno ve solo sus acciones según su rol.

---

## H3 — Renta + Servicios + Fraccionados
**Estado actual:** ~60% — backend parcial, sin UI visual, sin timer  
**Lo que falta:** 4 tareas

---

### T3.1 — Completar `VentaService` rama fraccionados
**Esfuerzo:** 3–4 horas  
**Archivo:** `app/Services/VentaService.php`

El servicio necesita detectar `tipo_producto = 'fraccionado'` y calcular el precio correctamente:

```php
// VentaService::agregarItem() — rama fraccionado
if ($producto->tipo === 'fraccionado') {
    // cantidad puede ser decimal (2.5 metros, 0.75 kg)
    // precio = producto->precio_unitario * cantidad
    // stock descuenta en la misma unidad (metros, kg, litros)
    // unidad_minima del producto define el paso mínimo de cantidad
    
    if ($cantidad < $producto->unidad_minima) {
        throw new ValidationException("Cantidad mínima: {$producto->unidad_minima} {$producto->unidad}");
    }
    
    $subtotal = round($producto->precio_unitario * $cantidad);
    // Descontar del stock en la unidad correspondiente
    MovimientoStock::create([
        'producto_id' => $producto->id,
        'tipo'        => 'salida',
        'cantidad'    => $cantidad,
        'unidad'      => $producto->unidad,  // 'metro', 'kg', 'litro'
        'venta_id'    => $venta->id,
    ]);
}
```

**Campos necesarios en tabla `productos` (verificar migración):**
- `tipo` enum: `stock_fisico | fraccionado | servicio | renta`
- `unidad` varchar: `metro | kg | litro | unidad`
- `unidad_minima` decimal: mínimo vendible (ej: 0.5 para medio metro)
- `precio_unitario` integer: precio por unidad base

---

### T3.2 — Panel visual habitaciones/canchas
**Esfuerzo:** 4–5 horas  
**Archivo nuevo:** integrar en `admin_dashboard_v2.html` como tab "Rentas"  
**UI de referencia exacta:** `ui_plan_completo.html` → sección Motel (panel habitaciones) y sección Pádel (slots horarios)

El panel consume el endpoint existente `GET /rentas` y renderiza el estado:

```javascript
// Consumir: GET /api/rentas/activas
// Respuesta esperada:
// [{ id, recurso_nombre, estado, cliente_nombre, iniciada_en, termina_en, precio }]

async function loadRentas() {
    const res = await fetch('/api/rentas/activas');
    const rentas = await res.json();
    renderGridRecursos(rentas);
    startTimers(rentas);
}

function renderGridRecursos(rentas) {
    const grid = document.getElementById('recursos-grid');
    grid.innerHTML = rentas.map(r => `
        <div class="slot ${r.estado}" data-id="${r.id}">
            <div class="slot-nombre">${r.recurso_nombre}</div>
            <div class="slot-cliente">${r.cliente_nombre || '—'}</div>
            <div class="slot-timer" id="timer-${r.id}">--:--</div>
            <div class="slot-acciones">
                ${r.estado === 'ocupado' ? `
                    <button onclick="extender(${r.id})">+tiempo</button>
                    <button onclick="devolver(${r.id})">Checkout</button>
                ` : `
                    <button onclick="nuevaRenta(${r.id})">Iniciar</button>
                `}
            </div>
        </div>
    `).join('');
}
```

**Endpoints que deben existir (verificar en `tenant.php`):**
- `GET /api/rentas/activas` → RentaController::activas()
- `PUT /api/rentas/{id}/extender` → RentaController::extender()
- `PUT /api/rentas/{id}/devolver` → RentaController::devolver()
- `POST /api/rentas` → RentaController::iniciar()

---

### T3.3 — Timer countdown frontend
**Esfuerzo:** 2 horas  
**Archivo:** mismo script del panel de rentas (T3.2)

```javascript
function startTimers(rentas) {
    rentas.filter(r => r.estado === 'ocupado').forEach(r => {
        const termina = new Date(r.termina_en).getTime();
        
        const interval = setInterval(() => {
            const ahora = Date.now();
            const diff = termina - ahora;
            
            const el = document.getElementById(`timer-${r.id}`);
            if (!el) { clearInterval(interval); return; }
            
            if (diff <= 0) {
                el.textContent = 'VENCIDA';
                el.style.color = 'var(--err)';
                // Alerta visual en el slot
                document.querySelector(`[data-id="${r.id}"]`).classList.add('vencida');
                clearInterval(interval);
                return;
            }
            
            const mins = Math.floor(diff / 60000);
            const segs = Math.floor((diff % 60000) / 1000);
            el.textContent = `${mins}:${segs.toString().padStart(2, '0')}`;
            
            // Alerta visual cuando quedan menos de 10 minutos
            if (diff < 600000) el.style.color = 'var(--warn)';
            if (diff < 180000) el.style.color = 'var(--err)';
            
        }, 1000);
    });
}
```

---

### T3.4 — Integrar `benderand-debug.js` en todos los HTML
**Esfuerzo:** 30 minutos  
**Acción:** Agregar antes de `</body>` en cada archivo:

```html
<script src="benderand-debug.js"></script>
```

**Archivos:**
- `pos_v3.html`
- `admin_dashboard_v2.html`
- `superadmin.html`
- `ticket.html`
- `login.html`
- `compras_proveedores.html`

---

## H4 — WhatsApp Onboarding
**Estado actual:** ~50% — services y jobs creados, notificación parcial, sin test e2e  
**Lo que falta:** 2 tareas

---

### T4.1 — Completar notificación de comprobante al confirmar venta
**Esfuerzo:** 2–3 horas  
**Archivo:** `app/Services/VentaService.php` + `app/Jobs/SendWhatsAppNotification.php`

En `VentaService::confirmar()` agregar el dispatch del job si el cliente tiene teléfono registrado:

```php
// VentaService::confirmar()
public function confirmar(Venta $venta): Venta
{
    // ... lógica existente de confirmación ...

    // Notificación WA si el cliente tiene teléfono
    if ($venta->cliente && $venta->cliente->telefono) {
        $mensaje = $this->buildComprobanteMessage($venta);
        SendWhatsAppNotification::dispatch($venta->cliente->telefono, $mensaje)
            ->delay(now()->addSeconds(5));  // pequeño delay para que el cajero vea la confirmación primero
    }

    return $venta;
}

private function buildComprobanteMessage(Venta $venta): string
{
    $items = $venta->items->map(fn($i) => "• {$i->producto->nombre} x{$i->cantidad} — $" . number_format($i->subtotal, 0, ',', '.'))->join("\n");
    
    return "✅ *Comprobante de compra*\n"
         . "*{$venta->tenant->nombre}*\n"
         . "────────────────\n"
         . $items . "\n"
         . "────────────────\n"
         . "*Total: $" . number_format($venta->total, 0, ',', '.') . "*\n"
         . "Pago: {$venta->tipo_pago}\n"
         . "📄 Venta #{$venta->id} · " . now()->format('d/m/Y H:i');
}
```

**Verificar en `WhatsAppService::enviar()`:** que el método recibe teléfono + mensaje y llama a la API correcta (Meta Cloud API o Twilio según config del tenant).

---

### T4.2 — Test onboarding end-to-end
**Esfuerzo:** medio día  
**Tipo:** test manual en staging con número WA real

**Secuencia a probar:**

```
1. Nuevo número WA escribe al bot → flujo de identificación
2. Selecciona industria → bot confirma módulos + precio
3. Elige módulos → bot muestra total
4. Confirma → sistema crea tenant, schema, seeder
5. Admin entra al panel con credenciales creadas por el bot
6. Admin hace una venta → sistema envía comprobante WA al cliente
7. Trial de 30 días → job CheckTrialsExpirando dispara → bot avisa
8. Admin configura desde panel (reconfigurar)
```

**Checklist de verificación:**
- [ ] Schema tenant creado correctamente tras onboarding
- [ ] `rubros_config` poblado con el preset correcto
- [ ] Módulos activos corresponden a los seleccionados
- [ ] Credenciales de admin funcionan en `{tenant}.benderand.cl`
- [ ] Comprobante WA llega en < 10 segundos tras confirmar venta
- [ ] Job de trial dispara al día 30 (simular con `Carbon::setTestNow`)

---

## H5 — Super Admin + Billing
**Estado actual:** ~50% — backend completo, `superadmin.html` con datos mock  
**Lo que falta:** 3 tareas

---

### T5.1 — Conectar `superadmin.html` a endpoints centrales
**Esfuerzo:** 3–4 horas  
**Archivo:** `superadmin.html`

Reemplazar cada stub JS por un `fetch()` real. Los endpoints ya existen:

```javascript
// ANTES (stub):
const tenants = [
    { id: 1, nombre: 'Ferretería Don Pedro', estado: 'trial', ... },
    ...
];

// DESPUÉS (API real):
async function loadTenants(filtros = {}) {
    const params = new URLSearchParams(filtros);
    const res = await fetch(`/central/tenants?${params}`, {
        headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const data = await res.json();
    renderTenants(data.data);  // paginado
}

// Al cargar el panel:
loadTenants();

// Con filtros:
loadTenants({ estado: 'trial', rubro: 'ferreteria' });
```

**Secciones a conectar:**
- Lista de tenants → `GET /central/tenants`
- Métricas MRR, churn, ARR → `GET /central/metrics`
- Acciones tenant (suspender, reactivar, impersonar) → endpoints ya creados en `TenantManageController`
- Historial de cobros → `GET /central/tenants/{id}/pagos`

---

### T5.2 — Test MRR correcto
**Esfuerzo:** 2 horas  
**Archivo:** `app/Services/MetricsService.php`

Crear test que verifique el cálculo:

```php
// tests/Unit/MetricsServiceTest.php

public function test_mrr_calculo_correcto(): void
{
    // Crear 3 tenants con suscripciones activas
    $t1 = Tenant::factory()->create();
    Subscription::factory()->create(['tenant_id' => $t1->id, 'precio_calculado' => 79940, 'estado' => 'activo']);
    
    $t2 = Tenant::factory()->create();
    Subscription::factory()->create(['tenant_id' => $t2->id, 'precio_calculado' => 49990, 'estado' => 'activo']);
    
    $t3 = Tenant::factory()->create();
    Subscription::factory()->create(['tenant_id' => $t3->id, 'precio_calculado' => 99990, 'estado' => 'trial']);
    // Trial NO cuenta en MRR hasta que paga

    $metrics = app(MetricsService::class)->calcularMRR();

    $this->assertEquals(129930, $metrics['mrr']);          // solo activos
    $this->assertEquals(99990, $metrics['mrr_pipeline']);  // trials en camino
    $this->assertEquals(2, $metrics['tenants_activos']);
    $this->assertEquals(1, $metrics['tenants_trial']);
}

public function test_churn_calculo(): void
{
    // Tenant que canceló este mes
    Subscription::factory()->create(['estado' => 'cancelado', 'cancelado_en' => now()]);
    
    $metrics = app(MetricsService::class)->calcularChurn();
    
    $this->assertGreaterThan(0, $metrics['churn_rate']);
}
```

---

### T5.3 — Test cron `ProcesarCobrosMensuales`
**Esfuerzo:** 2 horas  
**Archivo:** `app/Jobs/ProcesarCobrosMensuales.php`

```php
// tests/Feature/ProcesarCobrosMensualesTest.php

public function test_cobro_genera_pago_y_notifica(): void
{
    // Tenant con suscripción activa y próximo cobro hoy
    $tenant = Tenant::factory()->create();
    $sub = Subscription::factory()->create([
        'tenant_id'     => $tenant->id,
        'estado'        => 'activo',
        'proximo_cobro' => today(),
        'precio_calculado' => 79940,
    ]);
    
    Queue::fake();
    
    ProcesarCobrosMensuales::dispatch();
    
    // Verifica que se generó el registro de cobro
    $this->assertDatabaseHas('pago_subscriptions', [
        'subscription_id' => $sub->id,
        'monto'           => 79940,
    ]);
    
    // Verifica que se disparó WA al admin
    Queue::assertPushed(SendWhatsAppNotification::class);
}

public function test_cobro_fallido_entra_en_gracia(): void
{
    // Simular cobro fallido (Transbank rechazado)
    // Verificar que estado pasa a 'gracia'
    // Verificar que se envía WA de aviso
}
```

---

## H6 — Portal Cliente Web
**Estado actual:** ~40% — controller + rutas + `crearPedido()` listos, sin HTML, Transbank sin SDK  
**Lo que falta:** 3 tareas

---

### T6.1 — Crear `portal_cliente.html`
**Esfuerzo:** 4–5 horas  
**Ruta pública:** `{tenant}.benderand.cl/mi/`  
**UI de referencia:** `ui_modulos_completo.html` → tab POS Avanzado → Portal Cliente

Es una SPA Vanilla JS separada, sin sidebar de admin. Estructura:

```html
<!-- portal_cliente.html — SPA mínima -->
<nav class="topnav">
  <div class="brand"><!-- logo tenant desde config --></div>
  <button onclick="tab('historial')">Mis compras</button>
  <button onclick="tab('rentas')">Rentas activas</button>
  <button onclick="tab('deudas')">Mis deudas</button>
  <button onclick="tab('pedido')">Hacer pedido</button>
</nav>

<!-- Tab historial: GET /mi/historial -->
<!-- Tab rentas: GET /mi/rentas + timer countdown si hay activa -->
<!-- Tab deudas: GET /mi/deudas + botón "Pagar con WebPay" → POST /mi/pagar -->
<!-- Tab pedido: formulario → POST /mi/pedido -->
```

**Auth:** el cliente accede con su código rápido o RUT + fecha nacimiento (sin crear cuenta completa). Sanctum abilities ya configuradas: `ver-historial`, `crear-pedido`, `pagar-deuda`.

**Endpoints disponibles (ya creados en `ClientePortalController`):**
- `GET /mi/historial`
- `GET /mi/deudas`
- `POST /mi/pedido` → `crearPedido()`
- `POST /mi/pagar` → `PagoController`

---

### T6.2 — Integrar Transbank WebPay SDK
**Esfuerzo:** 3–4 horas  
**Archivo:** `app/Http/Controllers/Tenant/PagoController.php`

Instalar el SDK oficial:

```bash
composer require transbank/transbank-sdk
```

Completar el controller skeleton existente:

```php
use Transbank\Webpay\WebpayPlus\Transaction;

class PagoController extends Controller
{
    public function iniciarPago(Request $request)
    {
        $deuda = Deuda::findOrFail($request->deuda_id);
        
        $response = Transaction::create(
            buyOrder:    'DEUDA-' . $deuda->id,
            sessionId:   session()->getId(),
            amount:      $deuda->monto,
            returnUrl:   url('/mi/pago/confirmar')
        );
        
        return response()->json([
            'url'   => $response->getUrl(),
            'token' => $response->getToken(),
        ]);
    }

    public function confirmarPago(Request $request)
    {
        $resultado = Transaction::commit($request->token_ws);
        
        if ($resultado->isApproved()) {
            // Marcar deuda como pagada
            Deuda::where('id', $this->extractDeudaId($resultado->getBuyOrder()))
                 ->update(['estado' => 'pagado', 'pagado_en' => now()]);
            
            return redirect('/mi/?pago=ok');
        }
        
        return redirect('/mi/?pago=rechazado');
    }
}
```

**Variables de entorno a agregar a `.env`:**

```
TRANSBANK_ENVIRONMENT=integration   # 'integration' o 'production'
TRANSBANK_COMMERCE_CODE=597055555532
TRANSBANK_API_KEY=579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C
```

---

### T6.3 — Ruta pública para el portal
**Esfuerzo:** 30 minutos  
**Archivo:** `routes/tenant.php`

```php
// Rutas públicas del portal cliente (no requieren login de operario)
Route::prefix('mi')->middleware(['tenant', 'auth:sanctum', 'abilities:ver-historial'])->group(function () {
    Route::get('/historial', [ClientePortalController::class, 'historial']);
    Route::get('/rentas', [ClientePortalController::class, 'rentasActivas']);
    Route::get('/deudas', [ClientePortalController::class, 'deudas']);
    Route::post('/pedido', [ClientePortalController::class, 'crearPedido']);
    Route::post('/pagar', [PagoController::class, 'iniciarPago'])->middleware('abilities:pagar-deuda');
    Route::get('/pago/confirmar', [PagoController::class, 'confirmarPago']);
});

// Servir el HTML del portal (sin auth)
Route::get('/mi', fn() => view('portal-cliente'));
Route::get('/mi/login', fn() => view('portal-cliente-login'));
```

---

## H7 — Config Dinámica por Industria
**Estado actual:** ~20% — solo documentado, cero en base de datos  
**Lo que falta:** 4 tareas — estas son el prerequisito directo del sistema de billing/módulos

---

### T7.1 — Migración `rubros_config`
**Esfuerzo:** 1 hora  
**Comando:**

```bash
php artisan tenants:artisan "make:migration create_rubros_config_table"
```

El schema completo está definido en `BENDERAND_CONFIG_INDUSTRIAS.md §PARTE C`. Usar exactamente ese schema. Campos clave:

```php
// database/migrations/tenant/xxxx_create_rubros_config_table.php
Schema::create('rubros_config', function (Blueprint $table) {
    $table->id();
    $table->string('industria_preset', 50);        // 'retail','medico','motel'...
    $table->string('industria_nombre', 255)->nullable();
    $table->text('modulos_activos');               // JSON array: ['M01','M03',...]
    $table->string('label_operario', 100)->default('Vendedor');
    $table->string('label_cliente', 100)->default('Cliente');
    $table->string('label_cajero', 100)->default('Cajero');
    $table->string('label_producto', 100)->default('Producto');
    $table->string('label_recurso', 100)->nullable();
    $table->string('label_nota', 100)->nullable();
    $table->string('documento_default', 50)->default('boleta');
    $table->boolean('requiere_rut')->default(false);
    $table->boolean('boleta_sin_detalle')->default(false);
    $table->boolean('tiene_stock_fisico')->default(true);
    $table->boolean('tiene_renta')->default(false);
    $table->boolean('tiene_renta_hora')->default(false);
    $table->boolean('tiene_servicios')->default(false);
    $table->boolean('tiene_agenda')->default(false);
    $table->boolean('tiene_delivery')->default(false);
    $table->boolean('tiene_comandas')->default(false);
    $table->boolean('tiene_ot')->default(false);
    $table->boolean('tiene_membresias')->default(false);
    $table->boolean('tiene_notas_cifradas')->default(false);
    $table->boolean('tiene_fiado')->default(false);
    $table->boolean('tiene_fraccionado')->default(false);
    $table->string('accent_color', 7)->default('#3b82f6');
    $table->jsonb('config_extra')->nullable();
    $table->timestamps();
});
```

---

### T7.2 — Seeder de presets por industria
**Esfuerzo:** 2 horas  
**Archivo:** `database/seeders/RubrosConfigSeeder.php`

Crear un seeder con los 19 presets definidos en `BENDERAND_CONFIG_INDUSTRIAS.md §B.1`. Extracto:

```php
class RubrosConfigSeeder extends Seeder
{
    const PRESETS = [
        'retail' => [
            'industria_preset'   => 'retail',
            'industria_nombre'   => 'Abarrotes / Retail',
            'modulos_activos'    => ['M01','M02','M03','M04','M11','M12','M17','M18','M20','M24','M25','M32'],
            'label_operario'     => 'Vendedor',
            'label_cliente'      => 'Cliente',
            'documento_default'  => 'boleta',
            'tiene_stock_fisico' => true,
            'tiene_fraccionado'  => true,
            'tiene_fiado'        => true,
            'accent_color'       => '#f5c518',
        ],
        'motel' => [
            'industria_preset'   => 'motel',
            'industria_nombre'   => 'Motel / Hospedaje por horas',
            'modulos_activos'    => ['M01','M03','M06','M14','M17','M20'],
            'label_operario'     => 'Recepcionista',
            'label_cliente'      => 'Huésped',
            'label_recurso'      => 'Habitación',
            'documento_default'  => 'boleta',
            'boleta_sin_detalle' => true,    // privacidad motel
            'requiere_rut'       => false,
            'tiene_renta_hora'   => true,
            'accent_color'       => '#ff6b35',
        ],
        'medico' => [
            'industria_preset'   => 'medico',
            'industria_nombre'   => 'Médico / Clínica',
            'modulos_activos'    => ['M01','M07','M08','M09','M10','M20','M21','M22','M23','M32'],
            'label_operario'     => 'Médico',
            'label_cajero'       => 'Recepcionista',
            'label_cliente'      => 'Paciente',
            'label_nota'         => 'Historia clínica',
            'documento_default'  => 'honorarios',
            'requiere_rut'       => true,
            'tiene_servicios'    => true,
            'tiene_agenda'       => true,
            'tiene_notas_cifradas' => true,
            'accent_color'       => '#3dd9eb',
        ],
        // ... resto de presets
    ];

    public function run(): void
    {
        $preset = config('tenant.industria_preset', 'retail');
        RubrosConfig::create(self::PRESETS[$preset]);
    }
}
```

Este seeder se llama durante el onboarding con el preset elegido por el tenant.

---

### T7.3 — Endpoint `GET /api/config/rubro`
**Esfuerzo:** 1 hora  
**Archivo:** nuevo o en `WebPanelController`

```php
// GET /api/config/rubro
public function getRubroConfig(): JsonResponse
{
    $config = RubrosConfig::first();  // un tenant tiene una sola config
    
    return response()->json([
        'industria'          => $config->industria_preset,
        'modulos_activos'    => json_decode($config->modulos_activos),
        'labels'             => [
            'operario' => $config->label_operario,
            'cliente'  => $config->label_cliente,
            'cajero'   => $config->label_cajero,
            'recurso'  => $config->label_recurso,
        ],
        'flags'              => [
            'tiene_stock'    => $config->tiene_stock_fisico,
            'tiene_agenda'   => $config->tiene_agenda,
            'tiene_rentas'   => $config->tiene_renta || $config->tiene_renta_hora,
            'tiene_fiado'    => $config->tiene_fiado,
            'notas_cifradas' => $config->tiene_notas_cifradas,
        ],
        'accent_color'       => $config->accent_color,
        'documento_default'  => $config->documento_default,
    ]);
}
```

---

### T7.4 — Sidebar admin construido desde `rubros_config`
**Esfuerzo:** 2–3 horas  
**Archivo:** `admin_dashboard_v2.html`

Al cargar el dashboard, consultar la config y construir el menú dinámicamente. La lógica `buildMenu()` ya está diseñada en `BENDERAND_CONFIG_INDUSTRIAS.md §D.1` — solo hay que integrarla:

```javascript
// admin_dashboard_v2.html — al inicio
let rubrosConfig = null;

async function init() {
    const res = await fetch('/api/config/rubro', {
        headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    rubrosConfig = await res.json();
    
    // Aplicar accent color del rubro
    document.documentElement.style.setProperty('--ac', rubrosConfig.accent_color);
    
    // Construir sidebar dinámico
    buildSidebar(rubrosConfig);
    
    // Aplicar etiquetas del rubro
    applyLabels(rubrosConfig.labels);
}

function buildSidebar(config) {
    const MENU_MAP = [
        { id: 'dashboard',   label: 'Dashboard',                    icon: '📊', siempre: true },
        { id: 'ventas',      label: 'Ventas',                       icon: '💳', siempre: true },
        { id: 'clientes',    label: () => config.labels.cliente+'s',icon: '👤', siempre: true },
        { id: 'rentas',      label: () => config.labels.recurso+'s',icon: '🏨', modulo: 'M05', flag: config.flags.tiene_rentas },
        { id: 'agenda',      label: 'Agenda',                       icon: '📅', flag: config.flags.tiene_agenda },
        { id: 'inventario',  label: 'Inventario',                   icon: '📦', modulo: 'M03', flag: config.flags.tiene_stock },
        { id: 'compras',     label: 'Compras',                      icon: '🛒', modulo: 'M18' },
        { id: 'sii',         label: 'SII / DTE',                    icon: '📄', modulo: 'M20' },
        { id: 'config',      label: 'Configuración',                icon: '⚙️',  siempre: true },
    ];

    const sidebar = document.getElementById('admin-sidebar');
    sidebar.innerHTML = MENU_MAP
        .filter(item => {
            if (item.siempre) return true;
            if (item.flag !== undefined) return item.flag;
            if (item.modulo) return config.modulos_activos.includes(item.modulo);
            return false;
        })
        .map(item => `
            <div class="asi" onclick="showSection('${item.id}')">
                <span>${item.icon}</span>
                ${typeof item.label === 'function' ? item.label() : item.label}
            </div>
        `).join('');
}

init();
```

---

## Resumen de Tareas por Hito

| Hito | Tarea | Esfuerzo | Tipo | Prioridad |
|---|---|---|---|---|
| **H2** | T2.1 Test multi-operario simultáneo | 4h | QA | Media |
| **H3** | T3.1 VentaService fraccionados | 4h | Backend | Alta |
| **H3** | T3.2 Panel visual rentas | 5h | Frontend | Alta |
| **H3** | T3.3 Timer countdown | 2h | Frontend | Alta |
| **H3** | T3.4 Integrar benderand-debug.js | 30min | Frontend | Baja |
| **H4** | T4.1 Comprobante WA al confirmar venta | 3h | Backend | Alta |
| **H4** | T4.2 Test onboarding e2e | 4h | QA | Media |
| **H5** | T5.1 Conectar superadmin.html al API | 4h | Frontend | Alta |
| **H5** | T5.2 Test MRR correcto | 2h | QA | Media |
| **H5** | T5.3 Test cron ProcesarCobrosMensuales | 2h | QA | Media |
| **H6** | T6.1 Crear portal_cliente.html | 5h | Frontend | Alta |
| **H6** | T6.2 Integrar Transbank SDK | 4h | Backend | Alta |
| **H6** | T6.3 Rutas públicas portal | 30min | Backend | Alta |
| **H7** | T7.1 Migración rubros_config | 1h | Backend | Alta |
| **H7** | T7.2 Seeder presets (19 industrias) | 2h | Backend | Alta |
| **H7** | T7.3 Endpoint GET /api/config/rubro | 1h | Backend | Alta |
| **H7** | T7.4 Sidebar dinámico desde config | 3h | Frontend | Alta |

**Total estimado:** ~3.5 días de desarrollo  
**Resultado al completar:** H2–H7 cerrados, sistema multi-industria funcional, portal cliente operativo, superadmin con datos reales.

---

## Después de Completar Este MD

El siguiente paso es `HITO_BILLING_MODULOS.md` — el sistema de selección de módulos, precios visibles en onboarding y control de acceso por pago. H7 (rubros_config) es prerequisito directo de ese hito.

```
H2 QA ✅ → H3 ✅ → H5 conectado ✅ → H6 portal ✅ → H4 WA ✅ → H7 rubros_config ✅
                                                                          ↓
                                                            HITO_BILLING_MODULOS
                                                            (módulos + precios + acceso)
```

---

*BenderAnd ERP — Cierre de Hitos H2–H7*  
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Sanctum · Redis*
