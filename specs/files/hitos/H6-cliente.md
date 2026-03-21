# H6 — Portal Cliente Web
**BenderAnd POS · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ Pendiente · Duración estimada: 3 semanas · Requiere: H2 completo*

> Alcance: El cliente final tiene su propia interfaz para ver compras,
> hacer pedidos remotos y pagar deudas. Integración Transbank WebPay.

---

## Entregables

- [ ] Auth cliente con Sanctum abilities limitadas (`cliente` scope)
- [ ] Historial de compras propias con detalle de ítems
- [ ] Catálogo simplificado con stock disponible
- [ ] Crear pedido remoto → estado `remota_pendiente`
- [ ] Seguimiento de estado (pendiente → preparando → listo → entregado)
- [ ] Retiro / envío en el pedido
- [ ] Ver y pagar deudas propias
- [ ] Integración Transbank WebPay (Chile)
- [ ] Notificación WA cuando pedido cambia de estado

---

## Auth cliente con abilities

```php
// Al hacer login como cliente
$token = $usuario->createToken('portal-cliente', ['cliente:read', 'pedido:create', 'deuda:pay']);
```

```php
// Middleware de ability en rutas del portal
Route::middleware(['auth:sanctum', 'abilities:cliente:read'])->group(function () {
    Route::get('/portal/historial', [PortalClienteController::class, 'historial']);
    Route::get('/portal/catalogo', [PortalClienteController::class, 'catalogo']);
    Route::get('/portal/deudas', [PortalClienteController::class, 'deudas']);
    Route::get('/portal/pedidos/{uuid}', [PortalClienteController::class, 'seguimiento']);
});

Route::middleware(['auth:sanctum', 'abilities:pedido:create'])->group(function () {
    Route::post('/portal/pedidos', [PortalClienteController::class, 'crearPedido']);
});

Route::middleware(['auth:sanctum', 'abilities:deuda:pay'])->group(function () {
    Route::post('/portal/deudas/{deuda}/pagar', [PortalClienteController::class, 'pagarDeuda']);
});
```

---

## Transbank WebPay

```bash
composer require transbank/transbank-sdk
```

```php
// app/Services/WebPayService.php
public function iniciarPago(int $monto, string $ordenCompra, string $retornoUrl): array
{
    $tx = new WebpayPlus\Transaction(new Options(
        config('services.transbank.commerce_code'),
        config('services.transbank.api_key'),
        \Transbank\WebpayPlus\Options::DEFAULT_INTEGRATION_TYPE  // LIVE en prod
    ));

    $response = $tx->create($ordenCompra, session()->getId(), $monto, $retornoUrl);

    return [
        'url'   => $response->getUrl(),
        'token' => $response->getToken(),
    ];
}

public function confirmarPago(string $token): array
{
    $tx = new WebpayPlus\Transaction(...);
    $response = $tx->commit($token);

    return [
        'aprobada'    => $response->isApproved(),
        'codigo_auth' => $response->getAuthorizationCode(),
        'monto'       => $response->getAmount(),
    ];
}
```

---

## Flujo pedido remoto

```
1. Cliente crea pedido: POST /portal/pedidos
   → venta estado=remota_pendiente
   → WA al admin: "Nuevo pedido remoto de Juan #31"

2. Admin prepara el pedido
   → PUT /ventas/{id}/estado { "estado": "preparando" }
   → WA al cliente: "Tu pedido está siendo preparado"

3. Pedido listo
   → PUT /ventas/{id}/estado { "estado": "listo" }
   → WA al cliente: "Tu pedido está listo para retiro"

4. Cliente retira / se despacha
   → POST /ventas/{id}/confirmar
   → Stock descontado, venta pagada
```

---

## Checklist H6

- [ ] Cliente solo ve sus propias compras (nunca las de otro cliente)
- [ ] Catálogo solo muestra productos con stock > 0 (o tipo servicio)
- [ ] Pedido remoto aparece en dashboard del admin como `remota_pendiente`
- [ ] Seguimiento: cliente puede consultar estado por UUID sin auth
- [ ] Transbank WebPay en ambiente integración (certificación) funciona
- [ ] Pago exitoso → deuda marcada como pagada automáticamente
- [ ] Notificaciones WA en cada cambio de estado del pedido
- [ ] Cliente no puede ver productos con costo (campo oculto en catálogo público)
