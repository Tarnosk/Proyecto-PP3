<?php

namespace Database\Factories;

use App\Modules\Productos\Enums\ProductStatus;
use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Producto> */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('PROD-####'),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'precio_mayorista' => fake()->randomFloat(2, 100, 10000),
            'precio_minorista' => fake()->randomFloat(2, 100, 12000),
            'stock' => fake()->numberBetween(0, 100),
            'stock_minimo' => 10,
            'estado' => ProductStatus::ACTIVO,
        ];
    }
}
