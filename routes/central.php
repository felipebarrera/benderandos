<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Central\SuperAdminAuthController;
use App\Http\Controllers\Central\SuperAdminDashboardController;
use App\Http\Controllers\Central\TenantManageController;
use App\Http\Controllers\Central\MetricsController;
use App\Http\Controllers\Central\BillingController;

Route::prefix('superadmin')->group(function () {
    // Auth
    Route::post('/login', [SuperAdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('/me', [SuperAdminAuthController::class, 'me']);

        // Dashboard/Metrics
        Route::get('/dashboard', [\App\Http\Controllers\Central\SuperAdminDashboardController::class, 'index']);
        Route::get('/metrics', [\App\Http\Controllers\Central\MetricsController::class, 'index']);

        // Tenants
        Route::get('/tenants', [TenantManageController::class, 'index']);
        Route::get('/tenants/{tenant}', [TenantManageController::class, 'show']);
        Route::put('/tenants/{tenant}/suspender', [TenantManageController::class, 'suspender']);
        Route::put('/tenants/{tenant}/reactivar', [TenantManageController::class, 'reactivar']);
        Route::post('/tenants/{tenant}/impersonar', [TenantManageController::class, 'impersonar']);

        // Billing
        Route::get('/billing/suscripciones', [BillingController::class, 'suscripciones']);
        Route::get('/billing/pagos', [BillingController::class, 'historialPagos']);
    });
});
