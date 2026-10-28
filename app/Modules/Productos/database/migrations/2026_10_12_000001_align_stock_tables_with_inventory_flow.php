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
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('MOVIMIENTO_STOCK')) {
            Schema::table('MOVIMIENTO_STOCK', function (Blueprint $table) {
                if (!Schema::hasColumn('MOVIMIENTO_STOCK', 'cantidad_base')) {
                    $table->integer('cantidad_base')->nullable();
                }
                if (!Schema::hasColumn('MOVIMIENTO_STOCK', 'id_recepcion')) {
                    $table->integer('id_recepcion')->nullable();
                }
            });

            DB::table('MOVIMIENTO_STOCK')
                ->whereNull('cantidad_base')
                ->orderBy('id_movimiento')
                ->chunkById(500, function ($movimientos): void {
                    foreach ($movimientos as $movimiento) {
                        $equivalencia = $movimiento->id_unidad
                            ? DB::table('UNIDAD_MEDIDA')->where('id_unidad', $movimiento->id_unidad)->value('equivalencia_base')
                            : null;

                        $actualizar = [
                            'cantidad_base' => (int) round((int) $movimiento->cantidad * ($equivalencia !== null ? (float) $equivalencia : 1)),
                        ];

                        DB::table('MOVIMIENTO_STOCK')
                            ->where('id_movimiento', $movimiento->id_movimiento)
                            ->update($actualizar);
                    }
                }, 'id_movimiento');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('MOVIMIENTO_STOCK')) {
            $columnasMovimiento = array_values(array_filter(
                ['cantidad_base', 'id_recepcion'],
                fn (string $columna): bool => Schema::hasColumn('MOVIMIENTO_STOCK', $columna)
            ));

            if ($columnasMovimiento !== []) {
                Schema::table('MOVIMIENTO_STOCK', fn (Blueprint $table) => $table->dropColumn($columnasMovimiento));
            }
        }
    }
};
