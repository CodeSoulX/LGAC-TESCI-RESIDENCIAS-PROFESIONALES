<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirDocente();

$db  = getDB();
$uid = $_SESSION['uid'];
$id  = intval($_GET['id'] ?? 0);

// Verificar propiedad
$pub = $db->prepare("SELECT id FROM publicaciones WHERE id = ? AND usuario_id = ?");
$pub->execute([$id, $uid]);
if (!$pub->fetch()) { header('Location: panel.php'); exit; }

// Eliminar archivos del disco
$archs = $db->prepare("SELECT nombre_guardado FROM archivos WHERE publicacion_id = ?");
$archs->execute([$id]);
foreach ($archs->fetchAll() as $a) {
    @unlink(UPLOAD_PATH . $a['nombre_guardado']);
}

// Eliminar de BD (cascade elimina archivos)
$db->prepare("DELETE FROM publicaciones WHERE id = ? AND usuario_id = ?")->execute([$id, $uid]);

header('Location: panel.php?msg=eliminada');
exit;
