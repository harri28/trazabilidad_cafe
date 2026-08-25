<?php
session_start();
if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
$v_css = filemtime(__DIR__ . '/css/app.css');
$v_js  = filemtime(__DIR__ . '/js/app.js');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trazabilidad Café — Sistema de Gestión</title>
<link rel="stylesheet" href="css/app.css?v=<?= $v_css ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ══════════ SIDEBAR ══════════════════════════════════════ -->
<aside id="sidebar">
  <div class="sidebar-logo">
    <div class="icon js-logo-fallback">☕</div>
    <img class="js-logo-img" src="" alt="Logo" style="display:none;width:36px;height:36px;border-radius:8px;object-fit:cover">
    <div>
      <div class="brand-text">Trazabilidad <span>Café</span></div>
      <div class="brand-sub">Sistema de Gestión</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <!-- General -->
    <div class="nav-group">
      <div class="nav-group-label">General</div>
      <button class="nav-item active" data-section="dashboard">
        <span class="nav-icon">🏠</span>
        <span class="nav-label">Dashboard</span>
      </button>
    </div>

    <!-- Operaciones -->
    <div class="nav-group">
      <div class="nav-group-label">Operaciones</div>
      <button class="nav-item" data-section="clientes">
        <span class="nav-icon">👥</span>
        <span class="nav-label">Clientes</span>
      </button>
      <button class="nav-item" data-section="acopios">
        <span class="nav-icon">📦</span>
        <span class="nav-label">Acopios</span>
      </button>
      <button class="nav-item" data-section="compras">
        <span class="nav-icon">🛒</span>
        <span class="nav-label">Compras</span>
      </button>
      <button class="nav-item" data-section="laboratorio">
        <span class="nav-icon">🔬</span>
        <span class="nav-label">Laboratorio</span>
      </button>
    </div>

    <!-- Trazabilidad -->
    <div class="nav-group">
      <div class="nav-group-label">Trazabilidad</div>
      <button class="nav-item" data-section="kardex">
        <span class="nav-icon">🔄</span>
        <span class="nav-label">Trazabilidad</span>
      </button>
    </div>

    <!-- Almacén -->
    <div class="nav-group">
      <div class="nav-group-label">Almacén</div>
<button class="nav-item" data-section="stock">
        <span class="nav-icon">🏭</span>
        <span class="nav-label">Almacén</span>
      </button>
    </div>

    <!-- Comercial -->
    <div class="nav-group">
      <div class="nav-group-label">Comercial</div>
      <button class="nav-item" data-section="ventas">
        <span class="nav-icon">🚢</span>
        <span class="nav-label">Ventas</span>
        <span class="nav-badge" id="badge-ventas" style="display:none">0</span>
      </button>
    </div>

    <!-- Administrativo -->
    <div class="nav-group">
      <div class="nav-group-label">Administrativo</div>
      <button class="nav-item" data-section="financiero">
        <span class="nav-icon">💰</span>
        <span class="nav-label">Financiero</span>
      </button>
      <button class="nav-item" data-section="capacitacion">
        <span class="nav-icon">🎓</span>
        <span class="nav-label">Capacitación</span>
      </button>
      <button class="nav-item" data-section="auditoria">
        <span class="nav-icon">🛡️</span>
        <span class="nav-label">Auditoría y Seguridad</span>
      </button>
    </div>

    <!-- Configuración -->
    <div class="nav-group">
      <div class="nav-group-label">Configuración</div>
      <button class="nav-item" data-section="configuracion">
        <span class="nav-icon">⚙️</span>
        <span class="nav-label">Configuración</span>
      </button>
    </div>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info" onclick="navTo('configuracion')" title="Mi cuenta" style="cursor:pointer">
      <div class="user-avatar">👤</div>
      <div>
        <div class="user-name" id="sidebar-user-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="user-role"><?= htmlspecialchars($_SESSION['rol']) ?></div>
      </div>
    </div>
    <button class="btn-logout" onclick="cerrarSesion()" title="Cerrar sesión">
      <span class="logout-icon">⏻</span>
      <span class="logout-label">Cerrar sesión</span>
    </button>
  </div>
</aside>

<!-- ══════════ APP ═══════════════════════════════════════════ -->
<div id="app">

  <!-- TOPBAR -->
  <header id="topbar">
    <button id="toggle-sidebar" onclick="toggleSidebar()" title="Colapsar menú">☰</button>
    <button id="btn-back" onclick="navBack()" title="Ir atrás">
      <span class="back-arrow">&#8592;</span> Atrás
    </button>
    <nav class="topbar-breadcrumb" aria-label="breadcrumb">
      <span class="bc-item bc-home" onclick="navTo('dashboard')" title="Inicio">🏠 Inicio</span>
      <span class="bc-sep" id="bc-sep-group"></span>
      <span class="bc-item" id="bc-group"></span>
      <span class="bc-sep" id="bc-sep-page"></span>
      <span class="bc-item bc-current" id="topbar-section">Dashboard</span>
    </nav>
    <div class="topbar-right">
      <div class="topbar-stat">
        <span class="topbar-campana-icon">🗓</span>
        <label for="topbar-campana" class="topbar-campana-label">Campaña</label>
        <select id="topbar-campana" class="topbar-campana-sel" onchange="cambiarCampana(this.value)" title="Cambiar campaña activa">
          <option value="2026">CAMPAÑA: 2026</option>
        </select>
      </div>
      <div class="topbar-stat" id="topbar-stock">📦 Stock: <strong>—</strong></div>
    </div>
  </header>

  <!-- CONTENIDO -->
  <div id="content">

    <!-- ══════════ DASHBOARD ══════════════════════════════ -->
    <section id="dashboard" class="section active">
      <div class="page-header">
        <h1><span class="page-icon">🏠</span> Dashboard</h1>
        <span class="small text-muted" id="dash-fecha"></span>
      </div>

      <!-- 6 KPI cards -->
      <div class="metrics">
        <div class="metric-card">
          <div class="metric-header"><div class="metric-icon">📦</div></div>
          <div class="metric-label">Acopios Activos</div>
          <div class="metric-value" id="m-lotes">—</div>
          <div class="metric-sub" id="m-lotes-sub">acopio · proceso · disponible</div>
        </div>
        <div class="metric-card verde">
          <div class="metric-header"><div class="metric-icon">⚖️</div></div>
          <div class="metric-label">Kg Disponibles</div>
          <div class="metric-value" id="m-kg">—</div>
          <div class="metric-sub" id="m-kg-sub">stock total en almacén</div>
        </div>
        <div class="metric-card oro">
          <div class="metric-header"><div class="metric-icon">💰</div></div>
          <div class="metric-label">Ventas S/</div>
          <div class="metric-value" id="m-usd">—</div>
          <div class="metric-sub" id="m-usd-sub">campaña actual</div>
        </div>
        <div class="metric-card info">
          <div class="metric-header"><div class="metric-icon">🏆</div></div>
          <div class="metric-label">Score Promedio</div>
          <div class="metric-value" id="m-score">—</div>
          <div class="metric-sub" id="m-score-sub">catación SCA</div>
        </div>
        <div class="metric-card">
          <div class="metric-header"><div class="metric-icon">🌿</div></div>
          <div class="metric-label">Productores</div>
          <div class="metric-value" id="m-productores">—</div>
          <div class="metric-sub" id="m-productores-sub">con lotes activos</div>
        </div>
        <div class="metric-card verde">
          <div class="metric-header"><div class="metric-icon">💱</div></div>
          <div class="metric-label">Precio Prom.</div>
          <div class="metric-value" id="m-precio">—</div>
          <div class="metric-sub">S/ / kg exportado</div>
        </div>
      </div>

      <!-- Charts row -->
      <div class="form-grid cols-2" style="gap:16px;margin-bottom:16px;">
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">🏅</span> Distribución por Calidad</h2>
            <span class="text-muted small" id="dash-total-analisis"></span>
          </div>
          <div class="panel-body" style="display:flex;align-items:center;gap:24px;padding:16px;">
            <div style="width:180px;height:180px;flex-shrink:0;">
              <canvas id="chart-calidad"></canvas>
            </div>
            <ul id="chart-calidad-legend" style="list-style:none;padding:0;margin:0;flex:1;font-size:13px;"></ul>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">📊</span> Flujo de Inventario (kg)</h2>
          </div>
          <div class="panel-body" style="padding:16px;">
            <canvas id="chart-inventario" style="max-height:180px;"></canvas>
          </div>
        </div>
      </div>

      <!-- Top tables row -->
      <div class="form-grid cols-2" style="gap:16px;margin-bottom:16px;">
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">🌿</span> Top 5 Productores</h2>
          </div>
          <div class="panel-body no-pad">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Productor</th><th>Acopios</th><th>Kg</th><th>Score</th></tr></thead>
                <tbody id="tbl-top-productores">
                  <tr><td colspan="4"><div class="empty"><span class="empty-icon">⏳</span><p>Cargando...</p></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">🌍</span> Top 5 Destinos</h2>
          </div>
          <div class="panel-body no-pad">
            <div class="table-wrap">
              <table>
                <thead><tr><th>Destino</th><th>Ventas</th><th>Kg</th><th>S/</th></tr></thead>
                <tbody id="tbl-top-destinos">
                  <tr><td colspan="4"><div class="empty"><span class="empty-icon">⏳</span><p>Cargando...</p></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent lots -->
      <div class="panel">
        <div class="panel-header">
          <h2><span class="ph-icon">📦</span> Últimos Acopios</h2>
          <button class="btn btn-ghost btn-sm" onclick="navTo('acopios')">Ver todos →</button>
        </div>
        <div class="panel-body no-pad">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Código</th><th>Productor</th><th>Tipo</th><th>Acopio</th>
                  <th>Kg Actual</th><th>Score</th><th>Estado</th>
                </tr>
              </thead>
              <tbody id="tbl-lotes-dash">
                <tr><td colspan="8"><div class="empty"><span class="empty-icon">⏳</span><p>Cargando...</p></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ CLIENTES ═══════════════════════════════ -->
    <section id="clientes" class="section">
      <div class="page-header">
        <h1><span class="page-icon">👥</span> Gestión de Clientes</h1>
        <button class="btn btn-primary" onclick="abrirFormCliente()">+ Nuevo Cliente</button>
      </div>

      <!-- KPIs -->
      <div class="metrics metrics-sm" id="clientes-kpis">
        <div class="metric-card verde">
          <div class="metric-label">Total Clientes</div>
          <div class="metric-value" id="kpi-cli-total">—</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Productores</div>
          <div class="metric-value" id="kpi-cli-prod">—</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Compradores</div>
          <div class="metric-value" id="kpi-cli-comp">—</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Ambos roles</div>
          <div class="metric-value" id="kpi-cli-ambos">—</div>
        </div>
      </div>


      <!-- Lista -->
      <div class="panel">
        <div class="cli-header">
          <div class="cli-tabs">
            <button class="cli-tab active" data-tipo="" onclick="setCliTab(this)">Todos</button>
            <button class="cli-tab" data-tipo="comprador" onclick="setCliTab(this)">Comprador</button>
            <button class="cli-tab" data-tipo="productor" onclick="setCliTab(this)">Vendedor</button>
          </div>
          <input type="hidden" id="f-tipo-cliente" value="">
          <input type="search" id="f-buscar-cliente" placeholder="🔍  Buscar por nombre, RUC o asociación..." oninput="cargarClientes()">
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Razón Social</th>
                <th>RUC / DNI</th>
                <th>Ubicación</th>
                <th>Asociación</th>
                <th>Contacto</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbl-clientes"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══ DRAWER DETALLE CLIENTE ══════════════════════════ -->
    <div id="drawer-overlay" onclick="cerrarDrawer()" style="display:none"></div>
    <aside id="drawer-cliente" class="drawer">
      <div class="drawer-header">
        <div>
          <div class="drawer-title" id="drawer-cli-nombre">—</div>
          <div class="drawer-subtitle" id="drawer-cli-tipo">—</div>
        </div>
        <button class="drawer-close" onclick="cerrarDrawer()">✕</button>
      </div>
      <div class="drawer-body">
        <div class="drawer-section-title">Información General</div>
        <div class="drawer-grid" id="drawer-cli-info"></div>

        <div class="drawer-section-title" style="margin-top:20px">Observaciones</div>
        <div id="drawer-cli-obs" class="drawer-obs">—</div>

        <div class="drawer-section-title" style="margin-top:20px">Acopios Asociados</div>
        <div id="drawer-cli-lotes" class="drawer-lotes"></div>
      </div>
      <div class="drawer-footer">
        <button class="btn btn-primary btn-sm" id="drawer-btn-edit" onclick="editarClienteDesdeDrawer()">✏️ Editar</button>
        <button class="btn btn-danger btn-sm"  id="drawer-btn-del"  onclick="eliminarClienteDesdeDrawer()">🗑 Eliminar</button>
        <button class="btn btn-ghost btn-sm"   onclick="cerrarDrawer()">Cerrar</button>
      </div>
    </aside>

    <!-- ══════════ ACOPIOS ══════════════════════════════════ -->
    <section id="acopios" class="section">
      <div class="page-header">
        <h1><span class="page-icon">📦</span> Acopios de Café</h1>
        <button class="btn btn-primary" onclick="abrirFormNuevoLote()">+ Nuevo Acopio</button>
      </div>

      <div class="panel">
        <div class="filters">
          <select id="f-estado-lote" onchange="cargarLotes()">
            <option value="">Todos los estados</option>
            <option value="acopio">Acopio</option>
            <option value="proceso">En proceso</option>
            <option value="disponible">Disponible</option>
            <option value="parcial">Parcial</option>
            <option value="vendido">Vendido</option>
          </select>
          <input type="search" id="f-buscar-lote" placeholder="🔍  Buscar lote o productor..." oninput="cargarLotes()">
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Código</th><th>Productor</th><th>Tipo</th><th>Fecha</th><th>Hora</th>
                <th>Kg Inicial</th><th>Kg Actual</th>
                <th>Score</th><th>Estado</th>
              </tr>
            </thead>
            <tbody id="tbl-lotes"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══════════ TRAZABILIDAD ══════════════════════════════ -->
    <section id="kardex" class="section">

      <!-- ── Vista: Lista de lotes ──────────────────────────── -->
      <div id="traz-lista">
        <div class="page-header">
          <h1><span class="page-icon">🔄</span> Trazabilidad de Acopios</h1>
        </div>

        <div class="metrics">
          <div class="metric-card verde">
            <div class="metric-label">Total Lotes</div>
            <div class="metric-value" id="tm-total">—</div>
            <div class="metric-sub">en seguimiento</div>
          </div>
          <div class="metric-card info">
            <div class="metric-label">Kg Ingresados</div>
            <div class="metric-value" id="tm-kg-in">—</div>
            <div class="metric-sub">peso inicial total</div>
          </div>
          <div class="metric-card oro">
            <div class="metric-label">Score Promedio</div>
            <div class="metric-value" id="tm-score">—</div>
            <div class="metric-sub">puntos SCA</div>
          </div>
          <div class="metric-card danger">
            <div class="metric-label">Kg Vendidos</div>
            <div class="metric-value" id="tm-kg-vend">—</div>
            <div class="metric-sub">kg despachados</div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">📦</span> Acopios en Seguimiento</h2>
            <div class="ph-actions filters">
              <input type="search" id="f-traz-q" placeholder="Buscar código o productor..." autocomplete="off" oninput="filtrarTrazLotes(this.value)">
              <select id="f-traz-estado" onchange="cargarKardex()" class="filter-select-sm">
                <option value="">Todos los estados</option>
                <option value="acopio">Acopio</option>
                <option value="proceso">En proceso</option>
                <option value="disponible">Disponible</option>
                <option value="parcial">Parcial</option>
                <option value="vendido">Vendido</option>
              </select>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Productor</th>
                  <th>Origen</th>
                  <th>Ingreso</th>
                  <th>Estado</th>
                  <th class="text-right">Peso Inicial (kg)</th>
                  <th class="text-right">Peso Actual (kg)</th>
                  <th class="text-right">Score Taza</th>
                </tr>
              </thead>
              <tbody id="tbl-traz-lotes"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── Vista: Detalle de un lote ─────────────────────── -->
      <div id="traz-detalle" style="display:none">
        <div class="page-header">
          <button class="btn btn-sm" onclick="volverTrazLista()">← Volver a la lista</button>
          <h1 id="traz-det-titulo"><span class="page-icon">🔄</span> Trazabilidad del Acopio</h1>
        </div>
        <div id="traz-det-contenido"></div>
      </div>

    </section>

    <!-- ══════════ LABORATORIO ════════════════════════════ -->
    <section id="laboratorio" class="section">
      <div class="page-header">
        <h1><span class="page-icon">🔬</span> Análisis de Laboratorio</h1>
        <button class="btn btn-primary" onclick="toggleForm('form-lab')">+ Nuevo Análisis</button>
      </div>

      <div class="panel">
        <div class="filters">
          <input type="search" id="f-lab-dni" placeholder="Buscar por DNI / RUC del productor..." autocomplete="off" oninput="buscarLabPorDni(this.value)">
          <select id="f-clasif" onchange="cargarLab()">
            <option value="">Todas las clasificaciones</option>
            <option value="specialty">Specialty (≥80)</option>
            <option value="premium">Premium (≥75)</option>
            <option value="comercial">Comercial (≥60)</option>
            <option value="descarte">Descarte</option>
          </select>
          <select id="f-aprobado" onchange="cargarLab()">
            <option value="">Todos</option>
            <option value="1">Aprobados</option>
            <option value="0">Rechazados</option>
          </select>
          <input type="date" id="f-lab-desde" onchange="cargarLab()">
        </div>
        <div id="lab-productor-card" style="display:none"></div>
        <div id="lab-lista"></div>
      </div>
    </section>

    <!-- ══════════ VENTAS ═════════════════════════════════ -->
    <section id="ventas" class="section">
      <div class="page-header">
        <h1><span class="page-icon">🚢</span> Ventas &amp; Facturación</h1>
        <div class="ph-actions">
          <button class="tasa-badge" onclick="editarTasaDolar()" title="Tipo de cambio actual — click para editar">
            <span class="tasa-badge-label">💵 USD/PEN</span>
            <strong id="tasa-usd-display">—</strong>
            <span class="tasa-badge-edit">✏️</span>
          </button>
          <button class="btn btn-ghost" onclick="abrirFormCotizacion()">+ Cotización</button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tab-nav">
        <button class="tab-btn active" onclick="mostrarVentaTab('vt-contratos', this)">📄 Ventas</button>
        <button class="tab-btn" onclick="mostrarVentaTab('vt-cotizaciones', this);cargarCotizaciones()">📋 Cotizaciones</button>
        <button class="tab-btn" onclick="mostrarVentaTab('vt-sunat', this);cargarPanelSunat()">🧾 SUNAT</button>
        <button class="tab-btn" onclick="mostrarVentaTab('vt-buscar', this)">🔍 Buscar cliente</button>
        <button class="tab-btn" onclick="mostrarVentaTab('vt-historial', this)">🕒 Historial</button>
        <button class="tab-btn" onclick="mostrarVentaTab('vt-reportes', this)">📊 Reportes</button>
      </div>

      <!-- TAB: Contratos -->
      <div id="vt-contratos">

        <!-- ── Layout 2 columnas: inventario + formulario ── -->
        <div class="nv-layout">

          <!-- Columna izquierda: lotes en almacén -->
          <div class="nv-lotes-col panel">
            <div class="nv-col-title">☕ Acopios en Almacén</div>
            <input type="search" id="v-shop-search"
                   class="shop-search"
                   placeholder="🔍 Buscar por código, productor o variedad..."
                   autocomplete="off"
                   oninput="filtrarShop(this.value)">
            <div id="v-lotes-disponibles" class="lotes-disp-wrap">
              <div class="text-muted small" style="padding:6px 0">Cargando lotes…</div>
            </div>
            <div id="v-lotes-seleccionados" class="lotes-venta-wrap"></div>
            <div id="v-resumen-total" class="calc-preview" style="display:none">
              <div class="calc-preview-label">Total estimado</div>
              <div class="calc-preview-row">
                <div>
                  <div class="calc-item-label">TOTAL USD</div>
                  <div class="calc-item-value text-green" id="v-total-usd">—</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Columna derecha: formulario de venta -->
          <div class="nv-form-col panel">
            <div class="nv-col-title">📋 Nueva Venta</div>

            <!-- Comprador -->
            <div class="form-section-title" style="margin-top:0">👤 Comprador</div>
            <input type="hidden" id="v-comprador-id">
            <div class="form-grid cols-2" style="margin-top:0">
              <div class="form-group">
                <label>Razón Social</label>
                <div class="prod-search-wrap">
                  <input type="text" id="v-comp-nombre"
                         placeholder="🔍 Nombre..."
                         oninput="buscarCompradorVenta('nombre', this.value)"
                         onfocus="buscarCompradorVenta('nombre', this.value)"
                         onblur="ocultarCompResults()"
                         autocomplete="off">
                  <div id="v-comp-results-nombre" class="prod-results-dropdown"></div>
                </div>
              </div>
              <div class="form-group">
                <label>DNI / RUC</label>
                <div class="prod-search-wrap">
                  <div class="ruc-input-group">
                    <input type="text" id="v-comp-ruc"
                           placeholder="🔍 DNI o RUC..."
                           oninput="buscarCompradorVenta('ruc', this.value)"
                           onfocus="buscarCompradorVenta('ruc', this.value)"
                           onblur="ocultarCompResults()"
                           autocomplete="off">
                    <button type="button" class="btn-buscar-comp"
                            onmousedown="event.preventDefault()"
                            onclick="buscarCompradorBtn()">
                      Buscar
                    </button>
                  </div>
                  <div id="v-comp-results-ruc" class="prod-results-dropdown"></div>
                </div>
              </div>
            </div>
            <div id="v-comp-selected" class="prod-selected-card" style="display:none">
              <div class="prod-sel-info">
                <strong id="v-comp-sel-name"></strong>
                <span class="small text-muted" id="v-comp-sel-ruc"></span>
              </div>
              <button type="button" class="prod-sel-clear" onclick="limpiarCompradorVenta()" title="Cambiar">✕</button>
            </div>
            <div id="v-comp-sunat-card" class="sunat-ruc-card" style="display:none"></div>
            <div id="v-comp-notfound" class="prod-notfound" style="display:none;margin-top:6px">
              <span>⚠️ Comprador no registrado</span>
              <button type="button" class="btn btn-sm btn-primary" onclick="irRegistrarCliente()">+ Registrar cliente</button>
            </div>

            <!-- Fecha e Incoterm -->
            <div class="form-grid" style="margin-top:12px">
              <div class="form-group">
                <label>Fecha de Venta *</label>
                <input type="date" id="v-fecha">
              </div>
              <div class="form-group">
                <label>Incoterm</label>
                <select id="v-incoterm">
                  <option value="FOB">FOB</option>
                  <option value="CIF">CIF</option>
                  <option value="EXW">EXW</option>
                  <option value="DDP">DDP</option>
                </select>
              </div>
            </div>

            <!-- Acciones -->
            <div class="nv-form-actions">
              <button class="btn btn-ghost btn-block" onclick="previewWAFormVenta()">👁 Ver Pre Venta</button>
              <button class="btn btn-success btn-block" onclick="guardarVenta()">💾 Realizar Venta</button>
            </div>
          </div><!-- /nv-form-col -->

        </div><!-- /nv-layout -->

        <!-- ── Historial de ventas ── -->
        <div class="panel" style="margin-top:16px">
          <div class="panel-header">
            <h2><span class="ph-icon">🕒</span> Historial de Ventas</h2>
            <span class="small text-muted">Últimas ventas registradas · más reciente primero</span>
          </div>
          <div class="filters">
            <input type="search" id="f-buscar-venta" placeholder="Buscar por contrato, comprador o lote…" oninput="filtrarHistorialVentas(this.value)" style="min-width:220px">
            <select id="f-estado-venta" onchange="cargarVentas()">
              <option value="">Todos los estados</option>
              <option value="confirmado">Confirmado</option>
              <option value="en_proceso">En proceso</option>
              <option value="entregado">Entregado</option>
              <option value="cancelado">Cancelado</option>
            </select>
            <input type="date" id="f-venta-desde" onchange="cargarVentas()" title="Desde fecha">
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Venta</th><th>Comprador</th><th>Lote</th><th>Fecha</th>
                  <th class="text-right">Kg</th><th class="text-right">S/ /kg</th><th class="text-right">Total S/</th><th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tbl-ventas"></tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- TAB: Cotizaciones -->
      <div id="vt-cotizaciones" style="display:none">
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">📋</span> Cotizaciones</h2>
            <div class="ph-actions">
              <select id="f-estado-cot" onchange="cargarCotizaciones()" class="filter-select-sm">
                <option value="">Todos los estados</option>
                <option value="borrador">Borrador</option>
                <option value="enviada">Enviada</option>
                <option value="aceptada">Aceptada</option>
                <option value="rechazada">Rechazada</option>
                <option value="vencida">Vencida</option>
              </select>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Cotización</th><th>Comprador</th><th>Lote</th>
                  <th>Emisión</th><th>Vence</th>
                  <th>Kg</th><th>S/ /kg</th><th>Total S/</th>
                  <th>Estado</th><th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tbl-cotizaciones"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: SUNAT -->
      <div id="vt-sunat" style="display:none">
        <div class="metrics" id="sunat-kpis">
          <div class="metric-card verde">
            <div class="metric-header"><div class="metric-icon">✅</div></div>
            <div class="metric-label">Aceptadas SUNAT</div>
            <div class="metric-value" id="sunat-aceptadas">—</div>
            <div class="metric-sub">CPE válidos</div>
          </div>
          <div class="metric-card oro">
            <div class="metric-header"><div class="metric-icon">💵</div></div>
            <div class="metric-label">USD Facturado</div>
            <div class="metric-value" id="sunat-usd-fact">—</div>
            <div class="metric-sub">aceptado por SUNAT</div>
          </div>
          <div class="metric-card" style="border-left:4px solid var(--danger)">
            <div class="metric-header"><div class="metric-icon">⚠️</div></div>
            <div class="metric-label">Con Problemas</div>
            <div class="metric-value" id="sunat-problemas">—</div>
            <div class="metric-sub">rechazados u observados</div>
          </div>
          <div class="metric-card" style="border-left:4px solid var(--oro)">
            <div class="metric-header"><div class="metric-icon">⏳</div></div>
            <div class="metric-label">Sin Facturar</div>
            <div class="metric-value" id="sunat-pendientes">—</div>
            <div class="metric-sub">requieren CPE</div>
          </div>
        </div>

        <!-- Pendientes de facturar -->
        <div class="panel" id="sunat-panel-pendientes">
          <div class="panel-header">
            <h2><span class="ph-icon">⏳</span> Ventas sin comprobante electrónico</h2>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Venta</th><th>Comprador</th><th>Lote</th>
                  <th>Fecha</th><th>Total S/</th><th>Estado</th><th>Emitir</th>
                </tr>
              </thead>
              <tbody id="tbl-sunat-pendientes"></tbody>
            </table>
          </div>
        </div>

        <!-- CPE emitidos -->
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">🧾</span> Comprobantes Electrónicos Emitidos</h2>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Venta</th><th>Comprador</th><th>Tipo</th>
                  <th>Serie-Número</th><th>Emitido</th><th>Estado SUNAT</th><th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tbl-sunat-cpe"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: Buscar cliente por DNI -->
      <div id="vt-buscar" style="display:none">
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">🔍</span> Buscar cliente por DNI / RUC</h2>
          </div>
          <div class="panel-body">
            <div style="display:flex;gap:10px;margin-bottom:18px">
              <input type="text" id="dni-input" placeholder="Ingresa DNI o RUC..." style="flex:1;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:.9rem"
                     onkeydown="if(event.key==='Enter')buscarClienteDNI()">
              <button class="btn btn-primary" onclick="buscarClienteDNI()">Buscar</button>
            </div>
            <div id="dni-resultado"></div>
          </div>
        </div>
      </div>

      <!-- TAB: Historial de ventas -->
      <div id="vt-historial" style="display:none">
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">🕒</span> Historial de ventas por comprador</h2>
            <div class="ph-actions">
              <input type="text" id="f-hist-comprador" placeholder="Filtrar comprador..." class="filter-select-sm" style="width:180px" oninput="filtrarHistorial()">
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Venta</th><th>Comprador</th><th>País</th><th>Lote</th>
                  <th>Fecha</th><th class="text-right">Kg</th>
                  <th class="text-right">Total S/</th><th class="text-right">Total PEN</th>
                  <th>Estado</th><th>SUNAT</th>
                </tr>
              </thead>
              <tbody id="tbl-historial"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB: Reportes -->
      <div id="vt-reportes" style="display:none">
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
          <button class="btn btn-primary" onclick="verReporte('diario', this)">📅 Diario</button>
          <button class="btn btn-ghost"   onclick="verReporte('semanal', this)">📆 Semanal</button>
          <button class="btn btn-ghost"   onclick="verReporte('mensual', this)">🗓 Mensual</button>
        </div>
        <div class="metrics" id="rep-metrics">
          <div class="metric-card verde">
            <div class="metric-header"><div class="metric-icon">📄</div></div>
            <div class="metric-label">Ventas</div>
            <div class="metric-value" id="rep-contratos">—</div>
            <div class="metric-sub" id="rep-periodo-label">período seleccionado</div>
          </div>
          <div class="metric-card oro">
            <div class="metric-header"><div class="metric-icon">💵</div></div>
            <div class="metric-label">Total S/</div>
            <div class="metric-value" id="rep-usd">—</div>
            <div class="metric-sub">facturado</div>
          </div>
          <div class="metric-card info">
            <div class="metric-header"><div class="metric-icon">🏦</div></div>
            <div class="metric-label">Total PEN</div>
            <div class="metric-value" id="rep-pen">—</div>
            <div class="metric-sub">en soles</div>
          </div>
          <div class="metric-card">
            <div class="metric-header"><div class="metric-icon">⚖️</div></div>
            <div class="metric-label">Kg facturados</div>
            <div class="metric-value" id="rep-kg">—</div>
            <div class="metric-sub">volumen</div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">📋</span> Detalle de facturación</h2>
            <span id="rep-rango" class="text-muted" style="font-size:.8rem"></span>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Venta</th><th>Fecha</th><th>Comprador</th><th>Lote</th>
                  <th class="text-right">Kg</th><th class="text-right">S/ /kg</th>
                  <th class="text-right">Total S/</th><th class="text-right">Total PEN</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody id="tbl-reporte"></tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ PRODUCCIÓN ════════════════════════════ -->
    <section id="produccion" class="section">
      <div class="page-header">
        <h1><span class="page-icon">⚙️</span> Producción</h1>
        <button class="btn btn-primary" onclick="toggleForm('form-ot')">+ Nueva O.T.</button>
      </div>

      <div class="metrics">
        <div class="metric-card">
          <div class="metric-header"><div class="metric-icon">📋</div></div>
          <div class="metric-label">OTs Pendientes</div>
          <div class="metric-value" id="prod-pendientes">—</div>
          <div class="metric-sub">en cola</div>
        </div>
        <div class="metric-card info">
          <div class="metric-header"><div class="metric-icon">🔄</div></div>
          <div class="metric-label">En Proceso</div>
          <div class="metric-value" id="prod-en-proceso">—</div>
          <div class="metric-sub">activas ahora</div>
        </div>
        <div class="metric-card verde">
          <div class="metric-header"><div class="metric-icon">✅</div></div>
          <div class="metric-label">Completadas</div>
          <div class="metric-value" id="prod-completadas">—</div>
          <div class="metric-sub">este año</div>
        </div>
        <div class="metric-card oro">
          <div class="metric-header"><div class="metric-icon">📊</div></div>
          <div class="metric-label">Avance Prom.</div>
          <div class="metric-value" id="prod-avance">—</div>
          <div class="metric-sub">% promedio OTs activas</div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <h2><span class="ph-icon">📋</span> Órdenes de Trabajo</h2>
          <select class="filter-select-sm" id="f-ot-estado" onchange="cargarOTs()">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="en_proceso">En Proceso</option>
            <option value="completada">Completada</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>N° OT</th><th>Lote</th><th>Tipo Proceso</th><th>Inicio</th>
                <th>Fin Est.</th><th>Operador</th><th>Avance</th><th>Estado</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbl-ot"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══════════ STOCK ══════════════════════════════════ -->
    <section id="stock" class="section">
      <div class="page-header">
        <h1><span class="page-icon">🏭</span> Almacén</h1>
      </div>

      <div class="metrics">
        <div class="metric-card verde">
          <div class="metric-header"><div class="metric-icon">📦</div></div>
          <div class="metric-label">Stock Total</div>
          <div class="metric-value" id="st-total-kg">—</div>
          <div class="metric-sub">kg disponibles</div>
        </div>
        <div class="metric-card">
          <div class="metric-header"><div class="metric-icon">📋</div></div>
          <div class="metric-label">Acopios Activos</div>
          <div class="metric-value" id="st-lotes">—</div>
          <div class="metric-sub">sin vender</div>
        </div>
        <div class="metric-card oro">
          <div class="metric-header"><div class="metric-icon">🔒</div></div>
          <div class="metric-label">Comprometido</div>
          <div class="metric-value" id="st-comprometido">—</div>
          <div class="metric-sub">en contratos activos</div>
        </div>
        <div class="metric-card info">
          <div class="metric-header"><div class="metric-icon">✅</div></div>
          <div class="metric-label">Stock Libre</div>
          <div class="metric-value" id="st-libre">—</div>
          <div class="metric-sub">disponible para venta</div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <h2><span class="ph-icon">📊</span> Almacén por Lote</h2>
          <select class="filter-select-sm" id="f-stock-estado" onchange="cargarStock()">
            <option value="">Todos</option>
            <option value="acopio">Acopio</option>
            <option value="proceso">En proceso</option>
            <option value="disponible">Disponible</option>
            <option value="parcial">Parcial</option>
          </select>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Código</th><th>Tipo Café</th><th>Productor</th><th>Variedad</th>
                <th>Stock Actual</th><th>Comprometido</th><th>Stock Libre</th>
                <th>Score</th><th>Estado</th>
              </tr>
            </thead>
            <tbody id="tbl-stock"></tbody>
          </table>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <h2><span class="ph-icon">💲</span> Valorización de Almacén</h2>
          <div class="ph-actions">
            <select class="filter-select-sm" id="f-val-metodo">
              <option value="promedio">Promedio Ponderado</option>
              <option value="fifo">FIFO</option>
            </select>
            <button class="btn btn-ghost btn-sm" onclick="cargarValorizacion()">Calcular</button>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Código</th><th>Tipo Café</th><th>Productor</th>
                <th>Stock kg</th><th>Costo Unitario</th><th>Valorización</th><th>Moneda</th>
              </tr>
            </thead>
            <tbody id="tbl-valorizacion"></tbody>
          </table>
        </div>
        <div class="valorizacion-footer">
          <div class="valorizacion-total">
            Total valorizado: <span id="val-total">—</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ COMPRAS ════════════════════════════════ -->
    <section id="compras" class="section">
      <div class="page-header">
        <h1><span class="page-icon">🛒</span> Compras</h1>
      </div>

      <div class="tab-nav">
        <button class="tab-btn active" onclick="mostrarTab('tab-proveedores', this)">👷 Proveedores</button>
        <button class="tab-btn" onclick="mostrarTab('tab-oc', this)">📄 Órdenes de Compra</button>
        <button class="tab-btn" onclick="mostrarTab('tab-cxp', this)">💸 Cuentas × Pagar</button>
      </div>

      <!-- PROVEEDORES -->
      <div id="tab-proveedores" class="compras-tab">
        <div class="page-header" style="margin-bottom:14px">
          <h2 class="fw-bold">👷 Proveedores</h2>
          <button class="btn btn-primary btn-sm" onclick="abrirFormProveedor()">+ Nuevo Proveedor</button>
        </div>
        <div class="panel">
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>#</th><th>Razón Social</th><th>RUC</th><th>Categoría</th><th>Teléfono</th><th>Cond. Pago</th><th>Deuda Pend.</th><th></th></tr>
              </thead>
              <tbody id="tbl-proveedores"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ÓRDENES DE COMPRA -->
      <div id="tab-oc" class="compras-tab" style="display:none">
        <div class="page-header" style="margin-bottom:14px">
          <h2 class="fw-bold">📄 Órdenes de Compra</h2>
          <button class="btn btn-primary btn-sm" onclick="toggleForm('form-oc')">+ Nueva OC</button>
        </div>
        <div class="panel">
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>N° OC</th><th>Proveedor</th><th>Fecha</th><th>Moneda</th><th>Subtotal</th><th>IGV</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
              </thead>
              <tbody id="tbl-oc"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- CUENTAS POR PAGAR -->
      <div id="tab-cxp" class="compras-tab" style="display:none">
        <div class="metrics mb-16">
          <div class="metric-card danger">
            <div class="metric-header"><div class="metric-icon">⚠️</div></div>
            <div class="metric-label">Vencidas</div>
            <div class="metric-value" id="cxp-vencidas">—</div>
            <div class="metric-sub">requieren atención</div>
          </div>
          <div class="metric-card oro">
            <div class="metric-header"><div class="metric-icon">⏳</div></div>
            <div class="metric-label">Pendientes</div>
            <div class="metric-value" id="cxp-pendientes">—</div>
            <div class="metric-sub">por pagar</div>
          </div>
          <div class="metric-card verde">
            <div class="metric-header"><div class="metric-icon">✅</div></div>
            <div class="metric-label">Pagadas</div>
            <div class="metric-value" id="cxp-pagadas">—</div>
            <div class="metric-sub">este período</div>
          </div>
          <div class="metric-card info">
            <div class="metric-header"><div class="metric-icon">💸</div></div>
            <div class="metric-label">Deuda Total</div>
            <div class="metric-value" id="cxp-deuda">—</div>
            <div class="metric-sub">saldo pendiente</div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">💸</span> Cuentas por Pagar</h2>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('form-cxp')">+ Agregar Cuenta</button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Proveedor</th><th>N° Doc.</th><th>Tipo</th><th>Emisión</th><th>Vencimiento</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Estado</th><th>Acciones</th></tr>
              </thead>
              <tbody id="tbl-cxp"></tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ FINANCIERO ═════════════════════════════ -->
    <section id="financiero" class="section">
      <div class="page-header">
        <h1><span class="page-icon">💰</span> Financiero</h1>
        <button class="btn btn-primary" onclick="toggleForm('form-flujo')">+ Registrar Movimiento</button>
      </div>

      <div class="metrics">
        <div class="metric-card verde">
          <div class="metric-header"><div class="metric-icon">📈</div></div>
          <div class="metric-label">Ingresos</div>
          <div class="metric-value" id="fin-ingresos">—</div>
          <div class="metric-sub">mes actual</div>
        </div>
        <div class="metric-card danger">
          <div class="metric-header"><div class="metric-icon">📉</div></div>
          <div class="metric-label">Egresos</div>
          <div class="metric-value" id="fin-egresos">—</div>
          <div class="metric-sub">mes actual</div>
        </div>
        <div class="metric-card oro">
          <div class="metric-header"><div class="metric-icon">⚖️</div></div>
          <div class="metric-label">Saldo Neto</div>
          <div class="metric-value" id="fin-saldo">—</div>
          <div class="metric-sub">flujo neto PEN</div>
        </div>
        <div class="metric-card info">
          <div class="metric-header"><div class="metric-icon">🔮</div></div>
          <div class="metric-label">Por Cobrar (30d)</div>
          <div class="metric-value" id="fin-cobrar">—</div>
          <div class="metric-sub">ventas confirmadas</div>
        </div>
      </div>

      <!-- Tabs Financiero -->
      <div class="tab-nav">
        <button class="tab-btn active" onclick="mostrarFinTab('flujo-tabla', this)">📊 Movimientos</button>
        <button class="tab-btn" onclick="mostrarFinTab('proyeccion', this)">🔮 Proyección 30d</button>
        <button class="tab-btn" onclick="mostrarFinTab('centros-costo', this)">🏷️ Centros de Costo</button>
      </div>

      <div id="flujo-tabla">
        <div class="panel">
          <div class="panel-header">
            <h2><span class="ph-icon">💸</span> Flujo de Caja</h2>
            <div class="ph-actions">
              <select class="filter-select-sm" id="f-fc-tipo" onchange="cargarFlujo()">
                <option value="">Todos</option>
                <option value="ingreso">Ingresos</option>
                <option value="egreso">Egresos</option>
              </select>
              <input type="date" class="filter-select-sm" id="f-fc-desde" onchange="cargarFlujo()">
              <input type="date" class="filter-select-sm" id="f-fc-hasta" onchange="cargarFlujo()">
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Concepto</th><th>Monto</th><th>Moneda</th><th>Monto PEN</th><th>Banco</th></tr>
              </thead>
              <tbody id="tbl-flujo"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div id="proyeccion" style="display:none">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
          <div class="panel">
            <div class="panel-header"><h2><span class="ph-icon">📈</span> Cobros Estimados (30d)</h2></div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Referencia</th><th>Fecha Est.</th><th>Monto</th><th>Moneda</th><th>Contraparte</th></tr></thead>
                <tbody id="tbl-cobros"></tbody>
              </table>
            </div>
          </div>
          <div class="panel">
            <div class="panel-header"><h2><span class="ph-icon">📉</span> Pagos Estimados (30d)</h2></div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Referencia</th><th>Vencimiento</th><th>Saldo</th><th>Moneda</th><th>Proveedor</th></tr></thead>
                <tbody id="tbl-pagos"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div id="centros-costo" style="display:none">
        <div class="panel">
          <div class="panel-header"><h2><span class="ph-icon">🏷️</span> Análisis por Centro de Costo</h2></div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Código</th><th>Centro de Costo</th><th>Ingresos</th><th>Egresos</th><th>Resultado</th></tr></thead>
              <tbody id="tbl-centros"></tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════ CONFIGURACIÓN ═════════════════════════════ -->
    <section id="configuracion" class="section">
      <div class="page-header">
        <h1><span class="page-icon">⚙️</span> Configuración del Sistema</h1>
      </div>

      <!-- Tabs de navegación principal -->
      <div class="tab-nav" id="cfg-tab-nav" style="margin-bottom:24px;flex-wrap:wrap;gap:4px">
        <button class="tab-btn active" onclick="mostrarCfgTab('cfg-tab-general',this)">⚙️ General</button>
        <button class="tab-btn" onclick="mostrarCfgTab('cfg-tab-campanas',this)">🗓 Campañas</button>
        <button class="tab-btn" onclick="mostrarCfgTab('cfg-tab-backups',this)">💾 Backups</button>
        <button class="tab-btn" onclick="mostrarCfgTab('cfg-tab-usuarios',this)">👥 Usuarios</button>
        <button class="tab-btn" onclick="mostrarCfgTab('cfg-tab-cuenta',this)">👤 Mi Cuenta</button>
      </div>

      <!-- ── Tab: General ─────────────────────────────────────── -->
      <div id="cfg-tab-general">
        <div class="cfg-group">
          <div class="cfg-group-title">🖼️ Logo del Sistema</div>
          <div class="cfg-cards">
            <div class="cfg-card">
              <div class="cfg-card-icon">
                <img class="js-logo-img" src="" alt="Logo" style="display:none;max-width:48px;max-height:48px;border-radius:8px">
                <span class="js-logo-fallback">☕</span>
              </div>
              <div class="cfg-card-body">
                <div class="cfg-card-label">Logo del sistema</div>
                <div class="cfg-card-desc">PNG, JPG, WEBP o SVG — máximo 2 MB. Reemplaza el ícono ☕ en el menú lateral y el login.</div>
                <input type="file" id="cfg-logo-input" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="margin-top:8px">
              </div>
              <div style="display:flex;flex-direction:column;gap:6px">
                <button class="btn btn-primary btn-sm" onclick="subirLogoSistema()">⬆️ Subir</button>
                <button class="btn btn-ghost btn-sm" onclick="restablecerLogoSistema()">↺ Restablecer</button>
              </div>
            </div>
          </div>
        </div>
        <div class="cfg-group">
          <div class="cfg-group-title">💱 Divisas</div>
          <div class="cfg-cards">
            <div class="cfg-card">
              <div class="cfg-card-icon">💵</div>
              <div class="cfg-card-body">
                <div class="cfg-card-label">Tasa de Cambio USD → PEN</div>
                <div class="cfg-card-desc">Valor predeterminado en contratos de venta y movimientos de caja</div>
                <div class="cfg-card-value">
                  <span id="cfg-val-tasa-usd" class="cfg-valor">—</span>
                  <span class="cfg-moneda">PEN / USD</span>
                </div>
              </div>
              <div style="display:flex;flex-direction:column;gap:6px">
                <button class="btn btn-ghost btn-sm" onclick="editarTasaDolar()">✏️ Editar</button>
                <button class="btn btn-ghost btn-sm" onclick="restablecerConfiguracion(['tasa_usd','tasa_eur'])">↺ Restablecer</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab: Campañas ────────────────────────────────────── -->
      <div id="cfg-tab-campanas" style="display:none">

        <!-- KPI cards -->
        <div class="cfg-cards" style="margin-bottom:20px">
          <div class="cfg-card" style="flex:1;min-width:140px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.8rem;line-height:1">🗓</div>
            <div style="font-size:1.9rem;font-weight:800;color:var(--verde);line-height:1" id="kpi-camp-total">—</div>
            <div class="cfg-card-desc">Total campañas</div>
          </div>
          <div class="cfg-card" style="flex:1;min-width:140px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.8rem;line-height:1">✅</div>
            <div style="font-size:1.9rem;font-weight:800;color:var(--verde);line-height:1" id="kpi-camp-activa">—</div>
            <div class="cfg-card-desc">Campaña activa</div>
          </div>
          <div class="cfg-card" style="flex:1;min-width:140px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.8rem;line-height:1">📦</div>
            <div style="font-size:1.9rem;font-weight:800;color:var(--verde);line-height:1" id="kpi-camp-lotes">—</div>
            <div class="cfg-card-desc">Lotes campaña activa</div>
          </div>
          <div class="cfg-card" style="flex:1;min-width:140px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.8rem;line-height:1">🔒</div>
            <div style="font-size:1.9rem;font-weight:800;color:var(--oro,#CBA258);line-height:1" id="kpi-camp-cerradas">—</div>
            <div class="cfg-card-desc">Cerradas / Archivadas</div>
          </div>
        </div>

        <!-- Tabla de campañas -->
        <div class="cfg-section-card">
          <div class="cfg-section-card-header">
            <div>
              <div class="cfg-card-label" style="font-size:.95rem">Campañas de cosecha</div>
              <div class="cfg-card-desc">Gestiona el estado, fechas y ciclo de vida de cada campaña</div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="toggleForm('form-nueva-campana')">+ Nueva Campaña</button>
          </div>
          <div class="table-wrap" style="margin-top:14px">
            <table>
              <thead>
                <tr>
                  <th>Año</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Notas</th>
                  <th style="text-align:right">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbl-campanas"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── Tab: Backups ─────────────────────────────────────── -->
      <div id="cfg-tab-backups" style="display:none">

        <!-- KPI cards -->
        <div class="cfg-cards" style="margin-bottom:20px">
          <div class="cfg-card" style="flex:1;min-width:130px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.6rem;line-height:1">📅</div>
            <div style="font-size:1.9rem;font-weight:800;color:var(--verde);line-height:1" id="kpi-bk-diarios">—</div>
            <div class="cfg-card-desc">Diarios</div>
          </div>
          <div class="cfg-card" style="flex:1;min-width:130px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.6rem;line-height:1">📆</div>
            <div style="font-size:1.9rem;font-weight:800;color:var(--verde);line-height:1" id="kpi-bk-mensuales">—</div>
            <div class="cfg-card-desc">Mensuales</div>
          </div>
          <div class="cfg-card" style="flex:1;min-width:130px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.6rem;line-height:1">🗃</div>
            <div style="font-size:1.9rem;font-weight:800;color:var(--verde);line-height:1" id="kpi-bk-anuales">—</div>
            <div class="cfg-card-desc">Anuales</div>
          </div>
          <div class="cfg-card" style="flex:1;min-width:130px;flex-direction:column;align-items:flex-start;gap:4px">
            <div style="font-size:1.6rem;line-height:1">🕒</div>
            <div style="font-size:.9rem;font-weight:700;color:var(--verde);line-height:1.3;margin-top:2px" id="kpi-bk-ultimo">—</div>
            <div class="cfg-card-desc">Último backup</div>
          </div>
        </div>

        <!-- Tabla con tabs de tipo -->
        <div class="cfg-section-card">
          <div class="tab-nav" style="margin-bottom:16px">
            <button class="tab-btn backup-tab-btn active" onclick="mostrarBackupTab('backup-diarios',this)">📅 Diarios</button>
            <button class="tab-btn backup-tab-btn" onclick="mostrarBackupTab('backup-mensuales',this)">📆 Mensuales</button>
            <button class="tab-btn backup-tab-btn" onclick="mostrarBackupTab('backup-anuales',this)">🗃 Anuales</button>
          </div>

          <div id="backup-diarios" class="backup-tab">
            <div class="backup-tab-header">
              <span class="small text-muted">Backups diarios registrados</span>
              <button class="btn btn-primary btn-sm" onclick="abrirRegistrarBackup('diario')">+ Registrar Backup</button>
            </div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Fecha y hora</th><th>Campaña</th><th>Descripción</th><th>Realizado por</th><th>Estado</th></tr></thead>
                <tbody id="tbl-backup-diarios"></tbody>
              </table>
            </div>
          </div>

          <div id="backup-mensuales" class="backup-tab" style="display:none">
            <div class="backup-tab-header">
              <span class="small text-muted">Resúmenes mensuales</span>
              <button class="btn btn-primary btn-sm" onclick="abrirRegistrarBackup('mensual')">+ Registrar Backup</button>
            </div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Fecha y hora</th><th>Campaña</th><th>Descripción</th><th>Realizado por</th><th>Estado</th></tr></thead>
                <tbody id="tbl-backup-mensuales"></tbody>
              </table>
            </div>
          </div>

          <div id="backup-anuales" class="backup-tab" style="display:none">
            <div class="backup-tab-header">
              <span class="small text-muted">Archivo anual — campañas cerradas</span>
              <button class="btn btn-primary btn-sm" onclick="abrirRegistrarBackup('anual')">+ Registrar Backup</button>
            </div>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Fecha y hora</th><th>Campaña</th><th>Descripción</th><th>Realizado por</th><th>Estado</th></tr></thead>
                <tbody id="tbl-backup-anuales"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab: Usuarios ────────────────────────────────────── -->
      <div id="cfg-tab-usuarios" style="display:none">
        <div class="cfg-section-card">
          <div class="cfg-section-card-header">
            <div>
              <div class="cfg-card-label" style="font-size:.95rem">Usuarios del sistema</div>
              <div class="cfg-card-desc">Administra los accesos, roles y datos de contacto de cada usuario</div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="abrirNuevoUsuario()">+ Nuevo Usuario</button>
          </div>
          <div class="table-wrap" style="margin-top:14px">
            <table>
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Nombre</th>
                  <th>Correo</th>
                  <th>Rol</th>
                  <th style="text-align:right">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbl-usuarios"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ── Tab: Mi Cuenta ───────────────────────────────────── -->
      <div id="cfg-tab-cuenta" style="display:none">
        <div class="cfg-cards">

          <div class="cfg-card">
            <div class="cfg-card-icon">👤</div>
            <div class="cfg-card-body">
              <div class="cfg-card-label">Datos personales</div>
              <div class="cfg-card-value" id="perfil-nombre-display"
                   style="font-size:.95rem;margin-top:4px;font-weight:600;color:var(--verde)">—</div>
              <div class="cfg-card-desc" id="perfil-email-display" style="margin-top:2px">Sin correo registrado</div>
              <div class="cfg-card-desc" style="margin-top:2px">
                Rol: <span id="perfil-rol-display" style="font-weight:600">—</span>
              </div>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="editarPerfil()">✏️ Editar</button>
          </div>

          <div class="cfg-card">
            <div class="cfg-card-icon">🔒</div>
            <div class="cfg-card-body">
              <div class="cfg-card-label">Contraseña</div>
              <div class="cfg-card-desc">Cambia tu contraseña de acceso al sistema</div>
              <div class="cfg-card-desc" style="font-size:.78rem;margin-top:4px;opacity:.65">Mínimo 8 caracteres</div>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="abrirCambioPassword()">🔑 Cambiar</button>
          </div>

        </div>
      </div>

    </section>


    <!-- ══ CAPACITACIÓN ═══════════════════════════════════════ -->
    <section id="capacitacion" class="section">
      <div class="page-header">
        <h1><span class="page-icon">🎓</span> Capacitación</h1>
        <button class="btn btn-primary" id="btn-nueva-cap" onclick="abrirFormCapacitacion()">+ Nueva Capacitación</button>
      </div>

      <!-- Tabs -->
      <div class="tab-nav" style="margin-bottom:18px">
        <button class="tab-btn active" onclick="mostrarCapTab('cap-tab-lista',this)">📋 Capacitaciones</button>
        <button class="tab-btn" onclick="mostrarCapTab('cap-tab-manual',this)">📖 Manual de Usuario</button>
      </div>

      <!-- TAB: Capacitaciones -->
      <div id="cap-tab-lista">
        <div class="metrics metrics-xs">
          <div class="metric-card">
            <div class="metric-header"><div class="metric-icon">🎓</div></div>
            <div class="metric-label">Total</div>
            <div class="metric-value" id="cap-k-total">—</div>
            <div class="metric-sub">capacitaciones registradas</div>
          </div>
          <div class="metric-card verde">
            <div class="metric-header"><div class="metric-icon">✅</div></div>
            <div class="metric-label">Completadas</div>
            <div class="metric-value" id="cap-k-completadas">—</div>
            <div class="metric-sub">finalizadas con éxito</div>
          </div>
          <div class="metric-card info">
            <div class="metric-header"><div class="metric-icon">👥</div></div>
            <div class="metric-label">Participaciones</div>
            <div class="metric-value" id="cap-k-participantes">—</div>
            <div class="metric-sub">asistentes totales</div>
          </div>
          <div class="metric-card oro">
            <div class="metric-header"><div class="metric-icon">🔄</div></div>
            <div class="metric-label">En Curso</div>
            <div class="metric-value" id="cap-k-curso">—</div>
            <div class="metric-sub">capacitaciones activas</div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap">
          <button class="cap-filter-btn active" data-estado="" onclick="setCapFiltro(this,'')">Todos</button>
          <button class="cap-filter-btn" data-estado="programado" onclick="setCapFiltro(this,'programado')">Programado</button>
          <button class="cap-filter-btn" data-estado="en_curso" onclick="setCapFiltro(this,'en_curso')">En Curso</button>
          <button class="cap-filter-btn" data-estado="completado" onclick="setCapFiltro(this,'completado')">Completado</button>
          <button class="cap-filter-btn" data-estado="cancelado" onclick="setCapFiltro(this,'cancelado')">Cancelado</button>
          <input type="hidden" id="f-cap-estado" value="">
        </div>
        <div id="cap-lista">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Título</th><th>Instructor</th><th>Organización</th>
                  <th>Fecha Inicio</th><th>Lugar</th><th>Modalidad</th>
                  <th>Participantes</th><th>Estado</th>
                </tr>
              </thead>
              <tbody id="tbl-capacitaciones"></tbody>
            </table>
          </div>
        </div>
        <div id="cap-detalle" style="display:none">
          <button class="btn btn-ghost btn-sm" onclick="volverCapLista()" style="margin-bottom:16px">← Volver a lista</button>
          <div id="cap-detalle-body"></div>
        </div>
      </div>

      <!-- TAB: Manual de Usuario -->
      <div id="cap-tab-manual" style="display:none">
        <div class="panel" style="max-width:860px;margin:0 auto">
          <div class="panel-header">
            <h2><span class="ph-icon">📖</span> Manual de Usuario — Sistema de Trazabilidad de Café</h2>
            <button class="btn btn-ghost btn-sm" onclick="window.print()">🖨️ Imprimir</button>
          </div>
          <div class="panel-body" style="padding:28px;line-height:1.7;color:#2d3a34">

            <p style="color:#7B9E94;font-size:.9rem;margin-bottom:24px">
              Versión 2.0 &nbsp;·&nbsp; Sistema de Gestión de Café de Especialidad
            </p>

            <!-- 1. INTRODUCCIÓN -->
            <div class="manual-section">
              <h2 class="manual-h2">1. Introducción</h2>
              <p>El Sistema de Trazabilidad de Café permite registrar y dar seguimiento completo al ciclo de vida del café de especialidad: desde el acopio en campo hasta la venta al comprador final. Garantiza transparencia, calidad y trazabilidad en cada etapa de la cadena productiva.</p>
            </div>

            <!-- 2. MÓDULOS -->
            <div class="manual-section">
              <h2 class="manual-h2">2. Módulos del Sistema</h2>

              <h3 class="manual-h3">📦 Acopios</h3>
              <p>Registra cada lote de café recibido de un productor. Para crear un acopio:</p>
              <ol class="manual-list">
                <li>Ve al menú lateral → <strong>Acopios</strong>.</li>
                <li>Haz clic en <strong>+ Nuevo Acopio</strong>.</li>
                <li>Busca al proveedor por nombre, DNI o RUC. El DNI se rellena automáticamente.</li>
                <li>Completa la <strong>Fecha</strong>, <strong>Hora</strong>, <strong>Tipo de café</strong> y <strong>Sector</strong>.</li>
                <li>En la sección de peso: ingresa <strong>SAC</strong> (sacos), <strong>KG BRT</strong> (peso bruto) y el sistema calcula automáticamente:
                  <ul class="manual-list" style="margin-top:6px">
                    <li><strong>KG NET</strong> = KG BRT − (SAC × 0.20)</li>
                    <li><strong>QQ</strong> = KG NET ÷ 55.2</li>
                    <li><strong>P. Unitario</strong> = Precio KG + Prima</li>
                    <li><strong>Total S/</strong> = P. Unitario × KG NET</li>
                    <li><strong>Pago Prima</strong> = Prima × KG NET</li>
                  </ul>
                </li>
                <li>Haz clic en <strong>Registrar Acopio</strong>.</li>
              </ol>
              <div class="manual-tip">💡 La prima tiene un valor por defecto de S/ 0.40/kg. Puedes modificarla antes de guardar.</div>

              <h3 class="manual-h3">👥 Clientes</h3>
              <p>Gestiona productores y compradores. Un cliente puede tener rol de <em>productor</em>, <em>comprador</em> o <em>ambos</em>. Haz clic en cualquier fila para ver el detalle y sus acopios asociados.</p>

              <h3 class="manual-h3">🔬 Laboratorio</h3>
              <p>Registra análisis de calidad SCA (cupping). Busca al productor por DNI/RUC para filtrar sus acopios. El sistema clasifica automáticamente según el score taza:</p>
              <ul class="manual-list">
                <li><strong>Specialty</strong>: ≥ 80 puntos</li>
                <li><strong>Premium</strong>: ≥ 75 puntos</li>
                <li><strong>Comercial</strong>: ≥ 60 puntos</li>
                <li><strong>Descarte</strong>: &lt; 60 puntos</li>
              </ul>

              <h3 class="manual-h3">🚢 Ventas</h3>
              <p>Registra contratos de venta. Selecciona el acopio disponible del estante, ingresa el comprador, cantidad, precio e incoterm. El sistema descuenta el stock automáticamente al confirmar.</p>

              <h3 class="manual-h3">🔄 Trazabilidad</h3>
              <p>Consulta la línea de tiempo completa de cualquier acopio: registro, transformaciones, análisis de calidad y ventas. Haz clic en cualquier fila para ver el detalle.</p>

              <h3 class="manual-h3">🏭 Almacén</h3>
              <p>Visualiza el stock actual por tipo de café y estado. Muestra alertas cuando el stock cae por debajo del umbral configurado.</p>

              <h3 class="manual-h3">💰 Financiero</h3>
              <p>Registra asientos contables, flujo de caja y analiza centros de costo. Permite proyecciones de ingresos y egresos.</p>

              <h3 class="manual-h3">⚙️ Configuración</h3>
              <p>Gestiona campañas anuales, tasa de cambio USD/PEN, usuarios del sistema y backups. La campaña activa determina qué datos se muestran por defecto en el dashboard.</p>
            </div>

            <!-- 3. FLUJO -->
            <div class="manual-section">
              <h2 class="manual-h2">3. Flujo de Trabajo Recomendado</h2>
              <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px">
                <div class="manual-step"><span class="manual-step-num">1</span> Registrar al productor como <strong>Cliente</strong> (tipo: productor).</div>
                <div class="manual-step"><span class="manual-step-num">2</span> Crear un <strong>Acopio</strong> vinculado al productor.</div>
                <div class="manual-step"><span class="manual-step-num">3</span> Registrar el <strong>Análisis de Laboratorio</strong> del acopio.</div>
                <div class="manual-step"><span class="manual-step-num">4</span> Registrar al comprador como <strong>Cliente</strong> (tipo: comprador).</div>
                <div class="manual-step"><span class="manual-step-num">5</span> Crear la <strong>Venta</strong> seleccionando el acopio disponible.</div>
                <div class="manual-step"><span class="manual-step-num">6</span> Consultar la <strong>Trazabilidad</strong> completa del acopio.</div>
              </div>
            </div>

            <!-- 4. PREGUNTAS FRECUENTES -->
            <div class="manual-section">
              <h2 class="manual-h2">4. Preguntas Frecuentes</h2>
              <div class="manual-faq">
                <div class="manual-faq-q">¿Cómo agrego un nuevo sector?</div>
                <div class="manual-faq-a">En el formulario de Acopio, despliega el selector de Sector y elige <em>"+ Registrar nuevo sector..."</em>. Escribe el nombre y guarda el acopio.</div>
              </div>
              <div class="manual-faq">
                <div class="manual-faq-q">¿Puedo cambiar la prima por defecto?</div>
                <div class="manual-faq-a">Sí, en el formulario de Acopio el campo Prima S/ arranca en 0.40. Puedes editarlo antes de guardar.</div>
              </div>
              <div class="manual-faq">
                <div class="manual-faq-q">¿Qué ocurre cuando confirmo una venta?</div>
                <div class="manual-faq-a">El sistema descuenta los kg vendidos del stock del acopio. Si el stock queda en 0, el acopio pasa a estado <em>Vendido</em>; si queda saldo, pasa a <em>Parcial</em>.</div>
              </div>
              <div class="manual-faq">
                <div class="manual-faq-q">¿Cómo cambio la campaña activa?</div>
                <div class="manual-faq-a">Ve a <strong>Configuración → Campañas</strong> y haz clic en <em>"Activar"</em> en la campaña deseada. También puedes cambiarla desde el selector en la barra superior.</div>
              </div>
            </div>

          </div><!-- /panel-body -->
        </div><!-- /panel -->
      </div><!-- /cap-tab-manual -->

    </section>

    <!-- ══ AUDITORÍA Y SEGURIDAD ══════════════════════════════ -->
    <section id="auditoria" class="section">
      <div class="page-header">
        <h1><span class="page-icon">🛡️</span> Auditoría y Seguridad</h1>
        <button class="btn btn-primary" onclick="abrirFormAuditoria()">+ Nueva Auditoría</button>
      </div>

      <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
        <div class="stat-card"><div class="stat-value" id="aud-k-total">—</div><div class="stat-label">Total Auditorías</div></div>
        <div class="stat-card"><div class="stat-value" id="aud-k-completadas">—</div><div class="stat-label">Completadas</div></div>
        <div class="stat-card"><div class="stat-value text-danger" id="aud-k-hallazgos">—</div><div class="stat-label">Hallazgos Abiertos</div></div>
        <div class="stat-card"><div class="stat-value text-success" id="aud-k-aprobadas">—</div><div class="stat-label">Aprobadas</div></div>
      </div>

      <div class="tab-nav" id="aud-tab-nav" style="margin-bottom:18px">
        <button class="tab-btn active" onclick="mostrarAudTab('aud-tab-lista',this)">📋 Auditorías</button>
        <button class="tab-btn" onclick="mostrarAudTab('aud-tab-log',this);cargarLogSeguridad()">🔒 Bitácora de Seguridad</button>
      </div>

      <!-- Tab auditorías -->
      <div id="aud-tab-lista">
        <div class="filter-bar" style="margin-bottom:14px">
          <select id="f-aud-tipo" onchange="cargarAuditorias()">
            <option value="">Todos los tipos</option>
            <option value="interna">Interna</option>
            <option value="externa">Externa</option>
            <option value="certificacion">Certificación</option>
            <option value="inocuidad">Inocuidad</option>
          </select>
          <select id="f-aud-estado" onchange="cargarAuditorias()">
            <option value="">Todos los estados</option>
            <option value="programada">Programada</option>
            <option value="en_proceso">En Proceso</option>
            <option value="completada">Completada</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </div>
        <div id="aud-lista">
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Código</th><th>Tipo</th><th>Título</th><th>Auditor</th>
                  <th>Fecha</th><th>Hallazgos</th><th>Resultado</th><th>Estado</th><th></th>
                </tr>
              </thead>
              <tbody id="tbl-auditorias"></tbody>
            </table>
          </div>
        </div>
        <!-- Panel detalle auditoría -->
        <div id="aud-detalle" style="display:none">
          <button class="btn btn-ghost btn-sm" onclick="volverAudLista()" style="margin-bottom:16px">← Volver a lista</button>
          <div id="aud-detalle-body"></div>
        </div>
      </div>

      <!-- Tab bitácora -->
      <div id="aud-tab-log" style="display:none">
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>IP</th></tr>
            </thead>
            <tbody id="tbl-seg-log"></tbody>
          </table>
        </div>
      </div>
    </section>

  </div><!-- /content -->
</div><!-- /app -->

<!-- Overlay móvil -->
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- Toast -->
<div id="toast"></div>

<!-- ══ MODAL EDITAR PERFIL ══════════════════════════════════ -->
<div id="modal-form-perfil" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-perfil')">
  <div class="modal" onclick="event.stopPropagation()" style="max-width:440px">
    <div class="modal-header">
      <div class="modal-title">👤 Editar perfil</div>
      <button class="modal-close" onclick="toggleForm('form-perfil')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Nombre para mostrar *</label>
        <input type="text" id="inp-perfil-nombre" placeholder="Tu nombre completo" maxlength="80">
      </div>
      <div class="form-group">
        <label>Correo electrónico</label>
        <input type="email" id="inp-perfil-email" placeholder="correo@ejemplo.com" maxlength="120">
        <div class="form-hint">Se usa para recuperar tu contraseña si la olvidas</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarPerfil()">💾 Guardar cambios</button>
      <button class="btn btn-ghost"   onclick="toggleForm('form-perfil')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL CAMBIAR CONTRASEÑA ═════════════════════════════ -->
<div id="modal-form-password" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-password')">
  <div class="modal" onclick="event.stopPropagation()" style="max-width:440px">
    <div class="modal-header">
      <div class="modal-title">🔑 Cambiar contraseña</div>
      <button class="modal-close" onclick="toggleForm('form-password')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Contraseña actual *</label>
        <div style="position:relative">
          <input type="password" id="inp-pw-actual" placeholder="Tu contraseña actual"
                 style="padding-right:42px">
          <button type="button" onclick="togglePwInput('inp-pw-actual',this)"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#7B9E94;font-size:1.1rem;line-height:1">👁</button>
        </div>
      </div>
      <div class="form-group">
        <label>Nueva contraseña *</label>
        <div style="position:relative">
          <input type="password" id="inp-pw-nueva" placeholder="Mínimo 8 caracteres"
                 style="padding-right:42px" oninput="checkPwStrength(this.value)">
          <button type="button" onclick="togglePwInput('inp-pw-nueva',this)"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#7B9E94;font-size:1.1rem;line-height:1">👁</button>
        </div>
        <!-- Medidor de fortaleza -->
        <div style="height:4px;background:#e0e0e0;border-radius:2px;margin-top:6px">
          <div id="pw-strength-bar" style="height:100%;width:0;border-radius:2px;transition:width .3s,background .3s"></div>
        </div>
        <div id="pw-strength-label" style="font-size:.75rem;color:#7B9E94;margin-top:3px;min-height:16px"></div>
      </div>
      <div class="form-group">
        <label>Confirmar nueva contraseña *</label>
        <div style="position:relative">
          <input type="password" id="inp-pw-confirma" placeholder="Repite la nueva contraseña"
                 style="padding-right:42px">
          <button type="button" onclick="togglePwInput('inp-pw-confirma',this)"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#7B9E94;font-size:1.1rem;line-height:1">👁</button>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarPassword()">🔐 Actualizar contraseña</button>
      <button class="btn btn-ghost"   onclick="toggleForm('form-password')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL CLIENTE ════════════════════════════════════════ -->
<div id="modal-cliente-overlay" class="modal-overlay" onclick="cerrarFormCliente(event)">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title" id="form-cliente-title">👤 Nuevo Cliente</div>
      <button class="modal-close" onclick="cerrarFormCliente()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="c-id">
      <div class="form-grid cols-3">
        <div class="form-group">
          <label>Tipo *</label>
          <select id="c-tipo">
            <option value="productor">Productor</option>
            <option value="comprador">Comprador</option>
            <option value="ambos">Ambos</option>
          </select>
        </div>
        <div class="form-group">
          <label>Razón Social / Nombre *</label>
          <input type="text" id="c-nombre" placeholder="Ej: Juan Huamán Quispe">
        </div>
        <div class="form-group">
          <label>RUC / DNI</label>
          <input type="text" id="c-ruc" placeholder="20123456789">
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="tel" id="c-tel" placeholder="999 999 999" maxlength="9" pattern="\d{9}">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="c-email" placeholder="correo@ejemplo.com">
          <span id="c-email-error" style="display:none;color:#dc2626;font-size:.8rem;margin-top:4px;">Ingrese un correo válido</span>
        </div>
        <div class="form-group">
          <label>Departamento</label>
          <input type="text" id="c-depto" placeholder="Cajamarca">
        </div>
        <div class="form-group">
          <label>Provincia</label>
          <input type="text" id="c-prov" placeholder="Jaén">
        </div>
        <div class="form-group">
          <label>Distrito</label>
          <input type="text" id="c-dist" placeholder="Jaén">
        </div>
        <div class="form-group">
          <label>Asociación / Cooperativa</label>
          <input type="text" id="c-asoc" placeholder="Ej: CENFROCAFÉ">
        </div>
      </div>
      <div class="form-group" style="margin-top:14px">
        <label>Observaciones</label>
        <textarea id="c-notas" placeholder="Notas internas, características del productor, historial relevante..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" id="btn-guardar-cliente" onclick="guardarCliente()">💾 Guardar</button>
      <button class="btn btn-ghost" onclick="cerrarFormCliente()">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL TASA USD ════════════════════════════════════════ -->
<div id="modal-form-tasa-usd" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-tasa-usd')">
  <div class="modal" style="max-width:420px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">💵 Tipo de Cambio USD</div>
      <button class="modal-close" onclick="toggleForm('form-tasa-usd')">✕</button>
    </div>
    <div class="modal-body">
      <p style="margin:0 0 18px;color:var(--text-muted);font-size:.875rem;line-height:1.6">
        Define el tipo de cambio vigente. Este valor se aplicará automáticamente como valor por defecto en nuevos contratos de venta y movimientos de caja.
      </p>
      <div class="form-group">
        <label>Soles (PEN) por 1 USD *</label>
        <input type="number" id="cfg-tasa-usd-input" step="0.0001" min="0.0001" placeholder="3.7500">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarTasaDolar()">💾 Guardar tasa</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-tasa-usd')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL LOTE ════════════════════════════════════════════ -->
<div id="modal-form-lote" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-lote')">
  <div class="modal" style="max-width:980px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">📦 Registrar Acopio de Café</div>
      <button class="modal-close" onclick="toggleForm('form-lote')">✕</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info" style="margin-bottom:12px">ℹ️ El código se genera automáticamente — ej: <strong>ACOP-2026-0001</strong></div>

      <!-- PROVEEDOR + DNI -->
      <div class="lote-section-label">Proveedor *</div>
      <div class="form-grid cols-2" style="margin-bottom:10px;align-items:start">
        <div class="form-group" style="margin-bottom:0">
          <label>Buscar por nombre, DNI o RUC</label>
          <div class="prod-search-wrap">
            <input type="text" id="l-prod-search" placeholder="🔍 Buscar..." oninput="buscarClienteLote(this.value)" autocomplete="off">
            <input type="hidden" id="l-productor">
            <div id="l-prod-results" class="prod-results-dropdown"></div>
            <div id="l-prod-selected" class="prod-selected-card" style="display:none">
              <div class="prod-sel-info">
                <strong id="l-prod-sel-name"></strong>
                <span class="small text-muted" id="l-prod-sel-ruc"></span>
              </div>
              <button type="button" class="prod-sel-clear" onclick="limpiarClienteLote()" title="Cambiar proveedor">✕</button>
            </div>
            <div id="l-prod-notfound" class="prod-notfound" style="display:none">
              <span>⚠️ No encontrado</span>
              <button type="button" class="btn btn-sm btn-primary" onclick="irRegistrarCliente()">+ Registrar</button>
            </div>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label>DNI / RUC</label>
          <input type="text" id="l-dni" placeholder="Auto-rellenable" readonly
                 style="background:#f4f6f5;cursor:default">
        </div>
      </div>

      <!-- DATOS DEL CAFÉ -->
      <div class="lote-section-label">Datos del Café</div>
      <div class="form-grid cols-3" style="margin-bottom:10px">
        <div class="form-group">
          <label>Fecha de Acopio *</label>
          <input type="date" id="l-fecha">
        </div>
        <div class="form-group">
          <label>Hora</label>
          <input type="time" id="l-hora">
        </div>
        <div class="form-group">
          <label>Tipo de Café *</label>
          <select id="l-tipo">
            <option value="1">Pergamino</option>
            <option value="2">Oro</option>
            <option value="3">Tostado</option>
            <option value="4">Verde</option>
          </select>
        </div>
        <div class="form-group">
          <label>Sector</label>
          <div style="display:flex;gap:6px;align-items:center">
            <select id="l-sector" style="flex:1" onchange="toggleNuevoSector(this.value)">
              <option value="">— Seleccionar —</option>
              <!-- opciones cargadas dinámicamente -->
            </select>
            <button type="button" class="btn btn-sm btn-ghost" onclick="toggleNuevoSector('__nuevo__')" title="Registrar nuevo sector">+</button>
          </div>
          <input type="text" id="l-sector-nuevo" placeholder="Nombre del nuevo sector..."
                 style="display:none;margin-top:6px" oninput="sincronizarSector(this.value)">
        </div>
      </div>

      <!-- PESO Y PRECIO — solo al registrar, oculto en edición -->
      <div id="lote-campos-nuevos">
        <div class="lote-section-label">Registro de Peso y Precio</div>
        <div class="form-grid cols-3">
          <div class="form-group">
            <label>SAC (Sacos)</label>
            <input type="number" id="l-sacos" min="0" step="0.01" placeholder="2.00" oninput="calcularLote()">
          </div>
          <div class="form-group">
            <label>KG BRT (Bruto)</label>
            <input type="number" id="l-peso" step="0.001" placeholder="0.000" oninput="calcularLote()">
          </div>
          <div class="form-group">
            <label>REND %</label>
            <input type="number" id="l-rend" step="0.1" min="0" max="100" placeholder="79.0" oninput="calcularLote()">
          </div>
          <div class="form-group">
            <label>H° (Humedad %)</label>
            <input type="number" id="l-humedad" step="0.1" min="0" max="30" placeholder="11.5" oninput="calcularLote()">
          </div>
          <div class="form-group">
            <label>KG NET (Neto)</label>
            <input type="number" id="l-kg-net" step="0.001" placeholder="0.000" readonly class="input-calc">
          </div>
          <div class="form-group">
            <label>Precio / KG (S/)</label>
            <input type="number" id="l-precio" step="0.001" placeholder="0.000" oninput="calcularLote()">
          </div>
          <div class="form-group">
            <label>QQ (Quintales)</label>
            <input type="number" id="l-qq" step="0.0001" placeholder="0.0000" readonly class="input-calc">
          </div>
          <div class="form-group">
            <label>Prima S/</label>
            <input type="number" id="l-prima" step="0.01" placeholder="0.00" oninput="calcularLote()">
          </div>
          <div class="form-group">
            <label>P. Unitario (S/)</label>
            <input type="number" id="l-punit" step="0.001" placeholder="0.000" readonly class="input-calc">
          </div>
          <div class="form-group">
            <label>Total S/</label>
            <input type="number" id="l-total" step="0.01" placeholder="0.00" readonly class="input-calc">
          </div>
          <div class="form-group">
            <label>Pago Total de Prima (S/)</label>
            <input type="number" id="l-pago-prima" step="0.01" placeholder="0.00" readonly class="input-calc">
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" id="btn-guardar-lote" onclick="guardarLote()">💾 Registrar Acopio</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-lote')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL CAPACITACIÓN ════════════════════════════════════ -->
<div id="modal-form-cap" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-cap')">
  <div class="modal" style="max-width:680px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title" id="cap-modal-title">🎓 Nueva Capacitación</div>
      <button class="modal-close" onclick="toggleForm('form-cap')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid cols-2">
        <div class="form-group" style="grid-column:1/-1">
          <label>Título *</label>
          <input type="text" id="cap-titulo" placeholder="Nombre de la capacitación...">
        </div>
        <div class="form-group">
          <label>Instructor</label>
          <input type="text" id="cap-instructor" placeholder="Nombre del instructor">
        </div>
        <div class="form-group">
          <label>Organización</label>
          <input type="text" id="cap-org" placeholder="Entidad organizadora">
        </div>
        <div class="form-group">
          <label>Fecha Inicio *</label>
          <input type="date" id="cap-inicio">
        </div>
        <div class="form-group">
          <label>Fecha Fin</label>
          <input type="date" id="cap-fin">
        </div>
        <div class="form-group">
          <label>Lugar</label>
          <input type="text" id="cap-lugar" placeholder="Ciudad / plataforma virtual">
        </div>
        <div class="form-group">
          <label>Modalidad</label>
          <select id="cap-modalidad">
            <option value="presencial">Presencial</option>
            <option value="virtual">Virtual</option>
            <option value="mixto">Mixto</option>
          </select>
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select id="cap-estado">
            <option value="programado">Programado</option>
            <option value="en_curso">En Curso</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>
        <div class="form-group">
          <label>Máx. Participantes</label>
          <input type="number" id="cap-max" min="1" placeholder="Sin límite">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Descripción / Notas</label>
          <textarea id="cap-notas" rows="3" placeholder="Temas a tratar, objetivos..."></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" id="btn-guardar-cap" onclick="guardarCapacitacion()">💾 Guardar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-cap')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL PARTICIPANTE ═════════════════════════════════════ -->
<div id="modal-form-part" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-part')">
  <div class="modal" style="max-width:480px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">👤 Agregar Participante</div>
      <button class="modal-close" onclick="toggleForm('form-part')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Nombre completo *</label>
        <input type="text" id="part-nombre" placeholder="Nombre del participante">
      </div>
      <div class="form-group">
        <label>Cargo / Rol</label>
        <input type="text" id="part-cargo" placeholder="Productor, técnico, supervisor...">
      </div>
      <div class="form-group" style="display:flex;gap:24px;margin-top:8px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" id="part-asistio" checked> Asistió
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" id="part-cert"> Certificado emitido
        </label>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarParticipante()">💾 Agregar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-part')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL LABORATORIO ═════════════════════════════════════ -->
<div id="modal-form-lab" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-lab')">
  <div class="modal" style="max-width:900px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">🧪 Registrar Análisis de Calidad</div>
      <button class="modal-close" onclick="toggleForm('form-lab')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-cols-2">
        <div>
          <div class="form-col-label">Datos Generales</div>
          <div class="form-grid cols-2">
            <div class="form-group">
              <label>Lote *</label>
              <select id="lab-lote"></select>
            </div>
            <div class="form-group">
              <label>Fecha Análisis *</label>
              <input type="date" id="lab-fecha">
            </div>
            <div class="form-group">
              <label>Analista</label>
              <input type="text" id="lab-analista">
            </div>
            <div class="form-group">
              <label>Laboratorio</label>
              <input type="text" id="lab-lab" placeholder="Interno">
            </div>
            <div class="form-group">
              <label>Humedad (%)</label>
              <input type="number" id="lab-humedad" step="0.01" placeholder="11.5">
            </div>
            <div class="form-group">
              <label>Rendimiento (%)</label>
              <input type="number" id="lab-rend" step="0.01" placeholder="76.0">
            </div>
            <div class="form-group">
              <label>Defectos Cat. 1</label>
              <input type="number" id="lab-def1" min="0" placeholder="0">
            </div>
            <div class="form-group">
              <label>Defectos Cat. 2</label>
              <input type="number" id="lab-def2" min="0" placeholder="0">
            </div>
            <div class="form-group">
              <label>Aprobado</label>
              <select id="lab-aprobado">
                <option value="">⏳ Pendiente</option>
                <option value="1">✅ Aprobado</option>
                <option value="0">❌ Rechazado</option>
              </select>
            </div>
          </div>
        </div>
        <div>
          <div class="form-col-label">Perfil de Taza (SCA)</div>
          <div class="form-grid cols-2">
            <div class="form-group">
              <label>Score Total</label>
              <input type="number" id="lab-score" step="0.25" min="0" max="100" placeholder="82.25" oninput="previewScore()">
            </div>
            <div class="form-group">
              <label>Fragancia / Aroma</label>
              <input type="number" id="lab-aroma" step="0.25" min="6" max="10" placeholder="8.00">
            </div>
            <div class="form-group">
              <label>Sabor</label>
              <input type="number" id="lab-sabor" step="0.25" min="6" max="10">
            </div>
            <div class="form-group">
              <label>Acidez</label>
              <input type="number" id="lab-acidez" step="0.25" min="6" max="10">
            </div>
            <div class="form-group">
              <label>Cuerpo</label>
              <input type="number" id="lab-cuerpo" step="0.25" min="6" max="10">
            </div>
            <div class="form-group">
              <label>Balance</label>
              <input type="number" id="lab-balance" step="0.25" min="6" max="10">
            </div>
          </div>
          <div id="score-preview" class="score-preview">
            <div class="score-preview-label">Clasificación estimada</div>
            <div class="score-clasif" id="score-clasif">—</div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarAnalisis()">💾 Guardar Análisis</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-lab')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL DETALLE VENTA ════════════════════════════════════ -->
<div id="modal-detalle-venta" class="modal-overlay" onclick="if(event.target===this)toggleForm('detalle-venta')">
  <div class="modal modal-lg" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">📄 Detalle de Venta</div>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn btn-sm btn-ghost" onclick="imprimirDetalleVenta()" title="Imprimir detalle">🖨 Imprimir</button>
        <button class="modal-close" onclick="toggleForm('detalle-venta')">✕</button>
      </div>
    </div>
    <div class="modal-body" id="modal-detalle-venta-body">
      <div class="empty"><span class="empty-icon">⏳</span><p>Cargando...</p></div>
    </div>
  </div>
</div>

<!-- ══ MODAL COTIZACIÓN ════════════════════════════════════════ -->
<div id="modal-form-cotizacion" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-cotizacion')">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">📋 Nueva Cotización</div>
      <button class="modal-close" onclick="toggleForm('form-cotizacion')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Comprador *</label>
          <select id="cot-comprador"></select>
        </div>
        <div class="form-group">
          <label>Lote *</label>
          <select id="cot-lote"></select>
        </div>
        <div class="form-group">
          <label>Fecha Emisión *</label>
          <input type="date" id="cot-fecha">
        </div>
        <div class="form-group">
          <label>Fecha Vencimiento *</label>
          <input type="date" id="cot-vence">
        </div>
        <div class="form-group">
          <label>Cantidad (kg) *</label>
          <input type="number" id="cot-cantidad" step="0.001" oninput="calcularTotalCot()">
        </div>
        <div class="form-group">
          <label>Precio S/ / kg *</label>
          <input type="number" id="cot-precio" step="0.0001" placeholder="3.5000" oninput="calcularTotalCot()">
        </div>
        <div class="form-group">
          <label>Incoterm</label>
          <select id="cot-incoterm">
            <option value="FOB">FOB</option>
            <option value="CIF">CIF</option>
            <option value="EXW">EXW</option>
            <option value="DDP">DDP</option>
          </select>
        </div>
        <div class="form-group">
          <label>Condiciones de pago</label>
          <input type="text" id="cot-condiciones" placeholder="Ej: 50% anticipo, 50% contra entrega">
        </div>
      </div>
      <div id="cot-calculo" style="display:none" class="calc-preview">
        <div class="calc-preview-label">Resumen de cotización</div>
        <div class="calc-preview-row">
          <div>
            <div class="calc-item-label">TOTAL USD</div>
            <div class="calc-item-value text-green" id="cot-total-usd">—</div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarCotizacion()">💾 Crear Cotización</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-cotizacion')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL VENTA ════════════════════════════════════════════ -->
<!-- ══ MODAL ORDEN DE TRABAJO ════════════════════════════════ -->
<div id="modal-form-ot" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-ot')">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">📋 Nueva Orden de Trabajo</div>
      <button class="modal-close" onclick="toggleForm('form-ot')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Lote *</label>
          <select id="ot-lote"></select>
        </div>
        <div class="form-group">
          <label>Tipo de Proceso *</label>
          <select id="ot-tipo">
            <option value="secado">Secado</option>
            <option value="despergaminado">Despergaminado</option>
            <option value="tostado">Tostado</option>
            <option value="molido">Molido</option>
            <option value="envasado">Envasado</option>
            <option value="seleccion">Selección</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label>Fecha Inicio *</label>
          <input type="date" id="ot-fecha-ini">
        </div>
        <div class="form-group">
          <label>Fecha Fin Estimada</label>
          <input type="date" id="ot-fecha-fin">
        </div>
        <div class="form-group">
          <label>Operador</label>
          <input type="text" id="ot-operador" placeholder="Nombre del responsable">
        </div>
        <div class="form-group">
          <label>Maquinaria</label>
          <input type="text" id="ot-maquinaria" placeholder="Equipo utilizado">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarOT()">💾 Crear Orden</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-ot')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL PROVEEDOR ═══════════════════════════════════════ -->
<div id="modal-form-prov" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-prov')">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title" id="prov-modal-title">👷 Registrar Proveedor</div>
      <button class="modal-close" onclick="toggleForm('form-prov')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Razón Social *</label>
          <input type="text" id="pv-nombre" placeholder="Empresa S.A.C.">
        </div>
        <div class="form-group">
          <label>RUC</label>
          <input type="text" id="pv-ruc" placeholder="20xxxxxxxxx">
        </div>
        <div class="form-group">
          <label>Categoría</label>
          <select id="pv-cat">
            <option value="insumos">Insumos</option>
            <option value="servicios">Servicios</option>
            <option value="transporte">Transporte</option>
            <option value="maquinaria">Maquinaria</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label>Teléfono</label>
          <input type="text" id="pv-tel">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="pv-email">
        </div>
        <div class="form-group">
          <label>Condiciones de Pago</label>
          <input type="text" id="pv-cond" placeholder="30 días, contado...">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" id="btn-guardar-prov" onclick="guardarProveedor()">💾 Guardar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-prov')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL ORDEN DE COMPRA ═════════════════════════════════ -->
<div id="modal-form-oc" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-oc')">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">📄 Nueva Orden de Compra</div>
      <button class="modal-close" onclick="toggleForm('form-oc')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Proveedor *</label>
          <select id="oc-prov"></select>
        </div>
        <div class="form-group">
          <label>Fecha Emisión *</label>
          <input type="date" id="oc-fecha">
        </div>
        <div class="form-group">
          <label>Fecha Entrega</label>
          <input type="date" id="oc-entrega">
        </div>
        <div class="form-group">
          <label>Moneda</label>
          <select id="oc-moneda">
            <option value="PEN">PEN (Soles)</option>
            <option value="USD">USD</option>
          </select>
        </div>
      </div>
      <div class="mt-14">
        <div class="form-col-label mb-8">Ítems de la Orden</div>
        <div id="oc-items-list"></div>
        <button class="btn btn-ghost btn-sm mt-8" onclick="agregarItemOC()">+ Agregar ítem</button>
      </div>
      <div id="oc-total-preview" class="oc-total-preview">
        <span class="text-muted">Subtotal: </span><strong id="oc-subtotal">—</strong>
        &nbsp;|&nbsp;
        <span class="text-muted">IGV (18%): </span><strong id="oc-igv">—</strong>
        &nbsp;|&nbsp;
        <span class="fw-black" style="color:var(--cafe)">TOTAL: <span id="oc-total">—</span></span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarOC()">💾 Crear OC</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-oc')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL COMPROBANTE WHATSAPP ════════════════════════════ -->
<div id="modal-wa" class="modal-overlay" onclick="if(event.target===this)cerrarModalWA()">
  <div class="modal" onclick="event.stopPropagation()" style="max-width:500px">
    <div class="modal-header">
      <div class="modal-title">📱 Enviar comprobante por WhatsApp</div>
      <button class="modal-close" onclick="cerrarModalWA()">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label>Número de WhatsApp del comprador</label>
        <div style="display:flex;gap:8px">
          <select id="wa-prefijo" style="width:110px;padding:8px;border:1px solid var(--border);border-radius:6px;font-size:.88rem">
            <option value="51">🇵🇪 +51</option>
            <option value="1">🇺🇸 +1</option>
            <option value="44">🇬🇧 +44</option>
            <option value="49">🇩🇪 +49</option>
            <option value="31">🇳🇱 +31</option>
            <option value="32">🇧🇪 +32</option>
            <option value="39">🇮🇹 +39</option>
            <option value="34">🇪🇸 +34</option>
            <option value="81">🇯🇵 +81</option>
            <option value="82">🇰🇷 +82</option>
          </select>
          <input type="tel" id="wa-telefono" placeholder="999 999 999" style="flex:1">
        </div>
      </div>
      <div class="form-group">
        <label>Vista previa del mensaje</label>
        <textarea id="wa-preview" rows="12" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:.8rem;font-family:monospace;resize:vertical;background:var(--bg)"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-wa" onclick="abrirWhatsApp()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:5px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.524 5.847L.057 23.625a.5.5 0 0 0 .612.612l5.704-1.488A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.006-1.375l-.36-.213-3.731.974.996-3.638-.234-.374A9.818 9.818 0 0 1 12 2.182c5.42 0 9.818 4.398 9.818 9.818S17.42 21.818 12 21.818z"/></svg>
        Abrir WhatsApp
      </button>
      <button class="btn btn-ghost" onclick="cerrarModalWA()">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL FLUJO DE CAJA ════════════════════════════════════ -->
<div id="modal-form-flujo" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-flujo')">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">💸 Registrar Movimiento de Caja</div>
      <button class="modal-close" onclick="toggleForm('form-flujo')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Fecha *</label>
          <input type="date" id="fc-fecha">
        </div>
        <div class="form-group">
          <label>Tipo *</label>
          <select id="fc-tipo">
            <option value="ingreso">Ingreso</option>
            <option value="egreso">Egreso</option>
          </select>
        </div>
        <div class="form-group">
          <label>Categoría</label>
          <select id="fc-cat">
            <option value="operativo">Operativo</option>
            <option value="financiero">Financiero</option>
            <option value="inversion">Inversión</option>
          </select>
        </div>
        <div class="form-group">
          <label>Concepto *</label>
          <input type="text" id="fc-concepto" placeholder="Descripción del movimiento">
        </div>
        <div class="form-group">
          <label>Monto *</label>
          <input type="number" id="fc-monto" step="0.01" placeholder="0.00">
        </div>
        <div class="form-group">
          <label>Moneda</label>
          <select id="fc-moneda">
            <option value="PEN">PEN (Soles)</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
          </select>
        </div>
        <div class="form-group">
          <label>Tipo de Cambio</label>
          <input type="number" id="fc-tc" step="0.0001" placeholder="1.0000">
        </div>
        <div class="form-group">
          <label>Cuenta / Banco</label>
          <input type="text" id="fc-banco" placeholder="BCP, BBVA...">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarFlujo()">💾 Registrar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-flujo')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ── MODAL: CUENTA POR PAGAR ───────────────────────────── -->
<div id="modal-form-cxp" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-cxp')">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">💸 Agregar Cuenta por Pagar</div>
      <button class="modal-close" onclick="toggleForm('form-cxp')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Proveedor *</label>
          <select id="cxp-prov"></select>
        </div>
        <div class="form-group">
          <label>N° Documento *</label>
          <input type="text" id="cxp-num-doc" placeholder="F001-00123">
        </div>
        <div class="form-group">
          <label>Tipo de Documento</label>
          <select id="cxp-tipo-doc">
            <option value="factura">Factura</option>
            <option value="boleta">Boleta</option>
            <option value="liquidacion">Liquidación</option>
            <option value="otro">Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label>Moneda</label>
          <select id="cxp-moneda">
            <option value="PEN">PEN — Soles</option>
            <option value="USD">USD — Dólares</option>
          </select>
        </div>
        <div class="form-group">
          <label>Fecha de Emisión *</label>
          <input type="date" id="cxp-emision">
        </div>
        <div class="form-group">
          <label>Fecha de Vencimiento *</label>
          <input type="date" id="cxp-vencimiento">
        </div>
        <div class="form-group">
          <label>Monto Total *</label>
          <input type="number" id="cxp-monto" step="0.01" placeholder="0.00">
        </div>
      </div>
      <div class="form-group" style="margin-top:10px">
        <label>Notas</label>
        <textarea id="cxp-notas" rows="2" placeholder="Observaciones opcionales..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarCxP()">💾 Registrar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-cxp')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ── MODAL: NUEVA CAMPAÑA ──────────────────────────────── -->
<div id="modal-form-nueva-campana" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-nueva-campana')">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">🗓 Nueva Campaña</h3>
      <button class="modal-close" onclick="toggleForm('form-nueva-campana')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid" style="grid-template-columns:1fr 1fr">
        <div class="form-group">
          <label>Año *</label>
          <input type="number" id="nc-anio" min="2000" max="2100" placeholder="2026">
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select id="nc-estado">
            <option value="activa">Activa</option>
            <option value="cerrada">Cerrada</option>
            <option value="archivada">Archivada</option>
          </select>
        </div>
        <div class="form-group">
          <label>Fecha Inicio</label>
          <input type="date" id="nc-inicio">
        </div>
        <div class="form-group">
          <label>Fecha Fin</label>
          <input type="date" id="nc-fin">
        </div>
      </div>
      <div class="form-group">
        <label>Notas</label>
        <textarea id="nc-notas" rows="2" placeholder="Observaciones opcionales..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarCampana()">💾 Guardar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-nueva-campana')">Cancelar</button>
    </div>
  </div>
</div>


<!-- ══ MODAL NUEVO / EDITAR USUARIO ═════════════════════════ -->
<div id="modal-form-usuario" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-usuario')">
  <div class="modal" onclick="event.stopPropagation()" style="max-width:500px">
    <div class="modal-header">
      <div class="modal-title" id="modal-usuario-title">👤 Nuevo Usuario</div>
      <button class="modal-close" onclick="toggleForm('form-usuario')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="u-username-original">
      <div class="form-grid" style="grid-template-columns:1fr 1fr">
        <div class="form-group">
          <label>Nombre de usuario *</label>
          <input type="text" id="u-username" placeholder="usuario_123" maxlength="40" autocomplete="off">
          <div class="form-hint" id="u-username-hint">Letras, números y guión bajo (3-40 caracteres)</div>
        </div>
        <div class="form-group">
          <label>Rol *</label>
          <select id="u-rol">
            <option value="Administrador">Administrador</option>
            <option value="Supervisor">Supervisor</option>
            <option value="Operador" selected>Operador</option>
            <option value="Auditor">Auditor</option>
          </select>
        </div>
        <div class="form-group">
          <label>Nombre para mostrar *</label>
          <input type="text" id="u-nombre" placeholder="Nombre Apellido" maxlength="80">
        </div>
        <div class="form-group">
          <label>Correo electrónico</label>
          <input type="email" id="u-email" placeholder="correo@ejemplo.com" maxlength="120">
        </div>
      </div>
      <div class="form-group">
        <label id="u-password-label">Contraseña *</label>
        <div style="position:relative">
          <input type="password" id="u-password" placeholder="Mínimo 8 caracteres"
                 style="padding-right:42px" oninput="checkUwStrength(this.value)">
          <button type="button" onclick="togglePwInput('u-password',this)"
                  style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                         background:none;border:none;cursor:pointer;color:#7B9E94;font-size:1.1rem;line-height:1">👁</button>
        </div>
        <div style="height:4px;background:#e0e0e0;border-radius:2px;margin-top:6px">
          <div id="uw-strength-bar" style="height:100%;width:0;border-radius:2px;transition:width .3s,background .3s"></div>
        </div>
        <div class="form-hint" id="u-password-hint">Mínimo 8 caracteres</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarUsuario()">💾 Guardar</button>
      <button class="btn btn-ghost"   onclick="toggleForm('form-usuario')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL REGISTRAR BACKUP ════════════════════════════════ -->
<div id="modal-form-backup-reg" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-backup-reg')">
  <div class="modal" onclick="event.stopPropagation()" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title" id="modal-backup-title">💾 Registrar Backup</div>
      <button class="modal-close" onclick="toggleForm('form-backup-reg')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="bk-tipo">
      <div class="form-grid" style="grid-template-columns:1fr 1fr">
        <div class="form-group">
          <label>Fecha y hora *</label>
          <input type="datetime-local" id="bk-fecha">
        </div>
        <div class="form-group">
          <label>Estado *</label>
          <select id="bk-estado">
            <option value="completado">✅ Completado</option>
            <option value="pendiente">🕒 Pendiente</option>
            <option value="fallido">❌ Fallido</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <input type="text" id="bk-descripcion" placeholder="Ej: Backup automático nocturno" maxlength="200">
      </div>
      <div class="form-group">
        <label>Realizado por</label>
        <input type="text" id="bk-realizado-por" placeholder="Nombre del responsable" maxlength="80">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="confirmarRegistrarBackup()">💾 Registrar</button>
      <button class="btn btn-ghost"   onclick="toggleForm('form-backup-reg')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL CAPACITACIÓN ════════════════════════════════════ -->
<div id="modal-form-cap" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-cap')">
  <div class="modal" style="max-width:680px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title" id="cap-modal-title">🎓 Nueva Capacitación</div>
      <button class="modal-close" onclick="toggleForm('form-cap')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid cols-2">
        <div class="form-group" style="grid-column:1/-1">
          <label>Título *</label>
          <input type="text" id="cap-titulo" placeholder="Buenas Prácticas Agrícolas...">
        </div>
        <div class="form-group">
          <label>Instructor</label>
          <input type="text" id="cap-instructor" placeholder="Nombre del instructor">
        </div>
        <div class="form-group">
          <label>Organización</label>
          <input type="text" id="cap-org" placeholder="SENASA, MINAGRI...">
        </div>
        <div class="form-group">
          <label>Fecha Inicio *</label>
          <input type="date" id="cap-inicio">
        </div>
        <div class="form-group">
          <label>Fecha Fin</label>
          <input type="date" id="cap-fin">
        </div>
        <div class="form-group">
          <label>Lugar</label>
          <input type="text" id="cap-lugar" placeholder="Centro de Acopio, Virtual...">
        </div>
        <div class="form-group">
          <label>Modalidad</label>
          <select id="cap-modalidad">
            <option value="presencial">Presencial</option>
            <option value="virtual">Virtual</option>
            <option value="mixto">Mixto</option>
          </select>
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select id="cap-estado">
            <option value="programado">Programado</option>
            <option value="en_curso">En Curso</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>
        <div class="form-group">
          <label>Máx. Participantes</label>
          <input type="number" id="cap-max" min="1" placeholder="30">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Descripción / Notas</label>
          <textarea id="cap-notas" rows="2" placeholder="Temas a tratar, objetivos..."></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" id="btn-guardar-cap" onclick="guardarCapacitacion()">💾 Guardar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-cap')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL PARTICIPANTE ════════════════════════════════════ -->
<div id="modal-form-part" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-part')">
  <div class="modal" style="max-width:480px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title">👤 Agregar Participante</div>
      <button class="modal-close" onclick="toggleForm('form-part')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group">
          <label>Nombre</label>
          <input type="text" id="part-nombre" placeholder="Nombre completo">
        </div>
        <div class="form-group">
          <label>Cargo / Rol</label>
          <input type="text" id="part-cargo" placeholder="Productor, Técnico...">
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:22px">
          <input type="checkbox" id="part-asistio" checked style="width:auto">
          <label for="part-asistio" style="margin:0">Asistió</label>
          <input type="checkbox" id="part-cert" style="width:auto;margin-left:16px">
          <label for="part-cert" style="margin:0">Certificado emitido</label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarParticipante()">💾 Agregar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-part')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL AUDITORÍA ═══════════════════════════════════════ -->
<div id="modal-form-aud" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-aud')">
  <div class="modal" style="max-width:680px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title" id="aud-modal-title">🛡️ Nueva Auditoría</div>
      <button class="modal-close" onclick="toggleForm('form-aud')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid cols-2">
        <div class="form-group" style="grid-column:1/-1">
          <label>Título *</label>
          <input type="text" id="aud-titulo" placeholder="Auditoría interna semestral...">
        </div>
        <div class="form-group">
          <label>Tipo</label>
          <select id="aud-tipo">
            <option value="interna">Interna</option>
            <option value="externa">Externa</option>
            <option value="certificacion">Certificación</option>
            <option value="inocuidad">Inocuidad</option>
          </select>
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select id="aud-estado">
            <option value="programada">Programada</option>
            <option value="en_proceso">En Proceso</option>
            <option value="completada">Completada</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </div>
        <div class="form-group">
          <label>Auditor / Responsable</label>
          <input type="text" id="aud-auditor" placeholder="Nombre del auditor">
        </div>
        <div class="form-group">
          <label>Organismo / Empresa</label>
          <input type="text" id="aud-organismo" placeholder="SGS, Bureau Veritas...">
        </div>
        <div class="form-group">
          <label>Fecha Auditoría *</label>
          <input type="date" id="aud-fecha">
        </div>
        <div class="form-group">
          <label>Próxima Auditoría</label>
          <input type="date" id="aud-prox">
        </div>
        <div class="form-group">
          <label>Resultado</label>
          <select id="aud-resultado">
            <option value="">— Sin resultado —</option>
            <option value="aprobada">Aprobada</option>
            <option value="observada">Observada</option>
            <option value="rechazada">Rechazada</option>
          </select>
        </div>
        <div class="form-group">
          <label>Puntaje</label>
          <input type="number" id="aud-puntaje" step="0.01" min="0" max="100" placeholder="0 – 100">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Descripción / Alcance</label>
          <textarea id="aud-notas" rows="2" placeholder="Alcance, norma de referencia..."></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" id="btn-guardar-aud" onclick="guardarAuditoria()">💾 Guardar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-aud')">Cancelar</button>
    </div>
  </div>
</div>

<!-- ══ MODAL HALLAZGO ════════════════════════════════════════ -->
<div id="modal-form-hall" class="modal-overlay" onclick="if(event.target===this)toggleForm('form-hall')">
  <div class="modal" style="max-width:580px" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div class="modal-title" id="hall-modal-title">⚠️ Registrar Hallazgo</div>
      <button class="modal-close" onclick="toggleForm('form-hall')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid cols-2">
        <div class="form-group">
          <label>Tipo de Hallazgo</label>
          <select id="hall-tipo">
            <option value="no_conformidad_mayor">No Conformidad Mayor</option>
            <option value="no_conformidad_menor">No Conformidad Menor</option>
            <option value="observacion" selected>Observación</option>
            <option value="oportunidad_mejora">Oportunidad de Mejora</option>
          </select>
        </div>
        <div class="form-group">
          <label>Estado</label>
          <select id="hall-estado">
            <option value="abierto">Abierto</option>
            <option value="en_proceso">En Proceso</option>
            <option value="cerrado">Cerrado</option>
          </select>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Descripción *</label>
          <textarea id="hall-desc" rows="2" placeholder="Descripción del hallazgo..."></textarea>
        </div>
        <div class="form-group">
          <label>Área / Proceso</label>
          <input type="text" id="hall-area" placeholder="Almacén, Producción...">
        </div>
        <div class="form-group">
          <label>Responsable</label>
          <input type="text" id="hall-responsable" placeholder="Nombre del responsable">
        </div>
        <div class="form-group">
          <label>Fecha Límite</label>
          <input type="date" id="hall-limite">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label>Acción Correctiva</label>
          <textarea id="hall-accion" rows="2" placeholder="Acción tomada para corregir..."></textarea>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-success" onclick="guardarHallazgo()">💾 Guardar</button>
      <button class="btn btn-ghost" onclick="toggleForm('form-hall')">Cancelar</button>
    </div>
  </div>
</div>

<script src="js/app.js?v=<?= $v_js ?>"></script>
</body>
</html>
