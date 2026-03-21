<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campanas_marketing', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo_accion', ['descuento_porcentaje', 'descuento_fijo', 'dos_por_uno', 'abrir_whatsapp', 'encuesta', 'link_externo']);
            $table->integer('valor_descuento')->nullable(); // % o $
            $table->string('link_destino')->nullable();
            $table->string('mensaje_whatsapp')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['activa', 'pausada', 'finalizada'])->default('activa');
            $table->integer('limite_usos')->nullable(); // null = sin limite
            $table->integer('usos_actuales')->default(0);
            $table->string('codigo_pos')->nullable()->unique(); // Código para cajero
            $table->timestamps();
        });

        Schema::create('qr_campanas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas_marketing')->cascadeOnDelete();
            $table->string('uuid')->unique(); // Para la URL pública (ej: /qr/u1234abcd)
            $table->string('ubicacion_fisica')->nullable(); // Ej: "Mesa 1", "Puerta", "Flyer Centro"
            $table->string('qr_url')->nullable(); // URL a la imagen PNG generada
            $table->timestamps();
        });

        Schema::create('escaneos_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_id')->constrained('qr_campanas')->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->timestamp('fecha_escaneo');
            $table->boolean('convertido')->default(false); // Si se usó el código en el POS
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escaneos_qr');
        Schema::dropIfExists('qr_campanas');
        Schema::dropIfExists('campanas_marketing');
    }
};
