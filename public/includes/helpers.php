<?php
/**
 * Funciones auxiliares — Samuel Te Escucha
 *
 * Funciones de formato reutilizables en todo el proyecto.
 */

/**
 * Nombre completo desde un array con nombre, apellido_paterno, apellido_materno.
 */
function nombreCompleto(array $row): string
{
    return trim(
        ($row['nombre'] ?? '') . ' ' .
        ($row['apellido_paterno'] ?? '') . ' ' .
        ($row['apellido_materno'] ?? '')
    );
}

/**
 * Dirección formateada desde un array con campos de dirección.
 */
function direccion(array $row): string
{
    $dir  = ($row['calle'] ?? '') . ' #' . ($row['no_exterior'] ?? '');
    $dir .= ($row['no_interior'] ?? '') !== '' ? ' Int. ' . $row['no_interior'] : '';
    $dir .= ', Col. ' . ($row['colonia'] ?? '');
    $dir .= ', ' . ($row['municipio'] ?? '');
    $dir .= ', CP ' . ($row['codigo_postal'] ?? '');
    return $dir;
}

/**
 * Genera folio con prefijo y padding.
 * Ej: folio('CITA', 42) => "CITA-000042"
 */
function folio(string $prefix, int $id, int $pad = 6): string
{
    return $prefix . '-' . str_pad((string) $id, $pad, '0', STR_PAD_LEFT);
}

/**
 * Hora formateada a HH:MM desde un valor TIME de PostgreSQL.
 */
function horaCorta(?string $hora): string
{
    return substr((string) ($hora ?? ''), 0, 5);
}

/**
 * Fecha formateada a dd/mm/yyyy.
 */
function fechaDisplay(?string $fecha): string
{
    if (!$fecha) return '';
    $ts = strtotime($fecha);
    return $ts ? date('d/m/Y', $ts) : $fecha;
}
