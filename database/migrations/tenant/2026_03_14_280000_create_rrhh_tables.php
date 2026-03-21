<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('rut', 12)->nullable()->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion')->nullable();
            $table->string('comuna', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('cargo')->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_termino')->nullable();
            $table->enum('tipo_contrato', ['indefinido', 'plazo_fijo', 'honorarios', 'part_time'])->default('indefinido');
            $table->integer('sueldo_base')->default(0);            // CLP
            $table->string('afp', 50)->nullable();                 // AFP Modelo, Habitat, etc.
            $table->decimal('afp_pct', 5, 2)->default(10.0);       // % cotización
            $table->string('salud', 50)->nullable();                // FONASA o ISAPRE nombre
            $table->enum('salud_tipo', ['fonasa', 'isapre'])->default('fonasa');
            $table->decimal('salud_pct', 5, 2)->default(7.0);      // %
            $table->decimal('salud_uf', 8, 4)->default(0);          // cotización UF para isapre
            $table->boolean('mutual')->default(true);               // Mutual de Seguridad
            $table->decimal('mutual_pct', 5, 2)->default(0.93);     // %
            $table->string('horario', 50)->default('09:00-18:00');  // Horario laboral
            $table->integer('dias_vacaciones_anuales')->default(15);
            $table->integer('dias_vacaciones_pendientes')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->integer('minutos_atraso')->default(0);
            $table->integer('minutos_extra')->default(0);
            $table->decimal('horas_trabajadas', 5, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['empleado_id', 'fecha']);
        });

        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias_solicitados');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cancelada'])->default('pendiente');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->timestamps();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('tipo', ['medico', 'personal', 'administrativo', 'duelo', 'otro'])->default('personal');
            $table->integer('horas')->default(8);
            $table->boolean('con_goce')->default(false);
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo')->nullable();
            $table->timestamps();
        });

        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->integer('anio');
            $table->integer('mes');
            $table->integer('dias_trabajados')->default(30);
            $table->integer('sueldo_base')->default(0);
            $table->integer('horas_extra_monto')->default(0);
            $table->integer('bonos')->default(0);
            $table->integer('total_haberes')->default(0);          // sueldo + extras + bonos
            $table->integer('dcto_afp')->default(0);
            $table->integer('dcto_salud')->default(0);
            $table->integer('dcto_mutual')->default(0);
            $table->integer('dcto_sis')->default(0);               // Seguro Invalidez y Sobrevivencia
            $table->integer('dcto_cesantia')->default(0);          // Seguro Cesantía
            $table->integer('base_imponible')->default(0);
            $table->integer('impuesto_unico')->default(0);
            $table->integer('total_descuentos')->default(0);
            $table->integer('sueldo_liquido')->default(0);
            $table->enum('estado', ['borrador', 'aprobada', 'pagada'])->default('borrador');
            $table->timestamps();

            $table->unique(['empleado_id', 'anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('vacaciones');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('empleados');
    }
};
