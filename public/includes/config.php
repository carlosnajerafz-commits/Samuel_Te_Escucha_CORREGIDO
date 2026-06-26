<?php
/**
 * Configuración centralizada — Samuel Te Escucha
 *
 * Lee variables de entorno del archivo .env o del sistema.
 * Proporciona constantes y funciones de configuración.
 */

// Cargar .env solo si no estamos en Docker (Docker inyecta env vars directamente)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Obtener variable de entorno con valor por defecto.
 */
function env(string $key, string $default = ''): string
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ── Base de datos ──
define('DB_HOST',     env('DB_HOST', 'db'));
define('DB_PORT',     env('DB_PORT', '5432'));
define('DB_NAME',     env('DB_NAME', 'tecamac'));
define('DB_USER',     env('DB_USER', 'tecamac_user'));
define('DB_PASSWORD', env('DB_PASSWORD', 'tecamac_pass'));

// ── Aplicación ──
define('APP_ENV',   env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', 'false') === 'true');
