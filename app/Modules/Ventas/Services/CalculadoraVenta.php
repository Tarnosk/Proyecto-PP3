<?php

namespace App\Modules\Ventas\Services;

use Illuminate\Validation\ValidationException;

/**
 * CALCULADORA DE UNA VENTA
 *
 * Recibe el carrito (productos, cantidades, descuentos) y devuelve las filas
 * listas para guardar en detalle_venta + subtotal, descuento global y total.
 *
 * Por qué existe aparte: modificar una venta tiene que calcular EXACTAMENTE
 * igual que crearla. Si la cuenta estuviera copiada en dos lugares, tarde o
 * temprano darían distinto. Esta clase no toca la base de datos: solo hace
 * cuentas, por eso es fácil de testear (tests/Unit/Ventas/CalculadoraVentaTest.php).
 *
 * Reglas (las mismas que VentaController@store):
 *  - El precio SIEMPRE sale del catálogo del servidor, nunca del front.
 *  - "minorista" usa precioMin, "mayorista" usa precioMay.
 *  - El descuento por producto es en $ por unidad y no puede superar el precio.
 *  - El descuento global se aplica DESPUÉS de los descuentos por producto:
 *      porcentaje → entre 0 y 100.
 *      monto      → si es mayor al subtotal se recorta al subtotal.
 *  - El total tiene que ser mayor a cero.
 * Si algo no cumple, lanza ValidationException → Laravel responde 422.
 */
class CalculadoraVenta
{
    /**
     * @param  array  $items  [['id' => 1, 'cantidad' => 2, 'descuento' => 100], ...]
     * @param  array  $catalogo  productos indexados por id: [1 => ['id' => 1, 'nombre' => ..., 'precioMin' => ..., 'precioMay' => ...]]
     * @param  string  $lista  "minorista" o "mayorista"
     * @param  array|null  $descuentoGlobal  ['modo' => 'porcentaje'|'monto', 'valor' => 10] o null
     * @return array{detalles: list<array>, subtotal: float, descuento_global: float, total: float}
     */
    public function calcular(array $items, array $catalogo, string $lista, ?array $descuentoGlobal): array
    {
        // 1) Qué columna de precio usar según la lista elegida.
        $columnaPrecio = $lista === 'mayorista' ? 'precioMay' : 'precioMin';

        // 2) Arma cada línea del carrito.
        $detalles = [];
        foreach ($items as $indice => $item) {
            $producto = $catalogo[(int) $item['id']] ?? null;

            // El producto tiene que existir en el catálogo.
            if ($producto === null) {
                throw ValidationException::withMessages([
                    "items.{$indice}.id" => ["El producto #{$item['id']} no existe en el catálogo."],
                ]);
            }

            $precio = round((float) $producto[$columnaPrecio], 2);
            $descuento = round((float) $item['descuento'], 2);

            // El descuento por unidad no puede ser mayor que el precio.
            if ($descuento > $precio) {
                throw ValidationException::withMessages([
                    "items.{$indice}.descuento" => ["El descuento no puede superar el precio unitario actual de \"{$producto['nombre']}\"."],
                ]);
            }

            $cantidad = (int) $item['cantidad'];

            // Fila tal cual se guarda en la tabla detalle_venta.
            $detalles[] = [
                'id_producto' => (int) $producto['id'],
                'id_promocion' => null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'descuento' => $descuento,
                'subtotal' => round($cantidad * ($precio - $descuento), 2),
            ];
        }

        // 3) Suma de todas las líneas (ya con su descuento individual).
        $subtotal = round(array_sum(array_column($detalles, 'subtotal')), 2);

        // 4) Descuento global (si no vino, es 0).
        $modo = $descuentoGlobal['modo'] ?? 'monto';
        $valor = (float) ($descuentoGlobal['valor'] ?? 0);

        if ($modo === 'porcentaje' && $valor > 100) {
            throw ValidationException::withMessages([
                'descuento_global.valor' => ['El porcentaje de descuento global debe estar entre 0 y 100.'],
            ]);
        }

        // Se pasa el descuento global a pesos.
        $montoDescuentoGlobal = $modo === 'porcentaje'
            ? round($subtotal * $valor / 100, 2)
            : round(min($valor, $subtotal), 2);

        // 5) Total final.
        $total = round($subtotal - $montoDescuentoGlobal, 2);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'items' => ['La venta debe tener un total mayor a cero.'],
            ]);
        }

        return [
            'detalles' => $detalles,
            'subtotal' => $subtotal,
            'descuento_global' => $montoDescuentoGlobal,
            'total' => $total,
        ];
    }
}
