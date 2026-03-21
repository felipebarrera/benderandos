<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Esta migración corre en la conexión central (landlord)
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('audit_logs', function (Blueprint $table) {
            // Hacer user_id nullable (actores externos al tenant no tienen user en la tabla users)
            if (Schema::hasColumn('audit_logs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }

            if (!Schema::hasColumn('audit_logs', 'actor_type')) {
                $table->string('actor_type')->default('user')->after('user_id');
            }
            if (!Schema::hasColumn('audit_logs', 'actor_email')) {
                $table->string('actor_email')->nullable()->after('actor_type');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['actor_type', 'actor_email']);
            // If dropping, we might want to revert user_id to not nullable if that was its original state,
            // but in this project it seems it was already nullable in some versions.
            // $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
