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
        if (!Schema::hasTable('COMPRA')) {
            return;
        }

        Schema::table('COMPRA', function (Blueprint $table): void {
            if (!Schema::hasColumn('COMPRA', 'numero_compra')) {
                $table->string('numero_compra', 30)->nullable();
            }
            if (!Schema::hasColumn('COMPRA', 'fecha_vencimiento')) {
                $table->date('fecha_vencimiento')->nullable();
            }
            if (!Schema::hasColumn('COMPRA', 'fecha_modificacion')) {
                $table->date('fecha_modificacion')->nullable();
            }
            if (!Schema::hasColumn('COMPRA', 'id_usuario_modificacion')) {
                $table->integer('id_usuario_modificacion')->nullable();
            }
        });

        DB::table('COMPRA')
            ->whereNull('numero_compra')
            ->orderBy('id_compra')
            ->chunkById(500, function ($compras): void {
                foreach ($compras as $compra) {
                    DB::table('COMPRA')
                        ->where('id_compra', $compra->id_compra)
                        ->update(['numero_compra' => 'NCC-LEGACY-' . $compra->id_compra]);
                }
            }, 'id_compra');

        Schema::table('COMPRA', function (Blueprint $table): void {
            $table->string('numero_compra', 30)->nullable(false)->change();
        });

        if (!Schema::hasIndex('COMPRA', ['numero_compra'], 'unique')) {
            Schema::table('COMPRA', function (Blueprint $table): void {
                $table->unique('numero_compra', 'uq_compra_numero_compra');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('COMPRA')) {
            return;
        }

        if (Schema::hasIndex('COMPRA', ['numero_compra'], 'unique')) {
            Schema::table('COMPRA', function (Blueprint $table): void {
                $table->dropUnique('uq_compra_numero_compra');
            });
        }

        $columnas = array_values(array_filter(
            ['numero_compra', 'fecha_vencimiento', 'fecha_modificacion', 'id_usuario_modificacion'],
            fn (string $columna): bool => Schema::hasColumn('COMPRA', $columna)
        ));

        if ($columnas !== []) {
            Schema::table('COMPRA', function (Blueprint $table) use ($columnas): void {
                $table->dropColumn($columnas);
            });
        }
    }
};
