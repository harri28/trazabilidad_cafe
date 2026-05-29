<?php
$results = [];

// 1. OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = ['OPcache', 'limpiado', true];
} else {
    $results[] = ['OPcache', 'no disponible', null];
}

// 2. Headers para forzar revalidación en el navegador
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 3. Versiones actuales de assets
$css_v = filemtime(__DIR__ . '/css/app.css');
$js_v  = filemtime(__DIR__ . '/js/app.js');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Limpiar Caché — Trazabilidad Café</title>
<style>
  body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
  .card { background: #fff; border-radius: 12px; padding: 36px 40px; box-shadow: 0 4px 24px rgba(0,0,0,.1); max-width: 480px; width: 100%; }
  h1 { font-size: 1.3rem; color: #1E3932; margin: 0 0 6px; }
  p  { color: #7B9E94; font-size: .9rem; margin: 0 0 24px; }
  .item { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: .9rem; }
  .item:last-child { border: none; }
  .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
  .ok  { background: #00704A; }
  .na  { background: #aaa; }
  .label { flex: 1; color: #1E3932; font-weight: 600; }
  .status { color: #7B9E94; font-size: .82rem; }
  .version { font-size: .75rem; color: #bbb; font-family: monospace; }
  .btn { display: inline-block; margin-top: 24px; padding: 11px 24px; background: #00704A; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: .9rem; }
  .btn:hover { background: #1E3932; }
</style>
</head>
<body>
<div class="card">
  <h1>🧹 Caché limpiada</h1>
  <p>Se ejecutaron las siguientes acciones:</p>

  <?php foreach ($results as [$label, $status, $ok]): ?>
  <div class="item">
    <div class="dot <?= $ok === true ? 'ok' : 'na' ?>"></div>
    <span class="label"><?= $label ?></span>
    <span class="status"><?= $status ?></span>
  </div>
  <?php endforeach; ?>

  <div class="item">
    <div class="dot ok"></div>
    <span class="label">CSS</span>
    <span class="version">v<?= $css_v ?> (<?= date('H:i:s', $css_v) ?>)</span>
  </div>
  <div class="item">
    <div class="dot ok"></div>
    <span class="label">JS</span>
    <span class="version">v<?= $js_v ?> (<?= date('H:i:s', $js_v) ?>)</span>
  </div>

  <a class="btn" href="index.php">← Volver al sistema</a>
</div>
</body>
</html>
