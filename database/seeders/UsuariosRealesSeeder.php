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
        // 1. Actualizar usuario admin existente para que su nombre sea el nombre real "Don Alberto"
        DB::table('usuario')
            ->where('usuario', 'admin')
            ->orWhere('id_usuario', 21)
            ->update([
                'nombre' => 'Don Alberto',
                'rol' => 'administrativo',
            ]);

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

        $now = Carbon::now();
        $passwordHash = Hash::make('password123');

        foreach ($usuarios as $u) {
            $existente = DB::table('usuario')->where('usuario', $u['usuario'])->first();

            if ($existente) {
                DB::table('usuario')
                    ->where('id_usuario', $existente->id_usuario)
                    ->update([
                        'nombre' => $u['nombre'],
                        'rol' => $u['rol'],
                        'estado' => $u['estado'],
                    ]);
            } else {
                DB::table('usuario')->insert([
                    'usuario' => $u['usuario'],
                    'password_hash' => $passwordHash,
                    'nombre' => $u['nombre'],
                    'rol' => $u['rol'],
                    'estado' => $u['estado'],
                    'intentos_fallidos' => 0,
                    'created_at' => $now,
                ]);
            }
        }
    }
}
