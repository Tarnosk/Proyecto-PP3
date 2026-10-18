<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario' => fake()->unique()->userName(),
            'password_hash' => Hash::make('Password1234'),
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'rol' => UserRole::REPARTIDOR,
            'estado' => UserStatus::ACTIVO,
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'ultimo_intento_fallido' => null,
            'current_session_id' => null,
        ];
    }
}
