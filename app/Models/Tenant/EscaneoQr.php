<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class EscaneoQr extends Model
{
    protected $table = 'escaneos_qr';
    public $timestamps = false; 

    protected $fillable = [
        'qr_id', 'ip_address', 'user_agent', 'device_type',
        'fecha_escaneo', 'convertido', 'venta_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_escaneo' => 'datetime',
            'convertido' => 'boolean',
        ];
    }

    public function qr()
    {
        return $this->belongsTo(QrCampana::class, 'qr_id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
