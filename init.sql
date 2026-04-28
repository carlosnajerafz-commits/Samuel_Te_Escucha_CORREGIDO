DROP TABLE IF EXISTS citas CASCADE;
DROP TABLE IF EXISTS quejas CASCADE;
DROP TABLE IF EXISTS empleados CASCADE;

CREATE TABLE empleados (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    nombre_completo VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE quejas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    localidad VARCHAR(150) NOT NULL,
    direccion TEXT NOT NULL,
    tipo VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE citas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO empleados (usuario, password_hash, nombre_completo)
VALUES (
    'empleado01',
    '$2y$12$Ta7QQhB5oAydubgL/TY.0udehBNlXgX1z/RxAJIaKL2qzcIp3.weK',
    'Empleado General'
);