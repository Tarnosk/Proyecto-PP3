<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Venta> */
class VentaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'fecha' => fake()->dateTimeBetween('-30 days'),
            'total' => fake()->randomFloat(2, 500, 50000),
            'numero_factura' => fake()->unique()->numerify('FC-########'),
            'estado' => SaleStatus::CONFIRMADA,
            'observaciones' => null,
            'usuario_id' => User::factory()->state(['rol' => UserRole::REPARTIDOR]),
        ];
    }
}
