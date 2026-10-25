<?php

namespace Database\Factories;

use App\Modules\Clientes\Enums\ClientStatus;
use App\Modules\Clientes\Enums\ClientType;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cliente> */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellido_razon_social' => fake()->lastName(),
            'dni_cuit' => fake()->unique()->numerify('20-########-#'),
            'telefono' => fake()->numerify('11-####-####'),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->streetAddress(),
            'tipo_cliente' => fake()->randomElement(ClientType::cases()),
            'estado' => ClientStatus::ACTIVO,
            'creado_por' => User::factory()->state(['rol' => UserRole::ADMINISTRATIVO]),
        ];
    }
}
