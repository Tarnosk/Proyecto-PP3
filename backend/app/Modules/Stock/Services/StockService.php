<?php
declare(strict_types=1);

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Productos\Models\Producto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Servicio central de lógica de stock (S01 a S08).
 *
 * En el esquema fusionado el stock disponible vive embebido en PRODUCTO.stock
 * (OB3), de modo que las operaciones actualizan esa columna y registran cada
 * cambio en MOVIMIENTO_STOCK dentro de una transacción ACID. El estado de
 * alerta (S07) es un atributo derivado del producto (no se persiste).
 */
class StockService
{
    /**
     * Tipos de movimiento permitidos (coinciden con el CHECK de la BD).
     */
    public const TIPOS = ['ingreso', 'venta', 'devolucion', 'ajuste'];

    /**
     * Recalcula el estado de alerta derivado (S07).
     *
     * Reglas:
     *  - crítico: stock_disponible <= 0
     *  - bajo:    stock_disponible <= stock_minimo (y > 0)
     *  - normal:  stock_disponible > stock_minimo
     *
     * No persiste nada: el estado es accesible vía Producto::estado_alerta.
     */
    public function recalcularAlerta(Producto $producto): Producto
    {
        return $producto;
    }

    /**
     * Aplica un ajuste con signo a la cantidad disponible y registra el movimiento.
     *
     * @param Producto $producto  Producto afectado (debe estar persistido).
     * @param string   $tipo      'ingreso' | 'venta' | 'devolucion' | 'ajuste'
     * @param int      $cantidad  Cantidad positiva. El signo se deduce del tipo.
     * @param string   $motivo    Motivo u observación.
     * @param int      $idUsuario Usuario responsable del movimiento.
     * @param int|null $idUnidad  Unidad de medida usada (opcional, S08).
     * @param int|null $idVenta   Referencia a venta (opcional, grupo externo).
     */
    public function registrarMovimiento(
        Producto $producto,
        string $tipo,
        int $cantidad,
        string $motivo,
        int $idUsuario,
        ?int $idUnidad = null,
        ?int $idVenta = null
    ): void {
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new RuntimeException("Tipo de movimiento inválido: {$tipo}");
        }
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }
        if ($tipo === 'ajuste' && trim($motivo) === '') {
            throw new RuntimeException('Debe indicar un motivo para el ajuste.');
        }

        $fecha = Carbon::now()->toDateString();

        // Transacción atómica: actualizar stock embebido + registrar movimiento
        DB::beginTransaction();

        try {
            $disponible = $producto->stock_disponible;

            // Determinar delta aplicado al stock
            $delta = match ($tipo) {
                'ingreso', 'devolucion' => $cantidad,
                'venta', 'ajuste' => -$cantidad,
            };

            $nuevoDisponible = $disponible + $delta;

            // S04: impedir stock negativo en ventas. En ajustes negativos también se impide.
            if ($nuevoDisponible < 0) {
                DB::rollBack();
                throw new RuntimeException(
                    "Stock insuficiente para {$producto->codigo}. Disponible: {$disponible}, solicitado: {$cantidad}."
                );
            }

            $producto->stock = $nuevoDisponible;
            $producto->save();
            $this->recalcularAlerta($producto);

            MovimientoStock::create([
                'id_producto' => $producto->id_producto,
                'id_unidad' => $idUnidad,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'fecha' => $fecha,
                'motivo' => $motivo,
                'id_usuario' => $idUsuario,
                'id_venta' => $idVenta,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Consulta el stock disponible de un producto (S01 / S02).
     */
    public function stockDisponibleDe(Producto $producto): int
    {
        return $producto->stock_disponible;
    }

    /**
     * Convierte una cantidad entre unidades según sus equivalencias base (S08).
     *
     * $equivalencia_base expresa cuántas unidades de "unidad base" hay en una unidad.
     * Conversión: cantidadDestino = cantidadOrigen * (equivalenciaOrigen / equivalenciaDestino)
     */
    public static function convertir(float|int $cantidad, float|int $equivalenciaOrigen, float|int $equivalenciaDestino): float
    {
        if ($equivalenciaDestino <= 0) {
            throw new RuntimeException('La unidad de destino debe tener una equivalencia mayor a cero.');
        }
        return (float) $cantidad * $equivalenciaOrigen / $equivalenciaDestino;
    }
}
