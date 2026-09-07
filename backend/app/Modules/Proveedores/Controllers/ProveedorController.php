<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Módulo Proveedores.
 *
 * PV01 - Registrar proveedores.
 * PV02 - Consultar información de proveedores.
 * PV03 - Editar información de proveedores.
 * PV04 - Desactivar proveedores.
 * PV05 - Asociar productos a proveedores.
 */
class ProveedorController extends Controller
{
    /**
     * Formato de salida de un proveedor.
     */
    private function formato(Proveedor $p): array
    {
        return [
            'id_proveedor' => $p->id_proveedor,
            'razon_social' => $p->razon_social,
            'cuit' => $p->cuit,
            'telefono' => $p->telefono,
            'correo' => $p->correo,
            'estado' => $p->estado,
            'fecha_alta' => $p->fecha_alta ? $p->fecha_alta->format('Y-m-d') : null,
            'fecha_modificacion' => $p->fecha_modificacion ? $p->fecha_modificacion->format('Y-m-d') : null,
            'fecha_desactivacion' => $p->fecha_desactivacion ? $p->fecha_desactivacion->format('Y-m-d') : null,
            'plazo_entrega_dias' => $p->plazo_entrega_dias,
            'id_usuario_carga' => $p->id_usuario_carga,
            'id_usuario_modificacion' => $p->id_usuario_modificacion,
        ];
    }

    /**
     * PV02 - Listar y buscar proveedores por razón social o CUIT.
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $estado = $request->input('estado');
        if ($estado === 'todos') {
            $estado = null;
        }

        $query = Proveedor::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'LIKE', "%{$search}%")
                  ->orWhere('cuit', 'LIKE', "%{$search}%");
            });
        }

        if ($estado && in_array($estado, ['activo', 'inactivo'], true)) {
            $query->where('estado', $estado);
        }

        $proveedores = $query->orderBy('razon_social', 'asc')
            ->get()
            ->map(fn (Proveedor $p) => $this->formato($p));

        return response()->json([
            'status' => 'success',
            'data' => $proveedores,
        ]);
    }

    /**
     * PV02 - Consultar información de un proveedor por ID (con productos asociados).
     */
    public function show(int $id): JsonResponse
    {
        $proveedor = Proveedor::with('productos.producto')->find($id);

        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => "El proveedor con ID {$id} no existe.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge($this->formato($proveedor), [
                'productos_asociados' => $proveedor->productos
                    ->filter(fn (ProductoProveedor $pp) => $pp->activo)
                    ->values()
                    ->map(function (ProductoProveedor $pp) {
                        return [
                            'id_producto_proveedor' => $pp->id_producto_proveedor,
                            'id_producto' => $pp->id_producto,
                            'codigo' => $pp->producto?->codigo,
                            'descripcion' => $pp->producto?->descripcion,
                            'es_proveedor_principal' => (bool) $pp->es_proveedor_principal,
                            'precio_acordado' => $pp->precio_acordado !== null ? (float) $pp->precio_acordado : null,
                            'fecha_asociacion' => $pp->fecha_asociacion ? $pp->fecha_asociacion->format('Y-m-d') : null,
                        ];
                    }),
            ]),
        ]);
    }

    /**
     * PV01 - Registrar un nuevo proveedor.
     * Valida duplicados por CUIT y registra fecha de alta y usuario de carga.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'razon_social' => 'required|string|max:150',
            'cuit' => 'required|string|max:20|unique:PROVEEDOR,cuit',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:100',
            'plazo_entrega_dias' => 'nullable|integer|min:0',
            'id_usuario' => 'nullable|integer',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'cuit.required' => 'El CUIT es obligatorio.',
            'cuit.unique' => "El CUIT ':input' ya se encuentra registrado.",
            'correo.email' => 'El correo electrónico no es válido.',
            'plazo_entrega_dias.min' => 'El plazo de entrega no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $now = Carbon::now()->toDateString();
            $idUsuario = (int) $request->input('id_usuario', 1);

            $proveedor = Proveedor::create([
                'razon_social' => trim($request->input('razon_social')),
                'cuit' => trim($request->input('cuit')),
                'telefono' => $request->input('telefono') ? trim($request->input('telefono')) : null,
                'correo' => $request->input('correo') ? trim($request->input('correo')) : null,
                'estado' => 'activo',
                'fecha_alta' => $now,
                'fecha_modificacion' => $now,
                'plazo_entrega_dias' => $request->filled('plazo_entrega_dias') ? (int) $request->input('plazo_entrega_dias') : null,
                'id_usuario_carga' => $idUsuario,
                'id_usuario_modificacion' => $idUsuario,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Proveedor registrado exitosamente.',
                'data' => $this->formato($proveedor),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el proveedor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PV03 - Editar información de un proveedor.
     * Registra fecha de modificación y usuario; valida que no haya duplicados de CUIT.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => "El proveedor con ID {$id} no existe.",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'razon_social' => 'required|string|max:150',
            'cuit' => "required|string|max:20|unique:PROVEEDOR,cuit,{$id},id_proveedor",
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:100',
            'plazo_entrega_dias' => 'nullable|integer|min:0',
            'id_usuario' => 'nullable|integer',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'cuit.required' => 'El CUIT es obligatorio.',
            'cuit.unique' => "El CUIT ':input' ya está siendo utilizado por otro proveedor.",
            'correo.email' => 'El correo electrónico no es válido.',
            'plazo_entrega_dias.min' => 'El plazo de entrega no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $now = Carbon::now()->toDateString();
            $idUsuario = (int) $request->input('id_usuario', 1);

            $proveedor->update([
                'razon_social' => trim($request->input('razon_social')),
                'cuit' => trim($request->input('cuit')),
                'telefono' => $request->input('telefono') ? trim($request->input('telefono')) : null,
                'correo' => $request->input('correo') ? trim($request->input('correo')) : null,
                'fecha_modificacion' => $now,
                'id_usuario_modificacion' => $idUsuario,
            ]);

            if ($request->filled('plazo_entrega_dias')) {
                $proveedor->update(['plazo_entrega_dias' => (int) $request->input('plazo_entrega_dias')]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Proveedor modificado exitosamente.',
                'data' => $this->formato($proveedor->fresh()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el proveedor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PV04 - Desactivar proveedor (borrado lógico).
     * Conserva el historial y registra la fecha de desactivación.
     */
    public function desactivar(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => "El proveedor con ID {$id} no existe.",
            ], 404);
        }

        if ($proveedor->estado === 'inactivo') {
            return response()->json([
                'status' => 'error',
                'message' => 'El proveedor ya se encuentra inactivo.',
            ], 400);
        }

        $now = Carbon::now()->toDateString();
        $idUsuario = (int) $request->input('id_usuario', 1);

        $proveedor->update([
            'estado' => 'inactivo',
            'fecha_desactivacion' => $now,
            'fecha_modificacion' => $now,
            'id_usuario_modificacion' => $idUsuario,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Proveedor desactivado exitosamente.',
            'data' => $this->formato($proveedor),
        ]);
    }

    /**
     * Reactivar proveedor.
     */
    public function activar(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => "El proveedor con ID {$id} no existe.",
            ], 404);
        }

        $now = Carbon::now()->toDateString();
        $idUsuario = (int) $request->input('id_usuario', 1);

        $proveedor->update([
            'estado' => 'activo',
            'fecha_desactivacion' => null,
            'fecha_modificacion' => $now,
            'id_usuario_modificacion' => $idUsuario,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Proveedor reactivado exitosamente.',
            'data' => $this->formato($proveedor),
        ]);
    }

    /**
     * PV05 - Asociar productos a un proveedor.
     * Permite asociar uno o varios productos; opcionalmente define el proveedor principal.
     */
    public function asociarProductos(Request $request, int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => "El proveedor con ID {$id} no existe.",
            ], 404);
        }

        if ($proveedor->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pueden asociar productos a un proveedor inactivo.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'id_productos' => 'required|array|min:1',
            'id_productos.*' => 'integer|exists:PRODUCTO,id_producto',
            'precio_acordado' => 'nullable|numeric|min:0',
            'id_usuario' => 'nullable|integer',
        ], [
            'id_productos.required' => 'Debe indicar al menos un producto.',
            'id_productos.*.exists' => 'Uno de los productos seleccionados no existe.',
            'precio_acordado.min' => 'El precio acordado no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $now = Carbon::now()->toDateString();
            $idUsuario = (int) $request->input('id_usuario', 1);
            $precioAcordado = $request->has('precio_acordado') ? (float) $request->input('precio_acordado') : null;

            $idsProductos = array_map('intval', $request->input('id_productos'));
            $asociados = [];
            $yaAsociados = [];

            DB::transaction(function () use ($id, $idsProductos, $now, $idUsuario, $precioAcordado, &$asociados, &$yaAsociados) {
                foreach ($idsProductos as $idProducto) {
                    $existe = ProductoProveedor::where('id_producto', $idProducto)
                        ->where('id_proveedor', $id)
                        ->first();

                    if ($existe) {
                        if ($existe->activo) {
                            $yaAsociados[] = $idProducto;
                            continue;
                        }
                        // Reasociar (reactivar una asociación previa desactivada)
                        $existe->update([
                            'activo' => true,
                            'fecha_desasociacion' => null,
                            'fecha_asociacion' => $now,
                            'id_usuario' => $idUsuario,
                        ]);
                        $asociados[] = $existe;
                        continue;
                    }

                    $nuevo = ProductoProveedor::create([
                        'id_producto' => $idProducto,
                        'id_proveedor' => $id,
                        'es_proveedor_principal' => false,
                        'precio_acordado' => $precioAcordado,
                        'activo' => true,
                        'fecha_asociacion' => $now,
                        'id_usuario' => $idUsuario,
                    ]);
                    $asociados[] = $nuevo;
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Productos asociados exitosamente al proveedor.',
                'data' => [
                    'asociados' => count($asociados),
                    'ya_asociados' => $yaAsociados,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al asociar productos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PV05 - Definir el proveedor principal de un producto.
     * Al fijar uno como principal, los demás del producto dejan de serlo.
     */
    public function definirPrincipal(Request $request, int $id): JsonResponse
    {
        $relacion = ProductoProveedor::find($id);

        if (!$relacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'La asociación producto-proveedor no existe.',
            ], 404);
        }

        $now = Carbon::now()->toDateString();
        $idUsuario = (int) $request->input('id_usuario', 1);

        DB::transaction(function () use ($relacion, $now, $idUsuario) {
            // Quitar principal a las demás asociaciones del producto
            ProductoProveedor::where('id_producto', $relacion->id_producto)
                ->where('activo', true)
                ->update(['es_proveedor_principal' => false]);

            $relacion->update([
                'es_proveedor_principal' => true,
                'fecha_asociacion' => $now,
                'id_usuario' => $idUsuario,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Proveedor principal definido correctamente.',
            'data' => $relacion,
        ]);
    }

    /**
     * PV05 - Desasociar un producto de un proveedor (baja lógica de la asociación).
     */
    public function desasociarProducto(Request $request, int $id): JsonResponse
    {
        $relacion = ProductoProveedor::where('id_producto_proveedor', $id)->first();

        if (!$relacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'La asociación producto-proveedor no existe.',
            ], 404);
        }

        $now = Carbon::now()->toDateString();
        $idUsuario = (int) $request->input('id_usuario', 1);

        $relacion->update([
            'activo' => false,
            'es_proveedor_principal' => false,
            'fecha_desasociacion' => $now,
            'id_usuario' => $idUsuario,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Producto desasociado del proveedor.',
            'data' => $relacion,
        ]);
    }
}
