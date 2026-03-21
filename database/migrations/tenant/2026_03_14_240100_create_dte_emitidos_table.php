<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_emitidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->integer('tipo_dte');            // 33=factura, 39=boleta, 61=nota_credito
            $table->integer('folio');
            $table->date('fecha_emision');
            $table->string('rut_receptor', 12)->nullable();
            $table->string('razon_social_receptor')->nullable();
            $table->integer('monto_neto')->default(0);
            $table->integer('monto_iva')->default(0);
            $table->integer('monto_total')->default(0);
            $table->text('xml')->nullable();
            $table->string('track_id')->nullable();  // ID SII para seguimiento
            $table->enum('estado_sii', [
                'pendiente', 'enviado', 'ACE', 'REC', 'REP', 'error'
            ])->default('pendiente');
            $table->string('pdf_url')->nullable();
            $table->foreignId('dte_referencia_id')->nullable()->constrained('dte_emitidos')->nullOnDelete();
            $table->string('motivo_nc')->nullable();
            $table->timestamps();

            $table->index(['tipo_dte', 'estado_sii']);
            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_emitidos');
    }
};
