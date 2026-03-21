<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SaasCobro extends Model
{
    protected $table = 'saas_cobros';

    protected $fillable = [
        'cliente_id', 'periodo', 'monto', 'descuento', 'total',
        'estado', 'fecha_vencimiento', 'fecha_pago', 'metodo_pago',
        'dte_id', 'referencia_pago'
    ];

    protected function casts(): array
    {
        return [
            'periodo'           => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_pago'        => 'date',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(SaasCliente::class, 'cliente_id');
    }
}
