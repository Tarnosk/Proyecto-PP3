<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_auth.php';

requireRole(['repartidor', 'administrativo']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ventas y Pedidos | Sistema de gestion</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="module-page">
    <main class="layout narrow">
        <section class="page-heading">
            <p class="eyebrow">Modulo comercial</p>
            <h1>Ventas y Pedidos</h1>
            <p>Modulo preparado para la gestion de ventas y pedidos.</p>
        </section>

        <section class="form-panel">
            <p>Esta seccion queda disponible como acceso operativo para el repartidor.</p>
            <div class="form-actions">
                <a class="button button-secondary" href="../dashboard.php">Volver al panel</a>
            </div>
        </section>
    </main>
</body>
</html>
