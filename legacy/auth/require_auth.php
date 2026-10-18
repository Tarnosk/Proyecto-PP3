<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

const SESSION_TIMEOUT_SECONDS = 7200;

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(relativePathToLogin());
    }

    if (isSessionExpired()) {
        clearSession();
        redirect(relativePathToLogin() . '?reason=session_expired');
    }

    if (!isCurrentSessionValid()) {
        clearSession();
        redirect(relativePathToLogin() . '?reason=session_replaced');
    }

    $_SESSION['last_activity'] = time();
}

function requireRole(array $allowedRoles): void
{
    requireLogin();

    if (!in_array($_SESSION['rol'], $allowedRoles, true)) {
        setFlash('permission_error', 'No tiene permisos para acceder a esa pantalla.');
        redirect(relativePathToDashboard());
    }
}

function relativePathToLogin(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return isNestedScript($script) ? '../login.php' : 'login.php';
}

function relativePathToDashboard(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return isNestedScript($script) ? '../dashboard.php' : 'dashboard.php';
}

function isNestedScript(string $script): bool
{
    foreach (['/clientes/', '/ventas/', '/stock/', '/reclamos/'] as $path) {
        if (str_contains($script, $path)) {
            return true;
        }
    }

    return false;
}

function isSessionExpired(): bool
{
    $lastActivity = (int) ($_SESSION['last_activity'] ?? time());
    return (time() - $lastActivity) > SESSION_TIMEOUT_SECONDS;
}

function isCurrentSessionValid(): bool
{
    if (empty($_SESSION['session_token'])) {
        return false;
    }

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            'SELECT current_session_id
             FROM usuarios
             WHERE id = :id AND estado = :estado
             LIMIT 1'
        );
        $stmt->execute([
            'id' => (int) $_SESSION['id_usuario'],
            'estado' => 'activo',
        ]);
        $user = $stmt->fetch();
    } catch (PDOException $exception) {
        error_log('[auth][sesion_unica] ' . $exception->getMessage());
        return false;
    }

    return $user && hash_equals((string) $user['current_session_id'], (string) $_SESSION['session_token']);
}
