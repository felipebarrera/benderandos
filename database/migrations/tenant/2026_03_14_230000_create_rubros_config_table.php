<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rubros_config', function (Blueprint $table) {
            $table->id();
            $table->string('industria_preset', 50);
            $table->string('industria_nombre', 255)->nullable();
            
            // modulos_activos TEXT[]
            // En Laravel 11 / PG16 usamos raw para tipos específicos
            $table->jsonb('modulos_activos')->default('["M01"]');
            
            $table->string('label_operario', 100)->default('Vendedor');
            $table->string('label_cliente', 100)->default('Cliente');
            $table->string('label_cajero', 100)->default('Cajero');
            $table->string('label_producto', 100)->default('Producto');
            $table->string('label_recurso', 100)->default('Recurso');
            $table->string('label_nota', 100)->nullable();
            
            $table->string('documento_default', 50)->default('boleta');
            $table->boolean('requiere_rut')->default(false);
            $table->boolean('boleta_sin_detalle')->default(false);
            
            $table->boolean('tiene_stock_fisico')->default(true);
            $table->boolean('tiene_renta')->default(false);
            $table->boolean('tiene_renta_hora')->default(false);
            $table->boolean('tiene_servicios')->default(false);
            $table->boolean('tiene_agenda')->default(false);
            $table->boolean('tiene_delivery')->default(false);
            $table->boolean('tiene_comandas')->default(false);
            $table->boolean('tiene_ot')->default(false);
            $table->boolean('tiene_membresias')->default(false);
            $table->boolean('tiene_notas_cifradas')->default(false);
            $table->boolean('tiene_fiado')->default(false);
            $table->boolean('tiene_fraccionado')->default(false);
            $table->boolean('tiene_descuento_vol')->default(false);
            
            // recurso_estados TEXT[]
            $table->jsonb('recurso_estados')->default('["libre","ocupado"]');
            
            $table->integer('alerta_vencimiento_min')->default(15);
            $table->boolean('log_acceso_notas')->default(false);
            $table->boolean('cifrado_notas')->default(false);
            $table->string('accent_color', 7)->default('#3b82f6');
            $table->string('recurso_historial', 50)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubros_config');
    }
};
