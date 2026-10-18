<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['administrativo']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    setFlash('cliente_errors', ['El cliente solicitado no es valido.']);
    redirect('index.php');
}

$errors = getFlash('cliente_errors', []);
$old = getFlash('cliente_old', []);
$cliente = null;

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare(
        'SELECT id, nombre, apellido_razon_social, dni_cuit, telefono, email, direccion, tipo_cliente, estado
         FROM clientes
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $cliente = $stmt->fetch();
} catch (PDOException $exception) {
    error_log('[clientes][editar] ' . $exception->getMessage());
    setFlash('cliente_errors', ['No fue posible cargar el cliente.']);
    redirect('index.php');
}

if (!$cliente) {
    setFlash('cliente_errors', ['El cliente solicitado no existe.']);
    redirect('index.php');
}

$data = array_merge($cliente, $old);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar cliente | Sistema de gestion</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <a class="app-title" href="../dashboard.php">Sistema de gestion</a>
        <nav class="top-nav" aria-label="Navegacion principal">
            <a href="../dashboard.php">Panel</a>
            <a href="index.php">Clientes</a>
            <a href="../logout.php">Cerrar sesion</a>
        </nav>
    </header>

    <main class="layout narrow">
        <section class="page-heading">
            <p class="eyebrow">Administracion</p>
            <h1>Editar cliente</h1>
            <p>Actualice los datos comerciales y el estado del cliente.</p>
        </section>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="actualizar.php" method="post" class="form form-panel" data-validate="cliente" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">

            <div class="form-grid">
                <div class="field">
                    <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
                    <input type="text" id="nombre" name="nombre" required value="<?= e($data['nombre'] ?? '') ?>">
                    <small class="field-error" data-error-for="nombre"></small>
                </div>

                <div class="field">
                    <label for="apellido_razon_social">Apellido / Razon social <span aria-hidden="true">*</span></label>
                    <input type="text" id="apellido_razon_social" name="apellido_razon_social" required value="<?= e($data['apellido_razon_social'] ?? '') ?>">
                    <small class="field-error" data-error-for="apellido_razon_social"></small>
                </div>

                <div class="field">
                    <label for="dni_cuit">DNI / CUIT <span aria-hidden="true">*</span></label>
                    <input type="text" id="dni_cuit" name="dni_cuit" required value="<?= e($data['dni_cuit'] ?? '') ?>">
                    <small class="field-error" data-error-for="dni_cuit"></small>
                </div>

                <div class="field">
                    <label for="telefono">Telefono <span aria-hidden="true">*</span></label>
                    <input type="text" id="telefono" name="telefono" required value="<?= e($data['telefono'] ?? '') ?>">
                    <small class="field-error" data-error-for="telefono"></small>
                </div>

                <div class="field">
                    <label for="email">Email <span aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" required value="<?= e($data['email'] ?? '') ?>">
                    <small class="field-error" data-error-for="email"></small>
                </div>

                <div class="field">
                    <label for="tipo_cliente">Tipo de cliente <span aria-hidden="true">*</span></label>
                    <select id="tipo_cliente" name="tipo_cliente" required>
                        <option value="">Seleccione</option>
                        <option value="minorista" <?= ($data['tipo_cliente'] ?? '') === 'minorista' ? 'selected' : '' ?>>Minorista</option>
                        <option value="mayorista" <?= ($data['tipo_cliente'] ?? '') === 'mayorista' ? 'selected' : '' ?>>Mayorista</option>
                    </select>
                    <small class="field-error" data-error-for="tipo_cliente"></small>
                </div>

                <div class="field">
                    <label for="estado">Estado <span aria-hidden="true">*</span></label>
                    <select id="estado" name="estado" required>
                        <option value="activo" <?= ($data['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= ($data['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                    <small class="field-error" data-error-for="estado"></small>
                </div>

                <div class="field field-full">
                    <label for="direccion">Direccion <span aria-hidden="true">*</span></label>
                    <textarea id="direccion" name="direccion" rows="4" required><?= e($data['direccion'] ?? '') ?></textarea>
                    <small class="field-error" data-error-for="direccion"></small>
                </div>
            </div>

            <div class="form-actions">
                <a class="button button-secondary" href="index.php">Cancelar</a>
                <button type="submit" class="button button-primary">Guardar cambios</button>
            </div>
        </form>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
