<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria')->nullable();           // entrada, plato_fondo, postre, bebestible
            $table->integer('porciones_por_batch')->default(1);
            $table->integer('tiempo_preparacion_min')->default(0);
            $table->integer('costo_mano_obra')->default(0);    // CLP por batch
            $table->decimal('porcentaje_merma', 5, 2)->default(0);
            $table->integer('costo_por_porcion')->default(0);  // calculado
            $table->integer('precio_venta')->default(0);
            $table->decimal('margen_pct', 5, 2)->default(0);   // calculado
            $table->text('instrucciones')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('ingredientes_receta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad', 10, 3);
            $table->string('unidad', 20)->default('unidad');   // unidad, kg, gr, lt, ml
            $table->integer('costo_unitario')->default(0);     // snapshot del costo al momento
            $table->integer('costo_total')->default(0);        // cantidad * costo_unitario
            $table->timestamps();
        });

        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas');
            $table->foreignId('usuario_id')->constrained('users');
            $table->integer('cantidad_batches')->default(1);
            $table->integer('porciones_producidas')->default(0);
            $table->integer('costo_total')->default(0);
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'cancelada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('items_produccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->constrained('producciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad_necesaria', 10, 3);
            $table->decimal('cantidad_usada', 10, 3)->default(0);
            $table->decimal('cantidad_merma', 10, 3)->default(0);
            $table->boolean('stock_suficiente')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_produccion');
        Schema::dropIfExists('producciones');
        Schema::dropIfExists('ingredientes_receta');
        Schema::dropIfExists('recetas');
    }
};
