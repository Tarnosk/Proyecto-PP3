<?php
require_once __DIR__ . '/auth/session.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = getFlash('login_errors', []);
$old = getFlash('login_old', []);
$reason = (string) ($_GET['reason'] ?? '');

if ($reason === 'session_expired') {
    $errors[] = 'La sesion expiro por inactividad. Inicie sesion nuevamente.';
}

if ($reason === 'session_replaced') {
    $errors[] = 'La cuenta inicio sesion en otro dispositivo. Inicie sesion nuevamente.';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesion | Sistema de gestion</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card" aria-labelledby="login-title">
            <div class="brand-block">
                <span class="brand-mark">PyB</span>
                <div>
                    <h1 id="login-title">Iniciar sesion</h1>
                    <p>Sistema de gestion comercial</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="auth/authenticate.php" method="post" class="form" data-validate="login" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="field">
                    <label for="usuario">Usuario <span aria-hidden="true">*</span></label>
                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        autocomplete="username"
                        required
                        value="<?= e($old['usuario'] ?? '') ?>"
                    >
                    <small class="field-error" data-error-for="usuario"></small>
                </div>

                <div class="field">
                    <label for="password">Contrasena <span aria-hidden="true">*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                    <small class="field-error" data-error-for="password"></small>
                </div>

                <button type="submit" class="button button-primary">Iniciar sesion</button>
            </form>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
</body>
</html>
