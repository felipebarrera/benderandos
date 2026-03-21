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
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->enum('tipo', ['venta', 'compra', 'ajuste_manual', 'devolucion', 'merma']);
            $table->decimal('cantidad', 10, 3);
            $table->decimal('stock_antes', 10, 3);
            $table->decimal('stock_despues', 10, 3);
            $table->unsignedBigInteger('referencia_id')->nullable(); 
            $table->foreignId('usuario_id')->constrained('users');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
