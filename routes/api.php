<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Central\CentralAuthController;
use App\Http\Controllers\Central\TenantManageController;
use App\Http\Controllers\Central\MetricsController;
use App\Http\Controllers\Central\BillingController;

// Central Billing & Modulos Endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
            return $request->user();
        }
        );

        Route::group(['prefix' => 'central/plan'], function () {
            Route::get('/modulos', [\App\Http\Controllers\Central\ModuloPlanController::class , 'index']);
            Route::put('/modulos/{id}', [\App\Http\Controllers\Central\ModuloPlanController::class , 'update']);
            Route::get('/modulos/{id}/impacto', [\App\Http\Controllers\Central\ModuloPlanController::class , 'impacto']);
        }
        );
    });

// --- END CENTRAL ROUTES (Moved to routes/central.php) ---

// Spider QA endpoints (H24) — use auth:sanctum for proper Bearer token protection
Route::middleware('auth:sanctum')->prefix('spider')->group(function () {
    // Route::get('/probe', ...); // @deprecated Spider v4 — browser-side fetch replaces server-side probe
    Route::get('/tenants', [\App\Http\Controllers\Central\SpiderController::class , 'listTenants']);
    Route::get('/db-check', [\App\Http\Controllers\Central\SpiderController::class , 'dbCheck']);
    Route::post('/sync', [\App\Http\Controllers\Central\SpiderController::class , 'syncTests']);
    Route::get('/tests', [\App\Http\Controllers\Central\SpiderController::class , 'getTests']);
    Route::post('/tests', [\App\Http\Controllers\Central\SpiderController::class , 'saveTests']);
});

// Spider QA endpoints (auth:sanctum)
Route::middleware('auth:sanctum')->prefix('spider')->group(function () {
    Route::get('/tenants', [SpiderController::class , 'listTenants']);
    Route::get('/db-check', [\App\Http\Controllers\Central\SpiderController::class , 'dbCheck']);
    Route::post('/sync', [\App\Http\Controllers\Central\SpiderController::class , 'syncTests']);
    Route::get('/tests', [\App\Http\Controllers\Central\SpiderController::class , 'getTests']);
    Route::post('/tests', [\App\Http\Controllers\Central\SpiderController::class , 'saveTests']);

    // NUEVO: Endpoint para obtener tenant slug recomendado
    Route::get('/tenant-slug', [\App\Http\Controllers\Central\SpiderController::class , 'getTenantSlug']);
});