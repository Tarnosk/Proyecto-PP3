<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Este diagnostico es solo para CLI.\n";
    exit(1);
}

function printCheck(string $label, bool $ok, string $detail = ''): void
{
    echo ($ok ? 'OK    ' : 'ERROR ') . $label;

    if ($detail !== '') {
        echo ' - ' . $detail;
    }

    echo PHP_EOL;
}

printCheck('PHP funcionando', true, PHP_VERSION);
printCheck('PDO disponible', extension_loaded('PDO'));
printCheck('pdo_mysql disponible', extension_loaded('pdo_mysql'));

if (!extension_loaded('PDO') || !extension_loaded('pdo_mysql')) {
    exit(1);
}

try {
    $pdo = getConnection();
    printCheck('Conexion con MySQL', true);
} catch (PDOException $exception) {
    printCheck('Conexion con MySQL', false, $exception->getMessage());
    exit(1);
}

try {
    $database = getenv('DB_NAME') ?: 'sistema_gestion';
    $stmt = $pdo->prepare('SELECT DATABASE() AS db_actual');
    $stmt->execute();
    $currentDatabase = (string) ($stmt->fetch()['db_actual'] ?? '');
    printCheck('Base sistema_gestion accesible', $currentDatabase === $database, 'base actual: ' . $currentDatabase);

    foreach (['usuarios', 'clientes', 'login_logs'] as $table) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM information_schema.tables
             WHERE table_schema = :database AND table_name = :table'
        );
        $stmt->execute([
            'database' => $database,
            'table' => $table,
        ]);
        printCheck('Tabla ' . $table . ' existente', (int) $stmt->fetch()['total'] === 1);
    }

    foreach (['intentos_fallidos', 'bloqueado_hasta', 'ultimo_intento_fallido', 'current_session_id'] as $column) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM information_schema.columns
             WHERE table_schema = :database
               AND table_name = :table
               AND column_name = :column'
        );
        $stmt->execute([
            'database' => $database,
            'table' => 'usuarios',
            'column' => $column,
        ]);
        printCheck('Columna usuarios.' . $column . ' existente', (int) $stmt->fetch()['total'] === 1);
    }

    foreach (['cliente_id', 'usuario_id', 'asunto', 'descripcion', 'prioridad', 'estado'] as $column) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM information_schema.columns
             WHERE table_schema = :database
               AND table_name = :table
               AND column_name = :column'
        );
        $stmt->execute([
            'database' => $database,
            'table' => 'reclamos',
            'column' => $column,
        ]);
        printCheck('Columna reclamos.' . $column . ' existente', (int) $stmt->fetch()['total'] === 1);
    }

    $stmt = $pdo->query(
        "SELECT usuario, estado
         FROM usuarios
         WHERE usuario IN ('admin', 'repartidor', 'contador', 'inactivo')
         ORDER BY usuario"
    );
    $users = $stmt->fetchAll();
    printCheck('Usuarios de prueba existentes', count($users) === 4);

    foreach ($users as $user) {
        printCheck('Usuario ' . $user['usuario'], true, 'estado: ' . $user['estado']);
    }
} catch (PDOException $exception) {
    printCheck('Revision de estructura/datos', false, $exception->getMessage());
    exit(1);
}
