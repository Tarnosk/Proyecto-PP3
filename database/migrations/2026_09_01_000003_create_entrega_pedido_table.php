<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: entrega_pedido (Módulo Entregas / Logística - Grupo 2)
 * Relación entre entregas/hojas de ruta y ventas/pedidos asignados.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('entrega_pedido')) {
            Schema::create('entrega_pedido', function (Blueprint $table) {
                $table->increments('id_entrega_pedido');
                $table->unsignedInteger('id_entrega');
                $table->unsignedInteger('id_venta')->comment('ID lógico de venta provista por API G4');
                $table->string('estado_pedido_entrega', 30)->default('en_preparacion');
                $table->text('motivo_rechazo')->nullable();
                $table->dateTime('fecha_actualizacion')->useCurrent();

                $table->index('id_venta', 'idx_ep_venta');
                $table->foreign('id_entrega', 'fk_ep_entrega')
                    ->references('id_entrega')->on('entrega')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entrega_pedido');
    }
};
