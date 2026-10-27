<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Solo las tablas propias de Ventas necesarias en este checkout.
 * Definiciones de origin/develop-g4, commit 022a72026c6ba68040918b1a6e12a92ecc6f72de,
 * database/migrations/2026_10_15_000000_create_erp_tables.php.
 * No ejecutar la migración general de esa rama: incluye tablas de otros grupos.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['venta', 'detalle_venta'] as $name) {
            if (Schema::hasTable($name)) {
                throw new RuntimeException("La tabla {$name} ya existe. Revisar la instalación de G4 sin reemplazarla.");
            }
        }

        Schema::create('venta', function (Blueprint $table): void {
            $table->bigIncrements('id_venta');
            $table->unsignedInteger('id_cliente');
            $table->date('fecha');
            $table->decimal('total', 12, 2);
            $table->decimal('descuento_global', 12, 2)->default(0);
            $table->string('numFactura', 50)->unique();
            $table->string('estado', 30);
            $table->string('observaciones', 150);
            $table->unsignedInteger('id_usuario');
        });

        Schema::create('detalle_venta', function (Blueprint $table): void {
            $table->bigIncrements('id_detalle');
            $table->unsignedBigInteger('id_venta');
            $table->unsignedInteger('id_producto');
            $table->unsignedInteger('id_promocion')->nullable();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->foreign('id_venta')->references('id_venta')->on('venta')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // En el lote acotado esta migración se revierte antes que la de cuentas.
        // Detener todo el rollback si se registraron operaciones desde la instalación.
        foreach (['venta', 'detalle_venta', 'cuenta_corriente_movimientos'] as $name) {
            if (Schema::hasTable($name) && DB::table($name)->exists()) {
                throw new RuntimeException("No se puede revertir G4: {$name} contiene datos. Exportarlos y coordinar su conservación antes de continuar.");
            }
        }

        Schema::dropIfExists('detalle_venta');
        Schema::dropIfExists('venta');
    }
};
