<?php

namespace Database\Factories;

use App\Models\Usuario;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Producto> */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        $categoria = Categoria::query()->first() ?? Categoria::query()->create(['nombre' => 'General']);
        $marca = Marca::query()->first() ?? Marca::query()->create(['nombre' => 'Genérica']);
        $usuarioId = Usuario::query()->value('id_usuario') ?? 1;

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
