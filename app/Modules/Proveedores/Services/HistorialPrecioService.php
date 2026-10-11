<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Services;

use App\Modules\Proveedores\Models\HistorialPrecioProveedor;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Repositories\HistorialPrecioProveedorRepository;
use App\Modules\Stock\Models\UnidadMedida;
use Carbon\Carbon;
use InvalidArgumentException;

class HistorialPrecioService
{
    public function __construct(
        protected HistorialPrecioProveedorRepository $repository = new HistorialPrecioProveedorRepository()
    ) {}

    /**
     * Registra un nuevo snapshot en el historial de precios del proveedor.
     */
    public function registrarSnapshot(
        int $idProductoProveedor,
        float $nuevoPrecio,
        int $idUsuario,
        ?int $idCompra = null,
        ?int $idUnidad = null,
        ?string $fechaActualizacion = null
    ): HistorialPrecioProveedor {
        if ($nuevoPrecio < 0) {
            throw new InvalidArgumentException('El precio acordado no puede ser negativo.');
        }

        $relacion = ProductoProveedor::find($idProductoProveedor);
        if (!$relacion) {
            throw new InvalidArgumentException("La relación producto-proveedor #{$idProductoProveedor} no existe.");
        }

        // Si no se especifica unidad, buscar la unidad base del producto
        if ($idUnidad === null || $idUnidad <= 0) {
            $idUnidad = UnidadMedida::where('id_producto', $relacion->id_producto)
                ->where('es_base', 1)
                ->value('id_unidad');

            if (!$idUnidad) {
                $idUnidad = UnidadMedida::where('es_base', 1)->value('id_unidad');
            }
        }

        $fecha = $fechaActualizacion ? Carbon::parse($fechaActualizacion) : Carbon::now();

        return HistorialPrecioProveedor::create([
            'id_producto_proveedor' => $relacion->id_producto_proveedor,
            'id_proveedor' => $relacion->id_proveedor,
            'id_producto' => $relacion->id_producto,
            'id_compra' => $idCompra,
            'id_unidad' => $idUnidad,
            'precio_acordado' => round($nuevoPrecio, 2),
            'fecha_actualizacion' => $fecha,
            'id_usuario' => $idUsuario,
        ]);
    }

    /**
     * Obtiene el historial formateado de un proveedor calculando las variaciones absolutas y porcentuales.
     */
    public function obtenerHistorialConVariaciones(
        int $idProveedor,
        ?int $idProducto = null,
        ?string $desde = null,
        ?string $hasta = null,
        string $orden = 'desc'
    ): array {
        // Obtenemos todos los registros cronológicos ascendentes para determinar los precios anteriores con exactitud
        $todosAsc = $this->repository->obtenerTodosAscendente($idProveedor, $idProducto);

        // Map para recordar el precio inmediatamente anterior por cada id_producto
        $preciosPreviosPorProducto = [];
        $itemsCalculados = [];

        $carbonDesde = !empty($desde) ? Carbon::parse($desde)->startOfDay() : null;
        $carbonHasta = !empty($hasta) ? Carbon::parse($hasta)->endOfDay() : null;

        foreach ($todosAsc as $registro) {
            $prodId = $registro->id_producto;
            $precioActual = (float) $registro->precio_acordado;
            $precioAnterior = $preciosPreviosPorProducto[$prodId] ?? null;

            $diferenciaMonto = null;
            $variacionPorcentual = 0.0;

            if ($precioAnterior !== null) {
                $diferenciaMonto = round($precioActual - $precioAnterior, 2);
                if ($precioAnterior > 0) {
                    $variacionPorcentual = round((($precioActual - $precioAnterior) / $precioAnterior) * 100, 2);
                }
            }

            // Actualizamos el último precio conocido de este producto para los siguientes registros
            $preciosPreviosPorProducto[$prodId] = $precioActual;

            // Filtro por fecha (si se especificó rango)
            $fechaReg = Carbon::parse($registro->fecha_actualizacion);
            if ($carbonDesde && $fechaReg->lt($carbonDesde)) {
                continue;
            }
            if ($carbonHasta && $fechaReg->gt($carbonHasta)) {
                continue;
            }

            $itemsCalculados[] = [
                'id_historial_prov' => $registro->id_historial_prov,
                'id_producto_proveedor' => $registro->id_producto_proveedor,
                'id_proveedor' => $registro->id_proveedor,
                'id_producto' => $registro->id_producto,
                'codigo_producto' => $registro->producto?->codigo,
                'nombre_producto' => $registro->producto?->nombre ?? $registro->producto?->descripcion,
                'descripcion_producto' => $registro->producto?->descripcion,
                'categoria' => $registro->producto?->categoria?->nombre,
                'marca' => $registro->producto?->marca?->nombre,
                'id_unidad' => $registro->id_unidad,
                'unidad' => $registro->unidad?->nombre ?? 'Unidad',
                'precio_acordado' => $precioActual,
                'precio_anterior' => $precioAnterior,
                'diferencia_monto' => $diferenciaMonto,
                'variacion_porcentual' => $variacionPorcentual,
                'fecha_actualizacion' => $fechaReg->format('Y-m-d H:i:s'),
                'fecha_formateada' => $fechaReg->format('Y-m-d H:i'),
                'id_compra' => $registro->id_compra,
                'numero_compra' => $registro->compra?->numero_compra,
                'origen' => $registro->id_compra
                    ? ('Factura Compra #' . ($registro->compra?->numero_compra ?? $registro->id_compra))
                    : 'Acuerdo comercial',
                'id_usuario' => $registro->id_usuario,
                'usuario_nombre' => $registro->usuario?->nombre ?? $registro->usuario?->user ?? 'Sistema',
            ];
        }

        // Ordenar según lo solicitado
        $ordenDesc = strtolower($orden) !== 'asc';
        usort($itemsCalculados, function ($a, $b) use ($ordenDesc) {
            $cmp = strcmp($a['fecha_actualizacion'], $b['fecha_actualizacion']);
            if ($cmp === 0) {
                $cmp = $a['id_historial_prov'] <=> $b['id_historial_prov'];
            }
            return $ordenDesc ? -$cmp : $cmp;
        });

        return $itemsCalculados;
    }

    /**
     * Genera un reporte comparativo y resumen de aumentos por producto de un proveedor.
     */
    public function obtenerResumenAumentos(
        int $idProveedor,
        ?string $desde = null,
        ?string $hasta = null
    ): array {
        $historial = $this->obtenerHistorialConVariaciones($idProveedor, null, $desde, $hasta, 'asc');

        if (empty($historial)) {
            return [
                'total_productos_con_historial' => 0,
                'total_registros' => 0,
                'variacion_promedio_general' => 0.0,
                'producto_mayor_aumento' => null,
                'producto_menor_aumento' => null,
                'productos' => [],
            ];
        }

        // Agrupar por producto
        $porProducto = [];
        foreach ($historial as $item) {
            $pid = $item['id_producto'];
            if (!isset($porProducto[$pid])) {
                $porProducto[$pid] = [];
            }
            $porProducto[$pid][] = $item;
        }

        $resumenProductos = [];
        $sumaVariaciones = 0.0;
        $mayorAumento = null;
        $menorAumento = null;

        foreach ($porProducto as $pid => $registros) {
            $primerRegistro = $registros[0];
            $ultimoRegistro = end($registros);

            $precioInicial = (float) $primerRegistro['precio_acordado'];
            $precioActual = (float) $ultimoRegistro['precio_acordado'];
            $variacionAbsoluta = round($precioActual - $precioInicial, 2);

            $variacionPorcentual = 0.0;
            if ($precioInicial > 0) {
                $variacionPorcentual = round((($precioActual - $precioInicial) / $precioInicial) * 100, 2);
            }

            $sumaVariaciones += $variacionPorcentual;

            $prodData = [
                'id_producto' => $pid,
                'codigo' => $primerRegistro['codigo_producto'],
                'nombre' => $primerRegistro['nombre_producto'],
                'unidad' => $primerRegistro['unidad'],
                'precio_inicial' => $precioInicial,
                'precio_actual' => $precioActual,
                'variacion_absoluta' => $variacionAbsoluta,
                'variacion_porcentual' => $variacionPorcentual,
                'total_cambios' => count($registros),
                'fecha_primer_precio' => $primerRegistro['fecha_formateada'],
                'fecha_ultimo_precio' => $ultimoRegistro['fecha_formateada'],
            ];

            $resumenProductos[] = $prodData;

            if ($mayorAumento === null || $variacionPorcentual > $mayorAumento['variacion_porcentual']) {
                $mayorAumento = $prodData;
            }

            if ($menorAumento === null || $variacionPorcentual < $menorAumento['variacion_porcentual']) {
                $menorAumento = $prodData;
            }
        }

        // Ordenar productos por mayor variación porcentual descendente
        usort($resumenProductos, fn($a, $b) => $b['variacion_porcentual'] <=> $a['variacion_porcentual']);

        $totalProds = count($resumenProductos);
        $promedioGeneral = $totalProds > 0 ? round($sumaVariaciones / $totalProds, 2) : 0.0;

        return [
            'total_productos_con_historial' => $totalProds,
            'total_registros' => count($historial),
            'variacion_promedio_general' => $promedioGeneral,
            'producto_mayor_aumento' => $mayorAumento,
            'producto_menor_aumento' => $menorAumento,
            'productos' => $resumenProductos,
        ];
    }
}
