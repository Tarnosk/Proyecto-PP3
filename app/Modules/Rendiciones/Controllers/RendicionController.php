<?php

namespace App\Modules\Rendiciones\Controllers;

use App\Modules\Rendiciones\Repositories\RendicionRepository;
use App\Modules\Rendiciones\Services\RendicionService;
use App\Modules\Shared\Http\Controllers\Controller;
use App\Modules\Shared\Http\Response;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;
use App\Modules\Shared\Auth\G2Identity;
use App\Modules\Shared\Services\UsuarioService;
use Illuminate\Support\Facades\DB;

/**
 * RendicionController
 *
 * Capa HTTP del módulo de Rendiciones.
 * Valida la entrada con $request->validate(), delega a RendicionService
 * y responde en JSON usando return Response::json() / return Response::error().
 *
 * Endpoints (ver 02-arquitectura-tecnica.md):
 *   GET    /api/v1/rendiciones
 *   GET    /api/v1/rendiciones/{id}
 *   POST   /api/v1/rendiciones
 *   POST   /api/v1/rendiciones/{id}/cobros
 *   POST   /api/v1/rendiciones/{id}/devoluciones
 *   POST   /api/v1/rendiciones/{id}/diferencias
 *   PATCH  /api/v1/rendiciones/{id}/cerrar
 *   PATCH  /api/v1/rendiciones/{id}/revisar
 */
class RendicionController extends Controller
{
    public function __construct(private RendicionService $service)
    {
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rendiciones
    // H.U.: Consultar historial de rendiciones
    // -------------------------------------------------------------------------

    /**
     * Lista rendiciones con filtros opcionales: fecha, id_repartidor, estado.
     */
    public function index(Request $request)
    {
        try {
            $filtros = $request->only(['fecha', 'id_repartidor', 'estado']);
            if (G2Identity::role() !== 'admin') {
                $filtros['id_repartidor'] = G2Identity::id();
            }
            $resultado = $this->service->listar($filtros);
            return Response::json($resultado, 200, 'Rendiciones obtenidas exitosamente.');
        } catch (Throwable $e) {
            return Response::error('Error al listar rendiciones: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rendiciones/{id}
    // H.U.: Consultar historial de rendiciones (detalle)
    // -------------------------------------------------------------------------

    /**
     * Retorna el detalle completo de una rendición con sus cobros asociados.
     */
    public function show(int $id)
    {
        try {
            $resultado = $this->service->obtenerDetalle($id);
            return Response::json($resultado, 200);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (Throwable $e) {
            return Response::error('Error al obtener la rendición: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/rendiciones
    // H.U.: Registrar rendición de reparto
    // -------------------------------------------------------------------------

    /**
     * Registra una nueva rendición de reparto.
     * Recibe: id_repartidor, id_entrega (opcional), cobros[], remitos[], observaciones.
     */
    public function store(Request $request)
    {
        $input = $request->all();
        if (empty($input['id_entrega']) && !empty($input['id_reparto'])) {
            $input['id_entrega'] = (int) $input['id_reparto'];
        }
        if (!empty($input['remitos']) && is_array($input['remitos'])) {
            $remitosNorm = [];
            foreach ($input['remitos'] as $r) {
                if (is_numeric($r)) {
                    $remitosNorm[] = ['id_remito' => (int) $r];
                } elseif (is_array($r)) {
                    $remitosNorm[] = $r;
                }
            }
            $input['remitos'] = $remitosNorm;
        }
        if (G2Identity::role() !== 'admin') {
            $input['id_repartidor'] = G2Identity::id();
        }
        $request->merge($input);

        $datos = $request->validate([
            'id_repartidor'          => ['required', 'integer'],
            'id_entrega'             => ['nullable', 'integer'],
            'fecha'                  => ['nullable', 'date'],
            'observaciones'          => ['nullable', 'string'],
            'cobros'                 => ['sometimes', 'array'],
            'cobros.*.id_cliente'    => ['required', 'integer', 'min:1'],
            'cobros.*.id_factura'    => ['required', 'integer', 'min:1'],
            'cobros.*.monto'         => ['required_with:cobros', 'numeric', 'gt:0'],
            'cobros.*.medio_pago'    => ['required_with:cobros', 'string', 'in:efectivo,transferencia,cheque'],
            'remitos'                => ['sometimes', 'array'],
            'remitos.*.id_remito'    => ['required_with:remitos', 'integer'],
        ]);

        abort_unless((UsuarioService::obtenerUsuario((int) $datos['id_repartidor'])['rol'] ?? null) === 'repartidor', 422, 'Seleccioná un repartidor activo.');
        if (!empty($datos['id_entrega'])) {
            $owner = DB::table('entrega')->where('id_entrega', $datos['id_entrega'])->value('id_repartidor');
            abort_unless((int) $owner === (int) $datos['id_repartidor'], 422, 'La entrega no pertenece al repartidor seleccionado.');
        }
        foreach ($datos['remitos'] ?? [] as $remito) {
            $query = DB::table('remito as r')->join('entrega as e', 'r.id_entrega', '=', 'e.id_entrega')
                ->where('r.id_remito', $remito['id_remito'])->where('e.id_repartidor', $datos['id_repartidor']);
            if (!empty($datos['id_entrega'])) {
                $query->where('e.id_entrega', $datos['id_entrega']);
            }
            abort_unless($query->exists(), 422, 'El remito no pertenece al reparto seleccionado.');
        }
        try {
            $resultado = $this->service->registrarRendicion($datos);
            return Response::json($resultado, 201, 'Rendición registrada exitosamente.');
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return Response::error('Error interno al registrar la rendición: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/rendiciones/{id}/cobros
    // H.U.: Registrar cobros dentro de una rendición
    // -------------------------------------------------------------------------

    /**
     * Agrega un cobro a una rendición existente.
     */
    public function agregarCobro(Request $request, int $id)
    {
        $datos = $request->validate([
            'id_cliente' => ['required', 'integer'],
            'id_factura' => ['required', 'integer'],
            'monto'      => ['required', 'numeric', 'gt:0'],
            'medio_pago' => ['required', 'string', 'in:efectivo,transferencia,cheque'],
        ]);

        try {
            $resultado = $this->service->agregarCobro($id, $datos);
            return Response::json($resultado, 201, 'Cobro registrado y asociado a la rendición.');
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return Response::error('Error interno al registrar el cobro: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/rendiciones/{id}/devoluciones
    // H.U.: Registrar devoluciones dentro de una rendición
    // -------------------------------------------------------------------------

    /**
     * Registra una devolución dentro de una rendición.
     */
    public function registrarDevolucion(Request $request, int $id)
    {
        $datos = $request->validate([
            'id_pedido'    => ['required', 'integer'],
            'motivo'       => ['required', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string'],
            'cliente'      => ['nullable', 'string'],
        ]);

        try {
            $resultado = $this->service->registrarDevolucion($id, $datos);
            return Response::json($resultado, 201, 'Devolución registrada y asociada a la rendición.');
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return Response::error('Error interno al registrar la devolución: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/rendiciones/{id}/diferencias
    // H.U.: Registrar diferencias en una rendición
    // -------------------------------------------------------------------------

    /**
     * Calcula y registra una diferencia entre lo esperado y lo rendido.
     */
    public function registrarDiferencia(Request $request, int $id)
    {
        $datos = $request->validate([
            'total_esperado' => ['required', 'numeric', 'min:0'],
            'motivo'         => ['nullable', 'string', 'max:500'],
            'observaciones'  => ['nullable', 'string'],
        ]);

        try {
            $resultado = $this->service->registrarDiferencia($id, $datos);
            return Response::json($resultado, 201, 'Diferencia calculada y registrada exitosamente.');
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return Response::error('Error interno al registrar la diferencia: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/rendiciones/{id}/cerrar
    // H.U.: Registrar rendición de reparto (repartidor cierra su jornada)
    // -------------------------------------------------------------------------

    /**
     * El repartidor cierra su jornada: marca la rendición como lista para revisión.
     * No cambia el estado (permanece 'pendiente'), pero registra la observación de cierre.
     */
    public function cerrar(Request $request, int $id)
    {
        $datos = $request->validate([
            'observaciones' => ['nullable', 'string'],
        ]);

        try {
            // "Cerrar" desde el lado del repartidor equivale a confirmar que los datos están cargados.
            // El estado formal (aprobada/rechazada) lo cambia solo el admin via /revisar.
            $rendicion = $this->service->obtenerDetalle($id);
            return Response::json(
                array_merge($rendicion, ['observaciones_cierre' => $datos['observaciones'] ?? null]),
                200,
                'Rendición cerrada por el repartidor. Pendiente de revisión administrativa.'
            );
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (Throwable $e) {
            return Response::error('Error al cerrar la rendición: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/rendiciones/{id}/revisar
    // H.U.: Aprobar o rechazar rendiciones
    // -------------------------------------------------------------------------

    /**
     * El admin/gestora contable revisa y valida la rendición.
     * accion: 'aprobar' | 'rechazar'
     * motivo_rechazo: obligatorio si accion es 'rechazar'.
     */
    public function revisar(Request $request, int $id)
    {
        abort_unless(G2Identity::role() === 'admin', 403);
        $request->merge(['id_usuario_validador' => G2Identity::id()]);
        $datos = $request->validate([
            'accion'              => ['required', 'string', 'in:aprobar,rechazar'],
            'id_usuario_validador' => ['required', 'integer'],
            'motivo_rechazo'      => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $resultado = $this->service->revisarRendicion($id, $datos);
            $mensaje   = $datos['accion'] === 'aprobar'
                ? 'Rendición aprobada exitosamente.'
                : 'Rendición rechazada. Se registró el motivo de rechazo.';

            return Response::json($resultado, 200, $mensaje);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return Response::error('Error interno al revisar la rendición: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Endpoint directo para agregar cobro sin ID en URL (toma id_rendicion del body).
     */
    public function agregarCobroDirecto(Request $request)
    {
        $idRendicion = (int) ($request->input('id_rendicion') ?? 0);
        $request->validate(['id_rendicion' => ['required', 'integer', 'min:1']]);
        return $this->agregarCobro($request, $idRendicion);
    }

    /**
     * Endpoint directo para registrar devolución sin ID en URL.
     */
    public function registrarDevolucionDirecta(Request $request)
    {
        $idRendicion = (int) ($request->input('id_rendicion') ?? 0);
        $request->validate(['id_rendicion' => ['required', 'integer', 'min:1']]);
        return $this->registrarDevolucion($request, $idRendicion);
    }

    /**
     * Endpoint directo para registrar diferencia sin ID en URL.
     */
    public function registrarDiferenciaDirecta(Request $request)
    {
        $idRendicion = (int) ($request->input('id_rendicion') ?? 0);
        $request->validate(['id_rendicion' => ['required', 'integer', 'min:1']]);
        return $this->registrarDiferencia($request, $idRendicion);
    }

    /**
     * Endpoint directo para validar rendición (POST /api/v1/rendiciones/validar).
     */
    public function validarDirecta(Request $request)
    {
        $idRendicion = (int) ($request->input('id_rendicion') ?? 0);
        $request->validate(['id_rendicion' => ['required', 'integer', 'min:1']]);
        $accion = $request->input('accion');
        if ($accion === 'aprobada') $accion = 'aprobar';
        if ($accion === 'rechazada') $accion = 'rechazar';
        $request->merge([
            'accion' => $accion,
            'id_usuario_validador' => G2Identity::id(),
            'motivo_rechazo' => $request->input('motivo_rechazo') ?? $request->input('motivo')
        ]);
        return $this->revisar($request, $idRendicion);
    }

    /**
     * GET /api/v1/rendiciones/preparar/{idEntrega}
     * Prepara los datos estructurados de una entrega para el formulario de nueva rendición.
     */
    public function preparar(int|string $idEntrega)
    {
        try {
            $id = (int) $idEntrega;
            $datos = $this->service->obtenerDatosParaRendir($id);
            return Response::json($datos, 200, "Datos para rendición preparados con éxito.");
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (Throwable $e) {
            return Response::error("Error al preparar rendición: " . $e->getMessage(), 500);
        }
    }
}
