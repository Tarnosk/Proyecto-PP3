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
        $uActiva = $producto->unidades->firstWhere('es_base', 1);

        $unidades = $producto->unidades->map(function ($u) {
            return [
                'id_unidad' => $u->id_unidad,
                'id_producto' => $u->id_producto,
                'nombre_unidad' => $u->nombre_unidad,
                'equivalencia_base' => (float) $u->equivalencia_base,
                'es_base' => (int) ($u->es_base ?? 0),
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
                'unidad_activa' => $uActiva ? [
                    'id_unidad' => $uActiva->id_unidad,
                    'nombre_unidad' => $uActiva->nombre_unidad,
                    'equivalencia_base' => (float) $uActiva->equivalencia_base,
                ] : null,
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
            'es_base' => 'nullable|boolean',
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

        $equivalenciaNueva = (float) $request->input('equivalencia_base');
        // Validar que no haya otra unidad con misma equivalencia (coherencia mínima)
        $existeEq = UnidadMedida::where('id_producto', $producto->id_producto)
            ->where('equivalencia_base', $equivalenciaNueva)
            ->exists();
        if ($existeEq) {
            return response()->json([
                'status' => 'error',
                'message' => "Ya existe otra unidad con equivalencia {$equivalenciaNueva} para este producto.",
            ], 400);
        }

        $esBase = $request->boolean('es_base');
        if ($esBase) {
            UnidadMedida::where('id_producto', $producto->id_producto)->update(['es_base' => 0]);
        }

        $unidad = UnidadMedida::create([
            'id_producto' => $producto->id_producto,
            'nombre_unidad' => $nombre,
            'equivalencia_base' => (float) $request->input('equivalencia_base'),
            'es_base' => $esBase ? 1 : 0,
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
                'es_base' => (int) $unidad->es_base,
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
            $eqNueva = (float) $request->input('equivalencia_base');
            $conflictoEq = UnidadMedida::where('id_producto', $unidad->id_producto)
                ->where('equivalencia_base', $eqNueva)
                ->where('id_unidad', '!=', $unidad->id_unidad)
                ->exists();
            if ($conflictoEq) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Ya existe otra unidad con equivalencia {$eqNueva} para este producto.",
                ], 400);
            }
            $unidad->equivalencia_base = $eqNueva;
        }

        if ($request->has('descripcion')) {
            $unidad->descripcion = $request->input('descripcion');
        }

        if ($request->has('es_base')) {
            $esBase = $request->boolean('es_base');
            if ($esBase) {
                UnidadMedida::where('id_producto', $unidad->id_producto)->update(['es_base' => 0]);
                $unidad->es_base = 1;
            } else {
                $unidad->es_base = 0;
            }
        }

        $unidad->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Unidad de medida actualizada exitosamente.',
            'data' => $unidad,
        ]);
    }

    /**
     * S08 - Elegir una unidad de medida / equivalencia como la unidad visual activa del producto.
     * Si $idUnidad es 0, restablece a unidades individuales por defecto.
     * POST /api/productos/{id}/unidades/{idUnidad}/seleccionar
     */
    public function seleccionar(Request $request, int $id, int $idUnidad): JsonResponse
    {
        $producto = Producto::find($id);
        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        if ($idUnidad === 0) {
            UnidadMedida::where('id_producto', $id)->update(['es_base' => 0]);
            return response()->json([
                'status' => 'success',
                'message' => 'El producto ahora se visualizará en unidades individuales por defecto (u.).',
                'data' => [
                    'id_producto' => $producto->id_producto,
                    'unidad_medida' => 'u.',
                    'unidad_activa' => 'u.',
                    'equivalencia_unidad' => 1.0,
                    'id_unidad_activa' => null,
                ],
            ]);
        }

        $unidad = UnidadMedida::where('id_producto', $id)->where('id_unidad', $idUnidad)->first();
        if (!$unidad) {
            return response()->json([
                'status' => 'error',
                'message' => 'La unidad de medida seleccionada no pertenece a este producto.',
            ], 404);
        }

        UnidadMedida::where('id_producto', $id)->update(['es_base' => 0]);
        $unidad->es_base = 1;
        $unidad->save();

        return response()->json([
            'status' => 'success',
            'message' => "La unidad '{$unidad->nombre_unidad}' (Eq: {$unidad->equivalencia_base} u.) fue establecida como la unidad de medida del producto.",
            'data' => [
                'id_producto' => $producto->id_producto,
                'unidad_medida' => $unidad->nombre_unidad,
                'unidad_activa' => $unidad->nombre_unidad,
                'equivalencia_unidad' => (float) $unidad->equivalencia_base,
                'id_unidad_activa' => $unidad->id_unidad,
            ],
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
