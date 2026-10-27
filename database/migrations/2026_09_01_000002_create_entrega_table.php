<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: entrega (Módulo Entregas / Logística - Grupo 2)
 * Viajes y hojas de ruta asignadas a repartidores.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('entrega')) {
            Schema::create('entrega', function (Blueprint $table) {
                $table->increments('id_entrega');
                $table->unsignedInteger('id_repartidor')->comment('ID lógico del usuario repartidor');
                $table->dateTime('fecha_creacion')->useCurrent();
                $table->date('fecha_salida')->nullable();
                $table->string('estado', 30)->default('en_preparacion');
                $table->text('observaciones')->nullable();
                $table->unsignedInteger('id_usuario_creacion')->default(1)->comment('ID lógico del usuario que planificó la entrega');

                $table->index('id_repartidor', 'idx_entrega_repartidor');
                $table->index('estado', 'idx_entrega_estado');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entrega');
    }
};
