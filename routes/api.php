<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Central\CentralAuthController;
use App\Http\Controllers\Central\TenantManageController;
use App\Http\Controllers\Central\MetricsController;
use App\Http\Controllers\Central\BillingController;
use App\Http\Controllers\Central\SpiderController;
use App\Http\Controllers\Central\ModuloPlanController;
use App\Http\Controllers\Api\Internal\Bot\TenantPhoneController;
use App\Http\Controllers\Api\Internal\Bot\CustomerLookupController;

// Central Billing & Modulos Endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
            return $request->user();
        }
        );

        Route::group(['prefix' => 'central/plan'], function () {
            Route::get('/modulos', [ModuloPlanController::class , 'index']);
            Route::put('/modulos/{id}', [ModuloPlanController::class , 'update']);
            Route::get('/modulos/{id}/impacto', [ModuloPlanController::class , 'impacto']);
        }
        );
    });

// Spider QA endpoints
Route::middleware('auth:sanctum')->prefix('spider')->group(function () {
    Route::get('/tenants', [SpiderController::class , 'listTenants']);
    Route::get('/db-check', [SpiderController::class , 'dbCheck']);
    Route::post('/sync', [SpiderController::class , 'syncTests']);
    Route::get('/tests', [SpiderController::class , 'getTests']);
    Route::post('/tests', [SpiderController::class , 'saveTests']);
    Route::get('/tenant-slug', [SpiderController::class , 'getTenantSlug']);
});

// === RUTAS INTERNAS PARA BOT WHATSAPP ===
// Solo usa auth.bot (sin sanctum) - valida X-Bot-Token
Route::middleware(['auth.bot'])
    ->prefix('internal/bot')
    ->group(function () {
        Route::get('/tenant-by-phone/{phone}', [TenantPhoneController::class , 'findByPhone'])
            ->name('internal.bot.tenant-by-phone');

        Route::get('/cliente/{phone}', [CustomerLookupController::class , 'findByPhone'])
            ->name('internal.bot.customer-by-phone')
            ->middleware('tenancy.initialize');
    });