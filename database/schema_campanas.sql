-- ============================================================
--  CAMPAÑAS Y BACKUPS — Migración
-- ============================================================

CREATE TABLE IF NOT EXISTS campanas (
    año           SMALLINT     PRIMARY KEY,
    fecha_inicio  DATE,
    fecha_fin     DATE,
    estado        VARCHAR(20)  NOT NULL DEFAULT 'activa'
                               CHECK (estado IN ('activa','cerrada','archivada')),
    notas         TEXT,
    creado_en     TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS backups_registro (
    id            SERIAL       PRIMARY KEY,
    campana_año   SMALLINT     NOT NULL,
    tipo          VARCHAR(10)  NOT NULL CHECK (tipo IN ('diario','mensual','anual')),
    fecha_backup  TIMESTAMP    NOT NULL DEFAULT NOW(),
    descripcion   VARCHAR(255),
    realizado_por VARCHAR(100) DEFAULT 'Administrador',
    estado        VARCHAR(20)  NOT NULL DEFAULT 'completado'
                               CHECK (estado IN ('completado','fallido','pendiente')),
    notas         TEXT,
    creado_en     TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- Campañas por defecto
INSERT INTO campanas (año, fecha_inicio, estado) VALUES
    (2026, '2026-01-01', 'activa'),
    (2025, '2025-01-01', 'cerrada'),
    (2024, '2024-01-01', 'archivada')
ON CONFLICT (año) DO NOTHING;
