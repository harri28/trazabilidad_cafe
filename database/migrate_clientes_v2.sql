-- ============================================================
--  MIGRACIÓN: Actualización tabla clientes
--  Ejecutar en psql o phpPgAdmin sobre la BD trazabilidad_cafe
--  Seguro de correr múltiples veces (IF NOT EXISTS)
-- ============================================================

-- Agregar columnas nuevas si no existen
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS contacto        VARCHAR(100);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS direccion       TEXT;
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS distrito        VARCHAR(60);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS altitud_msnm    SMALLINT CHECK (altitud_msnm >= 0);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS hectareas       DECIMAL(8,2);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS pais_destino    VARCHAR(80);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS notas           TEXT;
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS activo          BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS actualizado_en  TIMESTAMP NOT NULL DEFAULT NOW();

-- Agregar moneda_pref solo si el tipo existe, sino como VARCHAR
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'moneda_tipo') THEN
        ALTER TABLE clientes ADD COLUMN IF NOT EXISTS moneda_pref moneda_tipo DEFAULT 'USD';
    ELSE
        ALTER TABLE clientes ADD COLUMN IF NOT EXISTS moneda_pref VARCHAR(3) DEFAULT 'USD';
    END IF;
END$$;

-- Trigger para actualizar actualizado_en automáticamente
CREATE OR REPLACE FUNCTION fn_set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.actualizado_en = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_clientes_updated_at ON clientes;
CREATE TRIGGER trg_clientes_updated_at
    BEFORE UPDATE ON clientes
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

-- Verificar resultado
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'clientes'
ORDER BY ordinal_position;
