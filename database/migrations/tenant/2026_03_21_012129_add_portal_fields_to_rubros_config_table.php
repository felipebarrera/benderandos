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
        Schema::table('rubros_config', function (Blueprint $table) {
            $table->boolean('portal_activo')->default(true)->after('modulos_activos');
            $table->text('portal_descripcion')->nullable()->after('portal_activo');
            $table->string('portal_horario')->nullable()->after('portal_descripcion');
            $table->string('portal_telefono', 20)->nullable()->after('portal_horario');
            $table->text('portal_direccion')->nullable()->after('portal_telefono');
            $table->string('portal_logo_url', 500)->nullable()->after('portal_direccion');
            $table->string('portal_color_primario', 7)->default('#00e5a0')->after('portal_logo_url');
            $table->string('portal_telegram_url')->nullable()->after('portal_color_primario');
            $table->string('portal_whatsapp_numero', 20)->nullable()->after('portal_telegram_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rubros_config', function (Blueprint $table) {
            $table->dropColumn([
                'portal_activo',
                'portal_descripcion',
                'portal_horario',
                'portal_telefono',
                'portal_direccion',
                'portal_logo_url',
                'portal_color_primario',
                'portal_telegram_url',
                'portal_whatsapp_numero',
            ]);
        });
    }
};
