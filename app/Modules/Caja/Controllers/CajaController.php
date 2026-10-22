<?php

namespace App\Modules\Caja\Controllers;

use App\Modules\Caja\Models\Caja;
use App\Modules\Caja\Models\CajaMovimiento;
use App\Modules\Caja\Services\CajaService;
use App\Modules\Shared\Http\Controllers\Controller;
use App\Modules\Shared\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller único para el módulo Caja.
 * Toda la lógica de negocio vive en CajaService — este Controller solo valida
 * el request y traduce el resultado del Service a HTTP.
 *
 * Todas las rutas de Caja están protegidas con auth:sanctum (ver routes/api.php),
 * y $request->user() devuelve el App\Modules\Shared\Models\Usuario autenticado.
 * PROVISORIO: Sanctum se usa solo para probar roles hasta que G1 termine el login.
 */
class CajaController extends Controller
{
    public function __construct(private CajaService $service)
    {
    }

    private function resolverUsuarioYRol(Request $request): array
    {
        if ($request->user()) {
            return [(int) $request->user()->id_usuario, $request->user()->rol ?? Caja::ROL_ADMIN];
        }

        $idUsuario = (int) ($request->input('id_usuario') ?? $request->query('id_usuario') ?? 1);
        $rol = $request->input('rol') ?? $request->query('rol');

        if (!$rol) {
            $usuario = \App\Modules\Shared\Services\UsuarioService::obtenerUsuario($idUsuario);
            $rol = $usuario['rol'] ?? (($idUsuario === 2 || $idUsuario === 3) ? Caja::ROL_ADMIN : Caja::ROL_REPARTIDOR);
        }

        return [$idUsuario, $rol];
    }

    private function esAdmin(Request $request): bool
    {
        [, $rol] = $this->resolverUsuarioYRol($request);
        return $rol === Caja::ROL_ADMIN;
    }

    /**
     * POST /caja/apertura — cada usuario abre su propia caja (el vendedor no maneja caja)
     */
    public function abrir(Request $request)
    {
        [$idUsuario, $rol] = $this->resolverUsuarioYRol($request);

        if (!Caja::rolManejaCaja($rol)) {
            Response::error('Tu rol no maneja caja.', 403);
            return;
        }

        $datos = $request->validate([
            'monto_inicial' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $resultado = $this->service->abrirCaja(
            $idUsuario,
            $rol,
            (float) $datos['monto_inicial'],
            $datos['observaciones'] ?? null
        );

        if ($resultado['error'] ?? false) {
            Response::json($resultado, 409); // incluye id_caja_pendiente y vencida
            return;
        }

        Response::json($resultado, 201);
    }

    /**
     * GET /caja?estado=abierta&id_usuario=3
     */
    public function listar(Request $request)
    {
        $datos = $request->validate([
            'estado' => ['nullable', Rule::in([Caja::ESTADO_ABIERTA, Caja::ESTADO_CERRADA])],
            'id_usuario' => ['nullable', 'integer'],
        ]);

        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        // Quien no es admin solo ve sus propias cajas
        $idUsuario = ($rol === Caja::ROL_ADMIN)
            ? ($datos['id_usuario'] ?? null)
            : $idUsuarioSesion;

        Response::json($this->service->listarCajas($datos['estado'] ?? null, $idUsuario));
    }

    /**
     * GET /caja/{fecha} — todas las cajas de ese día (solo admin)
     */
    public function porFecha(Request $request, string $fecha)
    {
        if (!$this->esAdmin($request)) {
            Response::error('Solo un administrador puede ver las cajas de otros usuarios.', 403);
            return;
        }

        Response::json($this->service->obtenerCajasDeFecha($fecha));
    }

    /**
     * GET /caja/{fecha}/{id_usuario} — cajas de un usuario en esa fecha
     * (lista: puede haber más de una el mismo día). Admin o el propio usuario.
     */
    public function porUsuarioYFecha(Request $request, string $fecha, int $id_usuario)
    {
        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        if ($rol !== Caja::ROL_ADMIN && $idUsuarioSesion !== $id_usuario) {
            Response::error('No podés ver la caja de otro usuario.', 403);
            return;
        }

        Response::json($this->service->obtenerCajasDeUsuarioYFecha($id_usuario, $fecha));
    }

    /**
     * PATCH /caja/{id}/cerrar — la cierra su dueño, sin aprobación.
     * El admin también puede cerrar la caja de un repartidor.
     */
    public function cerrar(Request $request, int $id)
    {
        $datos = $request->validate([
            'monto_final' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        $resultado = $this->service->cerrarCaja(
            $id,
            $idUsuarioSesion,
            (float) $datos['monto_final'],
            $datos['observaciones'] ?? null,
            $rol
        );

        if ($resultado === null) {
            Response::error('Caja no encontrada.', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 409);
            return;
        }

        Response::json($resultado);
    }

    /**
     * PATCH /caja/{id}/completar-conteo — carga el monto contado de una caja
     * que se cerró sola a las 72 h (solo admin, si el dueño es repartidor).
     */
    public function completarConteo(Request $request, int $id)
    {
        $datos = $request->validate([
            'monto_final' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        $resultado = $this->service->completarConteoCierreAutomatico(
            $id,
            $idUsuarioSesion,
            (float) $datos['monto_final'],
            $datos['observaciones'] ?? null,
            $rol
        );

        if ($resultado === null) {
            Response::error('Caja no encontrada.', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 409);
            return;
        }

        Response::json($resultado);
    }

    /**
     * GET /caja/cierres?id_usuario=&desde=YYYY-MM-DD&hasta=YYYY-MM-DD
     * Quien no es admin solo ve sus propios cierres.
     */
    public function historialCierres(Request $request)
    {
        $datos = $request->validate([
            'id_usuario' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        $idUsuario = ($rol === Caja::ROL_ADMIN)
            ? ($datos['id_usuario'] ?? null)
            : $idUsuarioSesion;

        Response::json($this->service->obtenerHistorialCierres(
            $idUsuario !== null ? (int) $idUsuario : null,
            $datos['desde'] ?? null,
            $datos['hasta'] ?? null
        ));
    }

    /**
     * GET /caja/cierres/{id} — admin o dueño de la caja
     */
    public function detalleCierre(Request $request, int $id)
    {
        $cierre = $this->service->obtenerDetalleCierre($id);
        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        // 404 también cuando no es suyo, para no revelar que el id existe
        if ($cierre === null
            || ($rol !== Caja::ROL_ADMIN && (int) $cierre['id_usuario'] !== $idUsuarioSesion)) {
            Response::error('No existe un cierre con ese id.', 404);
            return;
        }

        Response::json($cierre);
    }

    /**
     * POST /caja/movimiento
     */
    public function registrarMovimiento(Request $request)
    {
        $datos = $request->validate([
            'id_caja' => ['required', 'integer'],
            'tipo' => ['required', Rule::in(CajaMovimiento::TIPOS_VALIDOS)],
            'concepto' => ['required', 'string', 'max:200'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'medio_pago' => ['nullable', Rule::in(CajaMovimiento::MEDIOS_VALIDOS)],
        ]);

        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        $resultado = $this->service->registrarMovimiento(
            (int) $datos['id_caja'],
            $idUsuarioSesion,
            $rol,
            $datos['tipo'],
            $datos['concepto'],
            (float) $datos['monto'],
            null,
            $datos['medio_pago'] ?? CajaMovimiento::MEDIO_EFECTIVO
        );

        if ($resultado === null) {
            Response::error('Caja no encontrada.', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 409);
            return;
        }

        Response::json($resultado, 201);
    }

    /**
     * GET /caja/movimiento/{id} — admin o quien lo cargó
     */
    public function detalleMovimiento(Request $request, int $id)
    {
        $movimiento = $this->service->obtenerMovimiento($id);
        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        if ($movimiento === null
            || ($rol !== Caja::ROL_ADMIN && (int) $movimiento['id_usuario'] !== $idUsuarioSesion)) {
            Response::error('Movimiento no encontrado.', 404);
            return;
        }

        Response::json($movimiento);
    }

    /**
     * PATCH /caja/movimiento/{id} — solo admin, y solo si la caja sigue abierta.
     * Guarda el monto anterior, quién lo cambió y cuándo.
     */
    public function modificarMovimiento(Request $request, int $id)
    {
        [$idUsuarioSesion, $rol] = $this->resolverUsuarioYRol($request);

        if ($rol !== Caja::ROL_ADMIN) {
            Response::error('Solo un administrador puede modificar un movimiento.', 403);
            return;
        }

        $datos = $request->validate([
            'concepto' => ['required', 'string', 'max:200'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        $resultado = $this->service->modificarMovimiento(
            $id,
            $datos['concepto'],
            (float) $datos['monto'],
            $idUsuarioSesion
        );

        if ($resultado === null) {
            Response::error('Movimiento no encontrado.', 404);
            return;
        }

        if ($resultado['error'] ?? false) {
            Response::error($resultado['mensaje'], 409);
            return;
        }

        Response::json($resultado);
    }
}
