<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $tablaHistorial = Schema::hasTable('historial_precio') ? 'historial_precio' : (Schema::hasTable('HISTORIAL_PRECIO') ? 'HISTORIAL_PRECIO' : null);

        if ($tablaHistorial) {
            DB::statement("ALTER TABLE `{$tablaHistorial}` MODIFY COLUMN `origen` VARCHAR(50) NOT NULL DEFAULT 'manual'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $tablaHistorial = Schema::hasTable('historial_precio') ? 'historial_precio' : (Schema::hasTable('HISTORIAL_PRECIO') ? 'HISTORIAL_PRECIO' : null);

        if ($tablaHistorial) {
            DB::statement("ALTER TABLE `{$tablaHistorial}` MODIFY COLUMN `origen` ENUM('manual','aumento_masivo') NOT NULL DEFAULT 'manual'");
        }
    }
};
