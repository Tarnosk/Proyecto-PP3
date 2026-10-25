<?php
declare(strict_types=1);

namespace App\Modules\Productos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CategoriaController extends Controller
{
    /**
     * Listar todas las categorías con conteo de productos asociados (P02)
     */
    public function index(): JsonResponse
    {
        $categorias = Categoria::withCount('productos as total_productos')
            ->orderBy('nombre', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categorias,
        ]);
    }

    /**
     * Crear nueva categoría (P02)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:CATEGORIA,nombre',
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => "Ya existe una categoría con el nombre ':input'.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $now = Carbon::now()->toDateString();
        $categoria = Categoria::create([
            'nombre' => trim($request->input('nombre')),
            'fecha_creacion' => $now,
            'fecha_modificacion' => $now,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría creada exitosamente.',
            'data' => $categoria,
        ], 201);
    }

    /**
     * Obtener una categoría por ID
     */
    public function show(int $id): JsonResponse
    {
        $categoria = Categoria::withCount('productos as total_productos')->find($id);

        if (!$categoria) {
            return response()->json([
                'status' => 'error',
                'message' => 'Categoría no encontrada.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $categoria,
        ]);
    }

    /**
     * Modificar categoría existente (P02)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'status' => 'error',
                'message' => "La categoría con ID {$id} no existe.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => "required|string|max:100|unique:CATEGORIA,nombre,{$id},id_categoria",
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => "Ya existe otra categoría con el nombre ':input'.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $categoria->update([
            'nombre' => trim($request->input('nombre')),
            'fecha_modificacion' => Carbon::now()->toDateString(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría actualizada exitosamente.',
            'data' => $categoria,
        ]);
    }

    /**
     * Eliminar categoría (P02 - Impide eliminación si está siendo utilizada por productos)
     */
    public function destroy(int $id): JsonResponse
    {
        $categoria = Categoria::withCount('productos')->find($id);

        if (!$categoria) {
            return response()->json([
                'status' => 'error',
                'message' => "La categoría con ID {$id} no existe.",
            ], 404);
        }

        if ($categoria->productos_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "No se puede eliminar la categoría '{$categoria->nombre}' porque está siendo utilizada por {$categoria->productos_count} producto(s).",
            ], 400);
        }

        $categoria->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría eliminada exitosamente.',
        ]);
    }
}
