<?php

namespace Database\Factories;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Ventas\Enums\PaymentMethod;
use App\Modules\Ventas\Enums\PaymentStatus;
use App\Modules\Ventas\Models\Cobro;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cobro> */
class CobroFactory extends Factory
{
    protected $model = Cobro::class;

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
