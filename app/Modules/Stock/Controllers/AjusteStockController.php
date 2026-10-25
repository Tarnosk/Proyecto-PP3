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
     * Cuando se indica un lote, el ajuste actualiza también el stock general.
     * Si el producto no tiene lotes, se permite disminuir directamente el stock general.
     */
    public function store(Request $request): JsonResponse
    {
        $rawUnidad = $request->input('id_unidad');
        if ($rawUnidad === 0 || $rawUnidad === '0' || $rawUnidad === '' || $rawUnidad === null) {
            $request->merge(['id_unidad' => null]);
        }

        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'cantidad' => 'required|numeric|min:0',
            'tipo' => 'required|in:aumentar,disminuir,fijar,establecer',
            'motivo' => 'required|string|max:255',
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'id_lote' => 'nullable|integer',
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
            'cantidad.required' => 'Debe registrar la cantidad ajustada.',
            'cantidad.min' => 'La cantidad debe ser mayor o igual a cero.',
            'tipo.required' => 'Debe indicar el tipo de ajuste (aumentar, disminuir o fijar).',
            'tipo.in' => 'El tipo de ajuste debe ser "aumentar", "disminuir" o "fijar".',
            'motivo.required' => 'Debe indicar el motivo del ajuste (rotura, pérdida, corrección...).',
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

            $cantidad = (int) $request->input('cantidad', 0);
            $tipo = $request->input('tipo', 'aumentar');
            $cantidadBase = StockService::cantidadEnUnidadBase($cantidad, $idUnidad);

            $esIncremento = true;
            $cantFinal = $cantidadBase;

            if ($tipo === 'fijar' || $tipo === 'establecer') {
                $baseActual = (int) $producto->stock_disponible;
                $delta = $cantidadBase - $baseActual;

                if ($delta === 0) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'La cantidad actual ya coincide con el valor indicado.',
                        'data' => [
                            'id_producto' => $producto->id_producto,
                            'codigo' => $producto->codigo,
                            'descripcion' => $producto->descripcion,
                            'stock_disponible' => $producto->stock_disponible,
                            'id_lote' => null,
                            'lote_cantidad_actual' => null,
                            'diferencia' => 0,
                        ],
                    ], 200);
                }

                $esIncremento = ($delta > 0);
                $cantFinal = abs($delta);
            } elseif ($tipo === 'disminuir') {
                $esIncremento = false;
                $cantFinal = $cantidadBase;
            } else {
                $esIncremento = true;
                $cantFinal = $cantidadBase;
            }

            if (!$esIncremento && $producto->stock_disponible < $cantFinal) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Stock insuficiente en el producto para descontar {$cantFinal} u. Disponible actual: {$producto->stock_disponible} u.",
                ], 400);
            }

            $prefijo = $esIncremento ? 'Ajuste manual (+): ' : 'Ajuste manual (-): ';
            $motivoFinal = "{$prefijo}{$motivoRaw}";

            $cantMovimiento = in_array($tipo, ['fijar', 'establecer'], true) ? $cantFinal : $cantidad;
            $unidadMovimiento = in_array($tipo, ['fijar', 'establecer'], true) ? null : $idUnidad;

            $this->stockService->registrarMovimiento(
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

            $producto->refresh();
            $impactoStr = $esIncremento ? "+{$cantFinal}" : "-{$cantFinal}";
            $unidadObj = $idUnidad ? \App\Modules\Stock\Models\UnidadMedida::find($idUnidad) : null;
            $nombreUnidad = $unidadObj ? $unidadObj->nombre_unidad : 'u.';
            $equivTexto = ($unidadObj && (float) $unidadObj->equivalencia_base > 1.0)
                ? "{$cantidad} {$nombreUnidad} ({$cantFinal} u. individuales)"
                : "{$cantFinal} u.";
            $unidadMsg = ($unidadObj && (float) $unidadObj->equivalencia_base > 1.0) ? " [Equivalencia: {$equivTexto}]" : "";

            return response()->json([
                'status' => 'success',
                'message' => "Ajuste de inventario registrado ({$impactoStr} u. aplicadas al stock general){$unidadMsg}.",
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
                    'id_lote' => null,
                    'nro_lote' => null,
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
