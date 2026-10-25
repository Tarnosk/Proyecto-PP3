<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuenta_corriente_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cliente_id');
            $table->unsignedBigInteger('venta_id')->nullable()->unique();
            $table->string('tipo', 20);
            $table->string('descripcion');
            $table->decimal('importe', 12, 2);
            $table->string('referencia_externa')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuenta_corriente_movimientos');
    }
};
