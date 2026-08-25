<?php
session_start();

// Si ya tiene sesión activa, ir directo al sistema
if (!empty($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $usuarios = require __DIR__ . '/config/users.php';

    if (isset($usuarios[$usuario]) && password_verify($password, $usuarios[$usuario]['password'])) {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuario;
        $_SESSION['nombre']  = $usuarios[$usuario]['nombre'];
        $_SESSION['rol']     = $usuarios[$usuario]['rol'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión — Trazabilidad Café</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', sans-serif;
    background: #1E3932;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .card {
    background: #fff;
    border-radius: 14px;
    padding: 40px 40px 36px;
    width: 100%;
    max-width: 380px;
    box-shadow: 0 8px 40px rgba(0,0,0,.3);
  }
  .logo {
    text-align: center;
    margin-bottom: 28px;
  }
  .logo .icon { font-size: 2.4rem; }
  .logo h1 { font-size: 1.25rem; color: #1E3932; margin-top: 6px; font-weight: 700; }
  .logo p  { font-size: .78rem; color: #7B9E94; margin-top: 2px; }
  label { display: block; font-size: .8rem; font-weight: 600; color: #1E3932; margin-bottom: 5px; }
  input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d0ddd9;
    border-radius: 8px;
    font-size: .9rem;
    outline: none;
    transition: border-color .2s;
    margin-bottom: 16px;
  }
  input:focus { border-color: #00704A; }
  .input-wrap { position: relative; margin-bottom: 16px; }
  .input-wrap input { margin-bottom: 0; padding-right: 42px; }
  .toggle-pw {
    position: absolute;
    right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #7B9E94; font-size: 1.1rem; padding: 2px 4px;
    line-height: 1;
  }
  .toggle-pw:hover { color: #1E3932; }
  .btn-login {
    width: 100%;
    padding: 11px;
    background: #00704A;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s;
  }
  .btn-login:hover { background: #1E3932; }
  .error {
    background: #fff0f0;
    border: 1px solid #ffcccc;
    color: #c0392b;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: .82rem;
    margin-bottom: 16px;
  }
  .link-forgot {
    display: block;
    text-align: right;
    margin-top: -8px;
    margin-bottom: 18px;
    font-size: .78rem;
    color: #00704A;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
  }
  .link-forgot:hover { color: #1E3932; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="icon js-logo-fallback">☕</div>
    <img class="js-logo-img" src="" alt="Logo" style="display:none;max-width:64px;max-height:64px;border-radius:10px;margin:0 auto">
    <h1>Trazabilidad Café</h1>
    <p>Sistema de Gestión</p>
  </div>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <label for="usuario">Usuario</label>
    <input type="text" id="usuario" name="usuario" placeholder="usuario"
           autocomplete="username" required
           value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">

    <label for="password">Contraseña</label>
    <div class="input-wrap">
      <input type="password" id="password" name="password" placeholder="••••••••"
             autocomplete="current-password" required>
      <button type="button" class="toggle-pw" onclick="togglePw()" title="Mostrar / ocultar contraseña" aria-label="Mostrar contraseña">👁</button>
    </div>

    <a href="forgot-password.php" class="link-forgot">¿Olvidaste tu contraseña?</a>

    <button type="submit" class="btn-login">Ingresar</button>
  </form>
</div>

<script>
(function () {
  const API = location.pathname.startsWith('/trazabilidad_cafe/') ? '/trazabilidad_cafe/api' : '/api';
  fetch(`${API}/configuracion/logo_url`)
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(data => {
      if (!data.valor) return;
      document.querySelectorAll('.js-logo-img').forEach(img => { img.src = data.valor; img.style.display = ''; });
      document.querySelectorAll('.js-logo-fallback').forEach(el => { el.style.display = 'none'; });
    })
    .catch(() => { /* sin logo configurado, se queda el emoji por defecto */ });
})();

function togglePw() {
  const input = document.getElementById('password');
  const btn   = document.querySelector('.toggle-pw');
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
    btn.title = 'Ocultar contraseña';
  } else {
    input.type = 'password';
    btn.textContent = '👁';
    btn.title = 'Mostrar contraseña';
  }
}
</script>
</body>
</html>
