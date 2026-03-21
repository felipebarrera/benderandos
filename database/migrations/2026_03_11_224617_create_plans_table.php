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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->integer('precio_mensual_clp')->default(0);
            $table->integer('max_usuarios')->default(0);
            $table->integer('max_productos')->default(0);
            $table->jsonb('features')->default('{}');
            $table->timestamps();
        });

        // Seed plans básicos
        \Illuminate\Support\Facades\DB::table('plans')->insert([
            ['nombre' => 'Trial', 'precio_mensual_clp' => 0, 'max_usuarios' => 3, 'max_productos' => 100],
            ['nombre' => 'Básico', 'precio_mensual_clp' => 19990, 'max_usuarios' => 5, 'max_productos' => 1000],
            ['nombre' => 'Pro', 'precio_mensual_clp' => 39990, 'max_usuarios' => 0, 'max_productos' => 0],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
