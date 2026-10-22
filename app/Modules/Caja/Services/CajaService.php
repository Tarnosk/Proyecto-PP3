<?php

namespace App\Modules\Caja\Services;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Models\CajaMovimiento;
use App\Modules\Caja\Repositories\CajaRepository;
use Illuminate\Support\Carbon;

class CajaService
{
    public function __construct(private CajaRepository $repositorio)
    {
    }

    /**
     * CAJ-01: una sola caja abierta por usuario. Si ya tiene una (de hoy o
     * de días anteriores) se bloquea la apertura y se avisa, indicando si
     * está vencida, para que la cierre antes de abrir otra.
     * El vendedor no maneja caja: no puede abrirla.
     */
    public function abrirCaja(int $idUsuarioSesion, string $rol, float $montoInicial, ?string $observaciones = null): array
    {
        if (!Caja::rolManejaCaja($rol)) {
            return ['error' => true, 'mensaje' => 'Tu rol no maneja caja.'];
        }

        $ahora = Carbon::now();

        $cajaAbierta = $this->repositorio->buscarCualquierAbiertaDeUsuario($idUsuarioSesion);

        if ($cajaAbierta !== null) {
            $vencida = $cajaAbierta->fecha_hora_vencimiento !== null
                && $ahora->greaterThan($cajaAbierta->fecha_hora_vencimiento);

            return [
                'error' => true,
                'mensaje' => 'Tenés una caja abierta desde el '
                    . $cajaAbierta->fecha_hora_apertura->format('d/m/Y H:i')
                    . ($vencida ? ' que ya venció' : '')
                    . '. Cerrala antes de abrir otra.',
                'id_caja_pendiente' => $cajaAbierta->id_caja,
                'vencida' => $vencida,
            ];
        }

        if ($montoInicial < 0) {
            return ['error' => true, 'mensaje' => 'El monto inicial no puede ser negativo.'];
        }

        $caja = $this->repositorio->crear([
            'id_usuario' => $idUsuarioSesion,
            'fecha' => $ahora->toDateString(),
            'monto_inicial' => $montoInicial,
            'estado' => Caja::ESTADO_ABIERTA,
            'fecha_hora_apertura' => $ahora,
            'fecha_hora_vencimiento' => $this->calcularVencimiento($rol, $ahora),
            'observaciones' => $observaciones,
        ]);

        return $caja->toArray();
    }

    /**
     * Repartidor: 72 h. Admin (Diego): 7 días o fin de mes, lo que
     * ocurra primero.
     */
    private function calcularVencimiento(string $rol, Carbon $apertura): Carbon
    {
        if ($rol === Caja::ROL_REPARTIDOR) {
            return $apertura->copy()->addHours(72);
        }

        return $apertura->copy()->addDays(7)->min($apertura->copy()->endOfMonth());
    }

    public function obtenerCajasDeFecha(string $fecha): array
    {
        return $this->repositorio->listarPorFecha($fecha)->toArray();
    }

    /**
     * Lista (puede haber varias cajas del mismo usuario el mismo día).
     */
    public function obtenerCajasDeUsuarioYFecha(int $idUsuario, string $fecha): array
    {
        return $this->repositorio->listarPorUsuarioYFecha($idUsuario, $fecha)->toArray();
    }

    public function obtenerCajaAbiertaDeUsuario(int $idUsuario): ?array
    {
        return $this->repositorio->buscarCualquierAbiertaDeUsuario($idUsuario)?->toArray();
    }

    public function obtenerCajaPendienteDeConteo(int $idUsuario): ?array
    {
        return $this->repositorio->buscarPendienteDeConteoDeUsuario($idUsuario)?->toArray();
    }

    public function listarCajas(?string $estado, ?int $idUsuario): array
    {
        return $this->repositorio->listarFiltradas($estado, $idUsuario)->toArray();
    }

    public function obtenerCaja(int $idCaja): ?array
    {
        return $this->repositorio->buscarPorId($idCaja)?->toArray();
    }

    public function obtenerMontoActual(int $idCaja): ?float
    {
        $caja = $this->repositorio->buscarPorId($idCaja);

        if ($caja === null) {
            return null;
        }

        return round((float) $caja->monto_inicial + $this->repositorio->calcularSaldoMovimientos($idCaja), 2);
    }

    /**
     * Cierra la caja. Solo la cierra su dueño (repartidor o Diego), sin
     * aprobación; el admin también puede cerrar la de un repartidor. Guarda el
     * monto esperado y la diferencia (final - esperado).
     */
    public function cerrarCaja(
        int $idCaja,
        int $idUsuarioSesion,
        float $montoFinal,
        ?string $observaciones = null,
        string $rolSesion = Caja::ROL_REPARTIDOR
    ): ?array {
        $caja = $this->repositorio->buscarPorId($idCaja);

        if ($caja === null) {
            return null;
        }

        if (!$caja->perteneceA($idUsuarioSesion) && !$this->puedeCerrarCajaAjena($caja, $rolSesion)) {
            return ['error' => true, 'mensaje' => 'No podés cerrar la caja de otro usuario.'];
        }

        if (!$caja->estaAbierta()) {
            return ['error' => true, 'mensaje' => 'La caja ya está cerrada.'];
        }

        $saldoEsperado = round((float) $caja->monto_inicial + $this->repositorio->calcularSaldoMovimientos($idCaja), 2);
        $diferencia = round($montoFinal - $saldoEsperado, 2);

        $caja->monto_final = $montoFinal;
        $caja->monto_esperado = $saldoEsperado;
        $caja->diferencia = $diferencia;
        $caja->estado = Caja::ESTADO_CERRADA;
        $caja->id_usuario_cierre = $idUsuarioSesion;
        $caja->fecha_hora_cierre = Carbon::now();

        if ($observaciones !== null) {
            $caja->observaciones = $observaciones;
        }

        return $this->repositorio->guardar($caja)->toArray();
    }

    /**
     * El admin (Diego) puede cerrar la caja de un repartidor, por ejemplo si
     * el repartidor no la cerró. No puede cerrar la caja de otro admin.
     * La diferencia queda asociada al dueño de la caja (id_usuario) y
     * id_usuario_cierre guarda quién la cerró.
     */
    private function puedeCerrarCajaAjena(Caja $caja, string $rolSesion): bool
    {
        return $rolSesion === Caja::ROL_ADMIN
            && $this->repositorio->rolDeUsuario((int) $caja->id_usuario) === Caja::ROL_REPARTIDOR;
    }

    /**
     * Cierre automático: las cajas de repartidor que pasaron su vencimiento
     * (72 h) se cierran solas SIN contar. Quedan con el monto esperado
     * guardado, sin monto final ni diferencia, y marcadas como cierre
     * automático. Diego carga después el monto contado
     * con completarConteoCierreAutomatico().
     * Lo ejecuta el comando programado `caja:cerrar-vencidas`.
     * Devuelve cuántas cajas se cerraron.
     */
    public function cerrarCajasVencidas(): int
    {
        $ahora = Carbon::now();
        $cerradas = 0;

        foreach ($this->repositorio->listarVencidasDeRepartidores($ahora) as $candidata) {
            // Se vuelve a leer: pudo cerrarse a mano mientras corría el proceso.
            $caja = $this->repositorio->buscarPorId((int) $candidata->id_caja);

            if ($caja === null || !$caja->estaAbierta()) {
                continue;
            }

            $caja->monto_esperado = round(
                (float) $caja->monto_inicial + $this->repositorio->calcularSaldoMovimientos((int) $caja->id_caja),
                2
            );
            $caja->estado = Caja::ESTADO_CERRADA;
            $caja->cierre_automatico = true;
            $caja->fecha_hora_cierre = $ahora;
            $caja->id_usuario_cierre = null;

            $this->repositorio->guardar($caja);
            $cerradas++;
        }

        return $cerradas;
    }

    /**
     * Carga el monto contado de una caja que se cerró sola. Calcula la
     * diferencia contra el monto esperado que quedó guardado al cierre.
     * Solo lo carga el admin (si el dueño es repartidor). El repartidor
     * no: abre otra caja y carga ahí lo pendiente.
     */
    public function completarConteoCierreAutomatico(
        int $idCaja,
        int $idUsuarioSesion,
        float $montoFinal,
        ?string $observaciones = null,
        string $rolSesion = Caja::ROL_REPARTIDOR
    ): ?array {
        $caja = $this->repositorio->buscarPorId($idCaja);

        if ($caja === null) {
            return null;
        }

        if (!$this->puedeCerrarCajaAjena($caja, $rolSesion)) {
            return ['error' => true, 'mensaje' => 'Solo el administrador puede cargar el conteo de una caja cerrada automáticamente.'];
        }

        if (!$caja->estaPendienteDeConteo()) {
            return ['error' => true, 'mensaje' => 'Esta caja no está pendiente de conteo.'];
        }

        $caja->monto_final = $montoFinal;
        $caja->diferencia = round($montoFinal - (float) $caja->monto_esperado, 2);
        $caja->id_usuario_cierre = $idUsuarioSesion;

        if ($observaciones !== null) {
            $caja->observaciones = $observaciones;
        }

        return $this->repositorio->guardar($caja)->toArray();
    }

    public function obtenerHistorialCierres(?int $idUsuario = null, ?string $desde = null, ?string $hasta = null): array
    {
        return $this->repositorio->listarCierres($idUsuario, $desde, $hasta)->toArray();
    }

    public function obtenerDetalleCierre(int $idCaja): ?array
    {
        $cierre = $this->repositorio->buscarCierrePorId($idCaja)?->toArray();

        if ($cierre === null) {
            return null;
        }

        // Subtotales de movimientos: efectivo por un lado, valores por el otro.
        $subtotales = $this->repositorio->calcularSubtotalesPorMedio($idCaja);
        $cierre['subtotal_efectivo'] = $subtotales['efectivo'];
        $cierre['subtotal_valores'] = $subtotales['valores'];

        return $cierre;
    }

    /**
     * Los movimientos con la caja vencida NO se bloquean (el repartidor
     * puede estar en ruta). $idCobro es opcional: lo usa Cobros para
     * dejar vinculado el movimiento con el cobro que lo generó.
     */
    public function registrarMovimiento(
        int $idCaja,
        int $idUsuarioSesion,
        string $rol,
        string $tipo,
        string $concepto,
        float $monto,
        ?int $idCobro = null,
        string $medioPago = CajaMovimiento::MEDIO_EFECTIVO
    ): ?array {
        $caja = $this->repositorio->buscarPorId($idCaja);

        if ($caja === null) {
            return null;
        }

        if (!$caja->perteneceA($idUsuarioSesion)) {
            return ['error' => true, 'mensaje' => 'No podés registrar movimientos en la caja de otro usuario.'];
        }

        if (!$caja->estaAbierta()) {
            return [
                'error' => true,
                'mensaje' => 'No se pueden registrar movimientos en una caja cerrada. Abrí una caja nueva para cargarlos.',
            ];
        }

        if (!in_array($tipo, CajaMovimiento::tiposPermitidosPara($rol), true)) {
            return ['error' => true, 'mensaje' => 'Tipo de movimiento no permitido para tu rol.'];
        }

        if ($monto <= 0) {
            return ['error' => true, 'mensaje' => 'El monto debe ser mayor a cero.'];
        }

        if (!in_array($medioPago, CajaMovimiento::MEDIOS_VALIDOS, true)) {
            return ['error' => true, 'mensaje' => 'Medio de pago no válido.'];
        }

        $ahora = Carbon::now();

        $movimiento = $this->repositorio->crearMovimiento([
            'id_caja' => $idCaja,
            'id_cobro' => $idCobro,
            'id_usuario' => $idUsuarioSesion,
            'fecha' => $ahora->toDateString(),
            'fecha_hora_carga' => $ahora,
            'tipo' => $tipo,
            'medio_pago' => $medioPago,
            'concepto' => $concepto,
            'monto' => round($monto, 2),
        ]);

        return $movimiento->toArray();
    }

    /**
     * Cobro en efectivo: entra a la caja abierta del usuario que cobra,
     * como movimiento de tipo 'cobro'. Si no tiene caja abierta se rechaza.
     * Pensado para que lo llame el módulo Cobros (a acordar con el equipo).
     */
    public function registrarCobroEnEfectivo(
        int $idUsuario,
        string $rol,
        float $monto,
        string $concepto,
        ?int $idCobro = null
    ): ?array {
        return $this->registrarCobro($idUsuario, $rol, $monto, $concepto, CajaMovimiento::MEDIO_EFECTIVO, $idCobro);
    }

    /**
     * Cobro con cualquier medio de pago (efectivo, cheque, transferencia, otro):
     * entra a la caja abierta de quien cobra como movimiento 'cobro' con su
     * medio_pago. Si no tiene caja abierta se rechaza. (COB-01)
     */
    public function registrarCobro(
        int $idUsuario,
        string $rol,
        float $monto,
        string $concepto,
        string $medioPago = CajaMovimiento::MEDIO_EFECTIVO,
        ?int $idCobro = null
    ): ?array {
        $caja = $this->repositorio->buscarCualquierAbiertaDeUsuario($idUsuario);

        if ($caja === null) {
            return ['error' => true, 'mensaje' => 'No tenés una caja abierta. Abrila antes de registrar el cobro.'];
        }

        return $this->registrarMovimiento(
            $caja->id_caja,
            $idUsuario,
            $rol,
            CajaMovimiento::TIPO_COBRO,
            $concepto,
            $monto,
            $idCobro,
            $medioPago
        );
    }

    /**
     * Anulación de un cobro (COB-03): se registra una reversión que RESTA en la
     * caja abierta de quien anula, vinculada al cobro original. La caja donde
     * se cobró (si ya está cerrada) no se modifica. Si quien anula no tiene
     * caja abierta, se rechaza.
     */
    public function registrarReversionDeCobro(
        int $idUsuarioSesion,
        string $rol,
        float $monto,
        string $concepto,
        int $idCobro,
        string $medioPago = CajaMovimiento::MEDIO_EFECTIVO
    ): array {
        if (!Caja::rolManejaCaja($rol)) {
            return ['error' => true, 'mensaje' => 'Tu rol no maneja caja.'];
        }

        $caja = $this->repositorio->buscarCualquierAbiertaDeUsuario($idUsuarioSesion);

        if ($caja === null) {
            return ['error' => true, 'mensaje' => 'No tenés una caja abierta. Abrila antes de anular el cobro.'];
        }

        if ($monto <= 0) {
            return ['error' => true, 'mensaje' => 'El monto debe ser mayor a cero.'];
        }

        if (!in_array($medioPago, CajaMovimiento::MEDIOS_VALIDOS, true)) {
            return ['error' => true, 'mensaje' => 'Medio de pago no válido.'];
        }

        $ahora = Carbon::now();

        return $this->repositorio->crearMovimiento([
            'id_caja' => $caja->id_caja,
            'id_cobro' => $idCobro,
            'id_usuario' => $idUsuarioSesion,
            'fecha' => $ahora->toDateString(),
            'fecha_hora_carga' => $ahora,
            'tipo' => CajaMovimiento::TIPO_REVERSION,
            'medio_pago' => $medioPago,
            'concepto' => $concepto,
            'monto' => round($monto, 2),
        ])->toArray();
    }

    public function obtenerMovimiento(int $idMovimiento): ?array
    {
        return $this->repositorio->buscarMovimientoPorId($idMovimiento)?->toArray();
    }

    /**
     * Solo se puede modificar un movimiento de una caja abierta. Guarda el
     * monto anterior, quién lo cambió y cuándo (auditoría).
     */
    public function modificarMovimiento(int $idMovimiento, string $concepto, float $monto, int $idUsuarioSesion): ?array
    {
        $movimiento = $this->repositorio->buscarMovimientoPorId($idMovimiento);

        if ($movimiento === null) {
            return null;
        }

        if ($monto <= 0) {
            return ['error' => true, 'mensaje' => 'El monto debe ser mayor a cero.'];
        }

        if ($movimiento->id_caja !== null) {
            $caja = $this->repositorio->buscarPorId((int) $movimiento->id_caja);

            if ($caja !== null && !$caja->estaAbierta()) {
                return ['error' => true, 'mensaje' => 'No se puede modificar un movimiento de una caja cerrada.'];
            }
        }

        $movimiento->monto_anterior = $movimiento->monto;
        $movimiento->concepto = $concepto;
        $movimiento->monto = round($monto, 2);
        $movimiento->id_usuario_modificacion = $idUsuarioSesion;
        $movimiento->fecha_hora_modificacion = Carbon::now();

        return $this->repositorio->guardarMovimiento($movimiento)->toArray();
    }
}
