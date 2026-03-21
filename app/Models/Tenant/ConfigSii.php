<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ConfigSii extends Model
{
    protected $table = 'config_sii';

    protected $fillable = [
        'rut_empresa', 'razon_social', 'giro', 'acteco',
        'direccion', 'comuna', 'ciudad', 'ambiente',
        'certificado_digital', 'clave_certificado',
        'resolucion_fecha', 'resolucion_numero',
        'libredte_hash', 'documento_default', 'email_dte',
        'folio_siguiente_boleta', 'folio_siguiente_factura', 'folio_siguiente_nc',
    ];

    protected $hidden = [
        'certificado_digital', 'clave_certificado', 'libredte_hash',
    ];

    protected function casts(): array
    {
        return [
            'certificado_digital' => 'encrypted',
            'clave_certificado'   => 'encrypted',
            'libredte_hash'       => 'encrypted',
        ];
    }

    public function esCertificacion(): bool
    {
        return $this->ambiente === 'certificacion';
    }

    public function esProduccion(): bool
    {
        return $this->ambiente === 'produccion';
    }

    /**
     * Obtener y auto-incrementar el folio siguiente para un tipo de DTE.
     */
    public function consumirFolio(int $tipoDte): int
    {
        $campo = match ($tipoDte) {
            33 => 'folio_siguiente_factura',
            39 => 'folio_siguiente_boleta',
            61 => 'folio_siguiente_nc',
            default => 'folio_siguiente_boleta',
        };

        $folio = $this->{$campo};
        $this->increment($campo);

        return $folio;
    }
}
