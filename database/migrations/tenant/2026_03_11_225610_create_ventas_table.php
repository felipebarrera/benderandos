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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('estado', ['abierta', 'pagada', 'anulada', 'fiada'])->default('abierta');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('cajero_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tipo_pago_id')->nullable()->constrained('tipos_pago');
            $table->integer('subtotal')->default(0);
            $table->integer('descuento_monto')->default(0);
            $table->decimal('descuento_pct', 5, 2)->default(0);
            $table->integer('total')->default(0);
            $table->string('tipo_entrega')->default('presencial');
            $table->boolean('es_deuda')->default(false);
            $table->string('numero_documento')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('origen')->default('presencial');
            $table->text('notas')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
