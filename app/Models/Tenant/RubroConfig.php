<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class RubroConfig extends Model
{
    protected $table = 'rubros_config';

    protected $fillable = [
        'industria_preset',
        'industria_nombre',
        'modulos_activos',
        'label_operario',
        'label_cliente',
        'label_cajero',
        'label_producto',
        'label_recurso',
        'label_nota',
        'documento_default',
        'requiere_rut',
        'boleta_sin_detalle',
        'tiene_stock_fisico',
        'tiene_renta',
        'tiene_renta_hora',
        'tiene_servicios',
        'tiene_agenda',
        'tiene_delivery',
        'tiene_comandas',
        'tiene_ot',
        'tiene_membresias',
        'tiene_notas_cifradas',
        'tiene_fiado',
        'tiene_fraccionado',
        'tiene_descuento_vol',
        'recurso_estados',
        'alerta_vencimiento_min',
        'log_acceso_notas',
        'cifrado_notas',
        'accent_color',
        'recurso_historial',
    ];

    protected $casts = [
        'modulos_activos' => 'array',
        'recurso_estados' => 'array',
        'requiere_rut' => 'boolean',
        'boleta_sin_detalle' => 'boolean',
        'tiene_stock_fisico' => 'boolean',
        'tiene_renta' => 'boolean',
        'tiene_renta_hora' => 'boolean',
        'tiene_servicios' => 'boolean',
        'tiene_agenda' => 'boolean',
        'tiene_delivery' => 'boolean',
        'tiene_comandas' => 'boolean',
        'tiene_ot' => 'boolean',
        'tiene_membresias' => 'boolean',
        'tiene_notas_cifradas' => 'boolean',
        'tiene_fiado' => 'boolean',
        'tiene_fraccionado' => 'boolean',
        'tiene_descuento_vol' => 'boolean',
        'log_acceso_notas' => 'boolean',
        'cifrado_notas' => 'boolean',
    ];
}
