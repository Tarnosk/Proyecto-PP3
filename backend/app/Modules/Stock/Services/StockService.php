<?php
declare(strict_types=1);

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Servicio central de lógica de stock (S01 a S08).
 *
 * Todas las operaciones que afectan la cantidad disponible se ejecutan en
 * transacciones ACID y registran un movimiento en MOVIMIENTO_STOCK. Cada
 * operación recalcula automáticamente el estado_alerta del producto (S07).
 */
class StockService
{
    /**
     * Tipos de movimiento permitidos (coinciden con el CHECK de la BD).
     */
    public const TIPOS = ['ingreso', 'venta', 'devolucion', 'ajuste'];

    /**
     * Devuelve el registro STOCK de un producto o lanza una excepción.
     */
    private function stockDe(Producto $producto): Stock
    {
        $stock = $producto->stock;
        if (!$stock) {
            throw new RuntimeException("El producto {$producto->codigo} no tiene registro de stock.");
        }
        return $stock;
    }

    /**
     * Recalcula y persiste el estado_alerta según el stock y el mínimo (S07).
     *
     * Reglas:
     *  - crítico: stock_disponible <= 0
     *  - bajo:    stock_disponible <= stock_minimo (y > 0)
     *  - normal:  stock_disponible > stock_minimo
     */
    public function recalcularAlerta(Stock $stock): Stock
    {
        $disponible = (int) $stock->stock_disponible;
        $minimo = (int) $stock->stock_minimo;

        if ($disponible <= 0) {
            $estado = 'critico';
        } elseif ($disponible <= $minimo) {
            $estado = 'bajo';
        } else {
            $estado = 'normal';
        }

        if ($stock->estado_alerta !== $estado) {
            $stock->estado_alerta = $estado;
            $stock->save();
        }

        return $stock;
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

        // Transacción atómica: actualizar stock + registrar movimiento
        DB::beginTransaction();

        try {
            $stock = $this->stockDe($producto);

            // Determinar delta aplicado al stock
            $delta = match ($tipo) {
                'ingreso', 'devolucion' => $cantidad,
                'venta', 'ajuste' => -$cantidad,
            };

            $nuevoDisponible = (int) $stock->stock_disponible + $delta;

            // S04: impedir stock negativo en ventas. En ajustes negativos también se impide.
            if ($nuevoDisponible < 0) {
                DB::rollBack();
                throw new RuntimeException(
                    "Stock insuficiente para {$producto->codigo}. Disponible: {$stock->stock_disponible}, solicitado: {$cantidad}."
                );
            }

            $stock->stock_disponible = $nuevoDisponible;

            // La unidad de medida se registra en MOVIMIENTO_STOCK.id_unidad;
            // la tabla STOCK no posee columna de unidad (es 1:1 con PRODUCTO).
            $stock->save();
            $this->recalcularAlerta($stock);

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
        return (int) $this->stockDe($producto)->stock_disponible;
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
