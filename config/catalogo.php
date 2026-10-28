<?php

// Configuración exclusiva del catálogo público de G1. La fuente se define en services.catalog.
return [
    'transport' => env('CATALOG_TRANSPORT', 'http'),
    'refresh_seconds' => 60,
    // Las fotos son opcionales. Agregar sólo hosts de imágenes acordados con el proveedor.
    'image_hosts' => array_values(array_filter(array_map('trim', explode(',', env('CATALOG_IMAGE_HOSTS', ''))))),
];
