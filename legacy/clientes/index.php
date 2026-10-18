<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['repartidor', 'administrativo']);

$isAdmin = $_SESSION['rol'] === 'administrativo';
$q = trim((string) ($_GET['q'] ?? ''));
$success = getFlash('cliente_success');
$errors = getFlash('cliente_errors', []);
$clientes = [];

try {
    $pdo = getConnection();

    $sql = 'SELECT id, nombre, apellido_razon_social, dni_cuit, telefono, email, direccion, tipo_cliente, estado, updated_at
            FROM clientes';
    $params = [];

    if ($q !== '') {
        $sql .= ' WHERE nombre LIKE :q_nombre
                  OR apellido_razon_social LIKE :q_apellido
                  OR dni_cuit LIKE :q_dni
                  OR email LIKE :q_email
                  OR telefono LIKE :q_telefono
                  OR direccion LIKE :q_direccion';
        $search = '%' . $q . '%';
        $params = [
            'q_nombre' => $search,
            'q_apellido' => $search,
            'q_dni' => $search,
            'q_email' => $search,
            'q_telefono' => $search,
            'q_direccion' => $search,
        ];
    }

    $sql .= ' ORDER BY apellido_razon_social ASC, nombre ASC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll();
} catch (PDOException $exception) {
    error_log('[clientes][listado] ' . $exception->getMessage());
    $errors[] = 'No fue posible cargar el listado de clientes.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes | Sistema de gestion</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header class="topbar">
        <a class="app-title" href="../dashboard.php">Sistema de gestion</a>
        <nav class="top-nav" aria-label="Navegacion principal">
            <a href="../dashboard.php">Panel</a>
            <?php if ($isAdmin): ?>
                <a href="nuevo.php">Nuevo cliente</a>
            <?php endif; ?>
            <a href="../logout.php">Cerrar sesion</a>
        </nav>
    </header>

    <main class="layout">
        <section class="page-heading">
            <p class="eyebrow">Administracion</p>
            <h1>Clientes</h1>
            <p>Listado y busqueda de clientes registrados.</p>
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

        <form action="index.php" method="get" class="toolbar-form">
            <div class="field search-field">
                <label for="q">Buscar cliente</label>
                <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre, DNI/CUIT, email o telefono">
            </div>
            <div class="toolbar-actions">
                <button type="submit" class="button button-primary">Buscar</button>
                <a class="button button-secondary" href="index.php">Limpiar</a>
            </div>
        </form>

        <section class="table-panel" aria-label="Listado de clientes">
            <?php if (empty($clientes)): ?>
                <p class="empty-state">No se encontraron clientes.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>DNI/CUIT</th>
                                <th>Contacto</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <?php if ($isAdmin): ?>
                                    <th>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($cliente['apellido_razon_social']) ?>, <?= e($cliente['nombre']) ?></strong>
                                        <span><?= e($cliente['email']) ?></span>
                                    </td>
                                    <td><?= e($cliente['dni_cuit']) ?></td>
                                    <td><?= e($cliente['telefono']) ?></td>
                                    <td><span class="type-badge type-<?= e($cliente['tipo_cliente']) ?>"><?= e($cliente['tipo_cliente']) ?></span></td>
                                    <td><?= e($cliente['estado']) ?></td>
                                    <?php if ($isAdmin): ?>
                                        <td>
                                            <a class="button button-secondary button-small" href="editar.php?id=<?= (int) $cliente['id'] ?>">Editar</a>
                                        </td>
                                    <?php endif; ?>
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
