<?php
/**
 * Conexión a la base de datos — Samuel Te Escucha
 *
 * Usa variables de entorno a través de includes/config.php.
 * Retrocompatible: si config.php no define las constantes, usa defaults.
 */

// Cargar configuración centralizada
$configPath = __DIR__ . '/includes/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

$host     = defined('DB_HOST')     ? DB_HOST     : 'db';
$port     = defined('DB_PORT')     ? DB_PORT     : '5432';
$dbname   = defined('DB_NAME')     ? DB_NAME     : 'tecamac';
$user     = defined('DB_USER')     ? DB_USER     : '';
$password = defined('DB_PASSWORD') ? DB_PASSWORD : '';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    http_response_code(503);
    die("Error interno del servidor. Intente más tarde.");
}
