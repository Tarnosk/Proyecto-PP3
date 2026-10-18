<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['repartidor', 'administrativo']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('nuevo.php');
}

$data = [
    'cliente_id' => filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT),
    'asunto' => trim((string) ($_POST['asunto'] ?? '')),
    'prioridad' => trim((string) ($_POST['prioridad'] ?? '')),
    'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
];

$errors = [];

if (!verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
    $errors[] = 'La solicitud no es valida. Intente nuevamente.';
}

if (!$data['cliente_id'] || $data['cliente_id'] < 1) {
    $errors[] = 'Debe seleccionar un cliente.';
}

if ($data['asunto'] === '' || $data['descripcion'] === '' || $data['prioridad'] === '') {
    $errors[] = 'Debe completar todos los campos obligatorios.';
}

if (strlen($data['asunto']) > 160) {
    $errors[] = 'El asunto no puede superar los 160 caracteres.';
}

if (!in_array($data['prioridad'], ['baja', 'media', 'alta'], true)) {
    $errors[] = 'La prioridad seleccionada no es valida.';
}

if (!empty($errors)) {
    setFlash('reclamo_errors', $errors);
    setFlash('reclamo_old', $data);
    redirect('nuevo.php');
}

try {
    $pdo = getConnection();

    $clienteStmt = $pdo->prepare('SELECT id FROM clientes WHERE id = :id AND estado = :estado LIMIT 1');
    $clienteStmt->execute([
        'id' => (int) $data['cliente_id'],
        'estado' => 'activo',
    ]);

    if (!$clienteStmt->fetch()) {
        setFlash('reclamo_errors', ['El cliente seleccionado no existe o no esta activo.']);
        setFlash('reclamo_old', $data);
        redirect('nuevo.php');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO reclamos
            (cliente_id, usuario_id, asunto, descripcion, prioridad, estado, created_at, updated_at)
         VALUES
            (:cliente_id, :usuario_id, :asunto, :descripcion, :prioridad, :estado, NOW(), NOW())'
    );

    $stmt->execute([
        'cliente_id' => (int) $data['cliente_id'],
        'usuario_id' => (int) $_SESSION['id_usuario'],
        'asunto' => $data['asunto'],
        'descripcion' => $data['descripcion'],
        'prioridad' => $data['prioridad'],
        'estado' => 'abierto',
    ]);

    setFlash('reclamo_success', 'Reclamo creado correctamente.');
    redirect('index.php');
} catch (PDOException $exception) {
    error_log('[reclamos][guardar] ' . $exception->getMessage());
    setFlash('reclamo_errors', ['No fue posible crear el reclamo.']);
    setFlash('reclamo_old', $data);
    redirect('nuevo.php');
}
