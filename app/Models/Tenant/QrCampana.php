<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QrCampana extends Model
{
    protected $table = 'qr_campanas';

    protected $fillable = [
        'campana_id', 'uuid', 'ubicacion_fisica', 'qr_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (QrCampana $qr) {
            if (empty($qr->uuid)) {
                $qr->uuid = Str::uuid()->toString();
            }
        });
    }

    public function campana()
    {
        return $this->belongsTo(CampanaMarketing::class, 'campana_id');
    }

    public function escaneos()
    {
        return $this->hasMany(EscaneoQr::class, 'qr_id');
    }
}
