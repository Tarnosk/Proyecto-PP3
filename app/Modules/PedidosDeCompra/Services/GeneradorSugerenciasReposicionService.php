<?php
declare(strict_types=1);

namespace App\Modules\PedidosDeCompra\Services;

use App\Modules\PedidosDeCompra\Models\DetalleOrden;
use App\Modules\PedidosDeCompra\Models\OrdenCompra;
use App\Modules\PedidosDeCompra\Models\SugerenciaCompra;
use App\Modules\PedidosDeCompra\Repositories\SugerenciaCompraRepository;
use App\Modules\Productos\Models\Producto;
use App\Modules\Proveedores\Models\ProductoProveedor;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * PC07 - Sugerencias de reposición.
 *
 * Fuentes de datos en el esquema unificado (no existen STOCK ni VENTA aquí):
 *  - stock actual / mínimo : PRODUCTO.stock, PRODUCTO.stock_minimo
 *  - stock máximo          : no existe -> config('reposicion.stock_maximo_default')
 *  - velocidad de venta    : MOVIMIENTO_STOCK (tipo 'venta') de los últimos N días
 *  - proveedor y precio    : PRODUCTO_PROVEEDOR (activo, menor precio_acordado)
 *  - plazo de entrega      : PROVEEDOR.plazo_entrega_dias
 */
class GeneradorSugerenciasReposicionService
{
    public function __construct(
        private SugerenciaCompraRepository $sugerencias
    ) {
    }

    public function generarSugerencias(): array
    {
        $generadas = [];
        $errores = [];

        foreach (Producto::where('estado', 'activo')->get() as $producto) {
            try {
                $filas = $this->analizarProducto($producto);
                $motivosActivos = array_column($filas, 'motivo_generacion');

                // Si el producto recuperó su stock y ya no aplica el motivo, limpiar sugerencias pendientes obsoletas sin orden
                $limpiezaQuery = SugerenciaCompra::pendientes()
                    ->where('id_producto', $producto->id_producto)
                    ->whereNull('id_orden_compra');
                if ($motivosActivos !== []) {
                    $limpiezaQuery->whereNotIn('motivo_generacion', $motivosActivos);
                }
                $limpiezaQuery->delete();

                foreach ($filas as $datos) {
                    $existente = $this->sugerencias->obtenerPendiente($producto->id_producto, $datos['motivo_generacion']);
                    if ($existente) {
                        $existente->update($datos);
                        $generadas[] = $existente->fresh();
                        continue;
                    }
                    $generadas[] = $this->sugerencias->crear($datos);
                }
            } catch (Throwable $e) {
                $errores[] = "Producto {$producto->id_producto}: {$e->getMessage()}";
            }
        }

        return [
            'exito' => true,
            'total_generadas' => count($generadas),
            'sugerencias' => collect($generadas)->map(fn (SugerenciaCompra $s) => $s->load(['producto', 'proveedor']))
                ->map(fn (SugerenciaCompra $s) => self::formatear($s))->all(),
            'errores' => $errores,
            'fecha_generacion' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /** @return array<int, array<string, mixed>> filas listas para SUGERENCIA_COMPRA */
    private function analizarProducto(Producto $producto): array
    {
        $stock = (int) $producto->stock_disponible;
        $minimo = (int) $producto->stock_minimo;
        $maximo = max($minimo, 1);
        $velocidad = $this->calcularVelocidadVenta($producto);

        // Cada motivo -> [cantidad, observación]
        $motivos = [];

        if ($stock <= $minimo) {
            $motivos['bajo_stock'] = [null, 'Stock por debajo del mínimo permitido'];
        }

        $dias = $this->calcularDiasHastaAgotamiento($stock, $velocidad);
        if ($dias > 0 && $dias <= (int) config('reposicion.dias_agotamiento_alerta', 7)) {
            $motivos['agotamiento_inmediato'] = [null, "Agotamiento en {$dias} días al ritmo de venta actual"];
        }

        if ($motivos === []) {
            return [];
        }

        $asociacion = $this->obtenerMejorProveedor($producto);
        if (!$asociacion) {
            throw new RuntimeException('No hay proveedores activos registrados para el producto');
        }

        $plazo = (int) ($asociacion->proveedor->plazo_entrega_dias ?? config('reposicion.plazo_entrega_default', 5));
        $precio = (float) ($asociacion->precio_acordado ?? 0);

        $filas = [];
        foreach ($motivos as $motivo => [$cantidadFija, $observacion]) {
            $cantidad = $cantidadFija ?? $this->calcularCantidadOptima($stock, $minimo);

            $filas[] = [
                'id_producto' => $producto->id_producto,
                'id_proveedor' => $asociacion->id_proveedor,
                'cantidad_sugerida' => $cantidad,
                'cantidad_minima' => $minimo,
                'cantidad_maxima' => $maximo,
                'precio_unitario' => $precio,
                'costo_total' => round($cantidad * $precio, 2),
                'velocidad_venta_diaria' => $velocidad,
                'plazo_entrega_dias' => $plazo,
                'fecha_reorden' => Carbon::today()->addDays($plazo)->toDateString(),
                'estado' => 'pendiente',
                'motivo_generacion' => $motivo,
                'observaciones' => $observacion,
            ];
        }

        return $filas;
    }

    /**
     * Calcula exactamente las unidades necesarias para alcanzar el stock_minimo asignado al producto
     * (stock_minimo - stock_actual, con mínimo 1 si el stock actual es igual al mínimo).
     */
    private function calcularCantidadOptima(int $stock, int $minimo): int
    {
        return max(1, $minimo - $stock);
    }

    private function calcularVelocidadVenta(Producto $producto): float
    {
        $ventana = (int) config('reposicion.ventana_ventas_dias', 30);

// La velocidad se mide en unidades base: PRODUCTO.stock y las cantidades
            // sugeridas están en base, así que usar 'cantidad' subestimaría la venta
            // cuando el movimiento se registró en una unidad no base (p.ej. cajas).
            $vendidas = (int) MovimientoStock::where('id_producto', $producto->id_producto)
              ->where('tipo', 'venta')
              ->where('fecha', '>=', Carbon::today()->subDays($ventana)->toDateString())
              ->sum('cantidad_base');

        return round($vendidas / max($ventana, 1), 2);
    }

    private function calcularDiasHastaAgotamiento(int $stock, float $velocidadDiaria): int
    {
        if ($velocidadDiaria <= 0) {
            return 365;
        }
        return (int) ($stock / $velocidadDiaria);
    }

    /** Prioriza el proveedor principal activo; si no hay principal, el de menor precio acordado. */
    private function obtenerMejorProveedor(Producto $producto): ?ProductoProveedor
    {
        return ProductoProveedor::query()
            ->with('proveedor')
            ->where('id_producto', $producto->id_producto)
            ->where('activo', true)
            ->whereHas('proveedor', fn ($q) => $q->where('estado', 'activo'))
            ->orderByDesc('es_proveedor_principal')
            ->orderByRaw('precio_acordado IS NULL')
            ->orderBy('precio_acordado')
            ->first();
    }

    public function procesarSugerencia(int $id, int $idUsuario): array
    {
        $this->resolver($id, 'procesada', $idUsuario);

        return ['exito' => true, 'mensaje' => 'Sugerencia procesada correctamente', 'sugerencia_id' => $id];
    }

    public function rechazarSugerencia(int $id, int $idUsuario, ?string $motivo = null): array
    {
        $this->resolver($id, 'rechazada', $idUsuario, $motivo);

        return ['exito' => true, 'mensaje' => 'Sugerencia rechazada correctamente', 'sugerencia_id' => $id];
    }

    private function resolver(int $id, string $estado, int $idUsuario, ?string $motivo = null): void
    {
        $sugerencia = $this->sugerencias->obtenerPorId($id);
        if (!$sugerencia) {
            throw new RuntimeException('Sugerencia no encontrada');
        }
        if ($sugerencia->estado !== 'pendiente') {
            throw new RuntimeException("La sugerencia ya fue {$sugerencia->estado}.");
        }
        $this->sugerencias->resolver($id, $estado, $idUsuario, $motivo);
    }

    public function procesarMultiples(array $ids, int $idUsuario): array
    {
        if ($ids === []) {
            throw new RuntimeException('No se recibieron sugerencias para procesar');
        }

        $r = $this->sugerencias->procesarMultiples($ids, $idUsuario);

        return ['exito' => true, 'total_procesadas' => $r['procesadas'], 'fallidas' => $r['fallidas']];
    }

    /**
     * PC07 - Generar una orden de compra a partir de la sugerencia y asociarla
     * al proveedor correspondiente.
     *
     * El proveedor NO se elige en la UI: es el que el generador ya asignado a la
     * sugerencia (el activo de mejor precio, ver obtenerMejorProveedor), que es el
     * criterio de la H.U. "asociar la orden al proveedor correspondiente".
     *
     * Se reutiliza el contrato de PC01 (ORDEN_COMPRA + DETALLE_ORDEN) en vez de
     * inventar uno nuevo: la orden resultante es indistinguible de una creada a
     * mano y entra por los mismos listados y validaciones de compras.
     *
     * Todo en una transacción: si falla el marcado de la sugerencia no debe quedar
     * una orden huérfana.
     */
    public function generarOrdenDesdeSugerencia(int $id, int $idUsuario, bool $agruparProveedor = false, ?int $cantidadOverride = null): array
    {
        $sugerencia = $this->sugerencias->obtenerPorId($id);
        if (!$sugerencia) {
            throw new RuntimeException('Sugerencia no encontrada');
        }

        // Candado: resolver() exige 'pendiente', así que repetir el intento falla acá.
        if ($sugerencia->estado !== 'pendiente') {
            throw new RuntimeException("La sugerencia ya fue {$sugerencia->estado}.");
        }
        if ($sugerencia->id_orden_compra !== null) {
            throw new RuntimeException('La sugerencia ya generó una orden de compra.');
        }
        if ($sugerencia->id_proveedor === null) {
            throw new RuntimeException('La sugerencia no tiene proveedor asignado, no se puede generar la orden.');
        }

        $proveedor = $sugerencia->proveedor;
        if (!$proveedor) {
            throw new RuntimeException('El proveedor de la sugerencia no existe.');
        }
        if ($proveedor->estado !== 'activo') {
            throw new RuntimeException("No se puede generar la orden: el proveedor {$proveedor->razon_social} está inactivo. (PV04)");
        }

        $producto = $sugerencia->producto;
        if (!$producto) {
            throw new RuntimeException('El producto de la sugerencia no existe.');
        }
        if ($producto->estado !== 'activo') {
            throw new RuntimeException("No se puede generar la orden: el producto {$producto->nombre} está inactivo. (PV05)");
        }

        $cantidad = $cantidadOverride !== null ? $cantidadOverride : (int) $sugerencia->cantidad_sugerida;
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad sugerida debe ser mayor a cero.');
        }

        $precio = (float) $sugerencia->precio_unitario;
        $subtotal = round($cantidad * $precio, 2);

        $idUnidad = (int) (UnidadMedida::where('id_producto', $producto->id_producto)
            ->where('es_base', 1)
            ->value('id_unidad')
            ?? UnidadMedida::where('es_base', 1)->orderBy('id_unidad')->value('id_unidad'));

        $sugerenciasProcesadas = 0;

        $orden = DB::transaction(function () use ($id, $sugerencia, $proveedor, $producto, $cantidad, $cantidadOverride, $precio, $subtotal, $idUnidad, $idUsuario, $agruparProveedor, &$sugerenciasProcesadas) {
            $bloqueada = $this->sugerencias->obtenerPorIdBloqueado($id);
            if (!$bloqueada) {
                throw new RuntimeException('Sugerencia no encontrada');
            }
            if ($bloqueada->estado !== 'pendiente') {
                throw new RuntimeException("La sugerencia ya fue {$bloqueada->estado}.");
            }
            if ($bloqueada->id_orden_compra !== null) {
                throw new RuntimeException('La sugerencia ya generó una orden de compra.');
            }

            if ($cantidadOverride !== null && $cantidadOverride > 0) {
                $bloqueada->cantidad_sugerida = $cantidadOverride;
                $bloqueada->costo_total = round($cantidadOverride * (float) $bloqueada->precio_unitario, 2);
                $bloqueada->save();
            }

            $hoy = Carbon::now()->toDateString();

            $orden = null;
            if ($agruparProveedor) {
                // Solo si el usuario eligió agrupar por proveedor reutilizamos una OC pendiente del mismo proveedor de hoy
                $orden = OrdenCompra::where('id_proveedor', $proveedor->id_proveedor)
                    ->where('estado', 'pendiente')
                    ->whereDate('fecha_creacion', $hoy)
                    ->doesntHave('compras')
                    ->orderByDesc('id_orden')
                    ->first();
            }

            if (!$orden) {
                $orden = OrdenCompra::create([
                    'numero_orden' => $this->generarNumero(),
                    'id_proveedor' => $proveedor->id_proveedor,
                    'total_estimado' => 0,
                    'estado' => 'pendiente',
                    'fecha_creacion' => $hoy,
                    'fecha_entrega_estimada' => $bloqueada->plazo_entrega_dias
                        ? Carbon::now()->addDays((int) $bloqueada->plazo_entrega_dias)->toDateString()
                        : null,
                    'id_usuario' => $idUsuario,
                ]);
            }

            // Lista de sugerencias a incluir en esta orden (solo la seleccionada, o todas las pendientes del proveedor si $agruparProveedor === true)
            $loteSugerencias = collect([$bloqueada]);
            if ($agruparProveedor) {
                $otrasDelProveedor = SugerenciaCompra::with('producto')
                    ->where('id_proveedor', $proveedor->id_proveedor)
                    ->where('estado', 'pendiente')
                    ->whereNull('id_orden_compra')
                    ->where('id_sugerencia', '!=', $id)
                    ->lockForUpdate()
                    ->get();
                $loteSugerencias = $loteSugerencias->concat($otrasDelProveedor);
            }

            foreach ($loteSugerencias as $sugItem) {
                $prodItem = $sugItem->id_sugerencia === $id ? $producto : $sugItem->producto;
                if (!$prodItem || $prodItem->estado !== 'activo') {
                    continue;
                }
                $cantItem = (int) $sugItem->cantidad_sugerida;
                if ($cantItem <= 0) {
                    continue;
                }
                $precioItem = (float) $sugItem->precio_unitario;
                $subItem = round($cantItem * $precioItem, 2);
                $unidadItem = (int) (UnidadMedida::where('id_producto', $prodItem->id_producto)
                    ->where('es_base', 1)
                    ->value('id_unidad')
                    ?? $idUnidad);

                $detalleExistente = DetalleOrden::where('id_orden', $orden->id_orden)
                    ->where('id_producto', $prodItem->id_producto)
                    ->first();

                if ($detalleExistente) {
                    $nuevaCant = max((int) $detalleExistente->cantidad_solicitada, $cantItem);
                    $detalleExistente->cantidad_solicitada = $nuevaCant;
                    $detalleExistente->cantidad_sugerida = max((int) $detalleExistente->cantidad_sugerida, $cantItem);
                    $detalleExistente->precio_estimado = $precioItem;
                    $detalleExistente->subtotal = round($nuevaCant * $precioItem, 2);
                    $detalleExistente->save();
                } else {
                    DetalleOrden::create([
                        'id_orden' => $orden->id_orden,
                        'id_producto' => $prodItem->id_producto,
                        'id_unidad' => $unidadItem,
                        'cantidad_solicitada' => $cantItem,
                        'cantidad_sugerida' => $cantItem,
                        'origen' => 'sugerencia',
                        'precio_estimado' => $precioItem,
                        'subtotal' => $subItem,
                    ]);
                }

                if (!$this->sugerencias->resolver((int) $sugItem->id_sugerencia, 'procesada', $idUsuario)) {
                    throw new RuntimeException('No se pudo vincular la sugerencia con la orden generada.');
                }
                SugerenciaCompra::where('id_sugerencia', $sugItem->id_sugerencia)
                    ->update(['id_orden_compra' => $orden->id_orden]);
                $sugerenciasProcesadas++;
            }

            $orden->total_estimado = round((float) DetalleOrden::where('id_orden', $orden->id_orden)->sum('subtotal'), 2);
            $orden->save();

            return $orden;
        });

        return [
            'exito' => true,
            'mensaje' => $sugerenciasProcesadas > 1
                ? "Orden de compra {$orden->numero_orden} generada agrupando {$sugerenciasProcesadas} productos de {$proveedor->razon_social}."
                : 'Orden de compra generada desde la sugerencia.',
            'sugerencia_id' => $sugerencia->id_sugerencia,
            'sugerencias_agrupadas' => $sugerenciasProcesadas,
            'orden' => [
                'id' => $orden->id_orden,
                'numero_orden' => $orden->numero_orden,
                'id_proveedor' => $orden->id_proveedor,
                'proveedor' => $proveedor->razon_social,
                'producto' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'total_estimado' => (float) $orden->total_estimado,
                'fecha_entrega_estimada' => $orden->fecha_entrega_estimada,
            ],
        ];
    }

    /** Genera un número único de orden con el formato de PC01: OC-AAAA-nnnnnn. */
    private function generarNumero(): string
    {
        do {
            $numero = 'OC-' . Carbon::now()->format('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (OrdenCompra::where('numero_orden', $numero)->exists());

        return $numero;
    }

    public function obtenerResumen(): array
    {
        $r = $this->sugerencias->obtenerResumen();
        $stockTotal = (int) Producto::where('estado', 'activo')->sum('stock');
        $porcentaje = $stockTotal > 0 ? ($r['cantidad_total'] / $stockTotal) * 100 : 0;

        return [
            'exito' => true,
            'resumen' => [
                'sugerencias_pendientes' => $r['total_pendientes'],
                'cantidad_total' => $r['cantidad_total'],
                'costo_total_sugerido' => '$' . number_format($r['costo_total_sugerido'], 2),
                'porcentaje_reposicion' => number_format($porcentaje, 2) . '%',
            ],
        ];
    }

    /**
     * Mapea al contrato que espera public/js/sugerencias-reposicion.js
     * (id, producto.{codigo,nombre}, proveedor.nombre).
     */
    public static function formatear(SugerenciaCompra $s): array
    {
        return [
            'id' => $s->id_sugerencia,
            'id_sugerencia' => $s->id_sugerencia,
            'id_producto' => $s->id_producto,
            'id_proveedor' => $s->id_proveedor,
            'stock_actual' => (int) ($s->producto?->stock_disponible ?? 0),
            'producto' => [
                'id' => $s->id_producto,
                'codigo' => $s->producto?->codigo,
                'nombre' => $s->producto?->nombre ?? $s->producto?->descripcion,
                'stock' => (int) ($s->producto?->stock_disponible ?? 0),
                'stock_minimo' => (int) ($s->producto?->stock_minimo ?? $s->cantidad_minima ?? 0),
            ],
            'proveedor' => [
                'id' => $s->id_proveedor,
                'nombre' => $s->proveedor?->razon_social,
                'razon_social' => $s->proveedor?->razon_social,
            ],
            'cantidad_sugerida' => $s->cantidad_sugerida,
            'cantidad_minima' => $s->cantidad_minima,
            'cantidad_maxima' => $s->cantidad_maxima,
            'precio_unitario' => $s->precio_unitario,
            'costo_total' => $s->costo_total,
            'velocidad_venta_diaria' => $s->velocidad_venta_diaria,
            'plazo_entrega_dias' => $s->plazo_entrega_dias,
            'fecha_reorden' => $s->fecha_reorden?->format('Y-m-d'),
            'estado' => $s->estado,
            // PC07: referencia a la orden generada, si la hubo.
            'id_orden_compra' => $s->id_orden_compra,
            'motivo_generacion' => $s->motivo_generacion,
            'observaciones' => $s->observaciones,
            'motivo_rechazo' => $s->motivo_rechazo,
        ];
    }
}
