# H1 — Venta Minorista
**BenderAnd POS · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ En progreso · Duración estimada: 3 semanas*

> Alcance: El sistema funciona para una tienda retail básica end-to-end.
> Auth → Productos → Venta → Compra → Dashboard.

---

## Prerrequisitos

- H0 completado: Docker dev levantado, PostgreSQL conectado, Laravel instalado
- `php artisan migrate` corrió sin errores en schema `public`
- Primer tenant de prueba creado vía `tinker`

---

## Entregables

- [ ] Login email + password → token Sanctum
- [ ] CRUD productos (admin/bodega)
- [ ] Búsqueda producto por nombre/código/marca con debounce
- [ ] Crear venta → agregar ítems → confirmar → descuenta stock
- [ ] Compra de stock con actualización de inventario
- [ ] Dashboard: ventas del día, stock crítico, deudas pendientes
- [ ] Clientes con RUT y código rápido (#31)
- [ ] Deudas / fiado
- [ ] Encargos básicos

---

## Migraciones tenant (orden de ejecución)

```bash
# Crear migraciones en database/migrations/tenant/
php artisan make:migration create_usuarios_table
php artisan make:migration create_clientes_table
php artisan make:migration create_tipos_pago_table
php artisan make:migration create_productos_table
php artisan make:migration create_ventas_table
php artisan make:migration create_items_venta_table
php artisan make:migration create_compras_table
php artisan make:migration create_items_compra_table
php artisan make:migration create_movimientos_stock_table
php artisan make:migration create_deudas_table
php artisan make:migration create_encargos_table

# Correr en todos los tenants
php artisan tenants:migrate
```

---

## Modelos Eloquent

```bash
php artisan make:model Tenant/Usuario
php artisan make:model Tenant/Cliente
php artisan make:model Tenant/Producto
php artisan make:model Tenant/Venta
php artisan make:model Tenant/ItemVenta
php artisan make:model Tenant/Compra
php artisan make:model Tenant/ItemCompra
php artisan make:model Tenant/MovimientoStock
php artisan make:model Tenant/Deuda
php artisan make:model Tenant/Encargo
php artisan make:model Tenant/TipoPago
```

---

## Controllers y rutas

```bash
php artisan make:controller Api/AuthController
php artisan make:controller Api/Tenant/ProductoController --api
php artisan make:controller Api/Tenant/VentaController --api
php artisan make:controller Api/Tenant/ClienteController --api
php artisan make:controller Api/Tenant/CompraController
php artisan make:controller Api/Tenant/DashboardController
php artisan make:controller Api/Tenant/DeudaController --api
php artisan make:controller Api/Tenant/EncargoController --api
```

### routes/tenant.php (rutas H1)

```php
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'tenancy.initialize'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Productos
    Route::get('/productos/buscar', [ProductoController::class, 'buscar']);
    Route::apiResource('/productos', ProductoController::class);
    Route::post('/productos/{producto}/ajuste-stock', [ProductoController::class, 'ajusteStock']);

    // Clientes
    Route::get('/clientes/buscar', [ClienteController::class, 'buscar']);
    Route::get('/clientes/{cliente}/historial', [ClienteController::class, 'historial']);
    Route::apiResource('/clientes', ClienteController::class);

    // Ventas
    Route::get('/ventas/por-cliente', [VentaController::class, 'porCliente']);
    Route::post('/ventas/{venta}/items', [VentaController::class, 'agregarItem']);
    Route::put('/ventas/{venta}/items/{item}', [VentaController::class, 'editarItem']);
    Route::delete('/ventas/{venta}/items/{item}', [VentaController::class, 'quitarItem']);
    Route::put('/ventas/{venta}/estado', [VentaController::class, 'cambiarEstado']);
    Route::post('/ventas/{venta}/confirmar', [VentaController::class, 'confirmar']);
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
        ->middleware('role:admin');
    Route::apiResource('/ventas', VentaController::class)->only(['index', 'show', 'store']);

    // Compras
    Route::post('/compras', [CompraController::class, 'crear'])
        ->middleware('role:admin,bodega');
    Route::get('/compras', [CompraController::class, 'index'])
        ->middleware('role:admin,bodega');

    // Deudas
    Route::get('/deudas', [DeudaController::class, 'index']);
    Route::post('/deudas/{deuda}/pagar', [DeudaController::class, 'pagar']);

    // Encargos
    Route::apiResource('/encargos', EncargoController::class);
});
```

---

## Services

```bash
php artisan make:service VentaService     # lógica cobro, descuento stock
php artisan make:service StockService     # movimientos, alertas, fraccionados
```

### VentaService — flujo confirmar

```php
// app/Services/VentaService.php
public function confirmar(Venta $venta, array $datos): Venta
{
    DB::transaction(function () use ($venta, $datos) {
        // 1. Verificar estado
        if ($venta->estado !== 'en_caja') {
            throw new \Exception('La venta no está en caja');
        }

        // 2. Descontar stock de cada ítem
        foreach ($venta->items as $item) {
            if ($item->tipo_item === 'normal') {
                $this->stockService->descontar($item->producto_id, $item->cantidad, $venta->id);
            }
        }

        // 3. Calcular totales con descuento
        $subtotal = $venta->items->sum('total_item');
        $descuentoMonto = $datos['descuento_monto'] ?? 0;
        $descuentoPct = $datos['descuento_pct'] ?? 0;
        if ($descuentoPct > 0) {
            $descuentoMonto = round($subtotal * $descuentoPct / 100);
        }
        $total = $subtotal - $descuentoMonto;

        // 4. Actualizar venta
        $venta->update([
            'estado'          => $datos['es_deuda'] ? 'fiada' : 'pagada',
            'tipo_pago_id'    => $datos['tipo_pago_id'],
            'subtotal'        => $subtotal,
            'descuento_monto' => $descuentoMonto,
            'descuento_pct'   => $descuentoPct,
            'total'           => $total,
            'es_deuda'        => $datos['es_deuda'] ?? false,
            'cajero_id'       => auth()->id(),
            'pagado_at'       => now(),
        ]);

        // 5. Crear deuda si es fiado
        if ($datos['es_deuda'] ?? false) {
            Deuda::create([
                'venta_id'   => $venta->id,
                'cliente_id' => $venta->cliente_id,
                'monto'      => $total,
            ]);
        }

        // 6. Notificar por WhatsApp (async)
        if ($venta->cliente?->whatsapp) {
            SendWhatsAppNotification::dispatch('comprobante', $venta)->onQueue('notifications');
        }
    });

    return $venta->fresh();
}
```

---

## Seeders

```bash
php artisan make:seeder TipoPagoSeeder    # efectivo, débito, crédito, transferencia
php artisan make:seeder UsuarioAdminSeeder
php artisan make:seeder ProductosDemoSeeder   # 20 productos ferretería
```

### TipoPagoSeeder
```php
TipoPago::insert([
    ['nombre' => 'Efectivo'],
    ['nombre' => 'Débito'],
    ['nombre' => 'Crédito'],
    ['nombre' => 'Transferencia'],
    ['nombre' => 'WebPay'],
]);
```

---

## Frontend — pantallas H1

Conectar las pantallas HTML existentes al API real. Los stubs JS (`// TODO: API`) se reemplazan por `fetch`:

| Pantalla | Archivo | Endpoints que consume |
|---|---|---|
| Login | `login.html` | `POST /auth/login` |
| POS Caja | `pos_v4.html` | `GET /productos/buscar`, `POST /ventas`, `POST /ventas/{id}/items`, `POST /ventas/{id}/confirmar` |
| Admin Panel | `admin_dashboard_v2.html` | `GET /dashboard`, `GET /ventas`, `GET /productos`, `POST /compras` |
| Compra | `compra.html` | `GET /productos/buscar`, `POST /compras` |
| Ticket | `ticket.html` | `GET /ventas/{id}` |

---

## Checklist de verificación H1

**Auth:**
- [ ] `POST /auth/login` devuelve token Sanctum con payload correcto
- [ ] Request sin token → 401
- [ ] Token expirado → 401 con mensaje claro

**Productos:**
- [ ] Buscar por nombre, código y marca (mínimo 2 chars, debounce 300ms en frontend)
- [ ] Crear producto con tipo `stock_fisico`, `servicio`, `renta`, `fraccionado`
- [ ] Ajuste manual de stock registra `movimiento_stock`
- [ ] Stock no puede quedar negativo al confirmar venta

**Ventas:**
- [ ] Crear venta vacía → estado `abierta`
- [ ] Agregar ítem → subtotal y total se recalculan
- [ ] Quitar ítem → stock no se toca hasta confirmar
- [ ] Confirmar venta → stock descontado, estado `pagada`, `pagado_at` registrado
- [ ] Confirmar como fiado → crea registro en `deudas`
- [ ] Anular venta → stock revertido, solo admin

**Compras:**
- [ ] Registrar compra → stock incrementado en cada producto
- [ ] `movimiento_stock` creado por cada ítem con tipo `compra`

**Dashboard:**
- [ ] Ventas del día calculadas con `DATE_TRUNC('day', pagado_at)`
- [ ] Stock crítico = productos donde `cantidad <= cantidad_minima`
- [ ] Deudas pendientes = suma de `deudas` con `estado = 'pendiente'`

**Multi-tenant:**
- [ ] Dos tenants distintos no pueden ver datos entre sí
- [ ] JWT de un tenant rechazado en endpoint de otro tenant

---

## Notas técnicas

- `valor_venta` y `costo` en **pesos CLP enteros** (BIGINT). CLP no tiene centavos.
- `cantidad` en `items_venta` es `NUMERIC(12,3)` para fraccionados (0.350 kg).
- Búsqueda de productos usa índice GIN con `to_tsvector('spanish', ...)` — más rápido que `ILIKE` en tablas grandes.
- `MovimientoStock` se registra en **toda** variación de stock: venta, compra, ajuste, merma.
- El frontend **nunca confía en los permisos del token** para lógica de negocio — el backend siempre valida.
