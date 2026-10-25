<?php
declare(strict_types=1);

namespace App\Modules\Productos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Marca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MarcaController extends Controller
{
    /**
     * Listar todas las marcas con conteo de productos asociados (P03)
     */
    public function index(): JsonResponse
    {
        $marcas = Marca::withCount('productos as total_productos')
            ->orderBy('nombre', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $marcas,
        ]);
    }

    /**
     * Crear nueva marca (P03)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:MARCA,nombre',
        ], [
            'nombre.required' => 'El nombre de la marca es obligatorio.',
            'nombre.unique' => "Ya existe una marca con el nombre ':input'.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $now = Carbon::now()->toDateString();
        $marca = Marca::create([
            'nombre' => trim($request->input('nombre')),
            'fecha_creacion' => $now,
            'fecha_modificacion' => $now,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Marca creada exitosamente.',
            'data' => $marca,
        ], 201);
    }

    /**
     * Obtener una marca por ID
     */
    public function show(int $id): JsonResponse
    {
        $marca = Marca::withCount('productos as total_productos')->find($id);

        if (!$marca) {
            return response()->json([
                'status' => 'error',
                'message' => 'Marca no encontrada.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $marca,
        ]);
    }

    /**
     * Modificar marca existente (P03)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $marca = Marca::find($id);

        if (!$marca) {
            return response()->json([
                'status' => 'error',
                'message' => "La marca con ID {$id} no existe.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => "required|string|max:100|unique:MARCA,nombre,{$id},id_marca",
        ], [
            'nombre.required' => 'El nombre de la marca es obligatorio.',
            'nombre.unique' => "Ya existe otra marca con el nombre ':input'.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $marca->update([
            'nombre' => trim($request->input('nombre')),
            'fecha_modificacion' => Carbon::now()->toDateString(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Marca actualizada exitosamente.',
            'data' => $marca,
        ]);
    }

    /**
     * Eliminar marca (P03 - Impide eliminación si está siendo utilizada por productos)
     */
    public function destroy(int $id): JsonResponse
    {
        $marca = Marca::withCount('productos')->find($id);

        if (!$marca) {
            return response()->json([
                'status' => 'error',
                'message' => "La marca con ID {$id} no existe.",
            ], 404);
        }

        if ($marca->productos_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "No se puede eliminar la marca '{$marca->nombre}' porque está siendo utilizada por {$marca->productos_count} producto(s).",
            ], 400);
        }

        $marca->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Marca eliminada exitosamente.',
        ]);
    }
}
