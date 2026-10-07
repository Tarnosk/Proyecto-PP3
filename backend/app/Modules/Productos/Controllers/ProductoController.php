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
use App\Models\LogAuditoria;
use App\Support\AuditoriaLogger;

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
        if ($estado === 'todos' || empty($estado)) {
            $estado = null;
        }

        $productos = Producto::with(['categoria', 'marca', 'usuarioCarga', 'usuarioModificacion', 'ubicaciones'])
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
                    'fecha_alta' => $p->fecha_alta ? ($p->fecha_alta instanceof \Carbon\CarbonInterface ? $p->fecha_alta->format('Y-m-d H:i:s') : (string) $p->fecha_alta) : null,
                    'fecha_modificacion' => $p->fecha_modificacion ? ($p->fecha_modificacion instanceof \Carbon\CarbonInterface ? $p->fecha_modificacion->format('Y-m-d H:i:s') : (string) $p->fecha_modificacion) : null,
                    'fecha_desactivacion' => $p->fecha_desactivacion ? ($p->fecha_desactivacion instanceof \Carbon\CarbonInterface ? $p->fecha_desactivacion->format('Y-m-d H:i:s') : (string) $p->fecha_desactivacion) : null,
                    'id_usuario_carga' => $p->id_usuario_carga,
                    'usuario_carga_nombre' => $p->usuarioCarga ? $p->usuarioCarga->nombre : ($p->id_usuario_carga ? "Usuario #{$p->id_usuario_carga}" : null),
                    'id_usuario_modificacion' => $p->id_usuario_modificacion,
                    'usuario_modificacion_nombre' => $p->usuarioModificacion ? $p->usuarioModificacion->nombre : ($p->id_usuario_modificacion ? "Usuario #{$p->id_usuario_modificacion}" : null),
                    'id_categoria' => $p->id_categoria,
                    'categoria_nombre' => $p->categoria ? $p->categoria->nombre : null,
                    'id_marca' => $p->id_marca,
                    'marca_nombre' => $p->marca ? $p->marca->nombre : null,
                    'id_ubicacion' => $p->ubicaciones->first()?->id_ubicacion,
                    'ubicacion_nombre' => $p->ubicaciones->first()?->descripcion ?? 'Sin asignar',
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
            $now = Carbon::now()->toDateTimeString();
            $stockInicial = (int) ($request->input('stock_disponible', 0));
            $stockMinimo = (int) ($request->input('stock_minimo', 5));
            $idUsuario = UsuarioActual::id($request);
            $precioUnitario = (float) ($request->input('precio_unitario') ?? $request->input('precioMay') ?? 0.0);
            $descripcion = trim($request->input('descripcion'));
            $nombre = trim((string) $request->input('nombre')) !== ''
                ? trim((string) $request->input('nombre'))
                : $descripcion;

            $producto = DB::transaction(function () use ($request, $now, $stockInicial, $stockMinimo, $idUsuario, $precioUnitario, $descripcion, $nombre) {
                // 1. Crear Producto (P01). El stock queda embebido en PRODUCTO (OB3).
                $prod = Producto::create([
                    'codigo' => trim($request->input('codigo')),
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio_unitario' => $precioUnitario,
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

                // 2. Registrar precio inicial en HISTORIAL_PRECIO (OB1)
                HistorialPrecio::create([
                    'id_producto' => $prod->id_producto,
                    'tipo_precio' => 'general',
                    'precio' => $precioUnitario,
                    'porcentaje_aumento' => 0.00,
                    'regla_redondeo' => 'sin_redondeo',
                    'origen' => 'manual',
                    'fecha_cambio' => $now,
                    'id_usuario' => $idUsuario,
                ]);

                // 3. Asignar ubicación física si se especificó (S14)
                if ($request->filled('id_ubicacion')) {
                    DB::table('producto_ubicacion')->insert([
                        'id_producto' => $prod->id_producto,
                        'id_ubicacion' => (int) $request->input('id_ubicacion'),
                        'fecha_asignacion' => Carbon::now()->toDateString(),
                        'id_usuario' => $idUsuario,
                    ]);
                }

                AuditoriaLogger::registrar(
                    'producto',
                    $prod->id_producto,
                    'INSERT',
                    null,
                    [
                        'codigo' => $prod->codigo,
                        'nombre' => $prod->nombre,
                        'precio_unitario' => $precioUnitario,
                        'stock' => $stockInicial,
                    ],
                    $idUsuario
                );

                return $prod->load(['categoria', 'marca', 'ubicaciones']);
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
        $producto = Producto::with(['categoria', 'marca', 'usuarioCarga', 'usuarioModificacion', 'historialPrecios.usuario', 'ubicaciones'])->find($id);

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
                'fecha_alta' => $producto->fecha_alta ? ($producto->fecha_alta instanceof \Carbon\CarbonInterface ? $producto->fecha_alta->format('Y-m-d H:i:s') : (string) $producto->fecha_alta) : null,
                'fecha_modificacion' => $producto->fecha_modificacion ? ($producto->fecha_modificacion instanceof \Carbon\CarbonInterface ? $producto->fecha_modificacion->format('Y-m-d H:i:s') : (string) $producto->fecha_modificacion) : null,
                'fecha_desactivacion' => $producto->fecha_desactivacion ? ($producto->fecha_desactivacion instanceof \Carbon\CarbonInterface ? $producto->fecha_desactivacion->format('Y-m-d H:i:s') : (string) $producto->fecha_desactivacion) : null,
                'id_usuario_carga' => $producto->id_usuario_carga,
                'usuario_carga_nombre' => $producto->usuarioCarga ? $producto->usuarioCarga->nombre : ($producto->id_usuario_carga ? "Usuario #{$producto->id_usuario_carga}" : null),
                'id_usuario_modificacion' => $producto->id_usuario_modificacion,
                'usuario_modificacion_nombre' => $producto->usuarioModificacion ? $producto->usuarioModificacion->nombre : ($producto->id_usuario_modificacion ? "Usuario #{$producto->id_usuario_modificacion}" : null),
                'id_categoria' => $producto->id_categoria,
                'categoria_nombre' => $producto->categoria ? $producto->categoria->nombre : null,
                'id_marca' => $producto->id_marca,
                'marca_nombre' => $producto->marca ? $producto->marca->nombre : null,
                'id_ubicacion' => $producto->ubicaciones->first()?->id_ubicacion,
                'ubicacion_nombre' => $producto->ubicaciones->first()?->descripcion ?? 'Sin asignar',
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
            $now = Carbon::now()->toDateTimeString();
            $idUsuario = UsuarioActual::id($request);
            $nuevoPrecio = (float) ($request->input('precio_unitario') ?? $request->input('precioMay') ?? $producto->precio_unitario);
            $anteriorPrecio = (float) $producto->precio_unitario;
            $descripcion = trim($request->input('descripcion'));
            $nombre = trim((string) $request->input('nombre')) !== ''
                ? trim((string) $request->input('nombre'))
                : $descripcion;

            $valoresAnteriores = [
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_unitario' => (float) $producto->precio_unitario,
                'id_categoria' => (int) $producto->id_categoria,
                'id_marca' => (int) $producto->id_marca,
                'stock_minimo' => (int) $producto->stock_minimo,
            ];

            $valoresNuevos = [
                'codigo' => trim($request->input('codigo')),
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio_unitario' => $nuevoPrecio,
                'id_categoria' => (int) $request->input('id_categoria'),
                'id_marca' => (int) $request->input('id_marca'),
                'stock_minimo' => $request->has('stock_minimo') ? (int) $request->input('stock_minimo') : (int) $producto->stock_minimo,
            ];

            DB::transaction(function () use ($producto, $request, $now, $idUsuario, $nuevoPrecio, $anteriorPrecio, $descripcion, $nombre, $valoresAnteriores, $valoresNuevos) {
                // Si cambió el precio unitario, se registra en HISTORIAL_PRECIO (P04)
                if (abs($nuevoPrecio - $anteriorPrecio) >= 0.001) {
                    $porcentaje = 0.0;
                    if ($anteriorPrecio > 0) {
                        $porcentaje = (($nuevoPrecio - $anteriorPrecio) / $anteriorPrecio) * 100.0;
                    }

                    HistorialPrecio::create([
                        'id_producto' => $producto->id_producto,
                        'tipo_precio' => 'general',
                        'precio' => $nuevoPrecio,
                        'porcentaje_aumento' => $porcentaje,
                        'regla_redondeo' => 'manual',
                        'origen' => 'manual',
                        'fecha_cambio' => Carbon::now(),
                        'id_usuario' => $idUsuario,
                    ]);
                }

                // Actualizar producto
                $producto->update([
                    'codigo' => trim($request->input('codigo')),
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio_unitario' => $nuevoPrecio,
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

                // Actualizar ubicación física si se envió (S14)
                if ($request->has('id_ubicacion')) {
                    DB::table('producto_ubicacion')->where('id_producto', $producto->id_producto)->delete();
                    $idUbic = (int) $request->input('id_ubicacion');
                    if ($idUbic > 0) {
                        DB::table('producto_ubicacion')->insert([
                            'id_producto' => $producto->id_producto,
                            'id_ubicacion' => $idUbic,
                            'fecha_asignacion' => Carbon::now()->toDateString(),
                            'id_usuario' => $idUsuario,
                        ]);
                    }
                }

                AuditoriaLogger::registrar(
                    'producto',
                    $producto->id_producto,
                    'UPDATE',
                    $valoresAnteriores,
                    $valoresNuevos,
                    $idUsuario
                );
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Producto modificado exitosamente.',
                'data' => $producto->fresh(['categoria', 'marca', 'ubicaciones']),
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

        $now = Carbon::now()->toDateTimeString();
        $idUsuario = UsuarioActual::id($request);

        $producto->update([
            'estado' => 'inactivo',
            'fecha_desactivacion' => $now,
            'fecha_modificacion' => $now,
            'id_usuario_modificacion' => $idUsuario,
        ]);

        AuditoriaLogger::registrar(
            'producto',
            $producto->id_producto,
            'UPDATE',
            ['estado' => 'activo'],
            ['estado' => 'inactivo'],
            $idUsuario
        );

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

        $now = Carbon::now()->toDateTimeString();
        $idUsuario = UsuarioActual::id($request);

        $producto->update([
            'estado' => 'activo',
            'fecha_desactivacion' => null,
            'fecha_modificacion' => $now,
            'id_usuario_modificacion' => $idUsuario,
        ]);

        AuditoriaLogger::registrar(
            'producto',
            $producto->id_producto,
            'UPDATE',
            ['estado' => 'inactivo'],
            ['estado' => 'activo'],
            $idUsuario
        );

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
        $registros = HistorialPrecio::with('usuario')
            ->where('id_producto', $id)
            ->orderBy('fecha_cambio', 'asc')
            ->orderBy('id_historial', 'asc')
            ->get();

        $resultado = [];
        $precioAnterior = null;
        foreach ($registros as $registro) {
            $precioNuevo = (float) $registro->precio;
            $variacionPorcentaje = null;
            if ($precioAnterior !== null && $precioAnterior > 0) {
                $variacionPorcentaje = round((($precioNuevo - $precioAnterior) / $precioAnterior) * 100, 2);
            }

            $resultado[] = [
                'id_historial' => $registro->id_historial,
                'id_producto' => $registro->id_producto,
                'tipo_precio' => $registro->tipo_precio,
                'precio' => $precioNuevo,
                'precio_anterior' => $precioAnterior !== null ? (float) $precioAnterior : null,
                'precio_nuevo' => $precioNuevo,
                'porcentaje_aumento' => $variacionPorcentaje,
                'porcentaje_variacion' => $variacionPorcentaje,
                'regla_redondeo' => $registro->regla_redondeo,
                'origen' => $registro->origen,
                'fecha_cambio' => $registro->fecha_cambio ? (is_string($registro->fecha_cambio) ? $registro->fecha_cambio : $registro->fecha_cambio->format('Y-m-d H:i:s')) : null,
                'id_usuario' => $registro->id_usuario,
                'usuario_nombre' => $registro->usuario ? $registro->usuario->nombre : ($registro->id_usuario ? "Usuario #{$registro->id_usuario}" : 'Sistema'),
            ];
            $precioAnterior = $precioNuevo;
        }
        $resultado = array_reverse($resultado);

        return response()->json([
            'status' => 'success',
            'data' => $resultado,
        ]);
    }

    /**
     * Consultar historial de cambios y auditoría del producto (P04 / Auditoría)
     */
    public function auditoria(int $id): JsonResponse
    {
        $logs = LogAuditoria::with('usuario')
            ->where('tabla_afectada', 'producto')
            ->where('id_registro', $id)
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id_auditoria')
            ->get()
            ->map(function (LogAuditoria $log) {
                return [
                    'id_auditoria' => $log->id_auditoria,
                    'accion' => $log->accion,
                    'valores_anteriores' => $log->valores_anteriores,
                    'valores_nuevos' => $log->valores_nuevos,
                    'id_usuario' => $log->id_usuario,
                    'usuario_nombre' => $log->usuario ? $log->usuario->nombre : ($log->id_usuario ? "Usuario #{$log->id_usuario}" : 'Sistema'),
                    'fecha_hora' => $log->fecha_hora ? $log->fecha_hora->format('Y-m-d H:i:s') : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    /**
     * Aplicar aumento porcentual masivo sobre productos activos (P07 / OB1)
     *
     * Permite filtrar por ids explícitos, categoría o marca. Cuando no se
     * especifica ningún filtro, aplica a todos los productos activos.
     */
    public function aumentoMasivo(Request $request): JsonResponse
    {
            $validator = Validator::make($request->all(), [
            'porcentaje' => 'required|numeric|gt:0',
            'tipo_precio' => 'nullable|string|in:ambos,mayorista,minorista',
            'regla_redondeo' => 'nullable|string|in:sin_redondeo,redondeo,ceil,floor,redondeo_0,redondeo_2',
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:PRODUCTO,id_producto',
            'id_categoria' => 'nullable|integer|exists:CATEGORIA,id_categoria',
            'id_marca' => 'nullable|integer|exists:MARCA,id_marca',
        ], [
            'porcentaje.required' => 'Debe indicar el porcentaje de aumento.',
            'porcentaje.numeric' => 'El porcentaje debe ser numǸrico.',
            'porcentaje.gt' => 'El porcentaje de aumento debe ser mayor a 0.',
            'tipo_precio.in' => 'El tipo de precio debe ser: ambos, mayorista o minorista.',
            'regla_redondeo.in' => 'La regla de redondeo debe ser: sin_redondeo, redondeo, ceil, floor, redondeo_0 o redondeo_2.',
            'ids.*.exists' => 'Alguno de los productos indicados no existe.',
            'id_categoria.exists' => 'La categor��a seleccionada no existe.',
            'id_marca.exists' => 'La marca seleccionada no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

            $porcentaje = (float) $request->input('porcentaje');
            $tipoPrecio = $request->input('tipo_precio', 'ambos');
            $reglaRedondeo = $request->input('regla_redondeo', 'sin_redondeo');
            $idUsuario = UsuarioActual::id($request);
            $now = Carbon::now()->toDateTimeString();

            try {
                $query = Producto::query()->where('estado', 'activo');

                $ids = $request->input('ids');
                if (is_array($ids) && count($ids) > 0) {
                    $query->whereIn('id_producto', array_map('intval', $ids));
                } else {
                    $query->filterCategoria($request->filled('id_categoria') ? (int) $request->input('id_categoria') : null)
                          ->filterMarca($request->filled('id_marca') ? (int) $request->input('id_marca') : null);
                }

                $productos = $query->orderBy('id_producto', 'asc')->get();

                $detalles = DB::transaction(function () use ($productos, $porcentaje, $tipoPrecio, $reglaRedondeo, $idUsuario, $now) {
                    $detalles = [];

                    foreach ($productos as $producto) {
                        $cambio = false;
                        $precioAnterior = (float) $producto->precio_unitario;
                        $precioCalculado = $precioAnterior * (1 + $porcentaje / 100.0);
                        $nuevoPrecio = match ($reglaRedondeo) {
                            'redondeo' => round($precioCalculado, 2),
                            'ceil' => ceil($precioCalculado * 100) / 100,
                            'floor' => floor($precioCalculado * 100) / 100,
                            'redondeo_0' => round($precioCalculado, 0),
                            'redondeo_2' => round($precioCalculado, 2),
                            default => round($precioCalculado, 2),
                        };

                        if (abs($nuevoPrecio - $precioAnterior) >= 0.001) {
                            HistorialPrecio::create([
                                'id_producto' => $producto->id_producto,
                                'tipo_precio' => 'general',
                                'precio' => $nuevoPrecio,
                                'porcentaje_aumento' => $porcentaje,
                                'regla_redondeo' => $reglaRedondeo,
                                'origen' => 'aumento_masivo',
                                'fecha_cambio' => $now,
                                'id_usuario' => $idUsuario,
                            ]);

                        $producto->update([
                            'precio_unitario' => $nuevoPrecio,
                            'fecha_modificacion' => $now,
                            'id_usuario_modificacion' => $idUsuario,
                        ]);
                        $cambio = true;
                    }

                    $detalles[] = [
                        'id_producto' => $producto->id_producto,
                        'codigo' => $producto->codigo,
                        'descripcion' => $producto->descripcion,
                        'precio_anterior' => $precioAnterior,
                        'precio_nuevo' => $nuevoPrecio,
                        'precio_unitario_anterior' => $precioAnterior,
                        'precio_unitario_nuevo' => $nuevoPrecio,
                        'precio_mayorista_anterior' => $precioAnterior,
                        'precio_mayorista_nuevo' => $nuevoPrecio,
                        'precio_minorista_anterior' => $precioAnterior,
                        'precio_minorista_nuevo' => $nuevoPrecio,
                        'aplicado' => $cambio,
                    ];
                }

                return $detalles;
            });

            $totalAplicados = count(array_filter($detalles, fn ($d) => $d['aplicado']));

            return response()->json([
                'status' => 'success',
                'message' => "Aumento del {$porcentaje}% aplicado a {$totalAplicados} producto(s) activo(s).",
                'data' => [
                    'porcentaje' => $porcentaje,
                    'tipo_precio' => $tipoPrecio,
                    'total_aplicados' => $totalAplicados,
                    'productos' => $detalles,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al aplicar el aumento masivo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
