<?php

namespace App\Services;

use App\Models\Tenant\Empleado;
use App\Models\Tenant\OfertaEmpleo;
use App\Models\Tenant\Postulacion;
use App\Models\Tenant\Entrevista;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReclutamientoService
{
    /**
     * Procesar nueva postulación pública
     */
    public function nuevaPostulacion(OfertaEmpleo $oferta, array $data): Postulacion
    {
        $postulacion = DB::transaction(function () use ($oferta, $data) {
            return $oferta->postulaciones()->create([
                'nombre'              => $data['nombre'],
                'email'               => $data['email'],
                'telefono'            => $data['telefono'] ?? null,
                'rut'                 => $data['rut'] ?? null,
                'mensaje'             => $data['mensaje'] ?? null,
                'cv_path'             => $data['cv_path'] ?? null,
                'pretension_salarial' => $data['pretension_salarial'] ?? null,
                'estado'              => 'recibida',
            ]);
        });

        // Notificar al candidato
        $this->notificarCandidato($postulacion, "¡Hola {$postulacion->nombre}! Hemos recibido tu postulación para el cargo de {$oferta->titulo}. Te contactaremos pronto.");

        return $postulacion;
    }

    /**
     * Mover candidato por el pipeline
     */
    public function moverPipeline(Postulacion $postulacion, string $nuevoEstado, ?string $notas = null): Postulacion
    {
        if (!$postulacion->puedeAvanzarA($nuevoEstado) && $nuevoEstado !== 'descartada') {
            throw new \Exception("Transición de estado no válida de {$postulacion->estado} a {$nuevoEstado}");
        }

        DB::transaction(function () use ($postulacion, $nuevoEstado, $notas) {
            $postulacion->estado = $nuevoEstado;
            if ($notas) {
                $postulacion->notas_internas = trim($postulacion->notas_internas . "\n[" . now()->format('Y-m-d H:i') . "] " . $notas);
            }
            $postulacion->save();

            // Si es contratada, convertir a empleado
            if ($nuevoEstado === 'contratada') {
                $this->contratarPostulante($postulacion);
            }
        });

        // Notificaciones según etapa
        $oferta = $postulacion->oferta;
        if ($nuevoEstado === 'preseleccionada') {
            $this->notificarCandidato($postulacion, "¡Buenas noticias {$postulacion->nombre}! Tu perfil para {$oferta->titulo} nos parece interesante. Pronto te contactaremos para una entrevista.");
        } elseif ($nuevoEstado === 'oferta') {
            $this->notificarCandidato($postulacion, "¡Felicidades {$postulacion->nombre}! Queremos hacerte una propuesta formal para unirte a nuestro equipo como {$oferta->titulo}. Revisa tu correo o contáctanos.");
        } elseif ($nuevoEstado === 'contratada') {
            $this->notificarCandidato($postulacion, "¡Bienvenido/a al equipo {$postulacion->nombre}! 🎉");
        } elseif ($nuevoEstado === 'descartada') {
            $this->notificarCandidato($postulacion, "Hola {$postulacion->nombre}, gracias por tu interés en {$oferta->titulo}. En esta ocasión hemos avanzado con otros candidatos, pero mantendremos tu CV en nuestra base de datos.");
        }

        return $postulacion->fresh();
    }

    /**
     * Programar entrevista
     */
    public function programarEntrevista(Postulacion $postulacion, array $data, int $entrevistadorId): Entrevista
    {
        $entrevista = $postulacion->entrevistas()->create([
            'entrevistador_id' => $entrevistadorId,
            'fecha_hora'       => $data['fecha_hora'],
            'tipo'             => $data['tipo'] ?? 'presencial',
            'lugar'            => $data['lugar'] ?? null,
            'link_video'       => $data['link_video'] ?? null,
            'estado'           => 'programada',
        ]);

        $this->moverPipeline($postulacion, 'entrevista', "Entrevista programada para: {$data['fecha_hora']}");

        $fechaFmt = Carbon::parse($data['fecha_hora'])->format('d/m/Y a las H:i');
        $lugarInfo = $entrevista->tipo === 'presencial' ? "Lugar: {$entrevista->lugar}" : "Link: {$entrevista->link_video}";
        $this->notificarCandidato($postulacion, "Tu entrevista para {$postulacion->oferta->titulo} ha sido programada para el {$fechaFmt}. Modalidad: {$entrevista->tipo}. {$lugarInfo}");

        return $entrevista;
    }

    /**
     * Contratar postulante (pasa a Empleado automáticamente)
     */
    private function contratarPostulante(Postulacion $postulacion): Empleado
    {
        return Empleado::create([
            'nombre'        => $postulacion->nombre,
            'rut'           => $postulacion->rut,
            'email'         => $postulacion->email,
            'telefono'      => $postulacion->telefono,
            'cargo'         => $postulacion->oferta->cargo ?? $postulacion->oferta->titulo,
            'fecha_ingreso' => today(),
            'sueldo_base'   => $postulacion->oferta->sueldo_max ?? 500000, // Valor referencial seguro
            'activo'        => true,
            'dias_vacaciones_anuales' => 15,
        ]);
    }

    /**
     * Enviar notificación WhatsApp al candidato (opcional)
     */
    private function notificarCandidato(Postulacion $postulacion, string $mensaje): void
    {
        if (empty($postulacion->telefono)) return;

        try {
            $wa = app(WhatsAppService::class);
            $wa->enviarMensaje($postulacion->telefono, $mensaje);
        } catch (\Exception $e) {
            Log::warning("Error notificando postulante {$postulacion->id}: " . $e->getMessage());
        }
    }

    /**
     * Dashboard reclutamiento
     */
    public function getDashboard(): array
    {
        return [
            'ofertas_activas'       => OfertaEmpleo::publicadas()->count(),
            'postulaciones_nuevas'  => Postulacion::where('estado', 'recibida')->count(),
            'entrevistas_semana'    => Entrevista::where('estado', 'programada')
                                         ->whereBetween('fecha_hora', [now()->startOfWeek(), now()->endOfWeek()])
                                         ->count(),
            'candidatos_pipeline'   => Postulacion::whereIn('estado', ['preseleccionada', 'entrevista', 'evaluacion', 'oferta'])->count(),
        ];
    }
}
