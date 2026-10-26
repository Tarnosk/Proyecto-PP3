<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NO reemplaza la migración original de CAJA_MOVIMIENTO (2026_09_03_233544) —
// esto es un ALTER TABLE nuevo porque esa migración puede ya estar corrida en
// entornos locales del equipo. Agrega id_caja como nullable a propósito: si ya
// hay filas de prueba cargadas sin id_caja, no se puede forzar NOT NULL sin
// antes backfillear. Una vez que todo movimiento nuevo se cree siempre con
// id_caja, evaluar una migración de limpieza que la vuelva NOT NULL.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->unsignedInteger('id_caja')->nullable()->after('id_movimiento_caja');
            $table->foreign('id_caja')->references('id_caja')->on('CAJA');
        });
    }

    public function down(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->dropForeign(['id_caja']);
            $table->dropColumn('id_caja');
        });
    }
};