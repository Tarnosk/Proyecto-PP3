<?php

namespace Database\Factories;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

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
