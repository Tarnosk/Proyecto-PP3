<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\Lote;
use App\Modules\Stock\Services\StockService;
use App\Support\UsuarioActual;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoteController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * S09 - Listar lotes registrados con filtros opcionales.
     */
    public function index(Request $request): JsonResponse
    {
        $idProducto = $request->filled('id_producto') ? (int) $request->input('id_producto') : null;
        $estado = $request->input('estado');
        if ($estado === 'todos') {
            $estado = null;
        }
        $search = $request->input('search');
        $porVencer = filter_var($request->input('por_vencer', false), FILTER_VALIDATE_BOOLEAN);
        $dias = (int) $request->input('dias', 30);

        $query = Lote::with(['producto', 'movimiento'])
            ->orderBy('fecha_vencimiento', 'asc')
            ->orderBy('id_lote', 'desc');

        if ($idProducto) {
            $query->where('id_producto', $idProducto);
        }

        if ($porVencer) {
            $query->porVencer($dias);
        } elseif ($estado) {
            // El estado no se persiste: cada filtro replica la precedencia de
            // Lote::getEstadoAttribute() (consumido > vencido > vigente).
            match (strtolower((string) $estado)) {
                'vigente'   => $query->vigentes(),
                'vencido'   => $query->vencidos(),
                'consumido' => $query->where('cantidad_actual', '<=', 0),
                default     => null,
            };
        }

        if ($search) {
            $query->search($search);
        }

        $lotes = $query->get()->map(function (Lote $l) {
            return $this->formatearLote($l);
        });

        return response()->json([
            'status' => 'success',
            'data' => $lotes,
        ]);
    }

    /**
     * S09 - Registrar un nuevo lote por producto y asociarlo opcionalmente a un ingreso de stock.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|integer|exists:PRODUCTO,id_producto',
            'nro_lote' => 'required|string|max:100',
            'fecha_vencimiento' => 'required|date',
            'cantidad' => 'required|integer|min:1',
            'id_unidad' => 'nullable|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'asociar_ingreso' => 'nullable|boolean',
            'motivo' => 'nullable|string|max:255',
        ], [
            'id_producto.required' => 'Debe seleccionar un producto.',
            'id_producto.exists' => 'El producto seleccionado no existe.',
            'nro_lote.required' => 'El número de lote es obligatorio.',
            'nro_lote.max' => 'El número de lote no puede superar los 100 caracteres.',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida.',
            'cantidad.required' => 'Debe indicar la cantidad del lote.',
            'cantidad.min' => 'La cantidad debe ser mayor a cero.',
            'id_unidad.exists' => 'La unidad de medida seleccionada no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        /** @var Producto $producto */
        $producto = Producto::find((int) $request->input('id_producto'));
        if (!$producto || $producto->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto seleccionado no se encuentra activo o no existe.',
            ], 400);
        }

        $idUsuario = UsuarioActual::id($request);
        $asociarIngreso = filter_var($request->input('asociar_ingreso', true), FILTER_VALIDATE_BOOLEAN);
        $cantidad = (int) $request->input('cantidad');
        $idUnidad = $request->filled('id_unidad') ? (int) $request->input('id_unidad') : null;
        $nroLote = trim((string) $request->input('nro_lote'));
        $fechaVencimiento = Carbon::parse($request->input('fecha_vencimiento'))->toDateString();
        $motivo = $request->input('motivo') ?? "Ingreso mercadería lote {$nroLote}";

        // LOTE tiene UNIQUE(id_producto, nro_lote): se chequea antes de insertar
        // para devolver un 400 legible en vez de un 500 con SQL crudo.
        if (Lote::where('id_producto', $producto->id_producto)->where('nro_lote', $nroLote)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => "El lote '{$nroLote}' ya está registrado para el producto {$producto->codigo}.",
            ], 400);
        }

        try {
            $lote = DB::transaction(function () use (
                $producto,
                $nroLote,
                $fechaVencimiento,
                $cantidad,
                $idUnidad,
                $asociarIngreso,
                $motivo,
                $idUsuario
            ) {
                // LOTE no tiene id_unidad: se guarda en unidades base, igual que
                // PRODUCTO.stock, para que la suma de lotes sea comparable al stock.
                $cantidadBase = StockService::cantidadEnUnidadBase($cantidad, $idUnidad);

                if ($asociarIngreso) {
                    $movimiento = $this->stockService->registrarMovimiento(
                        $producto,
                        'ingreso',
                        $cantidad,
                        $motivo,
                        $idUsuario,
                        $idUnidad
                    );

                    $lote = Lote::create([
                        'id_producto' => $producto->id_producto,
                        'nro_lote' => $nroLote,
                        'cantidad_inicial' => $cantidadBase,
                        'cantidad_actual' => $cantidadBase,
                        'fecha_vencimiento' => $fechaVencimiento,
                    ]);

                    // La FK vive en MOVIMIENTO_STOCK.id_lote, no en LOTE.
                    $movimiento->update(['id_lote' => $lote->id_lote]);

                    return $lote;
                }

                return Lote::create([
                    'id_producto' => $producto->id_producto,
                    'nro_lote' => $nroLote,
                    'cantidad_inicial' => $cantidadBase,
                    'cantidad_actual' => $cantidadBase,
                    'fecha_vencimiento' => $fechaVencimiento,
                ]);
            });

            $lote->load(['producto', 'movimiento']);
            $producto->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Lote registrado exitosamente.',
                'data' => [
                    'lote' => $this->formatearLote($lote),
                    'stock_disponible' => $producto->stock_disponible,
                    'estado_alerta' => $producto->estado_alerta,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * S09 - Consultar detalle de un lote específico.
     */
    public function show(int $id): JsonResponse
    {
        $lote = Lote::with(['producto', 'movimiento'])->find($id);
        if (!$lote) {
            return response()->json([
                'status' => 'error',
                'message' => 'El lote solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatearLote($lote),
        ]);
    }

    /**
     * S09 - Consultar historial de lotes de un producto específico.
     */
    public function lotesPorProducto(int $idProducto): JsonResponse
    {
        $producto = Producto::find($idProducto);
        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto solicitado no existe.',
            ], 404);
        }

        $lotes = Lote::with(['movimiento'])
            ->porProducto($idProducto)
            ->orderBy('fecha_vencimiento', 'asc')
            ->orderBy('id_lote', 'desc')
            ->get()
            ->map(function (Lote $l) use ($producto) {
                $item = $this->formatearLote($l);
                $item['codigo_producto'] = $producto->codigo;
                $item['descripcion_producto'] = $producto->descripcion;
                return $item;
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'stock_disponible' => $producto->stock_disponible,
                'total_lotes' => $lotes->count(),
                'lotes' => $lotes,
            ],
        ]);
    }

    /**
     * S10 - Endpoint especializado para lotes próximos a vencer (dashboard y alertas).
     */
    public function porVencer(Request $request): JsonResponse
    {
        $dias = (int) $request->input('dias', 30);
        if ($dias < 1) {
            $dias = 30;
        }

        $lotes = Lote::with(['producto'])
            ->porVencer($dias)
            ->orderBy('fecha_vencimiento', 'asc')
            ->get()
            ->map(function (Lote $l) {
                return $this->formatearLote($l);
            });

        return response()->json([
            'status' => 'success',
            'data' => $lotes,
        ]);
    }

    /**
     * Normalizar estructura de respuesta de un lote.
     */
    private function formatearLote(Lote $l): array
    {
        $dias = $l->dias_para_vencer;
        $urgente = ($dias !== null && $dias <= 15 && $dias >= 0);

        return [
            'id_lote' => $l->id_lote,
            'nro_lote' => $l->nro_lote,
            'id_producto' => $l->id_producto,
            'codigo_producto' => $l->producto ? $l->producto->codigo : null,
            'descripcion_producto' => $l->producto ? $l->producto->descripcion : null,
            'cantidad' => $l->cantidad_actual,
            'cantidad_inicial' => $l->cantidad_inicial,
            'id_unidad' => $l->movimiento?->id_unidad,
            'nombre_unidad' => $l->movimiento?->unidad?->nombre_unidad,
            'fecha_vencimiento' => $l->fecha_vencimiento ? $l->fecha_vencimiento->format('Y-m-d') : null,
            'dias_para_vencer' => $dias,
            // El estado lo define Lote::getEstadoAttribute(), que prioriza
            // 'consumido' (cantidad_actual <= 0) sobre la fecha. Forzar
            // 'vencido' acá hacía que el filtro estado=consumido devolviera
            // filas etiquetadas como vencidas.
            'estado' => (string) $l->estado,
            'urgente' => $urgente,
            'id_movimiento' => $l->movimiento?->id_movimiento,
            'fecha_ingreso' => $l->movimiento && $l->movimiento->fecha ? $l->movimiento->fecha->format('Y-m-d') : null,
        ];
    }
}