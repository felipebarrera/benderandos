<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Asistencia;
use App\Models\Tenant\Empleado;
use App\Models\Tenant\Liquidacion;
use App\Models\Tenant\Permiso;
use App\Models\Tenant\Vacacion;
use App\Services\RrhhService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RrhhController extends Controller
{
    public function __construct(
        private RrhhService $rrhhService
    ) {}

    // =================== DASHBOARD ===================

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'kpis' => $this->rrhhService->getDashboard(),
        ]);
    }

    // =================== EMPLEADOS ===================

    public function empleadosIndex(Request $request): JsonResponse
    {
        $query = Empleado::query()->orderBy('nombre');
        if ($request->boolean('solo_activos', true)) {
            $query->activos();
        }
        return response()->json($query->paginate($request->input('per_page', 30)));
    }

    public function empleadoStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'          => 'required|string|max:255',
            'rut'             => 'nullable|string|max:12',
            'fecha_ingreso'   => 'required|date',
            'cargo'           => 'nullable|string|max:255',
            'tipo_contrato'   => 'nullable|string|in:indefinido,plazo_fijo,honorarios,part_time',
            'sueldo_base'     => 'required|integer|min:0',
            'afp'             => 'nullable|string|max:50',
            'afp_pct'         => 'nullable|numeric|min:0|max:20',
            'salud'           => 'nullable|string|max:50',
            'salud_tipo'      => 'nullable|string|in:fonasa,isapre',
            'salud_pct'       => 'nullable|numeric|min:0|max:20',
            'mutual'          => 'nullable|boolean',
            'horario'         => 'nullable|string|max:50',
            'telefono'        => 'nullable|string|max:20',
            'email'           => 'nullable|email',
            'direccion'       => 'nullable|string',
        ]);

        return response()->json(Empleado::create($data), 201);
    }

    public function empleadoUpdate(Request $request, int $id): JsonResponse
    {
        $emp = Empleado::findOrFail($id);
        $emp->update($request->only([
            'nombre', 'rut', 'cargo', 'tipo_contrato', 'sueldo_base',
            'afp', 'afp_pct', 'salud', 'salud_tipo', 'salud_pct',
            'mutual', 'mutual_pct', 'horario', 'telefono', 'email',
            'direccion', 'comuna', 'activo', 'fecha_termino',
        ]));
        return response()->json($emp->fresh());
    }

    // =================== ASISTENCIA ===================

    public function marcarEntrada(Request $request): JsonResponse
    {
        $request->validate(['empleado_id' => 'required|integer|exists:empleados,id']);
        $empleado = Empleado::findOrFail($request->empleado_id);
        $asistencia = $this->rrhhService->marcarEntrada($empleado);
        return response()->json([
            'message'    => "Entrada registrada para {$empleado->nombre}",
            'asistencia' => $asistencia,
        ]);
    }

    public function marcarSalida(Request $request): JsonResponse
    {
        $request->validate(['empleado_id' => 'required|integer|exists:empleados,id']);
        $empleado = Empleado::findOrFail($request->empleado_id);
        try {
            $asistencia = $this->rrhhService->marcarSalida($empleado);
            return response()->json([
                'message'    => "Salida registrada: {$asistencia->horas_trabajadas}h",
                'asistencia' => $asistencia,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'No se encontró registro de entrada hoy'], 422);
        }
    }

    public function asistenciaHoy(): JsonResponse
    {
        return response()->json(
            Asistencia::with('empleado')
                ->whereDate('fecha', today())
                ->orderBy('hora_entrada')
                ->get()
        );
    }

    // =================== VACACIONES ===================

    public function vacacionesIndex(Request $request): JsonResponse
    {
        $query = Vacacion::with('empleado')->orderByDesc('created_at');
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        return response()->json($query->paginate(25));
    }

    public function solicitarVacacion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'empleado_id'   => 'required|integer|exists:empleados,id',
            'fecha_inicio'  => 'required|date|after:today',
            'fecha_fin'     => 'required|date|after:fecha_inicio',
            'motivo'        => 'nullable|string',
        ]);

        $inicio = \Carbon\Carbon::parse($data['fecha_inicio']);
        $fin = \Carbon\Carbon::parse($data['fecha_fin']);
        $data['dias_solicitados'] = $inicio->diffInWeekdays($fin) + 1;

        $vac = Vacacion::create($data);
        return response()->json(['message' => 'Solicitud creada', 'vacacion' => $vac->load('empleado')], 201);
    }

    public function resolverVacacion(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estado' => 'required|string|in:aprobada,rechazada',
            'motivo' => 'nullable|string',
        ]);

        $vac = Vacacion::with('empleado')->findOrFail($id);
        $vac = $this->rrhhService->resolverVacacion($vac, $request->estado, $request->user()->id, $request->motivo);
        return response()->json(['message' => "Vacación {$request->estado}", 'vacacion' => $vac]);
    }

    // =================== PERMISOS ===================

    public function solicitarPermiso(Request $request): JsonResponse
    {
        $data = $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'fecha'       => 'required|date',
            'tipo'        => 'required|string|in:medico,personal,administrativo,duelo,otro',
            'horas'       => 'nullable|integer|min:1|max:8',
            'con_goce'    => 'nullable|boolean',
            'motivo'      => 'nullable|string',
        ]);
        return response()->json(Permiso::create($data), 201);
    }

    public function resolverPermiso(Request $request, int $id): JsonResponse
    {
        $permiso = Permiso::findOrFail($id);
        $permiso->update([
            'estado'       => $request->input('estado'),
            'aprobado_por' => $request->user()->id,
        ]);
        return response()->json($permiso->fresh());
    }

    // =================== LIQUIDACIONES ===================

    public function generarLiquidacion(Request $request): JsonResponse
    {
        $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'anio'        => 'required|integer|min:2020',
            'mes'         => 'required|integer|min:1|max:12',
        ]);

        $empleado = Empleado::findOrFail($request->empleado_id);
        $liq = $this->rrhhService->generarLiquidacion($empleado, $request->anio, $request->mes);
        return response()->json(['message' => 'Liquidación generada', 'liquidacion' => $liq->load('empleado')], 201);
    }

    public function liquidacionesIndex(Request $request): JsonResponse
    {
        $query = Liquidacion::with('empleado')->orderByDesc('anio')->orderByDesc('mes');
        if ($request->filled('empleado_id')) {
            $query->where('empleado_id', $request->empleado_id);
        }
        return response()->json($query->paginate(25));
    }

    public function generarMasivo(Request $request): JsonResponse
    {
        $request->validate([
            'anio' => 'required|integer',
            'mes'  => 'required|integer|min:1|max:12',
        ]);

        $empleados = Empleado::activos()->get();
        $generadas = 0;

        foreach ($empleados as $emp) {
            $this->rrhhService->generarLiquidacion($emp, $request->anio, $request->mes);
            $generadas++;
        }

        return response()->json(['message' => "{$generadas} liquidaciones generadas"]);
    }
}
