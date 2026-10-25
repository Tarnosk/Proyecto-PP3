<?php

namespace App\Modules\ConciliacionBancaria\Services;

use App\Modules\ConciliacionBancaria\Repositories\ConciliacionRepository;
use Illuminate\Support\Carbon;

class ConciliacionService
{
    public function __construct(private ConciliacionRepository $repository)
    {
    }

    public function listarPeriodos(?string $estado = null): array
    {
        return $this->repository->listarPeriodos($estado)->toArray();
    }

    public function buscarPeriodo(int $idPeriodo): ?array
    {
        $periodo = $this->repository->buscarPeriodo($idPeriodo);

        return $periodo?->toArray();
    }

    /**
     * Crea un periodo manual. Bloquea si se superpone con uno existente
     * (incluido el periodo automatico del mes, si ya se creo).
     */
    public function crearPeriodo(array $datos): array
    {
        if ($this->repository->existeSuperposicion($datos['fecha_desde'], $datos['fecha_hasta'])) {
            return [
                'error' => true,
                'mensaje' => 'Ya existe un periodo que se superpone con esas fechas.',
            ];
        }

        $periodo = $this->repository->crearPeriodo($datos);

        return $periodo->toArray();
    }

    /**
     * Se llama cada vez que alguien entra al modulo. Si no existe todavia
     * un periodo que cubra el dia de hoy, crea automaticamente uno para
     * todo el mes calendario actual. Reemplaza la necesidad de un cron/
     * scheduler: se crea "perezosamente" en el primer acceso del mes.
     */
    public function asegurarPeriodoAutomaticoDelMes(int $idUsuarioSistema): array
    {
        $hoy = Carbon::now();
        $periodoExistente = $this->repository->buscarPeriodoQueCubre($hoy->toDateString());

        if ($periodoExistente !== null) {
            return $periodoExistente->toArray();
        }

        $datos = [
            'fecha_desde' => $hoy->copy()->startOfMonth()->toDateString(),
            'fecha_hasta' => $hoy->copy()->endOfMonth()->toDateString(),
            'id_usuario' => $idUsuarioSistema,
        ];

        $periodo = $this->repository->crearPeriodo($datos);

        return $periodo->toArray();
    }

    public function conciliarAutomatico(int $idPeriodo): ?array
    {
        $periodo = $this->repository->buscarPeriodo($idPeriodo);

        if (!$periodo) {
            return null;
        }

        if ($periodo->estado !== 'abierto') {
            return ['error' => true, 'mensaje' => 'Solo se puede conciliar un periodo abierto'];
        }

        $movimientosPendientes = $this->repository->listarMovimientosPendientes($idPeriodo);

        $conciliados = [];
        $sinCandidato = [];
        $ambiguos = [];

        foreach ($movimientosPendientes as $movimiento) {
            $candidatos = $movimiento->tipo === 'credito'
                ? $this->repository->buscarCandidatosCredito($movimiento)
                : $this->repository->buscarCandidatosDebito($movimiento);

            if (count($candidatos) === 0) {
                $sinCandidato[] = $movimiento->id_movimiento_bancario;
                continue;
            }

            if (count($candidatos) > 1) {
                $ambiguos[] = [
                    'id_movimiento_bancario' => $movimiento->id_movimiento_bancario,
                    'candidatos_encontrados' => count($candidatos),
                ];
                continue;
            }

            $candidato = $candidatos[0];

            $detalle = $this->repository->crearDetalle([
                'id_movimiento_bancario' => $movimiento->id_movimiento_bancario,
                'id_cobro' => $candidato['origen'] === 'cobro' ? $candidato['id'] : null,
                'id_movimiento_caja' => $candidato['origen'] === 'caja' ? $candidato['id'] : null,
                'id_ajuste' => $candidato['origen'] === 'ajuste' ? $candidato['id'] : null,
                'id_cheque' => $candidato['origen'] === 'cheque' ? $candidato['id'] : null,
                'metodo' => 'automatico',
                'id_usuario' => $periodo->id_usuario,
            ]);

            $this->repository->marcarMovimientoConciliado($movimiento);

            $conciliados[] = [
                'id_movimiento_bancario' => $movimiento->id_movimiento_bancario,
                'origen' => $candidato['origen'],
                'id_origen' => $candidato['id'],
                'id_detalle' => $detalle->id_detalle,
            ];
        }

        return [
            'periodo' => $periodo->toArray(),
            'total_pendientes_procesados' => $movimientosPendientes->count(),
            'conciliados_automaticamente' => count($conciliados),
            'detalle_conciliados' => $conciliados,
            'sin_candidato' => $sinCandidato,
            'ambiguos_requieren_revision_manual' => $ambiguos,
        ];
    }

    public function conciliarManual(int $idMovimiento, array $datos): ?array
    {
        $movimiento = $this->repository->buscarMovimiento($idMovimiento);

        if (!$movimiento) {
            return null;
        }

        $origenes = array_filter([
            $datos['id_cobro'] ?? null,
            $datos['id_movimiento_caja'] ?? null,
            $datos['id_ajuste'] ?? null,
            $datos['id_cheque'] ?? null,
        ]);

        if (count($origenes) !== 1) {
            return [
                'error' => true,
                'mensaje' => 'Debe indicarse exactamente un origen (cobro, movimiento de caja, ajuste o cheque)',
            ];
        }

        $detalle = $this->repository->crearDetalle([
            'id_movimiento_bancario' => $movimiento->id_movimiento_bancario,
            'id_cobro' => $datos['id_cobro'] ?? null,
            'id_movimiento_caja' => $datos['id_movimiento_caja'] ?? null,
            'id_ajuste' => $datos['id_ajuste'] ?? null,
            'id_cheque' => $datos['id_cheque'] ?? null,
            'metodo' => 'manual',
            'id_usuario' => $datos['id_usuario'],
        ]);

        $this->repository->marcarMovimientoConciliado($movimiento);

        return $detalle->toArray();
    }

    public function cerrarPeriodo(int $idPeriodo): ?array
    {
        $periodo = $this->repository->buscarPeriodo($idPeriodo);

        if (!$periodo) {
            return null;
        }

        if ($periodo->estado !== 'abierto') {
            return ['error' => true, 'mensaje' => 'El periodo ya esta cerrado'];
        }

        $periodo = $this->repository->cerrarPeriodo($periodo);

        return $periodo->toArray();
    }
}