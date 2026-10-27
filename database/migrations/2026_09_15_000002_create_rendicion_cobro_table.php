<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla RENDICION_COBRO.
 *
 * Registra cada cobro individual realizado durante un reparto,
 * asociado a su rendición, cliente, factura y medio de pago.
 * Permite múltiples cobros por rendición (relación 1:N con RENDICION).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('RENDICION_COBRO', function (Blueprint $table) {
            $table->increments('id_rendicion_cobro');

            // FK a la rendición que agrupa este cobro
            $table->unsignedInteger('id_rendicion');

            // FK al cliente (tabla CLIENTE)
            $table->unsignedInteger('id_cliente');

            // FK a la factura/venta que se está saldando (tabla VENTA)
            $table->unsignedInteger('id_factura');

            // Datos del cobro
            $table->decimal('monto', 10, 2);
            $table->enum('medio_pago', ['efectivo', 'transferencia', 'cheque']);
            $table->dateTime('fecha_registro');

            // Índice para buscar cobros de una rendición eficientemente
            $table->index('id_rendicion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('RENDICION_COBRO');
    }
};
