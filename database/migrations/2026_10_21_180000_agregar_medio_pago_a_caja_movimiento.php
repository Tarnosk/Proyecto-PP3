<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            // efectivo | cheque | transferencia | otro. Los movimientos existentes quedan en efectivo.
            $table->string('medio_pago', 20)->default('efectivo')->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->dropColumn('medio_pago');
        });
    }
};
