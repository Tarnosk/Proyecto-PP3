<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PC07 - Persistir el motivo del rechazo de una sugerencia de reposición.
 *
 * El endpoint POST /api/sugerencias-reposicion/{id}/rechazar ya recibía `motivo`
 * en el body, pero se descartaba: la tabla no tenía dónde guardarlo y
 * GeneradorSugerenciasReposicionService::rechazarSugerencia() ni lo recibía.
 * Sin esta columna el rechazo no era auditable.
 *
 * Va en una migración aparte (y no editando la de create) porque
 * SUGERENCIA_COMPRA ya está creada y migrada en los ambientes existentes.
 *
 * El campo es nullable a propósito: el motivo es recomendable pero la H.U. no lo
 * exige, así que rechazar sin enviarlo sigue siendo válido (queda NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('SUGERENCIA_COMPRA')) {
            return;
        }

        if (Schema::hasColumn('SUGERENCIA_COMPRA', 'motivo_rechazo')) {
            return;
        }

        Schema::table('SUGERENCIA_COMPRA', function (Blueprint $table) {
            $table->text('motivo_rechazo')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('SUGERENCIA_COMPRA')) {
            return;
        }

        if (!Schema::hasColumn('SUGERENCIA_COMPRA', 'motivo_rechazo')) {
            return;
        }

        Schema::table('SUGERENCIA_COMPRA', function (Blueprint $table) {
            $table->dropColumn('motivo_rechazo');
        });
    }
};