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
        // En Postgres, para cambiar un enum/check constraint de Laravel:
        // Necesitamos eliminar la restricción anterior si existe y crear una nueva.
        
        DB::statement("ALTER TABLE ventas DROP CONSTRAINT IF EXISTS ventas_estado_check");
        DB::statement("ALTER TABLE ventas ADD CONSTRAINT ventas_estado_check CHECK (estado::text = ANY (ARRAY['abierta'::text, 'en_caja'::text, 'pagada'::text, 'anulada'::text, 'fiada'::text, 'remota_pendiente'::text, 'remota_pagada'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE ventas DROP CONSTRAINT IF EXISTS ventas_estado_check");
        DB::statement("ALTER TABLE ventas ADD CONSTRAINT ventas_estado_check CHECK (estado::text = ANY (ARRAY['abierta'::text, 'pagada'::text, 'anulada'::text, 'fiada'::text]))");
    }
};
