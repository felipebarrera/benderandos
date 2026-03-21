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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('nombre')->nullable();
            $table->string('rut_empresa')->nullable();
            $table->string('estado')->default('activo');
            $table->timestamp('trial_hasta')->nullable();
            $table->string('whatsapp_admin')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'rut_empresa', 'estado', 'trial_hasta', 'whatsapp_admin', 'plan_id']);
        });
    }
};
