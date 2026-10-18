<?php
require_once __DIR__ . '/auth/require_auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$sAdmin = $_SESSION['rol'] === 'administrativo';
$isRepartidor = $_SESSION['rol'] === 'repartidor';
$permissionError = getFlash('permission_error');
$clientes = [];
$totalClientes = 0;
$totalMayoristas = 0;
$totalMinoristas = 0;
$totalReclamos = 0;

if ($isAdmin) {
    try {
        $pdo = getConnection();

        $totalClientes = (int) $pdo->query('SELECT COUNT(*) AS total FROM clientes')->fetch()['total'];
        $totalMayoristas = (int) $pdo->query("SELECT COUNT(*) AS total FROM clientes WHERE tipo_cliente = 'mayorista'")->fetch()['total'];
        $totalMinoristas = (int) $pdo->query("SELECT COUNT(*) AS total FROM clientes WHERE tipo_cliente = 'minorista'")->fetch()['total'];
        $totalReclamos = (int) $pdo->query("SELECT COUNT(*) AS total FROM reclamos WHERE estado <> 'cerrado'")->fetch()['total'];

        $stmt = $pdo->query(
            'SELECT id, nombre, apellido_razon_social, dni_cuit, telefono, email, direccion, tipo_cliente, estado
             FROM clientes
             ORDER BY updated_at DESC
             LIMIT 3'
        );
        $clientes = $stmt->fetchAll();
    } catch (PDOException $exception) {
        error_log('[dashboard][admin] ' . $exception->getMessage());
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel principal | Sistema de gestion</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="erp-body">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <strong>PyB Pigue ERP</strong>
            <span>Distribuidora comercial</span>
        </div>

        <div class="user-card">
            <span>Usuario activo</span>
            <strong><?= e($_SESSION['nombre_usuario']) ?></strong>
            <small><?= e($_SESSION['rol']) ?></small>
        </div>

        <nav class="side-nav" aria-label="Navegacion principal">
            <a class="active" href="dashboard.php">Inicio / Panel</a>
            <a href="ventas/index.php">Ventas y Pedidos</a>
            <a href="stock/index.php">Inventario y Stock</a>
            <?php if ($isAdmin): ?>
                <a href="clientes/index.php">Clientes, Prov. y Compras</a>
                <a href="reclamos/index.php">Reclamos</a>
                <a href="#">Logistica y Transporte</a>
                <a href="#">Finanzas y Tesoreria</a>
                <a href="#">Lista de Usuarios</a>
                <a href="#">Roles y Accesos</a>
            <?php elseif ($isRepartidor): ?>
                <a href="clientes/index.php">Clientes</a>
                <a href="reclamos/nuevo.php">Crear reclamo</a>
            <?php else: ?>
                <a href="#">Reportes contables</a>
                <a href="#">Finanzas y Tesoreria</a>
            <?php endif; ?>
            <a href="#">Mi Perfil</a>
            <a href="logout.php">Cerrar sesion</a>
        </nav>
    </aside>

    <main class="erp-main">
        <?php if ($permissionError): ?>
            <div class="alert alert-error" role="alert">
                <p><?= e($permissionError) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <section class="erp-heading">
                <div>
                    <p class="eyebrow">Panel administrativo</p>
                    <h1>Fichas, Proveedores y Compras</h1>
                    <p>Gestion de clientes y tarifas diferenciadas, base fiscal de proveedores y compras con ingreso automatico al inventario.</p>
                </div>
                <a class="button button-primary" href="clientes/nuevo.php">+ Nueva Ficha Cliente</a>
            </section>

            <section class="module-tabs" aria-label="Modulos administrativos">
                <a class="active" href="clientes/index.php">Fichas de Clientes (<?= $totalClientes ?>)</a>
                <a href="#">Proveedores / Fabricas (0)</a>
                <a href="#">Ingreso de Compras (0)</a>
            </section>

            <section class="search-panel">
                <h2>Buscador y Ruteo de Clientes</h2>
                <form action="clientes/index.php" method="get" class="dashboard-search">
                    <div class="field">
                        <label for="q">Buscar por Razon Social, CUIT o Direccion</label>
                        <input type="search" id="q" name="q" placeholder="Escriba nombre, CUIT o direccion de comercio...">
                    </div>
                    <div class="field">
                        <label for="zona">Filtrar por Zona de Reparto</label>
                        <select id="zona" name="zona">
                            <option>Todas las Zonas</option>
                            <option>Zona Norte</option>
                            <option>Zona Centro</option>
                            <option>Zona Sur</option>
                        </select>
                    </div>
                    <button type="submit" class="button button-primary">Buscar</button>
                </form>
            </section>

            <section class="summary-strip" aria-label="Resumen administrativo">
                <article>
                    <span>Clientes autorizados</span>
                    <strong><?= $totalClientes ?></strong>
                </article>
                <article>
                    <span>Mayoristas</span>
                    <strong><?= $totalMayoristas ?></strong>
                </article>
                <article>
                    <span>Minoristas</span>
                    <strong><?= $totalMinoristas ?></strong>
                </article>
                <article>
                    <span>Reclamos abiertos</span>
                    <strong><?= $totalReclamos ?></strong>
                </article>
            </section>

            <section class="client-section">
                <div class="section-title">
                    <h2>Base de Clientes Autorizados</h2>
                    <span>Mostrando <?= count($clientes) ?> de <?= $totalClientes ?> fichas</span>
                </div>

                <div class="client-card-grid">
                    <?php if (empty($clientes)): ?>
                        <p class="empty-state">Todavia no hay clientes registrados.</p>
                    <?php else: ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <article class="client-card">
                                <div class="client-card-head">
                                    <h3><?= e($cliente['apellido_razon_social']) ?> <?= e($cliente['nombre']) ?></h3>
                                    <span class="type-badge type-<?= e($cliente['tipo_cliente']) ?>"><?= e($cliente['tipo_cliente']) ?></span>
                                </div>
                                <p>Identificacion fiscal: <?= e($cliente['dni_cuit']) ?></p>
                                <p><?= e($cliente['direccion']) ?></p>
                                <p><?= e($cliente['telefono']) ?> · <?= e($cliente['email']) ?></p>
                                <div class="tariff-box">
                                    <span>Tarifa asignada</span>
                                    <strong><?= e($cliente['tipo_cliente']) ?></strong>
                                </div>
                                <a class="button button-secondary button-small" href="clientes/editar.php?id=<?= (int) $cliente['id'] ?>">Editar Ficha</a>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel-grid sprint-grid" aria-label="Accesos de sprint dos">
                <article class="action-panel">
                    <div>
                        <h2>Reclamos</h2>
                        <p>Crear y consultar reclamos asociados a clientes.</p>
                    </div>
                    <a class="button button-primary" href="reclamos/nuevo.php">Crear reclamo</a>
                </article>
                <article class="action-panel">
                    <div>
                        <h2>Seguridad de sesion</h2>
                        <p>Sesion unica por cuenta y expiracion automatica por inactividad.</p>
                    </div>
                    <a class="button button-secondary" href="logout.php">Cerrar sesion</a>
                </article>
            </section>
        <?php elseif ($isRepartidor): ?>
            <section class="erp-heading">
                <div>
                    <p class="eyebrow">Panel repartidor</p>
                    <h1>Bienvenido, <?= e($_SESSION['nombre_usuario']) ?></h1>
                    <p>Accesos comerciales para consultar clientes, crear reclamos, revisar ventas y ver stock.</p>
                </div>
            </section>

            <section class="panel-grid" aria-label="Accesos de repartidor">
                <article class="action-panel">
                    <div>
                        <h2>Clientes</h2>
                        <p>Buscar y consultar clientes registrados.</p>
                    </div>
                    <a class="button button-primary" href="clientes/index.php">Ver clientes</a>
                </article>
                <article class="action-panel">
                    <div>
                        <h2>Ventas y Pedidos</h2>
                        <p>Acceso operativo para proximas cargas de pedidos.</p>
                    </div>
                    <a class="button button-primary" href="ventas/index.php">Abrir</a>
                </article>
                <article class="action-panel">
                    <div>
                        <h2>Reclamos</h2>
                        <p>Cargar reclamos asociados a clientes registrados.</p>
                    </div>
                    <a class="button button-primary" href="reclamos/nuevo.php">Crear reclamo</a>
                </article>
                <article class="action-panel">
                    <div>
                        <h2>Inventario y Stock</h2>
                        <p>Consulta del estado de productos y existencias.</p>
                    </div>
                    <a class="button button-primary" href="stock/index.php">Abrir</a>
                </article>
            </section>
        <?php else: ?>
            <section class="erp-heading">
                <div>
                    <p class="eyebrow">Panel contador</p>
                    <h1>Bienvenido, <?= e($_SESSION['nombre_usuario']) ?></h1>
                    <p>Acceso contable preparado para reportes, finanzas y tesoreria.</p>
                </div>
            </section>

            <section class="panel-grid" aria-label="Accesos de contador">
                <article class="action-panel">
                    <div>
                        <h2>Reportes contables</h2>
                        <p>Modulo reservado para reportes y conciliaciones contables.</p>
                    </div>
                    <a class="button button-secondary" href="#">Pendiente</a>
                </article>
                <article class="action-panel">
                    <div>
                        <h2>Finanzas y Tesoreria</h2>
                        <p>Acceso preparado para futuras operaciones contables.</p>
                    </div>
                    <a class="button button-secondary" href="#">Pendiente</a>
                </article>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
