<?php

namespace Database\Factories;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use App\Modules\Ventas\Enums\SaleStatus;
use App\Modules\Ventas\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Venta> */
class VentaFactory extends Factory
{
    protected $model = Venta::class;

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
