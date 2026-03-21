<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\SaasActividad;
use App\Models\Tenant\SaasDemo;
use App\Models\Tenant\SaasPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaasPipelineController extends Controller
{
    /**
     * Devuelve todos los prospectos, agrupados o listados
     */
    public function index(Request $request): JsonResponse
    {
        $query = SaasPipeline::with('plan', 'ejecutivo')
            ->orderByRaw("
                CASE etapa 
                    WHEN 'nuevo' THEN 1 
                    WHEN 'contactado' THEN 2 
                    WHEN 'demo_agendada' THEN 3 
                    WHEN 'demo_hecha' THEN 4 
                    WHEN 'propuesta' THEN 5 
                    WHEN 'negociacion' THEN 6 
                    ELSE 7 END
            ")
            ->orderBy('id', 'desc');

        if ($request->filled('etapa')) {
            $query->where('etapa', $request->etapa);
        }

        return response()->json($query->paginate($request->input('per_page', 50)));
    }

    /**
     * Crear un nuevo prospecto en etapa 'nuevo'
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'razon_social'      => 'required|string|max:255',
            'contacto_nombre'   => 'required|string|max:255',
            'contacto_whatsapp' => 'required|string|max:20',
            'industria'         => 'required|string|max:100',
            'plan_interes'      => 'nullable|exists:saas_planes,id',
            'ejecutivo_id'      => 'nullable|exists:users,id',
            'origen'            => 'nullable|string'
        ]);

        // Si no envía ejecutivo, se auto-asigna al usuario autenticado
        $data['ejecutivo_id'] = $data['ejecutivo_id'] ?? $request->user()->id;
        $data['etapa'] = 'nuevo';
        
        $prospecto = SaasPipeline::create($data);

        return response()->json(['message' => 'Prospecto creado', 'prospecto' => $prospecto], 201);
    }

    /**
     * Mover a etapa (kanban drag & drop api endpoint)
     */
    public function moverEtapa(Request $request, int $id): JsonResponse
    {
        $request->validate(['etapa' => 'required|string']);
        
        $prospecto = SaasPipeline::findOrFail($id);
        $prospecto->update(['etapa' => $request->etapa]);

        // Guardar actividad CRM
        $prospecto->actividades()->create([
            'tipo' => 'nota',
            'descripcion' => "Movido a etapa: " . $request->etapa,
            'ejecutivo_id' => $request->user()->id,
            'fecha_actividad' => now()
        ]);

        return response()->json(['message' => 'Prospecto movido', 'prospecto' => $prospecto]);
    }

    /**
     * Agendar DEMO comercial
     */
    public function agendarDemo(Request $request, int $id): JsonResponse
    {
        $prospecto = SaasPipeline::findOrFail($id);
        
        $data = $request->validate([
            'fecha'        => 'required|date',
            'hora'         => 'required',
            'modalidad'    => 'nullable|string',
            'link_reunion' => 'nullable|string',
            'duracion_min' => 'nullable|integer'
        ]);

        $data['ejecutivo_id'] = $request->user()->id;

        $demo = $prospecto->demos()->create($data);
        
        // Mover a etapa demo agendada
        $prospecto->update(['etapa' => 'demo_agendada']);

        // CRM log
        $prospecto->actividades()->create([
            'tipo' => 'demo',
            'descripcion' => "Demo agendada para " . $data['fecha'] . " a las " . $data['hora'],
            'ejecutivo_id' => $request->user()->id,
            'fecha_actividad' => now()
        ]);

        return response()->json(['message' => 'Demo agendada', 'demo' => $demo]);
    }

    /**
     * Registrar una actividad manual en el hilo del prospecto (llamada, correo)
     */
    public function registrarActividad(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|string',
            'descripcion' => 'required|string'
        ]);

        $prospecto = SaasPipeline::findOrFail($id);
        $actividad = $prospecto->actividades()->create([
            'tipo'            => $request->tipo,
            'descripcion'     => $request->descripcion,
            'ejecutivo_id'    => $request->user()->id,
            'fecha_actividad' => now()
        ]);

        return response()->json(['message' => 'Actividad registrada', 'actividad' => $actividad]);
    }
}
