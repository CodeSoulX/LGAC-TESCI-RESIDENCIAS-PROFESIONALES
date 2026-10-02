<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirAdmin();

$db     = getDB();
$id     = intval($_GET['id'] ?? 0);
$accion = $_GET['accion'] ?? '';

if ($id && in_array($accion, ['activar', 'desactivar'])) {
    // Asegurarse de no tocar admins
    $check = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $check->execute([$id]);
    $u = $check->fetch();

    if ($u && $u['rol'] === 'docente') {
        $nuevo = ($accion === 'activar') ? 1 : 0;
        $db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$nuevo, $id]);
        header('Location: panel.php?msg=' . ($nuevo ? 'activado' : 'desactivado'));
        exit;
    }
}

header('Location: panel.php');
exit;
