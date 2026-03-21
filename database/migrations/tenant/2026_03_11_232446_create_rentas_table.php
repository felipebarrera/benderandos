<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_venta_id')->constrained('items_venta');
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');
            $table->timestamp('inicio_real')->nullable();
            $table->timestamp('fin_programado');
            $table->timestamp('fin_real')->nullable();
            $table->enum('estado', ['activa', 'vencida', 'devuelta', 'extendida'])->default('activa');
            $table->bigInteger('cargo_extra')->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentas');
    }
};
