-- ============================================================
--  SCHEMA V2 – Nuevos módulos
--  Ejecutar sobre la base existente (trazabilidad_cafe)
-- ============================================================

-- ── PRODUCCIÓN ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS plan_maestro (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    campaña             YEAR          NOT NULL,
    tipo_cafe_id        INT           NOT NULL,
    cantidad_meta_kg    DECIMAL(12,2) NOT NULL,
    cantidad_real_kg    DECIMAL(12,2) DEFAULT 0,
    fecha_inicio        DATE          NOT NULL,
    fecha_fin           DATE          NOT NULL,
    estado              ENUM('borrador','activo','cerrado') DEFAULT 'borrador',
    responsable         VARCHAR(100),
    notas               TEXT,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tipo_cafe_id) REFERENCES tipos_cafe(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ordenes_trabajo (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)   NOT NULL UNIQUE,
    lote_id             INT           NOT NULL,
    plan_maestro_id     INT,
    tipo_proceso        ENUM('secado','despergaminado','tostado','molido','envasado','seleccion','otro') NOT NULL,
    fecha_inicio        DATE          NOT NULL,
    fecha_fin_estimada  DATE,
    fecha_fin_real      DATE,
    avance_pct          DECIMAL(5,2)  DEFAULT 0,
    estado              ENUM('pendiente','en_proceso','pausada','completada','cancelada') DEFAULT 'pendiente',
    operador            VARCHAR(100),
    maquinaria          VARCHAR(100),
    notas               TEXT,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lote_id)         REFERENCES lotes(id),
    FOREIGN KEY (plan_maestro_id) REFERENCES plan_maestro(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ot_consumo_materiales (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    orden_trabajo_id    INT           NOT NULL,
    descripcion         VARCHAR(200)  NOT NULL,
    cantidad            DECIMAL(10,3) NOT NULL,
    unidad              VARCHAR(20)   NOT NULL DEFAULT 'kg',
    costo_unitario      DECIMAL(10,4),
    moneda              ENUM('PEN','USD','EUR') DEFAULT 'PEN',
    fecha               DATE          NOT NULL,
    notas               TEXT,
    FOREIGN KEY (orden_trabajo_id) REFERENCES ordenes_trabajo(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── INVENTARIO / ALMACENES ────────────────────────────────
CREATE TABLE IF NOT EXISTS almacenes (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    codigo              VARCHAR(20)   NOT NULL UNIQUE,
    nombre              VARCHAR(100)  NOT NULL,
    ubicacion           VARCHAR(200),
    capacidad_kg        DECIMAL(12,2),
    tipo                ENUM('materia_prima','proceso','producto_terminado') DEFAULT 'materia_prima',
    activo              TINYINT(1)    DEFAULT 1,
    notas               TEXT
) ENGINE=InnoDB;

-- Movimientos por almacén (extiende kardex hacia ubicaciones físicas)
CREATE TABLE IF NOT EXISTS kardex_almacen (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    kardex_id           INT           NOT NULL,
    almacen_origen_id   INT,
    almacen_destino_id  INT,
    FOREIGN KEY (kardex_id)          REFERENCES kardex(id),
    FOREIGN KEY (almacen_origen_id)  REFERENCES almacenes(id),
    FOREIGN KEY (almacen_destino_id) REFERENCES almacenes(id)
) ENGINE=InnoDB;

-- ── COMPRAS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS proveedores (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    razon_social        VARCHAR(200)  NOT NULL,
    ruc                 VARCHAR(11)   UNIQUE,
    contacto            VARCHAR(100),
    telefono            VARCHAR(20),
    email               VARCHAR(100),
    direccion           TEXT,
    categoria           ENUM('insumos','servicios','transporte','maquinaria','otro') DEFAULT 'insumos',
    condiciones_pago    VARCHAR(100),
    activo              TINYINT(1)    DEFAULT 1,
    notas               TEXT,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS requisiciones (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)   NOT NULL UNIQUE,
    area_solicitante    VARCHAR(100)  NOT NULL,
    solicitante         VARCHAR(100),
    fecha_solicitud     DATE          NOT NULL,
    fecha_requerida     DATE,
    estado              ENUM('pendiente','aprobada','rechazada','en_proceso','completada') DEFAULT 'pendiente',
    aprobado_por        VARCHAR(100),
    notas               TEXT,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS requisicion_items (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    requisicion_id      INT           NOT NULL,
    descripcion         VARCHAR(200)  NOT NULL,
    cantidad            DECIMAL(10,3) NOT NULL,
    unidad              VARCHAR(20)   NOT NULL,
    justificacion       TEXT,
    FOREIGN KEY (requisicion_id) REFERENCES requisiciones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ordenes_compra (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)   NOT NULL UNIQUE,
    proveedor_id        INT           NOT NULL,
    requisicion_id      INT,
    fecha_emision       DATE          NOT NULL,
    fecha_entrega       DATE,
    estado              ENUM('borrador','enviada','confirmada','parcial','completada','cancelada') DEFAULT 'borrador',
    moneda              ENUM('PEN','USD','EUR') DEFAULT 'PEN',
    tipo_cambio         DECIMAL(8,4)  DEFAULT 1,
    subtotal            DECIMAL(12,2) DEFAULT 0,
    igv                 DECIMAL(12,2) DEFAULT 0,
    total               DECIMAL(12,2) DEFAULT 0,
    notas               TEXT,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id)   REFERENCES proveedores(id),
    FOREIGN KEY (requisicion_id) REFERENCES requisiciones(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS oc_items (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    orden_compra_id     INT           NOT NULL,
    descripcion         VARCHAR(200)  NOT NULL,
    cantidad            DECIMAL(10,3) NOT NULL,
    unidad              VARCHAR(20)   NOT NULL DEFAULT 'und',
    precio_unitario     DECIMAL(10,4) NOT NULL,
    subtotal            DECIMAL(12,2) AS (cantidad * precio_unitario) STORED,
    FOREIGN KEY (orden_compra_id) REFERENCES ordenes_compra(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cuentas_pagar (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id        INT           NOT NULL,
    orden_compra_id     INT,
    numero_documento    VARCHAR(50)   NOT NULL,
    tipo_documento      ENUM('factura','boleta','recibo','nota_debito','otro') DEFAULT 'factura',
    fecha_emision       DATE          NOT NULL,
    fecha_vencimiento   DATE          NOT NULL,
    monto_total         DECIMAL(12,2) NOT NULL,
    monto_pagado        DECIMAL(12,2) DEFAULT 0,
    moneda              ENUM('PEN','USD','EUR') DEFAULT 'PEN',
    estado              ENUM('pendiente','parcial','pagado','vencido') DEFAULT 'pendiente',
    notas               TEXT,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id)    REFERENCES proveedores(id),
    FOREIGN KEY (orden_compra_id) REFERENCES ordenes_compra(id)
) ENGINE=InnoDB;

-- ── VENTAS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cotizaciones (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)   NOT NULL UNIQUE,
    comprador_id        INT           NOT NULL,
    lote_id             INT           NOT NULL,
    fecha_cotizacion    DATE          NOT NULL,
    fecha_vencimiento   DATE          NOT NULL,
    cantidad_kg         DECIMAL(10,2) NOT NULL,
    precio_usd_kg       DECIMAL(10,4) NOT NULL,
    total_usd           DECIMAL(12,2) AS (cantidad_kg * precio_usd_kg) STORED,
    estado              ENUM('borrador','enviada','aceptada','rechazada','vencida') DEFAULT 'borrador',
    incoterm            VARCHAR(10)   DEFAULT 'FOB',
    condiciones         TEXT,
    notas               TEXT,
    venta_id            INT,                          -- si se convirtió en venta
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comprador_id) REFERENCES clientes(id),
    FOREIGN KEY (lote_id)      REFERENCES lotes(id),
    FOREIGN KEY (venta_id)     REFERENCES ventas(id)
) ENGINE=InnoDB;

-- ── FINANCIERO ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS centros_costo (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    codigo              VARCHAR(20)   NOT NULL UNIQUE,
    nombre              VARCHAR(100)  NOT NULL,
    descripcion         TEXT,
    padre_id            INT,
    activo              TINYINT(1)    DEFAULT 1,
    FOREIGN KEY (padre_id) REFERENCES centros_costo(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plan_cuentas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    codigo              VARCHAR(20)   NOT NULL UNIQUE,
    nombre              VARCHAR(200)  NOT NULL,
    tipo                ENUM('activo','pasivo','patrimonio','ingreso','gasto','costo') NOT NULL,
    padre_id            INT,
    nivel               TINYINT       DEFAULT 1,
    acepta_movimientos  TINYINT(1)    DEFAULT 1,
    activo              TINYINT(1)    DEFAULT 1,
    FOREIGN KEY (padre_id) REFERENCES plan_cuentas(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS asientos_contables (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)   NOT NULL UNIQUE,
    fecha               DATE          NOT NULL,
    concepto            VARCHAR(300)  NOT NULL,
    referencia_tipo     VARCHAR(50),   -- venta, compra, ot, kardex, etc.
    referencia_id       INT,
    estado              ENUM('borrador','validado','anulado') DEFAULT 'borrador',
    total_debe          DECIMAL(12,2) DEFAULT 0,
    total_haber         DECIMAL(12,2) DEFAULT 0,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS asiento_lineas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    asiento_id          INT           NOT NULL,
    cuenta_id           INT           NOT NULL,
    centro_costo_id     INT,
    debe                DECIMAL(12,2) DEFAULT 0,
    haber               DECIMAL(12,2) DEFAULT 0,
    descripcion         VARCHAR(200),
    FOREIGN KEY (asiento_id)      REFERENCES asientos_contables(id) ON DELETE CASCADE,
    FOREIGN KEY (cuenta_id)       REFERENCES plan_cuentas(id),
    FOREIGN KEY (centro_costo_id) REFERENCES centros_costo(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS flujo_caja (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    fecha               DATE          NOT NULL,
    tipo                ENUM('ingreso','egreso')  NOT NULL,
    categoria           ENUM('operativo','financiero','inversion') DEFAULT 'operativo',
    concepto            VARCHAR(200)  NOT NULL,
    monto               DECIMAL(12,2) NOT NULL,
    moneda              ENUM('PEN','USD','EUR') DEFAULT 'PEN',
    tipo_cambio         DECIMAL(8,4)  DEFAULT 1,
    monto_pen           DECIMAL(12,2) AS (monto * tipo_cambio) STORED,
    referencia_tipo     VARCHAR(50),
    referencia_id       INT,
    cuenta_banco        VARCHAR(100),
    centro_costo_id     INT,
    notas               TEXT,
    creado_en           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (centro_costo_id) REFERENCES centros_costo(id)
) ENGINE=InnoDB;

-- Datos base: centros de costo iniciales
INSERT IGNORE INTO centros_costo (codigo, nombre) VALUES
    ('PROD',  'Producción'),
    ('INVEN', 'Inventario'),
    ('COMP',  'Compras'),
    ('VENT',  'Ventas'),
    ('ADMIN', 'Administración'),
    ('CALID', 'Control de Calidad');

-- Índices de performance
CREATE INDEX IF NOT EXISTS idx_ot_lote       ON ordenes_trabajo(lote_id);
CREATE INDEX IF NOT EXISTS idx_ot_estado     ON ordenes_trabajo(estado);
CREATE INDEX IF NOT EXISTS idx_cxp_estado    ON cuentas_pagar(estado, fecha_vencimiento);
CREATE INDEX IF NOT EXISTS idx_cotiz_estado  ON cotizaciones(estado, fecha_vencimiento);
CREATE INDEX IF NOT EXISTS idx_fc_fecha_tipo ON flujo_caja(fecha, tipo);
CREATE INDEX IF NOT EXISTS idx_asiento_ref   ON asientos_contables(referencia_tipo, referencia_id);
