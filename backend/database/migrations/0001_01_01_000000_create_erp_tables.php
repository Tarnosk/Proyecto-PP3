<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->increments('id');
                $table->string('usuario', 50)->unique('uq_usuarios_usuario');
                $table->string('password_hash', 255);
                $table->string('nombre', 120);
                $table->enum('rol', ['administrativo', 'repartidor', 'contador']);
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->unsignedTinyInteger('intentos_fallidos')->default(0);
                $table->dateTime('bloqueado_hasta')->nullable();
                $table->dateTime('ultimo_intento_fallido')->nullable();
                $table->string('current_session_id', 128)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 120);
                $table->string('apellido_razon_social', 160);
                $table->string('dni_cuit', 20)->unique('uq_clientes_dni_cuit');
                $table->string('telefono', 30);
                $table->string('email', 160)->unique('uq_clientes_email');
                $table->string('direccion', 255);
                $table->enum('tipo_cliente', ['minorista', 'mayorista']);
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->unsignedInteger('creado_por');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('creado_por', 'idx_clientes_creado_por');
                $table->foreign('creado_por', 'fk_clientes_creado_por')
                    ->references('id')->on('usuarios')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('login_logs')) {
            Schema::create('login_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('usuario_id');
                $table->timestamp('fecha_hora')->useCurrent();
                $table->string('ip', 45)->nullable();

                $table->index('usuario_id', 'idx_login_logs_usuario_id');
                $table->foreign('usuario_id', 'fk_login_logs_usuario_id')
                    ->references('id')->on('usuarios')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('reclamos')) {
            Schema::create('reclamos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('cliente_id');
                $table->unsignedInteger('usuario_id');
                $table->string('asunto', 160);
                $table->text('descripcion');
                $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
                $table->enum('estado', ['abierto', 'en_proceso', 'cerrado'])->default('abierto');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('cliente_id', 'idx_reclamos_cliente_id');
                $table->index('usuario_id', 'idx_reclamos_usuario_id');
                $table->index('estado', 'idx_reclamos_estado');
                $table->foreign('cliente_id', 'fk_reclamos_cliente_id')
                    ->references('id')->on('clientes')
                    ->cascadeOnUpdate()->restrictOnDelete();
                $table->foreign('usuario_id', 'fk_reclamos_usuario_id')
                    ->references('id')->on('usuarios')
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('usuarios');
    }
};
