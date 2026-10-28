<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 1. Quitar FKs e índices de id_lote en movimiento_stock y detalle_recepcion
            $this->dropColumnAndForeignKeys('movimiento_stock', 'id_lote');
            $this->dropColumnAndForeignKeys('detalle_recepcion', 'id_lote');
            $this->dropForeignKeysOnly('movimiento_stock', 'id_venta');
            $this->dropForeignKeysOnly('movimiento_stock', 'id_entrega');

            // 2. Eliminar tabla lote
            Schema::dropIfExists('lote');

            // 3. Eliminar columna dias_alerta_vencimiento y asegurar columna imagen en producto
            if (Schema::hasTable('producto')) {
                if (Schema::hasColumn('producto', 'dias_alerta_vencimiento')) {
                    Schema::table('producto', function (Blueprint $table) {
                        $table->dropColumn('dias_alerta_vencimiento');
                    });
                }

                if (! Schema::hasColumn('producto', 'imagen')) {
                    Schema::table('producto', function (Blueprint $table) {
                        $table->string('imagen', 255)->nullable()->after('precio_unitario');
                    });
                }
            }

            // 4. Eliminar las tablas en plural del mock antiguo (2026_09_29_000002_add_sprint3_tables)
            $legacyMockTables = [
                'cobro_ventas',
                'cobros',
                'detalle_ventas',
                'ventas',
                'productos',
                'marcas',
                'categorias',
            ];

            foreach ($legacyMockTables as $mockTable) {
                Schema::dropIfExists($mockTable);
            }

            if (Schema::hasTable('migrations')) {
                DB::table('migrations')
                    ->where('migration', '2026_09_29_000002_add_sprint3_tables')
                    ->delete();

                $batch = (int) (DB::table('migrations')->max('batch') ?? 1);
                $existingTableMigrations = [
                    '2026_09_03_233544_create_caja_movimiento_table' => 'caja_movimiento',
                    '2026_09_15_000001_create_rendicion_table' => 'rendicion',
                    '2026_09_15_000002_create_rendicion_cobro_table' => 'rendicion_cobro',
                ];

                foreach ($existingTableMigrations as $migName => $tableName) {
                    if (Schema::hasTable($tableName) && ! DB::table('migrations')->where('migration', $migName)->exists()) {
                        DB::table('migrations')->insert([
                            'migration' => $migName,
                            'batch' => $batch,
                        ]);
                    }
                }

                if (Schema::hasTable('caja_movimiento')) {
                    Schema::table('caja_movimiento', function (Blueprint $table) {
                        if (! Schema::hasColumn('caja_movimiento', 'monto_anterior')) {
                            $table->decimal('monto_anterior', 12, 2)->nullable();
                        }
                        if (! Schema::hasColumn('caja_movimiento', 'id_usuario_modificacion')) {
                            $table->unsignedInteger('id_usuario_modificacion')->nullable();
                        }
                        if (! Schema::hasColumn('caja_movimiento', 'fecha_hora_modificacion')) {
                            $table->dateTime('fecha_hora_modificacion')->nullable();
                        }
                        if (! Schema::hasColumn('caja_movimiento', 'id_cobro')) {
                            $table->unsignedInteger('id_cobro')->nullable();
                        }
                    });
                }

                if (Schema::hasTable('caja') && ! Schema::hasColumn('caja', 'monto_esperado')) {
                    Schema::table('caja', function (Blueprint $table) {
                        $table->decimal('monto_esperado', 12, 2)->nullable();
                    });
                }

                if (
                    Schema::hasTable('caja_movimiento')
                    && ! DB::table('migrations')->where('migration', '2026_10_19_203127_agregar_auditoria_a_caja_movimiento')->exists()
                ) {
                    DB::table('migrations')->insert([
                        'migration' => '2026_10_19_203127_agregar_auditoria_a_caja_movimiento',
                        'batch' => $batch,
                    ]);
                }
            }

            // 5. Normalizar IDs base de catálogo (Categorías 1..3, Proveedor 1, Unidades 1..2 para P001)
            $this->normalizeBaseCatalogIds();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function normalizeBaseCatalogIds(): void
    {
        $hoy = '2026-09-01';
        $idUsuario = (int) (
            (Schema::hasTable('usuarios') ? DB::table('usuarios')->value('id') : null)
            ?? (Schema::hasTable('usuario') ? DB::table('usuario')->value('id_usuario') : null)
            ?? 1
        );

        if (Schema::hasTable('categoria')) {
            $canonCats = [
                1 => 'Bebidas',
                2 => 'Panadería',
                3 => 'Almacén',
            ];
            foreach ($canonCats as $targetId => $nombre) {
                $existingByName = DB::table('categoria')->where('nombre', $nombre)->first();
                $existingById = DB::table('categoria')->where('id_categoria', $targetId)->first();

                if ($existingByName && (int) $existingByName->id_categoria !== $targetId && ! $existingById) {
                    $oldId = (int) $existingByName->id_categoria;
                    DB::table('categoria')->where('id_categoria', $oldId)->update(['id_categoria' => $targetId]);
                    if (Schema::hasTable('producto')) {
                        DB::table('producto')->where('id_categoria', $oldId)->update(['id_categoria' => $targetId]);
                    }
                } elseif (! $existingByName && ! $existingById) {
                    DB::table('categoria')->insertOrIgnore([
                        'id_categoria' => $targetId,
                        'nombre' => $nombre,
                        'fecha_creacion' => $hoy,
                    ]);
                }
            }
        }

        if (Schema::hasTable('proveedor') && ! DB::table('proveedor')->where('id_proveedor', 1)->exists()) {
            DB::table('proveedor')->insertOrIgnore([
                'id_proveedor' => 1,
                'razon_social' => 'Distribuidora Central SA',
                'contacto' => 'Juan Pérez',
                'cuit' => '30-71234567-8',
                'telefono' => '1145550101',
                'email' => 'ventas@distribuidoracentral.com.ar',
                'direccion' => 'Av. Industrial 1200',
                'estado' => 'activo',
                'fecha_alta' => $hoy,
                'plazo_entrega_dias' => 3,
                'id_usuario_carga' => $idUsuario,
            ]);
        }

        if (Schema::hasTable('unidad_medida') && Schema::hasTable('producto') && DB::table('producto')->where('id_producto', 1)->exists()) {
            DB::table('unidad_medida')->insertOrIgnore([
                [
                    'id_unidad' => 1,
                    'id_producto' => 1,
                    'nombre_unidad' => 'Unidad suelta',
                    'es_base' => 1,
                    'equivalencia_base' => 1,
                ],
                [
                    'id_unidad' => 2,
                    'id_producto' => 1,
                    'nombre_unidad' => 'Pack x6',
                    'es_base' => 0,
                    'equivalencia_base' => 6,
                ],
            ]);
        }
    }

    public function down(): void
    {
        // Limpieza estructural definitiva acordada en G3
    }

    private function dropColumnAndForeignKeys(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $dbName = DB::connection()->getDatabaseName();
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, $table, $column]
        );

        foreach ($fks as $fk) {
            $fkName = $fk->CONSTRAINT_NAME ?? null;
            if ($fkName) {
                try {
                    DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
                } catch (\Throwable $e) {
                    // Continuar si ya fue eliminada
                }
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropColumn($column);
        });
    }

    private function dropForeignKeysOnly(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $dbName = DB::connection()->getDatabaseName();
        $fks = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, $table, $column]
        );

        foreach ($fks as $fk) {
            $fkName = $fk->CONSTRAINT_NAME ?? null;
            if ($fkName) {
                try {
                    DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
                } catch (\Throwable $e) {
                    // Continuar si ya fue eliminada
                }
            }
        }
    }
};
