<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Proveedores\Services\PlazoEntregaService;
use App\Support\UsuarioActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * PV07 - Registrar plazos de entrega y consultar su historial.
 *
 * El plazo también se puede dar en el alta y la modificación de un proveedor
 * (POST/PUT /api/proveedores); este controller agrega el endpoint dedicado para
 * cambiarlo suelto y para leer el historial de cambios.
 */
class PlazoEntregaController extends Controller
{
    public function __construct(private PlazoEntregaService $service)
    {
    }

    /**
     * Cambiar el plazo de entrega de un proveedor, dejando el cambio registrado.
     */
    public function store(Request $request, int $proveedor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plazo_entrega_dias' => 'required|integer|min:0',
        ], [
            'plazo_entrega_dias.required' => 'Debe indicar el plazo de entrega en días.',
            'plazo_entrega_dias.integer' => 'El plazo de entrega debe ser un número entero.',
            'plazo_entrega_dias.min' => 'El plazo de entrega no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $modelo = Proveedor::find($proveedor);
        if (!$modelo) {
            return response()->json(['status' => 'error', 'message' => 'El proveedor no existe.'], 404);
        }

        try {
            $resultado = $this->service->cambiarPlazo(
                $modelo,
                (int) $validator->validated()['plazo_entrega_dias'],
                UsuarioActual::id($request)
            );

            return response()->json([
                'status' => 'success',
                'message' => $resultado['mensaje'],
                'data' => $resultado,
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el plazo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Historial de modificaciones del plazo de entrega (PV07).
     */
    public function index(Request $request, int $proveedor): JsonResponse
    {
        $limite = (int) $request->input('limite', 0);

        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->service->historial($proveedor, $limite > 0 ? $limite : 0),
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar el historial de plazos: ' . $e->getMessage(),
            ], 500);
        }
    }
}