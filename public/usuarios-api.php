<?php
/**
 * usuarios-api.php — CRUD de usuarios del sistema (protegido por sesión)
 *
 * GET  ?action=list                     → listar todos los usuarios (sin contraseñas)
 * POST ?action=create                   → crear nuevo usuario
 * POST ?action=update&username={user}   → actualizar nombre, email, rol y/o contraseña
 * POST ?action=delete&username={user}   → eliminar usuario (no puede ser la propia cuenta)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$me        = $_SESSION['usuario'];
$usersFile = __DIR__ . '/config/users.php';
$action    = $_GET['action'] ?? '';

$ROLES_VALIDOS = ['Administrador', 'Supervisor', 'Operador', 'Auditor'];

// ── Helpers ───────────────────────────────────────────────────────────────────

function loadUsers(string $file): array
{
    return require $file;
}

function saveUsers(string $file, array $usuarios): void
{
    $php  = "<?php\n";
    $php .= "// Usuarios del sistema — contraseñas almacenadas con bcrypt (password_hash)\n";
    $php .= "// Para agregar usuarios: php -r \"echo password_hash('nueva_clave', PASSWORD_DEFAULT);\"\n";
    $php .= "// El campo 'email' se usa para recuperación de contraseña vía SMTP.\n";
    $php .= "return " . var_export($usuarios, true) . ";\n";
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

// ── GET: listar usuarios ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $usuarios = loadUsers($usersFile);
    $list = [];
    foreach ($usuarios as $username => $u) {
        $list[] = [
            'username' => $username,
            'nombre'   => $u['nombre'] ?? $username,
            'email'    => $u['email']  ?? '',
            'rol'      => $u['rol']    ?? 'Usuario',
            'es_yo'    => ($username === $me),
        ];
    }
    ok(['usuarios' => $list]);
}

// ── POST: crear usuario ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = trim($data['username'] ?? '');
    $nombre   = trim($data['nombre']   ?? '');
    $email    = trim($data['email']    ?? '');
    $rol      = trim($data['rol']      ?? 'Operador');
    $password = $data['password']      ?? '';

    if ($username === '')      err('El nombre de usuario es requerido');
    if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username))
                               err('El usuario solo puede tener letras, números y guión bajo (3-40 caracteres)');
    if ($nombre === '')        err('El nombre para mostrar es requerido');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
                               err('El correo electrónico no tiene un formato válido');
    if (strlen($password) < 8) err('La contraseña debe tener al menos 8 caracteres');
    if (!in_array($rol, $ROLES_VALIDOS)) err('Rol inválido');

    $usuarios = loadUsers($usersFile);
    if (isset($usuarios[$username])) err("El nombre de usuario '{$username}' ya existe");

    $usuarios[$username] = [
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'nombre'   => $nombre,
        'email'    => $email,
        'rol'      => $rol,
    ];
    saveUsers($usersFile, $usuarios);

    ok(['username' => $username, 'nombre' => $nombre, 'rol' => $rol]);
}

// ── POST: actualizar usuario ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $username = trim($_GET['username'] ?? '');
    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $nombre   = trim($data['nombre']   ?? '');
    $email    = trim($data['email']    ?? '');
    $rol      = trim($data['rol']      ?? '');
    $password = $data['password']      ?? '';

    if ($username === '') err('Parámetro username requerido');
    if ($nombre   === '') err('El nombre para mostrar es requerido');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
                          err('El correo electrónico no tiene un formato válido');
    if ($rol !== '' && !in_array($rol, $ROLES_VALIDOS)) err('Rol inválido');
    if ($password !== '' && strlen($password) < 8)
                          err('La contraseña debe tener al menos 8 caracteres');

    $usuarios = loadUsers($usersFile);
    if (!isset($usuarios[$username])) err('Usuario no encontrado', 404);

    $usuarios[$username]['nombre'] = $nombre;
    $usuarios[$username]['email']  = $email;
    if ($rol !== '') $usuarios[$username]['rol'] = $rol;
    if ($password !== '') $usuarios[$username]['password'] = password_hash($password, PASSWORD_DEFAULT);

    saveUsers($usersFile, $usuarios);

    // Refrescar sesión si es el propio usuario
    if ($username === $me) {
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email']  = $email;
    }

    ok(['username' => $username, 'nombre' => $nombre]);
}

// ── POST: eliminar usuario ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $username = trim($_GET['username'] ?? '');

    if ($username === '')   err('Parámetro username requerido');
    if ($username === $me)  err('No puedes eliminar tu propia cuenta');

    $usuarios = loadUsers($usersFile);
    if (!isset($usuarios[$username])) err('Usuario no encontrado', 404);

    unset($usuarios[$username]);
    saveUsers($usersFile, $usuarios);

    ok(['message' => "Usuario '{$username}' eliminado correctamente"]);
}

err('Acción no reconocida');
