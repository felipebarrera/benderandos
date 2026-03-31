<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Cita;
use App\Models\Tenant\Usuario;
use App\Services\Tenant\AgendaLabelService;
use Illuminate\Http\Request;

class AgendaUniversalController extends Controller
{
    /**
     * Listar citas (Filtrado por Global Scope automáticamente)
     */
    public function index(Request $request)
    {
        $request->validate([
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
        ]);

        $query = Cita::with(['cliente', 'medico']);

        if ($request->fecha_desde) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }
        if ($request->fecha_hasta) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        $citas = $query->orderBy('fecha')->orderBy('hora_inicio')->get();

        return response()->json([
            'citas' => $citas,
            'labels' => AgendaLabelService::getLabels('medico')
        ]);
    }

    /**
     * Crear una cita
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'medico_id' => 'required|exists:users,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'producto_id' => 'nullable',
            'observaciones_recepcion' => 'nullable|string'
        ]);

        $cita = Cita::create($data);

        return response()->json([
            'success' => true,
            'cita' => $cita
        ]);
    }

    /**
     * Listar profesionales (configurados en profesionales_config)
     */
    public function profesionales()
    {
        // Solo usuarios con rol admin o operario que tengan config profesional
        $profesionales = Usuario::whereIn('rol', ['admin', 'operario'])
            ->with('profesionalConfig')
            ->get();

        return response()->json($profesionales);
    }
}
