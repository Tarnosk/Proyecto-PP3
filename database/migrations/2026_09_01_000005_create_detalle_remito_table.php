<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: detalle_remito (Módulo Entregas / Logística - Grupo 2)
 * Detalle de productos y bultos cargados en el remito de entrega.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('detalle_remito')) {
            Schema::create('detalle_remito', function (Blueprint $table) {
                $table->increments('id_detalle_remito');
                $table->unsignedInteger('id_remito');
                $table->unsignedInteger('id_producto')->nullable()->comment('ID lógico de producto en catálogo/stock');
                $table->string('descripcion', 255);
                $table->integer('cantidad');
                $table->integer('bultos')->default(1);

                $table->foreign('id_remito', 'fk_dr_remito')
                    ->references('id_remito')->on('remito')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_remito');
    }
};
