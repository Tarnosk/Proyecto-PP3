<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->decimal('monto_anterior', 12, 2)->nullable();
            $table->unsignedInteger('id_usuario_modificacion')->nullable();
            $table->dateTime('fecha_hora_modificacion')->nullable();
            $table->unsignedInteger('id_cobro')->nullable()->comment('ID lógico de cobro asociado');
        });

        Schema::table('CAJA', function (Blueprint $table) {
            $table->decimal('monto_esperado', 12, 2)->nullable()->after('monto_final');
        });
    }

    public function down(): void
    {
        Schema::table('CAJA_MOVIMIENTO', function (Blueprint $table) {
            $table->dropColumn(['monto_anterior', 'id_usuario_modificacion', 'fecha_hora_modificacion', 'id_cobro']);
        });

        Schema::table('CAJA', function (Blueprint $table) {
            $table->dropColumn('monto_esperado');
        });
    }
};
