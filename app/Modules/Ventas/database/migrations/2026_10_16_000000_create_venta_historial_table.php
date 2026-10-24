<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TABLA NUEVA: venta_historial (auditoría de cada venta)
 *
 * Guarda una fila por cada cosa importante que le pasa a una venta:
 * modificación, anulación, restauración y cambio de estado.
 * Responde "¿quién cambió qué, cuándo y por qué?".
 *
 * Además es la que permite RESTAURAR una venta anulada: cuando se anula se
 * guarda en qué estado estaba ("estado_anterior"), y al restaurar se vuelve ahí.
 *
 * Es una tabla PROPIA del módulo Ventas: no modifica ninguna tabla existente.
 *
 * No lleva clave foránea a venta.id_venta a propósito: la tabla "venta" no
 * se crea con migraciones en este proyecto (vino importada en la base), y una
 * FK con un tipo de columna distinto haría fallar la migración en MySQL.
 * Se usa un índice común para buscar rápido por venta.
 *
 * Para crearla SOLO a ella en MySQL (sin correr otras migraciones pendientes):
 *   php artisan migrate --path=app/Modules/Ventas/database/migrations/2026_10_16_000000_create_venta_historial_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // Si ya existe (por ejemplo, creada a mano) no hace nada.
        if (Schema::hasTable('venta_historial')) {
            return;
        }

        Schema::create('venta_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_venta')->index();      // venta afectada
            $table->string('accion', 20);                       // modificacion | anulacion | restauracion | cambio_estado
            $table->string('estado_anterior', 20)->nullable(); // estado antes del cambio
            $table->string('estado_nuevo', 20);                 // estado después del cambio
            $table->string('motivo', 150)->nullable();          // por qué (obligatorio al anular)
            $table->json('datos')->nullable();                  // detalle extra (ej: total anterior y nuevo)
            $table->unsignedInteger('id_usuario')->nullable();  // quién lo hizo (usuario de prueba hasta tener login)
            $table->timestamp('created_at')->useCurrent();      // cuándo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_historial');
    }
};
