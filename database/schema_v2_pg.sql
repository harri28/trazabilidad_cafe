-- ============================================================
--  SCHEMA V2 – Nuevos módulos  |  Motor: PostgreSQL 14+
--  Ejecutar sobre la base ya creada con schema_pg.sql
-- ============================================================

-- ── TIPOS ENUM nuevos ─────────────────────────────────────
CREATE TYPE estado_plan_maestro  AS ENUM ('borrador','activo','cerrado');
CREATE TYPE tipo_proceso_ot      AS ENUM ('secado','despergaminado','tostado','molido','envasado','seleccion','otro');
CREATE TYPE estado_ot            AS ENUM ('pendiente','en_proceso','pausada','completada','cancelada');
CREATE TYPE tipo_almacen         AS ENUM ('materia_prima','proceso','producto_terminado');
CREATE TYPE categoria_proveedor  AS ENUM ('insumos','servicios','transporte','maquinaria','otro');
CREATE TYPE estado_requisicion   AS ENUM ('pendiente','aprobada','rechazada','en_proceso','completada');
CREATE TYPE estado_oc            AS ENUM ('borrador','enviada','confirmada','parcial','completada','cancelada');
CREATE TYPE tipo_documento_cp    AS ENUM ('factura','boleta','recibo','nota_debito','otro');
CREATE TYPE estado_cp            AS ENUM ('pendiente','parcial','pagado','vencido');
CREATE TYPE estado_cotizacion    AS ENUM ('borrador','enviada','aceptada','rechazada','vencida');
CREATE TYPE tipo_cuenta_contable AS ENUM ('activo','pasivo','patrimonio','ingreso','gasto','costo');
CREATE TYPE estado_asiento       AS ENUM ('borrador','validado','anulado');
CREATE TYPE tipo_flujo           AS ENUM ('ingreso','egreso');
CREATE TYPE categoria_flujo      AS ENUM ('operativo','financiero','inversion');

-- ── PRODUCCIÓN ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS plan_maestro (
    id                  SERIAL                  PRIMARY KEY,
    campaña             SMALLINT                NOT NULL,
    tipo_cafe_id        INTEGER                 NOT NULL REFERENCES tipos_cafe(id),
    cantidad_meta_kg    DECIMAL(12,2)           NOT NULL,
    cantidad_real_kg    DECIMAL(12,2)           DEFAULT 0,
    fecha_inicio        DATE                    NOT NULL,
    fecha_fin           DATE                    NOT NULL,
    estado              estado_plan_maestro     DEFAULT 'borrador',
    responsable         VARCHAR(100),
    notas               TEXT,
    creado_en           TIMESTAMP               DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS ordenes_trabajo (
    id                  SERIAL          PRIMARY KEY,
    numero              VARCHAR(20)     NOT NULL UNIQUE,
    lote_id             INTEGER         NOT NULL REFERENCES lotes(id),
    plan_maestro_id     INTEGER         REFERENCES plan_maestro(id),
    tipo_proceso        tipo_proceso_ot NOT NULL,
    fecha_inicio        DATE            NOT NULL,
    fecha_fin_estimada  DATE,
    fecha_fin_real      DATE,
    avance_pct          DECIMAL(5,2)    DEFAULT 0 CHECK (avance_pct BETWEEN 0 AND 100),
    estado              estado_ot       DEFAULT 'pendiente',
    operador            VARCHAR(100),
    maquinaria          VARCHAR(100),
    notas               TEXT,
    creado_en           TIMESTAMP       DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS ot_consumo_materiales (
    id                  SERIAL          PRIMARY KEY,
    orden_trabajo_id    INTEGER         NOT NULL REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    descripcion         VARCHAR(200)    NOT NULL,
    cantidad            DECIMAL(10,3)   NOT NULL,
    unidad              VARCHAR(20)     NOT NULL DEFAULT 'kg',
    costo_unitario      DECIMAL(10,4),
    moneda              moneda_tipo     DEFAULT 'PEN',
    fecha               DATE            NOT NULL,
    notas               TEXT
);

-- ── INVENTARIO / ALMACENES ────────────────────────────────
CREATE TABLE IF NOT EXISTS almacenes (
    id                  SERIAL          PRIMARY KEY,
    codigo              VARCHAR(20)     NOT NULL UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    ubicacion           VARCHAR(200),
    capacidad_kg        DECIMAL(12,2),
    tipo                tipo_almacen    DEFAULT 'materia_prima',
    activo              BOOLEAN         DEFAULT TRUE,
    notas               TEXT
);

CREATE TABLE IF NOT EXISTS kardex_almacen (
    id                  SERIAL      PRIMARY KEY,
    kardex_id           INTEGER     NOT NULL REFERENCES kardex(id),
    almacen_origen_id   INTEGER     REFERENCES almacenes(id),
    almacen_destino_id  INTEGER     REFERENCES almacenes(id)
);

-- ── COMPRAS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS proveedores (
    id                  SERIAL              PRIMARY KEY,
    razon_social        VARCHAR(200)        NOT NULL,
    ruc                 VARCHAR(11)         UNIQUE,
    contacto            VARCHAR(100),
    telefono            VARCHAR(20),
    email               VARCHAR(100),
    direccion           TEXT,
    categoria           categoria_proveedor DEFAULT 'insumos',
    condiciones_pago    VARCHAR(100),
    activo              BOOLEAN             DEFAULT TRUE,
    notas               TEXT,
    creado_en           TIMESTAMP           DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS requisiciones (
    id                  SERIAL              PRIMARY KEY,
    numero              VARCHAR(20)         NOT NULL UNIQUE,
    area_solicitante    VARCHAR(100)        NOT NULL,
    solicitante         VARCHAR(100),
    fecha_solicitud     DATE                NOT NULL,
    fecha_requerida     DATE,
    estado              estado_requisicion  DEFAULT 'pendiente',
    aprobado_por        VARCHAR(100),
    notas               TEXT,
    creado_en           TIMESTAMP           DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS requisicion_items (
    id                  SERIAL          PRIMARY KEY,
    requisicion_id      INTEGER         NOT NULL REFERENCES requisiciones(id) ON DELETE CASCADE,
    descripcion         VARCHAR(200)    NOT NULL,
    cantidad            DECIMAL(10,3)   NOT NULL,
    unidad              VARCHAR(20)     NOT NULL,
    justificacion       TEXT
);

CREATE TABLE IF NOT EXISTS ordenes_compra (
    id                  SERIAL      PRIMARY KEY,
    numero              VARCHAR(20) NOT NULL UNIQUE,
    proveedor_id        INTEGER     NOT NULL REFERENCES proveedores(id),
    requisicion_id      INTEGER     REFERENCES requisiciones(id),
    fecha_emision       DATE        NOT NULL,
    fecha_entrega       DATE,
    estado              estado_oc   DEFAULT 'borrador',
    moneda              moneda_tipo DEFAULT 'PEN',
    tipo_cambio         DECIMAL(8,4) DEFAULT 1,
    subtotal            DECIMAL(12,2) DEFAULT 0,
    igv                 DECIMAL(12,2) DEFAULT 0,
    total               DECIMAL(12,2) DEFAULT 0,
    notas               TEXT,
    creado_en           TIMESTAMP   DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS oc_items (
    id                  SERIAL          PRIMARY KEY,
    orden_compra_id     INTEGER         NOT NULL REFERENCES ordenes_compra(id) ON DELETE CASCADE,
    descripcion         VARCHAR(200)    NOT NULL,
    cantidad            DECIMAL(10,3)   NOT NULL,
    unidad              VARCHAR(20)     NOT NULL DEFAULT 'und',
    precio_unitario     DECIMAL(10,4)   NOT NULL,
    subtotal            DECIMAL(12,2)   GENERATED ALWAYS AS (cantidad * precio_unitario) STORED
);

CREATE TABLE IF NOT EXISTS cuentas_pagar (
    id                  SERIAL              PRIMARY KEY,
    proveedor_id        INTEGER             NOT NULL REFERENCES proveedores(id),
    orden_compra_id     INTEGER             REFERENCES ordenes_compra(id),
    numero_documento    VARCHAR(50)         NOT NULL,
    tipo_documento      tipo_documento_cp   DEFAULT 'factura',
    fecha_emision       DATE                NOT NULL,
    fecha_vencimiento   DATE                NOT NULL,
    monto_total         DECIMAL(12,2)       NOT NULL,
    monto_pagado        DECIMAL(12,2)       DEFAULT 0,
    moneda              moneda_tipo         DEFAULT 'PEN',
    estado              estado_cp           DEFAULT 'pendiente',
    notas               TEXT,
    creado_en           TIMESTAMP           DEFAULT NOW()
);

-- ── VENTAS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cotizaciones (
    id                  SERIAL              PRIMARY KEY,
    numero              VARCHAR(20)         NOT NULL UNIQUE,
    comprador_id        INTEGER             NOT NULL REFERENCES clientes(id),
    lote_id             INTEGER             NOT NULL REFERENCES lotes(id),
    fecha_cotizacion    DATE                NOT NULL,
    fecha_vencimiento   DATE                NOT NULL,
    cantidad_kg         DECIMAL(10,2)       NOT NULL,
    precio_usd_kg       DECIMAL(10,4)       NOT NULL,
    total_usd           DECIMAL(12,2)       GENERATED ALWAYS AS (cantidad_kg * precio_usd_kg) STORED,
    estado              estado_cotizacion   DEFAULT 'borrador',
    incoterm            VARCHAR(10)         DEFAULT 'FOB',
    condiciones         TEXT,
    notas               TEXT,
    venta_id            INTEGER             REFERENCES ventas(id),
    creado_en           TIMESTAMP           DEFAULT NOW()
);

-- ── FINANCIERO ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS centros_costo (
    id                  SERIAL          PRIMARY KEY,
    codigo              VARCHAR(20)     NOT NULL UNIQUE,
    nombre              VARCHAR(100)    NOT NULL,
    descripcion         TEXT,
    padre_id            INTEGER         REFERENCES centros_costo(id),
    activo              BOOLEAN         DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS plan_cuentas (
    id                  SERIAL                  PRIMARY KEY,
    codigo              VARCHAR(20)             NOT NULL UNIQUE,
    nombre              VARCHAR(200)            NOT NULL,
    tipo                tipo_cuenta_contable    NOT NULL,
    padre_id            INTEGER                 REFERENCES plan_cuentas(id),
    nivel               SMALLINT                DEFAULT 1,
    acepta_movimientos  BOOLEAN                 DEFAULT TRUE,
    activo              BOOLEAN                 DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS asientos_contables (
    id                  SERIAL          PRIMARY KEY,
    numero              VARCHAR(20)     NOT NULL UNIQUE,
    fecha               DATE            NOT NULL,
    concepto            VARCHAR(300)    NOT NULL,
    referencia_tipo     VARCHAR(50),
    referencia_id       INTEGER,
    estado              estado_asiento  DEFAULT 'borrador',
    total_debe          DECIMAL(12,2)   DEFAULT 0,
    total_haber         DECIMAL(12,2)   DEFAULT 0,
    creado_en           TIMESTAMP       DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS asiento_lineas (
    id                  SERIAL          PRIMARY KEY,
    asiento_id          INTEGER         NOT NULL REFERENCES asientos_contables(id) ON DELETE CASCADE,
    cuenta_id           INTEGER         NOT NULL REFERENCES plan_cuentas(id),
    centro_costo_id     INTEGER         REFERENCES centros_costo(id),
    debe                DECIMAL(12,2)   DEFAULT 0,
    haber               DECIMAL(12,2)   DEFAULT 0,
    descripcion         VARCHAR(200)
);

CREATE TABLE IF NOT EXISTS flujo_caja (
    id                  SERIAL          PRIMARY KEY,
    fecha               DATE            NOT NULL,
    tipo                tipo_flujo      NOT NULL,
    categoria           categoria_flujo DEFAULT 'operativo',
    concepto            VARCHAR(200)    NOT NULL,
    monto               DECIMAL(12,2)   NOT NULL,
    moneda              moneda_tipo     DEFAULT 'PEN',
    tipo_cambio         DECIMAL(8,4)    DEFAULT 1,
    monto_pen           DECIMAL(12,2)   GENERATED ALWAYS AS (monto * tipo_cambio) STORED,
    referencia_tipo     VARCHAR(50),
    referencia_id       INTEGER,
    cuenta_banco        VARCHAR(100),
    centro_costo_id     INTEGER         REFERENCES centros_costo(id),
    notas               TEXT,
    creado_en           TIMESTAMP       DEFAULT NOW()
);

-- Datos base: centros de costo
INSERT INTO centros_costo (codigo, nombre) VALUES
    ('PROD',  'Producción'),
    ('INVEN', 'Inventario'),
    ('COMP',  'Compras'),
    ('VENT',  'Ventas'),
    ('ADMIN', 'Administración'),
    ('CALID', 'Control de Calidad')
ON CONFLICT (codigo) DO NOTHING;

-- ── ÍNDICES ───────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_ot_lote        ON ordenes_trabajo(lote_id);
CREATE INDEX IF NOT EXISTS idx_ot_estado      ON ordenes_trabajo(estado);
CREATE INDEX IF NOT EXISTS idx_cxp_estado     ON cuentas_pagar(estado, fecha_vencimiento);
CREATE INDEX IF NOT EXISTS idx_cotiz_estado   ON cotizaciones(estado, fecha_vencimiento);
CREATE INDEX IF NOT EXISTS idx_fc_fecha_tipo  ON flujo_caja(fecha, tipo);
CREATE INDEX IF NOT EXISTS idx_asiento_ref    ON asientos_contables(referencia_tipo, referencia_id);
