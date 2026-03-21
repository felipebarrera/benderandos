<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CampanaMarketing;
use App\Models\Tenant\EscaneoQr;
use App\Services\MarketingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketingController extends Controller
{
    public function __construct(
        private MarketingService $marketingService
    ) {}

    // =================== DASHBOARD ===================

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'kpis' => $this->marketingService->getDashboard(),
        ]);
    }

    // =================== CAMPAÑAS ===================

    public function campanasIndex(Request $request): JsonResponse
    {
        $query = CampanaMarketing::withCount('qrs')->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 25)));
    }

    public function campanaStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'           => 'required|string|max:255',
            'descripcion'      => 'nullable|string',
            'tipo_accion'      => 'required|string|in:descuento_porcentaje,descuento_fijo,dos_por_uno,abrir_whatsapp,encuesta,link_externo',
            'valor_descuento'  => 'nullable|integer|min:0',
            'link_destino'     => 'nullable|url|max:255',
            'mensaje_whatsapp' => 'nullable|string',
            'fecha_inicio'     => 'required|date',
            'fecha_fin'        => 'nullable|date|after_or_equal:fecha_inicio',
            'estado'           => 'nullable|string|in:activa,pausada,finalizada',
            'limite_usos'      => 'nullable|integer|min:1',
            'codigo_pos'       => 'nullable|string|max:50|unique:campanas_marketing,codigo_pos',
        ]);

        if (empty($data['codigo_pos']) && in_array($data['tipo_accion'], ['descuento_porcentaje', 'descuento_fijo', 'dos_por_uno'])) {
            $data['codigo_pos'] = strtoupper(Str::random(6));
        }

        $campana = CampanaMarketing::create($data);

        // Auto-generar el primer QR genérico para la campaña
        $this->marketingService->crearQr($campana, 'General');

        return response()->json(['message' => 'Campaña creada', 'campana' => $campana], 201);
    }

    public function campanaShow($id): JsonResponse
    {
        $campana = CampanaMarketing::with(['qrs.escaneos'])->findOrFail($id);
        return response()->json($campana);
    }

    public function generarQr(Request $request, int $id): JsonResponse
    {
        $request->validate(['ubicacion_fisica' => 'nullable|string|max:255']);
        $campana = CampanaMarketing::findOrFail($id);

        $qr = $this->marketingService->crearQr($campana, $request->ubicacion_fisica);

        return response()->json(['message' => 'QR generado', 'qr' => $qr], 201);
    }

    public function metricasEscaneos(Request $request): JsonResponse
    {
        // Traer últimos escaneos
        $escaneos = EscaneoQr::with(['qr.campana', 'venta'])
            ->orderByDesc('fecha_escaneo')
            ->paginate(50);

        return response()->json($escaneos);
    }
}
