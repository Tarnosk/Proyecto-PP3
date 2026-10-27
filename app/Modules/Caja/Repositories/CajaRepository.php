<?php

namespace App\Modules\Caja\Repositories;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Models\CajaMovimiento;
use Illuminate\Database\Eloquent\Collection;

class CajaRepository
{
    public function buscarPorId(int $idCaja): ?Caja
    {
        return Caja::with('movimientos')->find($idCaja);
    }

    public function buscarAbiertaDeUsuario(int $idUsuario, string $fecha): ?Caja
    {
        return Caja::where('id_usuario', $idUsuario)
            ->where('fecha', $fecha)
            ->where('estado', Caja::ESTADO_ABIERTA)
            ->first();
    }

    /**
     * Busca CUALQUIER caja abierta del usuario, sin importar la fecha.
     * Usado por CAJ-01: si hay una caja de un dia anterior sin cerrar, hay
     * que avisar y pedir que se cierre antes de abrir una nueva.
     */
    public function buscarCualquierAbiertaDeUsuario(int $idUsuario): ?Caja
    {
        return Caja::where('id_usuario', $idUsuario)
            ->where('estado', Caja::ESTADO_ABIERTA)
            ->orderByDesc('fecha')
            ->first();
    }

    /**
     * Puede haber varias cajas del mismo usuario el mismo dia (una se cierra
     * y se abre otra), por eso devuelve una lista.
     */
    public function listarPorUsuarioYFecha(int $idUsuario, string $fecha): Collection
    {
        return Caja::with('movimientos')
            ->where('id_usuario', $idUsuario)
            ->where('fecha', $fecha)
            ->orderBy('fecha_hora_apertura')
            ->get();
    }

    public function listarPorFecha(string $fecha): Collection
    {
        return Caja::where('fecha', $fecha)->get();
    }

    /**
     * Lista cajas filtrando opcionalmente por estado (abierta/cerrada) y
     * por usuario. Sirve para encontrar la caja de Diego aunque abarque
     * varios días.
     */
    public function listarFiltradas(?string $estado, ?int $idUsuario): Collection
    {
        $query = Caja::query();

        if ($estado !== null) {
            $query->where('estado', $estado);
        }

        if ($idUsuario !== null) {
            $query->where('id_usuario', $idUsuario);
        }

        return $query->orderByDesc('fecha_hora_apertura')->get();
    }

    /**
     * Última caja del usuario que se cerró sola y todavía no tiene el
     * conteo cargado (sin monto final).
     */
    public function buscarPendienteDeConteoDeUsuario(int $idUsuario): ?Caja
    {
        return Caja::where('id_usuario', $idUsuario)
            ->where('estado', Caja::ESTADO_CERRADA)
            ->where('cierre_automatico', true)
            ->whereNull('monto_final')
            ->orderByDesc('fecha_hora_cierre')
            ->first();
    }

    /**
     * Cajas abiertas, vencidas, cuyo dueño es repartidor. Son las que se
     * cierran solas (la caja del admin no se cierra automáticamente).
     */
    public function listarVencidasDeRepartidores(\Illuminate\Support\Carbon $ahora): Collection
    {
        return Caja::where('estado', Caja::ESTADO_ABIERTA)
            ->whereNotNull('fecha_hora_vencimiento')
            ->where('fecha_hora_vencimiento', '<', $ahora)
            ->whereIn(
                'id_usuario',
                \App\Modules\Login\Models\User::where('rol', Caja::ROL_REPARTIDOR)->select('id')
            )
            ->get();
    }

    /**
     * Rol del usuario dueño de una caja (directorio de G1). Lo usa el
     * Service para saber si un admin puede cerrar esa caja.
     */
    public function rolDeUsuario(int $idUsuario): ?string
    {
        return \App\Modules\Shared\Services\UsuarioService::obtenerUsuario($idUsuario)['rol'] ?? null;
    }

    public function crear(array $datos): Caja
    {
        return Caja::create($datos);
    }

    public function guardar(Caja $caja): Caja
    {
        $caja->save();
        return $caja;
    }

    /**
     * Historial de cierres (CAJ-04). Filtros opcionales: usuario y rango de
     * fechas (desde/hasta, sobre el campo 'fecha' de la caja).
     */
    public function listarCierres(?int $idUsuario = null, ?string $desde = null, ?string $hasta = null): Collection
    {
        $query = Caja::where('estado', Caja::ESTADO_CERRADA);

        if ($idUsuario !== null) {
            $query->where('id_usuario', $idUsuario);
        }

        if ($desde !== null) {
            $query->where('fecha', '>=', $desde);
        }

        if ($hasta !== null) {
            $query->where('fecha', '<=', $hasta);
        }

        return $query->orderByDesc('fecha_hora_cierre')->get();
    }

    public function buscarCierrePorId(int $idCaja): ?Caja
    {
        return Caja::with('movimientos')
            ->where('id_caja', $idCaja)
            ->where('estado', Caja::ESTADO_CERRADA)
            ->first();
    }

    public function crearMovimiento(array $datos): CajaMovimiento
    {
        return CajaMovimiento::create($datos);
    }

    public function buscarMovimientoPorId(int $idMovimiento): ?CajaMovimiento
    {
        return CajaMovimiento::find($idMovimiento);
    }

    public function guardarMovimiento(CajaMovimiento $movimiento): CajaMovimiento
    {
        $movimiento->save();
        return $movimiento;
    }

    public function listarMovimientosDeCaja(int $idCaja): Collection
    {
        return CajaMovimiento::where('id_caja', $idCaja)->get();
    }

    /**
     * Subtotales con signo de los movimientos de la caja: efectivo por un
     * lado y valores (cheques, transferencias, otros) por el otro.
     */
    public function calcularSubtotalesPorMedio(int $idCaja): array
    {
        $efectivo = 0.0;
        $valores = 0.0;

        foreach ($this->listarMovimientosDeCaja($idCaja) as $m) {
            $importe = $m->esIngreso() ? (float) $m->monto : -1 * (float) $m->monto;

            if ($m->esEfectivo()) {
                $efectivo += $importe;
            } else {
                $valores += $importe;
            }
        }

        return ['efectivo' => round($efectivo, 2), 'valores' => round($valores, 2)];
    }

    /**
     * Saldo neto de los movimientos de la caja (ingresos - egresos),
     * redondeado a 2 decimales para evitar errores de float.
     */
    public function calcularSaldoMovimientos(int $idCaja): float
    {
        return round((float) $this->listarMovimientosDeCaja($idCaja)
            ->sum(fn (CajaMovimiento $m) => $m->esIngreso() ? (float) $m->monto : -1 * (float) $m->monto), 2);
    }
}
