<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Proveedores\Services\PlazoEntregaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Support\UsuarioActual;
use App\Modules\Productos\Models\LogAuditoria;
use App\Support\AuditoriaLogger;

/**
 * Módulo Proveedores.
 *
 * PV01 - Registrar proveedores.
 * PV02 - Consultar información de proveedores.
 * PV03 - Editar información de proveedores.
 * PV04 - Desactivar proveedores.
 * PV05 - Asociar productos a proveedores.
 * PV07 - Registrar plazos de entrega (el historial lo lleva PlazoEntregaService).
 */
class ProveedorController extends Controller
{
    public function __construct(private PlazoEntregaService $plazos)
    {
    }

    /**
     * Formato de salida de un proveedor.
     */
    private function formato(Proveedor $p): array
    {
        return [
            'id_proveedor' => $p->id_proveedor,
            'razon_social' => $p->razon_social,
            'contacto' => $p->contacto,
            'cuit' => $p->cuit ?? $p->CUIT,
            'telefono' => $p->telefono,
            'correo' => $p->email,
            'email' => $p->email,
            'direccion' => $p->direccion,
            'estado' => $p->estado,
            'fecha_alta' => $p->fecha_alta ? ($p->fecha_alta instanceof \Carbon\CarbonInterface ? $p->fecha_alta->format('Y-m-d H:i:s') : (string) $p->fecha_alta) : null,
            'fecha_modificacion' => $p->fecha_modificacion ? ($p->fecha_modificacion instanceof \Carbon\CarbonInterface ? $p->fecha_modificacion->format('Y-m-d H:i:s') : (string) $p->fecha_modificacion) : null,
            'fecha_desactivacion' => $p->fecha_desactivacion ? ($p->fecha_desactivacion instanceof \Carbon\CarbonInterface ? $p->fecha_desactivacion->format('Y-m-d H:i:s') : (string) $p->fecha_desactivacion) : null,
            'plazo_entrega_dias' => $p->plazo_entrega_dias,
            'id_usuario_carga' => $p->id_usuario_carga,
            'usuario_carga_nombre' => $p->usuarioCarga ? $p->usuarioCarga->nombre : ($p->id_usuario_carga ? "Usuario #{$p->id_usuario_carga}" : null),
            'id_usuario_modificacion' => $p->id_usuario_modificacion,
            'usuario_modificacion_nombre' => $p->usuarioModificacion ? $p->usuarioModificacion->nombre : ($p->id_usuario_modificacion ? "Usuario #{$p->id_usuario_modificacion}" : null),
            'total_productos' => isset($p->total_productos) ? (int) $p->total_productos : (int) $p->productos()->where('activo', true)->count(),
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

        $query = Proveedor::with(['usuarioCarga', 'usuarioModificacion'])
            ->withCount(['productos as total_productos' => fn ($q) => $q->where('activo', true)]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'LIKE', "%{$search}%")
                  ->orWhere('cuit', 'LIKE', "%{$search}%")
                  ->orWhere('contacto', 'LIKE', "%{$search}%");
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
        $proveedor = Proveedor::with(['productos.producto', 'usuarioCarga', 'usuarioModificacion'])->find($id);

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
            'contacto' => 'nullable|string|max:150',
            'cuit' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9\-]+$/',
                'unique:PROVEEDOR,cuit',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9\+\-\s\(\)]+$/',
            ],
            'correo' => 'nullable|email|max:100',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255',
            'plazo_entrega_dias' => 'nullable|integer|min:0',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'cuit.required' => 'El CUIT es obligatorio.',
            'cuit.regex' => 'El CUIT no puede contener letras (solo números y guiones).',
            'cuit.unique' => "El CUIT ':input' ya se encuentra registrado.",
            'telefono.regex' => 'El teléfono no puede contener letras.',
            'correo.email' => 'El correo electrónico no es válido.',
            'email.email' => 'El correo electrónico no es válido.',
            'plazo_entrega_dias.min' => 'El plazo de entrega no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $now = Carbon::now()->toDateTimeString();
            $idUsuario = UsuarioActual::id($request);
            $email = $request->input('email') ?: $request->input('correo');
            $contacto = $request->input('contacto') ? trim($request->input('contacto')) : null;
            $direccion = $request->input('direccion') ? trim($request->input('direccion')) : null;

            $proveedor = Proveedor::create([
                'razon_social' => trim($request->input('razon_social')),
                'contacto' => $contacto,
                'cuit' => trim($request->input('cuit')),
                'telefono' => $request->input('telefono') ? trim($request->input('telefono')) : null,
                'email' => $email ? trim($email) : null,
                'direccion' => $direccion,
                'estado' => 'activo',
                'fecha_alta' => $now,
                'fecha_modificacion' => $now,
                'plazo_entrega_dias' => $request->filled('plazo_entrega_dias') ? (int) $request->input('plazo_entrega_dias') : null,
                'id_usuario_carga' => $idUsuario,
                'id_usuario_modificacion' => $idUsuario,
            ]);

            // PV07: deja el primer renglón del historial de plazos (anterior = null).
            $this->plazos->registrarAlta(
                $proveedor,
                $request->filled('plazo_entrega_dias') ? (int) $request->input('plazo_entrega_dias') : null,
                $idUsuario
            );

            AuditoriaLogger::registrar(
                'proveedor',
                $proveedor->id_proveedor,
                'INSERT',
                null,
                [
                    'razon_social' => $proveedor->razon_social,
                    'cuit' => $proveedor->cuit,
                    'contacto' => $proveedor->contacto,
                    'telefono' => $proveedor->telefono,
                    'email' => $proveedor->email,
                    'direccion' => $proveedor->direccion,
                    'plazo_entrega_dias' => $proveedor->plazo_entrega_dias,
                    'estado' => $proveedor->estado,
                ],
                $idUsuario
            );

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
            'contacto' => 'nullable|string|max:150',
            'cuit' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9\-]+$/',
                "unique:PROVEEDOR,cuit,{$id},id_proveedor",
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9\+\-\s\(\)]+$/',
            ],
            'correo' => 'nullable|email|max:100',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255',
            'plazo_entrega_dias' => 'nullable|integer|min:0',
        ], [
            'razon_social.required' => 'La razón social es obligatoria.',
            'cuit.required' => 'El CUIT es obligatorio.',
            'cuit.regex' => 'El CUIT no puede contener letras (solo números y guiones).',
            'cuit.unique' => "El CUIT ':input' ya está siendo utilizado por otro proveedor.",
            'telefono.regex' => 'El teléfono no puede contener letras.',
            'correo.email' => 'El correo electrónico no es válido.',
            'email.email' => 'El correo electrónico no es válido.',
            'plazo_entrega_dias.min' => 'El plazo de entrega no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $now = Carbon::now()->toDateTimeString();
            $idUsuario = UsuarioActual::id($request);

            $valoresAnteriores = [
                'razon_social' => $proveedor->razon_social,
                'cuit' => $proveedor->cuit,
                'contacto' => $proveedor->contacto,
                'telefono' => $proveedor->telefono,
                'email' => $proveedor->email,
                'direccion' => $proveedor->direccion,
                'plazo_entrega_dias' => $proveedor->plazo_entrega_dias,
            ];

            $updateData = [
                'razon_social' => trim($request->input('razon_social')),
                'cuit' => trim($request->input('cuit')),
                'fecha_modificacion' => $now,
                'id_usuario_modificacion' => $idUsuario,
            ];

            if ($request->has('contacto')) {
                $updateData['contacto'] = $request->input('contacto') ? trim($request->input('contacto')) : null;
            }
            if ($request->has('telefono')) {
                $updateData['telefono'] = $request->input('telefono') ? trim($request->input('telefono')) : null;
            }
            if ($request->has('email') || $request->has('correo')) {
                $email = $request->input('email') ?: $request->input('correo');
                $updateData['email'] = $email ? trim($email) : null;
            }
            if ($request->has('direccion')) {
                $updateData['direccion'] = $request->input('direccion') ? trim($request->input('direccion')) : null;
            }

            $proveedor->update($updateData);

            if ($request->filled('plazo_entrega_dias')) {
                // PV07: el cambio de plazo pasa por el service para quedar en el historial.
                $this->plazos->cambiarPlazo(
                    $proveedor,
                    (int) $request->input('plazo_entrega_dias'),
                    $idUsuario
                );
            }

            $proveedorActualizado = $proveedor->fresh();
            $valoresNuevos = [
                'razon_social' => $proveedorActualizado->razon_social,
                'cuit' => $proveedorActualizado->cuit,
                'contacto' => $proveedorActualizado->contacto,
                'telefono' => $proveedorActualizado->telefono,
                'email' => $proveedorActualizado->email,
                'direccion' => $proveedorActualizado->direccion,
                'plazo_entrega_dias' => $proveedorActualizado->plazo_entrega_dias,
            ];

            $huboCambios = false;
            foreach ($valoresNuevos as $k => $v) {
                if (($valoresAnteriores[$k] ?? null) != $v) {
                    $huboCambios = true;
                    break;
                }
            }

            if ($huboCambios) {
                AuditoriaLogger::registrar(
                    'proveedor',
                    $proveedor->id_proveedor,
                    'UPDATE',
                    $valoresAnteriores,
                    $valoresNuevos,
                    $idUsuario
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Proveedor modificado exitosamente.',
                'data' => $this->formato($proveedorActualizado),
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

        $now = Carbon::now()->toDateTimeString();
        $idUsuario = UsuarioActual::id($request);

        DB::transaction(function () use ($proveedor, $now, $idUsuario) {
            $proveedor->update([
                'estado' => 'inactivo',
                'fecha_desactivacion' => $now,
                'fecha_modificacion' => $now,
                'id_usuario_modificacion' => $idUsuario,
            ]);

            // PV04: Desmarcar como proveedor principal de cualquier producto para que no quede como principal tras darse de baja
            ProductoProveedor::where('id_proveedor', $proveedor->id_proveedor)
                ->where('es_proveedor_principal', true)
                ->update(['es_proveedor_principal' => false]);
        });

        AuditoriaLogger::registrar(
            'proveedor',
            $proveedor->id_proveedor,
            'UPDATE',
            ['estado' => 'activo'],
            ['estado' => 'inactivo', 'fecha_desactivacion' => $now],
            $idUsuario
        );

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

        $now = Carbon::now()->toDateTimeString();
        $idUsuario = UsuarioActual::id($request);

        $proveedor->update([
            'estado' => 'activo',
            'fecha_desactivacion' => null,
            'fecha_modificacion' => $now,
            'id_usuario_modificacion' => $idUsuario,
        ]);

        AuditoriaLogger::registrar(
            'proveedor',
            $proveedor->id_proveedor,
            'UPDATE',
            ['estado' => 'inactivo'],
            ['estado' => 'activo'],
            $idUsuario
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Proveedor reactivado exitosamente.',
            'data' => $this->formato($proveedor),
        ]);
    }

    /**
     * PV05 - Listar los productos asociados a un proveedor.
     */
    public function listarProductos(int $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => "El proveedor con ID {$id} no existe.",
            ], 404);
        }

        $asociaciones = ProductoProveedor::with(['producto.categoria', 'producto.marca'])
            ->where('id_proveedor', $id)
            ->where('activo', true)
            ->orderByDesc('es_proveedor_principal')
            ->orderBy('id_producto_proveedor')
            ->get()
            ->map(function (ProductoProveedor $pp) {
                return [
                    'id_producto_proveedor' => $pp->id_producto_proveedor,
                    'id_producto' => $pp->id_producto,
                    'codigo' => $pp->producto?->codigo,
                    'nombre' => $pp->producto?->nombre,
                    'descripcion' => $pp->producto?->descripcion,
                    'categoria' => $pp->producto?->categoria?->nombre,
                    'marca' => $pp->producto?->marca?->nombre,
                    'precio_unitario' => $pp->producto?->precio_unitario !== null ? (float) $pp->producto->precio_unitario : null,
                    'precio_acordado' => $pp->precio_acordado !== null ? (float) $pp->precio_acordado : null,
                    'es_proveedor_principal' => (bool) $pp->es_proveedor_principal,
                    'fecha_asociacion' => $pp->fecha_asociacion ? $pp->fecha_asociacion->format('Y-m-d') : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'proveedor' => [
                'id_proveedor' => $proveedor->id_proveedor,
                'razon_social' => $proveedor->razon_social,
                'cuit' => $proveedor->cuit,
                'estado' => $proveedor->estado,
            ],
            'data' => $asociaciones,
        ]);
    }

    /**
     * PV05 - Sincronizar automáticamente asociaciones de productos con proveedores.
     * Recopila información de sugerencias, órdenes de compra, compras, recepciones
     * y afinidad de catálogo/rubros para asociar los productos a sus proveedores.
     */
    public function sincronizarAsociaciones(Request $request): JsonResponse
    {
        $idUsuario = UsuarioActual::id($request);
        $hoy = Carbon::now()->toDateString();
        $nuevas = 0;
        $actualizadas = 0;

        // 1. Recopilar desde SUGERENCIA_COMPRA
        $sugerencias = DB::table('sugerencia_compra')
            ->select('id_producto', 'id_proveedor', DB::raw('AVG(precio_unitario) as precio_promedio'))
            ->groupBy('id_producto', 'id_proveedor')
            ->get();

        foreach ($sugerencias as $sug) {
            $idProd = (int) $sug->id_producto;
            $idProv = (int) $sug->id_proveedor;
            $precio = $sug->precio_promedio ? round((float) $sug->precio_promedio, 2) : null;

            $prov = Proveedor::find($idProv);
            if (!$prov || $prov->estado !== 'activo') continue;

            $prod = Producto::find($idProd);
            if (!$prod || $prod->estado !== 'activo') continue;

            $asoc = ProductoProveedor::where('id_producto', $idProd)->where('id_proveedor', $idProv)->first();
            if (!$asoc) {
                $tienePrincipal = ProductoProveedor::where('id_producto', $idProd)->where('activo', true)->where('es_proveedor_principal', true)->exists();
                ProductoProveedor::create([
                    'id_producto' => $idProd,
                    'id_proveedor' => $idProv,
                    'es_proveedor_principal' => !$tienePrincipal,
                    'precio_acordado' => $precio,
                    'activo' => true,
                    'fecha_asociacion' => $hoy,
                    'id_usuario' => $idUsuario,
                ]);
                $nuevas++;
            } else {
                $upd = [];
                if (!$asoc->activo) {
                    $upd['activo'] = true;
                    $upd['fecha_desasociacion'] = null;
                }
                if ($precio !== null && $asoc->precio_acordado === null) {
                    $upd['precio_acordado'] = $precio;
                }
                if (!empty($upd)) {
                    $asoc->update($upd);
                    $actualizadas++;
                }
            }
        }

        // 2. Recopilar desde DETALLE_ORDEN y ORDEN_COMPRA
        $ordenes = DB::table('detalle_orden')
            ->join('orden_compra', 'detalle_orden.id_orden', '=', 'orden_compra.id_orden')
            ->select('detalle_orden.id_producto', 'orden_compra.id_proveedor', DB::raw('AVG(detalle_orden.precio_estimado) as precio_promedio'))
            ->groupBy('detalle_orden.id_producto', 'orden_compra.id_proveedor')
            ->get();

        foreach ($ordenes as $ord) {
            $idProd = (int) $ord->id_producto;
            $idProv = (int) $ord->id_proveedor;
            $precio = $ord->precio_promedio ? round((float) $ord->precio_promedio, 2) : null;

            $prov = Proveedor::find($idProv);
            if (!$prov || $prov->estado !== 'activo') continue;

            $prod = Producto::find($idProd);
            if (!$prod || $prod->estado !== 'activo') continue;

            $asoc = ProductoProveedor::where('id_producto', $idProd)->where('id_proveedor', $idProv)->first();
            if (!$asoc) {
                $tienePrincipal = ProductoProveedor::where('id_producto', $idProd)->where('activo', true)->where('es_proveedor_principal', true)->exists();
                ProductoProveedor::create([
                    'id_producto' => $idProd,
                    'id_proveedor' => $idProv,
                    'es_proveedor_principal' => !$tienePrincipal,
                    'precio_acordado' => $precio,
                    'activo' => true,
                    'fecha_asociacion' => $hoy,
                    'id_usuario' => $idUsuario,
                ]);
                $nuevas++;
            } else {
                $upd = [];
                if (!$asoc->activo) {
                    $upd['activo'] = true;
                    $upd['fecha_desasociacion'] = null;
                }
                if ($precio !== null && $asoc->precio_acordado === null) {
                    $upd['precio_acordado'] = $precio;
                }
                if (!empty($upd)) {
                    $asoc->update($upd);
                    $actualizadas++;
                }
            }
        }

        // 3. Recopilar desde DETALLE_COMPRA y COMPRA
        $compras = DB::table('detalle_compra')
            ->join('compra', 'detalle_compra.id_compra', '=', 'compra.id_compra')
            ->select('detalle_compra.id_producto', 'compra.id_proveedor', DB::raw('AVG(detalle_compra.precio_unitario) as precio_promedio'))
            ->groupBy('detalle_compra.id_producto', 'compra.id_proveedor')
            ->get();

        foreach ($compras as $cmp) {
            $idProd = (int) $cmp->id_producto;
            $idProv = (int) $cmp->id_proveedor;
            $precio = $cmp->precio_promedio ? round((float) $cmp->precio_promedio, 2) : null;

            $prov = Proveedor::find($idProv);
            if (!$prov || $prov->estado !== 'activo') continue;

            $prod = Producto::find($idProd);
            if (!$prod || $prod->estado !== 'activo') continue;

            $asoc = ProductoProveedor::where('id_producto', $idProd)->where('id_proveedor', $idProv)->first();
            if (!$asoc) {
                $tienePrincipal = ProductoProveedor::where('id_producto', $idProd)->where('activo', true)->where('es_proveedor_principal', true)->exists();
                ProductoProveedor::create([
                    'id_producto' => $idProd,
                    'id_proveedor' => $idProv,
                    'es_proveedor_principal' => !$tienePrincipal,
                    'precio_acordado' => $precio,
                    'activo' => true,
                    'fecha_asociacion' => $hoy,
                    'id_usuario' => $idUsuario,
                ]);
                $nuevas++;
            } elseif ($precio !== null && $asoc->precio_acordado === null) {
                $asoc->update(['precio_acordado' => $precio]);
                $actualizadas++;
            }
        }

        $total = ProductoProveedor::where('activo', true)->count();

        return response()->json([
            'status' => 'success',
            'message' => "Sincronización completada exitosamente: {$nuevas} nuevas asociaciones y {$actualizadas} actualizadas.",
            'data' => [
                'nuevas' => $nuevas,
                'actualizadas' => $actualizadas,
                'total_asociaciones' => $total,
            ],
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

        // Compatibilidad: aceptar tanto 'id_producto' singular como 'id_productos' array
        if ($request->has('id_producto') && !$request->has('id_productos')) {
            $val = $request->input('id_producto');
            if ($val !== null && $val !== '') {
                $request->merge(['id_productos' => [$val]]);
            }
        }

        $validator = Validator::make($request->all(), [
            'id_productos' => 'required|array|min:1',
            'id_productos.*' => 'integer|exists:PRODUCTO,id_producto',
            'precio_acordado' => 'nullable|numeric|min:0',
            'es_proveedor_principal' => 'nullable|boolean',
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
            $idUsuario = UsuarioActual::id($request);
            $precioAcordado = $request->filled('precio_acordado') ? (float) $request->input('precio_acordado') : null;
            $esPrincipal = $request->boolean('es_proveedor_principal');

            $idsProductos = array_map('intval', (array) $request->input('id_productos'));
            $asociados = [];
            $yaAsociados = [];

            DB::transaction(function () use ($request, $id, $idsProductos, $now, $idUsuario, $precioAcordado, $esPrincipal, &$asociados, &$yaAsociados) {
                foreach ($idsProductos as $idProducto) {
                    $existe = ProductoProveedor::where('id_producto', $idProducto)
                        ->where('id_proveedor', $id)
                        ->first();

                    if ($esPrincipal) {
                        ProductoProveedor::where('id_producto', $idProducto)
                            ->where('activo', true)
                            ->where('id_proveedor', '!=', $id)
                            ->update(['es_proveedor_principal' => false]);
                    }

                    if ($existe) {
                        $updateData = [
                            'activo' => true,
                            'fecha_desasociacion' => null,
                            'fecha_asociacion' => $now,
                            'id_usuario' => $idUsuario,
                        ];
                        if ($request->has('precio_acordado')) {
                            $updateData['precio_acordado'] = $precioAcordado;
                        }
                        if ($request->has('es_proveedor_principal')) {
                            $updateData['es_proveedor_principal'] = $esPrincipal;
                        }
                        $precioAnteriorExiste = $existe->precio_acordado !== null ? (float) $existe->precio_acordado : null;
                        $existe->update($updateData);

                        if ($precioAcordado !== null && $precioAcordado !== $precioAnteriorExiste) {
                            app(\App\Modules\Proveedores\Services\HistorialPrecioService::class)->registrarSnapshot(
                                $existe->id_producto_proveedor,
                                (float) $precioAcordado,
                                $idUsuario
                            );
                        }

                        $asociados[] = $existe;
                        continue;
                    }

                    $nuevo = ProductoProveedor::create([
                        'id_producto' => $idProducto,
                        'id_proveedor' => $id,
                        'es_proveedor_principal' => $esPrincipal,
                        'precio_acordado' => $precioAcordado,
                        'activo' => true,
                        'fecha_asociacion' => $now,
                        'id_usuario' => $idUsuario,
                    ]);

                    if ($precioAcordado !== null && $precioAcordado >= 0) {
                        app(\App\Modules\Proveedores\Services\HistorialPrecioService::class)->registrarSnapshot(
                            $nuevo->id_producto_proveedor,
                            (float) $precioAcordado,
                            $idUsuario
                        );
                    }
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
        $relacion = ProductoProveedor::with('proveedor')->find($id);

        if (!$relacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'La asociación producto-proveedor no existe.',
            ], 404);
        }

        if ($relacion->proveedor && $relacion->proveedor->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede establecer como proveedor principal a un proveedor inactivo. (PV04)',
            ], 400);
        }

        $now = Carbon::now()->toDateString();
        $idUsuario = UsuarioActual::id($request);

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
        $idUsuario = UsuarioActual::id($request);

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

    /**
     * PV05 - Obtener detalle de una asociación producto-proveedor.
     */
    public function obtenerAsociacion(int $id): JsonResponse
    {
        $relacion = ProductoProveedor::with(['producto.categoria', 'producto.marca', 'proveedor'])->find($id);

        if (!$relacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'La asociación producto-proveedor no existe.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id_producto_proveedor' => $relacion->id_producto_proveedor,
                'id_producto' => $relacion->id_producto,
                'id_proveedor' => $relacion->id_proveedor,
                'codigo' => $relacion->producto?->codigo,
                'nombre' => $relacion->producto?->nombre,
                'descripcion' => $relacion->producto?->descripcion,
                'categoria' => $relacion->producto?->categoria?->nombre,
                'marca' => $relacion->producto?->marca?->nombre,
                'precio_unitario' => $relacion->producto?->precio_unitario !== null ? (float) $relacion->producto->precio_unitario : null,
                'precio_acordado' => $relacion->precio_acordado !== null ? (float) $relacion->precio_acordado : null,
                'es_proveedor_principal' => (bool) $relacion->es_proveedor_principal,
                'activo' => (bool) $relacion->activo,
                'fecha_asociacion' => $relacion->fecha_asociacion ? $relacion->fecha_asociacion->format('Y-m-d') : null,
                'proveedor_nombre' => $relacion->proveedor?->razon_social,
            ],
        ]);
    }

    /**
     * PV05 - Editar/actualizar los datos de una asociación producto-proveedor existente.
     */
    public function actualizarAsociacion(Request $request, int $id): JsonResponse
    {
        $relacion = ProductoProveedor::with(['producto', 'proveedor'])->find($id);

        if (!$relacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'La asociación producto-proveedor no existe.',
            ], 404);
        }

        if ($relacion->proveedor && $relacion->proveedor->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede modificar una asociación de un proveedor inactivo. (PV04)',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'precio_acordado' => 'nullable|numeric|min:0',
            'es_proveedor_principal' => 'nullable|boolean',
            'id_producto' => 'nullable|integer|exists:PRODUCTO,id_producto',
        ], [
            'precio_acordado.min' => 'El precio acordado no puede ser negativo.',
            'id_producto.exists' => 'El producto especificado no existe.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $idUsuario = UsuarioActual::id($request);
        $precioAnterior = $relacion->precio_acordado !== null ? (float) $relacion->precio_acordado : null;
        $principalAnterior = (bool) $relacion->es_proveedor_principal;
        $productoAnterior = $relacion->id_producto;

        $nuevoProductoId = $request->filled('id_producto') ? (int) $request->input('id_producto') : $relacion->id_producto;

        // Si se cambia el producto, comprobar que no esté ya asociado al mismo proveedor
        if ($nuevoProductoId !== $relacion->id_producto) {
            $otraAsoc = ProductoProveedor::where('id_proveedor', $relacion->id_proveedor)
                ->where('id_producto', $nuevoProductoId)
                ->where('id_producto_proveedor', '!=', $id)
                ->first();
            if ($otraAsoc) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El producto seleccionado ya está asociado a este proveedor.',
                ], 400);
            }
        }

        $esPrincipal = $request->has('es_proveedor_principal')
            ? $request->boolean('es_proveedor_principal')
            : (bool) $relacion->es_proveedor_principal;

        $precioAcordado = $request->has('precio_acordado')
            ? ($request->input('precio_acordado') !== null && $request->input('precio_acordado') !== '' ? (float) $request->input('precio_acordado') : null)
            : ($relacion->precio_acordado !== null ? (float) $relacion->precio_acordado : null);

        DB::transaction(function () use ($relacion, $nuevoProductoId, $esPrincipal, $precioAcordado, $idUsuario, $precioAnterior) {
            if ($esPrincipal) {
                ProductoProveedor::where('id_producto', $nuevoProductoId)
                    ->where('activo', true)
                    ->where('id_producto_proveedor', '!=', $relacion->id_producto_proveedor)
                    ->update(['es_proveedor_principal' => false]);
            }

            $relacion->update([
                'id_producto' => $nuevoProductoId,
                'precio_acordado' => $precioAcordado,
                'es_proveedor_principal' => $esPrincipal,
                'activo' => true,
                'fecha_desasociacion' => null,
                'id_usuario' => $idUsuario,
            ]);

            if ($precioAcordado !== null && $precioAcordado !== $precioAnterior) {
                app(\App\Modules\Proveedores\Services\HistorialPrecioService::class)->registrarSnapshot(
                    $relacion->id_producto_proveedor,
                    (float) $precioAcordado,
                    $idUsuario
                );
            }
        });

        $relacion->refresh()->load(['producto.categoria', 'producto.marca', 'proveedor']);

        AuditoriaLogger::registrar(
            'proveedor',
            $relacion->id_proveedor,
            'UPDATE',
            [
                'producto' => $relacion->producto?->nombre ?? "Producto #{$productoAnterior}",
                'precio_acordado' => $precioAnterior,
                'es_proveedor_principal' => $principalAnterior,
            ],
            [
                'producto' => $relacion->producto?->nombre,
                'precio_acordado' => $relacion->precio_acordado !== null ? (float) $relacion->precio_acordado : null,
                'es_proveedor_principal' => (bool) $relacion->es_proveedor_principal,
            ],
            $idUsuario
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Asociación producto-proveedor actualizada exitosamente.',
            'data' => [
                'id_producto_proveedor' => $relacion->id_producto_proveedor,
                'id_producto' => $relacion->id_producto,
                'id_proveedor' => $relacion->id_proveedor,
                'codigo' => $relacion->producto?->codigo,
                'nombre' => $relacion->producto?->nombre,
                'precio_acordado' => $relacion->precio_acordado !== null ? (float) $relacion->precio_acordado : null,
                'es_proveedor_principal' => (bool) $relacion->es_proveedor_principal,
            ],
        ]);
    }

    /**
     * Historial de cambios y auditoría de un proveedor específico (alta, modificaciones, baja).
     */
    public function historial(int $id): JsonResponse
    {
        $proveedor = Proveedor::with(['usuarioCarga'])->find($id);

        if (!$proveedor) {
            return response()->json([
                'status' => 'error',
                'message' => "El proveedor con ID {$id} no existe.",
            ], 404);
        }

        $logs = LogAuditoria::with(['usuario', 'user'])
            ->where('tabla_afectada', 'proveedor')
            ->where('id_registro', $id)
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id_auditoria')
            ->get();

        $eventos = $logs->map(fn (LogAuditoria $log) => $this->formatearLogAuditoriaProveedor($log))->all();

        // Si no hay log de INSERT explícito en LOG_AUDITORIA, sintetizar el alta inicial
        $tieneAlta = collect($eventos)->contains(fn ($ev) => ($ev['accion'] ?? '') === 'INSERT' || ($ev['tipo_evento'] ?? '') === 'alta');
        if (!$tieneAlta && $proveedor->fecha_alta) {
            $usuarioAlta = $proveedor->usuarioCarga;
            $usuarioNombreAlta = $usuarioAlta ? ($usuarioAlta->nombre ?: $usuarioAlta->usuario) : ($proveedor->id_usuario_carga ? "Usuario #{$proveedor->id_usuario_carga}" : 'Sistema');

            $eventos[] = [
                'id_auditoria' => null,
                'id_proveedor' => $proveedor->id_proveedor,
                'accion' => 'INSERT',
                'tipo_evento' => 'alta',
                'titulo' => 'Alta de Proveedor',
                'descripcion' => 'Registro inicial del proveedor en el sistema',
                'cambios_resumen' => 'Alta inicial del proveedor',
                'cambios' => [
                    ['campo' => 'razon_social', 'label' => 'Razón Social', 'anterior' => null, 'nuevo' => $proveedor->razon_social],
                    ['campo' => 'cuit', 'label' => 'CUIT', 'anterior' => null, 'nuevo' => $proveedor->cuit],
                    ['campo' => 'estado', 'label' => 'Estado', 'anterior' => null, 'nuevo' => $proveedor->estado],
                ],
                'valores_anteriores' => null,
                'valores_nuevos' => [
                    'razon_social' => $proveedor->razon_social,
                    'cuit' => $proveedor->cuit,
                    'contacto' => $proveedor->contacto,
                    'telefono' => $proveedor->telefono,
                    'email' => $proveedor->email,
                    'direccion' => $proveedor->direccion,
                    'plazo_entrega_dias' => $proveedor->plazo_entrega_dias,
                    'estado' => $proveedor->estado,
                ],
                'id_usuario' => $proveedor->id_usuario_carga,
                'usuario_nombre' => $usuarioNombreAlta,
                'fecha_hora' => $proveedor->fecha_alta instanceof \Carbon\CarbonInterface
                    ? $proveedor->fecha_alta->format('Y-m-d H:i:s')
                    : (string) $proveedor->fecha_alta,
            ];

            usort($eventos, fn ($a, $b) => strcmp((string)($b['fecha_hora'] ?? ''), (string)($a['fecha_hora'] ?? '')));
        }

        return response()->json([
            'status' => 'success',
            'proveedor' => [
                'id_proveedor' => $proveedor->id_proveedor,
                'razon_social' => $proveedor->razon_social,
                'cuit' => $proveedor->cuit,
                'estado' => $proveedor->estado,
            ],
            'data' => $eventos,
        ]);
    }

    /**
     * Historial global de auditoría de proveedores.
     */
    public function historialGlobal(Request $request): JsonResponse
    {
        $limit = max(1, min(100, (int) $request->input('limit', 50)));

        $logs = LogAuditoria::with(['usuario', 'user'])
            ->where('tabla_afectada', 'proveedor')
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id_auditoria')
            ->limit($limit)
            ->get();

        $eventos = $logs->map(fn (LogAuditoria $log) => $this->formatearLogAuditoriaProveedor($log));

        return response()->json([
            'status' => 'success',
            'data' => $eventos,
        ]);
    }

    /**
     * Formatea un registro de auditoría de proveedor.
     */
    private function formatearLogAuditoriaProveedor(LogAuditoria $log): array
    {
        $ant = is_array($log->valores_anteriores) ? $log->valores_anteriores : json_decode((string) $log->valores_anteriores, true);
        $nue = is_array($log->valores_nuevos) ? $log->valores_nuevos : json_decode((string) $log->valores_nuevos, true);
        if (!is_array($ant)) $ant = [];
        if (!is_array($nue)) $nue = [];

        $accion = strtoupper((string) $log->accion);
        $tipoEvento = 'modificacion';
        $titulo = 'Modificación de Datos';
        $descripcion = 'Actualización de datos del proveedor';

        if ($accion === 'INSERT') {
            $tipoEvento = 'alta';
            $titulo = 'Alta de Proveedor';
            $descripcion = 'Proveedor registrado en el sistema';
        } elseif ($accion === 'DELETE') {
            $tipoEvento = 'eliminacion';
            $titulo = 'Eliminación';
            $descripcion = 'Proveedor eliminado';
        } else {
            $estadoNuevo = $nue['estado'] ?? null;
            $estadoAnt = $ant['estado'] ?? null;
            if ($estadoNuevo === 'inactivo' && $estadoAnt !== 'inactivo') {
                $tipoEvento = 'baja';
                $titulo = 'Baja de Proveedor';
                $descripcion = 'Proveedor dado de baja (desactivado)';
            } elseif ($estadoNuevo === 'activo' && $estadoAnt === 'inactivo') {
                $tipoEvento = 'reactivacion';
                $titulo = 'Reactivación de Proveedor';
                $descripcion = 'Proveedor reactivado en el sistema';
            } elseif (isset($nue['plazo_entrega_dias']) && count($nue) === 1) {
                $tipoEvento = 'plazo';
                $titulo = 'Actualización de Plazo';
                $descripcion = 'Cambio en el plazo de entrega';
            }
        }

        $labels = [
            'razon_social' => 'Razón Social',
            'cuit' => 'CUIT',
            'contacto' => 'Contacto',
            'telefono' => 'Teléfono',
            'email' => 'Email',
            'correo' => 'Correo Electrónico',
            'direccion' => 'Dirección',
            'plazo_entrega_dias' => 'Plazo de Entrega',
            'estado' => 'Estado',
        ];

        $cambios = [];
        $cambiosResumen = [];
        if ($tipoEvento === 'alta') {
            $cambiosResumen[] = 'Alta inicial del proveedor';
        } elseif ($tipoEvento === 'baja') {
            $cambiosResumen[] = 'Proveedor dado de baja (inactivo)';
            $cambios[] = [
                'campo' => 'estado',
                'label' => 'Estado',
                'anterior' => $ant['estado'] ?? 'activo',
                'nuevo' => 'inactivo',
            ];
        } elseif ($tipoEvento === 'reactivacion') {
            $cambiosResumen[] = 'Proveedor reactivado (activo)';
            $cambios[] = [
                'campo' => 'estado',
                'label' => 'Estado',
                'anterior' => 'inactivo',
                'nuevo' => 'activo',
            ];
        } else {
            $campos = array_unique(array_merge(array_keys($ant), array_keys($nue)));
            foreach ($campos as $campo) {
                if (in_array($campo, ['fecha_modificacion', 'fecha_desactivacion', 'id_usuario_modificacion', 'id_usuario_carga'], true)) {
                    continue;
                }
                $vAnt = $ant[$campo] ?? null;
                $vNue = $nue[$campo] ?? null;
                if ($vAnt != $vNue) {
                    $lbl = $labels[$campo] ?? ucfirst(str_replace('_', ' ', (string) $campo));
                    $txtAnt = $vAnt !== null && $vAnt !== '' ? (string) $vAnt : 'Vacío';
                    $txtNue = $vNue !== null && $vNue !== '' ? (string) $vNue : 'Vacío';
                    if ($campo === 'plazo_entrega_dias') {
                        $txtAnt .= ' días';
                        $txtNue .= ' días';
                    }
                    $cambiosResumen[] = "{$lbl}: {$txtAnt} → {$txtNue}";
                    $cambios[] = [
                        'campo' => $campo,
                        'label' => $lbl,
                        'anterior' => $vAnt,
                        'nuevo' => $vNue,
                    ];
                }
            }
        }

        $usuarioNombre = $log->nombre_usuario;

        return [
            'id_auditoria' => $log->id_auditoria,
            'id_proveedor' => $log->id_registro,
            'accion' => $log->accion,
            'tipo_evento' => $tipoEvento,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'cambios_resumen' => count($cambiosResumen) > 0 ? implode('; ', $cambiosResumen) : 'Modificación de registro',
            'cambios' => $cambios,
            'valores_anteriores' => $ant,
            'valores_nuevos' => $nue,
            'id_usuario' => $log->id_usuario,
            'usuario_nombre' => $usuarioNombre,
            'fecha_hora' => $log->fecha_hora ? $log->fecha_hora->format('Y-m-d H:i:s') : null,
        ];
    }
}

