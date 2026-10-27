<?php

namespace App\Services;

use App\Modules\Ventas\Contracts\ClienteGateway;

// Cumple el contrato ClienteGateway: Ventas lo usa sin saber que es un mock (ver config/ventas.php).
class MockClienteApi implements ClienteGateway
{
    public function todos(): array
    {
        return [
            [
                'id' => 472891,
                'nombre' => 'Supermercado El Norte SRL',
                'cuit' => '30-71234567-8',
                'condicion' => 'Responsable Inscripto',
                'domicilio' => 'Av. Mitre 450, Buenos Aires',
                'localidad' => 'Buenos Aires',
                'movimientos' => [
                    ['fecha' => '2026-06-01', 'descripcion' => 'Compra Factura #1045', 'monto' => -8500, 'venta_id' => null],
                    ['fecha' => '2026-06-05', 'descripcion' => 'Pago parcial', 'monto' => 5000, 'venta_id' => null],
                    ['fecha' => '2026-06-10', 'descripcion' => 'Compra Factura #1062', 'monto' => -11500, 'venta_id' => null],
                ],
            ],
            [
                'id' => 638204,
                'nombre' => 'Almacén Don Pedro',
                'cuit' => '20-12345678-9',
                'condicion' => 'Consumidor Final',
                'domicilio' => 'Calle 9 123, Pigüé',
                'localidad' => 'Pigüé',
                'movimientos' => [
                    ['fecha' => '2026-06-12', 'descripcion' => 'Saldo a favor', 'monto' => 3500, 'venta_id' => null],
                ],
            ],
            [
                'id' => 915736,
                'nombre' => 'María González',
                'cuit' => '27-98765432-1',
                'condicion' => 'Consumidor Final',
                'domicilio' => 'Buenos Aires',
                'localidad' => 'Buenos Aires',
                'movimientos' => [],
            ],
            [
                'id' => 284159,
                'nombre' => 'Tuvi S.A.',
                'cuit' => '27-98762134-1',
                'condicion' => 'Responsable Inscripto',
                'domicilio' => 'Calle X 354, Carhué',
                'localidad' => 'Carhué',
                'movimientos' => [
                    ['fecha' => '2026-06-10', 'descripcion' => 'Compra Factura #2389', 'monto' => -11500, 'venta_id' => null],
                    ['fecha' => '2026-06-10', 'descripcion' => 'Compra Factura #3219', 'monto' => -30500, 'venta_id' => null],
                    ['fecha' => '2026-06-10', 'descripcion' => 'Pago parcial', 'monto' => 2000, 'venta_id' => null],
                ],
            ],
            [
                'id' => 706318,
                'nombre' => 'Babau S.A.',
                'cuit' => '27-98700002-8',
                'condicion' => 'Responsable Inscripto',
                'domicilio' => 'Calle 9 de Julio 101, Coronel Pringles',
                'localidad' => 'Coronel Pringles',
                'movimientos' => [
                    ['fecha' => '2026-06-10', 'descripcion' => 'Compra Factura #3019', 'monto' => -33450.88, 'venta_id' => null],
                    ['fecha' => '2026-06-10', 'descripcion' => 'Pago parcial', 'monto' => 15000, 'venta_id' => null],
                ],
            ],
            [
                'id' => 391527,
                'nombre' => 'WoW SRL',
                'cuit' => '27-128700002-8',
                'condicion' => 'Responsable Inscripto',
                'domicilio' => 'Calle San Martín 1033, Pigüé',
                'localidad' => 'Pigüé',
                'movimientos' => [
                    ['fecha' => '2026-06-10', 'descripcion' => 'Compra Factura #3019', 'monto' => -40450, 'venta_id' => null],
                    ['fecha' => '2026-06-10', 'descripcion' => 'Pago parcial', 'monto' => 9000, 'venta_id' => null],
                ],
            ],
        ];
    }

    public function buscar(int $id): ?array
    {
        foreach ($this->todos() as $cliente) {
            if ($cliente['id'] === $id) {
                return $cliente;
            }
        }

        return null;
    }
}
