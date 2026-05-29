-- ============================================================
--  SISTEMA DE TRAZABILIDAD DE CAFÉ
--  Base de datos: trazabilidad_cafe
--  Motor: MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS trazabilidad_cafe
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE trazabilidad_cafe;

-- ============================================================
-- TABLA: tipos_cafe
-- Catálogo de variedades / estados del grano
-- ============================================================
CREATE TABLE tipos_cafe (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL,           -- "Pergamino", "Oro", "Tostado"
    descripcion TEXT,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO tipos_cafe (nombre, descripcion) VALUES
  ('Pergamino', 'Café con cascarilla, post-despulpado'),
  ('Oro',       'Café verde sin cascarilla'),
  ('Tostado',   'Café tostado listo para exportar'),
  ('Verde',     'Café verde sin procesar');

-- ============================================================
-- TABLA: certificaciones_catalogo
-- Catálogo de certificaciones disponibles
-- ============================================================
CREATE TABLE certificaciones_catalogo (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo    VARCHAR(30)  NOT NULL UNIQUE,   -- "RFA", "ORG", "FT"
    nombre    VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo    TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO certificaciones_catalogo (codigo, nombre) VALUES
  ('RFA',   'Rainforest Alliance'),
  ('ORG',   'Orgánico / USDA Organic'),
  ('FT',    'Fair Trade / Comercio Justo'),
  ('UTZ',   'UTZ Certified'),
  ('4C',    '4C Association'),
  ('SPE',   'Specialty Coffee');

-- ============================================================
-- TABLA: clientes
-- Productores (proveedores) y compradores
-- ============================================================
CREATE TABLE clientes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo            ENUM('productor','comprador','ambos') NOT NULL,
    razon_social    VARCHAR(150) NOT NULL,
    ruc_dni         VARCHAR(20)  UNIQUE,
    contacto        VARCHAR(100),
    telefono        VARCHAR(20),
    email           VARCHAR(120),
    direccion       TEXT,
    departamento    VARCHAR(60),
    provincia       VARCHAR(60),
    distrito        VARCHAR(60),
    -- Datos específicos del productor
    altitud_msnm    SMALLINT UNSIGNED,          -- metros sobre el nivel del mar
    hectareas       DECIMAL(8,2),
    asociacion      VARCHAR(120),               -- cooperativa / asociación
    -- Datos específicos del comprador
    pais_destino    VARCHAR(80),
    moneda_pref     ENUM('PEN','USD','EUR') DEFAULT 'USD',
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    notas           TEXT,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: lotes
-- Unidad central de trazabilidad
-- ============================================================
CREATE TABLE lotes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30)  NOT NULL UNIQUE,   -- ej: LOT-2024-001
    estado          ENUM('acopio','proceso','disponible','vendido','parcial') NOT NULL DEFAULT 'acopio',
    tipo_cafe_id    INT UNSIGNED NOT NULL,
    productor_id    INT UNSIGNED NOT NULL,           -- cliente tipo productor
    fecha_acopio    DATE         NOT NULL,
    campaña         YEAR,                            -- año campaña ej: 2024
    -- Pesos
    peso_inicial_kg DECIMAL(10,3) NOT NULL,
    peso_actual_kg  DECIMAL(10,3) NOT NULL,          -- se actualiza con movimientos
    peso_final_kg   DECIMAL(10,3),                  -- tras transformación
    merma_kg        DECIMAL(10,3) GENERATED ALWAYS AS
                      (peso_inicial_kg - IFNULL(peso_final_kg, peso_actual_kg)) STORED,
    rendimiento_pct DECIMAL(5,2) GENERATED ALWAYS AS
                      (CASE WHEN peso_inicial_kg > 0 AND peso_final_kg IS NOT NULL
                            THEN (peso_final_kg / peso_inicial_kg) * 100
                            ELSE NULL END) STORED,
    -- Origen geográfico
    region          VARCHAR(80),
    finca           VARCHAR(120),
    altitud_msnm    SMALLINT UNSIGNED,
    variedad        VARCHAR(80),                    -- Typica, Caturra, Gesha, etc.
    -- Proceso
    proceso_beneficio ENUM('lavado','natural','honey','semi-lavado') DEFAULT 'lavado',
    notas           TEXT,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lote_tipo    FOREIGN KEY (tipo_cafe_id)  REFERENCES tipos_cafe(id),
    CONSTRAINT fk_lote_prod    FOREIGN KEY (productor_id)  REFERENCES clientes(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: lote_certificaciones
-- Relación N:M entre lotes y certificaciones
-- ============================================================
CREATE TABLE lote_certificaciones (
    lote_id           INT UNSIGNED NOT NULL,
    certificacion_id  INT UNSIGNED NOT NULL,
    fecha_inicio      DATE,
    fecha_vencimiento DATE,
    numero_certificado VARCHAR(80),
    PRIMARY KEY (lote_id, certificacion_id),
    CONSTRAINT fk_lc_lote  FOREIGN KEY (lote_id)          REFERENCES lotes(id) ON DELETE CASCADE,
    CONSTRAINT fk_lc_cert  FOREIGN KEY (certificacion_id)  REFERENCES certificaciones_catalogo(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: kardex
-- Entradas y salidas de inventario por lote
-- ============================================================
CREATE TABLE kardex (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lote_id         INT UNSIGNED NOT NULL,
    tipo_movimiento ENUM('entrada','salida','ajuste','transformacion') NOT NULL,
    concepto        VARCHAR(150) NOT NULL,          -- "Compra proveedor", "Venta exportación", etc.
    fecha           DATE         NOT NULL,
    cantidad_kg     DECIMAL(10,3) NOT NULL,         -- siempre positivo
    precio_unitario DECIMAL(10,4),                  -- por kg en moneda local
    moneda          ENUM('PEN','USD','EUR') DEFAULT 'PEN',
    tipo_cambio     DECIMAL(8,4) DEFAULT 1.0000,    -- respecto al USD
    total_monto     DECIMAL(14,4) GENERATED ALWAYS AS
                      (cantidad_kg * IFNULL(precio_unitario, 0)) STORED,
    prima_diferencial DECIMAL(8,4) DEFAULT 0,       -- prima adicional por kg (ej: Fair Trade)
    prima_total     DECIMAL(14,4) GENERATED ALWAYS AS
                      (cantidad_kg * prima_diferencial) STORED,
    saldo_kg        DECIMAL(10,3),                  -- saldo tras el movimiento (calculado por trigger)
    referencia_id   INT UNSIGNED,                   -- venta_id o transformacion_id origen
    referencia_tipo VARCHAR(30),                    -- 'venta', 'transformacion', 'compra'
    usuario         VARCHAR(60),
    notas           TEXT,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kardex_lote FOREIGN KEY (lote_id) REFERENCES lotes(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: laboratorio_analisis
-- Análisis de calidad vinculados a un lote
-- ============================================================
CREATE TABLE laboratorio_analisis (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lote_id         INT UNSIGNED NOT NULL,
    fecha_analisis  DATE         NOT NULL,
    analista        VARCHAR(100),
    laboratorio     VARCHAR(120) DEFAULT 'Interno',
    -- Parámetros físicos
    humedad_pct     DECIMAL(5,2),                   -- % humedad (óptimo 10-12%)
    rendimiento_pct DECIMAL(5,2),                   -- % rendimiento pergamino→oro
    densidad_gr_l   DECIMAL(7,2),                   -- gr/litro
    -- Defectos (SCAA categoría 1 y 2)
    defectos_cat1   SMALLINT UNSIGNED DEFAULT 0,    -- granos negros, vinagre, etc.
    defectos_cat2   SMALLINT UNSIGNED DEFAULT 0,    -- manchados, flotes, etc.
    -- Catación (SCA 0-100)
    score_taza      DECIMAL(5,2),                   -- puntaje SCA (≥80 = specialty)
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
    defecto_taza    DECIMAL(4,2) DEFAULT 0,
    -- Resultado
    clasificacion   ENUM('specialty','premium','comercial','descarte')
                    GENERATED ALWAYS AS (
                      CASE
                        WHEN score_taza >= 80 THEN 'specialty'
                        WHEN score_taza >= 75 THEN 'premium'
                        WHEN score_taza >= 60 THEN 'comercial'
                        WHEN score_taza IS NOT NULL THEN 'descarte'
                        ELSE NULL
                      END
                    ) STORED,
    notas_catacion  TEXT,
    aprobado        TINYINT(1),                     -- 1=aprobado, 0=rechazado, NULL=pendiente
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lab_lote FOREIGN KEY (lote_id) REFERENCES lotes(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: ventas
-- Contratos de venta de café
-- ============================================================
CREATE TABLE ventas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_contrato VARCHAR(40)  NOT NULL UNIQUE,   -- ej: CONT-2024-001
    estado          ENUM('borrador','confirmado','en_proceso','entregado','cancelado')
                    NOT NULL DEFAULT 'borrador',
    comprador_id    INT UNSIGNED NOT NULL,
    lote_id         INT UNSIGNED NOT NULL,
    fecha_contrato  DATE         NOT NULL,
    fecha_entrega   DATE,
    -- Cantidades
    cantidad_kg     DECIMAL(10,3) NOT NULL,
    -- Precio
    precio_usd_kg   DECIMAL(10,4) NOT NULL,         -- precio base en USD/kg
    tipo_cambio     DECIMAL(8,4)  NOT NULL DEFAULT 1.0000,
    moneda_factura  ENUM('USD','PEN','EUR') DEFAULT 'USD',
    -- Totales calculados
    total_usd       DECIMAL(14,4) GENERATED ALWAYS AS
                      (cantidad_kg * precio_usd_kg) STORED,
    total_local     DECIMAL(14,4) GENERATED ALWAYS AS
                      (cantidad_kg * precio_usd_kg * tipo_cambio) STORED,
    -- Incoterm y logística
    incoterm        VARCHAR(10) DEFAULT 'FOB',      -- FOB, CIF, EXW, etc.
    puerto_embarque VARCHAR(80),
    -- Calidad acordada
    humedad_max_pct DECIMAL(4,2),
    defectos_max    SMALLINT,
    score_min       DECIMAL(4,2),
    notas           TEXT,
    usuario         VARCHAR(60),
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_venta_comprador FOREIGN KEY (comprador_id) REFERENCES clientes(id),
    CONSTRAINT fk_venta_lote      FOREIGN KEY (lote_id)      REFERENCES lotes(id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: transformaciones
-- Registro de transformaciones de café (pergamino → oro, etc.)
-- ============================================================
CREATE TABLE transformaciones (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lote_origen_id    INT UNSIGNED NOT NULL,
    lote_destino_id   INT UNSIGNED,                 -- nuevo lote generado (si aplica)
    tipo_transformacion VARCHAR(80) NOT NULL,       -- "Despergaminado", "Tostado", etc.
    fecha             DATE NOT NULL,
    peso_entrada_kg   DECIMAL(10,3) NOT NULL,
    peso_salida_kg    DECIMAL(10,3),
    merma_kg          DECIMAL(10,3) GENERATED ALWAYS AS
                        (peso_entrada_kg - IFNULL(peso_salida_kg, 0)) STORED,
    rendimiento_pct   DECIMAL(5,2) GENERATED ALWAYS AS
                        (CASE WHEN peso_entrada_kg > 0 AND peso_salida_kg IS NOT NULL
                              THEN (peso_salida_kg / peso_entrada_kg) * 100
                              ELSE NULL END) STORED,
    operador          VARCHAR(100),
    maquinaria        VARCHAR(100),
    notas             TEXT,
    creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trans_origen  FOREIGN KEY (lote_origen_id)  REFERENCES lotes(id),
    CONSTRAINT fk_trans_destino FOREIGN KEY (lote_destino_id) REFERENCES lotes(id)
) ENGINE=InnoDB;

-- ============================================================
-- TRIGGERS: Mantenimiento automático de saldos e inventario
-- ============================================================

DELIMITER $$

-- Trigger: después de insertar en kardex → actualizar peso_actual_kg del lote
CREATE TRIGGER trg_kardex_after_insert
AFTER INSERT ON kardex
FOR EACH ROW
BEGIN
    DECLARE nuevo_peso DECIMAL(10,3);

    IF NEW.tipo_movimiento IN ('entrada', 'ajuste') THEN
        UPDATE lotes
        SET peso_actual_kg = peso_actual_kg + NEW.cantidad_kg
        WHERE id = NEW.lote_id;
    ELSEIF NEW.tipo_movimiento IN ('salida', 'transformacion') THEN
        UPDATE lotes
        SET peso_actual_kg = peso_actual_kg - NEW.cantidad_kg
        WHERE id = NEW.lote_id;
    END IF;

    -- Registrar saldo en la fila recién insertada
    SELECT peso_actual_kg INTO nuevo_peso FROM lotes WHERE id = NEW.lote_id;
    UPDATE kardex SET saldo_kg = nuevo_peso WHERE id = NEW.id;
END$$

-- Trigger: validar stock antes de insertar salida en kardex
CREATE TRIGGER trg_kardex_before_insert
BEFORE INSERT ON kardex
FOR EACH ROW
BEGIN
    DECLARE stock_actual DECIMAL(10,3);

    IF NEW.tipo_movimiento IN ('salida', 'transformacion') THEN
        SELECT peso_actual_kg INTO stock_actual FROM lotes WHERE id = NEW.lote_id;
        IF stock_actual < NEW.cantidad_kg THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente para realizar la salida';
        END IF;
    END IF;
END$$

-- Trigger: actualizar estado del lote al confirmar venta
CREATE TRIGGER trg_venta_after_update
AFTER UPDATE ON ventas
FOR EACH ROW
BEGIN
    DECLARE stock_restante DECIMAL(10,3);

    IF NEW.estado = 'confirmado' AND OLD.estado = 'borrador' THEN
        SELECT peso_actual_kg INTO stock_restante FROM lotes WHERE id = NEW.lote_id;

        IF stock_restante <= 0 THEN
            UPDATE lotes SET estado = 'vendido' WHERE id = NEW.lote_id;
        ELSE
            UPDATE lotes SET estado = 'parcial' WHERE id = NEW.lote_id;
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- VISTAS útiles
-- ============================================================

-- Vista: estado completo de lotes con análisis y trazabilidad
CREATE OR REPLACE VIEW v_lotes_completos AS
SELECT
    l.id,
    l.codigo,
    l.estado,
    l.campaña,
    tc.nombre                   AS tipo_cafe,
    c.razon_social              AS productor,
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
    -- Último análisis
    la.fecha_analisis           AS ultima_analisis,
    la.score_taza,
    la.clasificacion,
    la.humedad_pct,
    la.rendimiento_pct          AS rend_lab,
    -- Certificaciones (concatenadas)
    GROUP_CONCAT(DISTINCT cert.codigo ORDER BY cert.codigo SEPARATOR ', ') AS certificaciones,
    -- Ventas
    COALESCE(SUM(v.cantidad_kg), 0)    AS kg_vendidos,
    l.peso_actual_kg - COALESCE(SUM(v.cantidad_kg), 0) AS kg_disponibles
FROM lotes l
JOIN tipos_cafe tc        ON tc.id = l.tipo_cafe_id
JOIN clientes c           ON c.id  = l.productor_id
LEFT JOIN laboratorio_analisis la ON la.id = (
    SELECT id FROM laboratorio_analisis
    WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
)
LEFT JOIN lote_certificaciones lc   ON lc.lote_id = l.id
LEFT JOIN certificaciones_catalogo cert ON cert.id = lc.certificacion_id
LEFT JOIN ventas v ON v.lote_id = l.id AND v.estado NOT IN ('cancelado')
GROUP BY l.id;

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
GROUP BY l.id, k.tipo_movimiento, k.moneda;

-- Vista: rentabilidad por lote
CREATE OR REPLACE VIEW v_rentabilidad AS
SELECT
    l.codigo,
    l.campaña,
    c.razon_social   AS productor,
    -- Costos de compra
    SUM(CASE WHEN k.tipo_movimiento = 'entrada' THEN k.total_monto ELSE 0 END) AS costo_compra,
    SUM(CASE WHEN k.tipo_movimiento = 'entrada' THEN k.prima_total ELSE 0 END)  AS primas_pagadas,
    -- Ingresos por ventas
    COALESCE(SUM(v.total_usd * ve.tipo_cambio), 0)  AS ingresos_locales,
    COALESCE(SUM(v.total_usd), 0)                   AS ingresos_usd,
    -- Margen
    COALESCE(SUM(v.total_usd), 0) -
    (SUM(CASE WHEN k.tipo_movimiento = 'entrada' THEN k.total_monto / NULLIF(k.tipo_cambio,0) ELSE 0 END))
                                                     AS margen_usd
FROM lotes l
JOIN clientes c   ON c.id = l.productor_id
LEFT JOIN kardex k  ON k.lote_id = l.id
LEFT JOIN ventas ve ON ve.lote_id = l.id AND ve.estado NOT IN ('cancelado','borrador')
LEFT JOIN ventas v  ON v.id = ve.id
GROUP BY l.id;

-- ============================================================
-- ÍNDICES para rendimiento
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
