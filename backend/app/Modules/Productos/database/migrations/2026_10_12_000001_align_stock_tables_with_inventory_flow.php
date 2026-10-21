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
        if (Schema::hasTable('MOVIMIENTO_STOCK')) {
            Schema::table('MOVIMIENTO_STOCK', function (Blueprint $table) {
                if (!Schema::hasColumn('MOVIMIENTO_STOCK', 'cantidad_base')) {
                    $table->integer('cantidad_base')->nullable();
                }
                if (!Schema::hasColumn('MOVIMIENTO_STOCK', 'id_lote')) {
                    $table->integer('id_lote')->nullable();
                }
                if (!Schema::hasColumn('MOVIMIENTO_STOCK', 'id_recepcion')) {
                    $table->integer('id_recepcion')->nullable();
                }
            });

            $lotesPorMovimiento = Schema::hasTable('LOTE')
                && Schema::hasColumn('LOTE', 'id_movimiento')
                ? DB::table('LOTE')->whereNotNull('id_movimiento')->pluck('id_lote', 'id_movimiento')
                : collect();

            DB::table('MOVIMIENTO_STOCK')
                ->whereNull('cantidad_base')
                ->orderBy('id_movimiento')
                ->chunkById(500, function ($movimientos) use ($lotesPorMovimiento): void {
                    foreach ($movimientos as $movimiento) {
                        $equivalencia = $movimiento->id_unidad
                            ? DB::table('UNIDAD_MEDIDA')->where('id_unidad', $movimiento->id_unidad)->value('equivalencia_base')
                            : null;

                        $actualizar = [
                            'cantidad_base' => (int) round((int) $movimiento->cantidad * ($equivalencia !== null ? (float) $equivalencia : 1)),
                        ];

                        if ($movimiento->id_lote === null && isset($lotesPorMovimiento[$movimiento->id_movimiento])) {
                            $actualizar['id_lote'] = $lotesPorMovimiento[$movimiento->id_movimiento];
                        }

                        DB::table('MOVIMIENTO_STOCK')
                            ->where('id_movimiento', $movimiento->id_movimiento)
                            ->update($actualizar);
                    }
                }, 'id_movimiento');
        }

        if (Schema::hasTable('LOTE')) {
            Schema::table('LOTE', function (Blueprint $table) {
                if (!Schema::hasColumn('LOTE', 'cantidad_inicial')) {
                    $table->integer('cantidad_inicial')->nullable();
                }
                if (!Schema::hasColumn('LOTE', 'cantidad_actual')) {
                    $table->integer('cantidad_actual')->nullable();
                }
            });

            if (Schema::hasColumn('LOTE', 'cantidad')) {
                DB::table('LOTE')
                    ->orderBy('id_lote')
                    ->chunkById(500, function ($lotes): void {
                        foreach ($lotes as $lote) {
                            DB::table('LOTE')
                                ->where('id_lote', $lote->id_lote)
                                ->update([
                                    'cantidad_inicial' => $lote->cantidad_inicial ?? $lote->cantidad ?? 0,
                                    'cantidad_actual' => $lote->cantidad_actual ?? $lote->cantidad ?? 0,
                                ]);
                        }
                    }, 'id_lote');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('LOTE')) {
            $columnasLote = array_values(array_filter(
                ['cantidad_inicial', 'cantidad_actual'],
                fn (string $columna): bool => Schema::hasColumn('LOTE', $columna)
            ));

            if ($columnasLote !== []) {
                Schema::table('LOTE', fn (Blueprint $table) => $table->dropColumn($columnasLote));
            }
        }

        if (Schema::hasTable('MOVIMIENTO_STOCK')) {
            $columnasMovimiento = array_values(array_filter(
                ['cantidad_base', 'id_lote', 'id_recepcion'],
                fn (string $columna): bool => Schema::hasColumn('MOVIMIENTO_STOCK', $columna)
            ));

            if ($columnasMovimiento !== []) {
                Schema::table('MOVIMIENTO_STOCK', fn (Blueprint $table) => $table->dropColumn($columnasMovimiento));
            }
        }
    }
};
