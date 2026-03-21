<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\RubroConfig;
use Illuminate\Database\Seeder;

class IndustriaPresetsSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'industria_preset' => 'retail',
                'industria_nombre' => 'Retail / Abarrotes',
                'modulos_activos' => ['M01', 'M02', 'M03', 'M04', 'M11', 'M12', 'M17', 'M18', 'M20', 'M24', 'M25', 'M32'],
                'label_operario' => 'Vendedor',
                'accent_color' => '#f5c518',
            ],
            [
                'industria_preset' => 'mayorista',
                'industria_nombre' => 'Mayorista / Ferretería',
                'modulos_activos' => ['M01', 'M02', 'M03', 'M04', 'M11', 'M17', 'M18', 'M19', 'M20', 'M24', 'M26', 'M32'],
                'label_producto' => 'Artículo',
                'tiene_descuento_vol' => true,
                'accent_color' => '#3b82f6',
            ],
            [
                'industria_preset' => 'restaurante',
                'industria_nombre' => 'Restaurante',
                'modulos_activos' => ['M01', 'M02', 'M03', 'M14', 'M15', 'M16', 'M17', 'M18', 'M20', 'M24', 'M32'],
                'label_operario' => 'Garzón',
                'label_recurso' => 'Mesa',
                'tiene_comandas' => true,
                'recurso_estados' => ['libre', 'ocupada', 'pendiente_pago'],
                'accent_color' => '#ef4444',
            ],
            [
                'industria_preset' => 'delivery',
                'industria_nombre' => 'Delivery / Dark Kitchen',
                'modulos_activos' => ['M01', 'M02', 'M03', 'M13', 'M15', 'M16', 'M17', 'M18', 'M20', 'M24'],
                'tiene_delivery' => true,
                'accent_color' => '#f97316',
            ],
            [
                'industria_preset' => 'motel',
                'industria_nombre' => 'Motel / Hospedaje Horas',
                'modulos_activos' => ['M01', 'M03', 'M06', 'M14', 'M17', 'M20'],
                'label_operario' => 'Recepcionista',
                'label_cliente' => 'Huésped',
                'label_recurso' => 'Habitación',
                'tiene_renta_hora' => true,
                'boleta_sin_detalle' => true,
                'requiere_rut' => false,
                'recurso_estados' => ['libre', 'ocupada', 'limpieza', 'mantencion'],
                'accent_color' => '#ff6b35',
            ],
            [
                'industria_preset' => 'hotel',
                'industria_nombre' => 'Hotel / Alojamiento Días',
                'modulos_activos' => ['M01', 'M03', 'M05', 'M08', 'M13', 'M14', 'M17', 'M20', 'M27'],
                'label_recurso' => 'Habitación',
                'tiene_renta' => true,
                'tiene_agenda' => true,
                'accent_color' => '#6366f1',
            ],
            [
                'industria_preset' => 'canchas',
                'industria_nombre' => 'Canchas / Deportes',
                'modulos_activos' => ['M01', 'M03', 'M06', 'M08', 'M14', 'M17', 'M20', 'M24', 'M30'],
                'label_recurso' => 'Cancha',
                'tiene_renta_hora' => true,
                'tiene_agenda' => true,
                'tiene_membresias' => true,
                'recurso_estados' => ['libre', 'reservada', 'ocupada'],
                'accent_color' => '#10b981',
            ],
            [
                'industria_preset' => 'medico',
                'industria_nombre' => 'Médico / Clínica',
                'modulos_activos' => ['M01', 'M07', 'M08', 'M09', 'M10', 'M20', 'M21', 'M22', 'M23', 'M32'],
                'label_operario' => 'Médico',
                'label_cliente' => 'Paciente',
                'label_cajero' => 'Recepcionista',
                'label_nota' => 'Historia Clínica',
                'documento_default' => 'honorarios',
                'tiene_agenda' => true,
                'tiene_servicios' => true,
                'tiene_notas_cifradas' => true,
                'log_acceso_notas' => true,
                'cifrado_notas' => true,
                'accent_color' => '#34d399',
            ],
            [
                'industria_preset' => 'dentista',
                'industria_nombre' => 'Dentista',
                'modulos_activos' => ['M01', 'M07', 'M08', 'M09', 'M10', 'M20', 'M21', 'M32'],
                'label_operario' => 'Dentista',
                'label_cliente' => 'Paciente',
                'tiene_agenda' => true,
                'tiene_servicios' => true,
                'tiene_notas_cifradas' => true,
                'accent_color' => '#0ea5e9',
            ],
            [
                'industria_preset' => 'legal',
                'industria_nombre' => 'Abogados / Estudio Jurídico',
                'modulos_activos' => ['M01', 'M07', 'M08', 'M09', 'M10', 'M20', 'M21', 'M32'],
                'label_operario' => 'Abogado',
                'label_recurso' => 'Caso',
                'label_nota' => 'Expediente',
                'tiene_agenda' => true,
                'tiene_servicios' => true,
                'tiene_notas_cifradas' => true,
                'accent_color' => '#818cf8',
            ],
            [
                'industria_preset' => 'tecnico',
                'industria_nombre' => 'Gasfíter / Técnico',
                'modulos_activos' => ['M01', 'M03', 'M07', 'M28', 'M29', 'M20', 'M21', 'M32'],
                'label_operario' => 'Técnico',
                'tiene_ot' => true,
                'accent_color' => '#f97316',
            ],
            [
                'industria_preset' => 'taller',
                'industria_nombre' => 'Taller Mecánico',
                'modulos_activos' => ['M01', 'M03', 'M07', 'M18', 'M28', 'M29', 'M20', 'M21', 'M32'],
                'label_operario' => 'Mecánico',
                'label_recurso' => 'Vehículo',
                'tiene_ot' => true,
                'recurso_historial' => 'patente',
                'accent_color' => '#ea580c',
            ],
            [
                'industria_preset' => 'spa',
                'industria_nombre' => 'Salón de Belleza / Spa',
                'modulos_activos' => ['M01', 'M07', 'M08', 'M17', 'M20', '_M24', 'M30', 'M32'],
                'label_operario' => 'Estilista',
                'tiene_agenda' => true,
                'tiene_membresias' => true,
                'accent_color' => '#ec4899',
            ],
            [
                'industria_preset' => 'veterinaria',
                'industria_nombre' => 'Veterinaria',
                'modulos_activos' => ['M01', 'M03', 'M07', 'M08', 'M10', 'M20', 'M29', 'M32'],
                'label_cliente' => 'Dueño',
                'label_recurso' => 'Mascota',
                'tiene_agenda' => true,
                'tiene_notas_cifradas' => true,
                'accent_color' => '#14b8a6',
            ],
            [
                'industria_preset' => 'farmacia',
                'industria_nombre' => 'Farmacia',
                'modulos_activos' => ['M01', 'M03', 'M04', 'M11', 'M18', 'M19', 'M20', 'M32'],
                'tiene_fraccionado' => true,
                'accent_color' => '#10b981',
            ],
            [
                'industria_preset' => 'gym',
                'industria_nombre' => 'Gimnasio / Fitness',
                'modulos_activos' => ['M01', 'M03', 'M08', 'M17', 'M20', 'M30', 'M32'],
                'tiene_agenda' => true,
                'tiene_membresias' => true,
                'accent_color' => '#3b82f6',
            ],
            [
                'industria_preset' => 'inmobiliaria',
                'industria_nombre' => 'Inmobiliaria',
                'modulos_activos' => ['M01', 'M07', 'M08', 'M20', 'M21', 'M23', 'M32'],
                'tiene_agenda' => true,
                'accent_color' => '#6366f1',
            ],
            [
                'industria_preset' => 'constructora',
                'industria_nombre' => 'Constructora',
                'modulos_activos' => ['M01', 'M03', 'M07', 'M18', 'M28', 'M20', 'M21', 'M22'],
                'tiene_ot' => true,
                'accent_color' => '#475569',
            ],
            [
                'industria_preset' => 'saas',
                'industria_nombre' => 'Proveedor Software / SaaS',
                'modulos_activos' => ['M01', 'M07', 'M20', 'M21', 'M22', 'M23', 'M24', 'M25', 'M27', 'M31', 'M32'],
                'tiene_servicios' => true,
                'accent_color' => '#2563eb',
            ],
        ];

        foreach ($presets as $preset) {
            RubroConfig::updateOrCreate(
                ['industria_preset' => $preset['industria_preset']],
                $preset
            );
        }
    }
}
