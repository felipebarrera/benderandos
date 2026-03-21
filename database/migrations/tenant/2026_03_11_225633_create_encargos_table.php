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
        Schema::create('encargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->text('descripcion');
            $table->integer('valor')->default(0);
            $table->integer('abono')->default(0);
            $table->enum('estado', ['pendiente', 'listo', 'entregado', 'cancelado'])->default('pendiente');
            $table->timestamp('fecha_llegada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encargos');
    }
};
