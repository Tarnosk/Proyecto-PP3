<?php
declare(strict_types=1);

namespace App\Modules\Stock\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\Ubicacion;
use App\Support\UsuarioActual;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UbicacionController extends Controller
{
    /**
     * S14 - Listar todas las ubicaciones físicas de almacenamiento.
     */
    public function index(): JsonResponse
    {
        $ubicaciones = Ubicacion::withCount('productos')
            ->where('estado', 'activo')
            ->orderBy('descripcion', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $ubicaciones,
        ]);
    }

    /**
     * S14 - Registrar una nueva ubicación física.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:100|unique:ubicacion,descripcion',
        ], [
            'descripcion.required' => 'La descripción de la ubicación es obligatoria.',
            'descripcion.unique' => "La ubicación ':input' ya se encuentra registrada.",
            'descripcion.max' => 'La descripción no puede superar los 100 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $ubicacion = Ubicacion::create([
            'descripcion' => trim($request->input('descripcion')),
            'estado' => 'activo',
            'fecha_creacion' => Carbon::now()->toDateString(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ubicación física creada exitosamente.',
            'data' => $ubicacion,
        ], 201);
    }

    /**
     * S14 - Asociar o modificar la ubicación asignada a un producto.
     */
    public function asignarProducto(Request $request, int $id): JsonResponse
    {
        $producto = Producto::find($id);
        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_ubicacion' => 'required|integer|exists:ubicacion,id_ubicacion',
        ], [
            'id_ubicacion.required' => 'Debe seleccionar una ubicación.',
            'id_ubicacion.exists' => 'La ubicación seleccionada no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $idUbicacion = (int) $request->input('id_ubicacion');
        $idUsuario = UsuarioActual::id($request);
        $hoy = Carbon::now()->toDateString();

        // Eliminar asignación previa si existía y asignar la nueva
        DB::table('producto_ubicacion')->where('id_producto', $producto->id_producto)->delete();

        DB::table('producto_ubicacion')->insert([
            'id_producto' => $producto->id_producto,
            'id_ubicacion' => $idUbicacion,
            'fecha_asignacion' => $hoy,
            'id_usuario' => $idUsuario,
        ]);

        $ubicacion = Ubicacion::find($idUbicacion);

        return response()->json([
            'status' => 'success',
            'message' => "Ubicación '{$ubicacion->descripcion}' asignada exitosamente al producto.",
            'data' => [
                'id_producto' => $producto->id_producto,
                'id_ubicacion' => $idUbicacion,
                'ubicacion_nombre' => $ubicacion->descripcion,
                'fecha_asignacion' => $hoy,
                'id_usuario' => $idUsuario,
            ],
        ]);
    }

    /**
     * S14 - Quitar asignación de ubicación a un producto.
     */
    public function desasignarProducto(int $id): JsonResponse
    {
        DB::table('producto_ubicacion')->where('id_producto', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ubicación desasignada exitosamente.',
        ]);
    }
}
