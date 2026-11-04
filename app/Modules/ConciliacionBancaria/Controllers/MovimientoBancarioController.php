<?php

namespace App\Modules\ConciliacionBancaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ConciliacionBancaria\Models\MovimientoBancario;
use App\Modules\ConciliacionBancaria\Services\BankStatementImportService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class MovimientoBancarioController extends Controller
{
    private BankStatementImportService $importService;

    public function __construct(BankStatementImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * GET /api/movimientos-bancarios
     * Lista movimientos bancarios con filtros por fecha, CUIT, importe, tipo y estado.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MovimientoBancario::query();

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_operacion', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_operacion', '<=', $request->input('fecha_hasta'));
        }
        if ($request->filled('cuit')) {
            $cuitLimpio = preg_replace('/\D/', '', $request->input('cuit'));
            $query->where('cuit_extraido', 'like', "%{$cuitLimpio}%");
        }
        if ($request->filled('monto_min')) {
            $query->where('monto', '>=', (float)$request->input('monto_min'));
        }
        if ($request->filled('monto_max')) {
            $query->where('monto', '<=', (float)$request->input('monto_max'));
        }
        if ($request->filled('signo')) {
            $query->where('signo_monto', $request->input('signo'));
        }
        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->input('tipo_movimiento'));
        }
        if ($request->filled('estado_conciliacion')) {
            $query->where('estado_conciliacion', $request->input('estado_conciliacion'));
        }
        if ($request->filled('comprobante')) {
            $query->where('nro_comprobante_extraido', 'like', '%' . $request->input('comprobante') . '%');
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('descripcion_original', 'like', "%{$q}%")
                    ->orWhere('descripcion_normalizada', 'like', "%{$q}%");
            });
        }

        $perPage = (int)$request->input('per_page', 25);
        $movimientos = $query->orderBy('fecha_operacion', 'desc')
            ->orderBy('id_movimiento_bancario', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $movimientos->items(),
            'total' => $movimientos->total(),
            'current_page' => $movimientos->currentPage(),
            'per_page' => $movimientos->perPage(),
            'last_page' => $movimientos->lastPage(),
        ]);
    }

    /**
     * GET /api/movimientos-bancarios/{id}
     * Detalle inmutable de un movimiento bancario específico.
     */
    public function show(int $id): JsonResponse
    {
        $movimiento = MovimientoBancario::with('archivoImportado')->find($id);
        if (!$movimiento) {
            return response()->json([
                'error' => true,
                'codigo' => 404,
                'mensaje' => "Movimiento bancario #{$id} no encontrado."
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $movimiento
        ]);
    }

    /**
     * POST /api/extractos/importar
     * Único punto de entrada permitido para cargar movimientos bancarios.
     */
    public function importar(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:20480'], // max 20MB
        ]);

        $archivo = $request->file('archivo');
        $idUsuario = UsuarioActual::id() ?? 1;

        try {
            $res = $this->importService->importar($archivo, (int)$idUsuario);
            return response()->json($res, 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'codigo' => 422,
                'mensaje' => $e->getMessage()
            ], 422);
        }
    }
}
