<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\BotConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigBotController extends Controller
{
    /**
     * URL base del bot Node.js — desde .env
     * En dev local: http://localhost:3001
     * En producción: http://benderandos-bot:3001 o URL pública
     */
    private function botUrl(): string
    {
        return rtrim(env('BOT_API_URL', 'http://localhost:3001'), '/');
    }

    private function botHeaders(): array
    {
        return [
            'X-Bot-Token' => env('BOT_INTERNAL_SECRET', ''),
            'Accept'      => 'application/json',
        ];
    }

    /**
     * GET /api/bot/config
     * Devuelve config local (BotConfig DB) + status real del bot Node.js
     */
    public function getBotConfig(): JsonResponse
    {
        // Config local en DB del tenant
        $config = BotConfig::first() ?: BotConfig::create([
            'nombre_bot'   => 'BenderAndos',
            'personalidad' => 'formal',
            'activo'       => true,
        ]);

        // Status real del bot Node.js
        $botStatus = null;
        $llmConfig = null;
        try {
            $statusRes = Http::timeout(3)
                ->withHeaders($this->botHeaders())
                ->get($this->botUrl() . '/bot/status');

            $configRes = Http::timeout(3)
                ->withHeaders($this->botHeaders())
                ->get($this->botUrl() . '/bot/config');

            if ($statusRes->successful()) $botStatus = $statusRes->json();
            if ($configRes->successful()) $llmConfig = $configRes->json();
        } catch (\Exception $e) {
            Log::warning('Bot Node.js no disponible: ' . $e->getMessage());
        }

        return response()->json([
            'config'     => $config,
            'bot_status' => $botStatus,
            'llm_config' => $llmConfig,
            'bot_online' => $botStatus !== null,
        ]);
    }

    /**
     * PUT /api/bot/config
     * Actualiza config local del tenant
     */
    public function updateBotConfig(Request $request): JsonResponse
    {
        $config = BotConfig::firstOrCreate(
            [],
            ['nombre_bot' => 'BenderAndos', 'personalidad' => 'formal', 'activo' => true]
        );

        $config->update($request->only([
            'nombre_bot',
            'personalidad',
            'activo',
            'horario_atencion',
            'intenciones_activas',
            'faq',
            'mensaje_bienvenida',
            'mensaje_fuera_horario',
        ]));

        return response()->json([
            'message' => 'Configuración actualizada',
            'config'  => $config,
        ]);
    }

    /**
     * GET /api/bot/logs
     * Logs reales desde el bot Node.js
     */
    public function getLogs(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 20), 100);

        try {
            $res = Http::timeout(5)
                ->withHeaders($this->botHeaders())
                ->get($this->botUrl() . '/bot/logs', ['limit' => $limit]);

            if ($res->successful()) {
                return response()->json($res->json());
            }

            return response()->json(['logs' => [], 'error' => 'Bot returned ' . $res->status()]);
        } catch (\Exception $e) {
            Log::warning('Bot logs no disponibles: ' . $e->getMessage());
            return response()->json(['logs' => [], 'error' => 'Bot no disponible']);
        }
    }

    /**
     * GET /api/bot/test-connection
     * Prueba la conexión al bot — para el botón "Probar Conexión" de la UI
     */
    public function testConnection(): JsonResponse
    {
        try {
            $res = Http::timeout(3)
                ->withHeaders($this->botHeaders())
                ->get($this->botUrl() . '/bot/status');

            if ($res->successful()) {
                $data = $res->json();
                return response()->json([
                    'success' => true,
                    'message' => "Bot online · LLM: {$data['llm_provider']} ({$data['llm_model']}) · Uptime: {$data['uptime']}s",
                    'data'    => $data,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Bot respondió con error ' . $res->status(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar al bot: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /webhook/wa/config
     * Webhook para configuración vía WhatsApp
     */
    public function webhookConfig(Request $request): JsonResponse
    {
        $intent = $request->input('intent');
        $preset = $request->input('preset');
        $token  = $request->header('X-Bot-Token');

        Log::info("WA Webhook Config: intent={$intent}, preset={$preset}");

        if ($intent === 'cambiar_rubro' && $preset) {
            $rubroCtrl = new ConfigRubroController();
            return $rubroCtrl->aplicarPreset($preset);
        }

        return response()->json(['message' => 'Evento recibido', 'status' => 'ok']);
    }
}