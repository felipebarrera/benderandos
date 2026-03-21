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
        // It is safer to convert enum to string in postgres to avoid enum type errors
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('estado', 20)->default('trial')->change();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->jsonb('modulos_activos')->default('["M01"]');
            $table->integer('precio_calculado')->nullable();
            $table->date('trial_termina')->nullable();
            $table->integer('dias_gracia')->default(3);
            $table->integer('descuento_pct')->default(0);
            $table->string('descuento_motivo')->nullable();
            $table->string('link_pago', 500)->nullable();
        });
        
        // Ensure default 'estado' is updated correctly in the existing scheme
        DB::statement("ALTER TABLE subscriptions ALTER COLUMN estado DROP DEFAULT");
        DB::statement("ALTER TABLE subscriptions ALTER COLUMN estado SET DEFAULT 'trial'");

        // Modulos Activos Tenant Table
        Schema::create('tenant_modulos_activos', function (Blueprint $table) {
            // Using tenant context or referencing subscription ID
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('modulo_id', 10);
            $table->foreign('modulo_id')->references('modulo_id')->on('plan_modulos')->onDelete('cascade');
            $table->boolean('activo')->default(false);
            $table->timestamp('activado_en')->useCurrent();
            $table->string('activado_por', 100)->default('onboarding');
            $table->integer('precio_al_activar')->default(0);
            
            $table->unique(['tenant_id', 'modulo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_modulos_activos');
        
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'modulos_activos',
                'precio_calculado',
                'trial_termina',
                'dias_gracia',
                'descuento_pct',
                'descuento_motivo',
                'link_pago'
            ]);
        });
        
        // Note: Removing ENUM values in Postgres is complex and normally avoided in down migrations
        DB::statement("ALTER TABLE subscriptions ALTER COLUMN estado SET DEFAULT 'activa'");
    }
};
