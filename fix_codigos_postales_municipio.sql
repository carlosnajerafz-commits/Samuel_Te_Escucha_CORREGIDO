-- =============================================
-- Migración: agregar columna faltante "municipio"
-- Ejecutar en la base de datos "tecamac"
--
-- buscar_cp.php, admin_cp.php y guardar_cp.php ya esperaban una columna
-- "municipio" en codigos_postales, pero la tabla nunca la tuvo. Esto
-- rompía con un error fatal de PHP (no devolvía JSON válido) la búsqueda
-- de colonia por código postal en TODOS los formularios públicos que la
-- usan (cita.php y queja.php), impidiendo enviarlos porque el campo
-- "Colonia" (obligatorio) nunca se llenaba.
-- =============================================

ALTER TABLE codigos_postales
    ADD COLUMN IF NOT EXISTS municipio VARCHAR(150) DEFAULT '';
