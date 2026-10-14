<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsuariosRealesSeeder extends Seeder
{
    public function run(): void
    {
        $columnas = DB::getSchemaBuilder()->getColumnListing('usuario');
        $campoUsuario = in_array('user', $columnas, true) ? 'user' : 'usuario';
        $campoPassword = in_array('password', $columnas, true) ? 'password' : 'password_hash';
        $tieneNombre = in_array('nombre', $columnas, true);

        // 1. Actualizar usuario admin existente para que su nombre sea el nombre real "Don Alberto"
        $updateAdmin = ['rol' => 'administrativo'];
        if ($tieneNombre) {
            $updateAdmin['nombre'] = 'Don Alberto';
        }

        DB::table('usuario')
            ->where($campoUsuario, 'admin')
            ->orWhere('id_usuario', 21)
            ->update($updateAdmin);

        // 2. Usuarios del sistema con sus nombres y roles de negocio
        $usuarios = [
            [
                'usuario' => 'alberto',
                'nombre' => 'Don Alberto',
                'rol' => 'administrativo',
                'estado' => 'activo',
            ],
            [
                'usuario' => 'carlos',
                'nombre' => 'Carlos Gómez',
                'rol' => 'repartidor',
                'estado' => 'activo',
            ],
            [
                'usuario' => 'lucia',
                'nombre' => 'Lucía Pérez',
                'rol' => 'vendedor',
                'estado' => 'activo',
            ],
            [
                'usuario' => 'marta',
                'nombre' => 'Marta Rodríguez',
                'rol' => 'administrativo',
                'estado' => 'activo',
            ],
            [
                'usuario' => 'roberto',
                'nombre' => 'Roberto Di Marco',
                'rol' => 'repartidor',
                'estado' => 'inactivo',
            ],
        ];

        $passwordHash = Hash::make('password123');

        foreach ($usuarios as $u) {
            $existente = DB::table('usuario')->where($campoUsuario, $u['usuario'])->first();

            if ($existente) {
                $updateData = [
                    'rol' => $u['rol'],
                    'estado' => $u['estado'],
                ];

                if ($tieneNombre) {
                    $updateData['nombre'] = $u['nombre'];
                }

                DB::table('usuario')
                    ->where('id_usuario', $existente->id_usuario)
                    ->update($updateData);
            } else {
                $insertData = [
                    $campoUsuario => $u['usuario'],
                    $campoPassword => $passwordHash,
                    'rol' => $u['rol'],
                    'estado' => $u['estado'],
                ];

                if ($tieneNombre) {
                    $insertData['nombre'] = $u['nombre'];
                }

                DB::table('usuario')->insert($insertData);
            }
        }
    }
}
