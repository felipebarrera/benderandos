<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ProfesionalConfig;
use App\Models\Tenant\Usuario;
use Illuminate\Http\Request;

class ProfesionalConfigController extends Controller
{
    /**
     * Listar configuraciones profesionales
     */
    public function index()
    {
        // Solo para admin
        $configs = ProfesionalConfig::with('usuario')->get();
        return response()->json($configs);
    }

    /**
     * Crear o actualizar config de profesional
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => 'required|exists:users,id',
            'especialidad' => 'required|string',
            'titulo_prefijo' => 'nullable|string',
            'color' => 'nullable|string',
            'duracion_cita_minutos' => 'nullable|integer',
            'horario_json' => 'nullable|array',
            'visible_web' => 'nullable|boolean',
            'permiso_notas_cifradas' => 'nullable|boolean',
            'permiso_ver_solo_agenda' => 'nullable|boolean'
        ]);

        $config = ProfesionalConfig::updateOrCreate(
            ['usuario_id' => $data['usuario_id']],
            $data
        );

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    public function show($id)
    {
        $config = ProfesionalConfig::where('usuario_id', $id)->firstOrFail();
        return response()->json($config);
    }
}
