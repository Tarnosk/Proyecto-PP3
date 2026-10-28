<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Stock\Services\StockService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IngresoMercaderiaController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S03 - Registrar ingreso de mercadería de un proveedor.
     * Recibe una lista de productos con cantidades ingresadas y actualiza el stock.
     * Se valida opcionalmente la existencia del proveedor (PV01).
     */
    public function store(Request $request): JsonResponse
    {
        // Normalización si el cliente envía un ítem plano en vez del array items
        if (!$request->has('items') && $request->filled('id_producto')) {
            $request->merge([
                'items' => [
                    [
                        'id_producto' => (int) $request->input('id_producto'),
                        'cantidad' => (int) $request->input('cantidad'),
                        'id_unidad' => (!empty($request->input('id_unidad')) && (int)$request->input('id_unidad') > 0) ? (int)$request->input('id_unidad') : null,
                        'motivo' => $request->input('motivo', 'Ingreso de mercadería'),
                    ],
                ],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'id_proveedor' => 'nullable|integer|exists:PROVEEDOR,id_proveedor',
            'fecha' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    $fecha = \Carbon\Carbon::parse($value)->startOfDay();
                    $hoy = \Carbon\Carbon::today();
                    if ($fecha->greaterThan($hoy)) {
                        $fail('La fecha del ingreso no puede ser posterior a la fecha actual.');
                    }
                },
            ],
            'items' => 'required|array|min:1',
            'items.*.id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'items.*.motivo' => 'nullable|string|max:255',
        ], [
            'id_proveedor.required' => 'Debe seleccionar un proveedor.',
            'id_proveedor.exists' => 'El proveedor seleccionado no existe.',
            'fecha.before_or_equal' => 'La fecha del ingreso no puede ser posterior a la fecha actual.',
            'items.required' => 'Debe indicar al menos un producto recibido.',
            'items.*.id_producto.required' => 'Cada ítem debe indicar un producto.',
            'items.*.id_producto.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.cantidad.required' => 'Cada ítem debe indicar la cantidad ingresada.',
            'items.*.cantidad.min' => 'La cantidad ingresada debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        // PV04: impedir nuevas operaciones con proveedores inactivos.
        $proveedor = null;
        if ($request->filled('id_proveedor')) {
            $proveedor = Proveedor::find((int) $request->input('id_proveedor'));
            if (!$proveedor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El proveedor seleccionado no existe.',
                ], 404);
            }
            if ($proveedor->estado !== 'activo') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede registrar un ingreso con un proveedor inactivo. (PV04)',
                ], 400);
            }
        }

        $idUsuario = UsuarioActual::id($request);
        $fecha = $request->input('fecha');

        try {
            $registrados = [];
            $items = $request->input('items');

            foreach ($items as $item) {
                $producto = Producto::find((int) $item['id_producto']);
                $idUnidad = (!empty($item['id_unidad']) && (int) $item['id_unidad'] > 0) ? (int) $item['id_unidad'] : null;
                $motivo = $item['motivo'] ?? 'Ingreso de mercadería';
                $cantidad = (int) $item['cantidad'];

                if (!$producto || $producto->estado !== 'activo') {
                    return response()->json([
                        'status' => 'error',
                        'message' => "El producto con ID {$item['id_producto']} no está activo o no existe.",
                    ], 400);
                }

                $fechaIngreso = null;
                if ($fecha !== null && $fecha !== '') {
                    try {
                        $fechaIngreso = \Carbon\Carbon::parse($fecha);
                        if (!str_contains((string) $fecha, ':')) {
                            $ahora = \Carbon\Carbon::now();
                            $fechaIngreso->setTime($ahora->hour, $ahora->minute, $ahora->second);
                        }
                    } catch (\Throwable $e) {
                        $fechaIngreso = \Carbon\Carbon::now();
                    }
                } else {
                    $fechaIngreso = \Carbon\Carbon::now();
                }
                $cantidadBase = StockService::cantidadEnUnidadBase($cantidad, $idUnidad);

                DB::transaction(function () use (
                    $producto,
                    $cantidad,
                    $motivo,
                    $idUsuario,
                    $idUnidad,
                    $fechaIngreso
                ) {
                    $this->stockService->registrarMovimiento(
                        $producto,
                        'ingreso',
                        $cantidad,
                        $motivo,
                        $idUsuario,
                        $idUnidad,
                        null,
                        null,
                        $fechaIngreso
                    );
                });

                // PV05: Asociar automáticamente el producto al proveedor al ingresar mercadería si no está asociado
                if ($proveedor) {
                    $asocProv = ProductoProveedor::where('id_producto', $producto->id_producto)
                        ->where('id_proveedor', $proveedor->id_proveedor)
                        ->first();

                    $fechaAsoc = $fechaIngreso ? $fechaIngreso->toDateString() : \Carbon\Carbon::now()->toDateString();

                    if (!$asocProv) {
                        $tieneOtroPrincipal = ProductoProveedor::where('id_producto', $producto->id_producto)
                            ->where('activo', true)
                            ->where('es_proveedor_principal', true)
                            ->exists();

                        ProductoProveedor::create([
                            'id_producto' => $producto->id_producto,
                            'id_proveedor' => $proveedor->id_proveedor,
                            'es_proveedor_principal' => !$tieneOtroPrincipal,
                            'precio_acordado' => null,
                            'activo' => true,
                            'fecha_asociacion' => $fechaAsoc,
                            'id_usuario' => $idUsuario,
                        ]);
                    } elseif (!$asocProv->activo) {
                        $asocProv->update([
                            'activo' => true,
                            'fecha_asociacion' => $fechaAsoc,
                            'fecha_desasociacion' => null,
                            'id_usuario' => $idUsuario,
                        ]);
                    }
                }

                $producto->refresh();

                $unidadObj = $idUnidad ? \App\Modules\Stock\Models\UnidadMedida::find($idUnidad) : null;
                $nombreUnidad = $unidadObj ? $unidadObj->nombre_unidad : ($producto->unidad_medida ?: 'u.');
                $equivBase = $unidadObj ? (float) $unidadObj->equivalencia_base : 1.0;
                $equivalenciaTexto = ($unidadObj && $equivBase > 1.0)
                    ? "{$cantidad} {$nombreUnidad} ({$cantidadBase} u. individuales)"
                    : "{$cantidad} u.";

                $registrados[] = [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'cantidad' => $cantidad,
                    'id_unidad' => $idUnidad,
                    'nombre_unidad' => $unidadObj ? $nombreUnidad : 'u.',
                    'cantidad_base' => $cantidadBase,
                    'equivalencia_texto' => $equivalenciaTexto,
                    'stock_disponible' => $producto->stock_disponible,
                    'estado_alerta' => $producto->estado_alerta,
                ];
            }

            $mensaje = 'Ingreso de mercadería registrado exitosamente.';
            if (count($registrados) === 1 && !empty($registrados[0]['id_unidad'])) {
                $mensaje = "Ingreso de mercadería registrado exitosamente: {$registrados[0]['equivalencia_texto']}.";
            }

            return response()->json([
                'status' => 'success',
                'message' => $mensaje,
                'data' => [
                    'id_proveedor' => (int) $request->input('id_proveedor'),
                    'fecha' => $fecha,
                    'items' => $registrados,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el ingreso de mercadería: ' . $e->getMessage(),
            ], 500);
        }
    }
}
