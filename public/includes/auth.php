<?php
/**
 * Funciones de autenticación — Samuel Te Escucha
 *
 * Centraliza verificación de sesión y redirección.
 */

/**
 * Requiere que el usuario esté autenticado como empleado.
 * Si no, redirige a login.
 */
function require_auth(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['empleado_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Devuelve los datos del empleado actual de la sesión.
 *
 * @return array{id: int, usuario: string, nombre: string}
 */
function current_employee(): array
{
    return [
        'id'      => (int) ($_SESSION['empleado_id'] ?? 0),
        'usuario' => $_SESSION['empleado_usuario'] ?? '',
        'nombre'  => $_SESSION['empleado_nombre'] ?? '',
    ];
}
