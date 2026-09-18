-- =============================================
-- Migración: Tabla de rate limiting
-- Ejecutar en la base de datos "tecamac"
-- =============================================

CREATE TABLE IF NOT EXISTS rate_limits (
    id SERIAL PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índice para búsquedas rápidas por IP + acción
CREATE INDEX IF NOT EXISTS idx_rate_limits_ip_accion ON rate_limits(ip, accion);

-- Índice para limpieza de registros viejos
CREATE INDEX IF NOT EXISTS idx_rate_limits_created ON rate_limits(created_at);

-- Limpieza automática de registros mayores a 1 hora (ejecutar como cron o manualmente)
-- DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL '1 hour';
