<?php
declare(strict_types=1);

namespace App\Modules\Compras\Services;

use App\Modules\Compras\Models\Compra;
use App\Modules\Compras\Models\PagoProveedor;
use App\Modules\Compras\Repositories\PagoProveedorRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * C08 - Registrar múltiples métodos de pago de una compra.
 *
 * Una compra admite tantos pagos como haga falta, incluso del mismo método
 * (PAGO_PROVEEDOR es 1..N contra COMPRA). Cada pago descuenta del saldo a pagar.
 *
 * Nota sobre el saldo: COMPRA.saldo_pendiente lo recalcula RecepcionController
 * (C06) con el sentido de "importe de mercadería todavía no recibida"
 * (importe_total * unidades_pendientes / unidades_totales). Ese cálculo no se toca
 * para no alterar C06, así que la deuda por pagos se expone aparte como
 * saldo_a_pagar = importe_total - total_pagado. Quien necesite el saldo con el
 * sentido estándar (deuda) debe usar saldo_a_pagar.
 */
class PagoCompraService
{
    public function __construct(private PagoProveedorRepository $pagos)
    {
    }

    /**
     * Registra un pago contra la compra.
     *
     * @param array $datos metodo_pago, importe, fecha_pago
     */
    public function registrar(int $idCompra, array $datos, int $idUsuario): array
    {
        $compra = Compra::find($idCompra);
        if (!$compra) {
            throw new RuntimeException('La compra no existe.');
        }

        if ($compra->estado === 'cancelada') {
            throw new RuntimeException('No se pueden registrar pagos en una compra cancelada.');
        }

        $metodo = (string) $datos['metodo_pago'];
        if (!in_array($metodo, PagoProveedor::METODOS, true)) {
            throw new RuntimeException(
                'Método de pago inválido. Valores permitidos: ' . implode(', ', PagoProveedor::METODOS) . '.'
            );
        }

        $importe = round((float) $datos['importe'], 2);
        if ($importe <= 0) {
            throw new RuntimeException('El importe del pago debe ser mayor a cero.');
        }

        $saldo = $this->saldoAPagar($compra);
        if ($importe > $saldo) {
            throw new RuntimeException(
                'El importe del pago excede el saldo a pagar ($' . number_format($saldo, 2, ',', '.') . ').'
            );
        }

        $pago = DB::transaction(fn () => $this->pagos->crear([
            'id_compra' => $idCompra,
            'metodo_pago' => $metodo,
            'importe' => $importe,
            'fecha_pago' => $datos['fecha_pago'],
            'id_usuario' => $idUsuario,
        ]));

        return [
            'pago' => self::formatear($pago),
            'resumen' => $this->resumen($idCompra),
        ];
    }

    /**
     * Detalle de los pagos de una compra y cómo queda su deuda.
     */
    public function resumen(int $idCompra): array
    {
        $compra = Compra::find($idCompra);
        if (!$compra) {
            throw new RuntimeException('La compra no existe.');
        }

        $importeTotal = round((float) $compra->importe_total, 2);
        $totalPagado = $this->pagos->totalPagado($idCompra);
        $saldoAPagar = $this->saldoAPagar($compra);

        return [
            'compra' => [
                'id_compra' => $compra->id_compra,
                'numero_compra' => $compra->numero_compra,
                'numero_comprobante' => $compra->numero_comprobante,
                'estado' => $compra->estado,
            ],
            'importe_total' => $importeTotal,
            'total_pagado' => $totalPagado,
            'saldo_a_pagar' => $saldoAPagar,
            'cantidad_pagos' => $this->pagos->contar($idCompra),
            'pagos_completos' => $saldoAPagar <= 0,
            'por_metodo' => $this->pagos->totalesPorMetodo($idCompra),
            'pagos' => $this->pagos->listarPorCompra($idCompra)
                ->map(fn (PagoProveedor $p) => self::formatear($p))
                ->toArray(),
        ];
    }

    /** Saldo adeudado = importe total menos lo pagado, nunca negativo. */
    private function saldoAPagar(Compra $compra): float
    {
        $saldo = round((float) $compra->importe_total - $this->pagos->totalPagado($compra->id_compra), 2);

        return max(0.0, $saldo);
    }

    public static function formatear(PagoProveedor $p): array
    {
        return [
            'id_pago' => $p->id_pago,
            'id_compra' => $p->id_compra,
            'metodo_pago' => $p->metodo_pago,
            'importe' => round((float) $p->importe, 2),
            'fecha_pago' => $p->fecha_pago?->format('Y-m-d'),
            'id_usuario' => $p->id_usuario,
        ];
    }
}