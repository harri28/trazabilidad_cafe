# Sistema de Trazabilidad de Café — Documentación API

**Base URL:** `http://localhost/trazabilidad_cafe/api`

---

## Modelo de Base de Datos

```
┌─────────────────────────────────────────────────────────────────────┐
│                    DIAGRAMA LÓGICO DEL SISTEMA                      │
└─────────────────────────────────────────────────────────────────────┘

  [tipos_cafe]          [certificaciones_catalogo]
       │                          │
       │ 1:N                      │ N:M (via lote_certificaciones)
       ▼                          ▼
  [clientes] ──── 1:N ────► [lotes] ◄──────────────────┐
  (productor /                 │                         │
   comprador)                  │ 1:N                     │
                               │                         │ 1:N
                    ┌──────────┴──────────┐              │
                    ▼                     ▼              │
               [kardex]          [laboratorio_analisis]  │
         (entradas/salidas)      (análisis de calidad)   │
                                                         │
  [clientes]                                             │
  (comprador) ──── 1:N ─────► [ventas] ────────────────►┘
                               (contratos)

  [transformaciones]  vincula dos lotes (origen → destino)
```

### Tabla: clientes
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | Identificador único |
| tipo | ENUM | productor / comprador / ambos |
| razon_social | VARCHAR(150) | Nombre o razón social |
| ruc_dni | VARCHAR(20) UNIQUE | Documento de identidad |
| departamento / provincia / distrito | VARCHAR | Ubicación |
| altitud_msnm | SMALLINT | Altitud de la finca |
| hectareas | DECIMAL | Extensión de cultivo |
| asociacion | VARCHAR | Cooperativa o asociación |
| pais_destino | VARCHAR | Para compradores exportación |

### Tabla: lotes
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | Identificador único |
| codigo | VARCHAR(30) UNIQUE | LOT-AAAA-NNNN (autogenerado) |
| estado | ENUM | acopio / proceso / disponible / vendido / parcial |
| tipo_cafe_id | FK | Pergamino / Oro / Tostado |
| productor_id | FK | Cliente tipo productor |
| peso_inicial_kg | DECIMAL | Peso en acopio |
| peso_actual_kg | DECIMAL | Se actualiza con cada movimiento |
| merma_kg | DECIMAL GENERATED | peso_inicial - peso_final |
| rendimiento_pct | DECIMAL GENERATED | (peso_final / peso_inicial) × 100 |
| variedad | VARCHAR | Typica, Caturra, Gesha, etc. |
| proceso_beneficio | ENUM | lavado / natural / honey |

### Tabla: kardex
| Campo | Tipo | Descripción |
|-------|------|-------------|
| tipo_movimiento | ENUM | entrada / salida / ajuste / transformacion |
| cantidad_kg | DECIMAL | Siempre positivo |
| precio_unitario | DECIMAL | PEN o USD por kg |
| total_monto | DECIMAL GENERATED | cantidad × precio |
| prima_diferencial | DECIMAL | Prima adicional por kg (Fair Trade, etc.) |
| prima_total | DECIMAL GENERATED | cantidad × prima_diferencial |
| saldo_kg | DECIMAL | Saldo tras el movimiento (calculado por trigger) |

### Tabla: laboratorio_analisis
| Campo | Tipo | Descripción |
|-------|------|-------------|
| humedad_pct | DECIMAL | % humedad (óptimo 10–12%) |
| rendimiento_pct | DECIMAL | % conversión pergamino→oro |
| defectos_cat1 | SMALLINT | Defectos primarios SCAA |
| defectos_cat2 | SMALLINT | Defectos secundarios SCAA |
| score_taza | DECIMAL | Puntaje SCA (0–100) |
| clasificacion | ENUM GENERATED | specialty(≥80) / premium(≥75) / comercial(≥60) / descarte |
| fragancia, aroma, sabor... | DECIMAL | Atributos de catación (6–10 c/u) |

### Tabla: ventas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| numero_contrato | VARCHAR UNIQUE | CONT-AAAA-NNNN (autogenerado) |
| estado | ENUM | borrador / confirmado / en_proceso / entregado / cancelado |
| cantidad_kg | DECIMAL | Cantidad acordada |
| precio_usd_kg | DECIMAL | Precio en USD por kg |
| tipo_cambio | DECIMAL | Soles por USD |
| total_usd | DECIMAL GENERATED | cantidad × precio_usd_kg |
| total_local | DECIMAL GENERATED | total_usd × tipo_cambio |
| incoterm | VARCHAR | FOB / CIF / EXW / DDP |

---

## Endpoints API

### Clientes
```
GET    /clientes                    Lista con filtros: tipo, search, page
GET    /clientes/{id}               Detalle de un cliente
POST   /clientes                    Crear cliente
PUT    /clientes/{id}               Actualizar cliente
DELETE /clientes/{id}               Baja lógica (activo=0)
GET    /clientes/{id}/lotes         Lotes del productor
```

### Lotes
```
GET    /lotes                       Lista con filtros: estado, campana, productor_id, certificacion
GET    /lotes/{id}                  Detalle completo (kardex, análisis, ventas, certs)
POST   /lotes                       Crear lote + entrada automática en kardex
PUT    /lotes/{id}                  Actualizar datos del lote
POST   /lotes/{id}/certificaciones  Agregar certificación al lote
POST   /lotes/{id}/transformar      Transformación (pergamino→oro), actualiza pesos y kardex
```

### Kardex
```
GET    /kardex                      Lista con filtros: lote_id, tipo, desde, hasta
POST   /kardex                      Registrar movimiento manual
GET    /kardex/resumen              Resumen de entradas/salidas por lote
GET    /kardex/reporte-costos       Costos agregados por campaña
```

### Laboratorio
```
GET    /laboratorio                 Lista con filtros: lote_id, clasificacion, aprobado, desde
GET    /laboratorio/{id}            Detalle de análisis
POST   /laboratorio                 Registrar análisis (clasifica automáticamente)
PUT    /laboratorio/{id}            Actualizar análisis
GET    /laboratorio/estadisticas    Promedios por variedad, productor, campaña
```

### Ventas
```
GET    /ventas                      Lista con filtros: estado, comprador_id, lote_id, desde
GET    /ventas/{id}                 Detalle del contrato
POST   /ventas                      Crear contrato en borrador (valida stock)
PUT    /ventas/{id}/confirmar       Confirmar venta → descuenta stock en kardex
PUT    /ventas/{id}/cancelar        Cancelar venta (revierte kardex si estaba confirmada)
GET    /ventas/dashboard            Métricas de ventas por campaña
```

---

## Flujo Completo de un Lote

```
1. REGISTRO DE PRODUCTOR
   POST /clientes
   { "tipo": "productor", "razon_social": "Juan Huamán", "departamento": "Cajamarca",
     "altitud_msnm": 1850, "hectareas": 3.5, "asociacion": "Coop. Norte Andino" }
   → id: 12

2. ACOPIO (ENTRADA A KARDEX)
   POST /lotes
   { "productor_id": 12, "tipo_cafe_id": 1, "fecha_acopio": "2024-09-10",
     "peso_inicial_kg": 1250.500, "precio_unitario": 8.50, "moneda": "PEN",
     "prima_diferencial": 0.60, "variedad": "Caturra", "proceso_beneficio": "lavado",
     "campana": 2024 }
   → codigo: "LOT-2024-0001", kardex entrada automática creada

3. ANÁLISIS DE LABORATORIO
   POST /laboratorio
   { "lote_id": 1, "fecha_analisis": "2024-09-12",
     "humedad_pct": 11.2, "rendimiento_pct": 78.5,
     "score_taza": 83.25, "fragancia": 8.0, "aroma": 8.25,
     "sabor": 8.0, "acidez": 8.5, "cuerpo": 7.75, "balance": 8.0,
     "defectos_cat1": 0, "defectos_cat2": 2 }
   → clasificacion: "specialty", alertas: []

4. AGREGAR CERTIFICACIÓN
   POST /lotes/1/certificaciones
   { "certificacion_id": 1, "numero_certificado": "RFA-2024-PE-1234",
     "fecha_inicio": "2024-01-01", "fecha_vencimiento": "2024-12-31" }

5. TRANSFORMACIÓN (PERGAMINO → ORO)
   POST /lotes/1/transformar
   { "tipo_transformacion": "Despergaminado", "fecha": "2024-09-15",
     "peso_salida_kg": 981.250, "operador": "Carlos Vega" }
   → merma_kg: 269.250, rendimiento_pct: 78.47
   → kardex: salida (1250.5) + entrada (981.25)
   → lote.estado → "disponible"

6. REGISTRO DEL COMPRADOR
   POST /clientes
   { "tipo": "comprador", "razon_social": "Specialty Coffee GmbH",
     "pais_destino": "Alemania", "moneda_pref": "USD" }
   → id: 25

7. CREAR CONTRATO DE VENTA
   POST /ventas
   { "comprador_id": 25, "lote_id": 1, "fecha_contrato": "2024-09-20",
     "cantidad_kg": 900.000, "precio_usd_kg": 4.8500,
     "tipo_cambio": 3.7500, "incoterm": "FOB", "puerto_embarque": "Callao" }
   → numero_contrato: "CONT-2024-0001"
   → total_usd: 4365.00, total_local: 16368.75
   → Estado: borrador (stock AÚN no descontado)

8. CONFIRMAR VENTA (DESCUENTA STOCK)
   PUT /ventas/1/confirmar
   → kardex: salida 900.000 kg
   → lote.estado → "parcial" (quedan 81.25 kg)
```

---

## Reglas de Negocio

### Inventario y Kardex
- El stock nunca puede ser negativo (trigger de base de datos lo previene)
- Cada entrada al sistema genera automáticamente un movimiento en kardex
- La confirmación de una venta es la única que descuenta stock físicamente
- Una venta en borrador no reserva stock (puede implementarse si se requiere)

### Lotes y Trazabilidad
- El código de lote es único e inmutable: `LOT-{AÑO}-{SECUENCIAL}`
- Un lote en estado `vendido` no puede recibir más salidas
- La transformación crea una salida + entrada al mismo lote con los nuevos pesos
- Cada lote mantiene `peso_inicial_kg` histórico para calcular merma total

### Laboratorio y Calidad
- Un lote puede tener múltiples análisis (se considera el más reciente)
- Clasificación automática por score SCA:
  - ≥ 80 pts → Specialty
  - ≥ 75 pts → Premium
  - ≥ 60 pts → Comercial
  - < 60 pts → Descarte
- Alertas automáticas si humedad < 10% o > 14%
- Alertas si score < 60 (recomendación de renegociación)

### Ventas y Contratos
- Solo se pueden vender lotes en estado `disponible` o `parcial`
- El sistema valida stock libre (resta ventas activas pendientes de entrega)
- Una venta cancelada revierte el movimiento de kardex si ya estaba confirmada
- El precio siempre se registra en USD; el total local se calcula automáticamente

---

## Posibles Mejoras (IA y Automatización)

### 1. Predicción de Calidad (ML)
- Modelo de regresión entrenado con datos históricos de:
  - Altitud, variedad, proceso, humedad → predicción de score SCA
- Alertas tempranas antes del análisis formal

### 2. Precio Inteligente
- Integración con API de precios de café (ICO, NYSE:KC)
- Sugerencia de precio de venta basado en clasificación + mercado actual

### 3. Trazabilidad Blockchain (QR)
- Cada lote genera un QR que enlaza a página pública de trazabilidad
- El consumidor final puede ver: productor, finca, análisis, proceso, certificaciones

### 4. Dashboard Predictivo
- Forecast de producción por campaña y región
- Detección de anomalías en rendimiento (merma inusualmente alta)

### 5. Integración de Balanzas
- Lectura automática de peso desde balanza conectada a USB/RS232
- Eliminación de errores de entrada manual

### 6. App Móvil para Campo
- Registro de acopio offline (sync cuando hay red)
- Foto de la finca y del café al momento del acopio
- Geolocalización automática del proveedor

### 7. Módulo Contable
- Liquidaciones a productores con detalle de primas y descuentos
- Exportación a formatos SUNAT (Perú) para facturas electrónicas
- Integración con sistemas ERP

### 8. Control de Calidad Automático (Computer Vision)
- Análisis de imagen del grano para contar defectos automáticamente
- Clasificación por color para identificar granos brocados/negros
