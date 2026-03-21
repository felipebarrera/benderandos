<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\OfertaEmpleo;
use App\Models\Tenant\Postulacion;
use App\Models\Tenant\Entrevista;
use App\Services\ReclutamientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReclutamientoController extends Controller
{
    public function __construct(
        private ReclutamientoService $reclutaService
    ) {}

    // =================== DASHBOARD ===================

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'kpis' => $this->reclutaService->getDashboard(),
        ]);
    }

    // =================== OFERTAS ===================

    public function ofertasIndex(Request $request): JsonResponse
    {
        $query = OfertaEmpleo::withCount(['postulaciones as postulantes_activos' => function ($query) {
            $query->whereNotIn('estado', ['descartada', 'contratada']);
        }])->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 25)));
    }

    public function ofertaStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'titulo'         => 'required|string|max:255',
            'descripcion'    => 'required|string',
            'cargo'          => 'nullable|string|max:255',
            'departamento'   => 'nullable|string|max:255',
            'ubicacion'      => 'nullable|string|max:255',
            'modalidad'      => 'nullable|string|in:presencial,remoto,hibrido',
            'jornada'        => 'nullable|string|in:completa,media,part_time,freelance',
            'sueldo_min'     => 'nullable|integer|min:0',
            'sueldo_max'     => 'nullable|integer|min:0',
            'mostrar_sueldo' => 'nullable|boolean',
            'requisitos'     => 'nullable|string',
            'beneficios'     => 'nullable|string',
            'estado'         => 'nullable|string|in:borrador,publicada,pausada,cerrada',
            'fecha_cierre'   => 'nullable|date',
        ]);

        return response()->json(OfertaEmpleo::create($data), 201);
    }

    public function ofertaUpdate(Request $request, int $id): JsonResponse
    {
        $oferta = OfertaEmpleo::findOrFail($id);
        $oferta->update($request->all());
        return response()->json($oferta->fresh());
    }

    // =================== POSTULACIONES (Admin) ===================

    public function postulacionesIndex(Request $request): JsonResponse
    {
        $query = Postulacion::with('oferta')->orderByDesc('created_at');
        
        if ($request->filled('oferta_id')) {
            $query->where('oferta_id', $request->oferta_id);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 50)));
    }

    public function postulacionShow($id): JsonResponse
    {
        return response()->json(
            Postulacion::with(['oferta', 'entrevistas.entrevistador'])->findOrFail($id)
        );
    }

    public function moverPipeline(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estado' => 'required|string|in:recibida,preseleccionada,entrevista,evaluacion,oferta,contratada,descartada',
            'notas'  => 'nullable|string',
        ]);

        $postulacion = Postulacion::with('oferta')->findOrFail($id);

        try {
            $postulacion = $this->reclutaService->moverPipeline($postulacion, $request->estado, $request->notas);
            return response()->json(['message' => 'Estado actualizado', 'postulacion' => $postulacion]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function programarEntrevista(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'fecha_hora' => 'required|date|after:now',
            'tipo'       => 'required|string|in:presencial,telefonica,video',
            'lugar'      => 'nullable|string|max:255',
            'link_video' => 'nullable|url|max:255',
        ]);

        $postulacion = Postulacion::with('oferta')->findOrFail($id);
        $entrevista = $this->reclutaService->programarEntrevista($postulacion, $data, $request->user()->id);

        return response()->json(['message' => 'Entrevista programada', 'entrevista' => $entrevista], 201);
    }

    // =================== PÚBLICO (Candidatos) ===================

    public function publicOfertas(): JsonResponse
    {
        return response()->json(
            OfertaEmpleo::publicadas()
                ->select('id', 'titulo', 'slug', 'descripcion', 'cargo', 'departamento', 'ubicacion', 'modalidad', 'jornada', 'requisitos', 'beneficios', 'created_at')
                ->latest()
                ->get()
        );
    }

    public function publicOfertaBySlug(string $slug): JsonResponse
    {
        return response()->json(
            OfertaEmpleo::publicadas()->where('slug', $slug)->firstOrFail()
        );
    }

    public function postular(Request $request, string $slug): JsonResponse
    {
        $oferta = OfertaEmpleo::publicadas()->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'nombre'              => 'required|string|max:255',
            'email'               => 'required|email|max:255',
            'telefono'            => 'required|string|max:20',
            'rut'                 => 'nullable|string|max:12',
            'mensaje'             => 'nullable|string',
            'pretension_salarial' => 'nullable|integer',
            'cv'                  => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB
        ]);

        if ($request->hasFile('cv')) {
            // Guardar en disco local/S3. Simulado:
            // $path = $request->file('cv')->store('cvs', 'public');
            $data['cv_path'] = 'cvs/' . $request->file('cv')->hashName(); 
        }

        $postulacion = $this->reclutaService->nuevaPostulacion($oferta, $data);

        return response()->json([
            'message' => 'Postulación enviada exitosamente. Te hemos enviado un mensaje de confirmación.',
            'id'      => $postulacion->id,
        ], 201);
    }
}
