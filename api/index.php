<?php
/**
 * ============================================================
 *  SISTEMA DE TRAZABILIDAD DE CAFÉ
 *  API REST  –  Entry Point
 *  Base URL: /trazabilidad_cafe/api/
 * ============================================================
 */

// Headers CORS + JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload de clases
spl_autoload_register(function (string $class): void {
    // Paths fijos: config y middleware
    $fixed = [
        __DIR__ . "/config/{$class}.php",
        __DIR__ . "/middleware/{$class}.php",
    ];
    foreach ($fixed as $path) {
        if (file_exists($path)) { require_once $path; return; }
    }

    // Búsqueda recursiva en modules/
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/modules'));
    foreach ($it as $file) {
        if ($file->getFilename() === "{$class}.php") {
            require_once $file->getPathname();
            return;
        }
    }
});

require_once __DIR__ . '/middleware/Router.php';
require_once __DIR__ . '/middleware/Response.php';

$router = new Router();

// ── CLIENTES ──────────────────────────────────────────────
$router->get('/clientes',              fn()     => (new ClienteController())->index());
$router->get('/clientes/{id}',         fn($p)   => (new ClienteController())->show($p));
$router->post('/clientes',             fn()     => (new ClienteController())->store());
$router->put('/clientes/{id}',         fn($p)   => (new ClienteController())->update($p));
$router->delete('/clientes/{id}',      fn($p)   => (new ClienteController())->destroy($p));
$router->get('/clientes/{id}/acopios',  fn($p)   => (new ClienteController())->acopios($p));

// ── ACOPIOS ───────────────────────────────────────────────
$router->get('/acopios',                        fn()   => (new AcopioController())->index());
$router->get('/acopios/{id}',                   fn($p) => (new AcopioController())->show($p));
$router->post('/acopios',                       fn()   => (new AcopioController())->store());
$router->put('/acopios/{id}',                   fn($p) => (new AcopioController())->update($p));
$router->post('/acopios/{id}/certificaciones',  fn($p) => (new AcopioController())->addCertificacion($p));
$router->post('/acopios/{id}/transformar',      fn($p) => (new AcopioController())->transformar($p));

// ── KARDEX ────────────────────────────────────────────────
$router->get('/kardex',               fn()   => (new KardexController())->index());
$router->post('/kardex',              fn()   => (new KardexController())->store());
$router->get('/kardex/resumen',       fn()   => (new KardexController())->resumen());
$router->get('/kardex/reporte-costos',fn()   => (new KardexController())->reporteCostos());

// ── LABORATORIO ───────────────────────────────────────────
$router->get('/laboratorio',               fn()   => (new LaboratorioController())->index());
$router->get('/laboratorio/estadisticas',  fn()   => (new LaboratorioController())->estadisticas());
$router->get('/laboratorio/{id}',          fn($p) => (new LaboratorioController())->show($p));
$router->post('/laboratorio',              fn()   => (new LaboratorioController())->store());
$router->put('/laboratorio/{id}',          fn($p) => (new LaboratorioController())->update($p));

// ── VENTAS ────────────────────────────────────────────────
$router->get('/ventas',                    fn()   => (new VentaController())->index());
$router->get('/ventas/dashboard',          fn()   => (new VentaController())->dashboard());
$router->get('/ventas/{id}',               fn($p) => (new VentaController())->show($p));
$router->post('/ventas',                   fn()   => (new VentaController())->store());
$router->put('/ventas/{id}/confirmar',     fn($p) => (new VentaController())->confirmar($p));
$router->put('/ventas/{id}/en_proceso',    fn($p) => (new VentaController())->enProceso($p));
$router->put('/ventas/{id}/entregar',      fn($p) => (new VentaController())->entregar($p));
$router->put('/ventas/{id}/cancelar',      fn($p) => (new VentaController())->cancelar($p));
$router->post('/ventas/{id}/facturar',     fn($p) => (new SunatController())->facturarVenta($p));
$router->post('/ventas/{id}/boleta',       fn($p) => (new SunatController())->boletaVenta($p));

// ── PRODUCCIÓN: PLAN MAESTRO (MRP) ────────────────────────
$router->get('/produccion/plan-maestro',         fn()   => (new PlanMaestroController())->index());
$router->get('/produccion/plan-maestro/mrp',     fn()   => (new PlanMaestroController())->mrp());
$router->get('/produccion/plan-maestro/{id}',    fn($p) => (new PlanMaestroController())->show($p));
$router->post('/produccion/plan-maestro',        fn()   => (new PlanMaestroController())->store());
$router->put('/produccion/plan-maestro/{id}',    fn($p) => (new PlanMaestroController())->update($p));

// ── PRODUCCIÓN: ÓRDENES DE TRABAJO ────────────────────────
$router->get('/produccion/ordenes-trabajo',                    fn()   => (new OrdenTrabajoController())->index());
$router->get('/produccion/ordenes-trabajo/{id}',               fn($p) => (new OrdenTrabajoController())->show($p));
$router->post('/produccion/ordenes-trabajo',                   fn()   => (new OrdenTrabajoController())->store());
$router->put('/produccion/ordenes-trabajo/{id}/avance',        fn($p) => (new OrdenTrabajoController())->actualizarAvance($p));
$router->post('/produccion/ordenes-trabajo/{id}/materiales',   fn($p) => (new OrdenTrabajoController())->registrarMaterial($p));

// ── INVENTARIO: STOCK ──────────────────────────────────────
$router->get('/inventario/stock',                 fn()   => (new StockController())->index());
$router->get('/inventario/stock/resumen',         fn()   => (new StockController())->resumen());
$router->get('/inventario/stock/alertas',         fn()   => (new StockController())->alertas());
$router->get('/inventario/stock/{id}/movimientos',fn($p) => (new StockController())->movimientos($p));

// ── INVENTARIO: VALORIZACIÓN ──────────────────────────────
$router->get('/inventario/valorizacion',            fn() => (new ValorizacionController())->index());
$router->get('/inventario/valorizacion/comparativo',fn() => (new ValorizacionController())->comparativo());

// ── COMPRAS: PROVEEDORES ──────────────────────────────────
$router->get('/compras/proveedores',       fn()   => (new ProveedorController())->index());
$router->get('/compras/proveedores/{id}',  fn($p) => (new ProveedorController())->show($p));
$router->post('/compras/proveedores',      fn()   => (new ProveedorController())->store());
$router->put('/compras/proveedores/{id}',  fn($p) => (new ProveedorController())->update($p));
$router->delete('/compras/proveedores/{id}',fn($p)=> (new ProveedorController())->destroy($p));

// ── COMPRAS: REQUISICIONES ────────────────────────────────
$router->get('/compras/requisiciones',             fn()   => (new RequisicionController())->index());
$router->get('/compras/requisiciones/{id}',        fn($p) => (new RequisicionController())->show($p));
$router->post('/compras/requisiciones',            fn()   => (new RequisicionController())->store());
$router->put('/compras/requisiciones/{id}/aprobar',fn($p) => (new RequisicionController())->aprobar($p));

// ── COMPRAS: ÓRDENES DE COMPRA ────────────────────────────
$router->get('/compras/ordenes',                    fn()   => (new OrdenCompraController())->index());
$router->get('/compras/ordenes/{id}',               fn($p) => (new OrdenCompraController())->show($p));
$router->post('/compras/ordenes',                   fn()   => (new OrdenCompraController())->store());
$router->put('/compras/ordenes/{id}/confirmar',     fn($p) => (new OrdenCompraController())->confirmar($p));
$router->put('/compras/ordenes/{id}/completar',     fn($p) => (new OrdenCompraController())->completar($p));
$router->put('/compras/ordenes/{id}/cancelar',      fn($p) => (new OrdenCompraController())->cancelar($p));

// ── COMPRAS: CUENTAS POR PAGAR ────────────────────────────
$router->get('/compras/cuentas-pagar',             fn()   => (new CuentaPagarController())->index());
$router->get('/compras/cuentas-pagar/resumen',     fn()   => (new CuentaPagarController())->resumen());
$router->post('/compras/cuentas-pagar',            fn()   => (new CuentaPagarController())->store());
$router->post('/compras/cuentas-pagar/{id}/pagar', fn($p) => (new CuentaPagarController())->registrarPago($p));

// ── VENTAS: COTIZACIONES ──────────────────────────────────
$router->get('/ventas/cotizaciones',                   fn()   => (new CotizacionController())->index());
$router->get('/ventas/cotizaciones/{id}',              fn($p) => (new CotizacionController())->show($p));
$router->post('/ventas/cotizaciones',                  fn()   => (new CotizacionController())->store());
$router->put('/ventas/cotizaciones/{id}/enviar',       fn($p) => (new CotizacionController())->enviar($p));
$router->put('/ventas/cotizaciones/{id}/convertir',    fn($p) => (new CotizacionController())->convertir($p));
$router->put('/ventas/cotizaciones/{id}/rechazar',     fn($p) => (new CotizacionController())->rechazar($p));

// ── FINANCIERO: ASIENTOS CONTABLES ────────────────────────
$router->get('/financiero/asientos',                   fn()   => (new AsientoContableController())->index());
$router->get('/financiero/asientos/plan-cuentas',      fn()   => (new AsientoContableController())->planCuentas());
$router->post('/financiero/asientos/plan-cuentas',     fn()   => (new AsientoContableController())->storeCuenta());
$router->get('/financiero/asientos/{id}',              fn($p) => (new AsientoContableController())->show($p));
$router->post('/financiero/asientos',                  fn()   => (new AsientoContableController())->store());
$router->put('/financiero/asientos/{id}/validar',      fn($p) => (new AsientoContableController())->validar($p));

// ── FINANCIERO: FLUJO DE CAJA ─────────────────────────────
$router->get('/financiero/flujo-caja',              fn() => (new FlujoCajaController())->index());
$router->get('/financiero/flujo-caja/resumen',      fn() => (new FlujoCajaController())->resumen());
$router->get('/financiero/flujo-caja/proyeccion',   fn() => (new FlujoCajaController())->proyeccion());
$router->post('/financiero/flujo-caja',             fn() => (new FlujoCajaController())->store());

// ── FINANCIERO: CENTROS DE COSTO ──────────────────────────
$router->get('/financiero/centros-costo',             fn()   => (new CentroCostoController())->index());
$router->get('/financiero/centros-costo/analisis',    fn()   => (new CentroCostoController())->analisis());
$router->get('/financiero/centros-costo/{id}',        fn($p) => (new CentroCostoController())->show($p));
$router->post('/financiero/centros-costo',            fn()   => (new CentroCostoController())->store());
$router->put('/financiero/centros-costo/{id}',        fn($p) => (new CentroCostoController())->update($p));

// ── CAMPAÑAS Y BACKUPS ────────────────────────────────────
$router->get('/campanas',           fn()   => (new CampanaController())->index());
$router->post('/campanas',          fn()   => (new CampanaController())->store());
$router->put('/campanas/{año}',     fn($p) => (new CampanaController())->update($p));
$router->get('/campanas/backups',   fn()   => (new CampanaController())->backups());
$router->post('/campanas/backups',  fn()   => (new CampanaController())->registrarBackup());

// ── CAPACITACIÓN ──────────────────────────────────────────
$router->get('/capacitaciones',                        fn()   => (new CapacitacionController())->index());
$router->post('/capacitaciones',                       fn()   => (new CapacitacionController())->store());
$router->get('/capacitaciones/estadisticas',           fn()   => (new CapacitacionController())->estadisticas());
$router->get('/capacitaciones/{id}',                   fn($p) => (new CapacitacionController())->show($p));
$router->put('/capacitaciones/{id}',                   fn($p) => (new CapacitacionController())->update($p));
$router->get('/capacitaciones/{id}/participantes',     fn($p) => (new CapacitacionController())->participantes($p));
$router->post('/capacitaciones/{id}/participantes',    fn($p) => (new CapacitacionController())->addParticipante($p));

// ── AUDITORÍA Y SEGURIDAD ─────────────────────────────────
$router->get('/auditorias',                fn()   => (new AuditoriaController())->index());
$router->post('/auditorias',               fn()   => (new AuditoriaController())->store());
$router->get('/auditorias/estadisticas',   fn()   => (new AuditoriaController())->estadisticas());
$router->get('/auditorias/{id}',           fn($p) => (new AuditoriaController())->show($p));
$router->put('/auditorias/{id}',           fn($p) => (new AuditoriaController())->update($p));
$router->post('/auditorias/{id}/hallazgos',fn($p) => (new AuditoriaController())->addHallazgo($p));
$router->put('/auditorias/hallazgos/{id}', fn($p) => (new AuditoriaController())->updateHallazgo($p));
$router->get('/seguridad/log',             fn()   => (new AuditoriaController())->securityLog());
$router->post('/seguridad/log',            fn()   => (new AuditoriaController())->addLog());

// ── CONFIGURACIÓN ─────────────────────────────────────────
$router->get('/configuracion',           fn()   => (new ConfiguracionController())->index());
$router->get('/configuracion/{clave}',   fn($p) => (new ConfiguracionController())->show($p));
$router->put('/configuracion/{clave}',   fn($p) => (new ConfiguracionController())->upsert($p));

// ── DOCUMENTAL ────────────────────────────────────────────
$router->get('/documentos',      fn()   => (new DocumentoController())->index());
$router->get('/documentos/{id}', fn($p) => (new DocumentoController())->show($p));
$router->post('/documentos',     fn()   => (new DocumentoController())->store());

// ── TRAZABILIDAD ──────────────────────────────────────────
$router->get('/trazabilidad/resumen',          fn()   => (new TrazabilidadController())->resumen());
$router->get('/trazabilidad/acopio/{id}',       fn($p) => (new TrazabilidadController())->acopio($p));
$router->get('/trazabilidad/productor/{id}',   fn($p) => (new TrazabilidadController())->productor($p));
$router->get('/trazabilidad/cliente/{id}',     fn($p) => (new TrazabilidadController())->cliente($p));

// ── DASHBOARD BI ──────────────────────────────────────────
$router->get('/dashboard', fn() => (new DashboardController())->index());

// ── INTEGRACIONES: SUNAT ──────────────────────────────────
$router->post('/sunat/factura',         fn()   => (new SunatController())->emitirFactura());
$router->post('/sunat/boleta',          fn()   => (new SunatController())->emitirBoleta());
$router->get('/sunat/consulta-ruc',     fn()   => (new SunatController())->consultarRuc());
$router->get('/sunat/cpe/{id}',         fn($p) => (new SunatController())->consultarCpe($p));
$router->delete('/sunat/cpe/{id}',      fn($p) => (new SunatController())->anularCpe($p));

// ── INTEGRACIONES: EXTERNAS ───────────────────────────────
$router->get('/externas/tipo-cambio',   fn() => (new ApiExternaController())->tipoCambio());
$router->get('/externas/precio-mercado',fn() => (new ApiExternaController())->precioMercado());

// ── HEALTH CHECK ──────────────────────────────────────────
$router->get('/', fn() => Response::json([
    'sistema'  => 'Trazabilidad Café API',
    'version'  => '2.0.0',
    'fecha'    => date('Y-m-d H:i:s'),
    'modulos'  => [
        'operativo'      => ['produccion', 'inventario', 'compras', 'ventas'],
        'administrativo' => ['financiero', 'documental', 'calidad'],
        'inteligencia'   => ['dashboard'],
        'integraciones'  => ['sunat', 'externas'],
    ],
    'endpoints'=> [
        'clientes'       => '/api/clientes',
        'acopios'        => '/api/acopios',
        'kardex'         => '/api/kardex',
        'laboratorio'    => '/api/laboratorio',
        'ventas'         => '/api/ventas',
        'trazabilidad'   => '/api/trazabilidad/resumen',
        'dashboard'      => '/api/dashboard',
    ],
]));

$router->dispatch();
