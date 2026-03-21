<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. saas_planes
        Schema::create('saas_planes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->bigInteger('precio_mensual');
            $table->bigInteger('precio_anual')->nullable();
            $table->integer('max_usuarios')->default(5);
            $table->integer('max_productos')->default(0);
            $table->json('modulos_incluidos'); // Almacená array de módulos (e.g. ['M01', 'M03'])
            $table->json('modulos_addon')->nullable();
            $table->string('soporte_nivel', 50)->default('email');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. saas_clientes
        Schema::create('saas_clientes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('tenant_uuid')->nullable(); // UUID del tenant real en DB central si ya está creado
            $table->string('razon_social');
            $table->string('rut', 20)->nullable();
            $table->string('industria', 100)->nullable();
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_whatsapp', 20)->nullable();
            $table->string('contacto_email')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('saas_planes')->nullOnDelete();
            $table->json('modulos_addon')->nullable();
            $table->string('estado', 50)->default('trial'); // trial|activo|moroso|suspendido|cancelado
            $table->date('fecha_inicio');
            $table->date('fecha_trial_fin')->nullable();
            $table->date('fecha_proximo_cobro')->nullable();
            $table->string('ciclo_facturacion', 20)->default('mensual');
            $table->bigInteger('precio_actual'); // Precio re-negociado o default del plan
            $table->decimal('descuento_pct', 5, 2)->default(0);
            $table->foreignId('ejecutivo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas_crm')->nullable();
            $table->timestamps();
        });

        // 3. saas_pipeline
        Schema::create('saas_pipeline', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social')->nullable();
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_whatsapp', 20)->nullable();
            $table->string('contacto_email')->nullable();
            $table->string('industria', 100)->nullable();
            $table->string('etapa', 50)->default('nuevo'); // nuevo|contactado|demo_agendada|demo_hecha|propuesta|negociacion|ganado|perdido
            $table->foreignId('plan_interes')->nullable()->constrained('saas_planes')->nullOnDelete();
            $table->bigInteger('valor_estimado')->nullable();
            $table->integer('probabilidad_pct')->default(20);
            $table->foreignId('ejecutivo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_proximo_contacto')->nullable();
            $table->text('motivo_perdida')->nullable();
            $table->text('notas')->nullable();
            $table->string('origen', 50)->nullable(); // e.g., whatsapp, qr, referido, web
            $table->timestamps();
        });

        // 4. saas_actividades (CRM activities)
        Schema::create('saas_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('saas_clientes')->cascadeOnDelete();
            $table->foreignId('pipeline_id')->nullable()->constrained('saas_pipeline')->cascadeOnDelete();
            $table->string('tipo', 50)->nullable(); // llamada, wa, email, demo, nota
            $table->text('descripcion');
            $table->text('resultado')->nullable();
            $table->foreignId('ejecutivo_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('fecha_actividad');
            $table->timestamps();
        });

        // 5. saas_cobros (billing recurrents)
        Schema::create('saas_cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('saas_clientes')->cascadeOnDelete();
            $table->date('periodo'); // e.g., 2026-03-01
            $table->bigInteger('monto');
            $table->bigInteger('descuento')->default(0);
            $table->bigInteger('total');
            $table->string('estado', 50)->default('pendiente'); // pendiente|pagado|vencido|anulado
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->string('metodo_pago', 50)->nullable();
            $table->foreignId('dte_id')->nullable()->constrained('dte_emitidos')->nullOnDelete();
            $table->string('referencia_pago')->nullable();
            $table->timestamps();
        });

        // 6. saas_metricas (SaaS snapshsots)
        Schema::create('saas_metricas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->bigInteger('mrr')->nullable();
            $table->bigInteger('arr')->nullable();
            $table->integer('tenants_activos')->nullable();
            $table->integer('tenants_trial')->nullable();
            $table->integer('tenants_morosos')->nullable();
            $table->integer('nuevos_mes')->nullable();
            $table->integer('cancelados_mes')->nullable();
            $table->decimal('churn_rate', 5, 2)->nullable();
            $table->bigInteger('ltv_promedio')->nullable();
            $table->bigInteger('arpu')->nullable();
            $table->timestamps();
        });

        // 7. saas_demos
        Schema::create('saas_demos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->nullable()->constrained('saas_pipeline')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora');
            $table->string('modalidad', 30)->default('videollamada');
            $table->string('link_reunion', 500)->nullable();
            $table->foreignId('ejecutivo_id')->constrained('users')->cascadeOnDelete();
            $table->integer('duracion_min')->default(45);
            $table->boolean('asistio')->nullable();
            $table->text('notas_post_demo')->nullable();
            $table->text('siguiente_paso')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_demos');
        Schema::dropIfExists('saas_metricas');
        Schema::dropIfExists('saas_cobros');
        Schema::dropIfExists('saas_actividades');
        Schema::dropIfExists('saas_pipeline');
        Schema::dropIfExists('saas_clientes');
        Schema::dropIfExists('saas_planes');
    }
};
