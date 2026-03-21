<?php

namespace App\Services;

use App\Models\Tenant\Entrega;
use App\Models\Tenant\Repartidor;
use App\Models\Tenant\TrackingEntrega;
use App\Models\Tenant\Venta;
use App\Models\Tenant\ZonaEnvio;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    /**
     * Crear entrega automáticamente al confirmar venta con tipo_entrega=envio
     */
    public function crearEntrega(Venta $venta, array $datos): Entrega
    {
        $zona = null;
        $costoEnvio = $datos['costo_envio'] ?? 0;

        if (!empty($datos['zona_envio_id'])) {
            $zona = ZonaEnvio::find($datos['zona_envio_id']);
            $costoEnvio = $zona?->costo_envio ?? $costoEnvio;
        }

        $entrega = Entrega::create([
            'venta_id'          => $venta->id,
            'zona_envio_id'     => $zona?->id,
            'estado'            => 'pendiente',
            'direccion_entrega' => $datos['direccion'],
            'comuna_entrega'    => $datos['comuna'] ?? null,
            'telefono_contacto' => $datos['telefono'] ?? $venta->cliente?->telefono,
            'nombre_receptor'   => $datos['nombre_receptor'] ?? $venta->cliente?->nombre,
            'instrucciones'     => $datos['instrucciones'] ?? null,
            'costo_envio'       => $costoEnvio,
        ]);

        $this->agregarTracking($entrega, 'pendiente', 'Entrega creada, esperando asignación de repartidor');

        return $entrega;
    }

    /**
     * Asignar repartidor a una entrega
     */
    public function asignar(Entrega $entrega, Repartidor $repartidor): Entrega
    {
        if (!$entrega->puedeAsignar()) {
            throw new \RuntimeException("Entrega no puede ser asignada en estado {$entrega->estado}");
        }

        $entrega->update([
            'repartidor_id' => $repartidor->id,
            'estado'        => 'asignada',
            'asignada_at'   => now(),
        ]);

        $this->agregarTracking($entrega, 'asignada', "Repartidor asignado: {$repartidor->nombre}");
        $this->notificarCambioEstado($entrega, 'asignada');

        return $entrega->fresh('repartidor');
    }

    /**
     * Cambiar estado (flujo: asignada → en_preparacion → en_camino → entregada)
     */
    public function cambiarEstado(Entrega $entrega, string $nuevoEstado, ?array $extra = null): Entrega
    {
        $transicionesValidas = [
            'asignada'       => ['en_preparacion'],
            'en_preparacion' => ['en_camino'],
            'en_camino'      => ['entregada', 'fallida'],
        ];

        $permitidos = $transicionesValidas[$entrega->estado] ?? [];

        if (!in_array($nuevoEstado, $permitidos)) {
            throw new \RuntimeException("No se puede cambiar de {$entrega->estado} a {$nuevoEstado}");
        }

        $campos = ['estado' => $nuevoEstado];
        $descripcion = match ($nuevoEstado) {
            'en_preparacion' => 'Pedido en preparación',
            'en_camino'      => 'Repartidor en camino',
            'entregada'      => 'Entrega completada exitosamente',
            'fallida'        => 'Entrega fallida: ' . ($extra['motivo'] ?? 'sin motivo'),
            default          => "Estado actualizado a {$nuevoEstado}",
        };

        // Timestamps por estado
        $campos["{$nuevoEstado}_at"] = now();
        if ($nuevoEstado === 'fallida') {
            $campos['motivo_fallo'] = $extra['motivo'] ?? null;
        }

        $entrega->update($campos);

        $this->agregarTracking(
            $entrega,
            $nuevoEstado,
            $descripcion,
            $extra['latitud'] ?? null,
            $extra['longitud'] ?? null,
        );

        $this->notificarCambioEstado($entrega, $nuevoEstado);

        return $entrega->fresh();
    }

    /**
     * Obtener tracking público por UUID
     */
    public function getTrackingPublico(string $uuid): ?array
    {
        $entrega = Entrega::with(['tracking', 'repartidor', 'zonaEnvio'])
            ->where('uuid', $uuid)
            ->first();

        if (!$entrega) return null;

        return [
            'uuid'             => $entrega->uuid,
            'estado'           => $entrega->estado,
            'direccion'        => $entrega->direccion_entrega,
            'repartidor'       => $entrega->repartidor?->nombre,
            'vehiculo'         => $entrega->repartidor?->vehiculo,
            'telefono_repartidor' => $entrega->repartidor?->telefono,
            'costo_envio'      => $entrega->costo_envio,
            'creado'           => $entrega->created_at?->format('d/m/Y H:i'),
            'asignada'         => $entrega->asignada_at?->format('H:i'),
            'en_camino'        => $entrega->en_camino_at?->format('H:i'),
            'entregada'        => $entrega->entregada_at?->format('H:i'),
            'tracking'         => $entrega->tracking->map(fn($t) => [
                'estado'      => $t->estado,
                'descripcion' => $t->descripcion,
                'hora'        => $t->created_at?->format('H:i'),
                'lat'         => $t->latitud,
                'lng'         => $t->longitud,
            ]),
        ];
    }

    /**
     * Dashboard de delivery
     */
    public function getDashboard(): array
    {
        return [
            'activas'            => Entrega::activas()->count(),
            'pendientes'         => Entrega::pendientes()->count(),
            'entregadas_hoy'     => Entrega::where('estado', 'entregada')->whereDate('entregada_at', today())->count(),
            'fallidas_hoy'       => Entrega::where('estado', 'fallida')->whereDate('updated_at', today())->count(),
            'repartidores_disp'  => Repartidor::disponibles()->count(),
            'tiempo_promedio'    => $this->calcularTiempoPromedio(),
        ];
    }

    // --- Privados ---

    private function agregarTracking(Entrega $entrega, string $estado, string $desc, ?float $lat = null, ?float $lng = null): void
    {
        TrackingEntrega::create([
            'entrega_id'  => $entrega->id,
            'estado'      => $estado,
            'descripcion' => $desc,
            'latitud'     => $lat,
            'longitud'    => $lng,
        ]);
    }

    private function notificarCambioEstado(Entrega $entrega, string $estado): void
    {
        try {
            $wa = app(WhatsAppService::class);
            $mensajes = [
                'asignada'       => "📦 Tu pedido ha sido asignado a un repartidor. Seguimiento: {$entrega->url_publica}",
                'en_camino'      => "🚗 Tu pedido está en camino! Seguimiento: {$entrega->url_publica}",
                'entregada'      => "✅ Tu pedido ha sido entregado. ¡Gracias!",
                'fallida'        => "❌ No pudimos entregar tu pedido. Te contactaremos pronto.",
            ];

            if (isset($mensajes[$estado]) && $entrega->telefono_contacto) {
                $wa->enviarMensaje($entrega->telefono_contacto, $mensajes[$estado]);
            }
        } catch (\Exception $e) {
            Log::warning("DeliveryService: Error notificando WA entrega {$entrega->id}: " . $e->getMessage());
        }
    }

    private function calcularTiempoPromedio(): ?int
    {
        $entregas = Entrega::where('estado', 'entregada')
            ->whereNotNull('asignada_at')
            ->whereNotNull('entregada_at')
            ->whereDate('entregada_at', today())
            ->get();

        if ($entregas->isEmpty()) return null;

        $totalMin = $entregas->sum(fn($e) => $e->asignada_at->diffInMinutes($e->entregada_at));
        return (int) round($totalMin / $entregas->count());
    }
}
