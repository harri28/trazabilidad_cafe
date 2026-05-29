<?php
/**
 * perfil-api.php — Gestión del perfil del usuario autenticado
 *
 * Requiere sesión activa. Acciones:
 *   GET  ?action=info            → datos del usuario actual
 *   POST ?action=update_perfil   → actualizar nombre y email
 *   POST ?action=change_password → cambiar contraseña
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$action    = $_GET['action'] ?? '';
$usuario   = $_SESSION['usuario'];
$usersFile = __DIR__ . '/config/users.php';

// ── Utilidades ────────────────────────────────────────────────────────────────

function loadUsers(string $file): array
{
    return require $file;
}

function saveUsers(string $file, array $usuarios): void
{
    $php = <<<'PHP'
<?php
// Usuarios del sistema — contraseñas almacenadas con bcrypt (password_hash)
// Para agregar usuarios: php -r "echo password_hash('nueva_clave', PASSWORD_DEFAULT);"
// El campo 'email' se usa para recuperación de contraseña vía SMTP.
return PHP;
    $php .= ' ' . var_export($usuarios, true) . ";\n";
    file_put_contents($file, $php, LOCK_EX);
}

function ok(array $extra = []): void
{
    echo json_encode(['success' => true] + $extra);
    exit;
}

function err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// ── GET /perfil-api.php?action=info ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'info') {
    $usuarios = loadUsers($usersFile);
    $u = $usuarios[$usuario] ?? [];

    ok([
        'usuario' => $usuario,
        'nombre'  => $u['nombre'] ?? $usuario,
        'email'   => $u['email']  ?? '',
        'rol'     => $u['rol']    ?? 'Usuario',
    ]);
}

// ── POST /perfil-api.php?action=update_perfil ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_perfil') {
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $nombre = trim($data['nombre'] ?? '');
    $email  = trim($data['email']  ?? '');

    if ($nombre === '') err('El nombre no puede estar vacío');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        err('El correo electrónico no tiene un formato válido');
    }

    $usuarios = loadUsers($usersFile);
    if (!isset($usuarios[$usuario])) err('Usuario no encontrado', 404);

    $usuarios[$usuario]['nombre'] = $nombre;
    $usuarios[$usuario]['email']  = $email;
    saveUsers($usersFile, $usuarios);

    // Refrescar sesión
    $_SESSION['nombre'] = $nombre;
    $_SESSION['email']  = $email;

    ok([
        'nombre' => $nombre,
        'email'  => $email,
        'rol'    => $usuarios[$usuario]['rol'] ?? 'Usuario',
    ]);
}

// ── POST /perfil-api.php?action=change_password ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_password') {
    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $actual   = $data['actual']   ?? '';
    $nueva    = $data['nueva']    ?? '';
    $confirma = $data['confirma'] ?? '';

    if ($actual === '' || $nueva === '' || $confirma === '') err('Todos los campos son requeridos');
    if (strlen($nueva) < 8)   err('La nueva contraseña debe tener al menos 8 caracteres');
    if ($nueva !== $confirma) err('Las contraseñas nuevas no coinciden');

    $usuarios = loadUsers($usersFile);
    if (!isset($usuarios[$usuario])) err('Usuario no encontrado', 404);

    if (!password_verify($actual, $usuarios[$usuario]['password'])) {
        err('La contraseña actual es incorrecta');
    }

    $usuarios[$usuario]['password'] = password_hash($nueva, PASSWORD_DEFAULT);
    saveUsers($usersFile, $usuarios);

    ok(['message' => 'Contraseña actualizada correctamente']);
}

err('Acción no reconocida');
