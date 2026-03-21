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
        Schema::create('bot_config', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_bot', 100)->default('Asistente BenderAnd');
            $table->enum('personalidad', ['formal', 'amigable', 'tecnico', 'personalizado'])->default('formal');
            $table->boolean('activo')->default(true);
            
            $table->jsonb('horario_atencion')->nullable(); // Ej: {"lunes": {"inicio": "09:00", "fin": "18:00"}}
            $table->jsonb('intenciones_activas')->nullable(); // Ej: ["agendar_cita", "consultar_precio"]
            $table->jsonb('faq')->nullable(); // Ej: [{"pregunta": "...", "respuesta": "..."}]
            
            $table->string('whatsapp_numero', 20)->nullable();
            $table->text('mensaje_bienvenida')->nullable();
            $table->text('mensaje_fuera_horario')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_config');
    }
};
