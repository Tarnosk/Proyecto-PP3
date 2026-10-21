<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PV07 - Conservar el historial de modificaciones del plazo de entrega.
 *
 * PROVEEDOR.plazo_entrega_dias guarda solo el valor vigente; este criterio de
 * aceptación pedía conservar el historial, que no existía en el esquema.
 * Especificación tomada de ACTUALIZACION\Consideracion (Claude)\DistribuidoraPyB_dbdiagram.dbml
 * (tabla HISTORIAL_PLAZO_PROVEEDOR).
 *
 * plazo_anterior_dias es nullable porque el primer registro de un proveedor
 * pasa de "sin plazo" (NULL) a un valor.
 *
 * La FK usa `integer` signed porque PROVEEDOR.id_proveedor y USUARIO.id_usuario
 * son INT(11) en el esquema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('HISTORIAL_PLAZO_PROVEEDOR')) {
            return;
        }

        Schema::create('HISTORIAL_PLAZO_PROVEEDOR', function (Blueprint $table) {
            $table->increments('id_historial_plazo');
            $table->integer('id_proveedor');
            $table->integer('plazo_anterior_dias')->nullable();
            $table->integer('plazo_nuevo_dias');
            $table->dateTime('fecha_cambio')->useCurrent();
            $table->integer('id_usuario');

            $table->index(['id_proveedor', 'fecha_cambio'], 'idx_historial_plazo_proveedor_fecha');

            $table->foreign('id_proveedor')->references('id_proveedor')->on('PROVEEDOR')->onDelete('cascade');
            $table->foreign('id_usuario')->references('id_usuario')->on('USUARIO');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('HISTORIAL_PLAZO_PROVEEDOR');
    }
};