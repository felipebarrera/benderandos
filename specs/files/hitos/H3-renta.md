# H3 — Renta + Servicios + Fraccionados
**BenderAnd POS · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ Pendiente · Duración estimada: 3 semanas · Requiere: H2 completo*

> Alcance: Motel, pádel, médico, abogado, jamón, cable funcionan correctamente.
> Tres tipos de producto con comportamiento distinto: renta, servicio, fraccionado.

---

## Entregables

- [ ] Tipo `renta`: tabla `rentas`, inicio/fin programado, timer en panel
- [ ] Job `CheckRentasVencidas` cada minuto → alerta en panel y WA
- [ ] Extensión de renta desde caja (+tiempo, +cargo)
- [ ] Checkout de renta → stock vuelve a disponible
- [ ] Tipo `servicio`: sin descuento de stock, sin `cantidad`
- [ ] Tipo `fraccionado`: `cantidad NUMERIC(12,3)`, precio por unidad base
- [ ] Panel visual habitaciones/canchas con estado en tiempo real (Broadcasting)
- [ ] Tipo `honorarios`: boleta de honorarios en metadata de venta

---

## Migraciones

```bash
php artisan make:migration create_rentas_table --path=database/migrations/tenant
```

```sql
CREATE TABLE rentas (
    id              BIGSERIAL PRIMARY KEY,
    item_venta_id   BIGINT NOT NULL REFERENCES items_venta(id),
    producto_id     BIGINT NOT NULL REFERENCES productos(id),
    cliente_id      BIGINT REFERENCES clientes(id),
    inicio_real     TIMESTAMP,
    fin_programado  TIMESTAMP NOT NULL,
    fin_real        TIMESTAMP,
    estado          VARCHAR(20) DEFAULT 'activa',  -- activa|vencida|devuelta|extendida
    cargo_extra     BIGINT DEFAULT 0,
    notas           TEXT,
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_rentas_estado ON rentas(estado);
CREATE INDEX idx_rentas_activas_fin ON rentas(fin_programado) WHERE estado = 'activa';
```

---

## Job CheckRentasVencidas

```bash
php artisan make:job CheckRentasVencidas
```

```php
// Registrar en bootstrap/app.php (scheduler):
Schedule::job(new CheckRentasVencidas)->everyMinute();
```

```php
// app/Jobs/CheckRentasVencidas.php
public function handle(): void
{
    $venciendo = Renta::query()
        ->where('estado', 'activa')
        ->where('fin_programado', '<=', now()->addMinutes(10))
        ->get();

    foreach ($venciendo as $renta) {
        // Emitir evento Broadcasting para el panel
        broadcast(new RentaVenciendo($renta));

        // Notificar por WhatsApp al admin del tenant
        if ($renta->producto->tenant?->whatsapp_admin) {
            SendWhatsAppNotification::dispatch('renta_venciendo', $renta);
        }
    }
}
```

---

## Panel de recursos (Broadcasting)

```php
// app/Events/RentaVenciendo.php
class RentaVenciendo implements ShouldBroadcast
{
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('tenant.' . tenant('id'));
    }
}
```

Frontend escucha:
```javascript
Echo.private(`tenant.${tenantId}`)
    .listen('RentaVenciendo', (data) => {
        // Actualizar tarjeta de habitación en el panel
        actualizarEstadoRecurso(data.producto_id, data.minutos_restantes);
    });
```

---

## Fraccionados

```php
// ItemVentaController — agregar ítem fraccionado
// El frontend envía: { "producto_id": 14, "cantidad": 47.5 }
// La cantidad es NUMERIC(12,3) — hasta 3 decimales
// precio_unitario es por unidad base (metro, kg, litro)
// total_item = precio_unitario * cantidad (redondeado a entero CLP)

$totalItem = (int) round($request->precio_unitario * $request->cantidad);
```

---

## API rentas

```php
// routes/tenant.php — agregar en H3
Route::get('/rentas/panel', [RentaController::class, 'panel']);
Route::post('/rentas/{renta}/extender', [RentaController::class, 'extender']);
Route::post('/rentas/{renta}/devolver', [RentaController::class, 'devolver']);
```

---

## Checklist de verificación H3

**Renta:**
- [ ] Crear ítem tipo `renta` → se crea registro en tabla `rentas`
- [ ] `fin_programado` = `inicio_real` + duración elegida
- [ ] Panel de habitaciones muestra estado y timer en tiempo real
- [ ] Alerta Broadcasting cuando quedan ≤10 min
- [ ] Extender renta actualiza `fin_programado` y agrega `cargo_extra`
- [ ] Devolver renta → `fin_real` registrado, estado `devuelta`
- [ ] Anular venta con renta → renta queda en estado `devuelta`

**Servicio:**
- [ ] Confirmar venta con ítems tipo `servicio` → stock NO se descuenta
- [ ] `cantidad` en `productos` es NULL para servicios (sin alarma de stock)
- [ ] Tipo `honorarios` genera metadata en la venta con tipo_documento correcto

**Fraccionado:**
- [ ] Cantidad 0.350 kg se guarda y muestra correctamente
- [ ] `total_item = precio_unitario * cantidad` redondeado a CLP entero
- [ ] Stock descuenta fracción exacta (52.5 - 47.5 = 5.0 metros)
- [ ] Stock insuficiente con fraccionados: 5.0 metros disponibles, solicita 6 → 422
