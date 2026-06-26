<?php
/**
 * Rate Limiting básico — Samuel Te Escucha
 *
 * Limita acciones por IP usando la base de datos PostgreSQL.
 * Requiere la tabla `rate_limits` (ver migración).
 */

/**
 * Verifica si una acción está dentro del límite permitido.
 *
 * @param PDO    $pdo        Conexión PDO
 * @param string $action     Identificador de la acción (ej: 'login', 'cita', 'queja')
 * @param int    $maxAttempts Máximo de intentos permitidos en la ventana
 * @param int    $windowSecs  Ventana de tiempo en segundos (default: 60)
 * @return bool  true si la acción está permitida, false si excede el límite
 */
function rate_limit_check(PDO $pdo, string $action, int $maxAttempts, int $windowSecs = 60): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    try {
        // Limpiar registros viejos (fuera de la ventana)
        $stmtClean = $pdo->prepare("
            DELETE FROM rate_limits
            WHERE accion = :action
              AND ip = :ip
              AND created_at < NOW() - INTERVAL ':window seconds'
        ");
        // PostgreSQL no permite parámetros en INTERVAL, usar concatenación segura
        $stmtClean = $pdo->prepare("
            DELETE FROM rate_limits
            WHERE accion = :action
              AND ip = :ip
              AND created_at < NOW() - make_interval(secs => :window)
        ");
        $stmtClean->execute([
            ':action' => $action,
            ':ip'     => $ip,
            ':window' => $windowSecs,
        ]);

        // Contar intentos en la ventana actual
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM rate_limits
            WHERE accion = :action
              AND ip = :ip
              AND created_at >= NOW() - make_interval(secs => :window)
        ");
        $stmtCount->execute([
            ':action' => $action,
            ':ip'     => $ip,
            ':window' => $windowSecs,
        ]);

        $count = (int) $stmtCount->fetchColumn();

        if ($count >= $maxAttempts) {
            return false; // Límite excedido
        }

        // Registrar este intento
        $stmtInsert = $pdo->prepare("
            INSERT INTO rate_limits (ip, accion) VALUES (:ip, :action)
        ");
        $stmtInsert->execute([
            ':ip'     => $ip,
            ':action' => $action,
        ]);

        return true;

    } catch (PDOException $e) {
        // Si la tabla no existe o hay error, permitir (fail open)
        // En producción considerar fail closed
        return true;
    }
}

/**
 * Verifica rate limit y redirige si excede.
 *
 * @param PDO    $pdo
 * @param string $action
 * @param int    $maxAttempts
 * @param int    $windowSecs
 * @param string $redirectUrl  URL de redirección con parámetro de error
 */
function rate_limit_or_redirect(
    PDO $pdo,
    string $action,
    int $maxAttempts,
    int $windowSecs,
    string $redirectUrl
): void {
    if (!rate_limit_check($pdo, $action, $maxAttempts, $windowSecs)) {
        header("Location: $redirectUrl");
        exit;
    }
}
