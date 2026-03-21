<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repartidores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('telefono', 20)->nullable();
            $table->string('vehiculo')->nullable();          // moto, bici, auto, pie
            $table->string('patente', 10)->nullable();
            $table->jsonb('zonas_cobertura')->nullable();     // ["zona_norte", "centro"]
            $table->boolean('disponible')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('zonas_envio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo', 20)->unique();
            $table->integer('costo_envio')->default(0);
            $table->integer('tiempo_estimado_min')->default(30); // minutos
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('repartidor_id')->nullable()->constrained('repartidores')->nullOnDelete();
            $table->foreignId('zona_envio_id')->nullable()->constrained('zonas_envio')->nullOnDelete();
            $table->enum('estado', [
                'pendiente', 'asignada', 'en_preparacion', 'en_camino', 'entregada', 'fallida', 'cancelada'
            ])->default('pendiente');
            $table->string('direccion_entrega');
            $table->string('comuna_entrega')->nullable();
            $table->string('telefono_contacto', 20)->nullable();
            $table->string('nombre_receptor')->nullable();
            $table->text('instrucciones')->nullable();
            $table->integer('costo_envio')->default(0);
            $table->timestamp('asignada_at')->nullable();
            $table->timestamp('en_preparacion_at')->nullable();
            $table->timestamp('en_camino_at')->nullable();
            $table->timestamp('entregada_at')->nullable();
            $table->text('motivo_fallo')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('uuid');
        });

        Schema::create('tracking_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas')->cascadeOnDelete();
            $table->string('estado');
            $table->string('descripcion')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_entregas');
        Schema::dropIfExists('entregas');
        Schema::dropIfExists('zonas_envio');
        Schema::dropIfExists('repartidores');
    }
};
