<?php
declare(strict_types=1);

namespace App\Modules\Productos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\Stock;
use App\Modules\Productos\Models\HistorialPrecio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

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

        $productos = Producto::with(['categoria', 'marca', 'stock'])
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
                    'precio_unitario' => (float) $p->precio_unitario,
                    'estado' => $p->estado,
                    'fecha_alta' => $p->fecha_alta ? $p->fecha_alta->format('Y-m-d') : null,
                    'fecha_modificacion' => $p->fecha_modificacion ? $p->fecha_modificacion->format('Y-m-d') : null,
                    'fecha_desactivacion' => $p->fecha_desactivacion ? $p->fecha_desactivacion->format('Y-m-d') : null,
                    'id_categoria' => $p->id_categoria,
                    'categoria_nombre' => $p->categoria ? $p->categoria->nombre : null,
                    'id_marca' => $p->id_marca,
                    'marca_nombre' => $p->marca ? $p->marca->nombre : null,
                    'stock_disponible' => $p->stock ? $p->stock->stock_disponible : 0,
                    'stock_minimo' => $p->stock ? $p->stock->stock_minimo : 0,
                    'estado_alerta' => $p->stock ? $p->stock->estado_alerta : 'normal',
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
            'descripcion' => 'required|string|max:255',
            'precio_unitario' => 'required|numeric|min:0',
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
            $idUsuario = (int) ($request->input('id_usuario', 1));
            $precio = (float) $request->input('precio_unitario');

            $producto = DB::transaction(function () use ($request, $now, $stockInicial, $stockMinimo, $idUsuario, $precio) {
                // 1. Crear Producto (P01)
                $prod = Producto::create([
                    'codigo' => trim($request->input('codigo')),
                    'descripcion' => trim($request->input('descripcion')),
                    'precio_unitario' => $precio,
                    'estado' => 'activo',
                    'fecha_alta' => $now,
                    'fecha_modificacion' => $now,
                    'id_categoria' => (int) $request->input('id_categoria'),
                    'id_marca' => (int) $request->input('id_marca'),
                    'id_usuario_carga' => $idUsuario,
                    'id_usuario_modificacion' => $idUsuario,
                ]);

                // 2. Determinar estado de alerta
                $alerta = 'normal';
                if ($stockInicial <= 0) {
                    $alerta = 'critico';
                } elseif ($stockInicial <= $stockMinimo) {
                    $alerta = 'bajo';
                }

                // 3. Crear registro en tabla STOCK
                Stock::create([
                    'id_producto' => $prod->id_producto,
                    'stock_disponible' => $stockInicial,
                    'stock_minimo' => $stockMinimo,
                    'estado_alerta' => $alerta,
                    'dias_alerta_vencimiento' => 30,
                ]);

                // 4. Registrar precio inicial en HISTORIAL_PRECIO
                HistorialPrecio::create([
                    'id_producto' => $prod->id_producto,
                    'precio_anterior' => $precio,
                    'precio_nuevo' => $precio,
                    'porcentaje_aumento' => 0.00,
                    'regla_redondeo' => 'sin_redondeo',
                    'origen' => 'manual',
                    'fecha_cambio' => $now,
                    'id_usuario' => $idUsuario,
                ]);

                return $prod->load(['categoria', 'marca', 'stock']);
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
        $producto = Producto::with(['categoria', 'marca', 'stock', 'historialPrecios'])->find($id);

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
                'precio_unitario' => (float) $producto->precio_unitario,
                'estado' => $producto->estado,
                'fecha_alta' => $producto->fecha_alta ? $producto->fecha_alta->format('Y-m-d') : null,
                'fecha_modificacion' => $producto->fecha_modificacion ? $producto->fecha_modificacion->format('Y-m-d') : null,
                'fecha_desactivacion' => $producto->fecha_desactivacion ? $producto->fecha_desactivacion->format('Y-m-d') : null,
                'id_categoria' => $producto->id_categoria,
                'categoria_nombre' => $producto->categoria ? $producto->categoria->nombre : null,
                'id_marca' => $producto->id_marca,
                'marca_nombre' => $producto->marca ? $producto->marca->nombre : null,
                'stock_disponible' => $producto->stock ? $producto->stock->stock_disponible : 0,
                'stock_minimo' => $producto->stock ? $producto->stock->stock_minimo : 0,
                'estado_alerta' => $producto->stock ? $producto->stock->estado_alerta : 'normal',
                'historial_precios' => $producto->historialPrecios,
            ],
        ]);
    }

    /**
     * Modificar producto existente y registrar historial de precios si varía (P04)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $producto = Producto::with('stock')->find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => "El producto con ID {$id} no existe.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'codigo' => "required|string|max:50|unique:PRODUCTO,codigo,{$id},id_producto",
            'descripcion' => 'required|string|max:255',
            'precio_unitario' => 'required|numeric|min:0',
            'id_categoria' => 'required|integer|exists:CATEGORIA,id_categoria',
            'id_marca' => 'required|integer|exists:MARCA,id_marca',
            'stock_minimo' => 'nullable|integer|min:0',
        ], [
            'codigo.required' => 'El código del producto es obligatorio.',
            'codigo.unique' => "El código ':input' ya está siendo utilizado por otro producto.",
            'descripcion.required' => 'La descripción del producto es obligatoria.',
            'precio_unitario.required' => 'El precio unitario es obligatorio.',
            'precio_unitario.min' => 'El precio unitario no puede ser negativo.',
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
            $idUsuario = (int) ($request->input('id_usuario', 1));
            $nuevoPrecio = (float) $request->input('precio_unitario');
            $precioAnterior = (float) $producto->precio_unitario;

            DB::transaction(function () use ($producto, $request, $now, $idUsuario, $nuevoPrecio, $precioAnterior) {
                // Si el precio cambió, registrar en HISTORIAL_PRECIO (P04)
                if (abs($nuevoPrecio - $precioAnterior) >= 0.001) {
                    $porcentaje = 0.0;
                    if ($precioAnterior > 0) {
                        $porcentaje = (($nuevoPrecio - $precioAnterior) / $precioAnterior) * 100.0;
                    }

                    HistorialPrecio::create([
                        'id_producto' => $producto->id_producto,
                        'precio_anterior' => $precioAnterior,
                        'precio_nuevo' => $nuevoPrecio,
                        'porcentaje_aumento' => $porcentaje,
                        'regla_redondeo' => 'manual',
                        'origen' => 'manual',
                        'fecha_cambio' => $now,
                        'id_usuario' => $idUsuario,
                    ]);
                }

                // Actualizar producto
                $producto->update([
                    'codigo' => trim($request->input('codigo')),
                    'descripcion' => trim($request->input('descripcion')),
                    'precio_unitario' => $nuevoPrecio,
                    'id_categoria' => (int) $request->input('id_categoria'),
                    'id_marca' => (int) $request->input('id_marca'),
                    'fecha_modificacion' => $now,
                    'id_usuario_modificacion' => $idUsuario,
                ]);

                // Actualizar stock mínimo si se envió
                if ($request->has('stock_minimo') && $producto->stock) {
                    $producto->stock->update([
                        'stock_minimo' => (int) $request->input('stock_minimo'),
                    ]);
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Producto modificado exitosamente.',
                'data' => $producto->fresh(['categoria', 'marca', 'stock']),
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
        $idUsuario = (int) ($request->input('id_usuario', 1));

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
        $idUsuario = (int) ($request->input('id_usuario', 1));

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
