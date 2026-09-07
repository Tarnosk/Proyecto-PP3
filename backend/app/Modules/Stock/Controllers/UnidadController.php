<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\UnidadMedida;
use App\Modules\Stock\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnidadController extends Controller
{
    /**
     * S08 - Listar todas las unidades de medida de un producto.
     */
    public function index(int $id): JsonResponse
    {
        $producto = Producto::with('unidades')->find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        // Unidad base implícita (equivalencia 1) si no está registrada
        $base = $producto->unidades->first(fn ($u) => (float) $u->equivalencia_base === 1.0);

        $unidades = $producto->unidades->map(function ($u) {
            return [
                'id_unidad' => $u->id_unidad,
                'id_producto' => $u->id_producto,
                'nombre_unidad' => $u->nombre_unidad,
                'equivalencia_base' => (float) $u->equivalencia_base,
                'descripcion' => $u->descripcion,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_producto' => $producto->id_producto,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'tiene_unidad_base' => $base ? true : false,
                'unidades' => $unidades,
            ],
        ]);
    }

    /**
     * S08 - Registrar una nueva unidad de medida y su equivalencia para un producto.
     * Valida que no exista una unidad con el mismo nombre y que la equivalencia sea
     * consistente (mayor a cero).
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_unidad' => 'required|string|max:50',
            'equivalencia_base' => 'required|numeric|gt:0',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre_unidad.required' => 'Debe indicar el nombre de la unidad (ej: caja, pallet).',
            'equivalencia_base.required' => 'Debe indicar la equivalencia respecto a la unidad base.',
            'equivalencia_base.gt' => 'La equivalencia debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $nombre = trim($request->input('nombre_unidad'));

        $existe = UnidadMedida::where('id_producto', $producto->id_producto)
            ->where('nombre_unidad', $nombre)
            ->exists();

        if ($existe) {
            return response()->json([
                'status' => 'error',
                'message' => "Ya existe la unidad '{$nombre}' para este producto.",
            ], 400);
        }

        $unidad = UnidadMedida::create([
            'id_producto' => $producto->id_producto,
            'nombre_unidad' => $nombre,
            'equivalencia_base' => (float) $request->input('equivalencia_base'),
            'descripcion' => $request->input('descripcion'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Unidad de medida registrada exitosamente.',
            'data' => [
                'id_unidad' => $unidad->id_unidad,
                'id_producto' => $unidad->id_producto,
                'nombre_unidad' => $unidad->nombre_unidad,
                'equivalencia_base' => (float) $unidad->equivalencia_base,
                'descripcion' => $unidad->descripcion,
            ],
        ], 201);
    }

    /**
     * S08 - Modificar la equivalencia o descripción de una unidad existente.
     * Recalcula y valida la consistencia (no se permite colisionar con otra unidad).
     * Ruta: PUT /api/productos/{id}/unidades/{idUnidad}
     */
    public function update(Request $request, int $id, int $idUnidad): JsonResponse
    {
        $unidad = UnidadMedida::where('id_unidad', $idUnidad)
            ->where('id_producto', $id)
            ->first();

        if (!$unidad) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unidad de medida no encontrada para este producto.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_unidad' => 'sometimes|required|string|max:50',
            'equivalencia_base' => 'sometimes|required|numeric|gt:0',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre_unidad.required' => 'Debe indicar el nombre de la unidad.',
            'equivalencia_base.gt' => 'La equivalencia debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if ($request->has('nombre_unidad')) {
            $nombre = trim($request->input('nombre_unidad'));
            $existe = UnidadMedida::where('id_producto', $unidad->id_producto)
                ->where('nombre_unidad', $nombre)
                ->where('id_unidad', '!=', $unidad->id_unidad)
                ->exists();
            if ($existe) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Ya existe la unidad '{$nombre}' para este producto.",
                ], 400);
            }
            $unidad->nombre_unidad = $nombre;
        }

        if ($request->has('equivalencia_base')) {
            $unidad->equivalencia_base = (float) $request->input('equivalencia_base');
        }

        if ($request->has('descripcion')) {
            $unidad->descripcion = $request->input('descripcion');
        }

        $unidad->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Unidad de medida actualizada exitosamente.',
            'data' => $unidad,
        ]);
    }

    /**
     * S08 - Eliminar una unidad de medida (impide si está referenciada en movimientos).
     * Ruta: DELETE /api/productos/{id}/unidades/{idUnidad}
     */
    public function destroy(int $id, int $idUnidad): JsonResponse
    {
        $unidad = UnidadMedida::where('id_unidad', $idUnidad)
            ->where('id_producto', $id)
            ->withCount('movimientos')
            ->first();

        if (!$unidad) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unidad de medida no encontrada para este producto.',
            ], 404);
        }

        if ($unidad->movimientos_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "No se puede eliminar la unidad '{$unidad->nombre_unidad}' porque está referenciada en {$unidad->movimientos_count} movimiento(s) de stock.",
            ], 400);
        }

        $unidad->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Unidad de medida eliminada exitosamente.',
        ]);
    }

    /**
     * S08 - Convertir cantidades entre dos unidades de un mismo producto.
     * GET /api/unidades/convertir?id_unidad_origen=X&id_unidad_destino=Y&cantidad=N
     */
    public function convertir(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_unidad_origen' => 'required|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'id_unidad_destino' => 'required|integer|exists:UNIDAD_MEDIDA,id_unidad',
            'cantidad' => 'required|numeric|gt:0',
        ], [
            'id_unidad_origen.required' => 'Debe indicar la unidad de origen.',
            'id_unidad_destino.required' => 'Debe indicar la unidad de destino.',
            'cantidad.required' => 'Debe indicar la cantidad a convertir.',
            'cantidad.gt' => 'La cantidad debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $origen = UnidadMedida::find((int) $request->input('id_unidad_origen'));
        $destino = UnidadMedida::find((int) $request->input('id_unidad_destino'));

        if ($origen->id_producto !== $destino->id_producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Las unidades deben pertenecer al mismo producto para convertir.',
            ], 400);
        }

        $cantidad = (float) $request->input('cantidad');
        $resultado = StockService::convertir(
            $cantidad,
            (float) $origen->equivalencia_base,
            (float) $destino->equivalencia_base
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_producto' => $origen->id_producto,
                'unidad_origen' => $origen->nombre_unidad,
                'unidad_destino' => $destino->nombre_unidad,
                'cantidad_origen' => $cantidad,
                'cantidad_destino' => round($resultado, 4),
            ],
        ]);
    }
}
