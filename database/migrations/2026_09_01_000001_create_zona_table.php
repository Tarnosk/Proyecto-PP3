<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: zona (Módulo Entregas / Logística - Grupo 2)
 * Zonas y rutas de reparto para la asignación de pedidos y entregas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('zona')) {
            Schema::create('zona', function (Blueprint $table) {
                $table->increments('id_zona');
                $table->string('nombre', 100);
                $table->string('descripcion', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zona');
    }
};
