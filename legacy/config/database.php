<?php

declare(strict_types=1);

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $localConfigPath = __DIR__ . '/database.local.php';
    $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];

    $host = dbConfigValue('DB_HOST', $localConfig, 'localhost');
    $port = dbConfigValue('DB_PORT', $localConfig, '3306');
    $database = dbConfigValue('DB_NAME', $localConfig, 'sistema_gestion');
    $user = dbConfigValue('DB_USER', $localConfig, 'root');
    $password = dbConfigValue('DB_PASSWORD', $localConfig, '');
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function dbConfigValue(string $key, array $localConfig, string $default): string
{
    $envValue = getenv($key);

    if ($envValue !== false) {
        return (string) $envValue;
    }

    if (array_key_exists($key, $localConfig)) {
        return (string) $localConfig[$key];
    }

    return $default;
}
