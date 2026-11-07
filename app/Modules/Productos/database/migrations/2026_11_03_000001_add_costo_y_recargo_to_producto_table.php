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
        $tablaProducto = Schema::hasTable('producto') ? 'producto' : (Schema::hasTable('PRODUCTO') ? 'PRODUCTO' : null);

        // Ampliar columna origen en historial_precio para permitir 'costo_reposicion'
        $tablaHistorial = Schema::hasTable('historial_precio') ? 'historial_precio' : (Schema::hasTable('HISTORIAL_PRECIO') ? 'HISTORIAL_PRECIO' : null);
        if ($tablaHistorial && Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `{$tablaHistorial}` MODIFY COLUMN `origen` VARCHAR(50) NOT NULL DEFAULT 'manual'");
        }

        if ($tablaProducto) {
            Schema::table($tablaProducto, function (Blueprint $table) use ($tablaProducto) {
                if (!Schema::hasColumn($tablaProducto, 'costo')) {
                    $table->decimal('costo', 12, 2)->nullable();
                }
                if (!Schema::hasColumn($tablaProducto, 'recargo')) {
                    $table->decimal('recargo', 8, 2)->nullable();
                }
            });

            // Migración no destructiva de datos existentes:
            // Sincronizar costo de reposición desde el proveedor principal activo que tenga precio acordado
            $tablaPP = Schema::hasTable('producto_proveedor') ? 'producto_proveedor' : (Schema::hasTable('PRODUCTO_PROVEEDOR') ? 'PRODUCTO_PROVEEDOR' : null);

            if ($tablaPP) {
                $principales = DB::table($tablaPP)
                    ->where('es_proveedor_principal', 1)
                    ->where('activo', 1)
                    ->whereNotNull('precio_acordado')
                    ->where('precio_acordado', '>', 0)
                    ->get(['id_producto', 'precio_acordado']);

                foreach ($principales as $p) {
                    $prod = DB::table($tablaProducto)->where('id_producto', $p->id_producto)->first();
                    if (!$prod) {
                        continue;
                    }

                    $costo = (float) $p->precio_acordado;
                    $precio = (float) ($prod->precio_unitario ?? 0);
                    $recargo = null;

                    if ($costo > 0 && $precio >= $costo) {
                        $recargo = round((($precio - $costo) / $costo) * 100, 2);
                    }

                    DB::table($tablaProducto)
                        ->where('id_producto', $p->id_producto)
                        ->update([
                            'costo' => $costo,
                            'recargo' => $recargo,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        $tablaProducto = Schema::hasTable('producto') ? 'producto' : (Schema::hasTable('PRODUCTO') ? 'PRODUCTO' : null);

        // Ampliar columna origen en historial_precio para permitir 'costo_reposicion'
        $tablaHistorial = Schema::hasTable('historial_precio') ? 'historial_precio' : (Schema::hasTable('HISTORIAL_PRECIO') ? 'HISTORIAL_PRECIO' : null);
        if ($tablaHistorial && Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `{$tablaHistorial}` MODIFY COLUMN `origen` VARCHAR(50) NOT NULL DEFAULT 'manual'");
        }

        if ($tablaProducto) {
            $columnas = array_values(array_filter(
                ['costo', 'recargo'],
                fn (string $c): bool => Schema::hasColumn($tablaProducto, $c)
            ));

            if (!empty($columnas)) {
                Schema::table($tablaProducto, function (Blueprint $table) use ($columnas) {
                    $table->dropColumn($columnas);
                });
            }
        }
    }
};
