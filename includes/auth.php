<?php
require_once __DIR__ . '/config.php';

// ─── Iniciar sesión segura ─────────────────────
function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => SESSION_COOKIE_SECURE,
            'httponly' => true,
            'samesite' => 'Lax',
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

    if (!password_verify($password, $usuario['password'])) {
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
    $cookieParams = session_get_cookie_params();
    session_destroy();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $cookieParams['path'],
        'domain'   => $cookieParams['domain'],
        'secure'   => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
        'samesite' => $cookieParams['samesite'] ?? 'Lax',
    ]);
}

// ─── Verificar si está autenticado ────────────
function rolActual(): ?string
{
    iniciarSesionSegura();
    if (!isset($_SESSION['uid'])) {
        return null;
    }

    static $uidVerificado = null;
    static $rolVerificado = null;
    if ($uidVerificado === $_SESSION['uid']) {
        return $rolVerificado;
    }

    $stmt = getDB()->prepare('SELECT rol, activo FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['uid']]);
    $usuario = $stmt->fetch();
    if (!$usuario || !(int) $usuario['activo'] || !in_array($usuario['rol'], ['admin', 'docente'], true)) {
        logout();
        return null;
    }

    $uidVerificado = $_SESSION['uid'];
    $rolVerificado = $usuario['rol'];
    $_SESSION['rol'] = $rolVerificado;
    return $rolVerificado;
}

function estaAutenticado(): bool
{
    return rolActual() !== null;
}

function esAdmin(): bool
{
    return rolActual() === 'admin';
}

function esDocente(): bool
{
    return in_array(rolActual(), ['admin', 'docente'], true);
}

// ─── Proteger rutas ───────────────────────────
function requerirAdmin(): void
{
    if (!esAdmin()) {
        if (estaAutenticado()) {
            http_response_code(403);
            exit('Acceso denegado.');
        }
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
