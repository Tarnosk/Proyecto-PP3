<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_auth.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['administrativo']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    setFlash('cliente_errors', ['El cliente solicitado no es valido.']);
    redirect('index.php');
}

$data = [
    'nombre' => trim((string) ($_POST['nombre'] ?? '')),
    'apellido_razon_social' => trim((string) ($_POST['apellido_razon_social'] ?? '')),
    'dni_cuit' => trim((string) ($_POST['dni_cuit'] ?? '')),
    'telefono' => trim((string) ($_POST['telefono'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'direccion' => trim((string) ($_POST['direccion'] ?? '')),
    'tipo_cliente' => trim((string) ($_POST['tipo_cliente'] ?? '')),
    'estado' => trim((string) ($_POST['estado'] ?? '')),
];

$errors = [];

if (!verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
    $errors[] = 'La solicitud no es valida. Intente nuevamente.';
}

foreach (['nombre', 'apellido_razon_social', 'dni_cuit', 'telefono', 'email', 'direccion', 'tipo_cliente', 'estado'] as $field) {
    if ($data[$field] === '') {
        $errors[] = 'Debe completar todos los campos obligatorios.';
        break;
    }
}

if ($data['dni_cuit'] !== '' && !preg_match('/^[0-9.\-\s]{6,20}$/', $data['dni_cuit'])) {
    $errors[] = 'El DNI/CUIT ingresado no tiene un formato valido.';
}

if ($data['telefono'] !== '' && !preg_match('/^[0-9\s+\-()]{6,30}$/', $data['telefono'])) {
    $errors[] = 'El telefono ingresado no tiene un formato valido.';
}

if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El email ingresado no es valido.';
}

if (!in_array($data['tipo_cliente'], ['minorista', 'mayorista'], true)) {
    $errors[] = 'El tipo de cliente seleccionado no es valido.';
}

if (!in_array($data['estado'], ['activo', 'inactivo'], true)) {
    $errors[] = 'El estado seleccionado no es valido.';
}

if (!empty($errors)) {
    setFlash('cliente_errors', $errors);
    setFlash('cliente_old', $data);
    redirect('editar.php?id=' . $id);
}

try {
    $pdo = getConnection();

    $existsStmt = $pdo->prepare('SELECT id FROM clientes WHERE id = :id LIMIT 1');
    $existsStmt->execute(['id' => $id]);
    if (!$existsStmt->fetch()) {
        setFlash('cliente_errors', ['El cliente solicitado no existe.']);
        redirect('index.php');
    }

    $dniStmt = $pdo->prepare('SELECT id FROM clientes WHERE dni_cuit = :dni_cuit AND id <> :id LIMIT 1');
    $dniStmt->execute([
        'dni_cuit' => $data['dni_cuit'],
        'id' => $id,
    ]);
    if ($dniStmt->fetch()) {
        setFlash('cliente_errors', ['Ya existe otro cliente registrado con ese DNI/CUIT.']);
        setFlash('cliente_old', $data);
        redirect('editar.php?id=' . $id);
    }

    $emailStmt = $pdo->prepare('SELECT id FROM clientes WHERE email = :email AND id <> :id LIMIT 1');
    $emailStmt->execute([
        'email' => $data['email'],
        'id' => $id,
    ]);
    if ($emailStmt->fetch()) {
        setFlash('cliente_errors', ['Ya existe otro cliente registrado con ese email.']);
        setFlash('cliente_old', $data);
        redirect('editar.php?id=' . $id);
    }

    $stmt = $pdo->prepare(
        'UPDATE clientes
         SET nombre = :nombre,
             apellido_razon_social = :apellido_razon_social,
             dni_cuit = :dni_cuit,
             telefono = :telefono,
             email = :email,
             direccion = :direccion,
             tipo_cliente = :tipo_cliente,
             estado = :estado,
             updated_at = NOW()
         WHERE id = :id'
    );

    $stmt->execute([
        'nombre' => $data['nombre'],
        'apellido_razon_social' => $data['apellido_razon_social'],
        'dni_cuit' => $data['dni_cuit'],
        'telefono' => $data['telefono'],
        'email' => $data['email'],
        'direccion' => $data['direccion'],
        'tipo_cliente' => $data['tipo_cliente'],
        'estado' => $data['estado'],
        'id' => $id,
    ]);

    setFlash('cliente_success', 'Cliente actualizado correctamente.');
    redirect('index.php');
} catch (PDOException $exception) {
    $message = 'No fue posible actualizar el cliente.';

    if ($exception->getCode() === '23000') {
        $message = 'Ya existe un cliente con ese DNI/CUIT o email.';
    }

    setFlash('cliente_errors', [$message]);
    setFlash('cliente_old', $data);
    redirect('editar.php?id=' . $id);
}
