<?php
// =============================================
// CONFIGURACIÓN DE BASE DE DATOS
// Edita estos valores con los de tu hosting
// =============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'tesi_lgac');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// URL base del sitio (sin / al final)
define('BASE_URL', 'http://localhost:8012/Proyectos/tesi_lgac');

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
