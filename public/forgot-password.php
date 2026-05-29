<?php
/**
 * forgot-password.php
 * Paso 1: el usuario ingresa su nombre de usuario → se genera token y se envía email.
 * Paso 2 (via GET ?token=): muestra el formulario para establecer nueva contraseña.
 */
session_start();
if (!empty($_SESSION['usuario'])) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../api/config/MailService.php';

// ── Helpers de tokens ─────────────────────────────────────────────────────────
$tokenFile = __DIR__ . '/storage/reset_tokens.json';

function loadTokens(string $file): array
{
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function saveTokens(string $file, array $tokens): void
{
    file_put_contents($file, json_encode($tokens, JSON_PRETTY_PRINT), LOCK_EX);
}

function purgeExpiredTokens(array $tokens): array
{
    $now = time();
    return array_filter($tokens, fn($t) => $t['expires'] > $now);
}

// ── Lógica POST: solicitar reset ──────────────────────────────────────────────
$mensaje = '';
$error   = '';
$step    = 'form'; // 'form' | 'sent' | 'reset' | 'done'

// ── Paso 2 — mostrar formulario de nueva contraseña (GET ?token=) ─────────────
$token = trim($_GET['token'] ?? '');
if ($token !== '') {
    $tokens = purgeExpiredTokens(loadTokens($tokenFile));

    if (!isset($tokens[$token])) {
        $error = 'El enlace de recuperación no es válido o ha expirado. Solicita uno nuevo.';
        $step  = 'form';
    } else {
        $step = 'reset';

        // POST del formulario de nueva contraseña
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_password'])) {
            $nueva   = $_POST['nueva_password'] ?? '';
            $confirma= $_POST['confirmar_password'] ?? '';

            if (strlen($nueva) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($nueva !== $confirma) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                // Actualizar users.php
                $username  = $tokens[$token]['username'];
                $usersFile = __DIR__ . '/config/users.php';
                $usuarios  = require $usersFile;

                if (!isset($usuarios[$username])) {
                    $error = 'Usuario no encontrado. Contacta al administrador.';
                } else {
                    $usuarios[$username]['password'] = password_hash($nueva, PASSWORD_DEFAULT);

                    // Escribir el archivo de forma segura
                    $php = "<?php\n// Usuarios del sistema — contraseñas almacenadas con bcrypt (password_hash)\n// Para agregar usuarios: php -r \"echo password_hash('nueva_clave', PASSWORD_DEFAULT);\"\nreturn " . var_export($usuarios, true) . ";\n";
                    file_put_contents($usersFile, $php, LOCK_EX);

                    // Invalidar token
                    $tokens = loadTokens($tokenFile);
                    unset($tokens[$token]);
                    saveTokens($tokenFile, $tokens);

                    $step   = 'done';
                    $mensaje = 'Tu contraseña fue actualizada correctamente. Ya puedes iniciar sesión.';
                }
            }
        }
    }
}

// ── POST: solicitar enlace de recuperación ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['nueva_password']) && $step === 'form') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $usuarios = require __DIR__ . '/config/users.php';

    // Respuesta deliberadamente ambigua para no revelar si el usuario existe
    $mensaje = 'Si el usuario existe en el sistema, se enviará un correo con el enlace de recuperación.';
    $step    = 'sent';

    if (isset($usuarios[$usuario]) && !empty($usuarios[$usuario]['email'])) {
        // Generar token seguro
        $newToken = bin2hex(random_bytes(32));

        $tokens = purgeExpiredTokens(loadTokens($tokenFile));
        // Eliminar tokens previos del mismo usuario
        foreach ($tokens as $k => $v) {
            if ($v['username'] === $usuario) unset($tokens[$k]);
        }
        $tokens[$newToken] = [
            'username' => $usuario,
            'expires'  => time() + 3600, // 1 hora
        ];
        saveTokens($tokenFile, $tokens);

        // Enviar email
        $mailer = new MailService();
        $enviado = $mailer->sendPasswordReset(
            $usuarios[$usuario]['email'],
            $usuarios[$usuario]['nombre'],
            $newToken
        );

        if (!$enviado) {
            error_log("[forgot-password] Fallo al enviar email a {$usuarios[$usuario]['email']}");
        }
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña — Trazabilidad Café</title>
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
    max-width: 400px;
    box-shadow: 0 8px 40px rgba(0,0,0,.3);
  }
  .logo { text-align: center; margin-bottom: 28px; }
  .logo .icon { font-size: 2.2rem; }
  .logo h1 { font-size: 1.2rem; color: #1E3932; margin-top: 6px; font-weight: 700; }
  .logo p  { font-size: .78rem; color: #7B9E94; margin-top: 2px; }
  label { display: block; font-size: .8rem; font-weight: 600; color: #1E3932; margin-bottom: 5px; }
  input[type=text], input[type=password] {
    width: 100%; padding: 10px 12px; border: 1px solid #d0ddd9;
    border-radius: 8px; font-size: .9rem; outline: none; transition: border-color .2s;
    margin-bottom: 16px;
  }
  input:focus { border-color: #00704A; }
  .input-wrap { position: relative; margin-bottom: 16px; }
  .input-wrap input { margin-bottom: 0; padding-right: 42px; }
  .toggle-pw {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #7B9E94; font-size: 1.1rem; padding: 2px 4px; line-height: 1;
  }
  .toggle-pw:hover { color: #1E3932; }
  .btn {
    width: 100%; padding: 11px; background: #00704A; color: #fff;
    border: none; border-radius: 8px; font-size: .95rem; font-weight: 700;
    cursor: pointer; transition: background .2s;
  }
  .btn:hover { background: #1E3932; }
  .alert {
    border-radius: 8px; padding: 10px 14px; font-size: .84rem; margin-bottom: 18px; line-height: 1.5;
  }
  .alert.success { background: #eaf6f0; border: 1px solid #a3d9b9; color: #1a6642; }
  .alert.error   { background: #fff0f0; border: 1px solid #ffcccc; color: #c0392b; }
  .alert.info    { background: #e8f4fd; border: 1px solid #a8d4f0; color: #1a5276; }
  .link-back {
    display: block; text-align: center; margin-top: 18px; font-size: .82rem;
    color: #00704A; text-decoration: none;
  }
  .link-back:hover { color: #1E3932; text-decoration: underline; }
  .hint { font-size: .78rem; color: #7B9E94; margin-top: -10px; margin-bottom: 14px; }
  .password-strength { height: 4px; border-radius: 2px; margin-top: 6px; margin-bottom: 14px; background: #e0e0e0; }
  .password-strength .bar { height: 100%; border-radius: 2px; transition: width .3s, background .3s; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="icon">🔑</div>
    <h1>Recuperar contraseña</h1>
    <p>Trazabilidad Café</p>
  </div>

  <?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($step === 'form'): ?>
    <p style="font-size:.85rem;color:#4a6b5e;margin-bottom:20px;line-height:1.5;">
      Ingresa tu nombre de usuario y te enviaremos un enlace para restablecer tu contraseña.
    </p>
    <form method="POST" action="forgot-password.php">
      <label for="usuario">Nombre de usuario</label>
      <input type="text" id="usuario" name="usuario" placeholder="usuario"
             autocomplete="username" required
             value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
      <button type="submit" class="btn">Enviar enlace de recuperación</button>
    </form>

  <?php elseif ($step === 'sent'): ?>
    <div class="alert info">
      📧 <?= htmlspecialchars($mensaje) ?>
    </div>
    <p style="font-size:.82rem;color:#7B9E94;text-align:center;line-height:1.5;">
      Revisa tu bandeja de entrada y carpeta de spam. El enlace expira en 1 hora.
    </p>

  <?php elseif ($step === 'reset'): ?>
    <p style="font-size:.85rem;color:#4a6b5e;margin-bottom:20px;line-height:1.5;">
      Crea una nueva contraseña para tu cuenta. Debe tener al menos 8 caracteres.
    </p>
    <form method="POST" action="forgot-password.php?token=<?= urlencode($token) ?>">
      <label for="nueva_password">Nueva contraseña</label>
      <div class="input-wrap">
        <input type="password" id="nueva_password" name="nueva_password"
               placeholder="Mínimo 8 caracteres" required minlength="8"
               oninput="checkStrength(this.value)">
        <button type="button" class="toggle-pw" onclick="togglePw('nueva_password',this)">👁</button>
      </div>
      <div class="password-strength"><div class="bar" id="strengthBar"></div></div>

      <label for="confirmar_password">Confirmar contraseña</label>
      <div class="input-wrap">
        <input type="password" id="confirmar_password" name="confirmar_password"
               placeholder="Repite la contraseña" required minlength="8">
        <button type="button" class="toggle-pw" onclick="togglePw('confirmar_password',this)">👁</button>
      </div>

      <button type="submit" class="btn">Establecer nueva contraseña</button>
    </form>

  <?php elseif ($step === 'done'): ?>
    <div class="alert success">
      ✓ <?= htmlspecialchars($mensaje) ?>
    </div>
  <?php endif; ?>

  <a class="link-back" href="login.php">← Volver al inicio de sesión</a>
</div>

<script>
function togglePw(id, btn) {
  const input = document.getElementById(id);
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.textContent = isHidden ? '🙈' : '👁';
}
function checkStrength(val) {
  const bar = document.getElementById('strengthBar');
  if (!bar) return;
  let score = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const colors = ['#e74c3c','#e67e22','#f1c40f','#2ecc71','#00704A'];
  const widths  = ['20%','40%','60%','80%','100%'];
  bar.style.width      = widths[score - 1]  || '0%';
  bar.style.background = colors[score - 1]  || '#e0e0e0';
}
</script>
</body>
</html>
