<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Proveedores\Services\HistorialPrecioService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HistorialPrecioController extends Controller
{
    public function __construct(
        protected HistorialPrecioService $service = new HistorialPrecioService()
    ) {}

    /**
     * CA-01 a CA-05: Listar historial de precios de todos los productos de un proveedor.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => 'El proveedor no existe.',
            ], 404);
        }

        $idProducto = $request->filled('id_producto') ? (int) $request->input('id_producto') : null;
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $orden = $request->input('orden', 'desc');

        $data = $this->service->obtenerHistorialConVariaciones(
            $id,
            $idProducto,
            $fechaDesde,
            $fechaHasta,
            (string) $orden
        );

        $resumen = $this->service->obtenerResumenAumentos($id, $fechaDesde, $fechaHasta);

        return response()->json([
            'status' => 'success',
            'proveedor' => [
                'id_proveedor' => $proveedor->id_proveedor,
                'razon_social' => $proveedor->razon_social,
                'cuit' => $proveedor->cuit,
                'estado' => $proveedor->estado,
            ],
            'data' => $data,
            'total' => count($data),
            'resumen' => $resumen,
        ], 200);
    }

    /**
     * Consulta del historial de precios para un producto específico suministrado por el proveedor.
     */
    public function porProducto(Request $request, int $id, int $idProducto): JsonResponse
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => 'El proveedor no existe.',
            ], 404);
        }

        $producto = Producto::with(['categoria', 'marca'])->find($idProducto);
        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto no existe.',
            ], 404);
        }

        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $orden = $request->input('orden', 'desc');

        $historial = $this->service->obtenerHistorialConVariaciones(
            $id,
            $idProducto,
            $fechaDesde,
            $fechaHasta,
            (string) $orden
        );

        // Estadísticas específicas del producto
        $totalCambios = count($historial);
        $precioInicial = null;
        $precioActual = null;
        $variacionTotalMonto = 0.0;
        $variacionTotalPct = 0.0;

        if ($totalCambios > 0) {
            $cronologicoAsc = $orden === 'asc' ? $historial : array_reverse($historial);
            $precioInicial = (float) $cronologicoAsc[0]['precio_acordado'];
            $precioActual = (float) end($cronologicoAsc)['precio_acordado'];
            $variacionTotalMonto = round($precioActual - $precioInicial, 2);
            if ($precioInicial > 0) {
                $variacionTotalPct = round((($precioActual - $precioInicial) / $precioInicial) * 100, 2);
            }
        }

        return response()->json([
            'status' => 'success',
            'proveedor' => [
                'id_proveedor' => $proveedor->id_proveedor,
                'razon_social' => $proveedor->razon_social,
                'cuit' => $proveedor->cuit,
            ],
            'producto' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'categoria' => $producto->categoria?->nombre,
                'marca' => $producto->marca?->nombre,
            ],
            'estadisticas' => [
                'total_registros' => $totalCambios,
                'precio_inicial' => $precioInicial,
                'precio_actual' => $precioActual,
                'variacion_total_monto' => $variacionTotalMonto,
                'variacion_total_pct' => $variacionTotalPct,
            ],
            'historial' => $historial,
        ], 200);
    }

    /**
     * Reporte comparativo de aumentos y variaciones por producto en el período.
     */
    public function comparativaAumentos(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => 'El proveedor no existe.',
            ], 404);
        }

        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $resumen = $this->service->obtenerResumenAumentos($id, $fechaDesde, $fechaHasta);

        return response()->json([
            'status' => 'success',
            'proveedor' => [
                'id_proveedor' => $proveedor->id_proveedor,
                'razon_social' => $proveedor->razon_social,
                'cuit' => $proveedor->cuit,
            ],
            'data' => $resumen['productos'],
            'resumen' => $resumen,
        ], 200);
    }

    /**
     * Registra un nuevo precio acordado explícito para el producto y genera snapshot histórico.
     */
    public function actualizarPrecio(Request $request, int $id, int $idProducto): JsonResponse
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => 'El proveedor no existe.',
            ], 404);
        }

        $producto = Producto::find($idProducto);
        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto no existe.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'precio_acordado' => 'required|numeric|min:0',
            'id_unidad' => 'nullable|integer',
            'observaciones' => 'nullable|string|max:255',
        ], [
            'precio_acordado.required' => 'El precio acordado es obligatorio.',
            'precio_acordado.numeric' => 'El precio acordado debe ser un número válido.',
            'precio_acordado.min' => 'El precio acordado no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $idUsuario = UsuarioActual::id($request);
        $nuevoPrecio = (float) $request->input('precio_acordado');
        $idUnidad = $request->filled('id_unidad') ? (int) $request->input('id_unidad') : null;

        $resultado = DB::transaction(function () use ($id, $idProducto, $nuevoPrecio, $idUsuario, $idUnidad) {
            $relacion = ProductoProveedor::where('id_proveedor', $id)
                ->where('id_producto', $idProducto)
                ->first();

            $precioAnterior = null;

            if ($relacion) {
                $precioAnterior = $relacion->precio_acordado !== null ? (float) $relacion->precio_acordado : null;
                $relacion->update([
                    'precio_acordado' => $nuevoPrecio,
                    'activo' => true,
                    'id_usuario' => $idUsuario,
                ]);
            } else {
                $relacion = ProductoProveedor::create([
                    'id_proveedor' => $id,
                    'id_producto' => $idProducto,
                    'es_proveedor_principal' => false,
                    'precio_acordado' => $nuevoPrecio,
                    'activo' => true,
                    'fecha_asociacion' => date('Y-m-d'),
                    'id_usuario' => $idUsuario,
                ]);
            }

            $snapshot = $this->service->registrarSnapshot(
                $relacion->id_producto_proveedor,
                $nuevoPrecio,
                $idUsuario,
                null,
                $idUnidad
            );

            return [
                'relacion' => $relacion,
                'snapshot' => $snapshot,
                'precio_anterior' => $precioAnterior,
            ];
        });

        $snapshot = $resultado['snapshot'];
        $precioAnterior = $resultado['precio_anterior'];
        $diferencia = $precioAnterior !== null ? round($nuevoPrecio - $precioAnterior, 2) : null;
        $aumentoPct = ($precioAnterior !== null && $precioAnterior > 0)
            ? round((($nuevoPrecio - $precioAnterior) / $precioAnterior) * 100, 2)
            : 0.0;

        return response()->json([
            'status' => 'success',
            'message' => 'Precio acordado registrado y añadido al historial de precios.',
            'data' => [
                'id_historial_prov' => $snapshot->id_historial_prov,
                'id_proveedor' => $id,
                'id_producto' => $idProducto,
                'precio_anterior' => $precioAnterior,
                'nuevo_precio' => $nuevoPrecio,
                'diferencia_monto' => $diferencia,
                'aumento_pct' => $aumentoPct,
                'fecha_actualizacion' => $snapshot->fecha_actualizacion ? $snapshot->fecha_actualizacion->format('Y-m-d H:i:s') : null,
            ],
        ], 201);
    }
}
