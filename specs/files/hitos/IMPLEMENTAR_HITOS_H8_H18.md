# BenderAnd ERP — Implementar Hitos H8–H18

**Fecha:** 16 de Marzo de 2026  
**Prerequisito:** `COMPLETAR_HITOS_H2_H7.md` terminado + `HITO_BILLING_MODULOS.md` implementado  
**Stack:** Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Sanctum · Redis · Laravel Horizon  
**Total estimado:** 3–4 meses de desarrollo

---

## Mapa de Dependencias

```
H7 rubros_config ──┐
                   ├──► H8  ERP ↔ WhatsApp Bot  ──► H9  SII/DTE ──► H16 M31 SaaS
H5 billing ────────┘                                 │
                                                     ▼
H3 rentas ─────────────────────────────────────► H10 Compras/Proveedores
H1 ventas ─────────────────────────────────────► H11 Delivery
                                                 H12 Restaurante/Recetas
H2 roles ──────────────────────────────────────► H13 RRHH
H13 RRHH ──────────────────────────────────────► H14 Reclutamiento
H1 ventas ─────────────────────────────────────► H15 Marketing QR

H8–H16 todos completos ────────────────────────► H17 Dashboard Ejecutivo + API Pública
H17 completo ──────────────────────────────────► H18 Testing + Deploy
```

**Orden de ejecución recomendado por valor / dependencias:**

```
H8 → H9 → H10 → H11 → H12 → H13 → H14 → H15 → H16 → H17 → H18
```

---

## H8 — Integración ERP ↔ WhatsApp Bot
**Duración estimada:** 3 semanas  
**Módulo:** M17 (Pedido remoto WA)  
**UI de referencia:** `ui_modulos_completo.html` → WhatsApp Bot

### Contexto

El bot (Moteland, Node.js, ya en producción) y el ERP (Laravel 11) son dos sistemas separados. Este hito los conecta de forma bidireccional mediante un JWT compartido. Un cliente puede consultar stock, pedir precio, o hacer un pedido directamente por WhatsApp — y eso aparece en el ERP como venta remota.

### Semana 1 — JWT Bridge y endpoints internos Laravel

```bash
composer require firebase/php-jwt
```

**Nuevo archivo:** `app/Services/JwtBridgeService.php`

```php
class JwtBridgeService
{
    // JWT firmado con el mismo secret que usa Node.js
    // Claims: tenant_id, tenant_domain, issued_at, exp
    public function generarToken(Tenant $tenant): string
    {
        return JWT::encode([
            'tenant_id'     => $tenant->id,
            'tenant_domain' => $tenant->domain,
            'iat'           => time(),
            'exp'           => time() + 3600,
        ], config('tenancy.jwt_secret'), 'HS256');
    }

    public function validarToken(string $token): array
    {
        return (array) JWT::decode($token, new Key(config('tenancy.jwt_secret'), 'HS256'));
    }
}
```

**Nuevos endpoints en `routes/tenant.php`** (consumidos por Node.js):

```php
// Rutas internas para el bot WA — autenticadas con JWT Bridge
Route::prefix('bot')->middleware('jwt.bridge')->group(function () {
    Route::get('/stock/{sku}',            [BotApiController::class, 'stock']);
    Route::get('/precio/{sku}',           [BotApiController::class, 'precio']);
    Route::get('/cliente/{telefono}',     [BotApiController::class, 'cliente']);
    Route::get('/agenda/disponibilidad',  [BotApiController::class, 'disponibilidad']);
    Route::post('/pedido',                [BotApiController::class, 'crearPedido']);
    Route::get('/pedido/{id}/estado',     [BotApiController::class, 'estadoPedido']);
});
```

**Nuevo middleware:** `app/Http/Middleware/JwtBridgeMiddleware.php`

```php
// Valida el JWT en el header Authorization: Bearer <token>
// Inicializa el tenant correcto según tenant_id del JWT
```

**Variable de entorno nueva:**

```
JWT_BRIDGE_SECRET=mismo_secret_que_tiene_node_en_PROD
```

### Semana 2 — Webhooks bidireccionales

**Laravel → Node.js** (cuando ocurre algo en el ERP, avisa al bot):

```php
// app/Services/WhatsAppNotifier.php — ampliar métodos existentes

public function notificarVentaConfirmada(Venta $venta): void
{
    $this->postAlBot('/webhook/erp/venta-confirmada', [
        'telefono'    => $venta->cliente->telefono,
        'venta_id'    => $venta->id,
        'total'       => $venta->total,
        'items'       => $venta->items->map(fn($i) => $i->producto->nombre),
        'tenant_token'=> app(JwtBridgeService::class)->generarToken(tenant()),
    ]);
}

public function notificarPedidoListo(Venta $venta): void { ... }
public function notificarCitaConfirmada(Agenda $cita): void { ... }
public function notificarRentaVencida(Renta $renta): void { ... }
```

**Node.js → Laravel** (cuando el bot recibe un pedido del cliente):

```php
// routes/web.php — webhook entrante desde Node.js
Route::post('/webhook/wa/pedido', [WhatsAppWebhookController::class, 'pedidoEntrante']);

// WhatsAppWebhookController::pedidoEntrante()
// - Valida JWT Bridge
// - Identifica tenant por domain
// - Crea venta en estado 'remota_pendiente'
// - Notifica al admin (SSE o WA)
// - Responde al bot con { venta_id, estado }
```

### Semana 3 — Intenciones por rubro + UI dashboard

**Node.js** (`erp-integration.service.js`) — consulta al ERP según intención del cliente:

```javascript
// erp-integration.service.js (Node.js, Moteland)
const INTENCIONES_ERP = {
  stock:         (sku, token) => fetch(`${ERP_URL}/bot/stock/${sku}`,    { headers: jwt(token) }),
  precio:        (sku, token) => fetch(`${ERP_URL}/bot/precio/${sku}`,   { headers: jwt(token) }),
  disponibilidad:(fecha,token)=> fetch(`${ERP_URL}/bot/agenda/disponibilidad?fecha=${fecha}`, { headers: jwt(token) }),
  hacer_pedido:  (data, token)=> fetch(`${ERP_URL}/bot/pedido`, { method: 'POST', body: JSON.stringify(data), headers: jwt(token) }),
};
```

**UI panel WA** en `admin_dashboard_v2.html` — tab "WhatsApp":
- Conversaciones activas en tiempo real (SSE)
- Pedidos via bot pendientes de confirmar
- Botones: Confirmar pedido / Rechazar / Asignar a operario
- Config bot: intenciones activas, horario, mensaje fuera de hora

**Checklist de verificación H8:**
- [ ] Cliente WA consulta stock → bot llama ERP → responde precio en < 3 segundos
- [ ] Pedido WA crea venta `remota_pendiente` en ERP
- [ ] Venta confirmada en ERP → bot envía comprobante WA al cliente
- [ ] JWT del ERP válido en endpoints Node.js
- [ ] JWT expirado → Node.js renueva automáticamente
- [ ] Tenant A no puede acceder a datos de Tenant B via JWT (isolation)

---

## H9 — SII / LibreDTE
**Duración estimada:** 4 semanas  
**Módulo:** M20  
**UI de referencia:** `ui_modulos_completo.html` → SII / DTE

### Semana 1 — Instalación y configuración

```bash
composer require sasco/libredte-lib-core
```

**Nuevo archivo:** `config/sii.php`

```php
return [
    'ambiente'    => env('SII_AMBIENTE', 'certificacion'), // 'certificacion' | 'produccion'
    'libredte_url'=> env('LIBREDTE_URL', 'https://libredte.cl'),
    'libredte_key'=> env('LIBREDTE_API_KEY'),
];
```

**Migración** `create_config_sii_table` (schema tenant):

```php
Schema::create('config_sii', function (Blueprint $table) {
    $table->id();
    $table->string('rut_emisor');
    $table->string('razon_social');
    $table->string('giro');
    $table->string('direccion');
    $table->string('comuna');
    $table->text('certificado_pfx_encrypted'); // AES-256
    $table->string('certificado_pass_encrypted');
    $table->string('ambiente')->default('certificacion');
    $table->jsonb('caf_disponibles')->nullable(); // folios disponibles por tipo DTE
    $table->timestamps();
});
```

**Migración** `create_dte_emitidos_table`:

```php
Schema::create('dte_emitidos', function (Blueprint $table) {
    $table->id();
    $table->morphs('origen');        // venta_id o cualquier origen
    $table->string('tipo_dte');      // '39'=boleta, '33'=factura, '61'=NC, '56'=honorarios
    $table->integer('folio');
    $table->integer('monto_neto');
    $table->integer('iva')->default(0);
    $table->integer('monto_total');
    $table->string('estado')->default('pendiente'); // pendiente|aceptado|rechazado|anulado
    $table->string('track_id')->nullable();          // ID seguimiento SII
    $table->string('pdf_path')->nullable();
    $table->string('xml_path')->nullable();
    $table->timestamp('enviado_sii_en')->nullable();
    $table->timestamps();
});
```

### Semana 2 — SiiService con todos los tipos DTE

**Nuevo archivo:** `app/Services/SiiService.php`

```php
class SiiService
{
    public function emitirBoleta(Venta $venta): DteEmitido
    {
        $config = ConfigSii::first();
        // 1. Construir documento DTE tipo 39
        // 2. Enviar a LibreDTE API
        // 3. Guardar en dte_emitidos con folio y estado
        // 4. Guardar PDF en storage
        // 5. Disparar job de envío (email + WA)
    }

    public function emitirFactura(Venta $venta, string $rutReceptor, string $razonSocial): DteEmitido { ... }
    public function emitirHonorarios(Venta $venta): DteEmitido { ... }
    public function emitirNotaCredito(DteEmitido $dteOriginal, string $motivo): DteEmitido { ... }
    public function libroVentas(int $mes, int $anio): array { ... }
    public function libroCompras(int $mes, int $anio): array { ... }
}
```

### Semana 3 — Integración automática con VentaService

```php
// VentaService::confirmar() — agregar después de confirmar la venta
if ($config->modulos_activos incluye 'M20') {
    $configSii = ConfigSii::first();
    if ($configSii && $configSii->ambiente) {
        EmitirDteJob::dispatch($venta)->afterCommit();
    }
}
```

**Nuevo Job:** `app/Jobs/EmitirDteJob.php`

```php
class EmitirDteJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [30, 60, 120]; // reintentos con espera exponencial

    public function handle(SiiService $sii): void
    {
        $tipo = $this->venta->tipo_documento; // 'boleta' | 'factura' | 'honorarios'
        match ($tipo) {
            'boleta'     => $sii->emitirBoleta($this->venta),
            'factura'    => $sii->emitirFactura($this->venta, ...),
            'honorarios' => $sii->emitirHonorarios($this->venta),
        };
    }
}
```

### Semana 4 — UI SII en admin dashboard

Tab "SII / DTE" en `admin_dashboard_v2.html`:
- Dashboard: timbres disponibles por tipo, DTEs del día, estado por color
- Tabla de DTEs emitidos con filtros por tipo/estado/fecha
- Libro de ventas del mes (descargable Excel/PDF)
- Configuración: RUT, certificado PFX, ambiente, folios CAF

**Checklist de verificación H9:**
- [ ] Boleta se emite automáticamente al confirmar venta (< 5 segundos)
- [ ] Factura requiere RUT + razón social del receptor
- [ ] DTE rechazado → reintento automático 3 veces → alerta admin
- [ ] PDF del DTE enviado por WA al cliente si tiene teléfono
- [ ] Libro de ventas muestra totales correctos vs DTEs emitidos
- [ ] Ambiente certificación y producción funcionan con sus propias credenciales

---

## H10 — Compras y Proveedores
**Duración estimada:** 4 semanas  
**Módulo:** M18, M19  
**UI de referencia:** `ui_modulos_completo.html` → Admin → Compras

### Migraciones

```bash
# Schema public (proveedores globales — compartidos entre todos los tenants)
php artisan make:migration create_proveedores_globales_table

# Schema tenant
php artisan tenants:artisan "make:migration create_proveedores_tenant_table"
php artisan tenants:artisan "make:migration create_ordenes_compra_table"
php artisan tenants:artisan "make:migration create_items_orden_compra_table"
php artisan tenants:artisan "make:migration create_recepciones_compra_table"
php artisan tenants:artisan "make:migration create_items_recepcion_table"
```

**Tablas clave:**

```php
// ordenes_compra
$table->string('estado')->default('borrador'); // borrador|autorizada|enviada|recibida|parcial|anulada
$table->foreignId('proveedor_id');
$table->date('fecha_esperada')->nullable();
$table->text('notas')->nullable();

// items_recepcion
$table->string('estado_item')->default('aceptado'); // aceptado|rechazado|parcial
$table->integer('cantidad_esperada');
$table->integer('cantidad_recibida');
$table->date('fecha_vencimiento')->nullable(); // M19: lotes con vencimiento
$table->string('lote')->nullable();
```

### Flujo de Compra Completo

```
StockAlertService detecta producto bajo mínimo
    → Crea OC automática en estado 'borrador'
    → Notifica admin (badge en sidebar)

Admin revisa OC → ajusta cantidades → Autoriza
    → Estado: 'autorizada'
    → Sistema envía OC al proveedor por email

Llega mercadería → Admin registra Recepción
    → Por ítem: cantidad recibida + estado (aceptado/rechazado/parcial)
    → Si aceptado: MovimientoStock::entrada() actualiza stock
    → Si rechazado: no mueve stock, genera nota devolución

OC totalmente recibida → estado 'recibida'
OC parcialmente recibida → estado 'parcial' → puede recibir resto
```

### Nuevo controller y rutas

```php
// Nuevas rutas en routes/tenant.php
Route::middleware(['module:M18'])->group(function () {
    Route::apiResource('proveedores', ProveedorController::class);
    Route::apiResource('ordenes-compra', OrdenCompraController::class);
    Route::post('ordenes-compra/{id}/autorizar',  [OrdenCompraController::class, 'autorizar']);
    Route::post('ordenes-compra/{id}/enviar',      [OrdenCompraController::class, 'enviar']);
    Route::post('ordenes-compra/{id}/recepcion',   [RecepcionController::class, 'store']);
    Route::get('proveedores/globales/buscar',       [ProveedorController::class, 'buscarGlobales']);
});
```

**Checklist de verificación H10:**
- [ ] OC automática se crea cuando producto baja de stock mínimo
- [ ] Recepción parcial deja OC en estado `parcial`
- [ ] Ítems rechazados NO incrementan stock
- [ ] Proveedor global (ej: Coca-Cola) disponible para todos los tenants
- [ ] OC exportable en PDF para enviar al proveedor

---

## H11 — Delivery y Logística
**Duración estimada:** 3 semanas  
**Módulo:** M13  
**UI de referencia:** `ui_modulos_completo.html` → Delivery

### Migraciones

```bash
php artisan tenants:artisan "make:migration create_repartidores_table"
php artisan tenants:artisan "make:migration create_entregas_table"
php artisan tenants:artisan "make:migration create_tracking_entregas_table"
php artisan tenants:artisan "make:migration create_zonas_delivery_table"
```

**Tablas clave:**

```php
// entregas
$table->foreignId('venta_id');
$table->foreignId('repartidor_id')->nullable();
$table->string('estado')->default('pendiente'); // pendiente|asignado|en_camino|entregado|fallido
$table->string('direccion_entrega');
$table->foreignId('zona_id')->nullable();
$table->integer('costo_envio')->default(0);
$table->string('token_publico')->unique(); // para link de seguimiento sin login
$table->timestamp('entregado_en')->nullable();

// tracking_entregas
$table->foreignId('entrega_id');
$table->string('estado');
$table->string('nota')->nullable();
$table->decimal('lat', 10, 7)->nullable();
$table->decimal('lng', 10, 7)->nullable();
```

### Integración con VentaService

```php
// VentaService::confirmar() — si tiene delivery
if ($venta->tipo === 'delivery' && config módulo M13 activo) {
    Entrega::create([
        'venta_id'        => $venta->id,
        'direccion_entrega'=> $venta->direccion_entrega,
        'estado'          => 'pendiente',
        'token_publico'   => Str::random(32),
    ]);
    // Notificar admin: nuevo pedido delivery pendiente de asignar
}
```

### URL pública de seguimiento

```php
// routes/web.php — sin auth
Route::get('/seguimiento/{token}', [TrackingPublicoController::class, 'show']);
```

Página HTML simple que muestra el estado del pedido con actualización cada 30 segundos (polling). El repartidor puede actualizar estado desde su celular con una URL similar (`/repartidor/{token}/actualizar`).

**Checklist de verificación H11:**
- [ ] Venta con delivery crea entrega automáticamente en `pendiente`
- [ ] Admin asigna repartidor → estado `asignado` → WA al cliente
- [ ] Repartidor actualiza estado desde móvil → WA al cliente en cada cambio
- [ ] Link público de seguimiento funciona sin login
- [ ] Costo de envío calculado según zona configurada

---

## H12 — Restaurante: Recetas e Ingredientes
**Duración estimada:** 3 semanas  
**Módulo:** M15, M16  
**UI de referencia:** `ui_modulos_completo.html` → Restaurante

### Migraciones

```bash
php artisan tenants:artisan "make:migration create_recetas_table"
php artisan tenants:artisan "make:migration create_ingredientes_receta_table"
php artisan tenants:artisan "make:migration create_producciones_table"
php artisan tenants:artisan "make:migration create_items_produccion_table"
```

### RecetaCostService

```php
class RecetaCostService
{
    public function calcularCosto(Receta $receta): int
    {
        return $receta->ingredientes->sum(function ($ing) {
            return $ing->producto->costo_unitario * $ing->cantidad * (1 + $ing->merma_pct / 100);
        });
    }

    public function verificarStock(Receta $receta, int $porciones): array
    {
        $faltantes = [];
        foreach ($receta->ingredientes as $ing) {
            $necesario = $ing->cantidad * $porciones;
            if ($ing->producto->stock < $necesario) {
                $faltantes[] = [
                    'producto' => $ing->producto->nombre,
                    'disponible' => $ing->producto->stock,
                    'necesario'  => $necesario,
                    'faltante'   => $necesario - $ing->producto->stock,
                ];
            }
        }
        return $faltantes;
    }

    public function producir(Receta $receta, int $porciones): Produccion
    {
        // Descuenta ingredientes del stock
        foreach ($receta->ingredientes as $ing) {
            MovimientoStock::salida($ing->producto, $ing->cantidad * $porciones, 'produccion');
        }
        return Produccion::create([...]);
    }
}
```

### Vista cocina (sin login extra)

```php
// routes/web.php — pantalla de cocina simple, acceso por PIN del local
Route::get('/cocina', [CocinaController::class, 'index'])->middleware('cocina.pin');
```

Pantalla que muestra comandas en tiempo real (SSE). Sin sidebar, sin auth complejo — solo el PIN del local para acceder.

**Checklist de verificación H12:**
- [ ] Receta calcula costo automáticamente al cambiar precio de ingrediente
- [ ] Producción de X porciones descuenta ingredientes correctamente del stock
- [ ] Alerta clara cuando ingrediente insuficiente para producir
- [ ] Pantalla cocina muestra comandas en tiempo real sin recarga

---

## H13 — RRHH Completo
**Duración estimada:** 4 semanas  
**Módulo:** M21, M22  
**UI de referencia:** `ui_modulos_completo.html` → RRHH

### Migraciones

```bash
php artisan tenants:artisan "make:migration create_empleados_table"
php artisan tenants:artisan "make:migration create_asistencias_table"
php artisan tenants:artisan "make:migration create_vacaciones_table"
php artisan tenants:artisan "make:migration create_permisos_table"
php artisan tenants:artisan "make:migration create_liquidaciones_table"
```

**Tabla `empleados` — campos clave para Chile:**

```php
$table->string('rut')->unique();
$table->string('nombre_completo');
$table->string('cargo');
$table->string('tipo_contrato');    // indefinido|plazo_fijo|honorarios
$table->integer('sueldo_base');     // en CLP
$table->string('afp');              // Habitat|Capital|Cuprum|Modelo|PlanVital|ProVida|Uno
$table->decimal('tasa_afp', 5, 2);
$table->string('salud');            // Fonasa|Banmedica|Colmena|Cruz Blanca|Vida Tres|otros
$table->decimal('tasa_salud', 5, 2);
$table->string('mutual');           // ACHS|IST|Mutual de Seguridad
$table->string('banco');
$table->string('tipo_cuenta');      // corriente|vista|rut
$table->string('numero_cuenta');
$table->json('horario_semanal');    // { lun: {entrada:'09:00',salida:'18:00'}, ... }
```

### LiquidacionCalculator

```php
class LiquidacionCalculator
{
    // Cálculo según normativa chilena 2026
    public function calcular(Empleado $empleado, int $mes, int $anio): array
    {
        $sueldo     = $empleado->sueldo_base;
        $horasExtra = $this->calcularHorasExtra($empleado, $mes, $anio);
        $bruto      = $sueldo + ($horasExtra * $this->valorHoraExtra($sueldo));

        $afp        = round($bruto * $empleado->tasa_afp / 100);
        $salud      = round($bruto * $empleado->tasa_salud / 100);
        $sis        = round($bruto * 0.0163);          // Seguro Invalidez y Sobrevivencia
        $mutual     = round($bruto * 0.0093);          // tasa básica mutual

        $imponible  = $bruto - $afp - $salud - $sis - $mutual;
        $impuesto   = $this->calcularImpuestoUnico($imponible);
        $liquido    = $imponible - $impuesto;

        return compact('bruto', 'afp', 'salud', 'sis', 'mutual', 'impuesto', 'liquido');
    }

    private function calcularImpuestoUnico(int $base): int
    {
        // Tabla impuesto único 2026 — actualizar según SII
        $utm = 66000; // UTM Marzo 2026 aprox
        $base_utm = $base / $utm;
        return match(true) {
            $base_utm <= 13.5  => 0,
            $base_utm <= 30    => (int)($base * 0.04) - (int)(2 * $utm),
            $base_utm <= 50    => (int)($base * 0.08) - (int)(3.2 * $utm),
            $base_utm <= 70    => (int)($base * 0.135)- (int)(5.95 * $utm),
            $base_utm <= 90    => (int)($base * 0.23) - (int)(12.6 * $utm),
            $base_utm <= 120   => (int)($base * 0.304)- (int)(19.26 * $utm),
            default            => (int)($base * 0.35) - (int)(26.22 * $utm),
        };
    }
}
```

### Marcación de asistencia desde POS

```php
// Ruta nueva en tenant.php
Route::post('/asistencia/marcar', [AsistenciaController::class, 'marcar'])
     ->middleware('auth:sanctum'); // el empleado usa su propio token

// Muestra pantalla de marcación en el POS:
// - Input RUT o código rápido del empleado
// - Botón "Entrada" / "Salida"
// - Muestra hora actual y confirmación
```

**Checklist de verificación H13:**
- [ ] Empleado marca entrada/salida desde POS con su RUT
- [ ] Sistema calcula atrasos vs horario configurado
- [ ] Solicitud vacaciones notifica a admin + admin aprueba en un clic
- [ ] Liquidación mensual calcula correctamente AFP + salud + SIS + mutual + impuesto único
- [ ] Liquidación exportable en PDF (formato estándar chileno)

---

## H14 — Reclutamiento
**Duración estimada:** 2 semanas  
**Módulo:** M23  
**Prerequisito:** H13 (al contratar, el postulante pasa a RRHH)  
**UI de referencia:** `ui_modulos_completo.html` → Reclutamiento

### Migraciones

```bash
php artisan tenants:artisan "make:migration create_ofertas_empleo_table"
php artisan tenants:artisan "make:migration create_postulaciones_table"
php artisan tenants:artisan "make:migration create_entrevistas_table"
```

**Tabla `postulaciones`:**

```php
$table->string('etapa')->default('recibida'); // recibida|preseleccionada|entrevista|oferta|contratada|descartada
$table->string('cv_path')->nullable();
$table->jsonb('evaluaciones')->nullable();    // array de { evaluador, puntuacion, notas }
```

### Página pública de postulación

```php
// routes/web.php — sin auth
Route::get('/empleo/{oferta_slug}',    [PostulacionPublicaController::class, 'show']);
Route::post('/empleo/{oferta_slug}',   [PostulacionPublicaController::class, 'store']);
```

### Acción "Contratar"

```php
// PostulacionController::contratar()
public function contratar(Postulacion $postulacion): JsonResponse
{
    // Pre-llenar empleado con datos del postulante
    $empleado = Empleado::create([
        'nombre_completo' => $postulacion->nombre,
        'rut'             => $postulacion->rut,
        'cargo'           => $postulacion->oferta->cargo,
        // resto completa el admin
    ]);

    $postulacion->update(['etapa' => 'contratada', 'empleado_id' => $empleado->id]);

    // Notificar al postulante por WA/email
    return response()->json(['empleado_id' => $empleado->id]);
}
```

**Checklist de verificación H14:**
- [ ] Postulante completa formulario público sin login
- [ ] Admin ve todos los postulantes en Kanban por oferta
- [ ] Al contratar, datos pasan automáticamente al módulo RRHH
- [ ] Notificación WA al postulante en cada cambio de etapa

---

## H15 — Marketing QR
**Duración estimada:** 2 semanas  
**Módulo:** M24  
**UI de referencia:** `ui_modulos_completo.html` → Marketing QR

### Instalación

```bash
composer require endroid/qr-code
```

### Migraciones

```bash
php artisan tenants:artisan "make:migration create_campanas_marketing_table"
php artisan tenants:artisan "make:migration create_escaneos_qr_table"
```

**Tabla `campanas_marketing`:**

```php
$table->string('nombre');
$table->string('tipo_accion'); // descuento_pct|descuento_monto|2x1|abrir_whatsapp|encuesta
$table->integer('valor')->nullable();     // monto o % del descuento
$table->string('codigo_descuento')->unique()->nullable();
$table->string('landing_url')->nullable();
$table->date('valida_hasta')->nullable();
$table->boolean('activa')->default(true);
$table->string('qr_path')->nullable();    // PNG generado
```

### QrGenerator Service

```php
class QrGeneratorService
{
    public function generar(CampanaMarketing $campana): string
    {
        // URL pública de la landing
        $url = url("/qr/{$campana->uuid}");

        $qr = QrCode::create($url)
            ->setSize(400)
            ->setMargin(20)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setEncoding(new Encoding('UTF-8'));

        // Agregar logo del tenant si existe
        if ($logo = tenant()->logo_path) {
            $qr->setLogoPath(storage_path($logo))->setLogoSize(80);
        }

        $path = "qr/{$campana->uuid}.png";
        Storage::put($path, PngWriter::writeString($qr));
        return $path;
    }
}
```

### Landing pública + registro de escaneo

```php
// routes/web.php
Route::get('/qr/{uuid}', [QrLandingController::class, 'show']); // sin auth

// QrLandingController::show()
// 1. Registra escaneo (IP, device, timestamp, geolocalización si disponible)
// 2. Aplica la acción de la campaña:
//    - descuento → muestra código para mostrar en caja
//    - abrir_whatsapp → redirect a wa.me/?text=...
//    - encuesta → muestra formulario
// 3. Registra conversión si el cliente usa el código en una venta
```

**Checklist de verificación H15:**
- [ ] QR generado apunta a landing pública con diseño del tenant
- [ ] Escaneo registra datos sin login del cliente
- [ ] Descuento aplicado en POS vincula el escaneo como conversión
- [ ] Métricas en tiempo real: escaneos, conversiones, tasa

---

## H16 — M31: Venta de Software SaaS (BenderAnd se vende a sí mismo)
**Duración estimada:** 4 semanas  
**Módulo:** M31  
**Prerequisito:** H8 (WA) + H9 (SII) completos  
**UI de referencia:** `ui_plan_completo.html` → SaaS / M31

Este es el primer tenant en producción del sistema. BenderAnd usa BenderAnd para gestionar sus propios clientes.

### Semana 1 — Modelos y migraciones

```bash
php artisan tenants:artisan "make:migration create_saas_planes_table"
php artisan tenants:artisan "make:migration create_saas_clientes_table"
php artisan tenants:artisan "make:migration create_saas_pipeline_table"
php artisan tenants:artisan "make:migration create_saas_cobros_table"
php artisan tenants:artisan "make:migration create_saas_metricas_table"
php artisan tenants:artisan "make:migration create_saas_actividades_table"
php artisan tenants:artisan "make:migration create_saas_demos_table"
```

### Semana 2 — Servicios y controllers

```php
// SaasBillingService — genera cobros y emite facturas vía SiiService
// SaasMetricasService — calcula MRR, ARR, churn, ARPU, cohorts

// MRR correcto:
// MRR = Σ(precio_mensual de cada suscripción en estado 'activo')
// Churn = (cancelados en el mes / activos inicio del mes) × 100
// ARPU = MRR / tenants_activos
```

### Semana 3 — Jobs automáticos

| Job | Cuándo corre | Qué hace |
|---|---|---|
| `GenerarCobrosRecurrentes` | 1° de cada mes 08:00 | Genera registro de cobro para cada suscripción activa |
| `AlertaTrialVencimiento` | Diario | WA al tenant en día 25 del trial |
| `AlertaMorosos` | Diario | WA a tenants +5 días sin pagar |
| `SuspenderMorosos` | Diario | Suspende tenants +30 días sin pagar |
| `ActualizarMetricasMRR` | Diario 00:30 | Persiste snapshot de métricas |
| `SeguimientoTrialDia7` | Diario | WA de engagement en día 7 del trial |
| `EmitirFacturasDelMes` | 2° de cada mes | DTE por cada cobro pagado del mes |
| `ReporteEjecutivos` | Lunes 08:00 | Resumen semanal al equipo comercial |

### Semana 4 — UI Panel SaaS

Preset `saas_provider` activa M31 + M20 + M21 + M23. El sidebar NO muestra stock, delivery, comandas ni timers.

Vistas:
- **Pipeline tenants** (Kanban): Prospecto → Demo → Trial → Activo → Cancelado
- **Ficha CRM del tenant**: uso, billing, historial, acciones rápidas
- **Dashboard MRR**: gráfico 12 meses, distribución por plan/rubro
- **Gestión de Planes** (precios, módulos incluidos)
- **Cobros del mes** (pendientes, vencidos, emitir factura manual)
- **Demos agendadas** (calendario del equipo comercial)

**Checklist de verificación H16:**
- [ ] Prospecto escribe al bot → aparece automáticamente en `saas_pipeline`
- [ ] Trial creado via bot → schema tenant creado, preset aplicado
- [ ] Alertas WA en día 7, 25 y vencimiento del trial
- [ ] Cobro generado el día 1, factura electrónica emitida para cada pago
- [ ] Tenant moroso +30 días → suspendido automáticamente
- [ ] MRR y churn calculados correctamente
- [ ] Ejecutivo solo ve sus propios prospectos; admin ve todo

---

## H17 — Dashboard Ejecutivo Unificado + API Pública
**Duración estimada:** 3 semanas  
**Estado actual:** ✅ Parcialmente completado (`PublicApiController`, `SaasDashboardController` ya existen)  
**Lo que falta:**

### Tareas pendientes de H17

| # | Tarea | Esfuerzo |
|---|---|---|
| 17.1 | KPIs cruzados: ventas + WA + RRHH + delivery en un solo dashboard | 2 días |
| 17.2 | Centro de notificaciones SSE en tiempo real (alertas de todos los módulos) | 2 días |
| 17.3 | API REST documentada con OpenAPI/Swagger | 1 día |
| 17.4 | Webhooks salientes configurables por tenant | 2 días |
| 17.5 | Exportación de reportes en Excel/PDF/CSV por módulo | 2 días |
| 17.6 | Optimización: índices en tablas grandes + cache de reportes pesados | 1 día |

**Webhooks salientes (17.4):**

```php
// Permite al tenant configurar URLs externas que recibirán eventos
// Tabla: webhooks_config
// Evento: 'venta.confirmada' | 'entrega.actualizada' | 'empleado.asistencia' | etc.

// Cuando ocurre el evento → dispara job → POST a la URL del tenant con payload firmado
class DispararWebhookJob implements ShouldQueue
{
    public function handle(): void
    {
        Http::withHeaders(['X-Benderand-Signature' => $this->firmar($this->payload)])
            ->post($this->webhook->url, $this->payload);
    }
}
```

---

## H18 — Testing, Seguridad y Deploy
**Duración estimada:** 2 semanas  
**Prerequisito:** H8–H17 todos completos

### Tests de integración por rubro

```php
// tests/Feature/Rubros/MotelFlowTest.php
public function test_flujo_completo_motel(): void
{
    // 1. Crear tenant con preset motel
    // 2. Crear habitaciones
    // 3. Iniciar renta → timer activo
    // 4. Extender renta
    // 5. Checkout → venta confirmada
    // 6. DTE emitido (boleta sin detalle)
    // 7. Habitación queda en estado 'limpieza'
}

// tests/Feature/Rubros/MedicoFlowTest.php
// tests/Feature/Rubros/FerreteriFlowTest.php
// tests/Feature/Security/TenantIsolationTest.php ← CRÍTICO
```

### Test de aislamiento entre tenants (CRÍTICO)

```php
// TenantIsolationTest.php
public function test_tenant_a_no_accede_datos_tenant_b(): void
{
    [$tenantA, $tokenA] = $this->crearTenantConToken('tenant-a');
    [$tenantB, $tokenB] = $this->crearTenantConToken('tenant-b');

    // Crear venta en tenant B
    $ventaB = $this->crearVenta($tenantB);

    // Intentar acceder desde tenant A
    $response = $this->withToken($tokenA)
        ->withHeaders(['X-Tenant' => 'tenant-a.benderand.cl'])
        ->getJson("/api/ventas/{$ventaB->id}");

    $response->assertStatus(404); // NO debe devolver 403 (que revela que existe), debe ser 404
}
```

### Auditoría de seguridad

```
[ ] SQL injection: inputs sin sanitizar en búsquedas
[ ] XSS: outputs sin escapar en HTML
[ ] CSRF: rutas POST sin token
[ ] Tenant isolation: schema activo siempre del tenant autenticado
[ ] Mass assignment: $fillable explícito en todos los modelos
[ ] Rate limiting: endpoints de auth y webhooks
[ ] Certificados SII: cifrado AES-256 en DB, nunca en logs
[ ] Logs: nunca loggear datos sensibles (RUT, teléfono, monto completo)
```

### Runbook de Deploy

```bash
# 1. Servidor: Ubuntu 22.04 + Nginx + PHP 8.2-FPM + PostgreSQL 16 + Redis 7

# 2. Pull y setup
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Migraciones (central primero, luego todos los tenants)
php artisan migrate --path=database/migrations
php artisan tenants:migrate

# 4. Horizon (queue worker)
php artisan horizon:terminate
supervisorctl restart horizon

# 5. Scheduler (en crontab del servidor)
# * * * * * cd /var/www/benderand && php artisan schedule:run >> /dev/null 2>&1

# 6. Verificar
php artisan horizon:status
php artisan queue:monitor
```

**Checklist de verificación H18:**
- [ ] Tests pasan en CI/CD (GitHub Actions o similar)
- [ ] Tenant isolation: Tenant A nunca ve datos de Tenant B
- [ ] SQL injection: inputs sanitizados en todos los buscadores
- [ ] SII en producción: al menos 1 boleta real emitida y aceptada
- [ ] Horizon procesando jobs sin errores
- [ ] Scheduler activo: crons de billing, stock, WA corriendo
- [ ] Docker prod levanta sin errores con `docker-compose -f docker-compose.prod.yml up`

---

## Resumen de Todos los Hitos H8–H18

| Hito | Módulo | Semanas | Prerequisito | Archivos nuevos clave |
|---|---|---|---|---|
| **H8** | ERP ↔ WhatsApp Bot | 3 | H7 | `JwtBridgeService`, `BotApiController`, `JwtBridgeMiddleware` |
| **H9** | SII / LibreDTE | 4 | — | `SiiService`, `EmitirDteJob`, `config_sii`, `dte_emitidos` |
| **H10** | Compras / Proveedores | 4 | H3 | `OrdenCompraController`, `RecepcionController`, `StockAlertService` |
| **H11** | Delivery | 3 | H1 | `EntregaController`, `RepartidorController`, `DeliveryAssignService` |
| **H12** | Restaurante / Recetas | 3 | H10 | `RecetaController`, `ProduccionController`, `RecetaCostService` |
| **H13** | RRHH | 4 | H2 | `EmpleadoController`, `AsistenciaController`, `LiquidacionCalculator` |
| **H14** | Reclutamiento | 2 | H13 | `OfertaEmpleoController`, `PostulacionController` |
| **H15** | Marketing QR | 2 | H1 | `QrCampanaController`, `QrGeneratorService`, landing pública |
| **H16** | M31 SaaS | 4 | H8+H9 | `SaasBillingService`, `SaasMetricasService`, 8 jobs |
| **H17** | Dashboard + API | 3 | H8–H16 | SSE, OpenAPI docs, webhooks salientes, exportaciones |
| **H18** | Testing + Deploy | 2 | H17 | Tests por rubro, auditoría seguridad, runbook |

**Total: ~34 semanas (~8.5 meses) si se trabaja en secuencia**  
**Con paralelización (H10–H15 en paralelo tras H9): ~5–6 meses**

---

## Variables de Entorno Nuevas por Hito

```bash
# H8 — JWT Bridge
JWT_BRIDGE_SECRET=secret_compartido_con_node

# H9 — SII
SII_AMBIENTE=certificacion               # certificacion | produccion
LIBREDTE_URL=https://libredte.cl
LIBREDTE_API_KEY=tu_api_key

# H15 — QR
QR_LOGO_DEFAULT=storage/logos/benderand-qr.png

# H16 — M31 SaaS (ya existen algunas en H5)
SAAS_COBRO_DIA=1                         # día del mes que se generan cobros
SAAS_DIAS_GRACIA=3
SAAS_DIAS_SUSPENSION=30
```

---

*BenderAnd ERP — Hitos H8–H18*  
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Sanctum · Redis · Horizon*  
*Node.js (Moteland): Express · BullMQ · Socket.io · Prisma · GPT-4o-mini*
