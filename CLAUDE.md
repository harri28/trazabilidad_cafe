# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Sistema de Trazabilidad de Café** — a PHP REST API (v2.0.0) for coffee supply chain management in the Peruvian specialty coffee industry. Tracks batches (lotes) from producer to buyer, managing inventory (kardex), quality lab analysis, certifications, transformations (pergamino→oro), sales contracts, production orders, purchasing, and accounting.

## Stack & Environment

- **Runtime:** PHP 8.0+ (no Composer, no framework)
- **Database:** PostgreSQL 14+ via PDO — credentials in `api/config/database.php` (default: user `postgresql`, password `1234`, port 5432)
- **Web server:** Apache via XAMPP at `http://localhost/trazabilidad_cafe/api/`
- **Frontend:** Vanilla HTML/CSS/JS (`public/`) — partially implemented

## Running the Application

No build step. Start Apache and PostgreSQL, then the API is live at `http://localhost/trazabilidad_cafe/api/`.

Database setup (run all in order):

```sql
CREATE DATABASE trazabilidad_cafe ENCODING 'UTF8';
-- \c trazabilidad_cafe
-- \i database/schema_pg.sql           (core tables, triggers, views)
-- \i database/schema_v2_pg.sql        (production, purchasing, finance)
-- \i database/schema_campanas.sql     (campaigns + backup log)
-- \i database/schema_configuracion.sql (key-value config table)
-- \i database/schema_sunat_ventas.sql (SUNAT electronic invoicing)
```

Quick connectivity check: `GET /` returns a JSON health response with module list.

## Architecture

### Request Flow

```
HTTP Request
  → Apache (.htaccess rewrites all to api/index.php)
  → api/index.php  (registers routes, recursive autoload from api/modules/)
  → middleware/Router.php  (matches method + URI, extracts {params})
  → controllers/*Controller.php  (business logic + raw PDO queries)
  → middleware/Response.php  (standardized JSON output)
```

### Module Structure

Controllers live under `api/modules/` organized by domain:

```
api/modules/
  operativo/
    produccion/   LoteController, PlanMaestroController, OrdenTrabajoController
    inventario/   KardexController, StockController, ValorizacionController
    compras/      ProveedorController, RequisicionController, OrdenCompraController,
                  CuentaPagarController, CompraController
    ventas/       ClienteController, VentaController, CotizacionController
  administrativo/
    calidad/      LaboratorioController
    financiero/   AsientoContableController, FlujoCajaController, CentroCostoController,
                  FinancieroController
    documental/   DocumentoController
    CampanaController     (campaigns + backup log — no subdirectory)
    ConfiguracionController (key-value system settings — no subdirectory)
  inteligencia/
    dashboard/    DashboardController
    trazabilidad/ TrazabilidadController
  integraciones/
    sunat/        SunatController (uses SunatApiClient from api/config/)
    externas/     ApiExternaController
```

The autoloader (`spl_autoload_register` in `index.php`) does a **recursive search** through `api/modules/` by filename — class name must match filename exactly.

### Key Architectural Decisions

- **No ORM.** Raw PDO with prepared statements directly in controllers.
- **Business logic is split:** PHP controllers handle validation/orchestration; the database handles stock updates, state transitions, and aggregations via triggers and views.
- **PostgreSQL-specific:** Uses `SERIAL` PKs (not AUTO_INCREMENT), native ENUMs (`CREATE TYPE`), `STRING_AGG()` instead of `GROUP_CONCAT()`, and `Database::lastId($db, $tabla)` which calls `lastInsertId("{tabla}_id_seq")`.
- **Transactions** are used wherever multiple tables must be written atomically (e.g. `LoteController::store()` writes lotes + kardex + certificaciones together).
- **Request body:** Read with `json_decode(file_get_contents('php://input'), true) ?? []`. Controllers never use `$_POST` for JSON bodies.

### Response Helpers (`api/middleware/Response.php`)

| Method | Use |
|---|---|
| `Response::json($data, $code=200)` | Success responses (also used for 4xx by passing code) |
| `Response::error($msg, $code=400, $details=[])` | Validation and error responses |
| `Response::paginated($items, $total, $page, $per_page)` | Paginated list endpoints |

> **Note:** There is no `Response::success()`. Use `Response::json()` for success. The pagination method is `paginated()`, not `paginate()`.

### Core Domain Entities

| Entity | Table | Notes |
|---|---|---|
| Lote | `lotes` | Central unit; states: acopio→proceso→disponible→parcial/vendido; `peso_actual_kg` managed by kardex trigger |
| Cliente | `clientes` | Dual-role: producer (`productor`) and buyer (`comprador`) |
| Kardex | `kardex` | Ledger; types: entrada, salida, ajuste, transformacion |
| Venta | `ventas` | States: borrador→confirmado→en_proceso→entregado/cancelado |
| Análisis | `laboratorio_analisis` | SCA cupping score + 11 attributes; quality classification auto-derived |
| Transformación | `transformaciones` | Records weight loss during processing (pergamino→oro) |
| Campaña | `campanas` | Annual harvest campaigns; states: activa/cerrada/archivada |
| Configuración | `configuracion` | Key-value system settings (e.g. `tasa_usd`, `tasa_eur`) |

### Database Triggers (load-bearing)

- `trg_kardex_before_insert` — blocks `salida` movements that would make stock negative
- `trg_kardex_after_insert` — updates `lotes.peso_actual_kg` after every kardex movement
- `trg_venta_after_update` — flips lote `estado` to `vendido`/`parcial` when a sale is confirmed

### Views

- `v_lotes_completos`, `v_kardex_resumen`, `v_rentabilidad` — aggregated read-only views

### Authentication

**API:** No authentication middleware. All API endpoints are open (CORS allows all origins). Intended for local/development use only.

**Frontend (`public/`):** PHP session-based login via `public/login.php`. Users are stored in `public/config/users.php` as a plain PHP array with bcrypt passwords. To add a user:
```bash
php -r "echo password_hash('nueva_clave', PASSWORD_DEFAULT);"
```
Then add the hash to `public/config/users.php`. Password reset uses `MailService` (SMTP via `api/config/smtp.php`).

### Email / SMTP

`api/config/MailService.php` — native SMTP client (no Composer dependency). Configured in `api/config/smtp.php`. Used for:
- Password-reset emails (`sendPasswordReset`)
- Sale confirmation emails to buyers (`sendVentaConfirmada`)

### API Endpoints

Base: `/trazabilidad_cafe/api/`

**Core (v1 entities):**
- `GET|POST /clientes`, `GET|PUT|DELETE /clientes/{id}`, `GET /clientes/{id}/lotes`
- `GET|POST /lotes`, `GET|PUT /lotes/{id}`, `POST /lotes/{id}/certificaciones`, `POST /lotes/{id}/transformar`
- `GET|POST /kardex`, `GET /kardex/resumen`, `GET /kardex/reporte-costos`
- `GET|POST /laboratorio`, `GET|PUT /laboratorio/{id}`, `GET /laboratorio/estadisticas`
- `GET|POST /ventas`, `GET /ventas/{id}`, `GET /ventas/dashboard`
- `PUT /ventas/{id}/confirmar|en_proceso|entregar|cancelar`
- `POST /ventas/{id}/facturar`, `POST /ventas/{id}/boleta`

**Extended modules (v2):**
- `GET|POST /produccion/plan-maestro`, `GET /produccion/plan-maestro/mrp`, `GET|PUT /produccion/plan-maestro/{id}`
- `GET|POST /produccion/ordenes-trabajo`, `GET /produccion/ordenes-trabajo/{id}`, `PUT .../avance`, `POST .../materiales`
- `GET /inventario/stock`, `GET /inventario/stock/resumen|alertas`, `GET /inventario/stock/{id}/movimientos`
- `GET /inventario/valorizacion`, `GET /inventario/valorizacion/comparativo`
- `GET|POST /compras/proveedores`, `GET|PUT|DELETE /compras/proveedores/{id}`
- `GET|POST /compras/requisiciones`, `GET /compras/requisiciones/{id}`, `PUT /compras/requisiciones/{id}/aprobar`
- `GET|POST /compras/ordenes`, `GET /compras/ordenes/{id}`, `PUT .../confirmar|completar|cancelar`
- `GET|POST /compras/cuentas-pagar`, `GET /compras/cuentas-pagar/resumen`, `POST .../pagar`
- `GET|POST /ventas/cotizaciones`, `GET /ventas/cotizaciones/{id}`, `PUT .../enviar|convertir|rechazar`
- `GET|POST /financiero/asientos`, `GET|POST /financiero/asientos/plan-cuentas`, `GET /financiero/asientos/{id}`, `PUT .../validar`
- `GET|POST /financiero/flujo-caja`, `GET .../resumen|proyeccion`
- `GET|POST /financiero/centros-costo`, `GET .../analisis`, `GET|PUT /financiero/centros-costo/{id}`
- `GET|POST /documentos`, `GET /documentos/{id}`
- `GET /trazabilidad/resumen`, `GET /trazabilidad/lote/{id}`, `GET /trazabilidad/productor/{id}`, `GET /trazabilidad/cliente/{id}`
- `GET /campanas`, `POST /campanas`, `PUT /campanas/{año}`, `GET /campanas/backups`, `POST /campanas/backups`
- `GET /configuracion`, `GET|PUT /configuracion/{clave}`
- `GET /dashboard`
- `POST /sunat/factura`, `POST /sunat/boleta`, `GET /sunat/consulta-ruc`, `GET|DELETE /sunat/cpe/{id}`
- `GET /externas/tipo-cambio`, `GET /externas/precio-mercado`

### Adding a New Endpoint

1. Register the route in `api/index.php` using `$router->get|post|put|delete()`
2. Create or add a method to the relevant controller in `api/modules/<domain>/<subdomain>/`
3. Filename must match classname exactly (autoloader matches by filename)
4. Use `Response::json($data)`, `Response::error($msg, $code)`, or `Response::paginated(...)` for output
5. Pagination: accept `?page=N&per_page=N` (cap per_page at 100)
6. Read JSON body with `json_decode(file_get_contents('php://input'), true) ?? []`

### CORS

All origins allowed (headers set in `api/index.php`). Intentional for local development.

### HTTP Method Override

`POST` requests can override to `PUT`/`DELETE` by including a `_method` field in the POST body — useful for clients that don't support those verbs.
