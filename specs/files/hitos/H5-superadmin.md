# H5 — Super Admin + Billing
**BenderAnd POS · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ Pendiente · Duración estimada: 2 semanas · Requiere: H4 completo*

> Alcance: Panel de control de la plataforma para gestionar todos los tenants.
> Cobros mensuales automáticos. Métricas MRR y churn.

---

## Entregables

- [ ] Auth separado para `super_admin` (schema `public`, tabla `super_admins`)
- [ ] Lista de tenants con filtros (estado, plan, rubro) y métricas básicas
- [ ] MRR, churn, crecimiento mensual con `DATE_TRUNC` PostgreSQL
- [ ] Suspender / reactivar / cancelar tenant (bloquea todos sus usuarios)
- [ ] Impersonar tenant con audit log en `audit_logs`
- [ ] Tabla `subscriptions` + pagos + cron de cobro mensual
- [ ] Panel web: `superadmin.html` conectado al API

---

## Rutas super admin (schema public)

```php
// routes/central.php — sin TenancyMiddleware
Route::prefix('superadmin')->group(function () {
    Route::post('/auth/login', [SuperAdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'central'])->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index']);
        Route::apiResource('/tenants', TenantController::class);
        Route::put('/tenants/{tenant}/estado', [TenantController::class, 'cambiarEstado']);
        Route::post('/tenants/{tenant}/impersonar', [TenantController::class, 'impersonar']);
        Route::apiResource('/planes', PlanController::class);
        Route::get('/metricas/mrr', [MetricasController::class, 'mrr']);
        Route::get('/metricas/churn', [MetricasController::class, 'churn']);
        Route::get('/cobros/proximos', [BillingController::class, 'proximos']);
        Route::post('/cobros/generar-mes', [BillingController::class, 'generarMes']);
    });
});
```

---

## MRR con DATE_TRUNC

```php
// SuperAdminDashboardController
public function mrr(): array
{
    return DB::select("
        SELECT
            DATE_TRUNC('month', proximo_cobro) AS mes,
            SUM(monto_clp) AS mrr
        FROM subscriptions
        WHERE estado = 'activa'
        GROUP BY mes
        ORDER BY mes DESC
        LIMIT 12
    ");
}
```

---

## Suspender tenant

```php
public function cambiarEstado(Tenant $tenant, Request $request): JsonResponse
{
    $nuevoEstado = $request->input('estado'); // activo|suspendido|cancelado

    // Revocar todos los tokens del tenant
    if ($nuevoEstado === 'suspendido') {
        tenancy()->initialize($tenant);
        \Laravel\Sanctum\PersonalAccessToken::query()->delete();
        tenancy()->end();
    }

    $tenant->update(['estado' => $nuevoEstado]);

    AuditLog::create([
        'super_admin_id' => auth()->id(),
        'tenant_id'      => $tenant->id,
        'accion'         => 'cambiar_estado',
        'metadata'       => ['estado_anterior' => $tenant->getOriginal('estado'), 'nuevo' => $nuevoEstado],
        'ip'             => request()->ip(),
    ]);

    return response()->json(['estado' => $nuevoEstado]);
}
```

---

## Cron de cobro mensual

```php
// bootstrap/app.php
Schedule::call(function () {
    $subscriptions = Subscription::where('estado', 'activa')
        ->whereDate('proximo_cobro', today())
        ->with('tenant')
        ->get();

    foreach ($subscriptions as $sub) {
        // Notificar al admin del tenant
        SendWhatsAppNotification::dispatch('cobro_mensual', $sub);

        // Actualizar próximo cobro
        $sub->update(['proximo_cobro' => now()->addMonth()]);
    }
})->monthlyOn(1, '08:00');
```

---

## Checklist H5

- [ ] Super admin no puede acceder a endpoints de tenant (middleware `central`)
- [ ] Impersonar tenant queda registrado en `audit_logs` con IP
- [ ] Suspender tenant → usuarios del tenant reciben 401 en próxima request
- [ ] MRR calculado correctamente con subscriptions activas
- [ ] Churn = tenants cancelados mes / tenants activos inicio mes × 100
- [ ] `superadmin.html` conectado al API con datos reales
