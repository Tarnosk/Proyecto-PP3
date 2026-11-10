<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tablaCompra = Schema::hasTable('compra') ? 'compra' : (Schema::hasTable('COMPRA') ? 'COMPRA' : null);
        if ($tablaCompra) {
            Schema::table($tablaCompra, function (Blueprint $table) use ($tablaCompra) {
                if (!Schema::hasColumn($tablaCompra, 'cierre_parcial')) {
                    $table->boolean('cierre_parcial')->default(false)->after('estado');
                }
                if (!Schema::hasColumn($tablaCompra, 'observaciones')) {
                    $table->text('observaciones')->nullable()->after('cierre_parcial');
                }
            });
        }

        $tablaOrden = Schema::hasTable('orden_compra') ? 'orden_compra' : (Schema::hasTable('ORDEN_COMPRA') ? 'ORDEN_COMPRA' : null);
        if ($tablaOrden) {
            Schema::table($tablaOrden, function (Blueprint $table) use ($tablaOrden) {
                if (!Schema::hasColumn($tablaOrden, 'cierre_parcial')) {
                    $table->boolean('cierre_parcial')->default(false)->after('estado');
                }
                if (!Schema::hasColumn($tablaOrden, 'observaciones')) {
                    $table->text('observaciones')->nullable()->after('cierre_parcial');
                }
            });
        }
    }

    public function down(): void
    {
        $tablaCompra = Schema::hasTable('compra') ? 'compra' : (Schema::hasTable('COMPRA') ? 'COMPRA' : null);
        if ($tablaCompra) {
            Schema::table($tablaCompra, function (Blueprint $table) use ($tablaCompra) {
                if (Schema::hasColumn($tablaCompra, 'observaciones')) {
                    $table->dropColumn('observaciones');
                }
                if (Schema::hasColumn($tablaCompra, 'cierre_parcial')) {
                    $table->dropColumn('cierre_parcial');
                }
            });
        }

        $tablaOrden = Schema::hasTable('orden_compra') ? 'orden_compra' : (Schema::hasTable('ORDEN_COMPRA') ? 'ORDEN_COMPRA' : null);
        if ($tablaOrden) {
            Schema::table($tablaOrden, function (Blueprint $table) use ($tablaOrden) {
                if (Schema::hasColumn($tablaOrden, 'observaciones')) {
                    $table->dropColumn('observaciones');
                }
                if (Schema::hasColumn($tablaOrden, 'cierre_parcial')) {
                    $table->dropColumn('cierre_parcial');
                }
            });
        }
    }
};
