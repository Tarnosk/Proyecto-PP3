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
        Schema::create('MOVIMIENTO_BANCARIO', function (Blueprint $table) {
            $table->bigIncrements('id_movimiento_bancario');
            $table->unsignedInteger('id_archivo_importado_log');
            $table->date('fecha_operacion');
            $table->decimal('monto', 15, 2);
            $table->enum('signo_monto', ['ingreso', 'egreso']);
            $table->text('descripcion_original');
            $table->string('descripcion_normalizada', 255);
            $table->string('nro_comprobante_extraido', 100)->nullable();
            $table->string('cuit_extraido', 11)->nullable();
            $table->enum('tipo_movimiento', [
                'cobranza_cliente',
                'pago_proveedor',
                'gasto_bancario',
                'debito_rechazo',
                'otro',
                'desconocido',
                'pago_sueldos',
                'pago_servicios',
                'transferencia_propia',
                'impuesto_bancario'
            ])->default('desconocido');
            $table->enum('estado_conciliacion', [
                'pendiente',
                'en_disputa',
                'no_aplica',
                'revision_manual',
                'matcheado',
                'cerrado_definitivo'
            ])->default('pendiente');
            $table->timestamps();

            // Restricción de unicidad para evitar duplicados en re-procesamientos parciales
            $table->unique(
                ['fecha_operacion', 'monto', 'signo_monto', 'nro_comprobante_extraido', 'descripcion_normalizada'],
                'uk_movimiento_duplicado'
            );

            // Clave foránea al log de importación
            $table->foreign('id_archivo_importado_log', 'fk_MOVIMIENTO_BANCARIO_id_archivo_importado_log')
                ->references('id_archivo_importado_log')->on('ARCHIVO_IMPORTADO_LOG')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('MOVIMIENTO_BANCARIO');
    }
};
