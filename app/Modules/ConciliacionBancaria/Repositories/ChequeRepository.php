<?php

namespace App\Modules\ConciliacionBancaria\Repositories;

use App\Modules\ConciliacionBancaria\Models\Cheque;
use App\Modules\ConciliacionBancaria\Models\ConciliacionDetalle;
use App\Modules\ConciliacionBancaria\Models\MovimientoBancario;
use Illuminate\Database\Eloquent\Collection;

/**
 * Acceso a datos para el módulo de Cheques (dentro de Conciliación Bancaria).
 *
 * NOTA: el alcance de este Repository/Service/Controller no está respaldado
 * por ninguna historia de usuario existente — se infirió de los valores del
 * campo `estado` en la migración CHEQUE (en_cartera | depositado | conciliado
 * | rechazado). Pendiente de formalizar con una historia de usuario y
 * confirmar con el equipo (ver 05-historias-usuario.md).
 */
class ChequeRepository
{
    public function listar(?string $estado = null): Collection
    {
        $query = Cheque::query();

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        return $query->orderBy('fecha_cobro', 'desc')->get();
    }

    public function buscar(int $idCheque): ?Cheque
    {
        return Cheque::find($idCheque);
    }

    public function marcarDepositado(Cheque $cheque): Cheque
    {
        $cheque->estado = 'depositado';
        $cheque->fecha_deposito = now();
        $cheque->save();

        return $cheque;
    }

    public function marcarRechazado(Cheque $cheque): Cheque
    {
        $cheque->estado = 'rechazado';
        $cheque->fecha_rechazo = now();
        $cheque->save();

        return $cheque;
    }

    /**
     * Busca la fila de ConciliacionDetalle que tenga a este cheque como
     * origen, si existe (o sea, si el cheque ya estaba conciliado).
     */
    public function buscarDetalleConciliacion(int $idCheque): ?ConciliacionDetalle
    {
        return ConciliacionDetalle::where('id_cheque', $idCheque)->first();
    }

    /**
     * Revierte una conciliación: borra el detalle y vuelve el movimiento
     * bancario asociado a estado 'pendiente'.
     */
    public function revertirConciliacion(ConciliacionDetalle $detalle): void
    {
        $movimiento = MovimientoBancario::find($detalle->id_movimiento_bancario);

        if ($movimiento) {
            $movimiento->estado = 'pendiente';
            $movimiento->save();
        }

        $detalle->delete();
    }
}