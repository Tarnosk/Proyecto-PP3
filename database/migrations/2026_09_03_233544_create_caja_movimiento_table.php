<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->increments('id_movimiento_caja');
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->string('tipo', 20); // 'ingreso' | 'egreso'
            $table->string('concepto', 255);
            $table->unsignedInteger('id_usuario')->comment('ID lógico del usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CAJA_MOVIMIENTO');
    }
};