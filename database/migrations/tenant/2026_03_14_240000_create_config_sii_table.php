<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_sii', function (Blueprint $table) {
            $table->id();
            $table->string('rut_empresa', 12)->nullable();
            $table->string('razon_social')->nullable();
            $table->string('giro')->nullable();
            $table->string('acteco', 10)->nullable();        // Código actividad económica
            $table->string('direccion')->nullable();
            $table->string('comuna')->nullable();
            $table->string('ciudad')->nullable();
            $table->enum('ambiente', ['certificacion', 'produccion'])->default('certificacion');
            $table->text('certificado_digital')->nullable();   // encrypted .p12 base64
            $table->text('clave_certificado')->nullable();     // encrypted
            $table->string('resolucion_fecha')->nullable();    // Fecha resolución SII
            $table->integer('resolucion_numero')->nullable();  // Numero resolución SII
            $table->string('libredte_hash')->nullable();       // API key LibreDTE
            $table->enum('documento_default', ['boleta', 'factura'])->default('boleta');
            $table->string('email_dte')->nullable();
            $table->integer('folio_siguiente_boleta')->default(1);
            $table->integer('folio_siguiente_factura')->default(1);
            $table->integer('folio_siguiente_nc')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_sii');
    }
};
