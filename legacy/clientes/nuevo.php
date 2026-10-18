<?php
require_once __DIR__ . '/../auth/require_auth.php';

requireRole(['administrativo']);

$errors = getFlash('cliente_errors', []);
$success = getFlash('cliente_success');
$old = getFlash('cliente_old', []);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo cliente | Sistema de gestion</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <a class="app-title" href="../dashboard.php">Sistema de gestion</a>
        <nav class="top-nav" aria-label="Navegacion principal">
            <a href="../dashboard.php">Panel</a>
            <a href="../logout.php">Cerrar sesion</a>
        </nav>
    </header>

    <main class="layout narrow">
        <section class="page-heading">
            <p class="eyebrow">Clientes</p>
            <h1>Nuevo cliente</h1>
            <p>Complete los datos obligatorios para registrar un cliente activo.</p>
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

        <form action="guardar.php" method="post" class="form form-panel" data-validate="cliente" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-grid">
                <div class="field">
                    <label for="nombre">Nombre <span aria-hidden="true">*</span></label>
                    <input type="text" id="nombre" name="nombre" required value="<?= e($old['nombre'] ?? '') ?>">
                    <small class="field-error" data-error-for="nombre"></small>
                </div>

                <div class="field">
                    <label for="apellido_razon_social">Apellido / Razon social <span aria-hidden="true">*</span></label>
                    <input type="text" id="apellido_razon_social" name="apellido_razon_social" required value="<?= e($old['apellido_razon_social'] ?? '') ?>">
                    <small class="field-error" data-error-for="apellido_razon_social"></small>
                </div>

                <div class="field">
                    <label for="dni_cuit">DNI / CUIT <span aria-hidden="true">*</span></label>
                    <input type="text" id="dni_cuit" name="dni_cuit" required value="<?= e($old['dni_cuit'] ?? '') ?>">
                    <small class="field-error" data-error-for="dni_cuit"></small>
                </div>

                <div class="field">
                    <label for="telefono">Telefono <span aria-hidden="true">*</span></label>
                    <input type="text" id="telefono" name="telefono" required value="<?= e($old['telefono'] ?? '') ?>">
                    <small class="field-error" data-error-for="telefono"></small>
                </div>

                <div class="field">
                    <label for="email">Email <span aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" required value="<?= e($old['email'] ?? '') ?>">
                    <small class="field-error" data-error-for="email"></small>
                </div>

                <div class="field">
                    <label for="tipo_cliente">Tipo de cliente <span aria-hidden="true">*</span></label>
                    <select id="tipo_cliente" name="tipo_cliente" required>
                        <option value="">Seleccione</option>
                        <option value="minorista" <?= ($old['tipo_cliente'] ?? '') === 'minorista' ? 'selected' : '' ?>>Minorista</option>
                        <option value="mayorista" <?= ($old['tipo_cliente'] ?? '') === 'mayorista' ? 'selected' : '' ?>>Mayorista</option>
                    </select>
                    <small class="field-error" data-error-for="tipo_cliente"></small>
                </div>

                <div class="field field-full">
                    <label for="direccion">Direccion <span aria-hidden="true">*</span></label>
                    <textarea id="direccion" name="direccion" rows="4" required><?= e($old['direccion'] ?? '') ?></textarea>
                    <small class="field-error" data-error-for="direccion"></small>
                </div>
            </div>

            <div class="form-actions">
                <a class="button button-secondary" href="../dashboard.php">Cancelar</a>
                <button type="submit" class="button button-primary">Registrar cliente</button>
            </div>
        </form>
    </main>
    <script src="../assets/js/app.js"></script>
</body>
</html>
