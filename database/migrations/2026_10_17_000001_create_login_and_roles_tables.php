<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->increments('id');
                $table->string('usuario', 50)->unique('uq_usuarios_usuario');
                $table->string('password_hash', 255);
                $table->string('nombre', 120);
                $table->string('email', 160)->nullable();
                $table->enum('rol', ['administrativo', 'repartidor', 'contador'])->default('administrativo');
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->unsignedTinyInteger('intentos_fallidos')->default(0);
                $table->dateTime('bloqueado_hasta')->nullable();
                $table->dateTime('ultimo_intento_fallido')->nullable();
                $table->string('current_session_id', 128)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('login_logs')) {
            Schema::create('login_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('usuario_id');
                $table->timestamp('fecha_hora')->useCurrent();
                $table->string('ip', 45)->nullable();

                $table->index('usuario_id', 'idx_login_logs_usuario_id');
                if (Schema::hasTable('usuarios')) {
                    $table->foreign('usuario_id', 'fk_login_logs_usuario_id')
                        ->references('id')->on('usuarios')
                        ->cascadeOnUpdate()->restrictOnDelete();
                }
            });
        }

        // Si la tabla 'usuario' de G3 ya contenía registros, sincronizamos hacia 'usuarios'
        if (Schema::hasTable('usuario') && Schema::hasTable('usuarios')) {
            try {
                $existentes = DB::table('usuario')->get();
                foreach ($existentes as $u) {
                    $username = $u->usuario ?? $u->user ?? null;
                    if (!$username) continue;

                    $rolVal = in_array($u->rol ?? '', ['administrativo', 'repartidor', 'contador'], true)
                        ? $u->rol
                        : 'administrativo';

                    DB::table('usuarios')->updateOrInsert(
                        ['usuario' => $username],
                        [
                            'id' => $u->id_usuario ?? $u->id ?? null,
                            'password_hash' => $u->password_hash ?? $u->password ?? '',
                            'nombre' => $u->nombre ?? $username,
                            'rol' => $rolVal,
                            'estado' => $u->estado ?? 'activo',
                            'intentos_fallidos' => (int) ($u->intentos_fallidos ?? 0),
                            'bloqueado_hasta' => $u->bloqueado_hasta ?? null,
                            'ultimo_intento_fallido' => $u->ultimo_intento_fallido ?? null,
                            'current_session_id' => $u->current_session_id ?? null,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // Sincronización inicial no bloqueante
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('usuarios');
    }
};
