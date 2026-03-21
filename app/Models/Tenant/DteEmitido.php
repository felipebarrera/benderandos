<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class DteEmitido extends Model
{
    protected $table = 'dte_emitidos';

    protected $fillable = [
        'venta_id', 'tipo_dte', 'folio', 'fecha_emision',
        'rut_receptor', 'razon_social_receptor',
        'monto_neto', 'monto_iva', 'monto_total',
        'xml', 'track_id', 'estado_sii', 'pdf_url',
        'dte_referencia_id', 'motivo_nc',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
        ];
    }

    // --- Constantes Tipos DTE ---
    const FACTURA       = 33;
    const BOLETA        = 39;
    const NOTA_CREDITO  = 61;

    public static function tiposLabel(): array
    {
        return [
            self::FACTURA      => 'Factura Electrónica',
            self::BOLETA       => 'Boleta Electrónica',
            self::NOTA_CREDITO => 'Nota de Crédito Electrónica',
        ];
    }

    // --- Relaciones ---

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function dteReferencia()
    {
        return $this->belongsTo(DteEmitido::class, 'dte_referencia_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(DteEmitido::class, 'dte_referencia_id');
    }

    // --- Scopes ---

    public function scopeBoletas($query)
    {
        return $query->where('tipo_dte', self::BOLETA);
    }

    public function scopeFacturas($query)
    {
        return $query->where('tipo_dte', self::FACTURA);
    }

    public function scopeGetNotasCredito($query)
    {
        return $query->where('tipo_dte', self::NOTA_CREDITO);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado_sii', 'pendiente');
    }

    public function scopeAceptados($query)
    {
        return $query->where('estado_sii', 'ACE');
    }

    public function scopeDelDia($query)
    {
        return $query->whereDate('fecha_emision', today());
    }

    public function scopeDelMes($query, ?int $mes = null, ?int $anio = null)
    {
        $mes  = $mes ?? now()->month;
        $anio = $anio ?? now()->year;

        return $query->whereMonth('fecha_emision', $mes)
                     ->whereYear('fecha_emision', $anio);
    }

    // --- Helpers ---

    public function getTipoLabelAttribute(): string
    {
        return self::tiposLabel()[$this->tipo_dte] ?? 'Desconocido';
    }

    public function estaAceptado(): bool
    {
        return $this->estado_sii === 'ACE';
    }

    public function estaRechazado(): bool
    {
        return in_array($this->estado_sii, ['REC', 'REP']);
    }
}
