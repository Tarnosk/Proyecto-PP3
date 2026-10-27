<?php

namespace App\Modules\Shared\Services;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;

/** Directorio de sólo lectura de G1. Nunca crea usuarios ni devuelve mocks. */
class UsuarioService
{
    public static function obtenerRepartidores(): array
    {
        return User::where('rol', UserRole::REPARTIDOR->value)
            ->where('estado', UserStatus::ACTIVO->value)->orderBy('nombre')->get()
            ->map(fn (User $user) => self::map($user))->all();
    }

    public static function obtenerNombre(int $idUsuario): string
    {
        return self::obtenerUsuario($idUsuario)['nombre'] ?? "Usuario #{$idUsuario}";
    }

    public static function obtenerUsuario(int $idUsuario): ?array
    {
        $user = User::where('estado', UserStatus::ACTIVO->value)->find($idUsuario);

        return $user ? self::map($user) : null;
    }

    private static function map(User $user): array
    {
        return [
            'id_usuario' => (int) $user->getKey(),
            'nombre' => $user->nombre,
            'rol' => $user->rol === UserRole::ADMINISTRATIVO ? 'admin' : $user->rol->value,
            'user' => $user->usuario,
            'email' => $user->email,
        ];
    }
}
