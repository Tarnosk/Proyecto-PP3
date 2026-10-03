<?php
declare(strict_types=1);

namespace App\Modules\Compras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compras\Services\PagoCompraService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * C08 - Registrar múltiples métodos de pago de una compra.
 *
 * Los pagos son 1..N contra la compra: se pueden cargar tantos como haga falta y
 * con métodos distintos en cada registro.
 */
class PagoCompraController extends Controller
{
    public function __construct(private PagoCompraService $service)
    {
    }

    /**
     * Registrar un pago de la compra.
     */
    public function store(Request $request, int $compra): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metodo_pago' => 'required|string|in:' . implode(',', \App\Modules\Compras\Models\PagoProveedor::METODOS),
            'importe' => 'required|numeric|min:0.01',
            'fecha_pago' => 'nullable|date|before_or_equal:today',
        ], [
            'metodo_pago.required' => 'Debe indicar el método de pago.',
            'metodo_pago.in' => 'El método de pago no es válido.',
            'importe.required' => 'Debe indicar el importe del pago.',
            'importe.numeric' => 'El importe debe ser numérico.',
            'importe.min' => 'El importe del pago debe ser mayor a cero.',
            'fecha_pago.before_or_equal' => 'La fecha de pago no puede ser posterior a la fecha actual.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        try {
            $resultado = $this->service->registrar($compra, [
                'metodo_pago' => $validator->validated()['metodo_pago'],
                'importe' => $validator->validated()['importe'],
                'fecha_pago' => $validator->validated()['fecha_pago'] ?? now()->toDateString(),
            ], UsuarioActual::id($request));

            return response()->json([
                'status' => 'success',
                'message' => 'Pago registrado exitosamente.',
                'data' => $resultado,
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar los pagos de la compra con el desglose por método.
     */
    public function index(int $compra): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->service->resumen($compra),
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar los pagos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resumen de la deuda de la compra (importe total, pagado y saldo a pagar).
     */
    public function resumen(int $compra): JsonResponse
    {
        try {
            $r = $this->service->resumen($compra);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'importe_total' => $r['importe_total'],
                    'total_pagado' => $r['total_pagado'],
                    'saldo_a_pagar' => $r['saldo_a_pagar'],
                    'cantidad_pagos' => $r['cantidad_pagos'],
                    'pagos_completos' => $r['pagos_completos'],
                    'por_metodo' => $r['por_metodo'],
                ],
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar el resumen de pagos: ' . $e->getMessage(),
            ], 500);
        }
    }
}