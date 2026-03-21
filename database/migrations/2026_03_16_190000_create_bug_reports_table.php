<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BUG-008: Create bug_reports table for QA tracking.
     */
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->string('bug_id', 40)->unique();
            $table->string('tc_id', 20)->nullable();
            $table->string('tipo', 20)->nullable();        // E-AUTH, E-PERM, E-UI, etc.
            $table->string('capa', 20)->nullable();        // db, api, ui, config
            $table->text('descripcion')->nullable();
            $table->text('detalle')->nullable();
            $table->text('url')->nullable();
            $table->string('http_esperado', 10)->nullable();
            $table->string('http_obtenido', 10)->nullable();
            $table->string('estado', 20)->default('abierto'); // abierto, en_progreso, resuelto
            $table->string('prioridad', 10)->default('medio'); // critico, alto, medio, bajo
            $table->timestamp('encontrado')->useCurrent();
            $table->timestamp('resuelto_en')->nullable();
            $table->string('fix_commit', 100)->nullable();
            $table->boolean('exportado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
