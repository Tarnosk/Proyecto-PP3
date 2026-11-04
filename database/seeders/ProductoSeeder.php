<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Login\Models\User;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\HistorialPrecio;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\HistorialPrecioProveedor;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Models\Ubicacion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $idUsuario = (int) (
            (Schema::hasTable('usuarios') ? DB::table('usuarios')->value('id') : null)
            ?? (Schema::hasTable('usuario') ? DB::table('usuario')->value('id_usuario') : null)
            ?? 1
        );

        $hoy = Carbon::now()->toDateString();
        $ahora = Carbon::now()->toDateTimeString();

        // 1. Categorías
        $categoriasNombres = [
            'Bebidas',
            'Panadería',
            'Almacén',
            'Limpieza',
            'Lácteos y Frescos',
        ];

        $categoriasMap = [];
        foreach ($categoriasNombres as $nombre) {
            $cat = Categoria::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['fecha_creacion' => $hoy]
            );
            $categoriasMap[$nombre] = (int) $cat->id_categoria;
        }

        // 2. Marcas
        $marcasNombres = [
            'Coca-Cola',
            'Bimbo',
            'Playadito',
            'Natura',
            'Gallo',
            'Matarazzo',
            'Villavicencio',
            'Quilmes',
            'Ledesma',
            'Pureza',
            'De La Huerta',
            'Ala',
            'Ayudín',
            'Magistral',
            'La Serenísima',
        ];

        $marcasMap = [];
        foreach ($marcasNombres as $nombre) {
            $m = Marca::query()->firstOrCreate(
                ['nombre' => $nombre],
                ['fecha_creacion' => $hoy]
            );
            $marcasMap[$nombre] = (int) $m->id_marca;
        }

        // 3. Proveedores
        $proveedoresData = [
            [
                'razon_social' => 'Distribuidora Central SA',
                'contacto' => 'Juan Pérez',
                'cuit' => '30-71234567-8',
                'telefono' => '1145550101',
                'email' => 'ventas@distribuidoracentral.com.ar',
                'direccion' => 'Av. Industrial 1200',
                'estado' => 'activo',
                'plazo_entrega_dias' => 3,
                'fecha_alta' => $hoy,
                'id_usuario_carga' => $idUsuario,
            ],
            [
                'razon_social' => 'Molinos y Alimentos del Sur SRL',
                'contacto' => 'Mariana Gómez',
                'cuit' => '30-88334455-9',
                'telefono' => '1148880202',
                'email' => 'pedidos@molinossur.com.ar',
                'direccion' => 'Ruta 3 Km 45',
                'estado' => 'activo',
                'plazo_entrega_dias' => 5,
                'fecha_alta' => $hoy,
                'id_usuario_carga' => $idUsuario,
            ],
            [
                'razon_social' => 'Química & Limpieza Integral SA',
                'contacto' => 'Carlos López',
                'cuit' => '30-66442211-3',
                'telefono' => '1147770303',
                'email' => 'contacto@quimicaintegral.com.ar',
                'direccion' => 'Parque Industrial Lote 14',
                'estado' => 'activo',
                'plazo_entrega_dias' => 4,
                'fecha_alta' => $hoy,
                'id_usuario_carga' => $idUsuario,
            ],
        ];

        $proveedoresMap = [];
        foreach ($proveedoresData as $pData) {
            $prov = Proveedor::query()->firstOrCreate(
                ['cuit' => $pData['cuit']],
                $pData
            );
            $proveedoresMap[$pData['razon_social']] = (int) $prov->id_proveedor;
        }

        // 4. Ubicaciones de depósito (opcional si existe la tabla)
        $ubicacionesMap = [];
        if (Schema::hasTable('ubicacion')) {
            $ubicacionesDefault = [
                'Bebidas' => 'Estante A - Bebidas',
                'Panadería' => 'Deposito Central',
                'Almacén' => 'Deposito Central',
                'Limpieza' => 'Estante C - Limpieza',
                'Lácteos y Frescos' => 'Estante B - Lacteos',
            ];
            foreach ($ubicacionesDefault as $cat => $desc) {
                $ubic = DB::table('ubicacion')->where('descripcion', $desc)->first();
                if (!$ubic) {
                    $idU = DB::table('ubicacion')->insertGetId([
                        'descripcion' => $desc,
                        'estado' => 'activo',
                        'fecha_creacion' => $hoy,
                    ]);
                    $ubicacionesMap[$cat] = (int) $idU;
                } else {
                    $ubicacionesMap[$cat] = (int) $ubic->id_ubicacion;
                }
            }
        }

        // 5. Catálogo de Productos
        // Incluye los 6 productos base para no romper integraciones existentes (P001 - P006) más variedad
        $productosData = [
            // Bebidas
            [
                'codigo' => 'P001',
                'nombre' => 'Coca-Cola 1.5L',
                'descripcion' => 'Gaseosa Coca-Cola botella 1.5L',
                'precio_unitario' => 2500.00,
                'costo' => 1785.71,
                'recargo' => 40.00,
                'stock' => 48,
                'stock_minimo' => 12,
                'formato_bulto' => 'Pack',
                'unidades_por_bulto' => 6,
                'categoria' => 'Bebidas',
                'marca' => 'Coca-Cola',
                'proveedor' => 'Distribuidora Central SA',
                'ubicacion_id' => 1,
            ],
            [
                'codigo' => 'P007',
                'nombre' => 'Agua Mineral Villavicencio 2L',
                'descripcion' => 'Agua mineral natural sin gas 2L',
                'precio_unitario' => 1200.00,
                'costo' => 800.00,
                'recargo' => 50.00,
                'stock' => 60,
                'stock_minimo' => 18,
                'formato_bulto' => 'Pack',
                'unidades_por_bulto' => 6,
                'categoria' => 'Bebidas',
                'marca' => 'Villavicencio',
                'proveedor' => 'Distribuidora Central SA',
                'ubicacion_id' => 1,
            ],
            [
                'codigo' => 'P008',
                'nombre' => 'Cerveza Quilmes Clásica 1L',
                'descripcion' => 'Cerveza rubia retornable 1L',
                'precio_unitario' => 2800.00,
                'costo' => 2000.00,
                'recargo' => 40.00,
                'stock' => 36,
                'stock_minimo' => 12,
                'formato_bulto' => 'Cajón',
                'unidades_por_bulto' => 12,
                'categoria' => 'Bebidas',
                'marca' => 'Quilmes',
                'proveedor' => 'Distribuidora Central SA',
                'ubicacion_id' => 1,
            ],

            // Panadería
            [
                'codigo' => 'P002',
                'nombre' => 'Pan lactal grande Bimbo',
                'descripcion' => 'Pan lactal blanco grande 500g',
                'precio_unitario' => 1800.00,
                'costo' => 1333.33,
                'recargo' => 35.00,
                'stock' => 30,
                'stock_minimo' => 8,
                'formato_bulto' => 'Bolsa',
                'unidades_por_bulto' => 1,
                'categoria' => 'Panadería',
                'marca' => 'Bimbo',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],

            // Almacén
            [
                'codigo' => 'P003',
                'nombre' => 'Yerba Mate Playadito 1kg',
                'descripcion' => 'Yerba Mate Playadito con palo 1kg',
                'precio_unitario' => 4200.00,
                'costo' => 3000.00,
                'recargo' => 40.00,
                'stock' => 50,
                'stock_minimo' => 10,
                'formato_bulto' => 'Fardo',
                'unidades_por_bulto' => 10,
                'categoria' => 'Almacén',
                'marca' => 'Playadito',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],
            [
                'codigo' => 'P004',
                'nombre' => 'Aceite girasol Natura 900ml',
                'descripcion' => 'Aceite de girasol puro 900ml',
                'precio_unitario' => 2100.00,
                'costo' => 1500.00,
                'recargo' => 40.00,
                'stock' => 60,
                'stock_minimo' => 12,
                'formato_bulto' => 'Caja',
                'unidades_por_bulto' => 12,
                'categoria' => 'Almacén',
                'marca' => 'Natura',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],
            [
                'codigo' => 'P005',
                'nombre' => 'Arroz largo fino Gallo 1kg',
                'descripcion' => 'Arroz blanco largo fino 1kg',
                'precio_unitario' => 1300.00,
                'costo' => 1000.00,
                'recargo' => 30.00,
                'stock' => 80,
                'stock_minimo' => 15,
                'formato_bulto' => 'Fardo',
                'unidades_por_bulto' => 10,
                'categoria' => 'Almacén',
                'marca' => 'Gallo',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],
            [
                'codigo' => 'P006',
                'nombre' => 'Fideos secos Matarazzo 500g',
                'descripcion' => 'Fideos tallarines secos 500g',
                'precio_unitario' => 950.00,
                'costo' => 700.00,
                'recargo' => 35.71,
                'stock' => 100,
                'stock_minimo' => 20,
                'formato_bulto' => 'Caja',
                'unidades_por_bulto' => 20,
                'categoria' => 'Almacén',
                'marca' => 'Matarazzo',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],
            [
                'codigo' => 'P009',
                'nombre' => 'Azúcar Ledesma Clásica 1kg',
                'descripcion' => 'Azúcar blanca refinada tipo A 1kg',
                'precio_unitario' => 1100.00,
                'costo' => 800.00,
                'recargo' => 37.50,
                'stock' => 90,
                'stock_minimo' => 20,
                'formato_bulto' => 'Fardo',
                'unidades_por_bulto' => 10,
                'categoria' => 'Almacén',
                'marca' => 'Ledesma',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],
            [
                'codigo' => 'P010',
                'nombre' => 'Harina 000 Pureza 1kg',
                'descripcion' => 'Harina de trigo 000 para repostería y pan 1kg',
                'precio_unitario' => 980.00,
                'costo' => 700.00,
                'recargo' => 40.00,
                'stock' => 70,
                'stock_minimo' => 15,
                'formato_bulto' => 'Fardo',
                'unidades_por_bulto' => 10,
                'categoria' => 'Almacén',
                'marca' => 'Pureza',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],
            [
                'codigo' => 'P011',
                'nombre' => 'Puré de tomate De La Huerta 520g',
                'descripcion' => 'Puré de tomate tetra pack 520g',
                'precio_unitario' => 750.00,
                'costo' => 500.00,
                'recargo' => 50.00,
                'stock' => 120,
                'stock_minimo' => 24,
                'formato_bulto' => 'Caja',
                'unidades_por_bulto' => 12,
                'categoria' => 'Almacén',
                'marca' => 'De La Huerta',
                'proveedor' => 'Molinos y Alimentos del Sur SRL',
                'ubicacion_id' => 2,
            ],

            // Limpieza
            [
                'codigo' => 'P012',
                'nombre' => 'Jabón líquido para ropa Ala 3L',
                'descripcion' => 'Jabón líquido matic para lavarropas 3L',
                'precio_unitario' => 6500.00,
                'costo' => 4500.00,
                'recargo' => 44.44,
                'stock' => 40,
                'stock_minimo' => 8,
                'formato_bulto' => 'Bidón',
                'unidades_por_bulto' => 1,
                'categoria' => 'Limpieza',
                'marca' => 'Ala',
                'proveedor' => 'Química & Limpieza Integral SA',
                'ubicacion_id' => 3,
            ],
            [
                'codigo' => 'P013',
                'nombre' => 'Lavandina Ayudín Común 1L',
                'descripcion' => 'Lavandina clásica desinfectante 1L',
                'precio_unitario' => 850.00,
                'costo' => 600.00,
                'recargo' => 41.67,
                'stock' => 84,
                'stock_minimo' => 24,
                'formato_bulto' => 'Caja',
                'unidades_por_bulto' => 12,
                'categoria' => 'Limpieza',
                'marca' => 'Ayudín',
                'proveedor' => 'Química & Limpieza Integral SA',
                'ubicacion_id' => 3,
            ],
            [
                'codigo' => 'P014',
                'nombre' => 'Detergente Magistral Limón 500ml',
                'descripcion' => 'Detergente lavavajillas ultra concentrado 500ml',
                'precio_unitario' => 1600.00,
                'costo' => 1100.00,
                'recargo' => 45.45,
                'stock' => 60,
                'stock_minimo' => 12,
                'formato_bulto' => 'Caja',
                'unidades_por_bulto' => 12,
                'categoria' => 'Limpieza',
                'marca' => 'Magistral',
                'proveedor' => 'Química & Limpieza Integral SA',
                'ubicacion_id' => 3,
            ],
        ];

        $hasCostoCol = Schema::hasColumn('producto', 'costo');
        $hasRecargoCol = Schema::hasColumn('producto', 'recargo');

        foreach ($productosData as $p) {
            $catNombre = $p['categoria'];
            $marcaNombre = $p['marca'];
            $provNombre = $p['proveedor'];
            $costo = $p['costo'];

            unset($p['categoria'], $p['marca'], $p['proveedor']);
            if (isset($p['ubicacion_id'])) {
                unset($p['ubicacion_id']);
            }
            if (!$hasCostoCol) {
                unset($p['costo']);
            }
            if (!$hasRecargoCol) {
                unset($p['recargo']);
            }

            $p['id_categoria'] = $categoriasMap[$catNombre] ?? 1;
            $p['id_marca'] = $marcasMap[$marcaNombre] ?? 1;
            $idProvPrincipal = $proveedoresMap[$provNombre] ?? 1;
            $idUbic = $ubicacionesMap[$catNombre] ?? (Schema::hasTable('ubicacion') ? DB::table('ubicacion')->value('id_ubicacion') : null);

            $p['estado'] = 'activo';
            $p['fecha_alta'] = $hoy;
            $p['id_usuario_carga'] = $idUsuario;

            $producto = Producto::query()->updateOrCreate(
                ['codigo' => $p['codigo']],
                $p
            );

            $idProd = (int) $producto->id_producto;

            // Asociación con proveedor principal
            ProductoProveedor::query()->updateOrCreate(
                [
                    'id_producto' => $idProd,
                    'id_proveedor' => $idProvPrincipal,
                ],
                [
                    'es_proveedor_principal' => true,
                    'precio_acordado' => $costo,
                    'activo' => true,
                    'fecha_asociacion' => $hoy,
                    'id_usuario' => $idUsuario,
                ]
            );

            // Desmarcar otros como principal si los hubiera
            ProductoProveedor::query()
                ->where('id_producto', $idProd)
                ->where('id_proveedor', '!=', $idProvPrincipal)
                ->update(['es_proveedor_principal' => false]);

            // Historial de precios inicial
            HistorialPrecio::query()->updateOrCreate(
                [
                    'id_producto' => $idProd,
                    'origen' => 'manual',
                    'tipo_precio' => 'general',
                ],
                [
                    'precio' => $p['precio_unitario'],
                    'porcentaje_aumento' => 0.00,
                    'regla_redondeo' => 'sin_redondeo',
                    'fecha_cambio' => $ahora,
                    'id_usuario' => $idUsuario,
                ]
            );

            // Asignación de ubicación en depósito
            if (Schema::hasTable('producto_ubicacion') && $idUbic) {
                DB::table('producto_ubicacion')->updateOrInsert(
                    ['id_producto' => $idProd, 'id_ubicacion' => $idUbic],
                    ['fecha_asignacion' => $hoy, 'id_usuario' => $idUsuario]
                );
            }
        }
    }
}
