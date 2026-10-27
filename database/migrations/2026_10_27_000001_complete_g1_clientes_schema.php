<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clientes')) {
            throw new RuntimeException('Ejecute primero la migración base de G1: falta la tabla clientes.');
        }

        if (! Schema::hasTable('zonas')) {
            Schema::create('zonas', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nombre', 120)->unique();
                $table->string('descripcion', 255)->nullable();
            });
        }

        // También admite bases G1 donde estos campos ya se incorporaron.
        foreach (['localidad' => 120, 'condicion_iva' => 60, 'tipo_documento' => 4,
            'direccion_calle' => 200, 'direccion_numero' => 6] as $column => $length) {
            if (! Schema::hasColumn('clientes', $column)) {
                Schema::table('clientes', function (Blueprint $table) use ($column, $length): void {
                    $table->string($column, $length)->nullable();
                });
            }
        }

        if (! Schema::hasColumn('clientes', 'zona_id')) {
            Schema::table('clientes', function (Blueprint $table): void {
                $table->unsignedInteger('zona_id')->nullable();
                $table->foreign('zona_id', 'fk_clientes_zona')
                    ->references('id')->on('zonas')->cascadeOnUpdate()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('clientes', 'saldo')) {
            Schema::table('clientes', function (Blueprint $table): void {
                // Campo histórico de la ficha G1; no instala contabilidad de G4.
                $table->decimal('saldo', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        // Los campos pueden ser anteriores a esta migración y contener datos.
        // No se pueden eliminar de forma automática sin conocer su procedencia.
        throw new RuntimeException('Esta migración conserva datos de G1 y no admite rollback automático. Revise el respaldo y prepare una reversión específica de G1.');
    }
};
