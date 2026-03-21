<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('rut', 12)->nullable()->index();
            $table->string('nombre');
            $table->string('razon_social')->nullable();
            $table->string('giro')->nullable();
            $table->string('direccion')->nullable();
            $table->string('comuna')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_telefono', 20)->nullable();
            $table->integer('plazo_pago_dias')->default(30);
            $table->decimal('descuento_volumen_pct', 5, 2)->default(0);
            $table->integer('monto_minimo_oc')->default(0);
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('productos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('codigo_proveedor')->nullable();
            $table->integer('precio_unitario')->default(0);
            $table->integer('precio_anterior')->nullable();
            $table->integer('cantidad_minima_pedido')->default(1);
            $table->integer('dias_entrega')->default(3);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->unique(['proveedor_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_proveedor');
        Schema::dropIfExists('proveedores');
    }
};
