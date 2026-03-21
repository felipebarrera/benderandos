<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ofertas_empleo', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->text('descripcion');
            $table->string('cargo')->nullable();
            $table->string('departamento')->nullable();
            $table->string('ubicacion')->nullable();
            $table->enum('modalidad', ['presencial', 'remoto', 'hibrido'])->default('presencial');
            $table->enum('jornada', ['completa', 'media', 'part_time', 'freelance'])->default('completa');
            $table->integer('sueldo_min')->nullable();
            $table->integer('sueldo_max')->nullable();
            $table->boolean('mostrar_sueldo')->default(false);
            $table->text('requisitos')->nullable();
            $table->text('beneficios')->nullable();
            $table->enum('estado', ['borrador', 'publicada', 'pausada', 'cerrada'])->default('borrador');
            $table->date('fecha_cierre')->nullable();
            $table->timestamps();
        });

        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')->constrained('ofertas_empleo')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('email');
            $table->string('telefono', 20)->nullable();
            $table->string('rut', 12)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('cv_path')->nullable();
            $table->integer('pretension_salarial')->nullable();
            $table->enum('estado', [
                'recibida', 'preseleccionada', 'entrevista', 'evaluacion', 'oferta', 'contratada', 'descartada'
            ])->default('recibida');
            $table->text('notas_internas')->nullable();
            $table->integer('puntaje')->nullable();
            $table->timestamps();

            $table->index('estado');
        });

        Schema::create('entrevistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('postulaciones')->cascadeOnDelete();
            $table->foreignId('entrevistador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_hora');
            $table->enum('tipo', ['presencial', 'telefonica', 'video'])->default('presencial');
            $table->string('lugar')->nullable();
            $table->string('link_video')->nullable();
            $table->enum('estado', ['programada', 'realizada', 'cancelada', 'no_asistio'])->default('programada');
            $table->integer('puntaje')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrevistas');
        Schema::dropIfExists('postulaciones');
        Schema::dropIfExists('ofertas_empleo');
    }
};
