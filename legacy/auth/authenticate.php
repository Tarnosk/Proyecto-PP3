<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

const MAX_FAILED_LOGIN_ATTEMPTS = 3;
const LOGIN_LOCK_MINUTES = 15;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php');
}

$usuario = trim((string) ($_POST['usuario'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$csrfToken = (string) ($_POST['csrf_token'] ?? '');
$errors = [];

if (!verifyCsrfToken($csrfToken)) {
    $errors[] = 'La solicitud no es valida. Intente nuevamente.';
}

if ($usuario === '' || $password === '') {
    $errors[] = 'Debe completar usuario y contrasena.';
}

if (!empty($errors)) {
    setFlash('login_errors', $errors);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

try {
    $pdo = getConnection();
} catch (PDOException $exception) {
    error_log('[login][conexion] ' . $exception->getMessage());
    setFlash('login_errors', ['Error de conexion con la base de datos.']);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

try {
    $stmt = $pdo->prepare(
        'SELECT id, usuario, password_hash, nombre, rol, estado, intentos_fallidos, bloqueado_hasta
         FROM usuarios
         WHERE usuario = :usuario
         LIMIT 1'
    );
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch();

} catch (PDOException $exception) {
    error_log('[login][consulta_usuarios] ' . $exception->getMessage());
    setFlash('login_errors', ['Error al consultar usuarios.']);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

if (!$user || $user['estado'] !== 'activo') {
    setFlash('login_errors', ['Las credenciales ingresadas son incorrectas.']);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

if (!empty($user['bloqueado_hasta']) && strtotime((string) $user['bloqueado_hasta']) > time()) {
    setFlash('login_errors', ['El usuario esta bloqueado temporalmente. Intente nuevamente mas tarde.']);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

if (!password_verify($password, $user['password_hash'])) {
    $failedAttempts = (int) $user['intentos_fallidos'] + 1;

    try {
        if ($failedAttempts >= MAX_FAILED_LOGIN_ATTEMPTS) {
            $lockStmt = $pdo->prepare(
                'UPDATE usuarios
                 SET intentos_fallidos = :intentos_fallidos,
                     bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ' . LOGIN_LOCK_MINUTES . ' MINUTE),
                     ultimo_intento_fallido = NOW()
                 WHERE id = :id'
            );
            $lockStmt->execute([
                'intentos_fallidos' => $failedAttempts,
                'id' => (int) $user['id'],
            ]);

            setFlash('login_errors', ['Demasiados intentos fallidos. Usuario bloqueado por ' . LOGIN_LOCK_MINUTES . ' minutos.']);
        } else {
            $attemptStmt = $pdo->prepare(
                'UPDATE usuarios
                 SET intentos_fallidos = :intentos_fallidos,
                     ultimo_intento_fallido = NOW()
                 WHERE id = :id'
            );
            $attemptStmt->execute([
                'intentos_fallidos' => $failedAttempts,
                'id' => (int) $user['id'],
            ]);

            $remainingAttempts = MAX_FAILED_LOGIN_ATTEMPTS - $failedAttempts;
            setFlash('login_errors', ['Las credenciales ingresadas son incorrectas. Intentos restantes: ' . $remainingAttempts . '.']);
        }
    } catch (PDOException $exception) {
        error_log('[login][intentos_fallidos] ' . $exception->getMessage());
        setFlash('login_errors', ['Las credenciales ingresadas son incorrectas.']);
    }

    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

try {
    $resetStmt = $pdo->prepare(
        'UPDATE usuarios
         SET intentos_fallidos = 0,
             bloqueado_hasta = NULL,
             ultimo_intento_fallido = NULL
         WHERE id = :id'
    );
    $resetStmt->execute(['id' => (int) $user['id']]);
} catch (PDOException $exception) {
    error_log('[login][reset_intentos] ' . $exception->getMessage());
    setFlash('login_errors', ['Error al actualizar el estado de inicio de sesion.']);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

try {
    $logStmt = $pdo->prepare(
        'INSERT INTO login_logs (usuario_id, fecha_hora, ip)
         VALUES (:usuario_id, NOW(), :ip)'
    );
    $logStmt->execute([
        'usuario_id' => (int) $user['id'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
} catch (PDOException $exception) {
    error_log('[login][registro_login] ' . $exception->getMessage());
    setFlash('login_errors', ['Error al registrar el inicio de sesion.']);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

session_regenerate_id(true);

$sessionToken = session_id();

try {
    $sessionStmt = $pdo->prepare(
        'UPDATE usuarios
         SET current_session_id = :current_session_id
         WHERE id = :id'
    );
    $sessionStmt->execute([
        'current_session_id' => $sessionToken,
        'id' => (int) $user['id'],
    ]);
} catch (PDOException $exception) {
    error_log('[login][sesion_unica] ' . $exception->getMessage());
    setFlash('login_errors', ['Error al registrar la sesion del usuario.']);
    setFlash('login_old', ['usuario' => $usuario]);
    redirect('../login.php');
}

$_SESSION['id_usuario'] = (int) $user['id'];
$_SESSION['nombre_usuario'] = $user['nombre'];
$_SESSION['rol'] = $user['rol'];
$_SESSION['session_token'] = $sessionToken;
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

redirect('../dashboard.php');
