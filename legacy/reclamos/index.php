<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['repartidor', 'administrativo']);

$success = getFlash('reclamo_success');
$errors = getFlash('reclamo_errors', []);
$reclamos = [];

try {
    $pdo = getConnection();
    $stmt = $pdo->query(
        'SELECT r.id, r.asunto, r.prioridad, r.estado, r.created_at,
                c.nombre, c.apellido_razon_social, c.tipo_cliente
         FROM reclamos r
         INNER JOIN clientes c ON c.id = r.cliente_id
         ORDER BY r.created_at DESC
         LIMIT 100'
    );
    $reclamos = $stmt->fetchAll();
} catch (PDOException $exception) {
    error_log('[reclamos][listado] ' . $exception->getMessage());
    $errors[] = 'No fue posible cargar el listado de reclamos.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reclamos | Sistema de gestion</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="module-page">
    <header class="topbar">
        <a class="app-title" href="../dashboard.php">Sistema de gestion</a>
        <nav class="top-nav" aria-label="Navegacion principal">
            <a href="../dashboard.php">Panel</a>
            <a href="nuevo.php">Nuevo reclamo</a>
            <a href="../logout.php">Cerrar sesion</a>
        </nav>
    </header>

    <main class="layout">
        <section class="page-heading">
            <p class="eyebrow">Atencion al cliente</p>
            <h1>Reclamos</h1>
            <p>Registro y seguimiento inicial de reclamos comerciales.</p>
        </section>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="status">
                <p><?= e($success) ?></p>
            </div>
        <?php endif; ?>

        <section class="table-panel" aria-label="Listado de reclamos">
            <?php if (empty($reclamos)): ?>
                <p class="empty-state">Todavia no hay reclamos cargados.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Reclamo</th>
                                <th>Cliente</th>
                                <th>Tipo</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reclamos as $reclamo): ?>
                                <tr>
                                    <td><strong>#<?= (int) $reclamo['id'] ?> <?= e($reclamo['asunto']) ?></strong></td>
                                    <td><?= e($reclamo['apellido_razon_social']) ?>, <?= e($reclamo['nombre']) ?></td>
                                    <td><span class="type-badge type-<?= e($reclamo['tipo_cliente']) ?>"><?= e($reclamo['tipo_cliente']) ?></span></td>
                                    <td><?= e($reclamo['prioridad']) ?></td>
                                    <td><?= e($reclamo['estado']) ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $reclamo['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
