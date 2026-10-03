<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PC07 - Generar una orden de compra a partir de una sugerencia de reposición.
 *
 * Sin esta columna la sugerencia sólo podía quedar en estado 'procesada', sin
 * registrar qué orden salió de ella: no había forma de saber si una sugerencia
 * ya había generado una orden, ni de navegar desde la sugerencia hacia la orden.
 *
 * id_orden_compra:
 *  - traza el vínculo sugerencia -> orden (auditoría de la decisión de reposición);
 *  - actúa como candado: el servicio sólo genera la orden si la sugerencia sigue
 *    en 'pendiente', y resolver() exige el mismo estado, así que el segundo intento
 *    falla en lugar de duplicar la orden.
 *
 * Va en una migración aparte (y no editando la de create) porque SUGERENCIA_COMPRA
 * ya está creada y migrada en los ambientes existentes.
 *
 * Es nullable a propósito: las sugerencias procesadas o rechazadas sin generar
 * orden siguen siendo válidas (queda NULL).
 *
 * `integer` signed (no foreignId) porque ORDEN_COMPRA.id_orden es INT(11) en el
 * esquema ACTUALIZACION; con unsigned la MySQL rechaza la FK (errno: 150).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('SUGERENCIA_COMPRA')) {
            return;
        }

        if (Schema::hasColumn('SUGERENCIA_COMPRA', 'id_orden_compra')) {
            return;
        }

        Schema::table('SUGERENCIA_COMPRA', function (Blueprint $table) {
            $table->integer('id_orden_compra')
                ->nullable()
                ->after('estado')
                ->comment('Orden de compra generada a partir de esta sugerencia (PC07)');

            $table->index('id_orden_compra', 'sugerencia_compra_id_orden_compra_index');

            $table->foreign('id_orden_compra')
                ->references('id_orden')
                ->on('ORDEN_COMPRA')
                ->onDelete('SET NULL');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('SUGERENCIA_COMPRA')) {
            return;
        }

        if (!Schema::hasColumn('SUGERENCIA_COMPRA', 'id_orden_compra')) {
            return;
        }

        Schema::table('SUGERENCIA_COMPRA', function (Blueprint $table) {
            $table->dropForeign(['id_orden_compra']);
            $table->dropIndex('sugerencia_compra_id_orden_compra_index');
            $table->dropColumn('id_orden_compra');
        });
    }
};
