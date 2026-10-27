<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: remito (Módulo Entregas / Logística - Grupo 2)
 * Comprobante legal de traslado y entrega de mercadería.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('remito')) {
            Schema::create('remito', function (Blueprint $table) {
                $table->increments('id_remito');
                $table->string('numero_remito', 30)->unique();
                $table->unsignedInteger('id_entrega');
                $table->unsignedInteger('id_venta')->comment('ID lógico de venta asociada');
                $table->dateTime('fecha_emision')->useCurrent();
                $table->string('estado', 30)->default('emitido');
                $table->text('observaciones')->nullable();

                $table->index('id_venta', 'idx_remito_venta');
                $table->foreign('id_entrega', 'fk_remito_entrega')
                    ->references('id_entrega')->on('entrega')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('remito');
    }
};
