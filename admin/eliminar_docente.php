<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCSRF($_POST['csrf_token'] ?? '')) {
    header('Location: panel.php?msg=error');
    exit;
}

$db = getDB();
$id = (int) ($_POST['id'] ?? 0);
$stmt = $db->prepare('SELECT id, rol, foto_perfil FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$docente = $stmt->fetch();

if (!$docente || $docente['rol'] !== 'docente') {
    header('Location: panel.php?msg=error');
    exit;
}

$files = [];
if (!empty($docente['foto_perfil'])) {
    $files[] = $docente['foto_perfil'];
}
$attachments = $db->prepare(
    'SELECT a.nombre_guardado
     FROM archivos a
     JOIN publicaciones p ON p.id = a.publicacion_id
     WHERE p.usuario_id = ?'
);
$attachments->execute([$id]);
foreach ($attachments->fetchAll(PDO::FETCH_COLUMN) as $file) {
    $files[] = $file;
}

$db->beginTransaction();
try {
    $db->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'docente'")->execute([$id]);
    $db->commit();
} catch (Throwable $error) {
    $db->rollBack();
    header('Location: panel.php?msg=error');
    exit;
}

$uploadRoot = realpath(UPLOAD_PATH);
if ($uploadRoot !== false) {
    foreach ($files as $file) {
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
        $absolutePath = realpath($uploadRoot . DIRECTORY_SEPARATOR . $relativePath);
        if ($absolutePath !== false && str_starts_with($absolutePath, $uploadRoot . DIRECTORY_SEPARATOR) && is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}

header('Location: panel.php?msg=eliminado');
exit;
