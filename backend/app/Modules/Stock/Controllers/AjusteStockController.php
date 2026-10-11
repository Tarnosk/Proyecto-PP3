<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\Lote;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Services\StockService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AjusteStockController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S06 - Ajuste manual de inventario (rotura, pérdida, correcciones).
     * Regla de negocio estricta:
     * Si se descuenta/aumenta del lote, se descuenta/aumenta obligatoriamente del producto,
     * y viceversa. Lo que cambia es desde qué lado se inicia (producto o lote), pero ambos
     * se actualizan siempre de forma sincrónica e indivisible.
     */
    public function store(Request $request): JsonResponse
    {
        $rawUnidad = $request->input('id_unidad');
        if ($rawUnidad === 0 || $rawUnidad === '0' || $rawUnidad === '' || $rawUnidad === null) {
            $request->merge(['id_unidad' => null]);
        }

        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'cantidad' => 'required_unless:alcance,sincronizar_producto|nullable|numeric|min:0',
            'tipo' => 'required_unless:alcance,sincronizar_producto|nullable|in:aumentar,disminuir,fijar,establecer',
            'motivo' => 'required|string|max:255',
            'alcance' => 'nullable|in:ambos,sincronizar_producto',
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'id_lote' => 'nullable|integer|exists:LOTE,id_lote',
            'nro_lote' => 'nullable|string|max:100',
            'fecha_vencimiento' => 'nullable|date',
            'fecha' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    $fecha = \Carbon\Carbon::parse($value)->startOfDay();
                    $hoy = \Carbon\Carbon::today();
                    if ($fecha->greaterThan($hoy)) {
                        $fail('La fecha del ajuste no puede ser posterior a la fecha actual.');
                    }
                },
            ],
        ], [
            'id_producto.required' => 'Debe seleccionar un producto.',
            'cantidad.required_unless' => 'Debe registrar la cantidad ajustada.',
            'cantidad.min' => 'La cantidad debe ser mayor o igual a cero.',
            'tipo.required_unless' => 'Debe indicar el tipo de ajuste (aumentar, disminuir o fijar).',
            'tipo.in' => 'El tipo de ajuste debe ser "aumentar", "disminuir" o "fijar".',
            'motivo.required' => 'Debe indicar el motivo del ajuste (rotura, pérdida, corrección...).',
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
                    'message' => 'No se pueden realizar ajustes de stock sobre productos inactivos.',
                ], 400);
            }

            $idUsuario = UsuarioActual::id($request);
            $alcance = $request->input('alcance', 'ambos');
            $idUnidad = ($request->filled('id_unidad') && (int) $request->input('id_unidad') > 0)
                ? (int) $request->input('id_unidad')
                : null;
            $motivoRaw = trim((string) $request->input('motivo'));

            $fechaAjuste = null;
            if ($request->has('fecha') && $request->input('fecha')) {
                try {
                    $fechaAjuste = \Carbon\Carbon::parse($request->input('fecha'));
                    if (!str_contains((string) $request->input('fecha'), ':')) {
                        $ahora = \Carbon\Carbon::now();
                        $fechaAjuste->setTime($ahora->hour, $ahora->minute, $ahora->second);
                    }
                } catch (\Throwable $e) {
                    $fechaAjuste = \Carbon\Carbon::now();
                }
            } else {
                $fechaAjuste = \Carbon\Carbon::now();
            }

            // =========================================================
            // RECONCILIACIÓN / SINCRONIZACIÓN AUTOMÁTICA
            // =========================================================
            if ($alcance === 'sincronizar_producto') {
                $sumaLotes = (int) Lote::where('id_producto', $producto->id_producto)->sum('cantidad_actual');
                $stockActual = (int) $producto->stock_disponible;
                $delta = $sumaLotes - $stockActual;

                if ($delta === 0) {
                    return response()->json([
                        'status' => 'success',
                        'message' => "El stock general del producto ({$stockActual} u.) ya coincide con la suma de sus lotes.",
                        'data' => [
                            'id_producto' => $producto->id_producto,
                            'codigo' => $producto->codigo,
                            'descripcion' => $producto->descripcion,
                            'stock_disponible' => $producto->stock_disponible,
                            'suma_lotes' => $sumaLotes,
                            'diferencia' => 0,
                        ],
                    ], 200);
                }

                $esIncremento = ($delta > 0);
                $cantAbs = abs($delta);
                $motivoFinal = "Ajuste de sincronización: Se igualó el stock general ({$stockActual} u. -> {$sumaLotes} u.) con la suma física de lotes. {$motivoRaw}";

                $this->stockService->registrarMovimiento(
                    $producto,
                    'ajuste',
                    $cantAbs,
                    $motivoFinal,
                    $idUsuario,
                    null,
                    null,
                    null,
                    $fechaAjuste,
                    $esIncremento
                );

                $producto->refresh();

                return response()->json([
                    'status' => 'success',
                    'message' => "Stock general sincronizado exitosamente con la suma de lotes ({$sumaLotes} u.).",
                    'data' => [
                        'id_producto' => $producto->id_producto,
                        'codigo' => $producto->codigo,
                        'descripcion' => $producto->descripcion,
                        'tipo' => $esIncremento ? 'aumentar' : 'disminuir',
                        'impacto_stock' => $esIncremento ? "+{$cantAbs}" : "-{$cantAbs}",
                        'cantidad_ajustada' => $cantAbs,
                        'motivo' => $motivoFinal,
                        'stock_disponible' => $producto->stock_disponible,
                        'suma_lotes' => $sumaLotes,
                        'diferencia' => 0,
                    ],
                ], 200);
            }

            // Identificar lote asignado: OBLIGATORIO para evitar desincronización
            $idLote = $request->filled('id_lote') ? (int) $request->input('id_lote') : null;
            $nroLote = $request->filled('nro_lote') ? trim((string) $request->input('nro_lote')) : null;

            if (!$idLote && !$nroLote) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Debe seleccionar un lote existente o indicar el número de lote. No se permite ajustar stock sin asignar lote para evitar desincronizaciones.',
                ], 400);
            }

            $cantidad = (int) $request->input('cantidad', 0);
            $tipo = $request->input('tipo', 'aumentar');
            $cantidadBase = StockService::cantidadEnUnidadBase($cantidad, $idUnidad);

            $lote = null;
            if ($idLote) {
                $lote = Lote::where('id_producto', $producto->id_producto)
                    ->where('id_lote', $idLote)
                    ->first();
                if (!$lote) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'El lote seleccionado no pertenece al producto a ajustar.',
                    ], 400);
                }
            } elseif ($nroLote) {
                $lote = Lote::where('id_producto', $producto->id_producto)
                    ->where('nro_lote', $nroLote)
                    ->first();

                if (!$lote && in_array($tipo, ['disminuir', 'fijar', 'establecer'], true)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "El lote '{$nroLote}' no existe para este producto. Para disminuir o fijar stock debe seleccionar un lote existente.",
                    ], 400);
                }
            }

            // Determinar delta y sentido del ajuste
            $esIncremento = true;
            $cantFinal = $cantidadBase;

            if ($tipo === 'fijar' || $tipo === 'establecer') {
                // Al fijar cantidad exacta, la referencia base es el lote (si hay lote) o el producto
                $baseActual = $lote ? (int) $lote->cantidad_actual : (int) $producto->stock_disponible;
                $delta = $cantidadBase - $baseActual;

                if ($delta === 0) {
                    $sumaLotes = (int) Lote::where('id_producto', $producto->id_producto)->sum('cantidad_actual');
                    return response()->json([
                        'status' => 'success',
                        'message' => 'La cantidad actual ya coincide con el valor indicado.',
                        'data' => [
                            'id_producto' => $producto->id_producto,
                            'codigo' => $producto->codigo,
                            'descripcion' => $producto->descripcion,
                            'stock_disponible' => $producto->stock_disponible,
                            'id_lote' => $lote?->id_lote,
                            'lote_cantidad_actual' => $lote?->cantidad_actual,
                            'suma_lotes' => $sumaLotes,
                            'diferencia' => abs((int) $producto->stock_disponible - $sumaLotes),
                        ],
                    ], 200);
                }

                $esIncremento = ($delta > 0);
                $cantFinal = abs($delta);
            } elseif ($tipo === 'disminuir') {
                $esIncremento = false;
                $cantFinal = $cantidadBase;
            } else {
                // aumentar
                $esIncremento = true;
                $cantFinal = $cantidadBase;
            }

            // Validar stock disponible si es disminución:
            // Regla: si se descuenta del lote, se descuenta obligatoriamente del producto.
            if (!$esIncremento) {
                if ($lote && $lote->cantidad_actual < $cantFinal) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Stock insuficiente en el lote '{$lote->nro_lote}' para descontar {$cantFinal} u. Disponible en lote: {$lote->cantidad_actual} u.",
                    ], 400);
                }

                if ($producto->stock_disponible < $cantFinal) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Stock insuficiente en el producto para descontar {$cantFinal} u. Disponible general: {$producto->stock_disponible} u.",
                    ], 400);
                }
            }

            $prefijo = $esIncremento ? 'Ajuste manual (+): ' : 'Ajuste manual (-): ';
            $loteTexto = $lote ? " (Lote: {$lote->nro_lote})" : ($nroLote ? " (Lote nuevo: {$nroLote})" : '');
            $motivoFinal = "{$prefijo}{$motivoRaw}{$loteTexto}";

            // 1. OBLIGATORIO: Se registra el movimiento en Kardex y se actualiza el stock del producto
            $cantMovimiento = in_array($tipo, ['fijar', 'establecer'], true) ? $cantFinal : $cantidad;
            $unidadMovimiento = in_array($tipo, ['fijar', 'establecer'], true) ? null : $idUnidad;

            $movimiento = $this->stockService->registrarMovimiento(
                $producto,
                'ajuste',
                $cantMovimiento,
                $motivoFinal,
                $idUsuario,
                $unidadMovimiento,
                null,
                null,
                $fechaAjuste,
                $esIncremento
            );

            // 2. OBLIGATORIO: Si se descuenta/aumenta del lote o se indicó lote, se actualiza el lote por la misma cantidad exacta
            if ($lote) {
                if ($tipo === 'fijar' || $tipo === 'establecer') {
                    $lote->cantidad_actual = $cantidadBase;
                } elseif ($esIncremento) {
                    $lote->cantidad_actual += $cantFinal;
                } else {
                    $lote->cantidad_actual -= $cantFinal;
                }
                $lote->save();
                $movimiento->update(['id_lote' => $lote->id_lote]);
            } elseif ($esIncremento && $nroLote) {
                $lote = Lote::create([
                    'id_producto' => $producto->id_producto,
                    'nro_lote' => $nroLote,
                    'cantidad_inicial' => $cantFinal,
                    'cantidad_actual' => $cantFinal,
                    'fecha_vencimiento' => $request->input('fecha_vencimiento') ?: \Carbon\Carbon::now()->addMonths(6)->toDateString(),
                ]);
                $movimiento->update(['id_lote' => $lote->id_lote]);
            }

            $producto->refresh();
            $sumaLotes = (int) Lote::where('id_producto', $producto->id_producto)->sum('cantidad_actual');
            $impactoStr = $esIncremento ? "+{$cantFinal}" : "-{$cantFinal}";
            $loteMsg = $lote ? " y al lote '{$lote->nro_lote}' (Stock en lote: {$lote->cantidad_actual} u.)" : "";
            $unidadObj = $idUnidad ? \App\Modules\Stock\Models\UnidadMedida::find($idUnidad) : null;
            $nombreUnidad = $unidadObj ? $unidadObj->nombre_unidad : 'u.';
            $equivTexto = ($unidadObj && (float) $unidadObj->equivalencia_base > 1.0)
                ? "{$cantidad} {$nombreUnidad} ({$cantFinal} u. individuales)"
                : "{$cantFinal} u.";
            $unidadMsg = ($unidadObj && (float) $unidadObj->equivalencia_base > 1.0) ? " [Equivalencia: {$equivTexto}]" : "";

            return response()->json([
                'status' => 'success',
                'message' => "Ajuste de inventario registrado ({$impactoStr} u. aplicadas obligatoriamente tanto al producto{$loteMsg}){$unidadMsg}.",
                'data' => [
                    'id_producto' => $producto->id_producto,
                    'codigo' => $producto->codigo,
                    'descripcion' => $producto->descripcion,
                    'tipo' => $tipo,
                    'impacto_stock' => $impactoStr,
                    'cantidad_ajustada' => $cantFinal,
                    'id_unidad' => $idUnidad,
                    'nombre_unidad' => $nombreUnidad,
                    'cantidad_base' => $cantFinal,
                    'equivalencia_texto' => $equivTexto,
                    'motivo' => $motivoFinal,
                    'stock_disponible' => $producto->stock_disponible,
                    'estado_alerta' => $producto->estado_alerta,
                    'id_lote' => $lote?->id_lote,
                    'nro_lote' => $lote?->nro_lote,
                    'lote_cantidad_actual' => $lote?->cantidad_actual,
                    'suma_lotes' => $sumaLotes,
                    'diferencia' => abs((int) $producto->stock_disponible - $sumaLotes),
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
