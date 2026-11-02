<?php

namespace Tests\Unit\Ventas;

use App\Modules\Ventas\Services\CalculadoraVenta;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tests UNITARIOS de app/Modules/Ventas/Services/CalculadoraVenta.php.
 * No usan base de datos. Extienden Tests\TestCase solo porque
 * ValidationException necesita a Laravel encendido para armar el mensaje.
 */
class CalculadoraVentaTest extends TestCase
{
    private CalculadoraVenta $calculadora;

    // Catálogo chico y fijo para que las cuentas sean fáciles de seguir.
    private array $catalogo = [
        1 => ['id' => 1, 'nombre' => 'Coca-Cola 1.5L', 'precioMin' => 2500, 'precioMay' => 2200],
        2 => ['id' => 2, 'nombre' => 'Pan lactal', 'precioMin' => 1800, 'precioMay' => 1600],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculadora = new CalculadoraVenta;
    }

    private function calcular(array $items, string $lista = 'minorista', ?array $descuentoGlobal = null): array
    {
        return $this->calculadora->calcular($items, $this->catalogo, $lista, $descuentoGlobal);
    }

    /**
     * Ejecuta la cuenta esperando un error de validación y devuelve los errores.
     */
    private function erroresDe(callable $cuenta): array
    {
        try {
            $cuenta();
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('Se esperaba una ValidationException.');
    }

    public function test_venta_simple_minorista(): void
    {
        $r = $this->calcular([['id' => 1, 'cantidad' => 2, 'descuento' => 0]]);

        $this->assertSame(5000.0, $r['subtotal']);
        $this->assertSame(0.0, $r['descuento_global']);
        $this->assertSame(5000.0, $r['total']);
        $this->assertSame([
            'id_producto' => 1,
            'id_promocion' => null,
            'cantidad' => 2,
            'precio_unitario' => 2500.0,
            'descuento' => 0.0,
            'subtotal' => 5000.0,
        ], $r['detalles'][0]);
    }

    public function test_mayorista_usa_precio_mayorista(): void
    {
        $r = $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 0]], 'mayorista');

        $this->assertSame(2200.0, $r['total']);
    }

    public function test_conserva_la_promocion_en_la_linea_calculada(): void
    {
        $r = $this->calcular([
            ['id' => 1, 'cantidad' => 2, 'descuento' => 100, 'id_promocion' => 7],
        ]);

        $this->assertSame(7, $r['detalles'][0]['id_promocion']);
    }

    public function test_descuento_por_unidad_se_multiplica_por_la_cantidad(): void
    {
        // 3 x (1800 - 100) = 5100
        $r = $this->calcular([['id' => 2, 'cantidad' => 3, 'descuento' => 100]]);

        $this->assertSame(5100.0, $r['total']);
    }

    public function test_descuento_global_porcentaje_se_aplica_despues_de_los_de_linea(): void
    {
        // (2500 - 500) = 2000 → 10% = 200 → total 1800
        $r = $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 500]], 'minorista', ['modo' => 'porcentaje', 'valor' => 10]);

        $this->assertSame(2000.0, $r['subtotal']);
        $this->assertSame(200.0, $r['descuento_global']);
        $this->assertSame(1800.0, $r['total']);
    }

    public function test_descuento_global_monto_fijo(): void
    {
        $r = $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 0]], 'minorista', ['modo' => 'monto', 'valor' => 300]);

        $this->assertSame(300.0, $r['descuento_global']);
        $this->assertSame(2200.0, $r['total']);
    }

    public function test_porcentaje_decimal_redondea_a_centavos(): void
    {
        // 2500 * 33.333% = 833.325 → 833.33
        $r = $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 0]], 'minorista', ['modo' => 'porcentaje', 'valor' => 33.333]);

        $this->assertSame(833.33, $r['descuento_global']);
        $this->assertSame(1666.67, $r['total']);
    }

    public function test_varios_productos_suman_bien(): void
    {
        $r = $this->calcular([
            ['id' => 1, 'cantidad' => 2, 'descuento' => 0],   // 5000
            ['id' => 2, 'cantidad' => 1, 'descuento' => 300], // 1500
        ]);

        $this->assertCount(2, $r['detalles']);
        $this->assertSame(6500.0, $r['total']);
    }

    public function test_descuento_igual_al_precio_se_permite_si_el_total_es_positivo(): void
    {
        $r = $this->calcular([
            ['id' => 1, 'cantidad' => 1, 'descuento' => 2500], // regalo: 0
            ['id' => 2, 'cantidad' => 1, 'descuento' => 0],
        ]);

        $this->assertSame(1800.0, $r['total']);
    }

    public function test_producto_inexistente_indica_el_indice(): void
    {
        $errores = $this->erroresDe(fn () => $this->calcular([
            ['id' => 1, 'cantidad' => 1, 'descuento' => 0],
            ['id' => 77, 'cantidad' => 1, 'descuento' => 0],
        ]));

        $this->assertSame(['items.1.id' => ['El producto #77 no existe en el catálogo.']], $errores);
    }

    public function test_descuento_mayor_al_precio_de_la_lista_elegida(): void
    {
        // 2300 es válido en minorista (2500) pero supera el mayorista (2200).
        $errores = $this->erroresDe(fn () => $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 2300]], 'mayorista'));

        $this->assertArrayHasKey('items.0.descuento', $errores);
    }

    public function test_porcentaje_mayor_a_cien_es_invalido(): void
    {
        $errores = $this->erroresDe(fn () => $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 0]], 'minorista', ['modo' => 'porcentaje', 'valor' => 101]));

        $this->assertArrayHasKey('descuento_global.valor', $errores);
    }

    public function test_porcentaje_cien_deja_total_cero_y_es_invalido(): void
    {
        $errores = $this->erroresDe(fn () => $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 0]], 'minorista', ['modo' => 'porcentaje', 'valor' => 100]));

        $this->assertSame(['items' => ['La venta debe tener un total mayor a cero.']], $errores);
    }

    public function test_monto_global_mayor_al_subtotal_se_recorta_y_deja_total_cero(): void
    {
        $errores = $this->erroresDe(fn () => $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 0]], 'minorista', ['modo' => 'monto', 'valor' => 99999]));

        $this->assertArrayHasKey('items', $errores);
    }

    public function test_descuento_global_vacio_cuenta_como_cero(): void
    {
        $r = $this->calcular([['id' => 1, 'cantidad' => 1, 'descuento' => 0]], 'minorista', []);

        $this->assertSame(2500.0, $r['total']);
    }

    public function test_el_error_usa_la_misma_clave_que_mando_el_front(): void
    {
        // Si el front manda {"5": {...}} el error se informa como items.5 (la clave original).
        $errores = $this->erroresDe(fn () => $this->calcular([5 => ['id' => 99, 'cantidad' => 1, 'descuento' => 0]]));

        $this->assertArrayHasKey('items.5.id', $errores);
    }
}
