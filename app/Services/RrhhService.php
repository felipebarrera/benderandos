<?php

namespace App\Services;

use App\Models\Tenant\Asistencia;
use App\Models\Tenant\Empleado;
use App\Models\Tenant\Liquidacion;
use App\Models\Tenant\Vacacion;
use App\Models\Tenant\Permiso;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RrhhService
{
    /**
     * Marcar entrada
     */
    public function marcarEntrada(Empleado $empleado): Asistencia
    {
        $hoy = today();
        $ahora = now()->format('H:i:s');

        $asistencia = Asistencia::firstOrCreate(
            ['empleado_id' => $empleado->id, 'fecha' => $hoy],
            ['hora_entrada' => $ahora]
        );

        if ($asistencia->wasRecentlyCreated) {
            // Calcular atraso
            $horaEntrada = Carbon::parse($empleado->horario_entrada);
            $horaReal = Carbon::parse($ahora);
            $atraso = max(0, $horaReal->diffInMinutes($horaEntrada, false) * -1);
            $asistencia->update(['minutos_atraso' => $atraso]);
        }

        return $asistencia;
    }

    /**
     * Marcar salida
     */
    public function marcarSalida(Empleado $empleado): Asistencia
    {
        $asistencia = Asistencia::where('empleado_id', $empleado->id)
            ->where('fecha', today())
            ->firstOrFail();

        $ahora = now()->format('H:i:s');
        $horaEntrada = Carbon::parse($asistencia->hora_entrada);
        $horaSalida = Carbon::parse($ahora);
        $horasTrabajadas = round($horaEntrada->diffInMinutes($horaSalida) / 60, 2);

        // Horas extra (más allá de las 9 horas normales)
        $minutosExtra = max(0, ($horasTrabajadas - 9) * 60);

        $asistencia->update([
            'hora_salida'     => $ahora,
            'horas_trabajadas' => $horasTrabajadas,
            'minutos_extra'   => (int) $minutosExtra,
        ]);

        return $asistencia;
    }

    /**
     * Generar liquidación mensual con descuentos legales chilenos
     */
    public function generarLiquidacion(Empleado $empleado, int $anio, int $mes): Liquidacion
    {
        $sueldo = $empleado->sueldo_base;

        // Contar horas extra del mes
        $horasExtra = Asistencia::where('empleado_id', $empleado->id)
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->sum('minutos_extra');

        // Factor hora extra: sueldo/30/8 * 1.5
        $valorHoraExtra = ($sueldo / 30 / 8) * 1.5;
        $montoHorasExtra = (int) round(($horasExtra / 60) * $valorHoraExtra);

        $totalHaberes = $sueldo + $montoHorasExtra;

        // --- Descuentos Previsionales (sobre total haberes) ---
        $dctoAfp = (int) round($totalHaberes * $empleado->afp_pct / 100);
        $dctoSalud = (int) round($totalHaberes * $empleado->salud_pct / 100);
        $dctoMutual = $empleado->mutual ? (int) round($totalHaberes * $empleado->mutual_pct / 100) : 0;
        $dctoSis = (int) round($totalHaberes * 1.53 / 100);  // SIS: 1.53%
        $dctoCesantia = (int) round($totalHaberes * 0.6 / 100); // Cesantía trabajador: 0.6%

        $totalDctosPrevisionales = $dctoAfp + $dctoSalud + $dctoMutual + $dctoSis + $dctoCesantia;

        // Base imponible para impuesto
        $baseImponible = $totalHaberes - $totalDctosPrevisionales;

        // Impuesto Único (simplificado — tramos 2024)
        $impuesto = $this->calcularImpuestoUnico($baseImponible);

        $totalDescuentos = $totalDctosPrevisionales + $impuesto;
        $sueldoLiquido = $totalHaberes - $totalDescuentos;

        return Liquidacion::updateOrCreate(
            ['empleado_id' => $empleado->id, 'anio' => $anio, 'mes' => $mes],
            [
                'dias_trabajados'  => 30,
                'sueldo_base'      => $sueldo,
                'horas_extra_monto' => $montoHorasExtra,
                'bonos'            => 0,
                'total_haberes'    => $totalHaberes,
                'dcto_afp'         => $dctoAfp,
                'dcto_salud'       => $dctoSalud,
                'dcto_mutual'      => $dctoMutual,
                'dcto_sis'         => $dctoSis,
                'dcto_cesantia'    => $dctoCesantia,
                'base_imponible'   => $baseImponible,
                'impuesto_unico'   => $impuesto,
                'total_descuentos' => $totalDescuentos,
                'sueldo_liquido'   => $sueldoLiquido,
                'estado'           => 'borrador',
            ]
        );
    }

    /**
     * Impuesto Único (tramos simplificados Chile 2024)
     * Basado en UTM ~$65.000 aprox.
     */
    private function calcularImpuestoUnico(int $baseImponible): int
    {
        $utm = 65000; // Valor aproximado UTM

        $tramos = [
            ['hasta' => 13.5, 'tasa' => 0.00, 'rebaja' => 0],
            ['hasta' => 30,   'tasa' => 0.04, 'rebaja' => 13.5 * 0.04],
            ['hasta' => 50,   'tasa' => 0.08, 'rebaja' => 30 * 0.08 - (13.5 * 0.04)],
            ['hasta' => 70,   'tasa' => 0.135, 'rebaja' => 50 * 0.135 - (30 * 0.08)],
            ['hasta' => 90,   'tasa' => 0.23,  'rebaja' => 70 * 0.23 - (50 * 0.135)],
            ['hasta' => 120,  'tasa' => 0.304, 'rebaja' => 90 * 0.304 - (70 * 0.23)],
            ['hasta' => PHP_INT_MAX / $utm, 'tasa' => 0.35, 'rebaja' => 120 * 0.35 - (90 * 0.304)],
        ];

        $rentaUTM = $baseImponible / $utm;

        foreach ($tramos as $tramo) {
            if ($rentaUTM <= $tramo['hasta']) {
                $impuestoUTM = ($rentaUTM * $tramo['tasa']) - $tramo['rebaja'];
                return max(0, (int) round($impuestoUTM * $utm));
            }
        }

        return 0;
    }

    /**
     * Aprobar/rechazar vacaciones
     */
    public function resolverVacacion(Vacacion $vac, string $estado, int $aprobadorId, ?string $motivo = null): Vacacion
    {
        $vac->update([
            'estado'         => $estado,
            'aprobado_por'   => $aprobadorId,
            'motivo_rechazo' => $estado === 'rechazada' ? $motivo : null,
        ]);

        if ($estado === 'aprobada') {
            $vac->empleado->decrement('dias_vacaciones_pendientes', $vac->dias_solicitados);
        }

        // Notificar por WA
        try {
            $wa = app(WhatsAppService::class);
            $msg = $estado === 'aprobada'
                ? "✅ Tu solicitud de vacaciones ({$vac->fecha_inicio->format('d/m')} - {$vac->fecha_fin->format('d/m')}) fue APROBADA."
                : "❌ Tu solicitud de vacaciones fue RECHAZADA. Motivo: " . ($motivo ?? 'Sin especificar');
            $wa->enviarMensaje($vac->empleado->telefono, $msg);
        } catch (\Exception $e) {
            // WA opcional
        }

        return $vac->fresh();
    }

    /**
     * Dashboard RRHH
     */
    public function getDashboard(): array
    {
        $hoy = today();
        return [
            'empleados_activos'    => Empleado::activos()->count(),
            'presentes_hoy'        => Asistencia::whereDate('fecha', $hoy)->whereNotNull('hora_entrada')->count(),
            'atrasos_hoy'          => Asistencia::whereDate('fecha', $hoy)->where('minutos_atraso', '>', 0)->count(),
            'vacaciones_pendientes' => Vacacion::pendientes()->count(),
            'permisos_pendientes'  => Permiso::where('estado', 'pendiente')->count(),
        ];
    }
}
