<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla RENDICION.
 *
 * Representa la rendición de fin de jornada de un repartidor.
 * Se asocia a una entrega (opcional) y registra el total cobrado,
 * el estado de validación administrativa y el motivo de rechazo si aplica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RENDICION', function (Blueprint $table) {
            // PK
            $table->increments('id_rendicion');

            // FK al repartidor (tabla USUARIO, rol = 'repartidor')
            $table->unsignedInteger('id_repartidor');

            // FK a la entrega asociada (nullable: puede haber rendición sin entrega formal en el sistema)
            $table->unsignedInteger('id_entrega')->nullable();

            // Datos de la jornada
            $table->dateTime('fecha');
            $table->decimal('total_rendido', 10, 2)->default(0);

            // Estado del ciclo de vida
            // pendiente → aprobada | rechazada
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');

            $table->text('observaciones')->nullable();

            // Datos de validación administrativa
            $table->unsignedInteger('id_usuario_validador')->nullable();
            $table->dateTime('fecha_validacion')->nullable();
            $table->text('motivo_rechazo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RENDICION');
    }
};
