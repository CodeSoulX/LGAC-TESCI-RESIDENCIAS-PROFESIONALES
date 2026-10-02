<?php
require_once __DIR__ . '/config.php';

// ─── Iniciar sesión segura ─────────────────────
function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // cambiar a true en hosting con HTTPS
            'httponly' => true,
            'samesite' => 'Lax',   // Strict puede bloquear el token en local
        ]);
        session_start();
    }
}

// ─── Login ────────────────────────────────────
function login(string $correo, string $password): array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE correo = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        return ['ok' => false, 'msg' => 'Correo o contraseña incorrectos.'];
    }

    $passwordValida = password_verify($password, $usuario['password']);
    if (!$passwordValida && hash_equals($usuario['password'], $password)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('UPDATE usuarios SET password = ? WHERE id = ?')
            ->execute([$hash, $usuario['id']]);
        $passwordValida = true;
    }

    if (!$passwordValida) {
        return ['ok' => false, 'msg' => 'Correo o contraseña incorrectos.'];
    }

    iniciarSesionSegura();
    session_regenerate_id(true);

    $_SESSION['uid']  = $usuario['id'];
    $_SESSION['rol']  = $usuario['rol'];
    $_SESSION['nombre'] = $usuario['nombre'];

    return ['ok' => true, 'rol' => $usuario['rol']];
}

// ─── Logout ───────────────────────────────────
function logout(): void
{
    iniciarSesionSegura();
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}

// ─── Verificar si está autenticado ────────────
function estaAutenticado(): bool
{
    iniciarSesionSegura();
    return isset($_SESSION['uid']);
}

function esAdmin(): bool
{
    iniciarSesionSegura();
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function esDocente(): bool
{
    iniciarSesionSegura();
    return isset($_SESSION['uid']) &&
        in_array($_SESSION['rol'], ['admin', 'docente']);
}

// ─── Proteger rutas ───────────────────────────
function requerirAdmin(): void
{
    if (!esAdmin()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function requerirDocente(): void
{
    if (!esDocente()) {
        header('Location: ' . BASE_URL . '/docente/login.php');
        exit;
    }
}

// ─── CSRF Token ───────────────────────────────
function generarCSRF(): string
{
    iniciarSesionSegura();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarCSRF(string $token): bool
{
    iniciarSesionSegura();
    return isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

function campoCSRF(): string
{
    return '<input type="hidden" name="csrf_token" value="' . generarCSRF() . '">';
}

// ─── Subida de archivos segura ─────────────────
function subirArchivo(array $file, string $subcarpeta): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'Error al subir el archivo.'];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['ok' => false, 'msg' => 'El archivo supera 10 MB.'];
    }

    $mime = mime_content_type($file['tmp_name']);
    $permitidos = array_merge(ALLOWED_IMAGES, ALLOWED_DOCS);

    if (!in_array($mime, $permitidos)) {
        return ['ok' => false, 'msg' => 'Tipo de archivo no permitido.'];
    }

    $tipo = in_array($mime, ALLOWED_IMAGES) ? 'imagen' : 'documento';
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombre_guardado = bin2hex(random_bytes(16)) . '.' . strtolower($ext);

    $destDir = UPLOAD_PATH . $subcarpeta . '/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $destDir . $nombre_guardado)) {
        return ['ok' => false, 'msg' => 'No se pudo guardar el archivo.'];
    }

    return [
        'ok'              => true,
        'nombre_original' => htmlspecialchars($file['name'], ENT_QUOTES),
        'nombre_guardado' => $subcarpeta . '/' . $nombre_guardado,
        'tipo_mime'       => $mime,
        'tamanio'         => $file['size'],
        'tipo'            => $tipo,
    ];
}
