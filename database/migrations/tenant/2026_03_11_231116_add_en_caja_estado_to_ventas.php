<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // En lugar de tipo, Laravel crea un CHECK constraint para los enums numéricos
        DB::statement('ALTER TABLE ventas DROP CONSTRAINT IF EXISTS ventas_estado_check');
        DB::statement("ALTER TABLE ventas ADD CONSTRAINT ventas_estado_check CHECK (estado::text = ANY (ARRAY['abierta'::character varying, 'en_caja'::character varying, 'pagada'::character varying, 'anulada'::character varying, 'fiada'::character varying]::text[]))");
    }

    public function down(): void
    {
        // PostgreSQL no permite eliminar valores de enum fácilmente
        // Se deja como está por seguridad
    }
};
