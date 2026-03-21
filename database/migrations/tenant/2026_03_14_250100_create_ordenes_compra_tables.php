<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('autorizado_por')->nullable()->constrained('users');
            $table->enum('estado', [
                'borrador', 'autorizada', 'enviada', 'parcial', 'completa', 'anulada'
            ])->default('borrador');
            $table->integer('subtotal')->default(0);
            $table->decimal('descuento_pct', 5, 2)->default(0);
            $table->integer('descuento_monto')->default(0);
            $table->integer('total')->default(0);
            $table->date('fecha_entrega_esperada')->nullable();
            $table->text('notas')->nullable();
            $table->string('origen')->default('manual'); // manual | auto_stock
            $table->timestamp('autorizada_at')->nullable();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamps();

            $table->index('estado');
        });

        Schema::create('items_orden_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad_solicitada', 10, 3);
            $table->decimal('cantidad_recibida', 10, 3)->default(0);
            $table->integer('precio_unitario')->default(0);
            $table->integer('total_item')->default(0);
            $table->timestamps();
        });

        Schema::create('recepciones_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra');
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('numero_guia')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('items_recepcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('recepciones_compra')->cascadeOnDelete();
            $table->foreignId('item_orden_id')->constrained('items_orden_compra');
            $table->foreignId('producto_id')->constrained('productos');
            $table->decimal('cantidad_recibida', 10, 3);
            $table->decimal('cantidad_rechazada', 10, 3)->default(0);
            $table->string('motivo_rechazo')->nullable();
            $table->string('lote')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_recepcion');
        Schema::dropIfExists('recepciones_compra');
        Schema::dropIfExists('items_orden_compra');
        Schema::dropIfExists('ordenes_compra');
    }
};
