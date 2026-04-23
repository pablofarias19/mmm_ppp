-- migrations/017_consulta_siempre.sql
-- Columnas para inclusión forzada en consultas:
--   consulta_siempre  → admin puede marcar cualquier negocio para que SIEMPRE ingrese
--                       en Consulta Masiva dentro de su área geográfica.
--   proveedor_siempre → admin puede marcar un negocio P para que SIEMPRE ingrese
--                       en Consulta Global Proveedores de su rubro.

ALTER TABLE businesses
    ADD COLUMN consulta_siempre  TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Admin: 1 = este negocio siempre entra en Consulta Masiva dentro del área',
    ADD COLUMN proveedor_siempre TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Admin: 1 = este negocio P siempre entra en Consulta Global Proveedores';
