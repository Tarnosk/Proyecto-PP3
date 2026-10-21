<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('zonas')) {
            Schema::create('zonas', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nombre', 120)->unique();
                $table->string('descripcion', 255)->nullable();
            });
        }

        Schema::table('clientes', function (Blueprint $table): void {
            if (! Schema::hasColumn('clientes', 'localidad')) {
                $table->string('localidad', 120)->nullable()->after('direccion');
            }
            if (! Schema::hasColumn('clientes', 'zona_id')) {
                $table->unsignedInteger('zona_id')->nullable()->after('localidad');
                $table->foreign('zona_id', 'fk_clientes_zona')
                    ->references('id')->on('zonas')->cascadeOnUpdate()->nullOnDelete();
            }
            if (! Schema::hasColumn('clientes', 'condicion_iva')) {
                $table->string('condicion_iva', 60)->nullable()->after('zona_id');
            }
            if (! Schema::hasColumn('clientes', 'saldo')) {
                $table->decimal('saldo', 12, 2)->default(0)->after('condicion_iva');
            }
        });

        if (! Schema::hasTable('categorias')) {
            Schema::create('categorias', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nombre', 120)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('marcas')) {
            Schema::create('marcas', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nombre', 120)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('productos')) {
            Schema::create('productos', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('codigo', 60)->unique();
                $table->string('nombre', 160);
                $table->text('descripcion')->nullable();
                $table->decimal('precio_mayorista', 12, 2)->default(0);
                $table->decimal('precio_minorista', 12, 2)->default(0);
                $table->string('imagen')->nullable();
                $table->integer('stock')->default(0);
                $table->integer('stock_minimo')->default(0);
                $table->unsignedInteger('dias_alerta_vencimiento')->default(30);
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->unsignedInteger('categoria_id')->nullable();
                $table->unsignedInteger('marca_id')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->timestamps();

                $table->index(['stock', 'stock_minimo'], 'idx_productos_stock_bajo');
                $table->foreign('categoria_id', 'fk_productos_categoria')
                    ->references('id')->on('categorias')->cascadeOnUpdate()->nullOnDelete();
                $table->foreign('marca_id', 'fk_productos_marca')
                    ->references('id')->on('marcas')->cascadeOnUpdate()->nullOnDelete();
                $table->foreign('usuario_id', 'fk_productos_usuario')
                    ->references('id')->on('usuarios')->cascadeOnUpdate()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('cliente_id');
                $table->dateTime('fecha');
                $table->decimal('total', 12, 2);
                $table->string('numero_factura', 80)->nullable();
                $table->enum('estado', ['pendiente', 'confirmada', 'pagada', 'facturada', 'cancelada'])->default('pendiente');
                $table->text('observaciones')->nullable();
                $table->unsignedInteger('usuario_id');
                $table->timestamps();

                $table->index('fecha', 'idx_ventas_fecha');
                $table->index(['cliente_id', 'fecha'], 'idx_ventas_cliente_fecha');
                $table->foreign('cliente_id', 'fk_ventas_cliente')
                    ->references('id')->on('clientes')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('usuario_id', 'fk_ventas_usuario')
                    ->references('id')->on('usuarios')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('detalle_ventas')) {
            Schema::create('detalle_ventas', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('venta_id');
                $table->unsignedInteger('producto_id');
                $table->unsignedInteger('promocion_id')->nullable();
                $table->unsignedInteger('cantidad');
                $table->decimal('precio_unitario', 12, 2);
                $table->decimal('descuento', 12, 2)->default(0);
                $table->decimal('subtotal', 12, 2);

                $table->foreign('venta_id', 'fk_detalle_ventas_venta')
                    ->references('id')->on('ventas')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('producto_id', 'fk_detalle_ventas_producto')
                    ->references('id')->on('productos')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('cobros')) {
            Schema::create('cobros', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('cliente_id');
                $table->unsignedInteger('usuario_id');
                $table->dateTime('fecha');
                $table->decimal('monto_total', 12, 2);
                $table->enum('medio_pago', ['efectivo', 'transferencia', 'tarjeta', 'cheque', 'otro']);
                $table->string('comprobante_nro', 100)->nullable();
                $table->enum('estado', ['confirmado', 'anulado'])->default('confirmado');
                $table->text('observaciones')->nullable();
                $table->dateTime('anulado_en')->nullable();
                $table->unsignedInteger('anulado_por')->nullable();
                $table->string('motivo_anulacion', 255)->nullable();
                $table->timestamps();

                $table->index(['cliente_id', 'fecha'], 'idx_cobros_cliente_fecha');
                $table->foreign('cliente_id', 'fk_cobros_cliente')
                    ->references('id')->on('clientes')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('usuario_id', 'fk_cobros_usuario')
                    ->references('id')->on('usuarios')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('anulado_por', 'fk_cobros_anulado_por')
                    ->references('id')->on('usuarios')->cascadeOnUpdate()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('cobro_ventas')) {
            Schema::create('cobro_ventas', function (Blueprint $table): void {
                $table->unsignedInteger('cobro_id');
                $table->unsignedInteger('venta_id');
                $table->decimal('monto_aplicado', 12, 2);

                $table->primary(['cobro_id', 'venta_id']);
                $table->foreign('cobro_id', 'fk_cobro_ventas_cobro')
                    ->references('id')->on('cobros')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('venta_id', 'fk_cobro_ventas_venta')
                    ->references('id')->on('ventas')->cascadeOnUpdate()->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cobro_ventas');
        Schema::dropIfExists('cobros');
        Schema::dropIfExists('detalle_ventas');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('marcas');
        Schema::dropIfExists('categorias');

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table): void {
                if (Schema::hasColumn('clientes', 'zona_id')) {
                    $table->dropForeign('fk_clientes_zona');
                }
                $columns = array_values(array_filter(
                    ['localidad', 'zona_id', 'condicion_iva', 'saldo'],
                    fn (string $column): bool => Schema::hasColumn('clientes', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('zonas');
    }
};
