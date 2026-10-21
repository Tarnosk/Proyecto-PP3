<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUGERENCIA_COMPRA (PC07) - Generar sugerencias de reposición.
 *
 * Sigue el DDL canónico de PLAN_INTEGRACION_G3.md (sección 2.2). Las FKs usan
 * `integer` signed porque PRODUCTO.id_producto, PROVEEDOR.id_proveedor y
 * USUARIO.id_usuario son INT(11) en el esquema ACTUALIZACION; usar foreignId()
 * crearía unsigned bigint y MySQL rechazaría la FK.
 *
 * Requiere que PRODUCTO, PROVEEDOR y USUARIO ya existan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('SUGERENCIA_COMPRA')) {
            return;
        }

        Schema::create('SUGERENCIA_COMPRA', function (Blueprint $table) {
            $table->increments('id_sugerencia');
            $table->integer('id_producto');
            $table->integer('id_proveedor')->nullable();
            $table->integer('cantidad_sugerida');
            $table->integer('cantidad_minima')->nullable();
            $table->integer('cantidad_maxima')->nullable();
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('costo_total', 12, 2)->nullable();
            $table->decimal('velocidad_venta_diaria', 10, 4)->nullable();
            $table->integer('plazo_entrega_dias')->nullable();
            $table->date('fecha_reorden')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('motivo_generacion', 50);
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha_generacion')->useCurrent();
            $table->dateTime('fecha_resolucion')->nullable();
            $table->integer('id_usuario_resolucion')->nullable();

            $table->index('estado');
            $table->index('fecha_reorden');
            $table->index(['id_producto', 'motivo_generacion', 'estado'], 'idx_sugerencia_producto_motivo_estado');

            $table->foreign('id_producto')->references('id_producto')->on('PRODUCTO');
            $table->foreign('id_proveedor')->references('id_proveedor')->on('PROVEEDOR');
            $table->foreign('id_usuario_resolucion')->references('id_usuario')->on('USUARIO');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SUGERENCIA_COMPRA');
    }
};