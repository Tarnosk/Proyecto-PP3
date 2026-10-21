<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('usuarios', 'email')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->string('email', 160)->nullable()->unique('uq_usuarios_email');
            });
        }

        if (! Schema::hasTable('password_reset_codes')) {
            Schema::create('password_reset_codes', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('usuario_id')->unique('uq_password_reset_codes_usuario_id');
                $table->string('code_hash', 255);
                $table->unsignedTinyInteger('intentos')->default(0);
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->foreign('usuario_id', 'fk_password_reset_codes_usuario_id')
                    ->references('id')->on('usuarios')
                    ->cascadeOnUpdate()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');

        if (Schema::hasColumn('usuarios', 'email')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->dropUnique('uq_usuarios_email');
                $table->dropColumn('email');
            });
        }
    }
};
