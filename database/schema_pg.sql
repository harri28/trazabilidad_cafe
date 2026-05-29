-- ============================================================
--  SISTEMA DE TRAZABILIDAD DE CAFÉ
--  Base de datos: trazabilidad_cafe  |  Motor: PostgreSQL 14+
-- ============================================================

-- Crear base de datos (ejecutar como superusuario)
-- CREATE DATABASE trazabilidad_cafe ENCODING 'UTF8';
-- \c trazabilidad_cafe

-- ============================================================
-- TIPOS ENUM personalizados
-- ============================================================
CREATE TYPE tipo_cliente         AS ENUM ('productor','comprador','ambos');
CREATE TYPE moneda_tipo          AS ENUM ('PEN','USD','EUR');
CREATE TYPE estado_lote          AS ENUM ('acopio','proceso','disponible','vendido','parcial');
CREATE TYPE proceso_beneficio    AS ENUM ('lavado','natural','honey','semi-lavado');
CREATE TYPE tipo_movimiento_kard AS ENUM ('entrada','salida','ajuste','transformacion');
CREATE TYPE clasificacion_cafe   AS ENUM ('specialty','premium','comercial','descarte');
CREATE TYPE estado_venta         AS ENUM ('borrador','confirmado','en_proceso','entregado','cancelado');
CREATE TYPE moneda_factura       AS ENUM ('USD','PEN','EUR');

-- ============================================================
-- TABLA: tipos_cafe
-- ============================================================
CREATE TABLE tipos_cafe (
    id          SERIAL       PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,
    descripcion TEXT,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE,
    creado_en   TIMESTAMP    NOT NULL DEFAULT NOW()
);

INSERT INTO tipos_cafe (nombre, descripcion) VALUES
  ('Pergamino', 'Café con cascarilla, post-despulpado'),
  ('Oro',       'Café verde sin cascarilla'),
  ('Tostado',   'Café tostado listo para exportar'),
  ('Verde',     'Café verde sin procesar');

-- ============================================================
-- TABLA: certificaciones_catalogo
-- ============================================================
CREATE TABLE certificaciones_catalogo (
    id          SERIAL       PRIMARY KEY,
    codigo      VARCHAR(30)  NOT NULL UNIQUE,
    nombre      VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo      BOOLEAN      NOT NULL DEFAULT TRUE
);

INSERT INTO certificaciones_catalogo (codigo, nombre) VALUES
  ('RFA', 'Rainforest Alliance'),
  ('ORG', 'Orgánico / USDA Organic'),
  ('FT',  'Fair Trade / Comercio Justo'),
  ('UTZ', 'UTZ Certified'),
  ('4C',  '4C Association'),
  ('SPE', 'Specialty Coffee');

-- ============================================================
-- TABLA: clientes
-- ============================================================
CREATE TABLE clientes (
    id              SERIAL           PRIMARY KEY,
    tipo            tipo_cliente     NOT NULL,
    razon_social    VARCHAR(150)     NOT NULL,
    ruc_dni         VARCHAR(20)      UNIQUE,
    contacto        VARCHAR(100),
    telefono        VARCHAR(20),
    email           VARCHAR(120),
    direccion       TEXT,
    departamento    VARCHAR(60),
    provincia       VARCHAR(60),
    distrito        VARCHAR(60),
    altitud_msnm    SMALLINT         CHECK (altitud_msnm >= 0),
    hectareas       DECIMAL(8,2),
    asociacion      VARCHAR(120),
    pais_destino    VARCHAR(80),
    moneda_pref     moneda_tipo      DEFAULT 'USD',
    activo          BOOLEAN          NOT NULL DEFAULT TRUE,
    notas           TEXT,
    creado_en       TIMESTAMP        NOT NULL DEFAULT NOW(),
    actualizado_en  TIMESTAMP        NOT NULL DEFAULT NOW()
);

-- ============================================================
-- TABLA: lotes
-- ============================================================
CREATE TABLE lotes (
    id                SERIAL              PRIMARY KEY,
    codigo            VARCHAR(30)         NOT NULL UNIQUE,
    estado            estado_lote         NOT NULL DEFAULT 'acopio',
    tipo_cafe_id      INTEGER             NOT NULL REFERENCES tipos_cafe(id),
    productor_id      INTEGER             NOT NULL REFERENCES clientes(id),
    fecha_acopio      DATE                NOT NULL,
    campaña           SMALLINT,
    peso_inicial_kg   DECIMAL(10,3)       NOT NULL,
    peso_actual_kg    DECIMAL(10,3)       NOT NULL,
    peso_final_kg     DECIMAL(10,3),
    -- Columnas generadas (PostgreSQL 12+)
    merma_kg          DECIMAL(10,3)       GENERATED ALWAYS AS
                        (peso_inicial_kg - COALESCE(peso_final_kg, peso_actual_kg)) STORED,
    rendimiento_pct   DECIMAL(5,2)        GENERATED ALWAYS AS
                        (CASE WHEN peso_inicial_kg > 0 AND peso_final_kg IS NOT NULL
                              THEN (peso_final_kg / peso_inicial_kg) * 100
                              ELSE NULL END) STORED,
    region            VARCHAR(80),
    finca             VARCHAR(120),
    altitud_msnm      SMALLINT            CHECK (altitud_msnm >= 0),
    variedad          VARCHAR(80),
    proceso_beneficio proceso_beneficio   DEFAULT 'lavado',
    notas             TEXT,
    creado_en         TIMESTAMP           NOT NULL DEFAULT NOW(),
    actualizado_en    TIMESTAMP           NOT NULL DEFAULT NOW()
);

-- ============================================================
-- TABLA: lote_certificaciones
-- ============================================================
CREATE TABLE lote_certificaciones (
    lote_id             INTEGER  NOT NULL REFERENCES lotes(id) ON DELETE CASCADE,
    certificacion_id    INTEGER  NOT NULL REFERENCES certificaciones_catalogo(id),
    fecha_inicio        DATE,
    fecha_vencimiento   DATE,
    numero_certificado  VARCHAR(80),
    PRIMARY KEY (lote_id, certificacion_id)
);

-- ============================================================
-- TABLA: kardex
-- ============================================================
CREATE TABLE kardex (
    id                  SERIAL              PRIMARY KEY,
    lote_id             INTEGER             NOT NULL REFERENCES lotes(id),
    tipo_movimiento     tipo_movimiento_kard NOT NULL,
    concepto            VARCHAR(150)        NOT NULL,
    fecha               DATE                NOT NULL,
    cantidad_kg         DECIMAL(10,3)       NOT NULL,
    precio_unitario     DECIMAL(10,4),
    moneda              moneda_tipo         DEFAULT 'PEN',
    tipo_cambio         DECIMAL(8,4)        DEFAULT 1.0000,
    total_monto         DECIMAL(14,4)       GENERATED ALWAYS AS
                          (cantidad_kg * COALESCE(precio_unitario, 0)) STORED,
    prima_diferencial   DECIMAL(8,4)        DEFAULT 0,
    prima_total         DECIMAL(14,4)       GENERATED ALWAYS AS
                          (cantidad_kg * prima_diferencial) STORED,
    saldo_kg            DECIMAL(10,3),
    referencia_id       INTEGER,
    referencia_tipo     VARCHAR(30),
    usuario             VARCHAR(60),
    notas               TEXT,
    creado_en           TIMESTAMP           NOT NULL DEFAULT NOW()
);

-- ============================================================
-- TABLA: laboratorio_analisis
-- ============================================================
CREATE TABLE laboratorio_analisis (
    id              SERIAL          PRIMARY KEY,
    lote_id         INTEGER         NOT NULL REFERENCES lotes(id),
    fecha_analisis  DATE            NOT NULL,
    analista        VARCHAR(100),
    laboratorio     VARCHAR(120)    DEFAULT 'Interno',
    humedad_pct     DECIMAL(5,2),
    rendimiento_pct DECIMAL(5,2),
    densidad_gr_l   DECIMAL(7,2),
    defectos_cat1   SMALLINT        DEFAULT 0 CHECK (defectos_cat1 >= 0),
    defectos_cat2   SMALLINT        DEFAULT 0 CHECK (defectos_cat2 >= 0),
    score_taza      DECIMAL(5,2)    CHECK (score_taza BETWEEN 0 AND 100),
    fragancia       DECIMAL(4,2),
    aroma           DECIMAL(4,2),
    sabor           DECIMAL(4,2),
    post_gusto      DECIMAL(4,2),
    acidez          DECIMAL(4,2),
    cuerpo          DECIMAL(4,2),
    uniformidad     DECIMAL(4,2),
    balance         DECIMAL(4,2),
    limpieza_taza   DECIMAL(4,2),
    dulzura         DECIMAL(4,2),
    defecto_taza    DECIMAL(4,2)    DEFAULT 0,
    -- Columna generada: clasificación automática por score
    clasificacion   clasificacion_cafe GENERATED ALWAYS AS (
                      CASE
                        WHEN score_taza >= 80 THEN 'specialty'::clasificacion_cafe
                        WHEN score_taza >= 75 THEN 'premium'::clasificacion_cafe
                        WHEN score_taza >= 60 THEN 'comercial'::clasificacion_cafe
                        WHEN score_taza IS NOT NULL THEN 'descarte'::clasificacion_cafe
                        ELSE NULL
                      END
                    ) STORED,
    notas_catacion  TEXT,
    aprobado        BOOLEAN,
    creado_en       TIMESTAMP       NOT NULL DEFAULT NOW()
);

-- ============================================================
-- TABLA: ventas
-- ============================================================
CREATE TABLE ventas (
    id              SERIAL          PRIMARY KEY,
    numero_contrato VARCHAR(40)     NOT NULL UNIQUE,
    estado          estado_venta    NOT NULL DEFAULT 'borrador',
    comprador_id    INTEGER         NOT NULL REFERENCES clientes(id),
    lote_id         INTEGER         NOT NULL REFERENCES lotes(id),
    fecha_contrato  DATE            NOT NULL,
    fecha_entrega   DATE,
    cantidad_kg     DECIMAL(10,3)   NOT NULL,
    precio_usd_kg   DECIMAL(10,4)   NOT NULL,
    tipo_cambio     DECIMAL(8,4)    NOT NULL DEFAULT 1.0000,
    moneda_factura  moneda_factura  DEFAULT 'USD',
    total_usd       DECIMAL(14,4)   GENERATED ALWAYS AS (cantidad_kg * precio_usd_kg) STORED,
    total_local     DECIMAL(14,4)   GENERATED ALWAYS AS (cantidad_kg * precio_usd_kg * tipo_cambio) STORED,
    incoterm        VARCHAR(10)     DEFAULT 'FOB',
    puerto_embarque VARCHAR(80),
    humedad_max_pct DECIMAL(4,2),
    defectos_max    SMALLINT,
    score_min       DECIMAL(4,2),
    notas           TEXT,
    usuario         VARCHAR(60),
    creado_en       TIMESTAMP       NOT NULL DEFAULT NOW(),
    actualizado_en  TIMESTAMP       NOT NULL DEFAULT NOW()
);

-- ============================================================
-- TABLA: transformaciones
-- ============================================================
CREATE TABLE transformaciones (
    id                  SERIAL          PRIMARY KEY,
    lote_origen_id      INTEGER         NOT NULL REFERENCES lotes(id),
    lote_destino_id     INTEGER         REFERENCES lotes(id),
    tipo_transformacion VARCHAR(80)     NOT NULL,
    fecha               DATE            NOT NULL,
    peso_entrada_kg     DECIMAL(10,3)   NOT NULL,
    peso_salida_kg      DECIMAL(10,3),
    merma_kg            DECIMAL(10,3)   GENERATED ALWAYS AS
                          (peso_entrada_kg - COALESCE(peso_salida_kg, 0)) STORED,
    rendimiento_pct     DECIMAL(5,2)    GENERATED ALWAYS AS
                          (CASE WHEN peso_entrada_kg > 0 AND peso_salida_kg IS NOT NULL
                                THEN (peso_salida_kg / peso_entrada_kg) * 100
                                ELSE NULL END) STORED,
    operador            VARCHAR(100),
    maquinaria          VARCHAR(100),
    notas               TEXT,
    creado_en           TIMESTAMP       NOT NULL DEFAULT NOW()
);

-- ============================================================
-- FUNCIÓN AUXILIAR: updated_at automático
-- ============================================================
CREATE OR REPLACE FUNCTION fn_set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.actualizado_en = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_clientes_updated_at
    BEFORE UPDATE ON clientes
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

CREATE TRIGGER trg_lotes_updated_at
    BEFORE UPDATE ON lotes
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

CREATE TRIGGER trg_ventas_updated_at
    BEFORE UPDATE ON ventas
    FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();

-- ============================================================
-- TRIGGER: validar stock antes de insertar salida en kardex
-- ============================================================
CREATE OR REPLACE FUNCTION fn_kardex_before_insert()
RETURNS TRIGGER AS $$
DECLARE
    stock_actual DECIMAL(10,3);
BEGIN
    IF NEW.tipo_movimiento IN ('salida', 'transformacion') THEN
        SELECT peso_actual_kg INTO stock_actual FROM lotes WHERE id = NEW.lote_id;
        IF stock_actual < NEW.cantidad_kg THEN
            RAISE EXCEPTION 'Stock insuficiente para realizar la salida';
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_kardex_before_insert
    BEFORE INSERT ON kardex
    FOR EACH ROW EXECUTE FUNCTION fn_kardex_before_insert();

-- ============================================================
-- TRIGGER: actualizar peso_actual_kg del lote al insertar kardex
-- ============================================================
CREATE OR REPLACE FUNCTION fn_kardex_after_insert()
RETURNS TRIGGER AS $$
DECLARE
    nuevo_peso DECIMAL(10,3);
BEGIN
    IF NEW.tipo_movimiento IN ('entrada', 'ajuste') THEN
        UPDATE lotes SET peso_actual_kg = peso_actual_kg + NEW.cantidad_kg WHERE id = NEW.lote_id;
    ELSIF NEW.tipo_movimiento IN ('salida', 'transformacion') THEN
        UPDATE lotes SET peso_actual_kg = peso_actual_kg - NEW.cantidad_kg WHERE id = NEW.lote_id;
    END IF;

    SELECT peso_actual_kg INTO nuevo_peso FROM lotes WHERE id = NEW.lote_id;
    UPDATE kardex SET saldo_kg = nuevo_peso WHERE id = NEW.id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_kardex_after_insert
    AFTER INSERT ON kardex
    FOR EACH ROW EXECUTE FUNCTION fn_kardex_after_insert();

-- ============================================================
-- TRIGGER: actualizar estado del lote al confirmar venta
-- ============================================================
CREATE OR REPLACE FUNCTION fn_venta_after_update()
RETURNS TRIGGER AS $$
DECLARE
    stock_restante DECIMAL(10,3);
BEGIN
    IF NEW.estado = 'confirmado' AND OLD.estado = 'borrador' THEN
        SELECT peso_actual_kg INTO stock_restante FROM lotes WHERE id = NEW.lote_id;
        IF stock_restante <= 0 THEN
            UPDATE lotes SET estado = 'vendido' WHERE id = NEW.lote_id;
        ELSE
            UPDATE lotes SET estado = 'parcial'  WHERE id = NEW.lote_id;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_venta_after_update
    AFTER UPDATE ON ventas
    FOR EACH ROW EXECUTE FUNCTION fn_venta_after_update();

-- ============================================================
-- VISTAS
-- ============================================================

-- Vista: estado completo de lotes con análisis y trazabilidad
CREATE OR REPLACE VIEW v_lotes_completos AS
SELECT
    l.id,
    l.codigo,
    l.estado,
    l.campaña,
    tc.nombre                    AS tipo_cafe,
    c.razon_social               AS productor,
    c.departamento,
    c.provincia,
    l.finca,
    l.altitud_msnm,
    l.variedad,
    l.proceso_beneficio,
    l.fecha_acopio,
    l.peso_inicial_kg,
    l.peso_actual_kg,
    l.peso_final_kg,
    l.merma_kg,
    l.rendimiento_pct,
    la.fecha_analisis            AS ultima_analisis,
    la.score_taza,
    la.clasificacion,
    la.humedad_pct,
    la.rendimiento_pct           AS rend_lab,
    STRING_AGG(DISTINCT cert.codigo, ', ' ORDER BY cert.codigo) AS certificaciones,
    COALESCE(SUM(v.cantidad_kg), 0)    AS kg_vendidos,
    l.peso_actual_kg - COALESCE(SUM(v.cantidad_kg), 0) AS kg_disponibles
FROM lotes l
JOIN tipos_cafe tc        ON tc.id = l.tipo_cafe_id
JOIN clientes c           ON c.id  = l.productor_id
LEFT JOIN LATERAL (
    SELECT * FROM laboratorio_analisis
    WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
) la ON TRUE
LEFT JOIN lote_certificaciones lc   ON lc.lote_id = l.id
LEFT JOIN certificaciones_catalogo cert ON cert.id = lc.certificacion_id
LEFT JOIN ventas v ON v.lote_id = l.id AND v.estado <> 'cancelado'
GROUP BY l.id, tc.nombre, c.razon_social, c.departamento, c.provincia,
         la.fecha_analisis, la.score_taza, la.clasificacion, la.humedad_pct, la.rendimiento_pct;

-- Vista: resumen de kardex por lote
CREATE OR REPLACE VIEW v_kardex_resumen AS
SELECT
    l.codigo            AS lote,
    k.tipo_movimiento,
    SUM(k.cantidad_kg)  AS total_kg,
    SUM(k.total_monto)  AS total_monto,
    k.moneda,
    COUNT(*)            AS num_movimientos,
    MIN(k.fecha)        AS primera_fecha,
    MAX(k.fecha)        AS ultima_fecha
FROM kardex k
JOIN lotes l ON l.id = k.lote_id
GROUP BY l.id, l.codigo, k.tipo_movimiento, k.moneda;

-- Vista: rentabilidad por lote
CREATE OR REPLACE VIEW v_rentabilidad AS
SELECT
    l.codigo,
    l.campaña,
    c.razon_social   AS productor,
    SUM(CASE WHEN k.tipo_movimiento = 'entrada' THEN k.total_monto ELSE 0 END) AS costo_compra,
    SUM(CASE WHEN k.tipo_movimiento = 'entrada' THEN k.prima_total ELSE 0 END)  AS primas_pagadas,
    COALESCE(SUM(ve.total_usd * ve.tipo_cambio), 0) AS ingresos_locales,
    COALESCE(SUM(ve.total_usd), 0)                  AS ingresos_usd,
    COALESCE(SUM(ve.total_usd), 0) -
    SUM(CASE WHEN k.tipo_movimiento = 'entrada'
             THEN k.total_monto / NULLIF(k.tipo_cambio, 0) ELSE 0 END) AS margen_usd
FROM lotes l
JOIN clientes c   ON c.id  = l.productor_id
LEFT JOIN kardex k  ON k.lote_id = l.id
LEFT JOIN ventas ve ON ve.lote_id = l.id AND ve.estado NOT IN ('cancelado','borrador')
GROUP BY l.id, l.codigo, l.campaña, c.razon_social;

-- ============================================================
-- ÍNDICES
-- ============================================================
CREATE INDEX idx_lotes_productor    ON lotes (productor_id);
CREATE INDEX idx_lotes_estado       ON lotes (estado);
CREATE INDEX idx_lotes_campana      ON lotes (campaña);
CREATE INDEX idx_kardex_lote        ON kardex (lote_id);
CREATE INDEX idx_kardex_fecha       ON kardex (fecha);
CREATE INDEX idx_lab_lote           ON laboratorio_analisis (lote_id);
CREATE INDEX idx_ventas_comprador   ON ventas (comprador_id);
CREATE INDEX idx_ventas_lote        ON ventas (lote_id);
CREATE INDEX idx_ventas_estado      ON ventas (estado);
