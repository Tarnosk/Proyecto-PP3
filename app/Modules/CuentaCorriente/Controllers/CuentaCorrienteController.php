<?php

namespace App\Modules\CuentaCorriente\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rendiciones\Models\Rendicion;
use App\Modules\Rendiciones\Models\RendicionCobro;
use App\Modules\Ventas\Contracts\ClienteGateway;
use App\Modules\Ventas\Enums\EstadoVenta;
use App\Modules\Ventas\Models\Venta;
use App\Modules\Ventas\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CuentaCorrienteController extends Controller
{
    // Marca en referencia_externa el cobro de rendición ya aplicado a la cuenta.
    private const PREFIJO_COBRO = 'rendicion_cobro:';

    public function index(ClienteGateway $clientes)
    {
        $listado = $clientes->todos();
        $ids = array_column($listado, 'id');
        $persistidos = DB::table('cuenta_corriente_movimientos')
            ->whereIn('cliente_id', $ids)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $cobrosPendientes = $this->cobrosSinAplicar($ids);

        foreach ($listado as &$cliente) {
            $movimientos = $persistidos->where('cliente_id', $cliente['id'])
                ->map(fn ($movimiento) => [
                    'fecha' => $movimiento->created_at,
                    'descripcion' => $movimiento->descripcion,
                    'monto' => (float) $movimiento->importe,
                    'venta_id' => $movimiento->venta_id,
                    'referencia_externa' => str_starts_with((string) $movimiento->referencia_externa, self::PREFIJO_COBRO)
                        ? 'Rendición'
                        : $movimiento->referencia_externa,
                ])
                ->all();

            $cliente['movimientos'] = array_merge($cliente['movimientos'], $movimientos);
            $cliente['cobros_rendicion'] = $cobrosPendientes->where('id_cliente', $cliente['id'])
                ->map(fn (RendicionCobro $cobro) => [
                    'id' => $cobro->id_rendicion_cobro,
                    'id_rendicion' => $cobro->id_rendicion,
                    'id_venta' => $cobro->id_factura,
                    'monto' => (float) $cobro->monto,
                    'medio_pago' => $cobro->medio_pago,
                ])
                ->values()
                ->all();
        }
        unset($cliente);

        return view('saldos.saldo', ['clientes' => $listado]);
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

    public function registrarCobro(Request $request): JsonResponse
    {
        $id = (int) $request->validate(['id' => ['required', 'integer', 'min:1']])['id'];

        return DB::transaction(function () use ($id): JsonResponse {
            $cobro = RendicionCobro::query()->whereKey($id)->lockForUpdate()->first();

            if ($cobro === null) {
                return response()->json(['message' => 'El cobro no existe.'], 404);
            }

            if (! Rendicion::query()->whereKey($cobro->id_rendicion)->where('estado', 'aprobada')->exists()) {
                return response()->json(['message' => 'La rendición de este cobro no está aprobada.'], 409);
            }

            $referencia = self::PREFIJO_COBRO.$cobro->id_rendicion_cobro;

            if (DB::table('cuenta_corriente_movimientos')->where('referencia_externa', $referencia)->exists()) {
                return response()->json(['mensaje' => 'Este pago ya fue procesado.'], 200);
            }

            $venta = Venta::query()->whereKey($cobro->id_factura)->lockForUpdate()->first();

            if ($venta === null || (int) $venta->id_cliente !== (int) $cobro->id_cliente) {
                throw ValidationException::withMessages([
                    'id' => ['El cobro no corresponde a una venta de ese cliente.'],
                ]);
            }

            $estado = EstadoVenta::desdeTexto($venta->estado);

            if ($estado === EstadoVenta::Cancelada) {
                throw ValidationException::withMessages([
                    'id' => ["La venta #{$venta->id_venta} está anulada."],
                ]);
            }

            $monto = round((float) $cobro->monto, 2);
            $cobrado = round($this->cobradoDeVenta((int) $venta->id_venta) + $monto, 2);

            if ($cobrado > round((float) $venta->total, 2)) {
                throw ValidationException::withMessages([
                    'id' => ["El cobro supera lo pendiente de la venta #{$venta->id_venta}."],
                ]);
            }

            $descripcion = "Cobro de rendición #{$cobro->id_rendicion} - Venta #{$venta->id_venta} ({$cobro->medio_pago})";
            $fecha = now();
            DB::table('cuenta_corriente_movimientos')->insert([
                'cliente_id' => $cobro->id_cliente,
                'venta_id' => null,
                'tipo' => 'pago',
                'descripcion' => $descripcion,
                'importe' => $monto,
                'referencia_externa' => $referencia,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);

            $ventaPagada = $cobrado >= round((float) $venta->total, 2);

            if ($ventaPagada && $estado?->puedePasarA(EstadoVenta::Pagada)) {
                app(VentaService::class)->cambiarEstado(
                    (int) $venta->id_venta,
                    EstadoVenta::Pagada,
                    "Cobro de rendición #{$cobro->id_rendicion}"
                );
            }

            return response()->json([
                'mensaje' => 'Cobro registrado en la cuenta corriente.',
                'movimiento' => [
                    'fecha' => $fecha->toDateTimeString(),
                    'descripcion' => $descripcion,
                    'monto' => $monto,
                    'venta_id' => null,
                    'referencia_externa' => 'Rendición',
                ],
                'venta_pagada' => $ventaPagada,
            ], 201);
        });
    }

    /**
     * Cobros de rendiciones aprobadas que todavía no impactaron en la cuenta.
     */
    private function cobrosSinAplicar(array $idsClientes): Collection
    {
        $aplicados = $this->cobrosAplicados();

        return RendicionCobro::query()
            ->whereIn('id_cliente', $idsClientes)
            ->whereIn('id_rendicion', Rendicion::query()->where('estado', 'aprobada')->select('id_rendicion'))
            ->orderBy('id_rendicion_cobro')
            ->get()
            ->reject(fn (RendicionCobro $cobro) => $aplicados->contains($cobro->id_rendicion_cobro));
    }

    private function cobradoDeVenta(int $idVenta): float
    {
        return (float) RendicionCobro::query()
            ->where('id_factura', $idVenta)
            ->whereIn('id_rendicion_cobro', $this->cobrosAplicados())
            ->sum('monto');
    }

    private function cobrosAplicados(): Collection
    {
        return DB::table('cuenta_corriente_movimientos')
            ->where('referencia_externa', 'like', self::PREFIJO_COBRO.'%')
            ->pluck('referencia_externa')
            ->map(fn (string $referencia) => (int) substr($referencia, strlen(self::PREFIJO_COBRO)));
    }
}
