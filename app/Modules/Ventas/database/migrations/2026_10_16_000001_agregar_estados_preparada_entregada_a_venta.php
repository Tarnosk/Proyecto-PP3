<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AGREGA LOS ESTADOS "preparada" Y "entregada" A LA COLUMNA venta.estado (solo MySQL)
 *
 * En la base MySQL del proyecto la columna está definida como:
 *   ENUM('pendiente','confirmada','pagada','facturada','cancelada')
 * Un ENUM de MySQL SOLO acepta esos textos: si se intenta guardar "preparada"
 * o "entregada" la base da error. Esta migración suma esos dos valores.
 *
 * ⚠ Cambia la estructura de la tabla "venta" en MySQL. Coordinarlo con el
 *   equipo antes de correrla (todos deberían tener la misma estructura).
 *
 * Para correr SOLO esta migración:
 *   php artisan migrate --path=app/Modules/Ventas/database/migrations/2026_10_16_000001_agregar_estados_preparada_entregada_a_venta.php
 *
 * En SQLite (tests) no hace nada: ahí la columna es texto libre.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->esMysqlConTablaVenta()) {
            return;
        }

        DB::statement("ALTER TABLE venta MODIFY estado ENUM('pendiente','confirmada','preparada','entregada','pagada','facturada','cancelada') NOT NULL");
    }

    public function down(): void
    {
        if (! $this->esMysqlConTablaVenta()) {
            return;
        }

        // Antes de sacar los valores, las ventas que estén en esos estados vuelven
        // a "confirmada" (si no, MySQL no deja achicar el ENUM).
        DB::table('venta')->whereIn('estado', ['preparada', 'entregada'])->update(['estado' => 'confirmada']);

        DB::statement("ALTER TABLE venta MODIFY estado ENUM('pendiente','confirmada','pagada','facturada','cancelada') NOT NULL");
    }

    private function esMysqlConTablaVenta(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) && Schema::hasTable('venta');
    }
};
