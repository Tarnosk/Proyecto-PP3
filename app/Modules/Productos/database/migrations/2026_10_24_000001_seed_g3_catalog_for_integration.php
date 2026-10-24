<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // 1. Sincronizar usuarios existentes entre `usuarios` (G1) y `usuario` (FKs G3)
        if (Schema::hasTable('usuarios') && Schema::hasTable('usuario')) {
            $g1Users = DB::table('usuarios')->get();
            foreach ($g1Users as $u) {
                if (! DB::table('usuario')->where('id_usuario', $u->id)->exists()) {
                    $loginName = $u->usuario;
                    if (DB::table('usuario')->where('usuario', $loginName)->exists()) {
                        $loginName = $loginName.'_'.$u->id;
                    }
                    DB::table('usuario')->insertOrIgnore([
                        'id_usuario' => $u->id,
                        'usuario' => $loginName,
                        'password_hash' => $u->password_hash,
                        'nombre' => $u->nombre,
                        'rol' => in_array($u->rol, ['administrativo', 'repartidor', 'contador', 'vendedor', 'deposito'], true)
                            ? $u->rol
                            : 'administrativo',
                        'estado' => $u->estado ?? 'activo',
                    ]);
                }
            }

            if (DB::table('usuario')->count() === 0) {
                $hash = Hash::make('Admin123!');
                DB::table('usuarios')->insertOrIgnore([
                    'id' => 1,
                    'usuario' => 'admin',
                    'password_hash' => $hash,
                    'nombre' => 'Administrador',
                    'rol' => 'administrativo',
                    'estado' => 'activo',
                ]);
                DB::table('usuario')->insertOrIgnore([
                    'id_usuario' => 1,
                    'usuario' => 'admin',
                    'password_hash' => $hash,
                    'nombre' => 'Administrador',
                    'rol' => 'administrativo',
                    'estado' => 'activo',
                ]);
            }
        }

        // 2. Sembrar catálogo base compartido con G1/G4 únicamente si `producto` está vacía
        if (! Schema::hasTable('producto') || DB::table('producto')->count() > 0) {
            return;
        }

        $idUsuario = (int) (DB::table('usuario')->value('id_usuario') ?? 1);
        $hoy = '2026-09-01';

        if (Schema::hasTable('categoria') && DB::table('categoria')->count() === 0) {
            DB::table('categoria')->insertOrIgnore([
                ['id_categoria' => 1, 'nombre' => 'Bebidas', 'fecha_creacion' => $hoy],
                ['id_categoria' => 2, 'nombre' => 'Panadería', 'fecha_creacion' => $hoy],
                ['id_categoria' => 3, 'nombre' => 'Almacén', 'fecha_creacion' => $hoy],
            ]);
        }

        if (Schema::hasTable('marca') && DB::table('marca')->count() === 0) {
            DB::table('marca')->insertOrIgnore([
                ['id_marca' => 1, 'nombre' => 'Coca-Cola', 'fecha_creacion' => $hoy],
                ['id_marca' => 2, 'nombre' => 'Bimbo', 'fecha_creacion' => $hoy],
                ['id_marca' => 3, 'nombre' => 'Playadito', 'fecha_creacion' => $hoy],
                ['id_marca' => 4, 'nombre' => 'Natura', 'fecha_creacion' => $hoy],
                ['id_marca' => 5, 'nombre' => 'Gallo', 'fecha_creacion' => $hoy],
                ['id_marca' => 6, 'nombre' => 'Matarazzo', 'fecha_creacion' => $hoy],
            ]);
        }

        $productos = [
            [
                'id_producto' => 1,
                'codigo' => 'P001',
                'nombre' => 'Coca-Cola 1.5L',
                'descripcion' => 'Gaseosa Coca-Cola botella 1.5L',
                'precio_unitario' => 2500.00,
                'stock' => 48,
                'stock_minimo' => 10,
                'dias_alerta_vencimiento' => 30,
                'estado' => 'activo',
                'fecha_alta' => '2026-09-01',
                'id_categoria' => 1,
                'id_marca' => 1,
                'id_usuario_carga' => $idUsuario,
                'formato_bulto' => 'Pack',
                'unidades_por_bulto' => 6,
            ],
            [
                'id_producto' => 2,
                'codigo' => 'P002',
                'nombre' => 'Pan lactal grande',
                'descripcion' => 'Pan lactal grande 500g',
                'precio_unitario' => 1800.00,
                'stock' => 30,
                'stock_minimo' => 8,
                'dias_alerta_vencimiento' => 15,
                'estado' => 'activo',
                'fecha_alta' => '2026-09-02',
                'id_categoria' => 2,
                'id_marca' => 2,
                'id_usuario_carga' => $idUsuario,
                'formato_bulto' => 'Bolsa',
                'unidades_por_bulto' => 1,
            ],
            [
                'id_producto' => 3,
                'codigo' => 'P003',
                'nombre' => 'Yerba Mate 1kg',
                'descripcion' => 'Yerba Mate Playadito 1kg',
                'precio_unitario' => 4200.00,
                'stock' => 25,
                'stock_minimo' => 10,
                'dias_alerta_vencimiento' => 60,
                'estado' => 'activo',
                'fecha_alta' => '2026-09-05',
                'id_categoria' => 3,
                'id_marca' => 3,
                'id_usuario_carga' => $idUsuario,
                'formato_bulto' => 'Fardo',
                'unidades_por_bulto' => 10,
            ],
            [
                'id_producto' => 4,
                'codigo' => 'P004',
                'nombre' => 'Aceite girasol 900ml',
                'descripcion' => 'Aceite de girasol 900ml',
                'precio_unitario' => 2100.00,
                'stock' => 60,
                'stock_minimo' => 12,
                'dias_alerta_vencimiento' => 60,
                'estado' => 'activo',
                'fecha_alta' => '2026-09-10',
                'id_categoria' => 3,
                'id_marca' => 4,
                'id_usuario_carga' => $idUsuario,
                'formato_bulto' => 'Caja',
                'unidades_por_bulto' => 12,
            ],
            [
                'id_producto' => 5,
                'codigo' => 'P005',
                'nombre' => 'Arroz 1kg',
                'descripcion' => 'Arroz largo fino 1kg',
                'precio_unitario' => 1300.00,
                'stock' => 80,
                'stock_minimo' => 15,
                'dias_alerta_vencimiento' => 90,
                'estado' => 'activo',
                'fecha_alta' => '2026-09-12',
                'id_categoria' => 3,
                'id_marca' => 5,
                'id_usuario_carga' => $idUsuario,
                'formato_bulto' => 'Fardo',
                'unidades_por_bulto' => 10,
            ],
            [
                'id_producto' => 6,
                'codigo' => 'P006',
                'nombre' => 'Fideos 500g',
                'descripcion' => 'Fideos secos 500g',
                'precio_unitario' => 950.00,
                'stock' => 100,
                'stock_minimo' => 20,
                'dias_alerta_vencimiento' => 90,
                'estado' => 'activo',
                'fecha_alta' => '2026-09-15',
                'id_categoria' => 3,
                'id_marca' => 6,
                'id_usuario_carga' => $idUsuario,
                'formato_bulto' => 'Caja',
                'unidades_por_bulto' => 20,
            ],
        ];

        DB::table('producto')->insertOrIgnore($productos);
    }

    public function down(): void
    {
        // No destructivo
    }
};
