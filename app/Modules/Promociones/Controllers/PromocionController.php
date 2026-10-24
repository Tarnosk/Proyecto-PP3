<?php

namespace App\Modules\Promociones\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Promociones\Models\Promocion;
use App\Modules\Ventas\Contracts\ProductoGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PromocionController extends Controller
{
    // GET /api/promociones
    public function index(ProductoGateway $productos)
    {
        $promociones = Promocion::all();

        $promocionesFormateadas = $promociones->map(function ($promo) use ($productos) {
            $detallesPivot = DB::table('promocion_producto')
                ->where('id_promocion', $promo->id_promocion)
                ->get();

            $productosArray = $detallesPivot->map(function ($pivot) use ($productos) {
                return [
                    'id_producto' => $pivot->id_producto,
                    'nombre' => $productos->buscar((int) $pivot->id_producto)['nombre'] ?? "Producto #{$pivot->id_producto}",
                    'cantidad'    => $pivot->cantidad
                ];
            });

            return [
                'id_promocion'   => $promo->id_promocion,
                'nombre'         => $promo->nombre,
                'tipo_descuento' => $promo->total < 0 ? 'porcentaje' : 'monto_fijo',
                'valor'          => abs($promo->total),
                'vigencia_desde' => $promo->vigencia_desde,
                'vigencia_hasta' => $promo->vigencia_hasta,
                'condiciones'    => $promo->descripcion,
                'estado'         => $promo->estado == 1 ? 'activa' : 'inactiva',
                'productos'      => $productosArray
            ];
        });

        return response()->json($promocionesFormateadas, 200);
    }

    // POST /api/promociones
    public function store(Request $request, ProductoGateway $productos)
    {
        $datos = $this->validarPromocion($request, $productos);
        DB::beginTransaction();
        try {
            $valorRecibido = $datos['valor'];
            $esPorcentaje = $datos['tipo_descuento'] === 'porcentaje';
            $totalConMemoria = $esPorcentaje ? -abs($valorRecibido) : abs($valorRecibido);

            $promocion = Promocion::create([
                'nombre'         => $datos['nombre'],
                'descripcion'    => $datos['descripcion'] ?? '',
                'total'          => $totalConMemoria,
                'vigencia_desde' => $datos['vigencia_desde'],
                'vigencia_hasta' => $datos['vigencia_hasta'],
                'estado'         => ($datos['estado'] ?? 'activa') === 'activa' ? 1 : 0
            ]);

            foreach ($datos['productos'] as $prod) {
                DB::table('promocion_producto')->insert([
                    'id_promocion' => $promocion->id_promocion,
                    'id_producto'  => $prod['id_producto'],
                    'cantidad'     => $prod['cantidad']
                ]);
            }
            DB::commit();

            return response()->json(['mensaje' => 'Promoción guardada (Híbrida)'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/promociones/{id}
    public function show($id, ProductoGateway $productos)
    {
        $promo = Promocion::find($id);
        if (!$promo) return response()->json(['mensaje' => 'No encontrada'], 404);

        $detallesPivot = DB::table('promocion_producto')->where('id_promocion', $id)->get();
        $productosArray = $detallesPivot->map(function ($pivot) use ($productos) {
            return [
                'id_producto' => $pivot->id_producto,
                'nombre' => $productos->buscar((int) $pivot->id_producto)['nombre'] ?? "Producto #{$pivot->id_producto}",
                'cantidad'    => $pivot->cantidad
            ];
        });

        $respuesta = [
            'id_promocion'   => $promo->id_promocion,
            'nombre'         => $promo->nombre,
            'tipo_descuento' => $promo->total < 0 ? 'porcentaje' : 'monto_fijo',
            'valor'          => abs($promo->total),
            'vigencia_desde' => $promo->vigencia_desde,
            'vigencia_hasta' => $promo->vigencia_hasta,
            'condiciones'    => $promo->descripcion,
            'estado'         => $promo->estado == 1 ? 'activa' : 'inactiva',
            'productos'      => $productosArray
        ];

        return response()->json($respuesta, 200);
    }

    // PUT /api/promociones/{id}
    public function update(Request $request, $id, ProductoGateway $productos)
    {
        $promocion = Promocion::find($id);
        if (!$promocion) return response()->json(['mensaje' => 'No encontrada'], 404);

        $datos = $this->validarPromocion($request, $productos);
        DB::beginTransaction();
        try {
            $valorRecibido = $datos['valor'];
            $esPorcentaje = $datos['tipo_descuento'] === 'porcentaje';
            $totalConMemoria = $esPorcentaje ? -abs($valorRecibido) : abs($valorRecibido);

            $promocion->update([
                'nombre'         => $datos['nombre'],
                'descripcion'    => $datos['descripcion'] ?? '',
                'total'          => $totalConMemoria,
                'vigencia_desde' => $datos['vigencia_desde'],
                'vigencia_hasta' => $datos['vigencia_hasta'],
                'estado'         => ($datos['estado'] ?? 'activa') === 'activa' ? 1 : 0
            ]);

            DB::table('promocion_producto')->where('id_promocion', $id)->delete();

            foreach ($datos['productos'] as $prod) {
                DB::table('promocion_producto')->insert([
                    'id_promocion' => $promocion->id_promocion,
                    'id_producto'  => $prod['id_producto'],
                    'cantidad'     => $prod['cantidad']
                ]);
            }
            DB::commit();

            return response()->json(['mensaje' => "Promoción actualizada"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function validarPromocion(Request $request, ProductoGateway $productos): array
    {
        $todosLosProductos = $productos->todos();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'vigencia_desde' => ['required', 'date'],
            'vigencia_hasta' => ['required', 'date', 'after_or_equal:vigencia_desde'],
            'tipo_descuento' => ['required', Rule::in(['porcentaje', 'monto_fijo'])],
            'valor' => ['required', 'numeric', 'min:0'],
            'estado' => ['sometimes', Rule::in(['activa', 'inactiva'])],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.id_producto' => ['required', 'integer', Rule::in(array_column($todosLosProductos, 'id'))],
            'productos.*.cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        if ($datos['tipo_descuento'] === 'porcentaje' && (float) $datos['valor'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'valor' => ['El porcentaje de descuento debe estar entre 0 y 100.'],
            ]);
        }

        return $datos;
    }

    // DELETE /api/promociones/{id}
    public function destroy($id)
    {
        $promocion = Promocion::find($id);
        if ($promocion) {
            $promocion->estado = 0;
            $promocion->save();
        }
        return response()->json(['mensaje' => "Promoción dada de baja"], 200);
    }

    // PATCH /api/promociones/{id}/estado
    public function updateEstado(Request $request, $id)
    {
        $promocion = Promocion::find($id);
        if ($promocion) {
            $promocion->estado = $request->input('estado', 'activa') === 'activa' ? 1 : 0;
            $promocion->save();
        }
        return response()->json(['mensaje' => "Estado actualizado"], 200);
    }
}
