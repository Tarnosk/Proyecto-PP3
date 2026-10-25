<?php

namespace App\Modules\ConciliacionBancaria\Services;

use App\Modules\ConciliacionBancaria\Repositories\ChequeRepository;

/**
 * Lógica de negocio de Cheques. Ver nota en ChequeRepository sobre el
 * alcance no confirmado por historia de usuario todavía.
 */
class ChequeService
{
    public function __construct(private ChequeRepository $repository)
    {
    }

    public function listar(?string $estado = null): array
    {
        return $this->repository->listar($estado)->toArray();
    }

    public function buscar(int $idCheque): ?array
    {
        $cheque = $this->repository->buscar($idCheque);

        return $cheque?->toArray();
    }

    public function depositar(int $idCheque): ?array
    {
        $cheque = $this->repository->buscar($idCheque);

        if (!$cheque) {
            return null;
        }

        if ($cheque->estado !== 'en_cartera') {
            return ['error' => true, 'mensaje' => 'Solo se puede depositar un cheque en cartera'];
        }

        $cheque = $this->repository->marcarDepositado($cheque);

        return $cheque->toArray();
    }

    /**
     * Rechaza un cheque. Si ya estaba conciliado, revierte la conciliación
     * (borra el ConciliacionDetalle y devuelve el movimiento bancario a
     * estado 'pendiente').
     */
    public function rechazar(int $idCheque): ?array
    {
        $cheque = $this->repository->buscar($idCheque);

        if (!$cheque) {
            return null;
        }

        if ($cheque->estado === 'rechazado') {
            return ['error' => true, 'mensaje' => 'El cheque ya está rechazado'];
        }

        $seRevirtioConciliacion = false;

        if ($cheque->estado === 'conciliado') {
            $detalle = $this->repository->buscarDetalleConciliacion($idCheque);

            if ($detalle) {
                $this->repository->revertirConciliacion($detalle);
                $seRevirtioConciliacion = true;
            }
        }

        $cheque = $this->repository->marcarRechazado($cheque);

        return [
            'cheque' => $cheque->toArray(),
            'conciliacion_revertida' => $seRevirtioConciliacion,
        ];
    }
}