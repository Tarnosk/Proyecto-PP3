<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C08 - Registrar múltiples métodos de pago de una compra.
 *
 * PAGO_PROVEEDOR ya existe en el esquema ACTUALIZACION (el schema base se importa
 * por SQL externo), pero no había ninguna migración que la creara, así que un
 * ambiente nuevo no la tendría. Esta migración la define de forma canónica y es
 * idempotente: si la tabla ya está, no hace nada.
 *
 * Sigue el DDL del esquema: id_pago / id_compra / metodo_pago (ENUM) / importe /
 * fecha_pago / id_usuario. Las FKs usan `integer` signed porque COMPRA.id_compra
 * y USUARIO.id_usuario son INT(11) en el esquema.
 *
 * Permite N pagos por compra (una fila por método de pago), que es el núcleo de C08.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('PAGO_PROVEEDOR')) {
            return;
        }

        Schema::create('PAGO_PROVEEDOR', function (Blueprint $table) {
            $table->increments('id_pago');
            $table->integer('id_compra');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'cheque', 'echeq', 'tarjeta']);
            $table->decimal('importe', 12, 2);
            $table->date('fecha_pago');
            $table->integer('id_usuario');

            $table->index('id_compra', 'idx_pago_compra');
            $table->index('id_usuario', 'idx_pago_usuario');

            $table->foreign('id_compra')->references('id_compra')->on('COMPRA')->onDelete('cascade');
            $table->foreign('id_usuario')->references('id_usuario')->on('USUARIO');
        });
    }

    /**
     * No hace nada a propósito.
     *
     * PAGO_PROVEEDOR es una tabla del esquema base y en los ambientes donde ya
     * existía, up() no la creó. Si down() la dropeara, un rollback borraría pagos
     * reales que esta migración nunca hizo. Como no es posible saber en down()
     * si la tabla fue creada por esta migración o previa a ella, el rollback se
     * deja vacío y la limpieza es manual.
     */
    public function down(): void
    {
    }
};