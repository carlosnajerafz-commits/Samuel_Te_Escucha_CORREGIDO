-- Migración: Comedor Solidario
-- Ejecutar en la base de datos existente

-- Si la tabla registros_comedor ya existe, agregar columnas de dirección
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS seccion_electoral VARCHAR(10) DEFAULT '';
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS categoria_vulnerable VARCHAR(150) DEFAULT '';
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS calle VARCHAR(150) DEFAULT '';
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS no_exterior VARCHAR(20) DEFAULT '';
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS no_interior VARCHAR(20) DEFAULT '';
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS colonia VARCHAR(150) DEFAULT '';
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS municipio VARCHAR(150) DEFAULT '';
ALTER TABLE registros_comedor ADD COLUMN IF NOT EXISTS codigo_postal VARCHAR(10) DEFAULT '';

ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS tipo_comedor VARCHAR(100) DEFAULT '';
ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS grupo_dirigido VARCHAR(150) DEFAULT '';
ALTER TABLE eventos_comedor ADD COLUMN IF NOT EXISTS menu TEXT DEFAULT '';

CREATE TABLE IF NOT EXISTS eventos_comedor (
    id SERIAL PRIMARY KEY,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    lugar VARCHAR(255) DEFAULT '',
    descripcion TEXT DEFAULT '',
    cupo_maximo INT DEFAULT 0,
    tipo_comedor VARCHAR(100) DEFAULT '',
    grupo_dirigido VARCHAR(150) DEFAULT '',
    menu TEXT DEFAULT '',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_eventos_comedor_fecha ON eventos_comedor(fecha);

CREATE TABLE IF NOT EXISTS registros_comedor (
    id SERIAL PRIMARY KEY,
    evento_id INTEGER REFERENCES eventos_comedor(id) ON DELETE SET NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    celular_1 VARCHAR(15) NOT NULL,
    celular_2 VARCHAR(15) DEFAULT '',
    correo VARCHAR(150) DEFAULT '',
    seccion_electoral VARCHAR(10) DEFAULT '',
    calle VARCHAR(150) DEFAULT '',
    no_exterior VARCHAR(20) DEFAULT '',
    no_interior VARCHAR(20) DEFAULT '',
    colonia VARCHAR(150) DEFAULT '',
    municipio VARCHAR(150) DEFAULT '',
    codigo_postal VARCHAR(10) DEFAULT '',
    numero_personas INT DEFAULT 1,
    categoria_vulnerable VARCHAR(150) DEFAULT '',
    observaciones TEXT DEFAULT '',
    estatus VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
