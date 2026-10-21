<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('HISTORIAL_PRECIO_PROVEEDOR')
            && !Schema::hasColumn('HISTORIAL_PRECIO_PROVEEDOR', 'id_unidad')) {
            Schema::table('HISTORIAL_PRECIO_PROVEEDOR', function (Blueprint $table): void {
                $table->integer('id_unidad')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('HISTORIAL_PRECIO_PROVEEDOR')
            && Schema::hasColumn('HISTORIAL_PRECIO_PROVEEDOR', 'id_unidad')) {
            Schema::table('HISTORIAL_PRECIO_PROVEEDOR', function (Blueprint $table): void {
                $table->dropColumn('id_unidad');
            });
        }
    }
};
