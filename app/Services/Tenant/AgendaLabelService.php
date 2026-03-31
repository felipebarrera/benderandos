<?php

namespace App\Services\Tenant;

class AgendaLabelService
{
    /**
     * Devuelve las etiquetas dinámicas según la industria del tenant.
     */
    public static function getLabels($industria = 'clinica')
    {
        $map = [
            'medico' => [
                'sujeto' => 'Paciente',
                'objeto' => 'Cita Medica',
                'autor' => 'Profesional',
                'nota' => 'Nota Clínica'
            ],
            'abogado' => [
                'sujeto' => 'Cliente',
                'objeto' => 'Audiencia / Reunion',
                'autor' => 'Abogado',
                'nota' => 'Bitácora Legal'
            ],
            'gym' => [
                'sujeto' => 'Alumno',
                'objeto' => 'Clase / Entrenamiento',
                'autor' => 'Instructor',
                'nota' => 'Progreso Físico'
            ],
            'padel' => [
                'sujeto' => 'Jugador',
                'objeto' => 'Reserva de Cancha',
                'autor' => 'Administrador',
                'nota' => 'Notas del Juego'
            ]
        ];

        return $map[$industria] ?? $map['medico'];
    }
}
