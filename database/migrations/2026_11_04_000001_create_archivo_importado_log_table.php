<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ARCHIVO_IMPORTADO_LOG', function (Blueprint $table) {
            $table->increments('id_archivo_importado_log');
            $table->string('nombre_original', 255);
            $table->string('ruta_almacenada', 500);
            $table->char('hash_sha256', 64)->unique();
            $table->enum('formato_extension', ['xls', 'txt']);
            $table->integer('cantidad_filas_leidas')->default(0);
            $table->integer('cantidad_filas_insertadas')->default(0);
            $table->integer('cantidad_filas_omitidas')->default(0);
            $table->integer('cantidad_filas_error')->default(0);
            $table->unsignedInteger('id_usuario');
            $table->decimal('saldo_inicial', 15, 2)->nullable();
            $table->decimal('saldo_final', 15, 2)->nullable();
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario', 'fk_ARCHIVO_IMPORTADO_LOG_id_usuario_usuarios')
                ->references('id')->on('usuarios')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ARCHIVO_IMPORTADO_LOG');
    }
};
