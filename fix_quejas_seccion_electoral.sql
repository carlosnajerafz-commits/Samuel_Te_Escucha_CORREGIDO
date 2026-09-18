-- =============================================
-- Migración: permitir quejas sin sección electoral
-- Ejecutar en la base de datos "tecamac"
--
-- El formulario público de quejas (queja.php / guardar_queja.php) ya no
-- solicita "sección electoral", pero la columna seguía siendo NOT NULL,
-- lo que provocaba que TODAS las quejas fallaran al guardarse
-- (SQLSTATE[23502]: not-null violation).
-- =============================================

ALTER TABLE quejas
    ALTER COLUMN seccion_electoral DROP NOT NULL,
    ALTER COLUMN seccion_electoral SET DEFAULT '';
