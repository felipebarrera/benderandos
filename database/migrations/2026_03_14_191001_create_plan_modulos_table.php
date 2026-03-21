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
        Schema::create('plan_modulos', function (Blueprint $table) {
            $table->id();
            $table->string('modulo_id', 10)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('precio_mensual')->default(0); // en CLP
            $table->boolean('es_base')->default(false);
            $table->jsonb('requiere')->default('[]'); // dependencias
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_modulos_historial', function (Blueprint $table) {
            $table->id();
            $table->string('modulo_id');
            $table->foreign('modulo_id')->references('modulo_id')->on('plan_modulos')->onDelete('cascade');
            $table->integer('precio_anterior');
            $table->integer('precio_nuevo');
            $table->foreignId('cambiado_por')->nullable()->constrained('super_admins'); // User ID del super admin
            $table->date('aplica_desde')->nullable();
            $table->timestamps();
        });

        // Insert Default Modules from H19 Spec
        $modulos = [
            ['modulo_id' => 'M01', 'nombre' => 'Venta simple', 'precio_mensual' => 0, 'es_base' => true],
            ['modulo_id' => 'M02', 'nombre' => 'Venta multi-operario', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M03', 'nombre' => 'Stock físico', 'precio_mensual' => 0, 'es_base' => true],
            ['modulo_id' => 'M04', 'nombre' => 'Stock fraccionado', 'precio_mensual' => 4990, 'es_base' => false, 'requiere' => json_encode(['M03'])],
            ['modulo_id' => 'M05', 'nombre' => 'Renta / Arriendo', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M06', 'nombre' => 'Renta por hora', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M07', 'nombre' => 'Servicios sin stock', 'precio_mensual' => 0, 'es_base' => true],
            ['modulo_id' => 'M08', 'nombre' => 'Agenda / Citas', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M09', 'nombre' => 'Honorarios', 'precio_mensual' => 4990, 'es_base' => false],
            ['modulo_id' => 'M10', 'nombre' => 'Notas cifradas', 'precio_mensual' => 4990, 'es_base' => false],
            ['modulo_id' => 'M11', 'nombre' => 'Fiado / Crédito cliente', 'precio_mensual' => 4990, 'es_base' => false],
            ['modulo_id' => 'M12', 'nombre' => 'Encargos / Reservas', 'precio_mensual' => 4990, 'es_base' => false],
            ['modulo_id' => 'M13', 'nombre' => 'Delivery / Envíos', 'precio_mensual' => 14990, 'es_base' => false],
            ['modulo_id' => 'M14', 'nombre' => 'Habitaciones / Recursos', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M15', 'nombre' => 'Comandas / Cocina', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M16', 'nombre' => 'Recetas / Ingredientes', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M17', 'nombre' => 'Pedido remoto WhatsApp', 'precio_mensual' => 14990, 'es_base' => false],
            ['modulo_id' => 'M18', 'nombre' => 'Compras / Proveedores', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M19', 'nombre' => 'Inventario avanzado', 'precio_mensual' => 9990, 'es_base' => false, 'requiere' => json_encode(['M18'])],
            ['modulo_id' => 'M20', 'nombre' => 'SII / Facturación DTE', 'precio_mensual' => 14990, 'es_base' => false],
            ['modulo_id' => 'M21', 'nombre' => 'RRHH / Asistencia', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M22', 'nombre' => 'Liquidaciones', 'precio_mensual' => 9990, 'es_base' => false, 'requiere' => json_encode(['M21'])],
            ['modulo_id' => 'M23', 'nombre' => 'Reclutamiento', 'precio_mensual' => 9990, 'es_base' => false, 'requiere' => json_encode(['M21'])],
            ['modulo_id' => 'M24', 'nombre' => 'Marketing QR', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M25', 'nombre' => 'Portal cliente web', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M26', 'nombre' => 'Descuento por volumen', 'precio_mensual' => 4990, 'es_base' => false],
            ['modulo_id' => 'M27', 'nombre' => 'Multi-sucursal', 'precio_mensual' => 19990, 'es_base' => false],
            ['modulo_id' => 'M28', 'nombre' => 'Órdenes de trabajo', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M29', 'nombre' => 'Historial por recurso', 'precio_mensual' => 4990, 'es_base' => false],
            ['modulo_id' => 'M30', 'nombre' => 'Membresías / Suscripciones', 'precio_mensual' => 9990, 'es_base' => false],
            ['modulo_id' => 'M31', 'nombre' => 'Venta Software SaaS', 'precio_mensual' => 24990, 'es_base' => false],
            ['modulo_id' => 'M32', 'nombre' => 'CRM Modular', 'precio_mensual' => 9990, 'es_base' => false],
        ];

        $modulos = array_map(function ($modulo) {
            return [
                'modulo_id'      => $modulo['modulo_id'],
                'nombre'         => $modulo['nombre'],
                'precio_mensual' => $modulo['precio_mensual'],
                'es_base'        => $modulo['es_base'],
                'requiere'       => $modulo['requiere'] ?? '[]',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }, $modulos);

        DB::table('plan_modulos')->insert($modulos);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_modulos_historial');
        Schema::dropIfExists('plan_modulos');
    }
};
