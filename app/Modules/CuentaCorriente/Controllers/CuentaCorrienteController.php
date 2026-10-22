<?php

namespace App\Modules\CuentaCorriente\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ventas\Contracts\ClienteGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CuentaCorrienteController extends Controller
{
    public function index(ClienteGateway $clientes)
    {
        $listado = $clientes->todos();
        $ids = array_column($listado, 'id');
        $persistidos = DB::table('cuenta_corriente_movimientos')
            ->whereIn('cliente_id', $ids)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($listado as &$cliente) {
            $movimientos = $persistidos->where('cliente_id', $cliente['id'])
                ->map(fn ($movimiento) => [
                    'fecha' => $movimiento->created_at,
                    'descripcion' => $movimiento->descripcion,
                    'monto' => (float) $movimiento->importe,
                    'venta_id' => $movimiento->venta_id,
                    'referencia_externa' => $movimiento->referencia_externa,
                ])
                ->all();

            $cliente['movimientos'] = array_merge($cliente['movimientos'], $movimientos);
        }
        unset($cliente);

        return view('saldo', ['clientes' => $listado]);
    }

    public function registrarMovimiento(Request $request, int $cliente, ClienteGateway $clientes): JsonResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:pago,nota_credito'],
            'metodo_pago' => [
                'nullable',
                'required_if:tipo,pago',
                'in:Tarjeta de débito,Tarjeta de crédito,Efectivo,Cheque,Transferencia,QR',
            ],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ]);
        $clienteApi = $clientes->buscar($cliente);

        if ($clienteApi === null) {
            return response()->json(['message' => 'El cliente no existe.'], 404);
        }

        return DB::transaction(function () use ($cliente, $clienteApi, $datos): JsonResponse {
            $movimientos = DB::table('cuenta_corriente_movimientos')
                ->where('cliente_id', $cliente)
                ->lockForUpdate()
                ->get();

            $saldo = array_sum(array_column($clienteApi['movimientos'], 'monto'))
                + (float) $movimientos->sum('importe');
            $monto = round((float) $datos['monto'], 2);
            $tipo = $datos['tipo'];

            if ($tipo === 'pago' && ($saldo >= 0 || $monto > abs($saldo))) {
                throw ValidationException::withMessages([
                    'monto' => ['El pago no puede superar la deuda pendiente del cliente.'],
                ]);
            }

            $descripcion = $tipo === 'pago' ? 'Pago de cuenta' : 'Nota de crédito';
            $fecha = now();
            $id = DB::table('cuenta_corriente_movimientos')->insertGetId([
                'cliente_id' => $cliente,
                'venta_id' => null,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'importe' => $monto,
                'referencia_externa' => $datos['metodo_pago'] ?? null,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);

            return response()->json([
                'movimiento' => [
                    'fecha' => $fecha->toDateTimeString(),
                    'descripcion' => $descripcion,
                    'monto' => $monto,
                    'venta_id' => null,
                    'referencia_externa' => $datos['metodo_pago'] ?? null,
                ],
                'saldo' => round($saldo + $monto, 2),
                'referencia' => $id,
            ], 201);
        });
    }
}
