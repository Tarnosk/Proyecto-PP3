<?php

namespace Database\Factories;

use App\Modules\Login\Models\User;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

/** @extends Factory<Producto> */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return [
                'codigo' => fake()->unique()->bothify('PROD-####'),
                'nombre' => fake()->words(3, true),
                'descripcion' => fake()->sentence(),
                'precio_mayorista' => fake()->randomFloat(2, 100, 10000),
                'precio_minorista' => fake()->randomFloat(2, 100, 12000),
                'stock' => fake()->numberBetween(0, 100),
                'stock_minimo' => 10,
                'estado' => 'activo',
            ];
        }

        $categoria = Categoria::query()->first() ?? Categoria::query()->create(['nombre' => 'General']);
        $marca = Marca::query()->first() ?? Marca::query()->create(['nombre' => 'Genérica']);
        $usuarioId = User::query()->value('id') ?? 1;

        return [
            'codigo' => fake()->unique()->bothify('PROD-####'),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'precio_unitario' => fake()->randomFloat(2, 100, 10000),
            'stock' => fake()->numberBetween(0, 100),
            'stock_minimo' => 10,
            'estado' => 'activo',
            'fecha_alta' => now()->toDateTimeString(),
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
            'id_usuario_carga' => $usuarioId,
        ];
    }
}
