<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CAJA', function (Blueprint $table) {
            $table->boolean('cierre_automatico')->default(false)->after('diferencia');
        });
    }

    public function down(): void
    {
        Schema::table('CAJA', function (Blueprint $table) {
            $table->dropColumn('cierre_automatico');
        });
    }
};
