<?php
/**
 * Headers de seguridad — Samuel Te Escucha
 *
 * Incluir al inicio de cada página que renderiza HTML.
 * Estos headers protegen contra clickjacking, MIME sniffing, etc.
 */

// Prevenir que la página sea embebida en iframes (clickjacking)
header('X-Frame-Options: SAMEORIGIN');

// Prevenir MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Política de referrer
header('Referrer-Policy: strict-origin-when-cross-origin');

// Protección XSS del navegador (legacy pero útil)
header('X-XSS-Protection: 1; mode=block');

// Política de permisos (deshabilitar APIs innecesarias)
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

// Content Security Policy básica
// Permitir scripts/estilos inline (necesario por el código actual) + CDNs usadas
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self';");

// Strict Transport Security (solo para HTTPS en producción)
if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
