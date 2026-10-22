<?php

namespace App\Modules\Caja\Controllers;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Models\CajaMovimiento;
use App\Modules\Caja\Services\CajaService;
use App\Modules\Shared\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CajaWebController extends Controller
{
    // PROVISORIO: usuario y rol fijos hasta que G1 termine el login.
    // 3 = matias (repartidor). Para probar como Diego: 1 y Caja::ROL_ADMIN.
    private const ID_USUARIO_PROVISORIO = 3;
    private const ROL_PROVISORIO = Caja::ROL_REPARTIDOR;

    public function __construct(private CajaService $service)
    {
    }

    /**
     * Pantalla principal: muestra la caja ABIERTA del usuario (sea de hoy o de
     * días anteriores). Si no tiene ninguna abierta, muestra el formulario de
     * apertura. Así se puede abrir otra caja el mismo día si la anterior ya
     * se cerró, y no se ofrece abrir una si hay una abierta pendiente.
     */
    public function diaActual()
    {
        $caja = $this->service->obtenerCajaAbiertaDeUsuario(self::ID_USUARIO_PROVISORIO);

        if ($caja === null) {
            // Si la última caja se cerró sola a las 72 h, se avisa que falta cargar el conteo.
            return view('caja.abrir', [
                'cajaPendienteConteo' => $this->service->obtenerCajaPendienteDeConteo(self::ID_USUARIO_PROVISORIO),
            ]);
        }

        return $this->vistaCaja($caja, $this->service->obtenerMontoActual($caja['id_caja']));
    }

    /**
     * Muestra una caja puntual por id (usado, por ejemplo, para ir a cerrar
     * una caja de un dia anterior que quedo sin cerrar - CAJ-01).
     */
    public function mostrarCaja(int $id)
    {
        $caja = $this->service->obtenerCaja($id);

        if ($caja === null) {
            abort(404, 'Caja no encontrada');
        }

        $montoActual = $caja['estado'] === Caja::ESTADO_ABIERTA ? $this->service->obtenerMontoActual($id) : null;

        return $this->vistaCaja($caja, $montoActual);
    }

    private function vistaCaja(array $caja, ?float $montoActual)
    {
        return view('caja.dia', [
            'caja' => $caja,
            'montoActual' => $montoActual,
            // Solo los tipos de movimiento que el rol puede cargar.
            'tiposPermitidos' => CajaMovimiento::tiposPermitidosPara(self::ROL_PROVISORIO),
            // Solo las cajas de repartidor se cierran solas a las 72 h.
            'cierreAutomatico' => self::ROL_PROVISORIO === Caja::ROL_REPARTIDOR,
        ]);
    }

    public function abrir(Request $request)
    {
        $datos = $request->validate([
            'monto_inicial' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $resultado = $this->service->abrirCaja(
            self::ID_USUARIO_PROVISORIO,
            self::ROL_PROVISORIO,
            (float) $datos['monto_inicial'],
            $datos['observaciones'] ?? null
        );

        if ($resultado['error'] ?? false) {
            return back()
                ->with('error', $resultado['mensaje'])
                ->with('id_caja_pendiente', $resultado['id_caja_pendiente'] ?? null);
        }

        $montoFormateado = number_format((float) $resultado['monto_inicial'], 2, ',', '.');

        return redirect()
            ->route('caja.dia')
            ->with('mensaje', "Caja abierta correctamente con monto inicial \${$montoFormateado}.");
    }

    public function registrarMovimiento(Request $request, int $id)
    {
        $datos = $request->validate([
            'tipo' => ['required', 'string'],
            'concepto' => ['required', 'string', 'max:200'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'medio_pago' => ['nullable', 'string', 'in:' . implode(',', CajaMovimiento::MEDIOS_VALIDOS)],
        ]);

        $resultado = $this->service->registrarMovimiento(
            $id,
            self::ID_USUARIO_PROVISORIO,
            self::ROL_PROVISORIO,
            $datos['tipo'],
            $datos['concepto'],
            (float) $datos['monto'],
            null,
            $datos['medio_pago'] ?? CajaMovimiento::MEDIO_EFECTIVO
        );

        if ($resultado === null) {
            if ($request->wantsJson()) {
                return response()->json(['error' => true, 'mensaje' => 'Caja no encontrada.'], 404);
            }
            abort(404, 'Caja no encontrada');
        }

        if ($resultado['error'] ?? false) {
            if ($request->wantsJson()) {
                return response()->json(['error' => true, 'mensaje' => $resultado['mensaje']], 409);
            }
            return back()->with('error', $resultado['mensaje']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'movimiento' => $resultado,
                'monto_actual' => $this->service->obtenerMontoActual($id),
            ]);
        }

        return redirect()->route('caja.dia')->with('mensaje', 'Movimiento registrado correctamente.');
    }

    public function cerrar(Request $request, int $id)
    {
        $datos = $request->validate([
            'monto_final' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $resultado = $this->service->cerrarCaja(
            $id,
            self::ID_USUARIO_PROVISORIO,
            (float) $datos['monto_final'],
            $datos['observaciones'] ?? null,
            self::ROL_PROVISORIO
        );

        if ($resultado === null) {
            abort(404, 'Caja no encontrada');
        }

        if ($resultado['error'] ?? false) {
            return back()->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->route('caja.cierres.show', $id)
            ->with('mensaje', 'Caja cerrada correctamente.');
    }

    /**
     * Carga el monto contado de una caja que se cerró sola (solo admin).
     */
    public function completarConteo(Request $request, int $id)
    {
        $datos = $request->validate([
            'monto_final' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $resultado = $this->service->completarConteoCierreAutomatico(
            $id,
            self::ID_USUARIO_PROVISORIO,
            (float) $datos['monto_final'],
            $datos['observaciones'] ?? null,
            self::ROL_PROVISORIO
        );

        if ($resultado === null) {
            abort(404, 'Caja no encontrada');
        }

        if ($resultado['error'] ?? false) {
            return back()->with('error', $resultado['mensaje']);
        }

        return redirect()
            ->route('caja.cierres.show', $id)
            ->with('mensaje', 'Conteo cargado correctamente.');
    }

    public function cierresIndex(Request $request)
    {
        $idUsuario = $request->query('id_usuario');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $cierres = $this->service->obtenerHistorialCierres(
            $idUsuario !== null ? (int) $idUsuario : null,
            $desde ?: null,
            $hasta ?: null
        );

        return view('caja.cierres', [
            'cierres' => $cierres,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    public function cierresShow(int $id)
    {
        $cierre = $this->service->obtenerDetalleCierre($id);

        if ($cierre === null) {
            abort(404, 'Cierre no encontrado');
        }

        return view('caja.cierre-detalle', [
            'cierre' => $cierre,
            // Solo el admin carga el conteo de una caja cerrada sola.
            'puedeCargarConteo' => self::ROL_PROVISORIO === Caja::ROL_ADMIN,
        ]);
    }
}
