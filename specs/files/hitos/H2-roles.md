# H2 — Multi-operario + Roles Completos
**BenderAnd POS · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ Pendiente · Duración estimada: 2 semanas · Requiere: H1 completo*

> Alcance: Múltiples operarios arman la misma venta simultáneamente.
> Cajero separado que solo cobra. Roles con Gates y Policies.

---

## Entregables

- [ ] `GET /ventas/por-cliente?rut=&codigo=` — buscar venta abierta por RUT o código rápido
- [ ] Estado `en_caja` — bloquea adición de ítems, solo cajero puede cobrar
- [ ] Rol `bodega` con permisos solo de inventario
- [ ] Gates + Policies Laravel para permisos granulares
- [ ] Frontend adapta UI según rol en el payload del login
- [ ] Código rápido auto-asignado al crear cliente (entero único, sin RUT)
- [ ] Log de qué operario agregó cada ítem (`operario_id` en `items_venta`)
- [ ] Rol `cliente` con acceso limitado al portal propio

---

## Middleware de roles

```bash
php artisan make:middleware RoleMiddleware
```

```php
// app/Http/Middleware/RoleMiddleware.php
public function handle(Request $request, Closure $next, ...$roles): Response
{
    $usuario = $request->user();
    if (!$usuario || !in_array($usuario->rol, $roles)) {
        return response()->json(['error' => 'Sin permiso para esta acción'], 403);
    }
    return $next($request);
}
```

Registrar en `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['role' => RoleMiddleware::class]);
})
```

---

## Gates

```php
// app/Providers/AppServiceProvider.php — boot()
Gate::define('confirmar-venta',    fn($u) => in_array($u->rol, ['admin', 'cajero']));
Gate::define('agregar-item',       fn($u) => in_array($u->rol, ['admin', 'cajero', 'operario']));
Gate::define('gestionar-productos',fn($u) => in_array($u->rol, ['admin', 'bodega']));
Gate::define('ver-dashboard',      fn($u) => $u->rol === 'admin');
Gate::define('anular-venta',       fn($u) => $u->rol === 'admin');
Gate::define('ver-notas-cliente',  fn($u, $cliente) =>
    $u->rol === 'admin' || ($u->rol === 'operario' && $u->id === $cliente->operario_asignado_id)
);
```

---

## Código rápido auto-asignado

```php
// ClienteObserver o en ClienteService
public function asignarCodigoRapido(Cliente $cliente): void
{
    if (!$cliente->codigo_rapido) {
        // Próximo entero disponible en este tenant
        $ultimo = Cliente::max('codigo_rapido') ?? 0;
        $cliente->update(['codigo_rapido' => $ultimo + 1]);
    }
}
```

---

## Flujo multi-operario

```
Operario A:  POST /ventas           → venta abierta #150 para RUT 12.345.678-9
Operario B:  GET  /ventas/por-cliente?rut=12.345.678-9  → encuentra venta #150
Operario B:  POST /ventas/150/items → agrega su ítem (con su operario_id)
Cajero:      PUT  /ventas/150/estado { "estado": "en_caja" }
             → estado cambia, ítems bloqueados
Cajero:      POST /ventas/150/confirmar → cobra
```

---

## Respuesta de login con permisos

```json
{
  "token": "1|xyz...",
  "usuario": { "id": 5, "nombre": "Juan", "rol": "cajero" },
  "permisos": {
    "puede_confirmar_venta": true,
    "puede_agregar_item": true,
    "puede_ver_dashboard": false,
    "puede_gestionar_productos": false,
    "puede_anular_venta": false
  },
  "tenant": {
    "nombre": "Ferretería Don Pedro",
    "rubro_config": {
      "etiqueta_cliente": "Cliente",
      "etiqueta_operario": "Vendedor",
      "tiene_stock_fisico": true,
      "tiene_renta": false
    }
  }
}
```

El frontend usa `permisos` **solo para UX** (mostrar/ocultar botones).
La seguridad real siempre está en el backend.

---

## Checklist de verificación H2

- [ ] Operario A crea venta → Operario B la encuentra por RUT y agrega ítems
- [ ] `items_venta.operario_id` registra quién agregó cada ítem
- [ ] Al pasar a `en_caja`, operarios ya no pueden agregar ítems (409)
- [ ] Cajero cobra, admin puede hacer todo, operario no puede cobrar
- [ ] Bodega solo ve productos y compras, no ventas
- [ ] Cliente portal solo ve sus propias compras y deudas
- [ ] `codigo_rapido` auto-asignado, único en el tenant
- [ ] Buscar venta por `?codigo=31` funciona igual que por RUT
- [ ] RUT normalizado: `12345678`, `12.345.678-9`, `12345678-9` → mismo cliente
