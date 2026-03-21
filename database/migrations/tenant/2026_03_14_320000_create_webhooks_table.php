<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('url', 500);
            $table->json('eventos'); // ej: ['venta.creada', 'producto.actualizado']
            $table->string('secreto')->nullable(); // Para firmar el payload (HMAC)
            $table->boolean('activo')->default(true);
            $table->integer('fallos_consecutivos')->default(0);
            $table->timestamp('ultimo_intento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
