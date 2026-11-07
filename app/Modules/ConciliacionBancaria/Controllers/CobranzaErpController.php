<?php

namespace App\Modules\ConciliacionBancaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ConciliacionBancaria\Repositories\Cobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CobranzaErpController extends Controller
{
    /**
     * GET /api/cobranzas-erp
     * Lista cobranzas registradas en el ERP para el panel split-screen de conciliación.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cobro::query();

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->input('fecha_hasta'));
        }
        if ($request->filled('monto_min')) {
            $query->where('monto_total', '>=', (float)$request->input('monto_min'));
        }
        if ($request->filled('monto_max')) {
            $query->where('monto_total', '<=', (float)$request->input('monto_max'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        } else {
            // Criterio de Aceptación 4.2: Filtro predeterminado estado = aprobado / confirmado
            $query->where('estado', 'confirmado');
        }
        if ($request->filled('comprobante')) {
            $query->where('comprobante_nro', 'like', '%' . $request->input('comprobante') . '%');
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->input('cliente_id'));
        }

        $perPage = (int)$request->input('per_page', 25);
        $cobranzas = $query->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $cobranzas->items(),
            'total' => $cobranzas->total(),
            'current_page' => $cobranzas->currentPage(),
            'per_page' => $cobranzas->perPage(),
            'last_page' => $cobranzas->lastPage(),
        ]);
    }

    /**
     * GET /api/cobranzas-erp/{id}
     */
    public function show(int $id): JsonResponse
    {
        $cobro = Cobro::find($id);
        if (!$cobro) {
            return response()->json([
                'error' => true,
                'codigo' => 404,
                'mensaje' => "Cobranza ERP #{$id} no encontrada."
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cobro
        ]);
    }
}
