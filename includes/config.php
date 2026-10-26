<?php
// =============================================
// CONFIGURACIÓN DE BASE DE DATOS
// Edita estos valores con los de tu hosting
// =============================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'tesi_lgac');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// URL base del sitio (sin / al final)
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(__DIR__ . '/..');
$projectPath = '';

if ($documentRoot !== false && $projectRoot !== false) {
    $documentRoot = rtrim($documentRoot, DIRECTORY_SEPARATOR);
    $documentRootPrefix = $documentRoot . DIRECTORY_SEPARATOR;
    if (strncasecmp($projectRoot, $documentRootPrefix, strlen($documentRootPrefix)) === 0) {
        $projectPath = '/' . str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            substr($projectRoot, strlen($documentRootPrefix))
        );
    }
}

$urlScheme = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    ? 'https'
    : 'http';
$defaultBaseUrl = $urlScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $projectPath;
define(
    'BASE_URL',
    rtrim(
        getenv('BASE_URL') ?: 'http://localhost/Proyectos/LGAC-TESCI-RESIDENCIAS-PROFESIONALES',
        '/'
    )
);

$sessionCookieSecure = getenv('SESSION_COOKIE_SECURE');
define(
    'SESSION_COOKIE_SECURE',
    $sessionCookieSecure === false
        ? (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        : filter_var($sessionCookieSecure, FILTER_VALIDATE_BOOLEAN)
);

$mailConfigFile = __DIR__ . '/mail.local.php';
$mailConfig = is_file($mailConfigFile) ? require $mailConfigFile : [];
if (!is_array($mailConfig)) {
    throw new RuntimeException('includes/mail.local.php debe devolver un arreglo de configuración.');
}
define('SMTP_HOST', $mailConfig['host'] ?? 'smtp.gmail.com');
define('SMTP_PORT', (int) ($mailConfig['port'] ?? 587));
define('SMTP_USERNAME', $mailConfig['username'] ?? '');
define('SMTP_PASSWORD', $mailConfig['password'] ?? '');
define('MAIL_FROM_EMAIL', $mailConfig['from_email'] ?? '');
define('MAIL_FROM_NAME', $mailConfig['from_name'] ?? 'TESCI LGAC');
define('ADMIN_RECOVERY_EMAIL', $mailConfig['admin_recovery_email'] ?? '');

// Ruta absoluta a la carpeta uploads
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/');

// Tamaño máximo de archivos: 10 MB
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// Tipos de archivo permitidos
define('ALLOWED_IMAGES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_DOCS',   [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
]);

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// ─── Conexión PDO ─────────────────────────────
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            die(json_encode(['error' => 'Error de conexión a la base de datos.']));
        }
    }
    return $pdo;
}
