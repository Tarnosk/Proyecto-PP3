<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\Lote;
use App\Modules\Stock\Services\StockService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DevolucionController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S05 - Registrar devolución de un producto al depósito.
     * Selecciona el producto devuelto, su cantidad y motivo; repone o descuenta el stock
     * del producto y del lote asignado, registrando el movimiento tipo 'devolucion' (Kardex).
     */
    public function store(Request $request): JsonResponse
    {
        $rawUnidad = $request->input('id_unidad');
        if ($rawUnidad === 0 || $rawUnidad === '0' || $rawUnidad === '' || $rawUnidad === null) {
            $request->merge(['id_unidad' => null]);
        }

        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
            'tipo_devolucion' => 'nullable|string|in:cliente,proveedor',
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'id_lote' => 'nullable|integer|exists:LOTE,id_lote',
            'nro_lote' => 'nullable|string|max:100',
            'fecha' => 'nullable|date|before_or_equal:today',
        ], [
            'id_producto.required' => 'Debe seleccionar el producto devuelto.',
            'cantidad.required' => 'Debe registrar la cantidad devuelta.',
            'cantidad.min' => 'La cantidad devuelta debe ser mayor a cero.',
            'motivo.required' => 'Debe indicar el motivo de la devolución.',
            'tipo_devolucion.in' => 'El tipo de devolución debe ser cliente o proveedor.',
            'id_lote.exists' => 'El lote seleccionado no existe en el sistema.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $producto = Producto::find((int) $request->input('id_producto'));

            if (!$producto) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Producto no encontrado.',
                ], 404);
            }

            if ($producto->estado !== 'activo') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se pueden registrar devoluciones sobre productos inactivos.',
                ], 400);
            }

            $idUsuario = UsuarioActual::id($request);
            $idUnidad = ($request->filled('id_unidad') && (int) $request->input('id_unidad') > 0)
                ? (int) $request->input('id_unidad')
                : null;
            $cantidad = (int) $request->input('cantidad');

            $tipoDevolucion = $request->input('tipo_devolucion', 'cliente');
            // Cliente: devuelve mercadería previamente vendida -> SUMA al stock ($esIncremento = true)
            // Proveedor: se devuelve mercadería al proveedor -> DESCUENTA del stock ($esIncremento = false)
            $esIncremento = ($tipoDevolucion === 'cliente');

            $cantidadBase = StockService::cantidadEnUnidadBase($cantidad, $idUnidad);
            if (!$esIncremento && $producto->stock_disponible < $cantidadBase) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Stock insuficiente para devolver al proveedor. Stock actual disponible: {$producto->stock_disponible}, solicitado: {$cantidadBase}.",
                ], 400);
            }

            // Identificar lote asignado si se especificó
            $idLote = $request->filled('id_lote') ? (int) $request->input('id_lote') : null;
            $nroLote = $request->filled('nro_lote') ? trim((string) $request->input('nro_lote')) : null;
            $lote = null;

            if ($idLote) {
                $lote = Lote::where('id_producto', $producto->id_producto)
                    ->where('id_lote', $idLote)
                    ->first();
                if (!$lote) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'El lote seleccionado no pertenece al producto a devolver.',
                    ], 400);
                }
            } elseif ($nroLote) {
                $lote = Lote::where('id_producto', $producto->id_producto)
                    ->where('nro_lote', $nroLote)
                    ->first();
            }

            // Si es devolución a proveedor con lote especificado, verificar stock del lote
            if (!$esIncremento && $lote && $lote->cantidad_actual < $cantidadBase) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Stock insuficiente en el lote '{$lote->nro_lote}' para devolver al proveedor. Disponible en lote: {$lote->cantidad_actual} u., solicitado: {$cantidadBase} u.",
                ], 400);
            }

            $fechaDev = null;
            if ($request->has('fecha') && $request->input('fecha')) {
                try {
                    $fechaDev = \Carbon\Carbon::parse($request->input('fecha'));
                    if (!str_contains((string) $request->input('fecha'), ':')) {
                        $ahora = \Carbon\Carbon::now();
                        $fechaDev->setTime($ahora->hour, $ahora->minute, $ahora->second);
                    }
                } catch (\Throwable $e) {
                    $fechaDev = \Carbon\Carbon::now();
                }
            } else {
                $fechaDev = \Carbon\Carbon::now();
            }

            $prefijo = $esIncremento ? '[Devolución Cliente]' : '[Devolución a Proveedor]';
            $motivoRaw = trim((string) $request->input('motivo'));
            $motivoFinal = str_starts_with($motivoRaw, '[Devolución') ? $motivoRaw : "{$prefijo} {$motivoRaw}";
            if ($lote) {
                $motivoFinal .= " (Lote: {$lote->nro_lote})";
            }

            // Registrar movimiento en el Kardex y actualizar stock de producto
            $movimiento = $this->stockService->registrarMovimiento(
                $producto,
                'devolucion',
                $cantidad,
                $motivoFinal,
                $idUsuario,
                $idUnidad,
                null,
                null,
                $fechaDev,
                $esIncremento
            );

            // Actualizar stock del lote
            if ($lote) {
                if ($esIncremento) {
                    $lote->cantidad_actual += $cantidadBase;
                } else {
                    $lote->cantidad_actual -= $cantidadBase;
                }
                $lote->save();
                $movimiento->update(['id_lote' => $lote->id_lote]);
            } elseif ($esIncremento && $nroLote) {
                // Si el cliente devuelve con un nro de lote nuevo que no existía
                $lote = Lote::create([
                    'id_producto' => $producto->id_producto,
                    'nro_lote' => $nroLote,
                    'cantidad_inicial' => $cantidadBase,
                    'cantidad_actual' => $cantidadBase,
                    'fecha_vencimiento' => $request->input('fecha_vencimiento'),
                ]);
                $movimiento->update(['id_lote' => $lote->id_lote]);
            }

            $producto->refresh();

            $unidadObj = $idUnidad ? \App\Modules\Stock\Models\UnidadMedida::find($idUnidad) : null;
            $nombreUnidad = $unidadObj ? $unidadObj->nombre_unidad : 'u.';
            $equivTexto = ($unidadObj && (float) $unidadObj->equivalencia_base > 1.0)
                ? "{$cantidad} {$nombreUnidad} ({$cantidadBase} u. individuales)"
                : "{$cantidadBase} u.";
            $unidadMsg = ($unidadObj && (float) $unidadObj->equivalencia_base > 1.0) ? " [Equivalencia: {$equivTexto}]" : "";

            $loteMsg = $lote ? " y actualizado el lote '{$lote->nro_lote}' (Stock en lote: {$lote->cantidad_actual} u.)" : "";
            $mensaje = $esIncremento
                ? "Devolución de cliente registrada exitosamente (+{$cantidadBase} u. sumadas al stock{$loteMsg}){$unidadMsg}."
                : "Devolución a proveedor registrada exitosamente (-{$cantidadBase} u. descontadas del stock{$loteMsg}){$unidadMsg}.";

            return response()->json([
                'status' => 'success',
                'message' => $mensaje,
                'data' => [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'tipo_devolucion' => $tipoDevolucion,
                    'impacto_stock' => $esIncremento ? "+{$cantidadBase}" : "-{$cantidadBase}",
                    'cantidad_devuelta' => $cantidad,
                    'id_unidad' => $idUnidad,
                    'nombre_unidad' => $nombreUnidad,
                    'cantidad_base' => $cantidadBase,
                    'equivalencia_texto' => $equivTexto,
                    'motivo' => $motivoFinal,
                    'stock_disponible' => $producto->stock_disponible,
                    'estado_alerta' => $producto->estado_alerta,
                    'id_lote' => $lote ? $lote->id_lote : null,
                    'nro_lote' => $lote ? $lote->nro_lote : null,
                    'lote_cantidad_actual' => $lote ? $lote->cantidad_actual : null,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

