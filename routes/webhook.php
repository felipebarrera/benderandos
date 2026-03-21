<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Central\WhatsAppWebhookController;
use App\Http\Controllers\Central\WhatsAppPedidoController;

// Simple custom auth middleware protecting integration webhooks
$webhookAuth = function (Request $request, \Closure $next) {
    if ($request->header('X-Bot-Token') !== config('services.whatsapp_bot.token')) {
        abort(401, 'Invalid Bot Token');
    }
    return $next($request);
};

Route::group(['prefix' => 'webhook/whatsapp', 'middleware' => [$webhookAuth]], function () {
    // Onboarding
    Route::get('check-slug', [WhatsAppWebhookController::class, 'checkSlug']);
    Route::post('onboarding', [WhatsAppWebhookController::class, 'onboarding']);
    
    // Pedidos (Manejados globalmente aunque pertenezcan a un tenant)
    Route::post('pedido-remoto', [WhatsAppPedidoController::class, 'crear']);
    Route::get('pedido/{uuid}', [WhatsAppPedidoController::class, 'estado']);
});
