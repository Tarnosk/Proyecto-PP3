<?php
declare(strict_types=1);

namespace App\Modules\Productos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Productos\Models\HistorialPrecio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;
use App\Support\UsuarioActual;

class ProductoController extends Controller
{
    /**
     * Listar y buscar productos con filtros (P05)
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $categoriaId = $request->filled('categoria_id') ? (int) $request->input('categoria_id') : null;
        $marcaId = $request->filled('marca_id') ? (int) $request->input('marca_id') : null;
        $estado = $request->input('estado');
        if ($estado === 'todos') {
            $estado = null;
        }

        $productos = Producto::with(['categoria', 'marca'])
            ->search($search)
            ->filterCategoria($categoriaId)
            ->filterMarca($marcaId)
            ->filterEstado($estado)
            ->orderBy('id_producto', 'desc')
            ->get()
            ->map(function (Producto $p) {
                return [
                    'id_producto' => $p->id_producto,
                    'codigo' => $p->codigo,
                    'descripcion' => $p->descripcion,
                    'nombre' => $p->nombre,
                    'precio_unitario' => (float) $p->precio_unitario,
                    'precio_mayorista' => (float) $p->precio_mayorista,
                    'precio_minorista' => (float) $p->precio_minorista,
                    'estado' => $p->estado,
                    'fecha_alta' => $p->fecha_alta ? $p->fecha_alta->format('Y-m-d') : null,
                    'fecha_modificacion' => $p->fecha_modificacion ? $p->fecha_modificacion->format('Y-m-d') : null,
                    'fecha_desactivacion' => $p->fecha_desactivacion ? $p->fecha_desactivacion->format('Y-m-d') : null,
                    'id_categoria' => $p->id_categoria,
                    'categoria_nombre' => $p->categoria ? $p->categoria->nombre : null,
                    'id_marca' => $p->id_marca,
                    'marca_nombre' => $p->marca ? $p->marca->nombre : null,
                    'stock_disponible' => $p->stock_disponible,
                    'stock_minimo' => $p->stock_minimo,
                    'estado_alerta' => $p->estado_alerta,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $productos,
        ]);
    }

    /**
     * Registrar nuevo producto (P01)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string|max:50|unique:PRODUCTO,codigo',
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'required|string|max:255',
            'precio_unitario' => 'required|numeric|min:0',
            'precio_minorista' => 'nullable|numeric|min:0',
            'id_categoria' => 'required|integer|exists:CATEGORIA,id_categoria',
            'id_marca' => 'required|integer|exists:MARCA,id_marca',
            'stock_disponible' => 'nullable|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
        ], [
            'codigo.required' => 'El código del producto es obligatorio.',
            'codigo.unique' => "El código ':input' ya se encuentra registrado.",
            'descripcion.required' => 'La descripción del producto es obligatoria.',
            'precio_unitario.required' => 'El precio unitario es obligatorio.',
            'precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            'precio_minorista.min' => 'El precio minorista no puede ser negativo.',
            'id_categoria.required' => 'Debe seleccionar una categoría.',
            'id_categoria.exists' => 'La categoría seleccionada no existe.',
            'id_marca.required' => 'Debe seleccionar una marca.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $now = Carbon::now()->toDateString();
            $stockInicial = (int) ($request->input('stock_disponible', 0));
            $stockMinimo = (int) ($request->input('stock_minimo', 5));
            $idUsuario = UsuarioActual::id($request);
            $precioMayorista = (float) $request->input('precio_unitario');
            $precioMinorista = (float) ($request->input('precio_minorista', $precioMayorista));
            $descripcion = trim($request->input('descripcion'));
            $nombre = trim((string) $request->input('nombre')) !== ''
                ? trim((string) $request->input('nombre'))
                : $descripcion;

            $producto = DB::transaction(function () use ($request, $now, $stockInicial, $stockMinimo, $idUsuario, $precioMayorista, $precioMinorista, $descripcion, $nombre) {
                // 1. Crear Producto (P01). El stock queda embebido en PRODUCTO (OB3).
                $prod = Producto::create([
                    'codigo' => trim($request->input('codigo')),
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precioMay' => $precioMayorista,
                    'precioMin' => $precioMinorista,
                    'imagen' => null,
                    'stock' => $stockInicial,
                    'stock_minimo' => $stockMinimo,
                    'dias_alerta_vencimiento' => 30,
                    'estado' => 'activo',
                    'fecha_alta' => $now,
                    'fecha_modificacion' => $now,
                    'id_categoria' => (int) $request->input('id_categoria'),
                    'id_marca' => (int) $request->input('id_marca'),
                    'id_usuario_carga' => $idUsuario,
                    'id_usuario_modificacion' => $idUsuario,
                ]);

                // 2. Registrar precios iniciales en HISTORIAL_PRECIO (1 fila por tipo, OB1)
                HistorialPrecio::create([
                    'id_producto' => $prod->id_producto,
                    'tipo_precio' => 'mayorista',
                    'precio' => $precioMayorista,
                    'porcentaje_aumento' => 0.00,
                    'regla_redondeo' => 'sin_redondeo',
                    'origen' => 'manual',
                    'fecha_cambio' => $now,
                    'id_usuario' => $idUsuario,
                ]);

                HistorialPrecio::create([
                    'id_producto' => $prod->id_producto,
                    'tipo_precio' => 'minorista',
                    'precio' => $precioMinorista,
                    'porcentaje_aumento' => 0.00,
                    'regla_redondeo' => 'sin_redondeo',
                    'origen' => 'manual',
                    'fecha_cambio' => $now,
                    'id_usuario' => $idUsuario,
                ]);

                return $prod->load(['categoria', 'marca']);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Producto registrado exitosamente.',
                'data' => $producto,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el producto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener detalle de un producto por ID
     */
    public function show(int $id): JsonResponse
    {
        $producto = Producto::with(['categoria', 'marca', 'historialPrecios'])->find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'nombre' => $producto->nombre,
                'precio_unitario' => (float) $producto->precio_unitario,
                'precio_mayorista' => (float) $producto->precio_mayorista,
                'precio_minorista' => (float) $producto->precio_minorista,
                'estado' => $producto->estado,
                'fecha_alta' => $producto->fecha_alta ? $producto->fecha_alta->format('Y-m-d') : null,
                'fecha_modificacion' => $producto->fecha_modificacion ? $producto->fecha_modificacion->format('Y-m-d') : null,
                'fecha_desactivacion' => $producto->fecha_desactivacion ? $producto->fecha_desactivacion->format('Y-m-d') : null,
                'id_categoria' => $producto->id_categoria,
                'categoria_nombre' => $producto->categoria ? $producto->categoria->nombre : null,
                'id_marca' => $producto->id_marca,
                'marca_nombre' => $producto->marca ? $producto->marca->nombre : null,
                'stock_disponible' => $producto->stock_disponible,
                'stock_minimo' => $producto->stock_minimo,
                'estado_alerta' => $producto->estado_alerta,
                'historial_precios' => $producto->historialPrecios,
            ],
        ]);
    }

    /**
     * Modificar producto existente y registrar historial de precios si varía (P04)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => "El producto con ID {$id} no existe.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'codigo' => "required|string|max:50|unique:PRODUCTO,codigo,{$id},id_producto",
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'required|string|max:255',
            'precio_unitario' => 'required|numeric|min:0',
            'precio_minorista' => 'nullable|numeric|min:0',
            'id_categoria' => 'required|integer|exists:CATEGORIA,id_categoria',
            'id_marca' => 'required|integer|exists:MARCA,id_marca',
            'stock_minimo' => 'nullable|integer|min:0',
        ], [
            'codigo.required' => 'El código del producto es obligatorio.',
            'codigo.unique' => "El código ':input' ya está siendo utilizado por otro producto.",
            'descripcion.required' => 'La descripción del producto es obligatoria.',
            'precio_unitario.required' => 'El precio unitario es obligatorio.',
            'precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            'precio_minorista.min' => 'El precio minorista no puede ser negativo.',
            'id_categoria.required' => 'Debe seleccionar una categoría.',
            'id_categoria.exists' => 'La categoría seleccionada no existe.',
            'id_marca.required' => 'Debe seleccionar una marca.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $now = Carbon::now()->toDateString();
            $idUsuario = UsuarioActual::id($request);
            $nuevoMayorista = (float) $request->input('precio_unitario');
            $anteriorMayorista = (float) $producto->precioMay;
            $nuevoMinorista = (float) ($request->input('precio_minorista', (float) $producto->precioMin));
            $anteriorMinorista = (float) $producto->precioMin;
            $descripcion = trim($request->input('descripcion'));
            $nombre = trim((string) $request->input('nombre')) !== ''
                ? trim((string) $request->input('nombre'))
                : $descripcion;

            DB::transaction(function () use ($producto, $request, $now, $idUsuario, $nuevoMayorista, $anteriorMayorista, $nuevoMinorista, $anteriorMinorista, $descripcion, $nombre) {
                // Si cambió el precio mayorista, se registra en HISTORIAL_PRECIO (P04)
                if (abs($nuevoMayorista - $anteriorMayorista) >= 0.001) {
                    $porcentaje = 0.0;
                    if ($anteriorMayorista > 0) {
                        $porcentaje = (($nuevoMayorista - $anteriorMayorista) / $anteriorMayorista) * 100.0;
                    }

                    HistorialPrecio::create([
                        'id_producto' => $producto->id_producto,
                        'tipo_precio' => 'mayorista',
                        'precio' => $nuevoMayorista,
                        'porcentaje_aumento' => $porcentaje,
                        'regla_redondeo' => 'manual',
                        'origen' => 'manual',
                        'fecha_cambio' => $now,
                        'id_usuario' => $idUsuario,
                    ]);
                }

                // Si cambió el precio minorista, se registra su propia entrada de historial
                if (abs($nuevoMinorista - $anteriorMinorista) >= 0.001) {
                    $porcentajeMin = 0.0;
                    if ($anteriorMinorista > 0) {
                        $porcentajeMin = (($nuevoMinorista - $anteriorMinorista) / $anteriorMinorista) * 100.0;
                    }

                    HistorialPrecio::create([
                        'id_producto' => $producto->id_producto,
                        'tipo_precio' => 'minorista',
                        'precio' => $nuevoMinorista,
                        'porcentaje_aumento' => $porcentajeMin,
                        'regla_redondeo' => 'manual',
                        'origen' => 'manual',
                        'fecha_cambio' => $now,
                        'id_usuario' => $idUsuario,
                    ]);
                }

                // Actualizar producto
                $producto->update([
                    'codigo' => trim($request->input('codigo')),
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precioMay' => $nuevoMayorista,
                    'precioMin' => $nuevoMinorista,
                    'id_categoria' => (int) $request->input('id_categoria'),
                    'id_marca' => (int) $request->input('id_marca'),
                    'fecha_modificacion' => $now,
                    'id_usuario_modificacion' => $idUsuario,
                ]);

                // Actualizar stock mínimo si se envió
                if ($request->has('stock_minimo')) {
                    $producto->stock_minimo = (int) $request->input('stock_minimo');
                    $producto->save();
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Producto modificado exitosamente.',
                'data' => $producto->fresh(['categoria', 'marca']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el producto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desactivar producto - Borrado lógico (P06)
     */
    public function desactivar(Request $request, int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => "El producto con ID {$id} no existe.",
            ], 404);
        }

        $now = Carbon::now()->toDateString();
        $idUsuario = UsuarioActual::id($request);

        $producto->update([
            'estado' => 'inactivo',
            'fecha_desactivacion' => $now,
            'fecha_modificacion' => $now,
            'id_usuario_modificacion' => $idUsuario,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Producto desactivado exitosamente.',
            'data' => $producto,
        ]);
    }

    /**
     * Reactivar producto
     */
    public function activar(Request $request, int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => "El producto con ID {$id} no existe.",
            ], 404);
        }

        $now = Carbon::now()->toDateString();
        $idUsuario = UsuarioActual::id($request);

        $producto->update([
            'estado' => 'activo',
            'fecha_desactivacion' => null,
            'fecha_modificacion' => $now,
            'id_usuario_modificacion' => $idUsuario,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Producto reactivado exitosamente.',
            'data' => $producto,
        ]);
    }

    /**
     * Eliminar producto (DELETE route -> mapea a desactivación P06)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->desactivar($request, $id);
    }

    /**
     * Consultar historial de precios cronológico (P08 / P04)
     */
    public function historialPrecios(int $id): JsonResponse
    {
        $historial = HistorialPrecio::where('id_producto', $id)
            ->orderBy('id_historial', 'desc')
            ->orderBy('fecha_cambio', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $historial,
        ]);
    }
}
