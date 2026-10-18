<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Cliente;
use App\Models\Cobro;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cobro> */
class CobroFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'usuario_id' => User::factory()->state(['rol' => UserRole::ADMINISTRATIVO]),
            'fecha' => now(),
            'monto_total' => fake()->randomFloat(2, 100, 5000),
            'medio_pago' => PaymentMethod::EFECTIVO,
            'estado' => PaymentStatus::CONFIRMADO,
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
