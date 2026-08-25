/* ============================================================
   SISTEMA DE TRAZABILIDAD DE CAFÉ — Frontend JS
   ============================================================ */

// Local (XAMPP compartido): /trazabilidad_cafe/public/... -> API en /trazabilidad_cafe/api
// VPS (dominio propio): /public/... -> API en /api
const API = location.pathname.startsWith('/trazabilidad_cafe/') ? '/trazabilidad_cafe/api' : '/api';

/* ── Estado global laboratorio ───────────────────────────── */
let _labProductorId = null;
let _labDniTimer    = null;

/* ── Sidebar ─────────────────────────────────────────────── */
let sidebarCollapsed = false;

function toggleSidebar() {
  if (window.innerWidth <= 768) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('show');
  } else {
    sidebarCollapsed = !sidebarCollapsed;
    document.getElementById('sidebar').classList.toggle('collapsed', sidebarCollapsed);
    document.body.classList.toggle('sidebar-collapsed', sidebarCollapsed);
  }
}

function closeSidebar() {
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebar-overlay').classList.remove('show');
}

/* ── Navegación ──────────────────────────────────────────── */
const sectionTitles = {
  dashboard:      'Dashboard',
  clientes:       'Clientes',
  acopios:        'Acopios de Café',
  kardex:         'Trazabilidad',
  laboratorio:    'Laboratorio',
  ventas:         'Ventas',
  produccion:     'Producción',
  stock:          'Almacén',
  compras:        'Compras',
  financiero:     'Financiero',
  capacitacion:   'Capacitación',
  auditoria:      'Auditoría y Seguridad',
  configuracion:  'Configuración',
};

const sectionGroups = {
  dashboard:      null,
  clientes:       'Operaciones',
  acopios:        'Operaciones',
  compras:        'Operaciones',
  laboratorio:    'Operaciones',
  kardex:         'Trazabilidad',
  stock:          'Almacén',
  ventas:         'Comercial',
  financiero:     'Administrativo',
  capacitacion:   'Administrativo',
  auditoria:      'Administrativo',
  configuracion:  'Configuración',
};

let navHistory = [];

function updateBreadcrumb(name) {
  const group = sectionGroups[name];
  const title = sectionTitles[name] || name;

  document.getElementById('topbar-section').textContent = title;
  document.getElementById('bc-group').textContent  = group || '';
  document.getElementById('bc-sep-group').textContent = group ? '›' : '';
  document.getElementById('bc-sep-page').textContent  = name !== 'dashboard' ? '›' : '';

  // Ocultar "Inicio ›" y la sección actual cuando estamos en dashboard
  document.getElementById('bc-sep-page').style.display  = name !== 'dashboard' ? '' : 'none';
  document.getElementById('topbar-section').style.display = name !== 'dashboard' ? '' : 'none';

  // Botón atrás
  document.getElementById('btn-back').style.display = navHistory.length > 0 ? 'inline-flex' : 'none';
}

function navTo(name, addHistory = true) {
  if (addHistory) {
    const current = document.querySelector('.section.active');
    if (current && current.id !== name) navHistory.push(current.id);
  }

  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
  const btn = document.querySelector(`.nav-item[data-section="${name}"]`);
  if (btn) btn.classList.add('active');

  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  const sec = document.getElementById(name);
  if (sec) sec.classList.add('active');

  updateBreadcrumb(name);
  closeSidebar();

  if (name === 'dashboard')   loadDashboard();
  if (name === 'clientes')    { resetCliTabs(); cargarClientes(); }
  if (name === 'acopios')     cargarLotes();
  if (name === 'kardex')      cargarKardex();
  if (name === 'laboratorio') { _labProductorId = null; cargarLab(); cargarLotesSelectLab(); }
  if (name === 'ventas')      { cargarVentas(); cargarTasaUSDDisplay(); abrirFormVenta(); }
  if (name === 'produccion')  { cargarOTs(); cargarLotsSelectOT(); }
  if (name === 'stock')       { cargarStock(); cargarValorizacion(); }
  if (name === 'compras')     mostrarTab('tab-proveedores', null);
  if (name === 'financiero')    cargarFinanciero();
  if (name === 'capacitacion')  cargarCapacitaciones();
  if (name === 'auditoria')     cargarAuditorias();
  if (name === 'configuracion') { cargarConfiguracion(); mostrarCfgTab('cfg-tab-general', document.querySelector('#cfg-tab-nav .tab-btn')); }
}

function navBack() {
  if (navHistory.length === 0) return;
  const prev = navHistory.pop();
  navTo(prev, false);
}

document.querySelectorAll('.nav-item').forEach(btn => {
  btn.addEventListener('click', () => {
    navHistory = []; // reset historial al navegar desde sidebar
    navTo(btn.dataset.section);
  });
});

/* ── Utilidades ──────────────────────────────────────────── */
function toggleForm(id) {
  const overlay = document.getElementById('modal-' + id);
  if (!overlay) return;
  const isOpen = overlay.classList.contains('open');
  if (isOpen) {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  } else {
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    const first = overlay.querySelector('input:not([type=hidden]), select, textarea');
    if (first) setTimeout(() => first.focus(), 100);
  }
}

function toast(msg, error = false) {
  const t = document.getElementById('toast');
  t.innerHTML = (error ? '⚠️ ' : '✅ ') + msg;
  t.style.background = error ? 'var(--danger)' : 'var(--verde)';
  t.classList.add('show');
  t.style.display = 'flex';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.display = 'none'; t.classList.remove('show'); }, 3500);
}

function badge(cls, txt) {
  return `<span class="badge badge-${cls}">${txt}</span>`;
}

function clasifBadge(c) {
  const map = { specialty:'specialty', premium:'premium', comercial:'comercial', descarte:'descarte' };
  return c ? badge(map[c] || 'comercial', c) : '<span class="text-muted small">—</span>';
}

function estadoBadge(e) {
  return e ? badge(e, e.replace(/_/g, ' ')) : '<span class="text-muted">—</span>';
}

function tipoBadge(t) {
  return badge(t || 'otro', t || '—');
}

function fmt(n, dec = 1) {
  if (n == null || n === '') return '—';
  return parseFloat(n).toLocaleString('es-PE', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  });
}

function fmtPEN(n) {
  if (n == null) return '—';
  return 'S/ ' + parseFloat(n).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function fmtUSD(n) {
  if (n == null) return '—';
  return 'S/ ' + parseFloat(n).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

async function api(method, path, data = null) {
  let res;
  try {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (data) opts.body = JSON.stringify(data);
    res = await fetch(API + path, opts);
  } catch (e) {
    return { success: false, error: 'Error de red: ' + e.message };
  }
  try {
    return await res.json();
  } catch {
    // El servidor respondió pero no con JSON (ej: página de error del propio Apache/PHP-FPM)
    return { success: false, error: `El servidor respondió con un error inesperado (HTTP ${res.status}). Intenta de nuevo en unos segundos.` };
  }
}

function emptyRow(cols, msg = 'Sin registros') {
  return `<tr><td colspan="${cols}">
    <div class="empty"><span class="empty-icon">🍃</span><p>${msg}</p></div>
  </td></tr>`;
}

/* ══════════════════════════════════════════════════════════
   DASHBOARD
══════════════════════════════════════════════════════════ */
let _chartCalidad    = null;
let _chartInventario = null;

async function loadDashboard() {
  const hoy = new Date();
  document.getElementById('dash-fecha').textContent =
    hoy.toLocaleDateString('es-PE', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

  const campana = localStorage.getItem('campana_activa') || String(hoy.getFullYear());

  const [trazRes, ventasRes, lotesRes] = await Promise.all([
    api('GET', `/trazabilidad/resumen?campana=${campana}`),
    api('GET', '/ventas/dashboard'),
    api('GET', '/acopios?per_page=6'),
  ]);

  const traz   = trazRes.data   || {};
  const ventas = ventasRes.data || {};
  const lotes  = lotesRes.data  || [];

  // API shape: traz.lotes, traz.ventas, traz.calidad_distribucion[], traz.top_productores[], traz.top_destinos[]
  const kpiL      = traz.lotes  || {};
  const kpiV      = traz.ventas || {};
  const porEstado = kpiL.por_estado || {};
  const calidadDist = traz.calidad_distribucion || [];

  // ── KPIs ─────────────────────────────────────────────────────
  const activos = (kpiL.total || 0) - (porEstado.vendidos || 0);
  _set('m-lotes', activos || lotes.filter(l => l.estado !== 'vendido').length);
  _set('m-lotes-sub', `${kpiL.total ?? '—'} total · ${porEstado.en_proceso ?? 0} en proceso · ${porEstado.disponibles ?? 0} disponibles`);

  const kgDisp = kpiL.total_kg_disponible ?? 0;
  const kgAcop = kpiL.total_kg_ingresado  ?? 0;
  _set('m-kg',     fmt(kgDisp, 0) + ' kg');
  _set('m-kg-sub', `de ${fmt(kgAcop, 0)} kg ingresados`);

  const penTot = kpiV.total_ingresos_pen ?? null;
  _set('m-usd',     penTot ? fmtPEN(penTot) : '—');
  _set('m-usd-sub', `${kpiV.total_contratos ?? ventas.resumen?.confirmados ?? 0} contratos`);

  const scoreAvg      = kpiL.promedio_score_taza;
  const totalAnalisis = calidadDist.reduce((s, r) => s + (parseInt(r.total) || 0), 0);
  _set('m-score',     scoreAvg ? Number(scoreAvg).toFixed(1) + ' pts' : '—');
  _set('m-score-sub', `${totalAnalisis} análisis registrados`);

  _set('m-productores',     kpiL.total_productores ?? '—');
  _set('m-productores-sub', `compradores: ${kpiV.total_compradores ?? '—'}`);

  const kgVendido  = kpiV.total_kg_vendido ?? 0;
  const precioProm = (penTot && kgVendido) ? (penTot / kgVendido) : null;
  _set('m-precio', precioProm ? fmtPEN(precioProm) + '/kg' : '—');

  // ── Topbar ───────────────────────────────────────────────────
  document.querySelector('#topbar-stock strong').textContent = fmt(kgDisp || kgAcop, 0) + ' kg';
  const badgeVentas = document.getElementById('badge-ventas');
  const conf = ventas.resumen?.confirmados || 0;
  badgeVentas.textContent = conf;
  badgeVentas.style.display = conf > 0 ? '' : 'none';

  // ── Charts ───────────────────────────────────────────────────
  _renderChartCalidad(calidadDist);
  _renderChartInventario(kgAcop, kgDisp, kgVendido);

  // ── Top tables ───────────────────────────────────────────────
  _renderTopProductores(traz.top_productores || []);
  _renderTopDestinos(traz.top_destinos || []);

  // ── Recent lots ──────────────────────────────────────────────
  const tbody = document.getElementById('tbl-lotes-dash');
  tbody.innerHTML = lotes.length ? lotes.map(l => `
    <tr>
      <td class="mono fw-bold">${l.codigo}</td>
      <td>${l.productor}</td>
      <td>${l.tipo_cafe}</td>
      <td>${l.fecha_acopio}</td>
      <td>${fmt(l.peso_actual_kg)} kg</td>
      <td class="fw-bold" style="color:${l.score_taza >= 80 ? 'var(--verde)' : l.score_taza >= 60 ? 'var(--warn)' : 'var(--text-muted)'}">
        ${l.score_taza ? l.score_taza + ' pts' : '—'}
      </td>
      <td>${estadoBadge(l.estado)}</td>
    </tr>
  `).join('') : emptyRow(7, 'No hay acopios registrados aún');
}

function _set(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

// calidadDist = [{clasificacion, total, score_promedio}, ...]  (from API)
function _renderChartCalidad(calidadDist) {
  const distMap = {};
  calidadDist.forEach(r => { distMap[r.clasificacion] = parseInt(r.total) || 0; });

  const labels = ['Specialty', 'Premium', 'Comercial', 'Descarte'];
  const keys   = ['specialty', 'premium', 'comercial', 'descarte'];
  const values = keys.map(k => distMap[k] ?? 0);
  const colors = ['#2d7a45', '#8a6f2e', '#5b7fa6', '#888'];
  const total  = values.reduce((s, v) => s + v, 0);

  _set('dash-total-analisis', total ? `${total} análisis` : '');

  const legend = document.getElementById('chart-calidad-legend');
  if (legend) {
    legend.innerHTML = labels.map((l, i) => `
      <li style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
        <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:${colors[i]};flex-shrink:0;"></span>
        <span style="flex:1;">${l}</span>
        <strong>${values[i]}</strong>
        <span class="text-muted" style="min-width:32px;text-align:right;">${total ? Math.round(values[i] / total * 100) : 0}%</span>
      </li>
    `).join('');
  }

  if (_chartCalidad) { _chartCalidad.destroy(); _chartCalidad = null; }
  const ctx = document.getElementById('chart-calidad');
  if (!ctx) return;
  _chartCalidad = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: c => ` ${c.label}: ${c.raw} (${total ? Math.round(c.raw / total * 100) : 0}%)` } },
      },
      cutout: '65%',
    },
  });
}

function _renderChartInventario(kgIngresado, kgDisponible, kgVendido) {
  if (_chartInventario) { _chartInventario.destroy(); _chartInventario = null; }
  const ctx = document.getElementById('chart-inventario');
  if (!ctx) return;

  _chartInventario = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Ingresado', 'Disponible', 'Vendido'],
      datasets: [{
        data: [kgIngresado, kgDisponible, kgVendido],
        backgroundColor: ['#5b7fa6', '#2d7a45', '#8a6f2e'],
        borderRadius: 6,
        barThickness: 32,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { callback: v => fmt(v, 0) + ' kg' }, grid: { color: 'rgba(0,0,0,0.05)' } },
        y: { grid: { display: false } },
      },
    },
  });
}

// top_productores: [{id, razon_social, departamento, total_lotes, total_kg}]
function _renderTopProductores(rows) {
  const tbody = document.getElementById('tbl-top-productores');
  if (!tbody) return;
  tbody.innerHTML = rows.length ? rows.slice(0, 5).map(r => `
    <tr>
      <td>${r.razon_social ?? '—'}</td>
      <td class="text-center">${r.total_lotes ?? '—'}</td>
      <td>${fmt(r.total_kg, 0)} kg</td>
      <td class="fw-bold" style="color:var(--verde)">${r.score_promedio ? Number(r.score_promedio).toFixed(1) : '—'}</td>
    </tr>
  `).join('') : emptyRow(4, 'Sin datos de productores');
}

// top_destinos: [{id, razon_social, pais_destino, total_contratos, kg_total, usd_total}]
function _renderTopDestinos(rows) {
  const tbody = document.getElementById('tbl-top-destinos');
  if (!tbody) return;
  tbody.innerHTML = rows.length ? rows.slice(0, 5).map(r => `
    <tr>
      <td>${r.razon_social ?? '—'}${r.pais_destino ? `<br><small class="text-muted">${r.pais_destino}</small>` : ''}</td>
      <td class="text-center">${r.total_contratos ?? '—'}</td>
      <td>${fmt(r.kg_total, 0)} kg</td>
      <td>${r.pen_total ? fmtPEN(r.pen_total) : '—'}</td>
    </tr>
  `).join('') : emptyRow(4, 'Sin datos de destinos');
}

/* ══════════════════════════════════════════════════════════
   CLIENTES
══════════════════════════════════════════════════════════ */
/* ══════════════════════════════════════════════════════════
   CLIENTES — gestión completa
══════════════════════════════════════════════════════════ */
let _drawerClienteId = null;

// Válido si es un id real (número/string no vacío) y no las cadenas literales "null"/"undefined"
// que aparecen cuando un valor null/undefined se interpola directo en un atributo HTML sin comillas.
function _idValido(id) {
  return id !== null && id !== undefined && id !== '' && id !== 'null' && id !== 'undefined';
}

function setCliTab(btn) {
  document.querySelectorAll('.cli-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('f-tipo-cliente').value = btn.dataset.tipo;
  cargarClientes();
}

function resetCliTabs() {
  const tabs = document.querySelectorAll('.cli-tab');
  tabs.forEach(b => b.classList.remove('active'));
  if (tabs[0]) tabs[0].classList.add('active');
  const inp = document.getElementById('f-tipo-cliente');
  if (inp) inp.value = '';
}

async function cargarClientes() {
  const tipo   = document.getElementById('f-tipo-cliente').value;
  const search = document.getElementById('f-buscar-cliente').value;
  const qs = new URLSearchParams(
    Object.fromEntries(Object.entries({ tipo, search }).filter(([,v]) => v))
  ).toString();
  const res = await api('GET', `/clientes?${qs}&per_page=200`);
  if (!res.success) { toast('Error al cargar clientes: ' + (res.error || 'sin detalle'), true); return; }
  const rows  = res.data || [];

  // KPIs
  const total  = rows.length;
  const prods  = rows.filter(c => c.tipo === 'productor').length;
  const comps  = rows.filter(c => c.tipo === 'comprador').length;
  const ambos  = rows.filter(c => c.tipo === 'ambos').length;
  document.getElementById('kpi-cli-total').textContent = total;
  document.getElementById('kpi-cli-prod').textContent  = prods;
  document.getElementById('kpi-cli-comp').textContent  = comps;
  document.getElementById('kpi-cli-ambos').textContent = ambos;

  const tbody = document.getElementById('tbl-clientes');
  tbody.innerHTML = rows.length ? rows.map(c => `
    <tr>
      <td>${badge(c.tipo, c.tipo)}</td>
      <td><strong>${c.razon_social}</strong></td>
      <td class="mono small">${c.ruc_dni || '—'}</td>
      <td class="small">${[c.departamento, c.provincia].filter(Boolean).join(', ') || '—'}</td>
      <td class="small">${c.asociacion || '—'}</td>
      <td class="small">${c.telefono ? `📞 ${c.telefono}` : ''}${c.email ? `<br><span class="text-muted">✉ ${c.email}</span>` : ''}${!c.telefono && !c.email ? '—' : ''}</td>
      <td>
        <span class="link-verde" onclick="verCliente(${c.id})">Ver detalle</span>
      </td>
    </tr>
  `).join('') : emptyRow(7);
}

function abrirFormCliente(id = null) {
  // Limpiar
  ['c-id','c-nombre','c-ruc','c-tel','c-email','c-depto','c-prov','c-dist','c-asoc','c-alt','c-ha','c-pais','c-notas']
    .forEach(f => { const el = document.getElementById(f); if (el) el.value = ''; });
  document.getElementById('c-tipo').value = 'productor';

  if (id) {
    // Modo edición: cargar datos
    cargarDatosFormCliente(id);
    document.getElementById('form-cliente-title').textContent = '✏️ Editar Cliente';
    document.getElementById('btn-guardar-cliente').textContent = '💾 Actualizar';
  } else {
    document.getElementById('form-cliente-title').textContent = '👤 Nuevo Cliente';
    document.getElementById('btn-guardar-cliente').textContent = '💾 Guardar';
  }

  document.getElementById('c-email-error').style.display = 'none';
  document.getElementById('c-email').addEventListener('input', function () {
    if (this.value.includes('@')) document.getElementById('c-email-error').style.display = 'none';
  }, { once: true });

  document.getElementById('modal-cliente-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('c-nombre').focus();
}

async function cargarDatosFormCliente(id) {
  const res = await api('GET', `/clientes/${id}`);
  const c = res.data;
  if (!c) return;
  document.getElementById('c-id').value     = c.id;
  document.getElementById('c-tipo').value   = c.tipo;
  document.getElementById('c-nombre').value = c.razon_social || '';
  document.getElementById('c-ruc').value    = c.ruc_dni || '';
  document.getElementById('c-tel').value    = c.telefono || '';
  document.getElementById('c-email').value  = c.email || '';
  document.getElementById('c-depto').value  = c.departamento || '';
  document.getElementById('c-prov').value   = c.provincia || '';
  document.getElementById('c-dist').value   = c.distrito || '';
  document.getElementById('c-asoc').value   = c.asociacion || '';
  document.getElementById('c-notas').value    = c.notas || '';
}

function cerrarFormCliente(e) {
  if (e && e.target !== document.getElementById('modal-cliente-overlay')) return;
  document.getElementById('modal-cliente-overlay').classList.remove('open');
  document.body.style.overflow = '';
}

async function guardarCliente() {
  const id = document.getElementById('c-id').value;
  const nombre = document.getElementById('c-nombre').value.trim();
  if (!nombre) { toast('La razón social es obligatoria', true); return; }

  const telVal = document.getElementById('c-tel').value.trim();
  if (telVal && !/^\d{9}$/.test(telVal)) {
    toast('El teléfono debe tener exactamente 9 dígitos', true); return;
  }

  const emailVal = document.getElementById('c-email').value.trim();
  const emailError = document.getElementById('c-email-error');
  if (emailVal && !emailVal.includes('@')) {
    emailError.style.display = 'block';
    document.getElementById('c-email').focus();
    return;
  }
  emailError.style.display = 'none';

  const data = {
    tipo:          document.getElementById('c-tipo').value,
    razon_social:  nombre,
    ruc_dni:       document.getElementById('c-ruc').value   || null,
    telefono:      telVal || null,
    email:         emailVal || null,
    departamento:  document.getElementById('c-depto').value || null,
    provincia:     document.getElementById('c-prov').value  || null,
    distrito:      document.getElementById('c-dist').value  || null,
    asociacion:    document.getElementById('c-asoc').value  || null,
    notas: document.getElementById('c-notas').value   || null,
  };

  const res = id
    ? await api('PUT',  `/clientes/${id}`, data)
    : await api('POST', '/clientes', data);

  if (res.success) {
    toast(id ? 'Cliente actualizado correctamente' : 'Cliente registrado correctamente');
    cerrarFormCliente();
    cargarClientes();
  } else {
    const detalle = res.details?.length ? res.details.join(', ') : (res.error || 'Error desconocido');
    toast('Error: ' + detalle, true);
  }
}

async function verCliente(id) {
  _drawerClienteId = id;
  const [resC, resL] = await Promise.all([
    api('GET', `/clientes/${id}`),
    api('GET', `/clientes/${id}/acopios`),
  ]);
  const c = resC.data;
  if (!c) return;

  document.getElementById('drawer-cli-nombre').textContent = c.razon_social;
  document.getElementById('drawer-cli-tipo').innerHTML     = badge(c.tipo, c.tipo);
  document.getElementById('drawer-cli-obs').textContent    = c.notas || 'Sin notas registradas.';

  const campos = [
    ['RUC / DNI',     c.ruc_dni],
    ['Teléfono',      c.telefono],
    ['Email',         c.email],
    ['Departamento',  c.departamento],
    ['Provincia',     c.provincia],
    ['Distrito',      c.distrito],
    ['Asociación',    c.asociacion],
    ['Altitud',       c.altitud_msnm ? c.altitud_msnm + ' msnm' : null],
    ['Hectáreas',     c.hectareas ? fmt(c.hectareas, 2) + ' ha' : null],
    ['País destino',  c.pais_destino],
    ['Registrado',    c.creado_en ? c.creado_en.split('T')[0] : null],
  ];
  document.getElementById('drawer-cli-info').innerHTML = campos
    .filter(([,v]) => v)
    .map(([k,v]) => `<div class="drawer-field"><span class="drawer-field-label">${k}</span><span class="drawer-field-value">${v}</span></div>`)
    .join('') || '<p class="text-muted small">Sin información adicional.</p>';

  const lotes = resL.data || [];
  document.getElementById('drawer-cli-lotes').innerHTML = lotes.length
    ? lotes.map(l => `
        <div class="drawer-lote-row">
          <span class="mono fw-bold">${l.codigo}</span>
          <span class="small">${l.tipo_cafe || ''}</span>
          <span class="small">${fmt(l.peso_actual_kg)} kg</span>
          ${estadoBadge(l.estado)}
        </div>`).join('')
    : '<p class="text-muted small">Sin lotes registrados.</p>';

  abrirDrawer();
}

function abrirDrawer() {
  document.getElementById('drawer-cliente').classList.add('open');
  document.getElementById('drawer-overlay').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function cerrarDrawer() {
  document.getElementById('drawer-cliente').classList.remove('open');
  document.getElementById('drawer-overlay').style.display = 'none';
  document.body.style.overflow = '';
  _drawerClienteId = null;
}

function editarClienteDesdeDrawer() {
  if (!_idValido(_drawerClienteId)) return;
  const id = _drawerClienteId;
  cerrarDrawer();
  abrirFormCliente(id);
}

function eliminarClienteDesdeDrawer() {
  if (!_idValido(_drawerClienteId)) return;
  const id     = _drawerClienteId;
  const nombre = document.getElementById('drawer-cli-nombre').textContent;
  cerrarDrawer();
  confirmarEliminarCliente(id, nombre);
}

function confirmarEliminarCliente(id, nombre) {
  if (!confirm(`¿Eliminar al cliente "${nombre}"?\n\nEsta acción no se puede deshacer.`)) return;
  eliminarCliente(id);
}

async function eliminarCliente(id) {
  if (id === null || id === undefined || id === 'null' || id === 'undefined' || id === '') {
    toast('No se pudo identificar el cliente a eliminar. Recarga la página e intenta de nuevo.', true);
    return;
  }
  const res = await api('DELETE', `/clientes/${id}`);
  if (res.success) {
    toast('Cliente eliminado');
    cargarClientes();
  } else {
    toast(res.error || 'No se pudo eliminar el cliente', true);
  }
}

/* ══════════════════════════════════════════════════════════
   LOTES
══════════════════════════════════════════════════════════ */
let _loteEditId = null;

function abrirFormNuevoLote() {
  _loteEditId = null;
  limpiarClienteLote();
  document.querySelector('#modal-form-lote .modal-title').textContent = '📦 Registrar Acopio de Café';
  document.getElementById('btn-guardar-lote').textContent = '💾 Registrar Acopio';
  document.getElementById('lote-campos-nuevos').style.display = '';
  ['l-tipo','l-fecha','l-variedad','l-proceso','l-finca','l-altitud',
   'l-hora','l-sacos','l-peso','l-rend','l-humedad','l-precio','l-prima',
   'l-kg-net','l-qq','l-punit','l-total','l-pago-prima',
   'l-dni','l-sector','l-sector-nuevo']
    .forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      if (el.tagName === 'SELECT') el.selectedIndex = 0;
      else el.value = '';
    });
  // Ocultar campo nuevo sector
  const ns = document.getElementById('l-sector-nuevo');
  if (ns) ns.style.display = 'none';
  document.getElementById('l-fecha').value  = new Date().toISOString().split('T')[0];
  document.getElementById('l-hora').value   = new Date().toTimeString().slice(0,5);
  document.getElementById('l-sacos').value  = '2.00';
  // Prima por defecto S/ 0.40
  document.getElementById('l-prima').value = '0.40';
  cargarSectoresForm();
  toggleForm('form-lote');
}

async function cargarLotes() {
  const estado = document.getElementById('f-estado-lote').value;
  const qs = estado ? `estado=${estado}&` : '';
  const res = await api('GET', `/acopios?${qs}per_page=100`);
  const tbody = document.getElementById('tbl-lotes');
  const rows  = res.data || [];
  tbody.innerHTML = rows.length ? rows.map(l => `
    <tr class="traz-row" onclick="editarLote(${l.id})" title="Click para editar">
      <td class="mono fw-bold">${l.codigo}</td>
      <td>${l.productor}</td>
      <td>${l.tipo_cafe}</td>
      <td>${l.fecha_acopio}</td>
      <td>${l.hora_acopio ? l.hora_acopio.slice(0,5) : '—'}</td>
      <td>${fmt(l.peso_inicial_kg)} kg</td>
      <td class="fw-bold">${fmt(l.peso_actual_kg)} kg</td>
      <td class="fw-bold" style="color:${l.score_taza >= 80 ? 'var(--verde)' : 'var(--text-muted)'}">
        ${l.score_taza || '—'}
      </td>
      <td>${estadoBadge(l.estado)}</td>
    </tr>
  `).join('') : emptyRow(9);

  poblarSelectLotes(rows);
}

async function editarLote(id) {
  const res = await api('GET', `/acopios/${id}`);
  const l = res.data;
  if (!l) { toast('No se pudo cargar el acopio', true); return; }

  _loteEditId = id;

  // Cambiar título y botón del modal
  document.querySelector('#modal-form-lote .modal-title').textContent = '✏️ Editar Acopio ' + l.codigo;
  document.getElementById('btn-guardar-lote').textContent = '💾 Actualizar';

  // Rellenar campos editables
  document.getElementById('l-tipo').value  = l.tipo_cafe_id;
  document.getElementById('l-fecha').value = l.fecha_acopio?.slice(0, 10) || '';
  const horaEl = document.getElementById('l-hora');
  if (horaEl) horaEl.value = l.hora_acopio ? l.hora_acopio.slice(0,5) : '';

  // Sector
  await cargarSectoresForm();
  const selSec = document.getElementById('l-sector');
  if (selSec && l.region) selSec.value = l.region;

  // Mostrar productor (incluye auto-fill de DNI)
  seleccionarClienteLote({ id: l.productor_id, razon_social: l.productor, ruc_dni: l.productor_ruc || '' });

  // Ocultar campos que no aplican en edición (peso, precio, prima)
  document.getElementById('lote-campos-nuevos').style.display = 'none';

  toggleForm('form-lote');
}

function poblarSelectLotes(lotes) {
  const sel = document.getElementById('lab-lote');
  if (!sel) return;
  const disponibles = lotes.filter(l => l.estado !== 'vendido');
  sel.innerHTML = disponibles.length
    ? disponibles.map(l => `<option value="${l.id}">${l.codigo} — ${l.productor}${l.productor_ruc ? ' ['+l.productor_ruc+']' : ''} (${fmt(l.peso_actual_kg)} kg)</option>`).join('')
    : '<option value="">Sin lotes disponibles</option>';
}

/* ── Buscador de cliente en form-lote ───────────────────── */
let _prodSearchTimer = null;

function buscarClienteLote(val) {
  clearTimeout(_prodSearchTimer);
  document.getElementById('l-prod-results').innerHTML = '';
  document.getElementById('l-prod-results').style.display = 'none';
  document.getElementById('l-prod-notfound').style.display = 'none';
  if (!val || val.trim().length < 2) return;

  _prodSearchTimer = setTimeout(async () => {
    const res = await api('GET', `/clientes?search=${encodeURIComponent(val.trim())}&per_page=10`);
    const rows = res.data || [];

    if (!rows.length) {
      document.getElementById('l-prod-notfound').style.display = 'flex';
      return;
    }

    // 1 resultado → autoseleccionar directamente
    if (rows.length === 1) {
      seleccionarClienteLote(rows[0]);
      return;
    }

    // Múltiples → mostrar lista inline para elegir
    const list = document.getElementById('l-prod-results');
    list.innerHTML = rows.map(c => `
      <div class="prod-result-item" onclick='seleccionarClienteLote(${JSON.stringify(c)})'>
        <strong>${c.razon_social}</strong>
        <span class="small text-muted">${c.ruc_dni || '—'} · ${c.tipo}</span>
      </div>`).join('');
    list.style.display = '';
  }, 320);
}

function seleccionarClienteLote(c) {
  document.getElementById('l-productor').value           = c.id;
  document.getElementById('l-prod-sel-name').textContent = c.razon_social;
  document.getElementById('l-prod-sel-ruc').textContent  = c.ruc_dni ? `DNI/RUC: ${c.ruc_dni}` : c.tipo;

  // Auto-rellenar DNI y altitud desde datos del cliente
  const dniEl = document.getElementById('l-dni');
  if (dniEl) dniEl.value = c.ruc_dni || '';
  if (c.altitud_msnm) document.getElementById('l-altitud').value = c.altitud_msnm;

  document.getElementById('l-prod-search').style.display   = 'none';
  document.getElementById('l-prod-results').style.display  = 'none';
  document.getElementById('l-prod-notfound').style.display = 'none';
  document.getElementById('l-prod-selected').style.display = 'flex';
}

function limpiarClienteLote() {
  document.getElementById('l-productor').value             = '';
  document.getElementById('l-prod-search').value           = '';
  document.getElementById('l-prod-search').style.display   = '';
  document.getElementById('l-prod-selected').style.display = 'none';
  document.getElementById('l-prod-results').innerHTML      = '';
  document.getElementById('l-prod-results').style.display  = 'none';
  document.getElementById('l-prod-notfound').style.display = 'none';
  const dniEl = document.getElementById('l-dni');
  if (dniEl) dniEl.value = '';
  document.getElementById('l-prod-search').focus();
}

function irRegistrarCliente() {
  toggleForm('form-lote');
  navTo('clientes');
  setTimeout(() => abrirFormCliente(), 250);
}

// ── Sectores ─────────────────────────────────────────────
async function cargarSectoresForm() {
  const sel = document.getElementById('l-sector');
  if (!sel) return;
  const res = await api('GET', '/acopios?per_page=500');
  const rows = res.data || [];
  const sectores = [...new Set(rows.map(r => r.region).filter(Boolean))].sort();
  sel.innerHTML = '<option value="">— Seleccionar —</option>'
    + sectores.map(s => `<option value="${s}">${s}</option>`).join('')
    + '<option value="__nuevo__">+ Registrar nuevo sector...</option>';
}

function toggleNuevoSector(val) {
  const sel = document.getElementById('l-sector');
  const inp = document.getElementById('l-sector-nuevo');
  if (val === '__nuevo__') {
    inp.style.display = '';
    inp.focus();
    if (sel) sel.value = '__nuevo__';
  } else {
    inp.style.display = 'none';
    inp.value = '';
  }
}

function sincronizarSector(val) {
  // El valor real del sector viene del input de texto cuando se está creando uno nuevo
}

function getSectorValue() {
  const sel = document.getElementById('l-sector');
  const inp = document.getElementById('l-sector-nuevo');
  if (sel && sel.value === '__nuevo__') return (inp && inp.value.trim()) || null;
  return (sel && sel.value) || null;
}

function calcularLote() {
  const kgBrt  = parseFloat(document.getElementById('l-peso').value)   || 0;
  const sacos  = parseFloat(document.getElementById('l-sacos').value)  || 0;
  const precio = parseFloat(document.getElementById('l-precio').value) || 0;
  // Prima: usa lo ingresado, o el default 0.40
  const primaRaw = document.getElementById('l-prima').value;
  const prima  = primaRaw !== '' ? (parseFloat(primaRaw) || 0) : 0.40;

  const kgNet     = kgBrt - (sacos * 0.2);
  const qq        = kgNet / 55.2;
  const pUnit     = precio + prima;
  const total     = kgNet * pUnit;
  const pagoPrima = kgNet * prima;

  const setVal = (id, v) => {
    const el = document.getElementById(id);
    if (el) el.value = v > 0 ? v.toFixed(2) : '';
  };
  setVal('l-kg-net',    kgNet);
  setVal('l-qq',        qq);
  setVal('l-punit',     pUnit);
  setVal('l-total',     total);
  setVal('l-pago-prima',pagoPrima);
}

async function guardarLote() {
  const productorId = document.getElementById('l-productor').value;
  const fechaVal    = document.getElementById('l-fecha').value;
  const kgBrt       = parseFloat(document.getElementById('l-peso')?.value)  || 0;
  const sacosVal    = parseFloat(document.getElementById('l-sacos')?.value) || 0;
  const kgNet       = kgBrt - (sacosVal * 0.2);

  if (!productorId)       { toast('Selecciona un productor', true); return; }
  if (!fechaVal)          { toast('Ingresa la fecha de acopio', true); return; }
  if (!_loteEditId && kgNet <= 0) { toast('Ingresa el KG BRT mayor a 0', true); return; }

  const data = {
    productor_id:        parseInt(productorId),
    tipo_cafe_id:        parseInt(document.getElementById('l-tipo').value),
    fecha_acopio:        fechaVal,
    hora_acopio:         document.getElementById('l-hora')?.value || null,
    peso_inicial_kg:     kgNet > 0 ? kgNet : null,
    peso_bruto_kg:       kgBrt > 0 ? kgBrt : null,
    sacos:               sacosVal || null,
    humedad_entrada_pct: parseFloat(document.getElementById('l-humedad')?.value) || null,
    precio_unitario:     parseFloat(document.getElementById('l-precio')?.value)  || null,
    prima_diferencial:   parseFloat(document.getElementById('l-prima')?.value)   || 0.40,
    region:              getSectorValue(),
    moneda:              'PEN',
  };

  let res;
  if (_loteEditId) {
    // Modo edición — solo campos permitidos por el backend
    const editData = {
      tipo_cafe_id: data.tipo_cafe_id,
      fecha_acopio: data.fecha_acopio,
      region:       data.region,
    };
    res = await api('PUT', `/acopios/${_loteEditId}`, editData);
  } else {
    res = await api('POST', '/acopios', data);
  }

  if (res.success) {
    toast(_loteEditId ? 'Acopio actualizado' : 'Acopio ' + res.data?.codigo + ' creado');
    _loteEditId = null;
    cargarLotes();
    toggleForm('form-lote');
  } else {
    const detalle = res.details?.length ? ': ' + res.details.join(', ') : '';
    toast((res.error || 'Error') + detalle, true);
  }
}

/* ════════════════════════════════════════════════════════════
   TRAZABILIDAD
════════════════════════════════════════════════════════════ */
let _trazLotesData = [];

async function cargarKardex() {
  const estado = document.getElementById('f-traz-estado')?.value || '';
  const qs = new URLSearchParams(
    Object.fromEntries(Object.entries({ estado }).filter(([,v]) => v))
  );

  const [lotesRes, resumenRes] = await Promise.all([
    api('GET', `/acopios?${qs}&per_page=200`),
    api('GET', '/trazabilidad/resumen'),
  ]);

  // ── KPIs ─────────────────────────────────────────────────
  const res = resumenRes.data || {};
  const lts = res.lotes  || {};
  const ven = res.ventas || {};

  document.getElementById('tm-total').textContent   = lts.total ?? '—';
  document.getElementById('tm-kg-in').textContent   = fmt(lts.total_kg_ingresado ?? 0, 0) + ' kg';
  document.getElementById('tm-score').textContent   = lts.promedio_score_taza ? fmt(lts.promedio_score_taza, 2) + ' pts' : '—';
  document.getElementById('tm-kg-vend').textContent = fmt(ven.total_kg_vendido ?? 0, 0) + ' kg';

  // ── Tabla de lotes ────────────────────────────────────────
  _trazLotesData = lotesRes.data || [];
  renderTrazLotes(_trazLotesData);
}

function filtrarTrazLotes(q) {
  const fil = q.trim().toLowerCase();
  renderTrazLotes(fil
    ? _trazLotesData.filter(l =>
        (l.codigo    || '').toLowerCase().includes(fil) ||
        (l.productor || '').toLowerCase().includes(fil))
    : _trazLotesData
  );
}

function renderTrazLotes(lotes) {
  const tbody = document.getElementById('tbl-traz-lotes');
  tbody.innerHTML = lotes.length ? lotes.map(l => `
    <tr class="traz-row" onclick="verTrazabilidadLote(${l.id})" title="Ver trazabilidad completa">
      <td class="mono fw-bold">${l.codigo}</td>
      <td>${l.productor || '—'}</td>
      <td class="small text-muted">${[l.departamento, l.provincia].filter(Boolean).join(', ') || '—'}</td>
      <td class="small">${l.fecha_acopio || '—'}</td>
      <td>${estadoBadge(l.estado)}</td>
      <td class="text-right">${fmt(l.peso_inicial_kg, 1)}</td>
      <td class="text-right fw-bold">${fmt(l.peso_actual_kg, 1)}</td>
      <td class="text-right">${l.score_taza ? fmt(l.score_taza, 2) + ' pts' : '—'}</td>
    </tr>
  `).join('') : emptyRow(8, 'Sin acopios registrados');
}

async function verTrazabilidadLote(id) {
  document.getElementById('traz-lista').style.display   = 'none';
  document.getElementById('traz-detalle').style.display = '';
  document.getElementById('traz-det-contenido').innerHTML = '<div class="loading-msg">Cargando trazabilidad...</div>';

  const res = await api('GET', `/trazabilidad/acopio/${id}`);
  if (!res.success) {
    document.getElementById('traz-det-contenido').innerHTML = '<div class="empty-state">Error al cargar los datos del lote.</div>';
    return;
  }

  const { acopio: lote, productor, linea_tiempo, certificaciones, analisis_calidad, transformaciones, ventas } = res.data;

  document.getElementById('traz-det-titulo').innerHTML =
    `<span class="page-icon">🔄</span> ${lote.codigo} <span class="traz-det-sub">· ${productor.nombre}</span>`;

  renderTrazDetalle(lote, productor, linea_tiempo, certificaciones, analisis_calidad, transformaciones, ventas);
}

function volverTrazLista() {
  document.getElementById('traz-detalle').style.display = 'none';
  document.getElementById('traz-lista').style.display   = '';
}

function renderTrazDetalle(lote, productor, timeline, certs, analisis, transformaciones, ventas) {
  const kv = (label, value) =>
    `<div class="rpt-kv-row"><span class="rpt-kv-label">${label}</span><span class="rpt-kv-value">${value ?? '—'}</span></div>`;

  const certsHTML = certs.length
    ? certs.map(c => `<span class="cert-badge">${c.codigo}</span>`).join(' ')
    : '—';

  // ── Línea de tiempo ───────────────────────────────────────
  const etapaClass = {
    'Acopio':       'tl-etapa-acopio',
    'Transformación':'tl-etapa-transf',
    'Análisis de Calidad': 'tl-etapa-calidad',
    'Venta':        'tl-etapa-venta',
    'Estado':       'tl-etapa-estado',
    'Estado Venta': 'tl-etapa-estado-venta',
  };
  const tlRows = (timeline || []).map(paso => {
    let detalle = '';
    if (paso.etapa === 'Acopio') {
      detalle = paso.detalle || '';
    } else if (paso.etapa === 'Transformación') {
      detalle = `${paso.tipo || ''} — ${fmt(paso.peso_entrada_kg,1)} kg → ${fmt(paso.peso_salida_kg,1)} kg`
              + ` (merma ${fmt(paso.merma_kg,1)} kg, rendimiento ${fmt(paso.rendimiento_pct,1)}%)`;
    } else if (paso.etapa === 'Análisis de Calidad') {
      detalle = `Score: ${fmt(paso.score_taza,2)} pts — ${paso.clasificacion || '—'}`
              + ` — Humedad: ${fmt(paso.humedad_pct,1)}% — ${paso.aprobado ? 'Aprobado' : 'No aprobado'}`;
    } else if (paso.etapa === 'Venta') {
      detalle = paso.numero_contrato
        ? `${paso.numero_contrato} — ${paso.comprador} (${paso.pais_destino || '—'}) — ${fmt(paso.cantidad_kg,1)} kg a S/ ${fmt(paso.precio_usd_kg,2)}/kg — ${paso.estado}`
        : (paso.detalle || '');
    } else {
      detalle = paso.detalle || '';
    }
    const cls = etapaClass[paso.etapa] || 'tl-etapa-default';
    return `<tr>
      <td class="rpt-tc"><span class="tl-etapa-badge ${cls}">${paso.etapa}</span></td>
      <td class="rpt-tc" style="white-space:nowrap">${paso.fecha || '—'}</td>
      <td>${detalle}</td>
    </tr>`;
  }).join('');

  // ── Análisis de calidad ───────────────────────────────────
  const avgScore = analisis.length
    ? analisis.reduce((s,a) => s + parseFloat(a.score_taza||0), 0) / analisis.length
    : null;

  const analisisSection = analisis.length ? `
    <div class="rpt-section">
      <div class="rpt-section-head">Análisis de Calidad</div>
      <div class="rpt-table-wrap">
        <table class="rpt-table">
          <thead><tr>
            <th>Fecha</th><th>Analista</th><th>Laboratorio</th>
            <th>Score Taza</th><th>Humedad %</th>
            <th>Fragancia</th><th>Aroma</th><th>Sabor</th><th>Post-Gusto</th>
            <th>Acidez</th><th>Cuerpo</th><th>Uniformidad</th><th>Balance</th>
            <th>Estado</th>
          </tr></thead>
          <tbody>
            ${analisis.map(a => `<tr>
              <td class="rpt-tc" style="white-space:nowrap">${a.fecha_analisis}</td>
              <td>${a.analista}</td>
              <td>${a.laboratorio || '—'}</td>
              <td class="rpt-tc rpt-fw">${fmt(a.score_taza,2)}</td>
              <td class="rpt-tc">${fmt(a.humedad_pct,1)}%</td>
              <td class="rpt-tc">${fmt(a.fragancia,2)}</td>
              <td class="rpt-tc">${fmt(a.aroma,2)}</td>
              <td class="rpt-tc">${fmt(a.sabor,2)}</td>
              <td class="rpt-tc">${fmt(a.post_gusto,2)}</td>
              <td class="rpt-tc">${fmt(a.acidez,2)}</td>
              <td class="rpt-tc">${fmt(a.cuerpo,2)}</td>
              <td class="rpt-tc">${fmt(a.uniformidad,2)}</td>
              <td class="rpt-tc">${fmt(a.balance,2)}</td>
              <td class="rpt-tc">${a.aprobado ? 'Aprobado' : 'No aprobado'}</td>
            </tr>`).join('')}
            <tr class="rpt-total-row">
              <td colspan="3" class="rpt-tr">PROMEDIO SCORE TAZA</td>
              <td class="rpt-tc rpt-fw">${fmt(avgScore,2)}</td>
              <td colspan="10"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>` : '';

  // Notas de catación separadas (más espacio)
  const notasSection = analisis.some(a => a.notas_catacion) ? `
    <div class="rpt-section">
      <div class="rpt-section-head">Notas de Catación</div>
      <div class="rpt-kv-block">
        ${analisis.filter(a => a.notas_catacion).map(a => `
          <div class="rpt-nota-row">
            <span class="rpt-nota-fecha">${a.fecha_analisis} — ${a.analista}</span>
            <p class="rpt-nota-texto">${a.notas_catacion}</p>
          </div>`).join('')}
      </div>
    </div>` : '';

  // ── Transformaciones ──────────────────────────────────────
  const totalMerma   = transformaciones.reduce((s,t) => s + parseFloat(t.merma_kg||0), 0);
  const totalSalida  = transformaciones.reduce((s,t) => s + parseFloat(t.peso_salida_kg||0), 0);

  const transfSection = transformaciones.length ? `
    <div class="rpt-section">
      <div class="rpt-section-head">Transformaciones</div>
      <div class="rpt-table-wrap">
        <table class="rpt-table">
          <thead><tr>
            <th>Fecha</th><th>Tipo</th><th>Operador</th><th>Maquinaria</th>
            <th>Entrada (kg)</th><th>Salida (kg)</th><th>Merma (kg)</th><th>Rendimiento %</th><th>Notas</th>
          </tr></thead>
          <tbody>
            ${transformaciones.map(t => `<tr>
              <td class="rpt-tc" style="white-space:nowrap">${t.fecha}</td>
              <td>${t.tipo_transformacion}</td>
              <td>${t.operador || '—'}</td>
              <td>${t.maquinaria || '—'}</td>
              <td class="rpt-tr">${fmt(t.peso_entrada_kg,1)}</td>
              <td class="rpt-tr rpt-fw">${fmt(t.peso_salida_kg,1)}</td>
              <td class="rpt-tr rpt-danger">${fmt(t.merma_kg,1)}</td>
              <td class="rpt-tc">${fmt(t.rendimiento_pct,1)}%</td>
              <td>${t.notas || '—'}</td>
            </tr>`).join('')}
            <tr class="rpt-total-row">
              <td colspan="5" class="rpt-tr">TOTAL TRANSFORMACIONES</td>
              <td class="rpt-tr rpt-fw">${fmt(totalSalida,1)}</td>
              <td class="rpt-tr rpt-danger rpt-fw">${fmt(totalMerma,1)}</td>
              <td colspan="2"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>` : '';

  // ── Ventas / Contratos ────────────────────────────────────
  const totalKgV  = ventas.reduce((s,v) => s + parseFloat(v.cantidad_kg||0), 0);
  const totalUsdV = ventas.reduce((s,v) => s + parseFloat(v.total_usd||0), 0);

  const ventasSection = ventas.length ? `
    <div class="rpt-section">
      <div class="rpt-section-head">Ventas / Contratos</div>
      <div class="rpt-table-wrap">
        <table class="rpt-table">
          <thead><tr>
            <th>N° Contrato</th><th>Comprador</th><th>País Destino</th><th>Estado</th>
            <th>Fecha Contrato</th><th>Fecha Entrega</th>
            <th>Cantidad (kg)</th><th>Precio S/ /kg</th><th>Total S/</th>
          </tr></thead>
          <tbody>
            ${ventas.map(v => `<tr>
              <td class="rpt-fw" style="white-space:nowrap">${v.numero_contrato}</td>
              <td>${v.comprador_nombre}</td>
              <td class="rpt-tc">${v.pais_destino || '—'}</td>
              <td class="rpt-tc">${v.estado}</td>
              <td class="rpt-tc" style="white-space:nowrap">${v.fecha_contrato}</td>
              <td class="rpt-tc" style="white-space:nowrap">${v.fecha_entrega || '—'}</td>
              <td class="rpt-tr">${fmt(v.cantidad_kg,1)}</td>
              <td class="rpt-tr">S/ ${fmt(v.precio_usd_kg,2)}</td>
              <td class="rpt-tr rpt-fw">S/ ${fmt(v.total_usd,2)}</td>
            </tr>`).join('')}
            <tr class="rpt-total-row">
              <td colspan="6" class="rpt-tr">TOTAL</td>
              <td class="rpt-tr rpt-fw">${fmt(totalKgV,1)}</td>
              <td></td>
              <td class="rpt-tr rpt-fw">S/ ${fmt(totalUsdV,2)}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>` : '';

  // ── Render ────────────────────────────────────────────────
  document.getElementById('traz-det-contenido').innerHTML = `
    <div class="rpt-wrap">

      <div class="rpt-header">
        <div class="rpt-header-lot">ACOPIO: ${lote.codigo}</div>
        <div class="rpt-header-sub">Razón Social / Nombre: ${productor.nombre}</div>
      </div>

      <div class="rpt-title">TRAZABILIDAD DEL ACOPIO</div>

      <div class="rpt-section">
        <div class="rpt-section-head">Información General</div>
        <div class="rpt-kv-grid">
          ${kv('Código', lote.codigo)}
          ${kv('Campaña', lote.campaña || lote['campaña'])}
          ${kv('Tipo Café', lote.tipo_cafe)}
          ${kv('Variedad', lote.variedad)}
          ${kv('Proceso Beneficio', lote.proceso_beneficio)}
          ${kv('Finca', lote.finca)}
          ${kv('Altitud', lote.altitud_msnm ? lote.altitud_msnm + ' msnm' : null)}
          ${kv('Estado', lote.estado ? lote.estado.charAt(0).toUpperCase() + lote.estado.slice(1) : null)}
          ${kv('Peso Inicial', fmt(lote.peso_inicial_kg,1) + ' kg')}
          ${kv('Peso Actual', fmt(lote.peso_actual_kg,1) + ' kg')}
          ${kv('Fecha Acopio', lote.fecha_acopio)}
          ${kv('Certificaciones', certsHTML)}
        </div>
      </div>

      <div class="rpt-section">
        <div class="rpt-section-head">Productor</div>
        <div class="rpt-kv-grid">
          ${kv('Nombre', productor.nombre)}
          ${kv('Documento', productor.ruc_dni)}
          ${kv('Departamento', productor.departamento)}
          ${kv('Provincia', productor.provincia)}
          ${kv('Distrito', productor.distrito)}
          ${kv('Altitud', productor.altitud_msnm ? productor.altitud_msnm + ' msnm' : null)}
          ${productor.asociacion ? kv('Asociación', productor.asociacion) : ''}
          ${productor.telefono   ? kv('Teléfono',   productor.telefono)   : ''}
          ${productor.email      ? kv('Email',       productor.email)      : ''}
        </div>
      </div>

      <div class="rpt-section">
        <div class="rpt-section-head">Línea de Tiempo</div>
        <div class="rpt-table-wrap">
          <table class="rpt-table">
            <thead><tr><th>Etapa</th><th>Fecha</th><th>Descripción</th></tr></thead>
            <tbody>${tlRows || '<tr><td colspan="3" class="rpt-tc">Sin eventos registrados</td></tr>'}</tbody>
          </table>
        </div>
      </div>

      ${analisisSection}
      ${notasSection}
      ${transfSection}
      ${ventasSection}

    </div>
  `;
}

/* ══════════════════════════════════════════════════════════
   LABORATORIO
══════════════════════════════════════════════════════════ */
async function cargarLotesSelectLab() {
  const res = await api('GET', '/acopios?per_page=200');
  const sel = document.getElementById('lab-lote');
  if (!sel) return;
  const rows = (res.data||[]).filter(l => l.estado !== 'vendido');
  sel.innerHTML = rows.map(l => `<option value="${l.id}">${l.codigo} — ${l.productor}</option>`).join('');
}

function previewScore() {
  const s = parseFloat(document.getElementById('lab-score').value);
  const preview = document.getElementById('score-preview');
  const clasif  = document.getElementById('score-clasif');
  if (!s) { preview.style.display = 'none'; return; }
  preview.style.display = 'block';
  if      (s >= 80) { clasif.textContent = '🏆 Specialty Coffee (≥80)'; clasif.style.color = 'var(--verde)'; }
  else if (s >= 75) { clasif.textContent = '⭐ Premium (≥75)';           clasif.style.color = 'var(--warn)'; }
  else if (s >= 60) { clasif.textContent = '☕ Comercial (≥60)';         clasif.style.color = 'var(--info)'; }
  else              { clasif.textContent = '⚠️ Descarte (<60)';          clasif.style.color = 'var(--danger)'; }
}

function buscarLabPorDni(val) {
  clearTimeout(_labDniTimer);
  const card = document.getElementById('lab-productor-card');
  if (!val.trim()) {
    _labProductorId = null;
    card.style.display = 'none';
    cargarLab();
    return;
  }
  _labDniTimer = setTimeout(async () => {
    const res = await api('GET', `/clientes?search=${encodeURIComponent(val.trim())}&per_page=10`);
    const encontrados = (res.data || []).filter(c => c.tipo === 'productor' || c.tipo === 'ambos');
    card.style.display = 'block';
    if (encontrados.length) {
      const c = encontrados[0];
      _labProductorId = c.id;
      card.innerHTML = `<div class="lab-productor-info">
        <span class="lab-productor-nombre">${c.razon_social}</span>
        <span>DNI/RUC: <strong>${c.ruc_dni || '—'}</strong></span>
        ${c.telefono   ? `<span>Tel: ${c.telefono}</span>`   : ''}
        ${c.asociacion ? `<span>Asoc: ${c.asociacion}</span>` : ''}
        ${c.distrito   ? `<span>${c.distrito}${c.provincia ? ', '+c.provincia : ''}</span>` : ''}
      </div>`;
    } else {
      _labProductorId = null;
      card.innerHTML = `<div class="lab-productor-info lab-productor-notfound">
        Sin resultados para <strong>${val}</strong>
      </div>`;
    }
    cargarLab();
  }, 400);
}

async function cargarLab() {
  const lista = document.getElementById('lab-lista');
  if (!lista) return;

  lista.innerHTML = '<div style="padding:16px;color:var(--text-muted)">Cargando...</div>';

  try {
    const params = {};
    const clasif   = document.getElementById('f-clasif')?.value   || '';
    const aprobado = document.getElementById('f-aprobado')?.value || '';
    const desde    = document.getElementById('f-lab-desde')?.value || '';
    if (clasif)   params.clasificacion = clasif;
    if (aprobado) params.aprobado      = aprobado;
    if (desde)    params.desde         = desde;

    const qs  = new URLSearchParams(params).toString();
    const url = API + '/laboratorio' + (qs ? '?' + qs : '');

    const resp = await fetch(url);
    const json = await resp.json();
    let rows = Array.isArray(json.data) ? json.data : [];

    if (_labProductorId)
      rows = rows.filter(a => String(a.productor_id) === String(_labProductorId));

    if (!rows.length) {
      lista.innerHTML = '<div class="empty"><span class="empty-icon">🍃</span><p>Sin análisis registrados</p></div>';
      return;
    }

    lista.innerHTML = rows.map(a => {
      const sc       = parseFloat(a.score_taza);
      const scColor  = sc >= 80 ? 'var(--verde)' : sc >= 75 ? 'var(--warn)' : sc >= 60 ? 'var(--info)' : 'var(--danger)';
      const humAlert = a.humedad_pct && (parseFloat(a.humedad_pct) < 10 || parseFloat(a.humedad_pct) > 14);
      const estado   = a.aprobado === null
        ? '<span class="text-muted">⏳ Pendiente</span>'
        : a.aprobado
          ? '<span class="text-green">✅ Aprobado</span>'
          : '<span class="text-red">❌ Rechazado</span>';
      const metaLab  = [a.fecha_analisis, a.analista,
        (a.laboratorio && a.laboratorio !== 'Interno') ? a.laboratorio : null]
        .filter(Boolean).join(' · ');
      return `<div class="lab-item">
        <div class="lab-item-head">
          <span class="lab-item-lote mono">${a.acopio_codigo}</span>
          <span class="lab-item-productor">${a.productor}</span>
          <span class="lab-item-meta">${metaLab}</span>
          <span class="lab-item-estado">${estado}</span>
        </div>
        <div class="lab-item-body">
          <div class="lab-item-score" style="color:${scColor}">${a.score_taza || '—'}<span class="lab-item-score-label">SCA</span></div>
          <div class="lab-item-clasif">${clasifBadge(a.clasificacion)}</div>
          <div class="lab-item-metrics">
            <span ${humAlert ? 'class="lab-metric-alert"' : ''}>💧 ${a.humedad_pct||'—'}%${humAlert?' ⚠️':''}</span>
            <span>⚙️ ${a.rendimiento_pct||'—'}%</span>
            <span>C1: ${a.defectos_cat1??'—'} / C2: ${a.defectos_cat2??'—'}</span>
          </div>
          ${a.notas_catacion ? `<div class="lab-item-notas">${a.notas_catacion}</div>` : ''}
        </div>
      </div>`;
    }).join('');

  } catch(e) {
    lista.innerHTML = `<div class="empty"><span class="empty-icon">⚠️</span><p>Error: ${e.message}</p></div>`;
  }
}

async function guardarAnalisis() {
  const data = {
    acopio_id:       document.getElementById('lab-lote').value,
    fecha_analisis:  document.getElementById('lab-fecha').value,
    analista:        document.getElementById('lab-analista').value || null,
    laboratorio:     document.getElementById('lab-lab').value || 'Interno',
    humedad_pct:     parseFloat(document.getElementById('lab-humedad').value) || null,
    rendimiento_pct: parseFloat(document.getElementById('lab-rend').value) || null,
    defectos_cat1:   parseInt(document.getElementById('lab-def1').value) || 0,
    defectos_cat2:   parseInt(document.getElementById('lab-def2').value) || 0,
    score_taza:      parseFloat(document.getElementById('lab-score').value) || null,
    aroma:           parseFloat(document.getElementById('lab-aroma').value) || null,
    sabor:           parseFloat(document.getElementById('lab-sabor').value) || null,
    acidez:          parseFloat(document.getElementById('lab-acidez').value) || null,
    cuerpo:          parseFloat(document.getElementById('lab-cuerpo').value) || null,
    balance:         parseFloat(document.getElementById('lab-balance').value) || null,
    aprobado:        document.getElementById('lab-aprobado').value !== ''
                       ? parseInt(document.getElementById('lab-aprobado').value) : null,
  };
  const res = await api('POST', '/laboratorio', data);
  if (res.success) {
    let msg = `Análisis guardado — Clasificación: ${res.data.clasificacion || 'pendiente'}`;
    if (res.data.alertas?.length) msg += ' — ⚠ ' + res.data.alertas[0];
    toast(msg);
    cargarLab();
    toggleForm('form-lab');
  } else toast(res.error || 'Error al guardar', true);
}

/* ══════════════════════════════════════════════════════════
   VENTAS — módulo profesional con SUNAT integrado
══════════════════════════════════════════════════════════ */

// ── Helpers de presentación ──────────────────────────────

function sunatBadge(v) {
  if (!v.sunat_documento_id) return '<span class="text-muted small">—</span>';
  const cls = { aceptado:'aceptado', rechazado:'rechazado', observado:'observado', anulado:'anulado' }[v.sunat_estado] || 'pendiente';
  const label = v.sunat_estado || 'pendiente';
  const ref   = v.sunat_serie && v.sunat_numero ? `${v.sunat_serie}-${v.sunat_numero}` : '';
  return `<span class="badge badge-${cls}" title="${ref}">${label}</span>`;
}

function accionesBtns(v) {
  const btns = [`<button class="btn btn-ghost btn-xs" onclick="verDetalleVenta(${v.id})" title="Ver detalle">👁 Ver</button>`];
  if (v.estado === 'borrador')   btns.push(`<button class="btn btn-success btn-xs" onclick="confirmarVenta(${v.id})">✓</button>`);
  if (v.estado === 'confirmado') btns.push(`<button class="btn btn-xs" style="background:var(--info);color:#fff" onclick="iniciarProceso(${v.id})" title="Iniciar proceso">▶</button>`);
  if (v.estado === 'en_proceso') btns.push(`<button class="btn btn-success btn-xs" onclick="entregarVenta(${v.id})" title="Marcar entregado">📦</button>`);
  if (!v.sunat_documento_id && ['confirmado','en_proceso','entregado'].includes(v.estado)) {
    btns.push(`<button class="btn btn-xs" style="background:#dc2626;color:#fff" onclick="emitirFactura(${v.id})" title="Emitir Factura">F</button>`);
    btns.push(`<button class="btn btn-xs" style="background:#7c3aed;color:#fff" onclick="emitirBoleta(${v.id})"  title="Emitir Boleta">B</button>`);
  }
  if (v.sunat_documento_id) btns.push(`<button class="btn btn-ghost btn-xs" onclick="consultarCpe(${v.id})" title="Consultar CPE">🔄</button>`);
  if (!['cancelado','entregado'].includes(v.estado)) btns.push(`<button class="btn btn-ghost btn-xs" onclick="cancelarVenta(${v.id})" title="Cancelar">✕</button>`);
  return btns.join(' ');
}

// ── Carga principal ──────────────────────────────────────

let _ventasData = [];

async function cargarVentas() {
  const estado      = document.getElementById('f-estado-venta').value;
  const desde       = document.getElementById('f-venta-desde').value;
  const sinFact     = document.getElementById('f-sin-facturar')?.checked ? '1' : '';
  const params      = Object.fromEntries(Object.entries({ estado, desde, pendiente_factura: sinFact }).filter(([,v]) => v));
  const qs          = new URLSearchParams(params).toString();

  const [ventas, dash] = await Promise.all([
    api('GET', `/ventas?${qs}&per_page=100`),
    api('GET', '/ventas/dashboard'),
  ]);

  // KPIs generales (opcionales — sólo si existen en el DOM)
  const r = dash.data?.resumen || {};
  const _kpi = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  _kpi('vm-total', r.total_contratos || '0');
  _kpi('vm-conf',  (parseInt(r.confirmados||0) + parseInt(r.en_proceso||0)) || '0');
  _kpi('vm-usd',   r.usd_totales ? fmtUSD(r.usd_totales) : '—');
  _kpi('vm-kg',    r.kg_totales  ? fmt(r.kg_totales, 0) + ' kg' : '—');
  const s = dash.data?.sunat || {};
  _kpi('vm-sunat-fact', s.facturadas           || '0');
  _kpi('vm-sunat-pend', s.pendientes_factura   || '0');

  _ventasData = ventas.data || [];

  // Limpiar buscador al recargar con filtros
  const busq = document.getElementById('f-buscar-venta');
  if (busq) busq.value = '';

  renderHistorialVentas(_ventasData);

  // Poblar select de cotizaciones
  const comps = await api('GET', '/clientes?tipo=comprador&per_page=200');
  const compOpts = (comps.data||[]).map(c => `<option value="${c.id}">${c.razon_social}${c.pais_destino?' ('+c.pais_destino+')':''}</option>`).join('');
  const cotComp = document.getElementById('cot-comprador');
  if (cotComp) cotComp.innerHTML = compOpts;
}

function filtrarHistorialVentas(q) {
  const fil = q.trim().toLowerCase();
  const filtradas = fil
    ? _ventasData.filter(v =>
        (v.numero_contrato || '').toLowerCase().includes(fil) ||
        (v.comprador       || '').toLowerCase().includes(fil) ||
        (v.acopio_codigo     || '').toLowerCase().includes(fil) ||
        (v.variedad        || '').toLowerCase().includes(fil))
    : _ventasData;
  renderHistorialVentas(filtradas);
}

function renderHistorialVentas(rows) {
  const tbody = document.getElementById('tbl-ventas');
  tbody.innerHTML = rows.length ? rows.map(v => `
    <tr>
      <td class="mono fw-bold">${v.numero_contrato}</td>
      <td>${v.comprador}<br><small class="text-muted">${v.pais_destino || ''}</small></td>
      <td class="mono small">${v.acopio_codigo}<br><small class="text-muted">${v.variedad || ''}</small></td>
      <td>${v.fecha_contrato}</td>
      <td class="text-right">${fmt(v.cantidad_kg)} kg</td>
      <td class="text-right">S/ ${parseFloat(v.precio_usd_kg).toFixed(3)}</td>
      <td class="fw-bold text-green text-right">${fmtUSD(v.total_usd)}</td>
      <td><button class="btn btn-sm btn-ghost" onclick="verDetalleVenta(${v.id})">Ver</button></td>
    </tr>
  `).join('') : emptyRow(8, 'Sin resultados');
}

// ── Detalle de venta ─────────────────────────────────────

let _ventaDetalle = null;

async function verDetalleVenta(id) {
  const body = document.getElementById('modal-detalle-venta-body');
  body.innerHTML = '<div class="empty"><span class="empty-icon">⏳</span><p>Cargando...</p></div>';
  toggleForm('detalle-venta');

  const res = await api('GET', `/ventas/${id}`);
  if (!res.success) { body.innerHTML = `<p class="text-muted" style="padding:20px">Error al cargar detalle.</p>`; return; }
  const v = res.data;
  _ventaDetalle = v;

  body.innerHTML = `
    <div class="detalle-grid">
      <div class="detalle-col">
        <div class="detalle-section-title">📄 Datos de la Venta</div>
        <table class="detalle-table">
          <tr><th>Número</th><td class="mono fw-bold">${v.numero_contrato}</td></tr>
          <tr><th>Estado</th><td>${estadoBadge(v.estado)}</td></tr>
          <tr><th>Comprador</th><td>${v.comprador}</td></tr>
          <tr><th>País Destino</th><td>${v.pais_destino || '—'}</td></tr>
          <tr><th>Lote</th><td class="mono">${v.acopio_codigo} — ${v.variedad || ''}</td></tr>
          <tr><th>Productor</th><td>${v.productor}</td></tr>
          <tr><th>Fecha Venta</th><td>${v.fecha_contrato}</td></tr>
          <tr><th>Fecha Entrega</th><td>${v.fecha_entrega || '—'}</td></tr>
          <tr><th>Incoterm</th><td><strong>${v.incoterm}</strong></td></tr>
          <tr><th>Puerto</th><td>${v.puerto_embarque || '—'}</td></tr>
          ${v.notas ? `<tr><th>Notas</th><td>${v.notas}</td></tr>` : ''}
        </table>
      </div>
      <div class="detalle-col">
        <div class="detalle-section-title">💵 Valores Económicos</div>
        <table class="detalle-table">
          <tr><th>Cantidad</th><td class="fw-bold">${fmt(v.cantidad_kg)} kg</td></tr>
          <tr><th>Precio S/ /kg</th><td>S/ ${parseFloat(v.precio_usd_kg).toFixed(3)}</td></tr>
          <tr><th>Total S/</th><td class="fw-bold text-green">${fmtUSD(v.total_usd)}</td></tr>
          <tr><th>Tipo Cambio</th><td>S/ ${parseFloat(v.tipo_cambio).toFixed(4)}</td></tr>
          <tr><th>Total PEN</th><td class="fw-bold">S/ ${fmt(v.total_local, 2)}</td></tr>
          <tr><th>Moneda CPE</th><td>${v.moneda_factura || 'USD'}</td></tr>
          ${v.score_taza  ? `<tr><th>Score Taza</th><td>${v.score_taza} pts — ${v.clasificacion||''}</td></tr>` : ''}
          ${v.humedad_pct ? `<tr><th>Humedad</th><td>${v.humedad_pct}%</td></tr>` : ''}
          ${v.humedad_max_pct ? `<tr><th>Humedad Máx.</th><td>${v.humedad_max_pct}%</td></tr>` : ''}
          ${v.defectos_max    ? `<tr><th>Defectos Máx.</th><td>${v.defectos_max}</td></tr>` : ''}
          ${v.score_min       ? `<tr><th>Score Mín.</th><td>${v.score_min} pts</td></tr>` : ''}
        </table>

      </div>
    </div>
    <div class="detalle-actions">
      ${v.estado === 'borrador' ? `<button class="btn btn-success" onclick="confirmarVenta(${v.id},true)">✓ Confirmar Venta</button>` : ''}
      ${!['cancelado','entregado'].includes(v.estado) ? `<button class="btn btn-ghost" onclick="cancelarVenta(${v.id},true)">✕ Cancelar</button>` : ''}
    </div>
  `;
}

function imprimirDetalleVenta() {
  const v = _ventaDetalle;
  if (!v) return;

  const estadoTexto = (v.estado || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  const html = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Contrato de Venta ${v.numero_contrato}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #111; padding: 24px; }
    .print-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1E3A4A; padding-bottom: 12px; margin-bottom: 16px; }
    .print-logo-name { font-size: 15px; font-weight: 700; color: #1E3A4A; }
    .print-logo-sub  { font-size: 10px; color: #666; margin-top: 2px; }
    .print-doc-title { text-align: right; }
    .print-doc-title h1 { font-size: 13px; font-weight: 700; color: #1E3A4A; }
    .print-doc-num   { font-size: 16px; font-weight: 700; color: #2d7a45; margin-top: 4px; font-family: monospace; }
    .print-estado    { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 10px; font-weight: 700; text-transform: uppercase; background: #d1fae5; color: #065f46; margin-top: 4px; }
    .section-title   { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #1E3A4A; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; margin: 16px 0 8px; }
    .grid-2          { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    table.detail     { width: 100%; border-collapse: collapse; }
    table.detail tr  { border-bottom: 1px solid #f0f0f0; }
    table.detail th  { width: 40%; padding: 4px 6px; text-align: left; color: #666; font-weight: 600; font-size: 10px; }
    table.detail td  { padding: 4px 6px; font-size: 11px; }
    table.detail td.mono { font-family: monospace; font-weight: 700; }
    .total-row       { background: #f0fdf4; border-top: 2px solid #2d7a45 !important; }
    .total-row th    { font-size: 11px; color: #111; }
    .total-row td    { font-size: 14px; font-weight: 700; color: #2d7a45; }
    .print-footer    { margin-top: 32px; border-top: 1px solid #d1d5db; padding-top: 10px; display: flex; justify-content: space-between; color: #999; font-size: 9px; }
    .firma-block     { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    .firma-line      { border-top: 1px solid #333; padding-top: 6px; text-align: center; font-size: 10px; color: #444; }
    @page  { size: A4 portrait; margin: 18mm 15mm; }
    @media print { body { padding: 0; } }
  </style>
</head>
<body>
  <div class="print-header">
    <div>
      <div class="print-logo-name">☕ Sistema de Trazabilidad de Café</div>
      <div class="print-logo-sub">Café de especialidad peruano</div>
    </div>
    <div class="print-doc-title">
      <h1>CONTRATO DE VENTA</h1>
      <div class="print-doc-num">${v.numero_contrato}</div>
      <span class="print-estado">${estadoTexto}</span>
    </div>
  </div>

  <div class="grid-2">
    <div>
      <div class="section-title">Datos de la Venta</div>
      <table class="detail">
        <tr><th>N° Contrato</th><td class="mono">${v.numero_contrato}</td></tr>
        <tr><th>Comprador</th><td>${v.comprador || '—'}</td></tr>
        <tr><th>País Destino</th><td>${v.pais_destino || '—'}</td></tr>
        <tr><th>Productor</th><td>${v.productor || '—'}</td></tr>
        <tr><th>Lote</th><td class="mono">${v.acopio_codigo}${v.variedad ? ' — ' + v.variedad : ''}</td></tr>
        <tr><th>Fecha Venta</th><td>${v.fecha_contrato || '—'}</td></tr>
        <tr><th>Fecha Entrega</th><td>${v.fecha_entrega || '—'}</td></tr>
        <tr><th>Incoterm</th><td><strong>${v.incoterm || '—'}</strong></td></tr>
        <tr><th>Puerto</th><td>${v.puerto_embarque || '—'}</td></tr>
        ${v.notas ? `<tr><th>Notas</th><td>${v.notas}</td></tr>` : ''}
      </table>
    </div>
    <div>
      <div class="section-title">Valores Económicos</div>
      <table class="detail">
        <tr><th>Cantidad</th><td><strong>${parseFloat(v.cantidad_kg || 0).toLocaleString('es-PE', {minimumFractionDigits:3})} kg</strong></td></tr>
        <tr><th>Precio S/ /kg</th><td>S/ ${parseFloat(v.precio_usd_kg || 0).toFixed(3)}</td></tr>
        <tr><th>Tipo de Cambio</th><td>S/ ${parseFloat(v.tipo_cambio || 0).toFixed(4)}</td></tr>
        <tr><th>Moneda CPE</th><td>${v.moneda_factura || 'USD'}</td></tr>
        ${v.score_taza   ? `<tr><th>Score Taza</th><td>${v.score_taza} pts${v.clasificacion ? ' — ' + v.clasificacion : ''}</td></tr>` : ''}
        ${v.humedad_pct  ? `<tr><th>Humedad</th><td>${v.humedad_pct}%</td></tr>` : ''}
        ${v.humedad_max_pct ? `<tr><th>Humedad Máx.</th><td>${v.humedad_max_pct}%</td></tr>` : ''}
        ${v.defectos_max    ? `<tr><th>Defectos Máx.</th><td>${v.defectos_max}</td></tr>` : ''}
        ${v.score_min       ? `<tr><th>Score Mín.</th><td>${v.score_min} pts</td></tr>` : ''}
        <tr class="total-row"><th>TOTAL S/</th><td>S/ ${parseFloat(v.total_usd || 0).toLocaleString('es-PE', {minimumFractionDigits:2, maximumFractionDigits:2})}</td></tr>
      </table>
    </div>
  </div>

  <div class="firma-block">
    <div class="firma-line">Firma del Vendedor</div>
    <div class="firma-line">Firma del Comprador — ${v.comprador || ''}</div>
  </div>

  <div class="print-footer">
    <span>Generado: ${new Date().toLocaleDateString('es-PE', {year:'numeric',month:'long',day:'numeric'})}</span>
    <span>${v.numero_contrato} · Sistema de Trazabilidad de Café</span>
  </div>
</body>
</html>`;

  const win = window.open('', '_blank', 'width=800,height=650');
  win.document.write(html);
  win.document.close();
  win.focus();
  win.print();
}

// ── Form nueva venta — estado y helpers ──────────────────

let _ventaLotes       = [];   // lotes seleccionados en el formulario
let _lotesDisp        = [];   // caché de lotes disponibles
let _compVSearchTimer = null;

async function cargarLotesDisponiblesForm() {
  const el = document.getElementById('v-lotes-disponibles');
  if (!el) return;
  el.innerHTML = '<div class="text-muted small" style="padding:6px 0">Cargando lotes…</div>';
  const data = await api('GET', '/acopios?per_page=200');
  _lotesDisp = (data.data || []).filter(l => l.estado !== 'vendido' && parseFloat(l.peso_actual_kg) > 0);
  renderLotesDisponibles();
}

function renderLotesDisponibles() {
  const el = document.getElementById('v-lotes-disponibles');
  if (!el) return;
  const yaAgregados = new Set(_ventaLotes.map(x => x.acopio_id));
  const disponibles = _lotesDisp.filter(l => !yaAgregados.has(l.id));
  if (!disponibles.length) {
    el.innerHTML = '<div class="text-muted small" style="padding:12px 0;text-align:center">✓ Todos los lotes han sido agregados al carrito.</div>';
    return;
  }

  el.innerHTML = `<div class="shop-shelf">${disponibles.map(l => {
    const score = parseFloat(l.score_taza) || 0;
    const headCls = score >= 80 ? 'sc-specialty' : score >= 75 ? 'sc-premium' : score >= 60 ? 'sc-comercial' : 'sc-none';
    const scoreTxt = score ? fmt(score, 2) + ' pts' : 'Sin análisis';
    const tipo = [l.tipo_cafe, l.variedad].filter(Boolean).join(' / ') || '—';
    const lJson = JSON.stringify(l).replace(/'/g, '&#39;');
    return `
      <div class="shop-card" onclick='agregarLoteVenta(${lJson})'>
        <div class="shop-card-head ${headCls}">
          <span class="shop-card-icon">☕</span>
          <span class="shop-score">${scoreTxt}</span>
        </div>
        <div class="shop-card-body">
          <div class="shop-card-code">${l.codigo}</div>
          <div class="shop-card-tipo">${tipo}</div>
          <div class="shop-card-prod">${l.productor || '—'}</div>
        </div>
        <div class="shop-card-foot">
          <span class="shop-card-stock">${fmt(l.peso_actual_kg, 1)} kg</span>
          <button class="shop-add-btn" onclick='event.stopPropagation();agregarLoteVenta(${lJson})'>+ Agregar</button>
        </div>
      </div>`;
  }).join('')}</div>`;
}

function filtrarShop(q) {
  const el = document.getElementById('v-lotes-disponibles');
  if (!el) return;
  const fil = q.trim().toLowerCase();
  const yaAgregados = new Set(_ventaLotes.map(x => x.acopio_id));
  const base = _lotesDisp.filter(l => !yaAgregados.has(l.id));
  const resultado = fil
    ? base.filter(l =>
        (l.codigo      || '').toLowerCase().includes(fil) ||
        (l.productor   || '').toLowerCase().includes(fil) ||
        (l.variedad    || '').toLowerCase().includes(fil) ||
        (l.tipo_cafe   || '').toLowerCase().includes(fil) ||
        (l.departamento|| '').toLowerCase().includes(fil))
    : base;

  if (!resultado.length) {
    el.innerHTML = `<div class="text-muted small" style="padding:12px 0;text-align:center">Sin resultados para "<strong>${q}</strong>"</div>`;
    return;
  }

  const headCls = l => {
    const s = parseFloat(l.score_taza) || 0;
    return s >= 80 ? 'sc-specialty' : s >= 75 ? 'sc-premium' : s >= 60 ? 'sc-comercial' : 'sc-none';
  };

  el.innerHTML = `<div class="shop-shelf">${resultado.map(l => {
    const score = parseFloat(l.score_taza) || 0;
    const scoreTxt = score ? fmt(score, 2) + ' pts' : 'Sin análisis';
    const tipo = [l.tipo_cafe, l.variedad].filter(Boolean).join(' / ') || '—';
    const lJson = JSON.stringify(l).replace(/'/g, '&#39;');
    return `
      <div class="shop-card" onclick='agregarLoteVenta(${lJson})'>
        <div class="shop-card-head ${headCls(l)}">
          <span class="shop-card-icon">☕</span>
          <span class="shop-score">${scoreTxt}</span>
        </div>
        <div class="shop-card-body">
          <div class="shop-card-code">${l.codigo}</div>
          <div class="shop-card-tipo">${tipo}</div>
          <div class="shop-card-prod">${l.productor || '—'}</div>
        </div>
        <div class="shop-card-foot">
          <span class="shop-card-stock">${fmt(l.peso_actual_kg, 1)} kg</span>
          <button class="shop-add-btn" onclick='event.stopPropagation();agregarLoteVenta(${lJson})'>+ Agregar</button>
        </div>
      </div>`;
  }).join('')}</div>`;
}

function agregarLoteVenta(l) {
  if (_ventaLotes.find(x => x.acopio_id === l.id)) return;
  _ventaLotes.push({
    acopio_id: l.id,
    codigo:    l.codigo,
    tipo_cafe: l.tipo_cafe || '',
    variedad:  l.variedad  || '',
    stock:     parseFloat(l.peso_actual_kg) || 0,
    cantidad:  parseFloat(l.peso_actual_kg) || 0,
    precio:    0,
  });
  renderLotesDisponibles();
  renderLotesVenta();
}

function quitarLoteVenta(idx) {
  _ventaLotes.splice(idx, 1);
  renderLotesDisponibles();
  renderLotesVenta();
}

function renderLotesVenta() {
  const el  = document.getElementById('v-lotes-seleccionados');
  const tot = document.getElementById('v-resumen-total');
  if (tot) tot.style.display = 'none'; // el total vive dentro del cart-wrap

  const count = _ventaLotes.length;

  el.innerHTML = `
    <div class="cart-wrap">
      <div class="cart-header">
        🛒 Carrito de Venta
        <span class="cart-count">${count} lote${count !== 1 ? 's' : ''}</span>
      </div>
      ${!count
        ? `<div class="cart-empty">Selecciona un lote del estante para agregarlo</div>`
        : `<table class="cart-table">
            <thead>
              <tr>
                <th>Lote</th>
                <th>Tipo</th>
                <th>Variedad</th>
                <th class="text-right">Stock</th>
                <th class="text-right">Kg a vender</th>
                <th class="text-right">S/ /kg</th>
                <th class="text-right">Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              ${_ventaLotes.map((l, i) => `
              <tr>
                <td class="mono fw-bold">${l.codigo}</td>
                <td class="small">${l.tipo_cafe || '—'}</td>
                <td class="small">${l.variedad || '—'}</td>
                <td class="text-right" style="color:var(--verde);font-weight:700">${fmt(l.stock, 1)} kg</td>
                <td class="text-right">
                  <input class="cart-input-inline" type="number" value="${l.cantidad || ''}" step="0.001" min="0.001"
                         onchange="_ventaLotes[${i}].cantidad=parseFloat(this.value)||0;actualizarTotalVenta()">
                </td>
                <td class="text-right">
                  <input class="cart-input-inline" type="number" value="${l.precio || ''}" step="0.0001" placeholder="0.0000"
                         onchange="_ventaLotes[${i}].precio=parseFloat(this.value)||0;actualizarTotalVenta()">
                </td>
                <td class="text-right fw-bold" id="vsub-${i}">—</td>
                <td class="text-center">
                  <button class="cart-remove-btn" onclick="quitarLoteVenta(${i})" title="Quitar lote">🗑</button>
                </td>
              </tr>`).join('')}
            </tbody>
          </table>
          <div class="cart-total-bar">
            <span class="cart-total-label">TOTAL</span>
            <span class="cart-total-value" id="v-total-usd">—</span>
          </div>`}
    </div>`;

  if (count) actualizarTotalVenta();
}

function actualizarTotalVenta() {
  let total = 0;
  _ventaLotes.forEach((l, i) => {
    const sub = (l.cantidad || 0) * (l.precio || 0);
    total += sub;
    const cel = document.getElementById(`vsub-${i}`);
    if (cel) cel.textContent = sub ? fmtUSD(sub) : '—';
  });
  const totEl = document.getElementById('v-total-usd');
  if (totEl) totEl.textContent = total ? fmtUSD(total) : '—';
}

async function buscarCompradorVenta(campo, val) {
  clearTimeout(_compVSearchTimer);
  const resId = campo === 'ruc' ? 'v-comp-results-ruc' : 'v-comp-results-nombre';
  const otroId = campo === 'ruc' ? 'v-comp-results-nombre' : 'v-comp-results-ruc';
  const resEl  = document.getElementById(resId);
  const nfEl   = document.getElementById('v-comp-notfound');

  // Ocultar el dropdown del otro campo
  const otroEl = document.getElementById(otroId);
  if (otroEl) otroEl.style.display = 'none';
  nfEl.style.display = 'none';

  if (!val || val.trim().length < 2) { resEl.style.display = 'none'; return; }

  _compVSearchTimer = setTimeout(async () => {
    const data = await api('GET', `/clientes?search=${encodeURIComponent(val.trim())}&per_page=10`);
    const rows = data.data || [];
    if (!rows.length) { resEl.style.display = 'none'; nfEl.style.display = ''; return; }
    resEl.innerHTML = rows.map(c => `
      <div class="prod-result-item" onclick='seleccionarCompradorVenta(${JSON.stringify(c)})'>
        <strong>${c.razon_social}</strong>
        <span class="small text-muted"> · ${c.ruc_dni || '—'}${c.pais_destino ? ' · ' + c.pais_destino : ''}</span>
      </div>`).join('');
    resEl.style.display = 'block';
  }, 250);
}

async function buscarCompradorBtn() {
  const rucVal = document.getElementById('v-comp-ruc').value.trim();
  const nomVal = document.getElementById('v-comp-nombre').value.trim();
  const val    = rucVal || nomVal;
  const resEl  = document.getElementById('v-comp-results-ruc');
  const nfEl   = document.getElementById('v-comp-notfound');

  clearTimeout(_compVSearchTimer);
  document.getElementById('v-comp-results-nombre').style.display = 'none';
  nfEl.style.display = 'none';

  const url = val.length >= 2
    ? `/clientes?search=${encodeURIComponent(val)}&per_page=30`
    : `/clientes?per_page=50`;

  const data = await api('GET', url);
  const rows = (data.data || []).filter(c => ['comprador', 'ambos'].includes(c.tipo));

  if (!rows.length) { resEl.style.display = 'none'; nfEl.style.display = ''; return; }

  resEl.innerHTML = rows.map(c => `
    <div class="prod-result-item" onclick='seleccionarCompradorVenta(${JSON.stringify(c)})'>
      <strong>${c.razon_social}</strong>
      <span class="small text-muted"> · ${c.ruc_dni || '—'}${c.pais_destino ? ' · ' + c.pais_destino : ''}</span>
    </div>`).join('');
  resEl.style.display = 'block';
}

function ocultarCompResults() {
  setTimeout(() => {
    ['v-comp-results-nombre', 'v-comp-results-ruc'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = 'none';
    });
  }, 200);
}

function seleccionarCompradorVenta(c) {
  document.getElementById('v-comprador-id').value = c.id;
  document.getElementById('v-comp-nombre').value  = c.razon_social || '';
  document.getElementById('v-comp-ruc').value     = c.ruc_dni || '';
  ['v-comp-results-nombre', 'v-comp-results-ruc'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  });
  document.getElementById('v-comp-notfound').style.display = 'none';
  document.getElementById('v-comp-selected').style.display = 'none';
}

function limpiarCompradorVenta() {
  document.getElementById('v-comprador-id').value = '';
  document.getElementById('v-comp-nombre').value  = '';
  document.getElementById('v-comp-ruc').value     = '';
  ['v-comp-results-nombre', 'v-comp-results-ruc'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.innerHTML = ''; el.style.display = 'none'; }
  });
  document.getElementById('v-comp-notfound').style.display = 'none';
}

async function consultarRucSunat() {
  const input  = document.getElementById('v-comp-ruc');
  const numero = (input?.value || '').replace(/\D/g, '').trim();
  if (!numero || (numero.length !== 8 && numero.length !== 11)) {
    toast('Ingresa un DNI (8 dígitos) o RUC (11 dígitos) para consultar en SUNAT', true);
    return;
  }

  const btn  = document.getElementById('btn-consulta-ruc');
  const icon = document.getElementById('btn-ruc-icon');
  btn.disabled = true;
  icon.textContent = '⏳';

  try {
    const data = await api('GET', `/sunat/consulta-ruc?numero=${numero}`);

    // Rellenar campos de búsqueda
    const nombreInput = document.getElementById('v-comp-nombre');
    if (nombreInput) nombreInput.value = data.razon_social || '';
    if (input) input.value = data.ruc || numero;

    // Ocultar "no encontrado" y dropdowns
    document.getElementById('v-comp-notfound').style.display = 'none';
    ['v-comp-results-nombre', 'v-comp-results-ruc'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.display = 'none';
    });

    // Guardar datos temporales para poder crear el cliente
    window._sunatClienteTemp = {
      razon_social: data.razon_social || '',
      ruc:          data.ruc || numero,
      estado:       data.estado || '',
      condicion:    data.condicion || '',
      direccion:    data.direccion || '',
    };

    const estadoBadge = data.estado === 'ACTIVO'
      ? '<span class="badge badge-verde">ACTIVO</span>'
      : `<span class="badge badge-rojo">${data.estado || '—'}</span>`;
    const condBadge = data.condicion === 'HABIDO'
      ? '<span class="badge badge-verde">HABIDO</span>'
      : `<span class="badge badge-oro">${data.condicion || '—'}</span>`;

    const card = document.getElementById('v-comp-sunat-card');
    card.innerHTML = `
      <div class="sunat-ruc-header">
        <span class="sunat-ruc-icon">🏢</span>
        <div class="sunat-ruc-body">
          <div class="sunat-ruc-name">${data.razon_social || '—'}</div>
          <div class="sunat-ruc-meta">
            <span class="mono">${data.ruc || numero}</span>
            ${estadoBadge} ${condBadge}
          </div>
          ${data.direccion ? `<div class="sunat-ruc-dir">${data.direccion}</div>` : ''}
        </div>
      </div>
      <div class="sunat-ruc-actions">
        <button type="button" class="btn btn-sm btn-success" onclick="usarClienteSunat()">✓ Registrar y usar</button>
        <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('v-comp-sunat-card').style.display='none'">✕</button>
      </div>`;
    card.style.display = '';

  } catch (e) {
    toast('Error al consultar SUNAT: ' + (e.message || 'Servicio no disponible'), true);
  } finally {
    btn.disabled = false;
    icon.textContent = '🔎';
  }
}

async function usarClienteSunat() {
  const d = window._sunatClienteTemp;
  if (!d?.razon_social) { toast('Sin datos de SUNAT para registrar', true); return; }

  const res = await api('POST', '/clientes', {
    tipo:         'comprador',
    razon_social: d.razon_social,
    ruc_dni:      d.ruc,
    direccion:    d.direccion || '',
  });

  if (!res.success) {
    // Si ya existe (unicidad de RUC), buscarlo y seleccionarlo
    const busq = await api('GET', `/clientes?search=${encodeURIComponent(d.ruc)}&per_page=1`);
    const found = (busq.data || [])[0];
    if (found) {
      seleccionarCompradorVenta(found);
      document.getElementById('v-comp-sunat-card').style.display = 'none';
      toast('Comprador seleccionado desde la base de datos');
      return;
    }
    toast(res.error || 'Error al registrar el cliente', true);
    return;
  }

  seleccionarCompradorVenta(res.data);
  document.getElementById('v-comp-sunat-card').style.display = 'none';
  toast(`${d.razon_social} registrado y seleccionado`);
}

// ── Transiciones de estado ───────────────────────────────

async function guardarVenta() {
  const compradorId = document.getElementById('v-comprador-id').value;
  const fecha       = document.getElementById('v-fecha').value;
  const incoterm    = document.getElementById('v-incoterm').value;
  const cpeTipo     = document.getElementById('v-cpe-tipo')?.value || '';
  const tasa        = parseFloat(localStorage.getItem('tasa_usd')) || 3.75;

  if (!compradorId)        { toast('Selecciona un comprador', true); return; }
  if (!_ventaLotes.length) { toast('Agrega al menos un lote', true); return; }
  if (!fecha)              { toast('Ingresa la fecha de venta', true); return; }
  for (const l of _ventaLotes) {
    if (!l.cantidad || l.cantidad <= 0) { toast(`Cantidad inválida para lote ${l.codigo}`, true); return; }
    if (!l.precio   || l.precio   <= 0) { toast(`Precio inválido para lote ${l.codigo}`, true); return; }
  }

  let creadas = [], errores = [], cpeOk = [], cpeErr = [];

  for (const l of _ventaLotes) {
    // 1. Crear la venta
    const res = await api('POST', '/ventas', {
      comprador_id:   parseInt(compradorId),
      acopio_id:      l.acopio_id,
      fecha_contrato: fecha,
      cantidad_kg:    l.cantidad,
      precio_usd_kg:  l.precio,
      tipo_cambio:    tasa,
      incoterm,
    });
    if (!res.success) { errores.push(`${l.codigo}: ${res.error || 'Error'}`); continue; }
    const ventaId = res.data.id;
    creadas.push(res.data.numero_contrato);

  }

  if (creadas.length) toast(`Venta${creadas.length > 1 ? 's' : ''} creada${creadas.length > 1 ? 's' : ''}: ${creadas.join(', ')}`);
  if (cpeOk.length)   toast(cpeOk.join(', ') + ' emitido' + (cpeOk.length > 1 ? 's' : '') + ' en SUNAT');
  if (cpeErr.length)  toast(cpeErr.join(' | '), true);
  if (errores.length) toast(errores.join(' | '), true);
  if (creadas.length) { abrirFormVenta(); cargarVentas(); cargarLotesDisponiblesForm(); }
}

async function confirmarVenta(id, desdeDetalle = false) {
  if (!confirm('¿Confirmar la venta? Se descontará el stock del lote.')) return;
  const res = await api('PUT', `/ventas/${id}/confirmar`);
  if (res.success) { toast('Venta confirmada — stock descontado'); cargarVentas(); if (desdeDetalle) verDetalleVenta(id); }
  else toast(res.error || 'Error al confirmar', true);
}

async function iniciarProceso(id, desdeDetalle = false) {
  if (!confirm('¿Pasar la venta a estado "En Proceso"?')) return;
  const res = await api('PUT', `/ventas/${id}/en_proceso`);
  if (res.success) { toast('Venta en proceso'); cargarVentas(); if (desdeDetalle) verDetalleVenta(id); }
  else toast(res.error || 'Error', true);
}

async function entregarVenta(id, desdeDetalle = false) {
  if (!confirm('¿Marcar la venta como entregada al comprador?')) return;
  const res = await api('PUT', `/ventas/${id}/entregar`);
  if (res.success) { toast('Venta marcada como entregada'); cargarVentas(); if (desdeDetalle) verDetalleVenta(id); }
  else toast(res.error || 'Error al entregar', true);
}

async function cancelarVenta(id, desdeDetalle = false) {
  const razon = prompt('Motivo de cancelación:');
  if (razon === null) return;
  const res = await api('PUT', `/ventas/${id}/cancelar`, { razon });
  if (res.success) { toast('Venta cancelada'); if (desdeDetalle) toggleForm('detalle-venta'); cargarVentas(); }
  else toast(res.error || 'Error al cancelar', true);
}

// ── SUNAT ────────────────────────────────────────────────

async function emitirFactura(id, desdeDetalle = false) {
  if (!confirm('¿Emitir Factura Electrónica a SUNAT?')) return;
  const res = await api('POST', `/ventas/${id}/facturar`);
  if (res.success) {
    const d = res.data || {};
    toast(`Factura ${d.serie||''}-${d.numero||''} emitida • Estado: ${d.estado||''}`);
    cargarVentas(); if (desdeDetalle) verDetalleVenta(id);
  } else toast(res.error || 'Error al emitir factura', true);
}

async function emitirBoleta(id, desdeDetalle = false) {
  if (!confirm('¿Emitir Boleta Electrónica a SUNAT?')) return;
  const res = await api('POST', `/ventas/${id}/boleta`);
  if (res.success) {
    const d = res.data || {};
    toast(`Boleta ${d.serie||''}-${d.numero||''} emitida • Estado: ${d.estado||''}`);
    cargarVentas(); if (desdeDetalle) verDetalleVenta(id);
  } else toast(res.error || 'Error al emitir boleta', true);
}

async function consultarCpe(id, desdeDetalle = false) {
  const res = await api('GET', `/sunat/cpe/${id}`);
  if (res.success) {
    const d = res.data || {};
    toast(`CPE: ${d.sunat_tipo||''} ${d.sunat_serie||''}-${d.sunat_numero||''} — ${d.sunat_estado||''}`);
    cargarVentas(); if (desdeDetalle) verDetalleVenta(id);
  } else toast(res.error || 'Error al consultar CPE', true);
}

async function anularCpe(id, desdeDetalle = false) {
  if (!confirm('¿Anular el comprobante? Se marcará como anulado en el sistema local.')) return;
  const res = await api('DELETE', `/sunat/cpe/${id}`);
  if (res.success) { toast('Comprobante anulado'); cargarVentas(); if (desdeDetalle) verDetalleVenta(id); }
  else toast(res.error || 'Error al anular', true);
}

// ── Panel SUNAT (tab) ─────────────────────────────────────

async function cargarPanelSunat() {
  const [dash, pendientes, emitidos] = await Promise.all([
    api('GET', '/ventas/dashboard'),
    api('GET', '/ventas?pendiente_factura=1&per_page=100'),
    api('GET', '/ventas?sunat_estado=aceptado&per_page=50'),
  ]);

  const s = dash.data?.sunat || {};
  document.getElementById('sunat-aceptadas').textContent = s.aceptadas_sunat      || '0';
  document.getElementById('sunat-usd-fact').textContent  = s.usd_facturado_aceptado ? fmtUSD(s.usd_facturado_aceptado) : '—';
  document.getElementById('sunat-problemas').textContent = s.con_problemas_sunat  || '0';
  document.getElementById('sunat-pendientes').textContent= s.pendientes_factura   || '0';

  // Tabla: pendientes de facturar
  const tPend = document.getElementById('tbl-sunat-pendientes');
  const rPend = pendientes.data || [];
  tPend.innerHTML = rPend.length ? rPend.map(v => `
    <tr>
      <td class="mono fw-bold">${v.numero_contrato}</td>
      <td>${v.comprador}</td>
      <td class="mono small">${v.acopio_codigo}</td>
      <td>${v.fecha_contrato}</td>
      <td class="fw-bold text-green">${fmtUSD(v.total_usd)}</td>
      <td>${estadoBadge(v.estado)}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-xs" style="background:#dc2626;color:#fff" onclick="emitirFactura(${v.id})">🧾 Factura</button>
        <button class="btn btn-xs" style="background:#7c3aed;color:#fff" onclick="emitirBoleta(${v.id})">📄 Boleta</button>
      </td>
    </tr>
  `).join('') : emptyRow(7, 'No hay ventas pendientes de facturar');

  // Tabla: CPE emitidos
  const res2 = await api('GET', '/ventas?per_page=100');
  const tCpe  = document.getElementById('tbl-sunat-cpe');
  const rCpe  = (res2.data || []).filter(v => v.sunat_documento_id);
  tCpe.innerHTML = rCpe.length ? rCpe.map(v => `
    <tr>
      <td class="mono fw-bold">${v.numero_contrato}</td>
      <td>${v.comprador}</td>
      <td>${v.sunat_tipo ? `<span class="badge badge-${v.sunat_tipo}">${v.sunat_tipo.toUpperCase()}</span>` : '—'}</td>
      <td class="mono">${v.sunat_serie || ''}${v.sunat_numero ? '-'+v.sunat_numero : ''}</td>
      <td>${v.sunat_emitido_en ? v.sunat_emitido_en.substring(0,10) : '—'}</td>
      <td><span class="badge badge-${v.sunat_estado||'pendiente'}">${v.sunat_estado||'pendiente'}</span></td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick="consultarCpe(${v.id})">🔄</button>
        <button class="btn btn-ghost btn-xs" onclick="verDetalleVenta(${v.id})">👁</button>
      </td>
    </tr>
  `).join('') : emptyRow(7, 'No hay comprobantes emitidos');
}

// ── Cotizaciones ─────────────────────────────────────────

function abrirFormCotizacion() {
  const hoy    = new Date().toISOString().split('T')[0];
  const vence  = new Date(Date.now() + 15*864e5).toISOString().split('T')[0];
  document.getElementById('cot-fecha').value = hoy;
  document.getElementById('cot-vence').value = vence;
  // Poblar lotes si no están cargados
  const sel = document.getElementById('cot-lote');
  if (!sel.options.length) cargarAcopiosCot();
  toggleForm('form-cotizacion');
}

async function cargarAcopiosCot() {
  const res = await api('GET', '/acopios?per_page=200');
  const sel = document.getElementById('cot-lote');
  if (!sel) return;
  sel.innerHTML = (res.data||[]).filter(l => l.estado !== 'vendido')
    .map(l => `<option value="${l.id}" data-stock="${l.peso_actual_kg}">${l.codigo} — ${l.variedad||''} (${fmt(l.peso_actual_kg)} kg)</option>`).join('');
}

function calcularTotalCot() {
  const qty   = parseFloat(document.getElementById('cot-cantidad').value) || 0;
  const price = parseFloat(document.getElementById('cot-precio').value)   || 0;
  const el    = document.getElementById('cot-calculo');
  if (qty > 0 && price > 0) {
    el.style.display = 'block';
    document.getElementById('cot-total-usd').textContent = fmtUSD(qty * price);
  } else { el.style.display = 'none'; }
}

async function cargarCotizaciones() {
  const estado = document.getElementById('f-estado-cot')?.value || '';
  const qs     = estado ? `estado=${estado}&` : '';
  const res    = await api('GET', `/ventas/cotizaciones?${qs}per_page=100`);
  const tbody  = document.getElementById('tbl-cotizaciones');
  if (!tbody) return;
  const rows = res.data || [];
  tbody.innerHTML = rows.length ? rows.map(c => `
    <tr>
      <td class="mono fw-bold">${c.numero}</td>
      <td>${c.comprador || '—'}</td>
      <td class="mono small">${c.acopio_codigo || '—'}</td>
      <td>${c.fecha_cotizacion}</td>
      <td>${c.fecha_vencimiento}</td>
      <td class="text-right">${fmt(c.cantidad_kg)} kg</td>
      <td class="text-right">S/ ${parseFloat(c.precio_usd_kg).toFixed(3)}</td>
      <td class="fw-bold text-green text-right">${fmtUSD(c.total_usd)}</td>
      <td>${estadoBadge(c.estado)}</td>
      <td style="white-space:nowrap">
        ${c.estado === 'borrador' ? `<button class="btn btn-primary btn-xs" onclick="enviarCotizacion(${c.id})">📤 Enviar</button>` : ''}
        ${c.estado === 'enviada'  ? `
          <button class="btn btn-success btn-xs" onclick="convertirCotizacion(${c.id})">✓ A Venta</button>
          <button class="btn btn-ghost btn-xs"   onclick="rechazarCotizacion(${c.id})">✕</button>` : ''}
      </td>
    </tr>
  `).join('') : emptyRow(10, 'Sin cotizaciones registradas');
}

async function guardarCotizacion() {
  const data = {
    comprador_id:      document.getElementById('cot-comprador').value,
    acopio_id:         document.getElementById('cot-lote').value,
    fecha_cotizacion:  document.getElementById('cot-fecha').value,
    fecha_vencimiento: document.getElementById('cot-vence').value,
    cantidad_kg:       parseFloat(document.getElementById('cot-cantidad').value),
    precio_usd_kg:     parseFloat(document.getElementById('cot-precio').value),
    incoterm:          document.getElementById('cot-incoterm').value,
    condiciones:       document.getElementById('cot-condiciones').value || null,
  };
  const res = await api('POST', '/ventas/cotizaciones', data);
  if (res.success) { toast('Cotización ' + res.data.numero + ' creada'); cargarCotizaciones(); toggleForm('form-cotizacion'); }
  else toast(res.error || 'Error al crear cotización', true);
}

async function enviarCotizacion(id) {
  const res = await api('PUT', `/ventas/cotizaciones/${id}/enviar`);
  if (res.success) { toast('Cotización enviada al comprador'); cargarCotizaciones(); }
  else toast(res.error || 'Error', true);
}

async function convertirCotizacion(id) {
  if (!confirm('¿Convertir esta cotización en venta?')) return;
  const res = await api('PUT', `/ventas/cotizaciones/${id}/convertir`);
  if (res.success) {
    toast('Cotización convertida: ' + (res.data?.numero_contrato || ''));
    mostrarVentaTab('vt-contratos', document.querySelector('#ventas .tab-btn'));
    cargarVentas();
  } else toast(res.error || 'Error al convertir', true);
}

async function rechazarCotizacion(id) {
  if (!confirm('¿Rechazar la cotización?')) return;
  const res = await api('PUT', `/ventas/cotizaciones/${id}/rechazar`);
  if (res.success) { toast('Cotización rechazada'); cargarCotizaciones(); }
  else toast(res.error || 'Error', true);
}

/* ══════════════════════════════════════════════════════════
   PRODUCCIÓN
══════════════════════════════════════════════════════════ */
async function cargarLotsSelectOT() {
  const res = await api('GET', '/acopios?per_page=200');
  const sel = document.getElementById('ot-lote');
  if (!sel) return;
  const rows = (res.data||[]).filter(l => l.estado !== 'vendido');
  sel.innerHTML = rows.map(l => `<option value="${l.id}">${l.codigo} — ${l.productor} (${fmt(l.peso_actual_kg)} kg)</option>`).join('');
}

async function cargarOTs() {
  const estado = document.getElementById('f-ot-estado').value;
  const qs = estado ? `?estado=${estado}&per_page=100` : '?per_page=100';
  const res = await api('GET', `/produccion/ordenes-trabajo${qs}`);
  const rows = res.data || [];

  const pend = rows.filter(r => r.estado === 'pendiente').length;
  const enP  = rows.filter(r => r.estado === 'en_proceso').length;
  const comp = rows.filter(r => r.estado === 'completada').length;
  const avgs = rows.filter(r => r.estado === 'en_proceso').map(r => parseFloat(r.avance_pct||0));
  const avgAvance = avgs.length ? (avgs.reduce((a,b)=>a+b,0)/avgs.length).toFixed(1) : null;

  document.getElementById('prod-pendientes').textContent  = pend;
  document.getElementById('prod-en-proceso').textContent  = enP;
  document.getElementById('prod-completadas').textContent = comp;
  document.getElementById('prod-avance').textContent      = avgAvance ? avgAvance + '%' : '—';

  const colores = {
    pendiente: 'var(--warn)', en_proceso: 'var(--info)',
    completada: 'var(--verde)', pausada: '#888', cancelada: 'var(--danger)',
  };
  const tbody = document.getElementById('tbl-ot');
  tbody.innerHTML = rows.length ? rows.map(o => `
    <tr>
      <td class="mono fw-bold">${o.numero}</td>
      <td class="mono">${o.acopio_codigo}</td>
      <td>${o.tipo_proceso}</td>
      <td>${o.fecha_inicio}</td>
      <td>${o.fecha_fin_estimada || '—'}</td>
      <td>${o.operador || '—'}</td>
      <td>
        <div class="d-flex align-center gap-8">
          <div class="progress-bar-wrap">
            <div class="progress-bar" style="width:${o.avance_pct||0}%; background:${colores[o.estado]||'var(--cafe)'}"></div>
          </div>
          <span class="small fw-bold">${o.avance_pct||0}%</span>
        </div>
      </td>
      <td>${estadoBadge(o.estado)}</td>
      <td style="white-space:nowrap">
        ${o.estado !== 'completada' && o.estado !== 'cancelada' ? `
          <button class="btn btn-ghost btn-xs" onclick="actualizarAvanceOT(${o.id}, ${o.avance_pct||0})">📊 Avance</button>
        ` : ''}
      </td>
    </tr>
  `).join('') : emptyRow(9, 'Sin órdenes de trabajo');
}

async function guardarOT() {
  const data = {
    acopio_id:          document.getElementById('ot-lote').value,
    tipo_proceso:       document.getElementById('ot-tipo').value,
    fecha_inicio:       document.getElementById('ot-fecha-ini').value,
    fecha_fin_estimada: document.getElementById('ot-fecha-fin').value || null,
    operador:           document.getElementById('ot-operador').value || null,
    maquinaria:         document.getElementById('ot-maquinaria').value || null,
  };
  const res = await api('POST', '/produccion/ordenes-trabajo', data);
  if (res.success) { toast('OT ' + res.data.numero + ' creada'); cargarOTs(); toggleForm('form-ot'); }
  else toast(res.error || 'Error al crear OT', true);
}

async function actualizarAvanceOT(id, actual) {
  const nuevo = prompt(`Avance actual: ${actual}%\nIngrese nuevo avance (0-100):`, actual);
  if (nuevo === null) return;
  const pct = parseFloat(nuevo);
  if (isNaN(pct) || pct < 0 || pct > 100) { toast('Porcentaje inválido', true); return; }
  const res = await api('PUT', `/produccion/ordenes-trabajo/${id}/avance`, { avance_pct: pct });
  if (res.success) { toast(`Avance actualizado: ${pct}%`); cargarOTs(); }
  else toast(res.error || 'Error', true);
}

/* ══════════════════════════════════════════════════════════
   STOCK / ALMACÉN
══════════════════════════════════════════════════════════ */
async function cargarStock() {
  const estado = document.getElementById('f-stock-estado').value;
  const qs = estado ? `?estado=${estado}&per_page=200` : '?per_page=200';
  const stock = await api('GET', `/inventario/stock${qs}`);

  const rows = stock.data || [];

  // KPIs calculados desde las filas cargadas (evita dependencia del filtro de campaña del endpoint resumen)
  const totalKg      = rows.reduce((s,r) => s + parseFloat(r.stock_actual_kg||0), 0);
  const comprometido = rows.reduce((s,r) => s + parseFloat(r.comprometido_kg||0), 0);
  const libre        = rows.reduce((s,r) => s + parseFloat(r.stock_libre_kg||0), 0);

  document.getElementById('st-total-kg').textContent     = fmt(totalKg, 0) + ' kg';
  document.getElementById('st-lotes').textContent        = rows.length || '0';
  document.getElementById('st-comprometido').textContent = fmt(comprometido, 0) + ' kg';
  document.getElementById('st-libre').textContent        = fmt(libre, 0) + ' kg';

  const tbody = document.getElementById('tbl-stock');
  tbody.innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td class="mono fw-bold">${r.codigo}</td>
      <td>${r.tipo_cafe}</td>
      <td>${r.productor}</td>
      <td>${r.variedad || '—'}</td>
      <td class="fw-bold">${fmt(r.stock_actual_kg)} kg</td>
      <td class="text-warn">${fmt(r.comprometido_kg)} kg</td>
      <td class="text-green fw-bold">${fmt(r.stock_libre_kg)} kg</td>
      <td class="fw-bold" style="color:${r.score_taza>=80?'var(--verde)':r.score_taza>=60?'var(--warn)':'var(--text-muted)'}">
        ${r.score_taza||'—'}
      </td>
      <td>${estadoBadge(r.estado)}</td>
    </tr>
  `).join('') : emptyRow(9, 'Sin stock registrado');
}

async function cargarValorizacion() {
  const metodo = document.getElementById('f-val-metodo').value;
  const res = await api('GET', `/inventario/valorizacion?metodo=${metodo}`);
  const d   = res.data || {};
  const lotes = d.lotes || [];

  document.getElementById('val-total').textContent = 'S/ ' + fmt(d.total_valorizado, 2);

  const tbody = document.getElementById('tbl-valorizacion');
  tbody.innerHTML = lotes.length ? lotes.map(l => `
    <tr>
      <td class="mono fw-bold">${l.codigo}</td>
      <td>${l.tipo_cafe}</td>
      <td>${l.productor}</td>
      <td>${fmt(l.stock_actual_kg)} kg</td>
      <td>${l.costo_unitario_prom ? fmt(l.costo_unitario_prom, 4) : '—'}</td>
      <td class="fw-bold">S/ ${fmt(l.valorizacion_pen || l.valorizacion, 2)}</td>
      <td class="small">${l.moneda || 'PEN'}</td>
    </tr>
  `).join('') : emptyRow(7, 'Sin datos de valorización');
}

/* ══════════════════════════════════════════════════════════
   COMPRAS — Tab navigation
══════════════════════════════════════════════════════════ */
function mostrarTab(tabId, btn) {
  document.querySelectorAll('.compras-tab').forEach(t => t.style.display = 'none');
  const target = document.getElementById(tabId);
  if (target) target.style.display = 'block';

  if (btn) {
    btn.closest('.tab-nav').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }

  if (tabId === 'tab-proveedores') cargarProveedores();
  if (tabId === 'tab-oc')          { cargarOC(); cargarProveedoresSelect(); }
  if (tabId === 'tab-cxp')         { cargarCxP(); cargarProveedoresSelectCxP(); }
}

async function cargarProveedores() {
  const res   = await api('GET', '/compras/proveedores?per_page=100');
  const tbody = document.getElementById('tbl-proveedores');
  const rows  = res.data || [];
  tbody.innerHTML = rows.length ? rows.map(p => `
    <tr>
      <td class="small text-muted">${p.id}</td>
      <td><strong>${p.razon_social}</strong></td>
      <td class="mono small">${p.ruc || '—'}</td>
      <td>${tipoBadge(p.categoria)}</td>
      <td>${p.telefono || '—'}</td>
      <td>${p.condiciones_pago || '—'}</td>
      <td class="fw-bold text-red">${p.deuda_pendiente ? 'S/ ' + fmt(p.deuda_pendiente, 2) : '—'}</td>
      <td><button class="btn-icon" onclick="editarProveedor(${p.id})" title="Editar">✏️</button></td>
    </tr>
  `).join('') : emptyRow(8);
}

let _provEditId = null;

function abrirFormProveedor() {
  _provEditId = null;
  ['pv-nombre','pv-ruc','pv-tel','pv-email','pv-cond'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  document.getElementById('pv-cat').selectedIndex = 0;
  document.getElementById('prov-modal-title').textContent = '👷 Registrar Proveedor';
  document.getElementById('btn-guardar-prov').textContent = '💾 Guardar';
  toggleForm('form-prov');
}

async function editarProveedor(id) {
  const res = await api('GET', `/compras/proveedores/${id}`);
  const p = res.data;
  if (!p) { toast('No se pudo cargar el proveedor', true); return; }

  _provEditId = id;
  document.getElementById('pv-nombre').value = p.razon_social || '';
  document.getElementById('pv-ruc').value    = p.ruc          || '';
  document.getElementById('pv-tel').value    = p.telefono     || '';
  document.getElementById('pv-email').value  = p.email        || '';
  document.getElementById('pv-cond').value   = p.condiciones_pago || '';
  document.getElementById('pv-cat').value    = p.categoria    || 'insumos';

  document.getElementById('prov-modal-title').textContent = '✏️ Editar Proveedor';
  document.getElementById('btn-guardar-prov').textContent = '💾 Actualizar';
  toggleForm('form-prov');
}

async function guardarProveedor() {
  const nombre = document.getElementById('pv-nombre').value.trim();
  if (!nombre) { toast('La razón social es obligatoria', true); return; }

  const data = {
    razon_social:     nombre,
    ruc:              document.getElementById('pv-ruc').value   || null,
    categoria:        document.getElementById('pv-cat').value,
    telefono:         document.getElementById('pv-tel').value   || null,
    email:            document.getElementById('pv-email').value || null,
    condiciones_pago: document.getElementById('pv-cond').value  || null,
  };

  const res = _provEditId
    ? await api('PUT',  `/compras/proveedores/${_provEditId}`, data)
    : await api('POST', '/compras/proveedores', data);

  if (res.success) {
    toast(_provEditId ? 'Proveedor actualizado' : 'Proveedor registrado');
    _provEditId = null;
    cargarProveedores();
    toggleForm('form-prov');
  } else {
    toast(res.error || 'Error', true);
  }
}

async function cargarProveedoresSelect() {
  const res = await api('GET', '/compras/proveedores?per_page=200');
  const sel = document.getElementById('oc-prov');
  if (!sel) return;
  sel.innerHTML = (res.data||[]).map(p => `<option value="${p.id}">${p.razon_social}</option>`).join('');
}

/* OC items */
let ocItems = [];

function agregarItemOC() {
  ocItems.push({ desc: '', qty: 1, unidad: 'und', precio: 0 });
  renderOCItems();
}

function renderOCItems() {
  const cont = document.getElementById('oc-items-list');
  cont.innerHTML = ocItems.map((item, i) => `
    <div class="oc-item-row">
      <input placeholder="Descripción del ítem" value="${item.desc}"
        oninput="ocItems[${i}].desc=this.value">
      <input type="number" placeholder="Cant." value="${item.qty}" min="0" step="0.001"
        oninput="ocItems[${i}].qty=+this.value; calcOCTotal()">
      <input placeholder="Unidad" value="${item.unidad}"
        oninput="ocItems[${i}].unidad=this.value">
      <input type="number" placeholder="Precio unit." value="${item.precio}" min="0" step="0.01"
        oninput="ocItems[${i}].precio=+this.value; calcOCTotal()">
      <button class="btn btn-ghost btn-xs"
        onclick="ocItems.splice(${i},1); renderOCItems(); calcOCTotal()">✕</button>
    </div>
  `).join('');
  calcOCTotal();
}

function calcOCTotal() {
  const sub = ocItems.reduce((s,i) => s + (i.qty * i.precio), 0);
  const igv = sub * 0.18;
  const tot = sub + igv;
  const el  = document.getElementById('oc-total-preview');
  if (ocItems.length > 0) {
    el.classList.add('show');
    document.getElementById('oc-subtotal').textContent = fmt(sub, 2);
    document.getElementById('oc-igv').textContent      = fmt(igv, 2);
    document.getElementById('oc-total').textContent    = fmt(tot, 2);
  } else {
    el.classList.remove('show');
  }
}

async function cargarOC() {
  const res   = await api('GET', '/compras/ordenes?per_page=100');
  const tbody = document.getElementById('tbl-oc');
  const rows  = res.data || [];
  tbody.innerHTML = rows.length ? rows.map(o => `
    <tr>
      <td class="mono fw-bold">${o.numero}</td>
      <td>${o.proveedor}</td>
      <td>${o.fecha_emision}</td>
      <td class="small">${o.moneda}</td>
      <td>${fmt(o.subtotal, 2)}</td>
      <td>${fmt(o.igv, 2)}</td>
      <td class="fw-bold">${fmt(o.total, 2)}</td>
      <td>${estadoBadge(o.estado)}</td>
      <td style="white-space:nowrap">
        ${o.estado==='borrador' ? `<button class="btn btn-success btn-xs" onclick="confirmarOC(${o.id})">✓ Confirmar</button>` : ''}
        ${o.estado==='confirmada' ? `<button class="btn btn-ghost btn-xs" onclick="completarOC(${o.id})">✅ Completar</button>` : ''}
      </td>
    </tr>
  `).join('') : emptyRow(9);
}

async function guardarOC() {
  const data = {
    proveedor_id:  document.getElementById('oc-prov').value,
    fecha_emision: document.getElementById('oc-fecha').value,
    fecha_entrega: document.getElementById('oc-entrega').value || null,
    moneda:        document.getElementById('oc-moneda').value,
    items:         ocItems.map(i => ({
      descripcion:     i.desc,
      cantidad:        i.qty,
      unidad:          i.unidad,
      precio_unitario: i.precio,
    })),
  };
  const res = await api('POST', '/compras/ordenes', data);
  if (res.success) { toast('OC ' + res.data.numero + ' creada'); ocItems=[]; cargarOC(); toggleForm('form-oc'); }
  else toast(res.error || 'Error al crear OC', true);
}

async function confirmarOC(id) {
  const res = await api('PUT', `/compras/ordenes/${id}/confirmar`);
  if (res.success) { toast('OC confirmada'); cargarOC(); }
  else toast(res.error || 'Error', true);
}

async function completarOC(id) {
  const numDoc = prompt('Número de factura / documento del proveedor:');
  if (!numDoc) return;
  const res = await api('PUT', `/compras/ordenes/${id}/completar`, { numero_documento: numDoc });
  if (res.success) { toast('OC completada — cuenta por pagar generada'); cargarOC(); }
  else toast(res.error || 'Error', true);
}

async function cargarProveedoresSelectCxP() {
  const res = await api('GET', '/compras/proveedores?per_page=200');
  const sel = document.getElementById('cxp-prov');
  if (!sel) return;
  const rows = res.data || [];
  sel.innerHTML = rows.length
    ? rows.map(p => `<option value="${p.id}">${p.razon_social}</option>`).join('')
    : '<option value="">Sin proveedores registrados</option>';
}

async function guardarCxP() {
  const provId  = document.getElementById('cxp-prov').value;
  const numDoc  = document.getElementById('cxp-num-doc').value.trim();
  const emision = document.getElementById('cxp-emision').value;
  const venc    = document.getElementById('cxp-vencimiento').value;
  const monto   = parseFloat(document.getElementById('cxp-monto').value);

  if (!provId)           { toast('Selecciona un proveedor', true); return; }
  if (!numDoc)           { toast('Ingresa el número de documento', true); return; }
  if (!emision)          { toast('Ingresa la fecha de emisión', true); return; }
  if (!venc)             { toast('Ingresa la fecha de vencimiento', true); return; }
  if (!monto || monto <= 0) { toast('El monto debe ser mayor a 0', true); return; }

  const data = {
    proveedor_id:     parseInt(provId),
    numero_documento: numDoc,
    tipo_documento:   document.getElementById('cxp-tipo-doc').value,
    moneda:           document.getElementById('cxp-moneda').value,
    fecha_emision:    emision,
    fecha_vencimiento: venc,
    monto_total:      monto,
    notas:            document.getElementById('cxp-notas').value || null,
  };

  const res = await api('POST', '/compras/cuentas-pagar', data);
  if (res.message || res.id) {
    toast('Cuenta por pagar registrada');
    toggleForm('form-cxp');
    cargarCxP();
  } else {
    const detalle = res.details?.length ? ': ' + res.details.join(', ') : '';
    toast((res.error || 'Error') + detalle, true);
  }
}

async function cargarCxP() {
  const [lista, resumen] = await Promise.all([
    api('GET', '/compras/cuentas-pagar?per_page=100'),
    api('GET', '/compras/cuentas-pagar/resumen'),
  ]);

  const por   = resumen.data?.por_estado || [];
  const venc  = por.find(r=>r.estado==='vencido')?.cantidad || 0;
  const pend  = por.find(r=>r.estado==='pendiente')?.cantidad || 0;
  const pagad = por.find(r=>r.estado==='pagado')?.cantidad || 0;
  const deuda = por.filter(r=>['pendiente','parcial','vencido'].includes(r.estado))
                   .reduce((s,r) => s + parseFloat(r.total_pendiente||0), 0);

  document.getElementById('cxp-vencidas').textContent  = venc;
  document.getElementById('cxp-pendientes').textContent = pend;
  document.getElementById('cxp-pagadas').textContent   = pagad;
  document.getElementById('cxp-deuda').textContent     = 'S/ ' + fmt(deuda, 2);

  const tbody = document.getElementById('tbl-cxp');
  const rows  = lista.data || [];
  tbody.innerHTML = rows.length ? rows.map(c => {
    const vencido = c.dias_para_vencer < 0;
    const proximo = c.dias_para_vencer < 7;
    return `<tr>
      <td><strong>${c.proveedor}</strong></td>
      <td class="mono small">${c.numero_documento}</td>
      <td>${c.tipo_documento}</td>
      <td>${c.fecha_emision}</td>
      <td class="${vencido ? 'text-red fw-bold' : proximo ? 'text-warn' : ''}">
        ${c.fecha_vencimiento}${vencido ? ' ⚠' : ''}
      </td>
      <td>${fmt(c.monto_total, 2)}</td>
      <td class="text-green">${fmt(c.monto_pagado, 2)}</td>
      <td class="fw-bold ${c.saldo_pendiente>0?'text-red':'text-green'}">${fmt(c.saldo_pendiente, 2)}</td>
      <td>${estadoBadge(c.estado)}</td>
      <td>${c.estado !== 'pagado' ? `<button class="btn btn-success btn-xs" onclick="registrarPagoCxP(${c.id}, ${c.saldo_pendiente})">💸 Pagar</button>` : ''}</td>
    </tr>`;
  }).join('') : emptyRow(10, 'Sin cuentas por pagar');
}

async function registrarPagoCxP(id, saldo) {
  const monto = prompt(`Saldo pendiente: S/ ${fmt(saldo, 2)}\nMonto a pagar:`);
  if (!monto) return;
  const res = await api('POST', `/compras/cuentas-pagar/${id}/pagar`, {
    monto_pago: parseFloat(monto),
    fecha: new Date().toISOString().split('T')[0],
  });
  if (res.success) { toast('Pago registrado — estado: ' + res.data.nuevo_estado); cargarCxP(); }
  else toast(res.error || 'Error', true);
}

/* ══════════════════════════════════════════════════════════
   KARDEX — Tab navigation
══════════════════════════════════════════════════════════ */
function mostrarKardexTab(tabId, btn) {
  ['kardex-tabla', 'kardex-grafico'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  });
  const target = document.getElementById(tabId);
  if (target) target.style.display = 'block';

  if (btn) {
    btn.closest('.tab-nav').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }
}

/* ══════════════════════════════════════════════════════════
   FINANCIERO — Tab navigation
══════════════════════════════════════════════════════════ */
function mostrarFinTab(tabId, btn) {
  ['flujo-tabla','proyeccion','centros-costo'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  });
  const target = document.getElementById(tabId);
  if (target) target.style.display = 'block';

  if (btn) {
    btn.closest('.tab-nav').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }

  if (tabId === 'flujo-tabla')   cargarFlujo();
  if (tabId === 'proyeccion')    cargarProyeccion();
  if (tabId === 'centros-costo') cargarCentros();
}

async function cargarFinanciero() {
  const hoy   = new Date();
  const desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
  const hasta = hoy.toISOString().split('T')[0];

  const [resumen, proy] = await Promise.all([
    api('GET', `/financiero/flujo-caja/resumen?desde=${desde}&hasta=${hasta}`),
    api('GET', '/financiero/flujo-caja/proyeccion?dias=30'),
  ]);

  const s = resumen.data?.saldo || {};
  document.getElementById('fin-ingresos').textContent = s.total_ingresos ? 'S/ ' + fmt(s.total_ingresos, 0) : '—';
  document.getElementById('fin-egresos').textContent  = s.total_egresos  ? 'S/ ' + fmt(s.total_egresos, 0)  : '—';

  const saldoVal = parseFloat(s.saldo_neto || 0);
  const saldoEl  = document.getElementById('fin-saldo');
  saldoEl.textContent  = 'S/ ' + fmt(saldoVal, 0);
  saldoEl.style.color  = saldoVal >= 0 ? 'var(--verde)' : 'var(--danger)';

  const cobros = (proy.data?.cobros_estimados||[]).reduce((s,r) => s + parseFloat(r.monto||0), 0);
  document.getElementById('fin-cobrar').textContent = cobros ? '$' + fmt(cobros, 0) : '—';

  cargarFlujo();
}

async function cargarFlujo() {
  const tipo  = document.getElementById('f-fc-tipo')?.value  || '';
  const desde = document.getElementById('f-fc-desde')?.value || '';
  const hasta = document.getElementById('f-fc-hasta')?.value || '';
  const params = Object.fromEntries(Object.entries({ tipo, desde, hasta }).filter(([,v]) => v));
  const qs = new URLSearchParams(params).toString();

  const res   = await api('GET', `/financiero/flujo-caja?${qs}&per_page=100`);
  const tbody = document.getElementById('tbl-flujo');
  const rows  = res.data || [];
  tbody.innerHTML = rows.length ? rows.map(f => `
    <tr>
      <td>${f.fecha}</td>
      <td><span class="fw-bold small" style="color:${f.tipo==='ingreso'?'var(--verde)':'var(--danger)'}; text-transform:uppercase">
        ${f.tipo==='ingreso' ? '▲ Ingreso' : '▼ Egreso'}
      </span></td>
      <td>${f.categoria}</td>
      <td>${f.concepto}</td>
      <td class="fw-bold">${fmt(f.monto, 2)}</td>
      <td class="small">${f.moneda}</td>
      <td class="fw-bold ${f.tipo==='ingreso'?'text-green':'text-red'}">S/ ${fmt(f.monto_pen, 2)}</td>
      <td class="small">${f.cuenta_banco||'—'}</td>
    </tr>
  `).join('') : emptyRow(8, 'Sin movimientos en este período');
}

async function cargarProyeccion() {
  const res  = await api('GET', '/financiero/flujo-caja/proyeccion?dias=30');
  const d    = res.data || {};
  const tCobros = document.getElementById('tbl-cobros');
  const cobros  = d.cobros_estimados || [];
  tCobros.innerHTML = cobros.length ? cobros.map(c => `
    <tr>
      <td class="mono small">${c.referencia}</td>
      <td class="text-warn">${c.fecha_estimada}</td>
      <td class="fw-bold text-green">S/ ${fmt(c.monto, 2)}</td>
      <td class="small">${c.moneda}</td>
      <td>${c.contraparte}</td>
    </tr>
  `).join('') : emptyRow(5, 'Sin cobros estimados');

  const tPagos = document.getElementById('tbl-pagos');
  const pagos  = d.pagos_estimados || [];
  tPagos.innerHTML = pagos.length ? pagos.map(p => `
    <tr>
      <td class="mono small">${p.referencia}</td>
      <td class="text-warn">${p.fecha_estimada}</td>
      <td class="fw-bold text-red">S/ ${fmt(p.monto, 2)}</td>
      <td class="small">${p.moneda}</td>
      <td>${p.contraparte}</td>
    </tr>
  `).join('') : emptyRow(5, 'Sin pagos estimados');
}

async function cargarCentros() {
  const res   = await api('GET', `/financiero/centros-costo/analisis?anio=${new Date().getFullYear()}`);
  const tbody = document.getElementById('tbl-centros');
  const rows  = res.data?.centros || [];
  tbody.innerHTML = rows.length ? rows.map(c => {
    const resultado = parseFloat(c.resultado || 0);
    return `<tr>
      <td class="mono fw-bold">${c.codigo}</td>
      <td><strong>${c.nombre}</strong></td>
      <td class="text-green">S/ ${fmt(c.total_ingresos, 2)}</td>
      <td class="text-red">S/ ${fmt(c.total_egresos, 2)}</td>
      <td class="fw-black" style="color:${resultado>=0?'var(--verde)':'var(--danger)'}">S/ ${fmt(resultado, 2)}</td>
    </tr>`;
  }).join('') : emptyRow(5, 'Sin datos de centros de costo');
}

async function guardarFlujo() {
  const data = {
    fecha:        document.getElementById('fc-fecha').value,
    tipo:         document.getElementById('fc-tipo').value,
    categoria:    document.getElementById('fc-cat').value,
    concepto:     document.getElementById('fc-concepto').value,
    monto:        parseFloat(document.getElementById('fc-monto').value),
    moneda:       document.getElementById('fc-moneda').value,
    tipo_cambio:  parseFloat(document.getElementById('fc-tc').value) || 1,
    cuenta_banco: document.getElementById('fc-banco').value || null,
  };
  const res = await api('POST', '/financiero/flujo-caja', data);
  if (res.success) { toast('Movimiento registrado'); cargarFinanciero(); toggleForm('form-flujo'); }
  else toast(res.error || 'Error', true);
}

/* ══════════════════════════════════════════════════════════
   CONFIGURACIÓN — Tabs principales
══════════════════════════════════════════════════════════ */

const CFG_TABS = ['cfg-tab-general','cfg-tab-campanas','cfg-tab-backups','cfg-tab-usuarios','cfg-tab-cuenta'];

function mostrarCfgTab(tabId, btn) {
  CFG_TABS.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = (id === tabId) ? '' : 'none';
  });
  const nav = document.getElementById('cfg-tab-nav');
  if (nav) nav.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');

  // Carga lazy según el tab activo
  if (tabId === 'cfg-tab-campanas') cargarCampanasConfig();
  if (tabId === 'cfg-tab-backups')  { cargarBackupStats(); cargarBackups('diario'); }
  if (tabId === 'cfg-tab-usuarios') cargarUsuarios();
  if (tabId === 'cfg-tab-cuenta')   cargarPerfil();
}

/* ── Tasa USD ────────────────────────────────────────────── */
async function cargarConfiguracion() {
  try {
    const res = await fetch(`${API}/configuracion/tasa_usd`);
    if (!res.ok) throw new Error();
    const data = await res.json();
    _aplicarTasaUSD(parseFloat(data.valor));
  } catch {
    const cached = localStorage.getItem('tasa_usd');
    if (cached) _aplicarTasaUSD(parseFloat(cached));
  }
  cargarLogoSistema();
}

/* ── Logo del sistema ────────────────────────────────────── */
function _aplicarLogo(url) {
  document.querySelectorAll('.js-logo-img').forEach(img => {
    img.src = url;
    img.style.display = '';
  });
  document.querySelectorAll('.js-logo-fallback').forEach(el => { el.style.display = 'none'; });
}

async function cargarLogoSistema() {
  try {
    const res = await fetch(`${API}/configuracion/logo_url`);
    if (!res.ok) throw new Error();
    const data = await res.json();
    if (data.valor) _aplicarLogo(data.valor);
  } catch { /* sin logo configurado, se queda el emoji por defecto */ }
}

async function subirLogoSistema() {
  const input = document.getElementById('cfg-logo-input');
  const file  = input?.files?.[0];
  if (!file) { toast('Selecciona una imagen primero', true); return; }
  if (file.size > 2 * 1024 * 1024) { toast('El logo no puede superar 2 MB', true); return; }

  const fd = new FormData();
  fd.append('logo', file);

  try {
    const res = await fetch(`${API}/configuracion/logo`, { method: 'POST', body: fd });
    const data = await res.json();
    if (!res.ok || data.success === false) throw new Error(data.error || 'Error al subir el logo');
    _aplicarLogo(data.data?.valor || data.valor);
    toast('Logo actualizado');
  } catch (e) {
    toast(e.message || 'Error al subir el logo', true);
  }
}

function _quitarLogo() {
  document.querySelectorAll('.js-logo-img').forEach(img => { img.src = ''; img.style.display = 'none'; });
  document.querySelectorAll('.js-logo-fallback').forEach(el => { el.style.display = ''; });
}

async function restablecerLogoSistema() {
  if (!confirm('¿Restablecer el logo al ícono predeterminado (☕)?')) return;
  try {
    const res = await fetch(`${API}/configuracion/logo`, { method: 'DELETE' });
    if (!res.ok) throw new Error();
    _quitarLogo();
    const input = document.getElementById('cfg-logo-input');
    if (input) input.value = '';
    toast('Logo restablecido');
  } catch {
    toast('No se pudo restablecer el logo', true);
  }
}

/* ── Restablecer configuración a valores predeterminados ──── */
async function restablecerConfiguracion(claves) {
  if (!confirm('¿Restablecer estos valores a los predeterminados de fábrica?')) return;
  try {
    const res = await fetch(`${API}/configuracion/reset`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ claves })
    });
    if (!res.ok) throw new Error();
    toast('Configuración restablecida');
    cargarConfiguracion();
  } catch {
    toast('No se pudo restablecer la configuración', true);
  }
}

/* ── Campañas: topbar ────────────────────────────────────── */
async function cargarCampanasTopbar() {
  const sel = document.getElementById('topbar-campana');
  if (!sel) return;
  try {
    const res = await fetch(`${API}/campanas`);
    if (!res.ok) throw new Error();
    const data = await res.json();
    const rows = data.data || data;
    if (!rows.length) throw new Error();

    const saved = localStorage.getItem('campana_activa') || String(rows[0].año);
    sel.innerHTML = rows.map(c =>
      `<option value="${c.año}" ${String(c.año) === saved ? 'selected' : ''}>CAMPAÑA: ${c.año}</option>`
    ).join('');
    localStorage.setItem('campana_activa', sel.value);
  } catch {
    const y = localStorage.getItem('campana_activa') || String(new Date().getFullYear());
    sel.innerHTML = `<option value="${y}">CAMPAÑA: ${y}</option>`;
  }
}

function cambiarCampana(año) {
  localStorage.setItem('campana_activa', año);
  toast('Campaña ' + año + ' seleccionada');
}

/* ── Campañas: sección Configuración ─────────────────────── */
async function cargarCampanasConfig() {
  const tbody = document.getElementById('tbl-campanas');
  if (!tbody) return;

  try {
    const res = await fetch(`${API}/campanas`);
    if (!res.ok) throw new Error();
    const data = await res.json();
    const rows = data.data || data;

    // KPI cards
    const activa   = rows.find(c => c.estado === 'activa');
    const cerradas = rows.filter(c => c.estado !== 'activa').length;

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('kpi-camp-total',   rows.length);
    set('kpi-camp-activa',  activa ? activa.año : '—');
    set('kpi-camp-cerradas',cerradas);

    // Lotes en campaña activa
    if (activa) {
      try {
        const lr = await fetch(`${API}/acopios?campana=${activa.año}&per_page=1`);
        const ld = await lr.json();
        set('kpi-camp-lotes', ld.pagination?.total ?? '—');
      } catch { set('kpi-camp-lotes', '—'); }
    } else {
      set('kpi-camp-lotes', '—');
    }

    // Tabla
    tbody.innerHTML = rows.length ? rows.map(c => {
      const btnActivar  = c.estado !== 'activa'   ? `<button class="btn btn-ghost btn-sm" style="font-size:.78rem;padding:4px 10px" onclick="cambiarEstadoCampana(${c.año},'activa')">✅ Activar</button>` : '';
      const btnCerrar   = c.estado === 'activa'   ? `<button class="btn btn-ghost btn-sm" style="font-size:.78rem;padding:4px 10px" onclick="cambiarEstadoCampana(${c.año},'cerrada')">🔒 Cerrar</button>` : '';
      const btnArchivar = c.estado === 'cerrada'  ? `<button class="btn btn-ghost btn-sm" style="font-size:.78rem;padding:4px 10px" onclick="cambiarEstadoCampana(${c.año},'archivada')">🗃 Archivar</button>` : '';
      return `<tr>
        <td class="mono fw-bold">${c.año}</td>
        <td>${c.fecha_inicio ? c.fecha_inicio.slice(0,10) : '—'}</td>
        <td>${c.fecha_fin    ? c.fecha_fin.slice(0,10)    : '—'}</td>
        <td>${campanaEstadoBadge(c.estado)}</td>
        <td class="text-muted small">${c.notas || '—'}</td>
        <td style="text-align:right;white-space:nowrap">${btnActivar}${btnCerrar}${btnArchivar}</td>
      </tr>`;
    }).join('') : `<tr><td colspan="6"><div class="empty"><span class="empty-icon">🗓</span><p>Sin campañas registradas</p></div></td></tr>`;

  } catch {
    tbody.innerHTML = `<tr><td colspan="6" class="text-muted text-center">Error al cargar campañas</td></tr>`;
  }
}

function campanaEstadoBadge(estado) {
  const map = { activa: 'specialty', cerrada: 'comercial', archivada: 'descarte' };
  return `<span class="badge badge-${map[estado] || 'comercial'}">${estado}</span>`;
}

async function cambiarEstadoCampana(año, nuevoEstado) {
  const verb = { activa: 'Activar', cerrada: 'Cerrar', archivada: 'Archivar' };
  if (!confirm(`¿${verb[nuevoEstado]} la campaña ${año}?`)) return;
  const res = await api('PUT', `/campanas/${año}`, { estado: nuevoEstado });
  if (res.año || res.message || res.data) {
    toast(`Campaña ${año} → ${nuevoEstado}`);
    cargarCampanasConfig();
    cargarCampanasTopbar();
  } else {
    toast(res.error || 'Error al actualizar campaña', true);
  }
}

async function guardarCampana() {
  const año    = parseInt(document.getElementById('nc-anio')?.value);
  const inicio = document.getElementById('nc-inicio')?.value || null;
  const fin    = document.getElementById('nc-fin')?.value    || null;
  const estado = document.getElementById('nc-estado')?.value || 'activa';
  const notas  = document.getElementById('nc-notas')?.value  || null;

  if (!año || año < 2000 || año > 2100) { toast('Año inválido', true); return; }

  const res = await api('POST', '/campanas', { año, fecha_inicio: inicio, fecha_fin: fin, estado, notas });
  if (res.success) {
    toast('Campaña ' + año + ' guardada');
    toggleForm('form-nueva-campana');
    cargarCampanasConfig();
    cargarCampanasTopbar();
  } else {
    toast(res.error || 'Error al guardar campaña', true);
  }
}

/* ── Backups ─────────────────────────────────────────────── */
function mostrarBackupTab(tabId, btn) {
  document.querySelectorAll('.backup-tab').forEach(t => t.style.display = 'none');
  document.querySelectorAll('.backup-tab-btn').forEach(b => b.classList.remove('active'));
  const tab = document.getElementById(tabId);
  if (tab) tab.style.display = '';
  if (btn) btn.classList.add('active');
  const tipoMap = { 'backup-diarios': 'diario', 'backup-mensuales': 'mensual', 'backup-anuales': 'anual' };
  cargarBackups(tipoMap[tabId] || 'diario');
}

async function cargarBackupStats() {
  try {
    const [rd, rm, ra] = await Promise.all([
      fetch(`${API}/campanas/backups?tipo=diario`),
      fetch(`${API}/campanas/backups?tipo=mensual`),
      fetch(`${API}/campanas/backups?tipo=anual`),
    ]);
    const [dd, dm, da] = await Promise.all([rd.json(), rm.json(), ra.json()]);
    const diarios   = dd.data || dd || [];
    const mensuales = dm.data || dm || [];
    const anuales   = da.data || da || [];

    const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    set('kpi-bk-diarios',   Array.isArray(diarios)   ? diarios.length   : '—');
    set('kpi-bk-mensuales', Array.isArray(mensuales) ? mensuales.length : '—');
    set('kpi-bk-anuales',   Array.isArray(anuales)   ? anuales.length   : '—');

    // Último backup por fecha
    const all = [...(Array.isArray(diarios) ? diarios : []),
                 ...(Array.isArray(mensuales) ? mensuales : []),
                 ...(Array.isArray(anuales) ? anuales : [])]
      .filter(b => b.fecha_backup)
      .sort((a, b) => b.fecha_backup.localeCompare(a.fecha_backup));
    set('kpi-bk-ultimo', all.length ? all[0].fecha_backup.slice(0, 10) : 'Sin backups');
  } catch { /* silently ignore */ }
}

async function cargarBackups(tipo) {
  const tbodyId = { diario: 'tbl-backup-diarios', mensual: 'tbl-backup-mensuales', anual: 'tbl-backup-anuales' }[tipo];
  const tbody = document.getElementById(tbodyId);
  if (!tbody) return;

  try {
    const res = await fetch(`${API}/campanas/backups?tipo=${tipo}`);
    if (!res.ok) throw new Error();
    const data = await res.json();
    const rows = data.data || data;

    tbody.innerHTML = Array.isArray(rows) && rows.length ? rows.map(r => {
      const fecha = r.fecha_backup ? r.fecha_backup.slice(0, 16).replace('T', ' ') : '—';
      const est   = r.estado === 'completado'
        ? `<span class="badge badge-specialty">✅ completado</span>`
        : r.estado === 'fallido'
        ? `<span class="badge badge-descarte">❌ fallido</span>`
        : `<span class="badge badge-comercial">🕒 pendiente</span>`;
      return `<tr>
        <td class="mono">${fecha}</td>
        <td class="mono">${r.campana_año ?? '—'}</td>
        <td>${r.descripcion || '—'}</td>
        <td>${r.realizado_por || 'Administrador'}</td>
        <td>${est}</td>
      </tr>`;
    }).join('') : `<tr><td colspan="5"><div class="empty"><span class="empty-icon">💾</span><p>Sin backups ${tipo}s registrados</p></div></td></tr>`;
  } catch {
    tbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">Error al cargar backups</td></tr>`;
  }
}

function abrirRegistrarBackup(tipo) {
  const tipos = { diario: 'Diario', mensual: 'Mensual', anual: 'Anual' };
  const el = id => document.getElementById(id);

  el('bk-tipo').value = tipo;
  const title = el('modal-backup-title');
  if (title) title.textContent = `💾 Registrar Backup ${tipos[tipo] || tipo}`;

  // Pre-rellenar fecha con la hora actual local
  const now   = new Date();
  const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
  el('bk-fecha').value          = local;
  el('bk-descripcion').value    = `Backup ${tipo} - ${now.toISOString().slice(0, 10)}`;
  el('bk-realizado-por').value  = '';
  el('bk-estado').value         = 'completado';

  toggleForm('form-backup-reg');
}

async function confirmarRegistrarBackup() {
  const el = id => document.getElementById(id);
  const tipo         = el('bk-tipo').value;
  const fecha        = el('bk-fecha').value;
  const descripcion  = el('bk-descripcion').value.trim();
  const realizadoPor = el('bk-realizado-por').value.trim() || 'Administrador';
  const estado       = el('bk-estado').value;
  const año          = parseInt(localStorage.getItem('campana_activa') || new Date().getFullYear());

  if (!tipo || !fecha) { toast('Fecha requerida', true); return; }

  const res = await api('POST', '/campanas/backups', {
    campana_año:   año,
    tipo,
    descripcion:   descripcion || `Backup ${tipo} - ${fecha.slice(0, 10)}`,
    realizado_por: realizadoPor,
    fecha_backup:  fecha + ':00',
    estado,
  });
  if (res.id || res.message) {
    toast(`Backup ${tipo} registrado`);
    toggleForm('form-backup-reg');
    cargarBackups(tipo);
    cargarBackupStats();
  } else {
    toast(res.error || 'Error al registrar backup', true);
  }
}

/* ── Usuarios ────────────────────────────────────────────── */
async function cargarUsuarios() {
  const tbody = document.getElementById('tbl-usuarios');
  if (!tbody) return;

  try {
    const res  = await fetch('usuarios-api.php?action=list');
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Error desconocido');

    const usuarios = data.usuarios || [];
    tbody.innerHTML = usuarios.length ? usuarios.map(u => `
      <tr>
        <td class="mono fw-bold">
          ${u.username}
          ${u.es_yo ? '<span class="badge badge-specialty" style="font-size:.7rem;vertical-align:middle;margin-left:4px">Tú</span>' : ''}
        </td>
        <td>${u.nombre}</td>
        <td class="text-muted">${u.email || '—'}</td>
        <td>${rolBadge(u.rol)}</td>
        <td style="text-align:right;white-space:nowrap">
          <button class="btn btn-ghost btn-sm" style="font-size:.78rem;padding:4px 10px"
                  onclick='editarUsuario(${JSON.stringify(u)})'>✏️ Editar</button>
          ${!u.es_yo ? `<button class="btn btn-ghost btn-sm" style="font-size:.78rem;padding:4px 10px;color:var(--danger,#e74c3c)"
                  onclick="eliminarUsuario('${u.username}','${u.nombre.replace(/'/g, "\\'")}')">🗑 Eliminar</button>` : ''}
        </td>
      </tr>
    `).join('') : emptyRow(5, 'Sin usuarios registrados');
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-muted text-center">Error: ${e.message}</td></tr>`;
  }
}

function rolBadge(rol) {
  const map = { Administrador: 'specialty', Supervisor: 'premium', Operador: 'comercial', Auditor: 'descarte' };
  return `<span class="badge badge-${map[rol] || 'comercial'}">${rol}</span>`;
}

function abrirNuevoUsuario() {
  const el = id => document.getElementById(id);
  el('modal-usuario-title').textContent = '👤 Nuevo Usuario';
  el('u-username-original').value = '';
  el('u-username').value          = '';
  el('u-username').disabled       = false;
  el('u-username-hint').textContent = 'Letras, números y guión bajo (3-40 caracteres)';
  el('u-nombre').value            = '';
  el('u-email').value             = '';
  el('u-rol').value               = 'Operador';
  el('u-password').value          = '';
  el('u-password-label').textContent = 'Contraseña *';
  el('u-password-hint').textContent  = 'Mínimo 8 caracteres';
  el('uw-strength-bar').style.width  = '0';
  toggleForm('form-usuario');
}

function editarUsuario(u) {
  const el = id => document.getElementById(id);
  el('modal-usuario-title').textContent = '✏️ Editar Usuario';
  el('u-username-original').value = u.username;
  el('u-username').value          = u.username;
  el('u-username').disabled       = true;
  el('u-username-hint').textContent = 'El nombre de usuario no puede cambiarse';
  el('u-nombre').value            = u.nombre;
  el('u-email').value             = u.email  || '';
  el('u-rol').value               = u.rol;
  el('u-password').value          = '';
  el('u-password-label').textContent = 'Nueva contraseña';
  el('u-password-hint').textContent  = 'Dejar vacío para mantener la contraseña actual';
  el('uw-strength-bar').style.width  = '0';
  toggleForm('form-usuario');
}

async function guardarUsuario() {
  const el       = id => document.getElementById(id).value;
  const original = el('u-username-original');
  const isEdit   = original !== '';
  const username = isEdit ? original : el('u-username').trim();
  const nombre   = el('u-nombre').trim();
  const email    = el('u-email').trim();
  const rol      = el('u-rol');
  const password = el('u-password');

  if (!nombre)              { toast('El nombre para mostrar es requerido', true); return; }
  if (!isEdit && !username) { toast('El nombre de usuario es requerido', true);   return; }
  if (!isEdit && password.length < 8)          { toast('La contraseña debe tener al menos 8 caracteres', true); return; }
  if (isEdit && password && password.length < 8){ toast('La contraseña debe tener al menos 8 caracteres', true); return; }

  const url  = isEdit
    ? `usuarios-api.php?action=update&username=${encodeURIComponent(original)}`
    : 'usuarios-api.php?action=create';
  const body = isEdit
    ? { nombre, email, rol, password }
    : { username, nombre, email, rol, password };

  try {
    const res  = await fetch(url, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body),
    });
    const data = await res.json();
    if (!data.success) { toast(data.error || 'Error al guardar', true); return; }

    toast(isEdit ? `Usuario '${username}' actualizado` : `Usuario '${username}' creado`);
    toggleForm('form-usuario');
    cargarUsuarios();

    // Si editó su propio perfil, refrescar tarjeta de Mi Cuenta
    if (isEdit) cargarPerfil();
  } catch {
    toast('Error de red', true);
  }
}

async function eliminarUsuario(username, nombre) {
  if (!confirm(`¿Eliminar al usuario "${nombre}" (@${username})?\nEsta acción no se puede deshacer.`)) return;
  try {
    const res  = await fetch(`usuarios-api.php?action=delete&username=${encodeURIComponent(username)}`, { method: 'POST' });
    const data = await res.json();
    if (!data.success) { toast(data.error || 'Error al eliminar', true); return; }
    toast(`Usuario '${username}' eliminado`);
    cargarUsuarios();
  } catch {
    toast('Error de red', true);
  }
}

function checkUwStrength(val) {
  let score = 0;
  if (val.length >= 8)           score++;
  if (val.length >= 12)          score++;
  if (/[A-Z]/.test(val))         score++;
  if (/[0-9]/.test(val))         score++;
  if (/[^A-Za-z0-9]/.test(val))  score++;

  const bar = document.getElementById('uw-strength-bar');
  if (!bar) return;
  const levels = [
    { color: '#e74c3c', width: '20%' }, { color: '#e67e22', width: '40%' },
    { color: '#f1c40f', width: '60%' }, { color: '#2ecc71', width: '80%' },
    { color: '#00704A', width: '100%' },
  ];
  const lvl = levels[Math.max(0, score - 1)] || levels[0];
  bar.style.width      = val ? lvl.width : '0';
  bar.style.background = val ? lvl.color : '';
}

function _aplicarTasaUSD(val) {
  if (!val || val <= 0) return;
  localStorage.setItem('tasa_usd', val);

  const text = val.toFixed(4);
  const display = document.getElementById('tasa-usd-display');
  if (display) display.textContent = text;

  const cfgVal = document.getElementById('cfg-val-tasa-usd');
  if (cfgVal) cfgVal.textContent = 'S/. ' + text;
}

function cargarTasaUSDDisplay() {
  // Muestra la tasa en el widget de ventas; la carga desde API si no está en memoria
  const cached = localStorage.getItem('tasa_usd');
  if (cached) {
    _aplicarTasaUSD(parseFloat(cached));
  } else {
    cargarConfiguracion();
  }
}

function editarTasaDolar() {
  const current = localStorage.getItem('tasa_usd') || '';
  const input = document.getElementById('cfg-tasa-usd-input');
  if (input) input.value = current ? parseFloat(current).toFixed(4) : '';
  toggleForm('form-tasa-usd');
}

async function guardarTasaDolar() {
  const input = document.getElementById('cfg-tasa-usd-input');
  const val = parseFloat(input?.value);
  if (!val || val <= 0) { toast('Ingresa una tasa válida mayor a 0', true); return; }

  try {
    const res = await fetch(`${API}/configuracion/tasa_usd`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ valor: val.toString(), descripcion: 'Tipo de cambio USD → PEN' })
    });
    if (!res.ok) throw new Error();
    _aplicarTasaUSD(val);
    toggleForm('form-tasa-usd');
    toast('Tasa actualizada: S/. ' + val.toFixed(4));
  } catch {
    // Guardar localmente aunque falle la API (tabla puede no existir aún)
    _aplicarTasaUSD(val);
    toggleForm('form-tasa-usd');
    toast('Tasa guardada localmente: S/. ' + val.toFixed(4));
  }
}

function abrirFormVenta() {
  _ventaLotes = [];
  _lotesDisp  = [];
  limpiarCompradorVenta();
  renderLotesVenta();
  const shopSearch = document.getElementById('v-shop-search');
  if (shopSearch) shopSearch.value = '';
  cargarLotesDisponiblesForm();
  const fech = document.getElementById('v-fecha');
  if (fech && !fech.value) fech.value = new Date().toISOString().split('T')[0];
}

/* ══════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════ */
(async () => {
  const hoy = new Date().toISOString().split('T')[0];
  document.querySelectorAll('input[type="date"]').forEach(i => {
    if (!i.value && !i.closest('.filters') && !i.closest('.ph-actions')) i.value = hoy;
  });
  await Promise.all([loadDashboard(), cargarCampanasTopbar()]);
  // Cargar tasa USD al inicio para que esté disponible en cualquier módulo
  cargarConfiguracion();
})();

/* ══════════════════════════════════════════════════════════
   VENTAS — Tab navigation
══════════════════════════════════════════════════════════ */
function mostrarVentaTab(tabId, btn) {
  ['vt-contratos','vt-cotizaciones','vt-sunat','vt-buscar','vt-historial','vt-reportes'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
  });
  const target = document.getElementById(tabId);
  if (target) target.style.display = 'block';
  if (btn) {
    btn.closest('.tab-nav').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }
  if (tabId === 'vt-historial') cargarHistorial();
  if (tabId === 'vt-reportes')  verReporte('diario', document.querySelector('#vt-reportes .btn-primary'));
}

/* ══════════════════════════════════════════════════════════
   VENTAS — Buscar cliente por DNI / RUC
══════════════════════════════════════════════════════════ */
async function buscarClienteDNI() {
  const q   = document.getElementById('dni-input').value.trim();
  const out = document.getElementById('dni-resultado');
  if (!q) { out.innerHTML = '<p class="text-muted">Ingresa un DNI o RUC para buscar.</p>'; return; }

  out.innerHTML = '<p class="text-muted">Buscando...</p>';
  const res = await api('GET', `/clientes?search=${encodeURIComponent(q)}&per_page=5`);
  const rows = res.data || [];

  if (!rows.length) {
    out.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><div>No se encontró ningún cliente con ese DNI / RUC.</div></div>';
    return;
  }

  out.innerHTML = rows.map(c => `
    <div class="cliente-card">
      <div class="cliente-card-header">
        <span class="cliente-card-nombre">${c.razon_social}</span>
        <span class="badge ${c.tipo === 'comprador' ? 'badge-info' : 'badge-verde'}">${c.tipo}</span>
      </div>
      <div class="cliente-card-grid">
        <div><span class="cck-label">DNI / RUC</span><span class="cck-val mono">${c.ruc_dni || '—'}</span></div>
        <div><span class="cck-label">Contacto</span><span class="cck-val">${c.contacto || '—'}</span></div>
        <div><span class="cck-label">Teléfono</span><span class="cck-val">${c.telefono || '—'}</span></div>
        <div><span class="cck-label">Email</span><span class="cck-val">${c.email || '—'}</span></div>
        <div><span class="cck-label">País destino</span><span class="cck-val">${c.pais_destino || '—'}</span></div>
        <div><span class="cck-label">Asociación</span><span class="cck-val">${c.asociacion || '—'}</span></div>
        <div><span class="cck-label">Dpto.</span><span class="cck-val">${c.departamento || '—'}</span></div>
        <div><span class="cck-label">Hectáreas</span><span class="cck-val">${c.hectareas || '—'}</span></div>
      </div>
      <div id="ventas-cliente-${c.id}" class="cliente-ventas-hist"></div>
    </div>
  `).join('');

  // Cargar ventas de cada cliente encontrado
  rows.forEach(c => cargarVentasCliente(c.id));
}

async function cargarVentasCliente(clienteId) {
  const res  = await api('GET', `/ventas?comprador_id=${clienteId}&per_page=50`);
  const rows = res.data || [];
  const el   = document.getElementById(`ventas-cliente-${clienteId}`);
  if (!el) return;
  if (!rows.length) { el.innerHTML = '<p class="text-muted small" style="margin-top:10px">Sin ventas registradas.</p>'; return; }
  el.innerHTML = `
    <table style="margin-top:12px;width:100%;font-size:.8rem">
      <thead><tr><th>Venta</th><th>Lote</th><th>Fecha</th><th class="text-right">Kg</th><th class="text-right">Total S/</th><th>Estado</th></tr></thead>
      <tbody>${rows.map(v => `
        <tr>
          <td class="mono fw-bold">${v.numero_contrato}</td>
          <td class="mono small">${v.acopio_codigo}</td>
          <td>${v.fecha_contrato}</td>
          <td class="text-right">${fmt(v.cantidad_kg)}</td>
          <td class="text-right fw-bold text-green">${fmtUSD(v.total_usd)}</td>
          <td>${estadoBadge(v.estado)}</td>
        </tr>`).join('')}
      </tbody>
    </table>`;
}

/* ══════════════════════════════════════════════════════════
   VENTAS — Historial
══════════════════════════════════════════════════════════ */
let _historialRows = [];

async function cargarHistorial() {
  const res = await api('GET', '/ventas?per_page=200');
  _historialRows = res.data || [];
  filtrarHistorial();
}

function filtrarHistorial() {
  const q    = (document.getElementById('f-hist-comprador')?.value || '').toLowerCase();
  const rows = q ? _historialRows.filter(v => v.comprador.toLowerCase().includes(q)) : _historialRows;
  const tbody = document.getElementById('tbl-historial');
  tbody.innerHTML = rows.length ? rows.map(v => `
    <tr>
      <td class="mono fw-bold">${v.numero_contrato}</td>
      <td>${v.comprador}</td>
      <td class="small">${v.pais_destino || '—'}</td>
      <td class="mono small">${v.acopio_codigo}</td>
      <td>${v.fecha_contrato}</td>
      <td class="text-right">${fmt(v.cantidad_kg)} kg</td>
      <td class="text-right fw-bold text-green">${fmtUSD(v.total_usd)}</td>
      <td class="text-right">S/ ${fmt(v.total_local, 2)}</td>
      <td>${estadoBadge(v.estado)}</td>
      <td>${sunatBadge(v)}</td>
    </tr>`).join('') : emptyRow(10);
}

/* ══════════════════════════════════════════════════════════
   VENTAS — Reportes de facturación
══════════════════════════════════════════════════════════ */
async function verReporte(periodo, btn) {
  // Resaltar botón activo
  document.querySelectorAll('#vt-reportes .btn').forEach(b => {
    b.className = b === btn ? 'btn btn-primary' : 'btn btn-ghost';
  });

  const hoy   = new Date();
  let desde, label;
  if (periodo === 'diario') {
    desde = hoy.toISOString().split('T')[0];
    label = 'Hoy ' + hoy.toLocaleDateString('es-PE');
  } else if (periodo === 'semanal') {
    const ini = new Date(hoy); ini.setDate(hoy.getDate() - 6);
    desde = ini.toISOString().split('T')[0];
    label = 'Últimos 7 días';
  } else {
    const ini = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    desde = ini.toISOString().split('T')[0];
    label = hoy.toLocaleDateString('es-PE', { month: 'long', year: 'numeric' });
  }

  document.getElementById('rep-periodo-label').textContent = label;
  document.getElementById('rep-rango').textContent = `Desde ${desde} hasta hoy`;

  const res  = await api('GET', `/ventas?desde=${desde}&per_page=200`);
  const rows = res.data || [];

  const totUSD = rows.reduce((s, v) => s + parseFloat(v.total_usd  || 0), 0);
  const totPEN = rows.reduce((s, v) => s + parseFloat(v.total_local|| 0), 0);
  const totKG  = rows.reduce((s, v) => s + parseFloat(v.cantidad_kg|| 0), 0);

  document.getElementById('rep-contratos').textContent = rows.length;
  document.getElementById('rep-usd').textContent       = fmtUSD(totUSD);
  document.getElementById('rep-pen').textContent       = 'S/ ' + fmt(totPEN, 2);
  document.getElementById('rep-kg').textContent        = fmt(totKG, 0) + ' kg';

  const tbody = document.getElementById('tbl-reporte');
  tbody.innerHTML = rows.length ? rows.map(v => `
    <tr>
      <td class="mono fw-bold">${v.numero_contrato}</td>
      <td>${v.fecha_contrato}</td>
      <td>${v.comprador}</td>
      <td class="mono small">${v.acopio_codigo}</td>
      <td class="text-right">${fmt(v.cantidad_kg)} kg</td>
      <td class="text-right">S/ ${parseFloat(v.precio_usd_kg).toFixed(3)}</td>
      <td class="text-right fw-bold text-green">${fmtUSD(v.total_usd)}</td>
      <td class="text-right">S/ ${fmt(v.total_local, 2)}</td>
      <td>${estadoBadge(v.estado)}</td>
    </tr>`).join('') : emptyRow(9);
}

/* ══════════════════════════════════════════════════════════
   VENTAS — WhatsApp comprobante
══════════════════════════════════════════════════════════ */
function previewWAFormVenta() {
  if (!_ventaLotes.length) { toast('Agrega al menos un lote para previsualizar', true); return; }
  const comprador = document.getElementById('v-comp-sel-name')?.textContent || '—';
  const fecha     = document.getElementById('v-fecha').value || '—';
  const incoterm  = document.getElementById('v-incoterm').value || 'FOB';
  const tasa      = parseFloat(localStorage.getItem('tasa_usd')) || 3.75;
  const l         = _ventaLotes[0];
  const totalUSD  = _ventaLotes.reduce((s, x) => s + (x.cantidad || 0) * (x.precio || 0), 0);

  abrirModalWA({
    numero_contrato:    '(pendiente)',
    fecha_contrato:     fecha,
    comprador,
    pais_destino:       '',
    acopio_codigo:      _ventaLotes.map(x => x.codigo).join(', '),
    variedad:           _ventaLotes.map(x => x.variedad).filter(Boolean).join(', '),
    cantidad_kg:        _ventaLotes.reduce((s, x) => s + (x.cantidad || 0), 0),
    precio_usd_kg:      l?.precio || 0,
    total_usd:          totalUSD,
    total_local:        totalUSD * tasa,
    incoterm,
    estado:             'borrador',
    telefono_comprador: '',
  });
}

function abrirModalWA(venta) {
  const estados = { borrador:'Borrador', confirmado:'Confirmado', en_proceso:'En proceso', entregado:'Entregado', cancelado:'Cancelado' };
  const msg =
`🌿 *COMPROBANTE DE VENTA*
━━━━━━━━━━━━━━━━━━━━━━━━
📄 Contrato:   ${venta.numero_contrato}
📅 Fecha:      ${venta.fecha_contrato}
━━━━━━━━━━━━━━━━━━━━━━━━
👤 Comprador:  ${venta.comprador}${venta.pais_destino ? ' — ' + venta.pais_destino : ''}
📦 Lote:       ${venta.acopio_codigo}${venta.variedad ? ' (' + venta.variedad + ')' : ''}
━━━━━━━━━━━━━━━━━━━━━━━━
⚖️  Cantidad:   ${parseFloat(venta.cantidad_kg).toLocaleString('es-PE', {minimumFractionDigits:2})} kg
💵 Precio:     S/ ${parseFloat(venta.precio_usd_kg).toFixed(3)} / kg
💰 Total S/:   S/ ${parseFloat(venta.total_usd).toLocaleString('es-PE', {minimumFractionDigits:2})}
🏦 Total PEN:  S/ ${parseFloat(venta.total_local).toLocaleString('es-PE', {minimumFractionDigits:2})}
🚢 Incoterm:   ${venta.incoterm}
📌 Estado:     ${estados[venta.estado] || venta.estado}
━━━━━━━━━━━━━━━━━━━━━━━━
_Sistema de Trazabilidad Café_`;

  document.getElementById('wa-preview').value = msg;

  // Pre-llenar teléfono del comprador si existe
  const tel = (venta.telefono_comprador || '').replace(/\D/g, '');
  document.getElementById('wa-telefono').value = tel.length > 6 ? tel : '';

  document.getElementById('modal-wa').classList.add('open');
}

function cerrarModalWA() {
  document.getElementById('modal-wa').classList.remove('open');
}

function abrirWhatsApp() {
  const prefijo = document.getElementById('wa-prefijo').value;
  const tel     = document.getElementById('wa-telefono').value.replace(/\D/g, '');
  const msg     = document.getElementById('wa-preview').value;

  if (!tel) { toast('Ingresa el número de WhatsApp', true); return; }

  const url = `https://wa.me/${prefijo}${tel}?text=${encodeURIComponent(msg)}`;
  window.open(url, '_blank');
}

/* ══════════════════════════════════════════════════════════
   PERFIL DE USUARIO
══════════════════════════════════════════════════════════ */

async function cargarPerfil() {
  try {
    const res  = await fetch('perfil-api.php?action=info');
    const data = await res.json();
    if (!data.success) return;

    const el = id => document.getElementById(id);
    if (el('perfil-nombre-display')) el('perfil-nombre-display').textContent = data.nombre;
    if (el('perfil-email-display'))  el('perfil-email-display').textContent  = data.email  || 'Sin correo registrado';
    if (el('perfil-rol-display'))    el('perfil-rol-display').textContent    = data.rol;
  } catch (e) {
    console.error('Error cargando perfil:', e);
  }
}

function editarPerfil() {
  const nombre = document.getElementById('perfil-nombre-display')?.textContent ?? '';
  const email  = document.getElementById('perfil-email-display')?.textContent  ?? '';
  document.getElementById('inp-perfil-nombre').value = nombre === '—' ? '' : nombre;
  document.getElementById('inp-perfil-email').value  = email  === 'Sin correo registrado' ? '' : email;
  toggleForm('form-perfil');
}

async function guardarPerfil() {
  const nombre = document.getElementById('inp-perfil-nombre').value.trim();
  const email  = document.getElementById('inp-perfil-email').value.trim();

  if (!nombre) { toast('El nombre no puede estar vacío', true); return; }

  try {
    const res  = await fetch('perfil-api.php?action=update_perfil', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ nombre, email }),
    });
    const data = await res.json();
    if (!data.success) { toast(data.error || 'Error al guardar', true); return; }

    toast('Perfil actualizado correctamente');
    toggleForm('form-perfil');

    // Refrescar tarjeta y nombre en el sidebar
    document.getElementById('perfil-nombre-display').textContent = nombre;
    document.getElementById('perfil-email-display').textContent  = email || 'Sin correo registrado';
    const sidebarName = document.getElementById('sidebar-user-name');
    if (sidebarName) sidebarName.textContent = nombre;
  } catch (e) {
    toast('Error de red al guardar', true);
  }
}

function abrirCambioPassword() {
  ['inp-pw-actual', 'inp-pw-nueva', 'inp-pw-confirma'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const bar   = document.getElementById('pw-strength-bar');
  const label = document.getElementById('pw-strength-label');
  if (bar)   { bar.style.width = '0'; bar.style.background = ''; }
  if (label) label.textContent = '';
  toggleForm('form-password');
}

async function guardarPassword() {
  const actual   = document.getElementById('inp-pw-actual').value;
  const nueva    = document.getElementById('inp-pw-nueva').value;
  const confirma = document.getElementById('inp-pw-confirma').value;

  if (!actual || !nueva || !confirma) { toast('Todos los campos son requeridos', true); return; }
  if (nueva.length < 8) { toast('La nueva contraseña debe tener al menos 8 caracteres', true); return; }
  if (nueva !== confirma) { toast('Las contraseñas nuevas no coinciden', true); return; }

  try {
    const res  = await fetch('perfil-api.php?action=change_password', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ actual, nueva, confirma }),
    });
    const data = await res.json();
    if (!data.success) { toast(data.error || 'Error al cambiar contraseña', true); return; }

    toast('Contraseña actualizada correctamente');
    toggleForm('form-password');
  } catch (e) {
    toast('Error de red al cambiar contraseña', true);
  }
}

function checkPwStrength(val) {
  let score = 0;
  if (val.length >= 8)            score++;
  if (val.length >= 12)           score++;
  if (/[A-Z]/.test(val))          score++;
  if (/[0-9]/.test(val))          score++;
  if (/[^A-Za-z0-9]/.test(val))   score++;

  const bar   = document.getElementById('pw-strength-bar');
  const label = document.getElementById('pw-strength-label');
  if (!bar) return;

  const levels = [
    { color: '#e74c3c', width: '20%', text: 'Muy débil'  },
    { color: '#e67e22', width: '40%', text: 'Débil'       },
    { color: '#f1c40f', width: '60%', text: 'Regular'     },
    { color: '#2ecc71', width: '80%', text: 'Buena'       },
    { color: '#00704A', width: '100%',text: 'Muy segura'  },
  ];
  const lvl = levels[Math.max(0, score - 1)] || levels[0];
  bar.style.width      = val ? lvl.width : '0';
  bar.style.background = val ? lvl.color : '';
  if (label) label.textContent = val ? lvl.text : '';
}

function togglePwInput(inputId, btn) {
  const input  = document.getElementById(inputId);
  if (!input) return;
  const hidden = input.type === 'password';
  input.type   = hidden ? 'text' : 'password';
  btn.textContent = hidden ? '🙈' : '👁';
}

/* ══════════════════════════════════════════════════════════
   CERRAR SESIÓN
══════════════════════════════════════════════════════════ */
function cerrarSesion() {
  if (!confirm('¿Deseas cerrar sesión?')) return;
  localStorage.clear();
  sessionStorage.clear();
  window.location.href = 'logout.php';
}

/* ════════════════════════════════════════════════════════════
   CAPACITACIÓN
════════════════════════════════════════════════════════════ */
let _capEditId = null;

function setCapFiltro(btn, estado) {
  document.querySelectorAll('.cap-filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('f-cap-estado').value = estado;
  cargarCapacitaciones();
}

function mostrarCapTab(tabId, btn) {
  ['cap-tab-lista', 'cap-tab-manual'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = id === tabId ? '' : 'none';
  });
  document.querySelectorAll('#capacitacion .tab-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  const btnNueva = document.getElementById('btn-nueva-cap');
  if (btnNueva) btnNueva.style.display = tabId === 'cap-tab-lista' ? '' : 'none';
}

async function cargarCapacitaciones() {
  const estado = document.getElementById('f-cap-estado')?.value || '';
  const qs = estado ? `estado=${estado}` : '';

  const [listRes, statsRes] = await Promise.all([
    api('GET', `/capacitaciones${qs ? '?' + qs : ''}`),
    api('GET', '/capacitaciones/estadisticas'),
  ]);

  const s = statsRes.data || {};
  document.getElementById('cap-k-total').textContent        = s.total           || 0;
  document.getElementById('cap-k-completadas').textContent  = s.completadas      || 0;
  document.getElementById('cap-k-participantes').textContent= s.total_participantes || 0;
  document.getElementById('cap-k-curso').textContent        = s.en_curso         || 0;

  const rows = listRes.data || [];
  const tbody = document.getElementById('tbl-capacitaciones');
  tbody.innerHTML = rows.length ? rows.map(c => `
    <tr class="traz-row" onclick="verCapacitacion(${c.id})" title="Ver detalle">
      <td class="fw-bold">${c.titulo}</td>
      <td>${c.instructor || '—'}</td>
      <td>${c.organizacion || '—'}</td>
      <td>${c.fecha_inicio || '—'}</td>
      <td>${c.lugar || '—'}</td>
      <td>${capModalidadBadge(c.modalidad)}</td>
      <td class="text-center">${c.total_participantes || 0}</td>
      <td>${capEstadoBadge(c.estado)}</td>
    </tr>`).join('') : emptyRow(8);
}

function capEstadoBadge(e) {
  const m = { programado:'info', en_curso:'warning', completado:'specialty', cancelado:'danger' };
  const l = { programado:'Programado', en_curso:'En Curso', completado:'Completado', cancelado:'Cancelado' };
  return `<span class="badge badge-${m[e]||'info'}">${l[e]||e}</span>`;
}
function capModalidadLabel(m) {
  return { presencial:'Presencial', virtual:'Virtual', mixto:'Mixto' }[m] || m;
}
function capModalidadBadge(m) {
  const cls = { presencial:'info', virtual:'premium', mixto:'comercial' };
  return `<span class="badge badge-${cls[m]||'info'}">${capModalidadLabel(m)}</span>`;
}

function abrirFormCapacitacion() {
  _capEditId = null;
  document.getElementById('cap-modal-title').textContent = '🎓 Nueva Capacitación';
  document.getElementById('btn-guardar-cap').textContent = '💾 Guardar';
  ['cap-titulo','cap-instructor','cap-org','cap-inicio','cap-fin','cap-lugar','cap-notas','cap-max']
    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  document.getElementById('cap-modalidad').value = 'presencial';
  document.getElementById('cap-estado').value    = 'programado';
  document.getElementById('cap-inicio').value    = new Date().toISOString().split('T')[0];
  toggleForm('form-cap');
}

async function editarCapacitacion(id) {
  const res = await api('GET', `/capacitaciones/${id}`);
  const c = res.data; if (!c) return;
  _capEditId = id;
  document.getElementById('cap-modal-title').textContent = '✏️ Editar Capacitación';
  document.getElementById('cap-titulo').value      = c.titulo || '';
  document.getElementById('cap-instructor').value  = c.instructor || '';
  document.getElementById('cap-org').value         = c.organizacion || '';
  document.getElementById('cap-inicio').value      = c.fecha_inicio?.slice(0,10) || '';
  document.getElementById('cap-fin').value         = c.fecha_fin?.slice(0,10) || '';
  document.getElementById('cap-lugar').value       = c.lugar || '';
  document.getElementById('cap-modalidad').value   = c.modalidad || 'presencial';
  document.getElementById('cap-estado').value      = c.estado || 'programado';
  document.getElementById('cap-max').value         = c.max_participantes || '';
  document.getElementById('cap-notas').value       = c.notas || '';
  toggleForm('form-cap');
}

async function guardarCapacitacion() {
  const titulo = document.getElementById('cap-titulo').value.trim();
  const inicio = document.getElementById('cap-inicio').value;
  if (!titulo) { toast('El título es requerido', true); return; }
  if (!inicio) { toast('La fecha de inicio es requerida', true); return; }

  const data = {
    titulo,
    descripcion:      document.getElementById('cap-notas').value || null,
    instructor:       document.getElementById('cap-instructor').value || null,
    organizacion:     document.getElementById('cap-org').value || null,
    fecha_inicio:     inicio,
    fecha_fin:        document.getElementById('cap-fin').value || null,
    lugar:            document.getElementById('cap-lugar').value || null,
    modalidad:        document.getElementById('cap-modalidad').value,
    estado:           document.getElementById('cap-estado').value,
    max_participantes:parseInt(document.getElementById('cap-max').value) || null,
  };

  const res = _capEditId
    ? await api('PUT', `/capacitaciones/${_capEditId}`, data)
    : await api('POST', '/capacitaciones', data);

  if (res.success) {
    toast(_capEditId ? 'Capacitación actualizada' : 'Capacitación creada');
    toggleForm('form-cap');
    cargarCapacitaciones();
  } else {
    toast(res.error || 'Error al guardar', true);
  }
}

async function verCapacitacion(id) {
  const res = await api('GET', `/capacitaciones/${id}`);
  const c = res.data; if (!c) return;

  document.getElementById('cap-lista').style.display    = 'none';
  document.getElementById('cap-detalle').style.display  = '';

  const partsHtml = (c.participantes || []).length
    ? c.participantes.map(p => `
        <tr>
          <td>${p.cliente_nombre || p.nombre_participante || '—'}</td>
          <td>${p.cargo || '—'}</td>
          <td>${p.asistio ? '✅' : '❌'}</td>
          <td>${p.certificado_emitido ? '✅' : '—'}</td>
        </tr>`).join('')
    : `<tr><td colspan="4" class="text-center text-muted">Sin participantes registrados</td></tr>`;

  document.getElementById('cap-detalle-body').innerHTML = `
    <div class="det-header" style="background:#1E3A4A;color:#fff;padding:16px 20px;border-radius:8px 8px 0 0;margin-bottom:0">
      <div style="font-size:1.1rem;font-weight:700">${c.titulo}</div>
      <div style="font-size:.82rem;opacity:.8;margin-top:4px">${c.organizacion||''} · ${c.lugar||''}</div>
    </div>
    <div style="background:#fff;border:1px solid var(--border);border-top:none;padding:16px;border-radius:0 0 8px 8px;margin-bottom:20px">
      <div class="form-grid cols-3" style="gap:10px 20px">
        <div><span class="text-muted" style="font-size:.75rem">INSTRUCTOR</span><div>${c.instructor||'—'}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">FECHA INICIO</span><div>${c.fecha_inicio||'—'}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">FECHA FIN</span><div>${c.fecha_fin||'—'}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">MODALIDAD</span><div>${capModalidadLabel(c.modalidad)}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">ESTADO</span><div>${capEstadoBadge(c.estado)}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">PARTICIPANTES</span><div>${c.total_participantes||0}</div></div>
      </div>
      ${c.notas ? `<div style="margin-top:12px;font-size:.85rem;color:var(--text-muted)">${c.notas}</div>` : ''}
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <strong>Participantes</strong>
      <button class="btn btn-ghost btn-sm" onclick="editarCapacitacion(${id})">✏️ Editar</button>
      <button class="btn btn-primary btn-sm" onclick="_capDetId=${id};limpiarFormPart();toggleForm('form-part')">+ Participante</button>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Nombre</th><th>Cargo</th><th>Asistió</th><th>Certificado</th></tr></thead>
        <tbody>${partsHtml}</tbody>
      </table>
    </div>`;
}
let _capDetId = null;

function volverCapLista() {
  document.getElementById('cap-detalle').style.display = 'none';
  document.getElementById('cap-lista').style.display   = '';
}

function limpiarFormPart() {
  ['part-nombre','part-cargo'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  const asistio = document.getElementById('part-asistio');
  const cert    = document.getElementById('part-cert');
  if (asistio) asistio.checked = true;
  if (cert)    cert.checked    = false;
}

async function guardarParticipante() {
  if (!_capDetId) return;
  const data = {
    nombre_participante: document.getElementById('part-nombre').value.trim() || null,
    cargo:               document.getElementById('part-cargo').value.trim()  || null,
    asistio:             document.getElementById('part-asistio').checked,
    certificado_emitido: document.getElementById('part-cert').checked,
  };
  const res = await api('POST', `/capacitaciones/${_capDetId}/participantes`, data);
  if (res.success) {
    toast('Participante agregado');
    toggleForm('form-part');
    verCapacitacion(_capDetId);
  } else {
    toast(res.error || 'Error al agregar', true);
  }
}

/* ════════════════════════════════════════════════════════════
   AUDITORÍA Y SEGURIDAD
════════════════════════════════════════════════════════════ */
let _audEditId = null;
let _audDetId  = null;

async function cargarAuditorias() {
  const tipo   = document.getElementById('f-aud-tipo')?.value   || '';
  const estado = document.getElementById('f-aud-estado')?.value || '';
  const qs     = new URLSearchParams(Object.fromEntries(
    Object.entries({ tipo, estado }).filter(([,v]) => v)
  )).toString();

  const [listRes, statsRes] = await Promise.all([
    api('GET', `/auditorias${qs ? '?' + qs : ''}`),
    api('GET', '/auditorias/estadisticas'),
  ]);

  const s = statsRes.data || {};
  document.getElementById('aud-k-total').textContent       = s.total             || 0;
  document.getElementById('aud-k-completadas').textContent = s.completadas        || 0;
  document.getElementById('aud-k-hallazgos').textContent   = s.hallazgos_abiertos || 0;
  document.getElementById('aud-k-aprobadas').textContent   = s.aprobadas          || 0;

  const rows = listRes.data || [];
  const tbody = document.getElementById('tbl-auditorias');
  tbody.innerHTML = rows.length ? rows.map(a => `
    <tr>
      <td class="mono">${a.codigo||'—'}</td>
      <td>${audTipoBadge(a.tipo)}</td>
      <td class="fw-bold">${a.titulo}</td>
      <td>${a.auditor||'—'}</td>
      <td>${a.fecha_auditoria||'—'}</td>
      <td class="text-center">
        <span style="color:${(a.hallazgos_abiertos||0)>0?'var(--danger)':'var(--verde)'}">
          ${a.hallazgos_abiertos||0} / ${a.total_hallazgos||0}
        </span>
      </td>
      <td>${audResultadoBadge(a.resultado)}</td>
      <td>${audEstadoBadge(a.estado)}</td>
      <td style="white-space:nowrap">
        <button class="btn-icon" onclick="verAuditoria(${a.id})" title="Ver detalle">👁️</button>
        <button class="btn-icon" onclick="editarAuditoria(${a.id})" title="Editar">✏️</button>
      </td>
    </tr>`).join('') : emptyRow(9);
}

function audTipoBadge(t) {
  const m = { interna:'default', externa:'info', certificacion:'success', inocuidad:'warning' };
  const l = { interna:'Interna', externa:'Externa', certificacion:'Certificación', inocuidad:'Inocuidad' };
  return `<span class="badge badge-${m[t]||'default'}">${l[t]||t}</span>`;
}
function audEstadoBadge(e) {
  const m = { programada:'info', en_proceso:'warning', completada:'success', cancelada:'danger' };
  const l = { programada:'Programada', en_proceso:'En Proceso', completada:'Completada', cancelada:'Cancelada' };
  return `<span class="badge badge-${m[e]||'info'}">${l[e]||e}</span>`;
}
function audResultadoBadge(r) {
  if (!r) return '<span class="text-muted">—</span>';
  const m = { aprobada:'success', observada:'warning', rechazada:'danger' };
  const l = { aprobada:'Aprobada', observada:'Observada', rechazada:'Rechazada' };
  return `<span class="badge badge-${m[r]||'default'}">${l[r]||r}</span>`;
}
function hallazgoTipoBadge(t) {
  const m = { no_conformidad_mayor:'danger', no_conformidad_menor:'warning', observacion:'info', oportunidad_mejora:'success' };
  const l = { no_conformidad_mayor:'NC Mayor', no_conformidad_menor:'NC Menor', observacion:'Observación', oportunidad_mejora:'Oportunidad' };
  return `<span class="badge badge-${m[t]||'info'}">${l[t]||t}</span>`;
}

function abrirFormAuditoria() {
  _audEditId = null;
  document.getElementById('aud-modal-title').textContent = '🛡️ Nueva Auditoría';
  document.getElementById('btn-guardar-aud').textContent = '💾 Guardar';
  ['aud-titulo','aud-auditor','aud-organismo','aud-fecha','aud-prox','aud-puntaje','aud-notas']
    .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  document.getElementById('aud-tipo').value      = 'interna';
  document.getElementById('aud-estado').value    = 'programada';
  document.getElementById('aud-resultado').value = '';
  document.getElementById('aud-fecha').value     = new Date().toISOString().split('T')[0];
  toggleForm('form-aud');
}

async function editarAuditoria(id) {
  const res = await api('GET', `/auditorias/${id}`);
  const a = res.data; if (!a) return;
  _audEditId = id;
  document.getElementById('aud-modal-title').textContent = '✏️ Editar Auditoría';
  document.getElementById('aud-titulo').value     = a.titulo || '';
  document.getElementById('aud-tipo').value       = a.tipo || 'interna';
  document.getElementById('aud-auditor').value    = a.auditor || '';
  document.getElementById('aud-organismo').value  = a.organismo || '';
  document.getElementById('aud-fecha').value      = a.fecha_auditoria?.slice(0,10) || '';
  document.getElementById('aud-prox').value       = a.fecha_proxima?.slice(0,10) || '';
  document.getElementById('aud-estado').value     = a.estado || 'programada';
  document.getElementById('aud-resultado').value  = a.resultado || '';
  document.getElementById('aud-puntaje').value    = a.puntaje || '';
  document.getElementById('aud-notas').value      = a.notas || '';
  toggleForm('form-aud');
}

async function guardarAuditoria() {
  const titulo = document.getElementById('aud-titulo').value.trim();
  const fecha  = document.getElementById('aud-fecha').value;
  if (!titulo) { toast('El título es requerido', true); return; }
  if (!fecha)  { toast('La fecha es requerida', true); return; }

  const data = {
    titulo,
    tipo:            document.getElementById('aud-tipo').value,
    auditor:         document.getElementById('aud-auditor').value || null,
    organismo:       document.getElementById('aud-organismo').value || null,
    fecha_auditoria: fecha,
    fecha_proxima:   document.getElementById('aud-prox').value || null,
    estado:          document.getElementById('aud-estado').value,
    resultado:       document.getElementById('aud-resultado').value || null,
    puntaje:         parseFloat(document.getElementById('aud-puntaje').value) || null,
    descripcion:     document.getElementById('aud-notas').value || null,
  };

  const res = _audEditId
    ? await api('PUT', `/auditorias/${_audEditId}`, data)
    : await api('POST', '/auditorias', data);

  if (res.success) {
    toast(_audEditId ? 'Auditoría actualizada' : 'Auditoría creada');
    toggleForm('form-aud');
    cargarAuditorias();
  } else {
    toast(res.error || 'Error al guardar', true);
  }
}

async function verAuditoria(id) {
  const res = await api('GET', `/auditorias/${id}`);
  const a = res.data; if (!a) return;
  _audDetId = id;

  document.getElementById('aud-lista').style.display   = 'none';
  document.getElementById('aud-detalle').style.display = '';

  const hallHtml = (a.hallazgos || []).length
    ? a.hallazgos.map(h => `
        <tr>
          <td>${hallazgoTipoBadge(h.tipo)}</td>
          <td>${h.descripcion}</td>
          <td>${h.area||'—'}</td>
          <td>${h.responsable||'—'}</td>
          <td>${h.fecha_limite||'—'}</td>
          <td>${audEstadoBadge(h.estado)}</td>
          <td><button class="btn-icon" onclick="cerrarHallazgo(${h.id})" title="Marcar cerrado">✅</button></td>
        </tr>`).join('')
    : `<tr><td colspan="7" class="text-center text-muted">Sin hallazgos registrados</td></tr>`;

  document.getElementById('aud-detalle-body').innerHTML = `
    <div style="background:#1E3A4A;color:#fff;padding:16px 20px;border-radius:8px 8px 0 0">
      <div style="font-size:.7rem;opacity:.7;letter-spacing:.1em">${a.codigo||''}</div>
      <div style="font-size:1.1rem;font-weight:700;margin-top:2px">${a.titulo}</div>
      <div style="font-size:.82rem;opacity:.8;margin-top:4px">${a.auditor||''} · ${a.organismo||''}</div>
    </div>
    <div style="background:#fff;border:1px solid var(--border);border-top:none;padding:16px;border-radius:0 0 8px 8px;margin-bottom:20px">
      <div class="form-grid cols-3" style="gap:10px 20px">
        <div><span class="text-muted" style="font-size:.75rem">TIPO</span><div>${audTipoBadge(a.tipo)}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">FECHA</span><div>${a.fecha_auditoria||'—'}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">PRÓXIMA</span><div>${a.fecha_proxima||'—'}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">ESTADO</span><div>${audEstadoBadge(a.estado)}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">RESULTADO</span><div>${audResultadoBadge(a.resultado)}</div></div>
        <div><span class="text-muted" style="font-size:.75rem">PUNTAJE</span><div>${a.puntaje ? a.puntaje + ' / 100' : '—'}</div></div>
      </div>
      ${a.notas ? `<div style="margin-top:12px;font-size:.85rem;color:var(--text-muted)">${a.notas}</div>` : ''}
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <strong>Hallazgos <span style="color:${(a.hallazgos_abiertos||0)>0?'var(--danger)':'var(--verde)'}">(${a.hallazgos_abiertos||0} abiertos)</span></strong>
      <button class="btn btn-primary btn-sm" onclick="toggleForm('form-hall')">+ Agregar Hallazgo</button>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Tipo</th><th>Descripción</th><th>Área</th><th>Responsable</th><th>Fecha Límite</th><th>Estado</th><th></th></tr></thead>
        <tbody>${hallHtml}</tbody>
      </table>
    </div>`;
}

function volverAudLista() {
  document.getElementById('aud-detalle').style.display = 'none';
  document.getElementById('aud-lista').style.display   = '';
}

async function guardarHallazgo() {
  if (!_audDetId) return;
  const desc = document.getElementById('hall-desc').value.trim();
  if (!desc) { toast('La descripción es requerida', true); return; }

  const data = {
    tipo:              document.getElementById('hall-tipo').value,
    descripcion:       desc,
    area:              document.getElementById('hall-area').value || null,
    responsable:       document.getElementById('hall-responsable').value || null,
    fecha_limite:      document.getElementById('hall-limite').value || null,
    estado:            document.getElementById('hall-estado').value,
    accion_correctiva: document.getElementById('hall-accion').value || null,
  };

  const res = await api('POST', `/auditorias/${_audDetId}/hallazgos`, data);
  if (res.success) {
    toast('Hallazgo registrado');
    toggleForm('form-hall');
    ['hall-desc','hall-area','hall-responsable','hall-limite','hall-accion']
      .forEach(id => { document.getElementById(id).value = ''; });
    verAuditoria(_audDetId);
  } else {
    toast(res.error || 'Error al guardar', true);
  }
}

async function cerrarHallazgo(id) {
  const res = await api('PUT', `/auditorias/hallazgos/${id}`, {
    estado: 'cerrado',
    fecha_cierre: new Date().toISOString().split('T')[0],
  });
  if (res.success) { toast('Hallazgo cerrado'); verAuditoria(_audDetId); }
  else toast(res.error || 'Error', true);
}

function mostrarAudTab(tabId, btn) {
  ['aud-tab-lista','aud-tab-log'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = id === tabId ? '' : 'none';
  });
  document.querySelectorAll('#aud-tab-nav .tab-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
}

async function cargarLogSeguridad() {
  const res = await api('GET', '/seguridad/log?per_page=100');
  const rows = res.data || [];
  const tbody = document.getElementById('tbl-seg-log');
  tbody.innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td class="mono" style="white-space:nowrap">${r.fecha?.replace('T',' ').slice(0,19)||'—'}</td>
      <td>${r.usuario||'—'}</td>
      <td>${r.modulo||'—'}</td>
      <td>${r.accion}</td>
      <td class="text-muted">${r.ip_address||'—'}</td>
    </tr>`).join('') : emptyRow(5);
}
