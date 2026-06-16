-- =============================================
-- Migración: Tabla de bloqueos de citas
-- Ejecutar en la base de datos "tecamac"
-- =============================================

CREATE TABLE IF NOT EXISTS bloqueos_cita (
    id SERIAL PRIMARY KEY,
    fecha DATE NOT NULL,
    hora TIME DEFAULT NULL,
    dia_completo BOOLEAN DEFAULT FALSE,
    motivo VARCHAR(255) DEFAULT '',
    empleado_id INTEGER REFERENCES empleados(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índice para búsquedas rápidas por fecha
CREATE INDEX IF NOT EXISTS idx_bloqueos_fecha ON bloqueos_cita(fecha);

-- Índice compuesto para validaciones de hora+fecha
CREATE INDEX IF NOT EXISTS idx_bloqueos_fecha_hora ON bloqueos_cita(fecha, hora);
