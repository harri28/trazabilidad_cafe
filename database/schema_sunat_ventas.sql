-- ============================================================
--  MIGRACIÓN: Campos SUNAT en tabla ventas
--  Ejecutar una sola vez:
--    psql -U postgresql -d trazabilidad_cafe -f database/schema_sunat_ventas.sql
-- ============================================================

ALTER TABLE ventas
    ADD COLUMN IF NOT EXISTS sunat_documento_id    INTEGER,
    ADD COLUMN IF NOT EXISTS sunat_tipo            VARCHAR(10),    -- 'factura' | 'boleta'
    ADD COLUMN IF NOT EXISTS sunat_serie           VARCHAR(10),    -- F001 / B001
    ADD COLUMN IF NOT EXISTS sunat_numero          VARCHAR(20),    -- correlativo
    ADD COLUMN IF NOT EXISTS sunat_estado          VARCHAR(20),    -- pendiente | aceptado | rechazado | observado | anulado
    ADD COLUMN IF NOT EXISTS sunat_cdr_descripcion TEXT,           -- mensaje CDR de SUNAT
    ADD COLUMN IF NOT EXISTS sunat_emitido_en      TIMESTAMP;

-- Índice para búsquedas por estado SUNAT
CREATE INDEX IF NOT EXISTS idx_ventas_sunat_estado
    ON ventas (sunat_estado)
    WHERE sunat_estado IS NOT NULL;

-- Índice para búsquedas por documento SUNAT
CREATE INDEX IF NOT EXISTS idx_ventas_sunat_documento
    ON ventas (sunat_tipo, sunat_serie, sunat_numero)
    WHERE sunat_documento_id IS NOT NULL;
