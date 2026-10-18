<?php
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            'UPDATE usuarios
             SET current_session_id = NULL
             WHERE id = :id AND current_session_id = :current_session_id'
        );
        $stmt->execute([
            'id' => (int) $_SESSION['id_usuario'],
            'current_session_id' => (string) ($_SESSION['session_token'] ?? ''),
        ]);
    } catch (PDOException $exception) {
        error_log('[logout][sesion_unica] ' . $exception->getMessage());
    }
}

clearSession();

redirect('login.php');
