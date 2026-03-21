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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->nullable()->index();
            $table->string('codigo_referencia', 100)->nullable()->index();
            $table->string('nombre', 500);
            $table->text('descripcion')->nullable();
            $table->enum('tipo_producto', ['stock_fisico', 'servicio', 'renta', 'fraccionado', 'honorarios'])->default('stock_fisico');
            $table->string('marca')->nullable();
            $table->string('familia')->nullable();
            $table->string('subfamilia')->nullable();
            $table->string('zona', 50)->nullable();
            $table->string('proveedor')->nullable();
            $table->integer('valor_venta')->default(0);
            $table->integer('costo')->default(0);
            $table->decimal('cantidad', 10, 3)->default(0);
            $table->decimal('cantidad_minima', 10, 3)->default(0);
            $table->string('unidad_medida')->default('un');
            $table->boolean('fraccionable')->default(false);
            $table->enum('estado', ['activo', 'inactivo', 'agotado'])->default('activo');
            $table->foreignId('operario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
