<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['usuario' => 'admin', 'password' => 'Admin1234', 'nombre' => 'Usuario Administrativo', 'email' => 'admin@example.com', 'rol' => UserRole::ADMINISTRATIVO, 'estado' => UserStatus::ACTIVO],
            ['usuario' => 'repartidor', 'password' => 'Repartidor1234', 'nombre' => 'Usuario Repartidor', 'email' => 'repartidor@example.com', 'rol' => UserRole::REPARTIDOR, 'estado' => UserStatus::ACTIVO],
            ['usuario' => 'contador', 'password' => 'Contador1234', 'nombre' => 'Usuario Contador', 'email' => 'contador@example.com', 'rol' => UserRole::CONTADOR, 'estado' => UserStatus::ACTIVO],
            ['usuario' => 'inactivo', 'password' => 'Inactivo1234', 'nombre' => 'Usuario Inactivo', 'email' => 'inactivo@example.com', 'rol' => UserRole::REPARTIDOR, 'estado' => UserStatus::INACTIVO],
        ];

        foreach ($users as $data) {
            User::query()->updateOrCreate(
                ['usuario' => $data['usuario']],
                [
                    'password_hash' => Hash::make($data['password']),
                    'nombre' => $data['nombre'],
                    'email' => $data['email'],
                    'rol' => $data['rol'],
                    'estado' => $data['estado'],
                ],
            );
        }
    }
}
