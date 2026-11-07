<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RendicionesEjemploSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar clientes de muestra si no existen
        if (DB::table('clientes')->count() === 0) {
            DB::table('clientes')->insert([
                ['nombre' => 'Almacén', 'apellido_razon_social' => 'Don Pepe S.R.L.', 'dni_cuit' => '30-71234567-9', 'telefono' => '1145678901', 'email' => 'donpepe@gmail.com', 'direccion' => 'Av. San Martín 1234', 'tipo_cliente' => 'minorista', 'estado' => 'activo', 'creado_por' => 1],
                ['nombre' => 'Supermercado', 'apellido_razon_social' => 'El Triunfo S.A.', 'dni_cuit' => '30-89123456-2', 'telefono' => '1156789012', 'email' => 'eltriunfo@gmail.com', 'direccion' => 'Calle Belgrano 450', 'tipo_cliente' => 'mayorista', 'estado' => 'activo', 'creado_por' => 1],
                ['nombre' => 'Autoservicio', 'apellido_razon_social' => 'La Esperanza', 'dni_cuit' => '20-33445566-8', 'telefono' => '1167890123', 'email' => 'laesperanza@gmail.com', 'direccion' => 'Rivadavia 890', 'tipo_cliente' => 'minorista', 'estado' => 'activo', 'creado_por' => 1]
            ]);
        }

        // Entregas de muestra
        $e1 = DB::table('entrega')->insertGetId([
            'id_repartidor' => 2,
            'fecha_salida' => now()->format('Y-m-d'),
            'estado' => 'completada',
            'observaciones' => 'Reparto Zona Norte - Mañana',
            'id_usuario_creacion' => 1
        ]);

        $e2 = DB::table('entrega')->insertGetId([
            'id_repartidor' => 2,
            'fecha_salida' => now()->subDay()->format('Y-m-d'),
            'estado' => 'completada',
            'observaciones' => 'Reparto Zona Sur - Tarde',
            'id_usuario_creacion' => 1
        ]);

        $e3 = DB::table('entrega')->insertGetId([
            'id_repartidor' => 2,
            'fecha_salida' => now()->subDays(2)->format('Y-m-d'),
            'estado' => 'completada',
            'observaciones' => 'Reparto Centro Express',
            'id_usuario_creacion' => 1
        ]);

        // 1. Rendición PENDIENTE
        $r1 = DB::table('rendicion')->insertGetId([
            'id_repartidor' => 2,
            'id_entrega' => $e1,
            'fecha' => now(),
            'total_rendido' => 125500.00,
            'estado' => 'pendiente',
            'observaciones' => 'Jornada completada sin incidentes de tránsito. Pendiente de arqueo físico.',
            'id_usuario_validador' => null,
            'fecha_validacion' => null,
            'motivo_rechazo' => null
        ]);

        DB::table('rendicion_cobro')->insert([
            [
                'id_rendicion' => $r1,
                'id_cliente' => 1,
                'id_factura' => 101,
                'monto' => 45500.00,
                'medio_pago' => 'efectivo',
                'fecha_registro' => now()->subHours(4)
            ],
            [
                'id_rendicion' => $r1,
                'id_cliente' => 2,
                'id_factura' => 102,
                'monto' => 80000.00,
                'medio_pago' => 'transferencia',
                'fecha_registro' => now()->subHours(2)
            ]
        ]);

        // 2. Rendición APROBADA
        $r2 = DB::table('rendicion')->insertGetId([
            'id_repartidor' => 2,
            'id_entrega' => $e2,
            'fecha' => now()->subDay(),
            'total_rendido' => 230000.00,
            'estado' => 'aprobada',
            'observaciones' => 'Rendición validada y arqueada con tesorería conforme.',
            'id_usuario_validador' => 1,
            'fecha_validacion' => now()->subDay()->addHours(3),
            'motivo_rechazo' => null
        ]);

        DB::table('rendicion_cobro')->insert([
            [
                'id_rendicion' => $r2,
                'id_cliente' => 2,
                'id_factura' => 95,
                'monto' => 150000.00,
                'medio_pago' => 'transferencia',
                'fecha_registro' => now()->subDay()->subHours(3)
            ],
            [
                'id_rendicion' => $r2,
                'id_cliente' => 3,
                'id_factura' => 96,
                'monto' => 80000.00,
                'medio_pago' => 'efectivo',
                'fecha_registro' => now()->subDay()->subHours(1)
            ]
        ]);

        // 3. Rendición RECHAZADA
        $r3 = DB::table('rendicion')->insertGetId([
            'id_repartidor' => 2,
            'id_entrega' => $e3,
            'fecha' => now()->subDays(2),
            'total_rendido' => 54000.00,
            'estado' => 'rechazada',
            'observaciones' => 'Faltante de $5.000 en el sobre de billetes respecto al ticket de remito.',
            'id_usuario_validador' => 1,
            'fecha_validacion' => now()->subDays(2)->addHours(4),
            'motivo_rechazo' => 'Diferencia en caja física sin comprobante de ajuste.'
        ]);

        DB::table('rendicion_cobro')->insert([
            [
                'id_rendicion' => $r3,
                'id_cliente' => 1,
                'id_factura' => 88,
                'monto' => 34000.00,
                'medio_pago' => 'efectivo',
                'fecha_registro' => now()->subDays(2)->subHours(3)
            ],
            [
                'id_rendicion' => $r3,
                'id_cliente' => 3,
                'id_factura' => 89,
                'monto' => 20000.00,
                'medio_pago' => 'cheque',
                'fecha_registro' => now()->subDays(2)->subHours(2)
            ]
        ]);

        // 4. Rendición PENDIENTE con Cobro Mixto y Cheque
        $r4 = DB::table('rendicion')->insertGetId([
            'id_repartidor' => 2,
            'id_entrega' => null,
            'fecha' => now()->subHours(1),
            'total_rendido' => 92000.00,
            'estado' => 'pendiente',
            'observaciones' => 'Cobros de clientes directos en reparto express.',
            'id_usuario_validador' => null,
            'fecha_validacion' => null,
            'motivo_rechazo' => null
        ]);

        DB::table('rendicion_cobro')->insert([
            [
                'id_rendicion' => $r4,
                'id_cliente' => 3,
                'id_factura' => 105,
                'monto' => 42000.00,
                'medio_pago' => 'efectivo',
                'fecha_registro' => now()->subMinutes(45)
            ],
            [
                'id_rendicion' => $r4,
                'id_cliente' => 1,
                'id_factura' => 106,
                'monto' => 50000.00,
                'medio_pago' => 'cheque',
                'fecha_registro' => now()->subMinutes(30)
            ]
        ]);
    }
}

