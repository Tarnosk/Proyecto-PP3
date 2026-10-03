<?php

/**
 * Parámetros de Consulta de Vencimientos (S11) y Sugerencias de Reposición (PC07).
 * Centralizados acá para que los umbrales no queden hardcodeados en servicios.
 */
return [
    // Consulta de vencimientos: ventanas en días desde hoy.
    'critico_dias' => 7,    // 0..7   => crítico
    'proximo_dias' => 30,   // 8..30  => próximo ; >30 => seguro

    // Reposición: un lote que vence dentro de N días dispara "proximo_vencer".
    'umbral_proximo_vencer_dias' => 15,

    // Reposición: ventana para calcular la velocidad de venta diaria.
    'ventana_ventas_dias' => 30,

    // PRODUCTO no tiene stock máximo en el esquema unificado: valor por defecto.
    'stock_maximo_default' => 100,

    // Si PROVEEDOR.plazo_entrega_dias es NULL.
    'plazo_entrega_default' => 5,

    // "agotamiento_inmediato" si el stock alcanza para <= N días de venta.
    'dias_agotamiento_alerta' => 7,
];
