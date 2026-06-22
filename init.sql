DROP TABLE IF EXISTS registros_comedor CASCADE;
DROP TABLE IF EXISTS eventos_comedor CASCADE;
DROP TABLE IF EXISTS bloqueos_cita CASCADE;
DROP TABLE IF EXISTS citas CASCADE;
DROP TABLE IF EXISTS quejas CASCADE;
DROP TABLE IF EXISTS registros_apoyos CASCADE;
DROP TABLE IF EXISTS apoyos CASCADE;
DROP TABLE IF EXISTS codigos_postales CASCADE;
DROP TABLE IF EXISTS empleados CASCADE;

CREATE TABLE empleados (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    nombre_completo VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE citas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    celular_1 VARCHAR(15) NOT NULL,
    celular_2 VARCHAR(15) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    calle VARCHAR(150) NOT NULL,
    no_exterior VARCHAR(20) NOT NULL,
    no_interior VARCHAR(20) DEFAULT '',
    colonia VARCHAR(150) NOT NULL,
    municipio VARCHAR(150) NOT NULL,
    codigo_postal VARCHAR(10) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo TEXT DEFAULT '',
    ine_path TEXT DEFAULT NULL,
    estatus VARCHAR(20) NOT NULL DEFAULT 'solicitada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE quejas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    celular_1 VARCHAR(15) NOT NULL,
    celular_2 VARCHAR(15) DEFAULT '',
    correo VARCHAR(150) NOT NULL,
    seccion_electoral VARCHAR(10) NOT NULL,
    calle VARCHAR(150) NOT NULL,
    no_exterior VARCHAR(20) NOT NULL,
    no_interior VARCHAR(20) DEFAULT '',
    colonia VARCHAR(150) NOT NULL,
    municipio VARCHAR(150) NOT NULL,
    codigo_postal VARCHAR(10) NOT NULL,
    tipo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    evidencia_path TEXT DEFAULT NULL,
    estatus VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE apoyos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT '',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE registros_apoyos (
    id SERIAL PRIMARY KEY,
    apoyo VARCHAR(150) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    celular_1 VARCHAR(15) NOT NULL,
    celular_2 VARCHAR(15) DEFAULT '',
    correo VARCHAR(150) NOT NULL,
    seccion_electoral VARCHAR(10) DEFAULT '',
    calle VARCHAR(150) NOT NULL,
    no_exterior VARCHAR(20) NOT NULL,
    no_interior VARCHAR(20) DEFAULT '',
    colonia VARCHAR(150) NOT NULL,
    municipio VARCHAR(150) NOT NULL,
    codigo_postal VARCHAR(10) NOT NULL,
    observaciones TEXT DEFAULT '',
    ine_path TEXT DEFAULT NULL,
    estatus VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE codigos_postales (
    id SERIAL PRIMARY KEY,
    codigo_postal VARCHAR(10) NOT NULL,
    colonia VARCHAR(150) NOT NULL
);

CREATE INDEX idx_codigos_postales_cp ON codigos_postales(codigo_postal);

CREATE TABLE bloqueos_cita (
    id SERIAL PRIMARY KEY,
    fecha DATE NOT NULL,
    hora TIME DEFAULT NULL,
    dia_completo BOOLEAN DEFAULT FALSE,
    motivo VARCHAR(255) DEFAULT '',
    empleado_id INTEGER REFERENCES empleados(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_bloqueos_fecha ON bloqueos_cita(fecha);
CREATE INDEX idx_bloqueos_fecha_hora ON bloqueos_cita(fecha, hora);

CREATE TABLE eventos_comedor (
    id SERIAL PRIMARY KEY,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    lugar VARCHAR(255) DEFAULT '',
    descripcion TEXT DEFAULT '',
    cupo_maximo INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_eventos_comedor_fecha ON eventos_comedor(fecha);

CREATE TABLE registros_comedor (
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
    observaciones TEXT DEFAULT '',
    estatus VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Empleado de ejemplo (password: UWuCzz5863!)
INSERT INTO empleados (usuario, password_hash, nombre_completo)
VALUES (
    'empleado01',
    '$2y$12$Ta7QQhB5oAydubgL/TY.0udehBNlXgX1z/RxAJIaKL2qzcIp3.weK',
    'Empleado General'
);
