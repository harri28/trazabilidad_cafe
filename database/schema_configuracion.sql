-- ============================================================
--  CONFIGURACIÓN DEL SISTEMA — Migración
--  Tabla de pares clave-valor para ajustes globales
-- ============================================================

CREATE TABLE IF NOT EXISTS configuracion (
    clave          VARCHAR(100) PRIMARY KEY,
    valor          TEXT         NOT NULL,
    descripcion    VARCHAR(255),
    actualizado_en TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Valores iniciales (no sobreescribe si ya existen)
INSERT INTO configuracion (clave, valor, descripcion) VALUES
    ('tasa_usd', '3.7500', 'Tipo de cambio USD → PEN'),
    ('tasa_eur', '4.0500', 'Tipo de cambio EUR → PEN')
ON CONFLICT (clave) DO NOTHING;
