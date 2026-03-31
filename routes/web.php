<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Central\WhatsAppWebhookController;
use App\Http\Controllers\Central\CentralAuthController;
use App\Http\Controllers\Central\MetricsController;
use App\Http\Controllers\Central\TenantManageController;
use App\Http\Controllers\Central\BillingController;
use App\Http\Controllers\Central\SpiderController;

// Root route — redirect to central login
// Root route — redirect to central login ONLY on central domains
$centralDomains = config('tenancy.central_domains', ['localhost', '127.0.0.1']);
foreach ($centralDomains as $domain) {
    Route::domain($domain)->get('/', fn() => redirect('/central/login'));
}




// Endpoint central de Webhook que el bot llama para dar de alta clientes
Route::post('/webhook/whatsapp/onboarding', [WhatsAppWebhookController::class , 'onboarding']);

// --- REDIRECTS OLD SUPERADMIN ---
Route::redirect('/superadmin', '/central');
Route::redirect('/superadmin/spider', '/central/spider');

// --- PANEL CENTRAL (MIGRADO SUPERADMIN) ---
Route::group(['prefix' => 'central', 'as' => 'central.'], function () {
    Route::get('/login', [CentralAuthController::class , 'showLogin'])->name('login_page');
    Route::post('/login', [CentralAuthController::class , 'loginWeb'])->name('login.post');
    Route::post('/logout', [CentralAuthController::class , 'logoutWeb'])->name('logout');

    Route::middleware(['auth:super_admin'])->group(function () {
            Route::get('/', [MetricsController::class , 'dashboard'])->name('dashboard');

            Route::get('/tenants', [TenantManageController::class , 'indexWeb'])->name('tenants.index');
            Route::get('/tenants/{tenant}/usuarios', [TenantManageController::class , 'usuarios'])->name('tenants.usuarios');
            Route::put('/tenants/{tenant}/suspender', [TenantManageController::class , 'suspenderWeb'])->name('tenants.suspender');
            Route::put('/tenants/{tenant}/reactivar', [TenantManageController::class , 'reactivarWeb'])->name('tenants.reactivar');
            Route::post('/tenants/{tenant}/impersonar', [TenantManageController::class , 'impersonar'])->name('tenants.impersonar');

            Route::get('/billing', [BillingController::class , 'indexWeb'])->name('billing.index');
            Route::post('/billing/planes', [BillingController::class , 'planStore'])->name('billing.plan.store');
            Route::put('/billing/planes/{id}', [BillingController::class , 'planUpdate'])->name('billing.plan.update');
            Route::delete('/billing/planes/{id}', [BillingController::class , 'planDestroy'])->name('billing.plan.destroy');

            Route::post('/billing/subscription/{id}/activar', [BillingController::class , 'activar'])->name('billing.activar');
            Route::post('/billing/subscription/{id}/suspender', [BillingController::class , 'suspender'])->name('billing.suspender');
            Route::post('/billing/subscription', [BillingController::class , 'subscriptionStore'])->name('billing.subscription.store');
            Route::put('/billing/subscription/{id}', [BillingController::class , 'subscriptionUpdate'])->name('billing.subscription.update');

            Route::get('/planes', [BillingController::class , 'planesIndex'])->name('planes.index');
            Route::get('/modulos', [BillingController::class , 'modulosIndex'])->name('modulos.index');
            Route::post('/billing/modulos', [BillingController::class , 'moduloStore'])->name('billing.modulo.store');
            Route::put('/billing/modulos/{id}', [BillingController::class , 'moduloUpdate'])->name('billing.modulo.update');
            Route::delete('/billing/modulos/{id}', [BillingController::class , 'moduloDestroy'])->name('billing.modulo.destroy');
            Route::get('/spider', [SpiderController::class , 'index'])->name('central.spider');
            Route::post('/spider/token', [SpiderController::class , 'generateToken'])->name('spider.token');
        }
        );
    });

// Alias for Laravel's default 'login' route
Route::get('/login', [App\Http\Controllers\Central\CentralAuthController::class , 'showLogin'])->name('login');