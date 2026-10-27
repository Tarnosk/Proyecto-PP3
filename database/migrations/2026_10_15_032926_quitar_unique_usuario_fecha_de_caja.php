<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CAJA', function (Blueprint $table) {
            $table->index('id_usuario', 'idx_caja_usuario');
            $table->dropUnique('uq_caja_usuario_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('CAJA', function (Blueprint $table) {
            $table->unique(['id_usuario', 'fecha'], 'uq_caja_usuario_fecha');
            $table->dropIndex('idx_caja_usuario');
        });
    }
};