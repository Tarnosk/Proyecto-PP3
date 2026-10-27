<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CAJA', function (Blueprint $table) {
            $table->increments('id_caja');
            $table->unsignedInteger('id_usuario');
            $table->date('fecha');
            $table->decimal('monto_inicial', 12, 2);
            $table->decimal('monto_final', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->string('estado', 20)->default('abierta'); // abierta | cerrada
            $table->unsignedInteger('id_usuario_cierre')->nullable();
            $table->dateTime('fecha_hora_apertura');
            $table->dateTime('fecha_hora_cierre')->nullable();
            $table->string('observaciones', 500)->nullable();

            // Un usuario no puede tener dos cajas abiertas el mismo día
            $table->unique(['id_usuario', 'fecha'], 'uq_caja_usuario_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CAJA');
    }
};