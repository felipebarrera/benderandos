<?php
namespace App\Services;

use App\Models\Tenant\AgendaRecurso;
use App\Models\Tenant\AgendaHorario;
use App\Models\Tenant\AgendaConfig;
use App\Models\Tenant\AgendaServicio;
use App\Models\Tenant\RubroConfig;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;

class AgendaAutoRegistroService
{
    /**
     * Verifica si el tenant actual tiene M08 activo.
     */
    public function m08Activo(): bool
    {
        $config = RubroConfig::first();
        return $config && in_array('M08', $config->modulos_activos ?? []);
    }

    // ═══════════════════════════════════════════════════════════════
    // OPERARIOS → RECURSOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea o reactiva un AgendaRecurso para un usuario operario/cajero.
     * Si ya existe (por usuario_id), no duplica — solo reactiva si estaba inactivo.
     *
     * Retorna el recurso (nuevo o existente).
     */
    public function registrarOperario(Usuario $usuario): ?AgendaRecurso
    {
        if (!$this->m08Activo()) return null;
        if (!in_array($usuario->rol, ['operario', 'cajero', 'recepcionista', 'admin'])) return null;

        // Buscar por usuario_id primero
        $recurso = AgendaRecurso::where('usuario_id', $usuario->id)->first();

        if ($recurso) {
            // Ya existe: reactivar si estaba inactivo
            if (!$recurso->activo) {
                $recurso->update(['activo' => true]);
            }
            return $recurso;
        }

        // Crear nuevo recurso
        $color = $this->colorPorIndice(
            AgendaRecurso::where('tipo', 'profesional')->count()
        );

        $recurso = AgendaRecurso::create([
            'nombre'                  => $usuario->nombre,
            'tipo'                    => 'profesional',
            'especialidad'            => $this->especialidadPorRubro(),
            'color'                   => $color,
            'orden'                   => AgendaRecurso::max('orden') + 1,
            'usuario_id'              => $usuario->id,
            'auto_creado'             => true,
            'hereda_horario_tenant'   => true,
            'activo'                  => true,
        ]);

        // Crear horarios por defecto
        $this->crearHorariosDefecto($recurso);

        return $recurso;
    }

    /**
     * Desactiva el recurso cuando el operario se desactiva o elimina.
     * NO elimina — conserva historial de citas.
     */
    public function desactivarOperario(int $usuarioId): void
    {
        AgendaRecurso::where('usuario_id', $usuarioId)
                     ->where('auto_creado', true)
                     ->update(['activo' => false]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTOS RENTA → RECURSOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea o reactiva un AgendaRecurso para un producto de tipo renta.
     */
    public function registrarProductoRenta(Producto $producto): ?AgendaRecurso
    {
        if (!$this->m08Activo()) return null;
        if ($producto->tipo_producto !== 'renta') return null;

        $recurso = AgendaRecurso::where('producto_id', $producto->id)->first();

        if ($recurso) {
            if (!$recurso->activo) {
                $recurso->update(['activo' => true, 'nombre' => $producto->nombre]);
            }
            return $recurso;
        }

        $config   = RubroConfig::first();
        $labelRec = $config?->label_recurso ?? 'Recurso';

        $recurso = AgendaRecurso::create([
            'nombre'                => $producto->nombre,
            'tipo'                  => 'recurso',
            'especialidad'          => $labelRec,
            'color'                 => '#00c4ff',
            'orden'                 => AgendaRecurso::max('orden') + 1,
            'producto_id'           => $producto->id,
            'auto_creado'           => true,
            'hereda_horario_tenant' => true,
            'activo'                => true,
        ]);

        // Crear servicio automático: "Reserva de {nombre}" con precio del producto
        AgendaServicio::create([
            'agenda_recurso_id' => $recurso->id,
            'nombre'       => 'Reserva de ' . $producto->nombre,
            'duracion_min' => 60,
            'precio'       => $producto->valor_venta ?? 0,
            'activo'       => true,
        ]);

        $this->crearHorariosDefecto($recurso);

        return $recurso;
    }

    /**
     * Desactiva el recurso cuando el producto se desactiva o elimina.
     */
    public function desactivarProductoRenta(int $productoId): void
    {
        AgendaRecurso::where('producto_id', $productoId)
                     ->where('auto_creado', true)
                     ->update(['activo' => false]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ACTIVACIÓN DEL MÓDULO M08 EN UN TENANT EXISTENTE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Retroactivamente crea recursos para operarios y productos renta.
     */
    public function inicializarTenant(): array
    {
        $resultados = [
            'operarios_registrados' => 0,
            'productos_registrados' => 0,
        ];

        // Registrar todos los operarios/cajeros/recepcionistas activos
        $operarios = Usuario::whereIn('rol', ['operario','cajero','recepcionista','admin'])
                           ->where('activo', true)
                           ->get();

        foreach ($operarios as $u) {
            if ($this->registrarOperario($u)) {
                $resultados['operarios_registrados']++;
            }
        }

        // Registrar todos los productos de renta activos
        $productos = Producto::where('tipo_producto', 'renta')
                            ->where('estado', 'activo')
                            ->get();

        foreach ($productos as $p) {
            if ($this->registrarProductoRenta($p)) {
                $resultados['productos_registrados']++;
            }
        }

        // Crear AgendaConfig por defecto si no existe
        AgendaConfig::firstOrCreate([], [
            'titulo_landing'          => config('tenancy.tenant_name', 'Nuestros servicios'),
            'descripcion_landing'     => 'Reserva tu hora online fácilmente.',
            'landing_publico_activo'  => true,
            'confirmacion_wa_activa'  => false,
            'recordatorio_activo'     => true,
            'recordatorio_horas_antes'=> 24,
            'requiere_telefono'       => true,
            'requiere_email'          => false,
            'color_primario'          => '#00e5a0',
        ]);

        return $resultados;
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Crea horarios por defecto.
     */
    private function crearHorariosDefecto(AgendaRecurso $recurso): void
    {
        $config = AgendaConfig::first();

        // Intentar leer horario del tenant si existe
        $hIni  = $config?->horario_inicio ?? '09:00';
        $hFin  = $config?->horario_fin    ?? '18:00';
        $sDur  = $config?->duracion_slot_min ?? 30;

        $horarios = [
            ['dia_semana' => 1, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 2, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 3, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 4, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 5, 'hora_inicio' => $hIni, 'hora_fin' => $hFin, 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 6, 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'activo' => 1, 'duracion_slot_min' => $sDur],
            ['dia_semana' => 7, 'hora_inicio' => '09:00', 'hora_fin' => '18:00', 'activo' => 0, 'duracion_slot_min' => $sDur],
        ];

        foreach ($horarios as $h) {
            AgendaHorario::updateOrCreate(
                ['agenda_recurso_id' => $recurso->id, 'dia_semana' => $h['dia_semana']],
                $h
            );
        }
    }

    /** Paleta de 8 colores vibrantes */
    private function colorPorIndice(int $i): string
    {
        $palette = [
            '#00e5a0', '#7c6af7', '#00c4ff', '#ff6b35',
            '#3dd9eb', '#f5c518', '#e040fb', '#ff3f5b',
        ];
        return $palette[$i % count($palette)];
    }

    /** Especialidad por defecto según el rubro */
    private function especialidadPorRubro(): string
    {
        $config = RubroConfig::first();
        $preset = $config?->industria_preset ?? '';
        return match(true) {
            in_array($preset, ['medico','clinica'])           => 'Médico General',
            in_array($preset, ['dentista'])                   => 'Odontología',
            in_array($preset, ['abogados','legal'])           => 'Abogado',
            in_array($preset, ['padel','canchas','deportes']) => 'Cancha',
            in_array($preset, ['salon','spa'])                => 'Estilista',
            in_array($preset, ['veterinaria'])                => 'Veterinario',
            default                                           => 'Profesional',
        };
    }
}
