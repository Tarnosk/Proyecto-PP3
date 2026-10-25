<?php

namespace App\Services;

class MockUsuarioApi
{
    public function todos(): array
    {
        return [
            ['id' => 1, 'nombre' => 'Administrador',       'rol' => 'administrativo'],
            ['id' => 2, 'nombre' => 'Pedro Gonzalez', 'rol' => 'administrativo'],
            ['id' => 3, 'nombre' => 'Ana Ramirez',       'rol' => 'repartidor'],
            ['id' => 4, 'nombre' => 'Diego Povich',       'rol' => 'repartidor'],
        ];
    }

    public function buscar(int $id): ?array
    {
        foreach ($this->todos() as $u) {
            if ($u['id'] === $id) return $u;
        }
        return null;
    }

    public function nombre(int $id): string
    {
        return $this->buscar($id)['nombre'] ?? "Usuario #{$id}";
    }
}
