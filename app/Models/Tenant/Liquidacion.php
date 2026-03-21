<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';

    protected $fillable = [
        'empleado_id', 'anio', 'mes', 'dias_trabajados',
        'sueldo_base', 'horas_extra_monto', 'bonos', 'total_haberes',
        'dcto_afp', 'dcto_salud', 'dcto_mutual', 'dcto_sis', 'dcto_cesantia',
        'base_imponible', 'impuesto_unico', 'total_descuentos', 'sueldo_liquido',
        'estado',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }
}
