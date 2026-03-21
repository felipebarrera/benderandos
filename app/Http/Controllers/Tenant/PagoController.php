<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Venta;
use App\Models\Tenant\TipoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Transbank\Webpay\WebpayPlus\Transaction;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    /**
     * Iniciar una transacción de WebPay Plus
     */
    public function iniciarPago(Venta $venta)
    {
        // En un flujo real, usaríamos las credenciales de comercio.
        // Aquí usaremos el entorno de integración por defecto del SDK si no hay config.
        
        try {
            $transaction = new Transaction();
            $response = $transaction->create(
                $venta->uuid, // Buy order
                session()->getId(), // Session ID
                (int) $venta->total, // Amount
                route('portal.pago.retorno', ['venta' => $venta->uuid]) // Return URL
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'url'   => $response->getUrl(),
                    'token' => $response->getToken(),
                ]);
            }

            return view('tenant.portal.pago_redirect', [
                'url'   => $response->getUrl(),
                'token' => $response->getToken(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al iniciar pago WebPay: ' . $e->getMessage());
            return response()->json(['message' => 'No se pudo iniciar la transacción de pago'], 500);
        }
    }

    /**
     * Procesar el retorno de Transbank
     */
    public function retornoPago(Request $request, string $uuid)
    {
        $token = $request->input('token_ws');

        if (!$token) {
            return redirect()->route('portal.historial')->with('error', 'Pago cancelado por el usuario.');
        }

        try {
            $transaction = new Transaction();
            $result = $transaction->commit($token);

            if ($result->isApproved()) {
                $venta = Venta::where('uuid', $uuid)->firstOrFail();
                
                // Confirmar la venta y marcar como pagada
                $venta->update([
                    'estado' => 'pagada', // O el estado que corresponda post-pago
                    'tipo_pago_id' => 3, // Asumiendo 3 = WebPay / Online
                    'pagado_at' => now(),
                    'notas' => 'Pago aprobado por WebPay. AuthCode: ' . $result->getAuthorizationCode()
                ]);

                return redirect()->route('portal.historial')->with('success', '¡Pago realizado con éxito!');
            } else {
                return redirect()->route('portal.historial')->with('error', 'El pago fue rechazado.');
            }
        } catch (\Exception $e) {
            Log::error('Error al confirmar pago WebPay: ' . $e->getMessage());
            return redirect()->route('portal.historial')->with('error', 'Error al procesar la confirmación del pago.');
        }
    }
}
