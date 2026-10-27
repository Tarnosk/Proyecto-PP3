<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->dateTime('fecha_hora_carga')->nullable()->after('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->dropColumn('fecha_hora_carga');
        });
    }
};