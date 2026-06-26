<?php
/**
 * Protección CSRF — Samuel Te Escucha
 *
 * Genera y valida tokens CSRF para formularios POST.
 * Requiere que session_start() haya sido llamado previamente.
 */

/**
 * Genera (o recupera) un token CSRF para la sesión actual.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Devuelve un campo <input> hidden con el token CSRF.
 * Usar dentro de cada <form method="POST">.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Valida el token CSRF recibido en POST.
 * Redirige con error si es inválido.
 *
 * @param string $redirectUrl  URL a la que redirigir si falla la validación
 */
function csrf_validate(string $redirectUrl = 'index.php'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $token = $_POST['_csrf_token'] ?? '';

    if (
        empty($token) ||
        empty($_SESSION['_csrf_token']) ||
        !hash_equals($_SESSION['_csrf_token'], $token)
    ) {
        // Regenerar token para evitar reutilización
        unset($_SESSION['_csrf_token']);
        header("Location: $redirectUrl?error=csrf");
        exit;
    }
}

/**
 * Regenera el token CSRF (llamar después de procesar una acción exitosa).
 */
function csrf_regenerate(): void
{
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}
