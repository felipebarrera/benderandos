<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\TenantOnboardingService;
use App\Models\Central\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private TenantOnboardingService $onboardingService
    ) {}

    /**
     * Endpoint para validar si el nombre/slug está disponible.
     * GET /webhook/whatsapp/check-slug?nombre=Ferretería...
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $request->validate(['nombre' => 'required|string|max:255']);
        $slug = Str::slug($request->query('nombre'));

        $disponible = ! Tenant::where('id', $slug)->exists();

        return response()->json([
            'disponible' => $disponible,
            'slug_sugerido' => $disponible ? $slug : $slug . '-' . Str::random(4),
        ]);
    }
    /**
     * Endpoint central para que el bot registre a un usuario (Onboarding).
     */
    public function onboarding(Request $request): JsonResponse
    {
        $data = $request->validate([
            'step'           => 'required|in:complete',
            'nombre_empresa' => 'required|string|max:255',
            'rubro'          => 'required|string',
            'rut_empresa'    => 'nullable|string',
            'whatsapp_admin' => 'required|string',
            'email_admin'    => 'required|email',
            'password_admin' => 'required|string',
            'nombre_admin'   => 'required|string',
        ]);

        try {
            $resultado = $this->onboardingService->crear($data);
            return response()->json($resultado, 201);
        } catch (\Exception $e) {
            // Manejar validación específica si el slug no está disponible (ejemplo 422 manual en servicio)
            if ($e->getCode() == 422) {
                return response()->json(json_decode($e->getMessage(), true), 422);
            }
            throw $e;
        }
    }
}
