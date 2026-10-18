<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['repartidor', 'administrativo']);

$errors = getFlash('reclamo_errors', []);
$old = getFlash('reclamo_old', []);
$clientes = [];

try {
    $pdo = getConnection();
    $stmt = $pdo->query(
        'SELECT id, nombre, apellido_razon_social, dni_cuit, tipo_cliente
         FROM clientes
         WHERE estado = "activo"
         ORDER BY apellido_razon_social ASC, nombre ASC'
    );
    $clientes = $stmt->fetchAll();
} catch (PDOException $exception) {
    error_log('[reclamos][clientes] ' . $exception->getMessage());
    $errors[] = 'No fue posible cargar los clientes.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo reclamo | Sistema de gestion</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="module-page">
    <header class="topbar">
        <a class="app-title" href="../dashboard.php">Sistema de gestion</a>
        <nav class="top-nav" aria-label="Navegacion principal">
            <a href="../dashboard.php">Panel</a>
            <a href="index.php">Reclamos</a>
            <a href="../logout.php">Cerrar sesion</a>
        </nav>
    </header>

    <main class="layout narrow">
        <section class="page-heading">
            <p class="eyebrow">Atencion al cliente</p>
            <h1>Nuevo reclamo</h1>
            <p>Seleccione el cliente y registre el detalle del reclamo.</p>
        </section>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="guardar.php" method="post" class="form form-panel" data-validate="reclamo" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="field">
                <label for="cliente_id">Cliente <span aria-hidden="true">*</span></label>
                <select id="cliente_id" name="cliente_id" required>
                    <option value="">Seleccione un cliente</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= (int) $cliente['id'] ?>" <?= (string) ($old['cliente_id'] ?? '') === (string) $cliente['id'] ? 'selected' : '' ?>>
                            <?= e($cliente['apellido_razon_social']) ?>, <?= e($cliente['nombre']) ?> - <?= e($cliente['dni_cuit']) ?> (<?= e($cliente['tipo_cliente']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="field-error" data-error-for="cliente_id"></small>
            </div>

            <div class="field">
                <label for="asunto">Asunto <span aria-hidden="true">*</span></label>
                <input type="text" id="asunto" name="asunto" maxlength="160" required value="<?= e($old['asunto'] ?? '') ?>">
                <small class="field-error" data-error-for="asunto"></small>
            </div>

            <div class="field">
                <label for="prioridad">Prioridad <span aria-hidden="true">*</span></label>
                <select id="prioridad" name="prioridad" required>
                    <option value="baja" <?= ($old['prioridad'] ?? '') === 'baja' ? 'selected' : '' ?>>Baja</option>
                    <option value="media" <?= ($old['prioridad'] ?? 'media') === 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="alta" <?= ($old['prioridad'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta</option>
                </select>
                <small class="field-error" data-error-for="prioridad"></small>
            </div>

            <div class="field">
                <label for="descripcion">Descripcion <span aria-hidden="true">*</span></label>
                <textarea id="descripcion" name="descripcion" rows="5" required><?= e($old['descripcion'] ?? '') ?></textarea>
                <small class="field-error" data-error-for="descripcion"></small>
            </div>

            <div class="form-actions">
                <a class="button button-secondary" href="index.php">Cancelar</a>
                <button type="submit" class="button button-primary">Crear reclamo</button>
            </div>
        </form>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
