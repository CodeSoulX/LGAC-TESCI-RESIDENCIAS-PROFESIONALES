<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirAdmin();

$db = getDB();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$consulta = $db->prepare(
    "SELECT id, nombre, apellidos, correo
   FROM usuarios
   WHERE id = ? AND rol = 'docente'
   LIMIT 1"
);
$consulta->execute([$id]);
$docente = $consulta->fetch();

if (!$docente) {
    header('Location: panel.php?msg=error');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token inválido. Recarga la página.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmacion = $_POST['confirmacion'] ?? '';

        if (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $confirmacion) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $actualizar = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ? AND rol = 'docente'");
            $actualizar->execute([$hash, $id]);
            header('Location: panel.php?msg=password_restablecida');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña · Admin TESCI</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body>
    <div class="panel-layout">
        <aside class="panel-sidebar">
            <div class="logo"><strong>TESCI · LGAC</strong><span>Administrador</span></div>
            <ul class="sidebar-nav">
                <li><a href="panel.php" class="activo">👥 Docentes</a></li>
                <li><a href="nuevo_docente.php">➕ Nuevo docente</a></li>
                <li><a href="../index.php">🌐 Ver portal</a></li>
                <li><a href="logout.php" style="margin-top:2rem;opacity:.7;">🚪 Cerrar sesión</a></li>
            </ul>
        </aside>

        <main class="panel-main">
            <div class="panel-header">
                <h1>Restablecer contraseña</h1>
                <a href="panel.php" class="btn btn-secundario btn-sm">← Regresar</a>
            </div>

            <div class="alerta alerta-info">
                Nueva contraseña para <strong><?= htmlspecialchars($docente['nombre'] . ' ' . $docente['apellidos']) ?></strong>
                (<?= htmlspecialchars($docente['correo']) ?>).
            </div>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" style="max-width:560px;">
                <?= campoCSRF() ?>
                <input type="hidden" name="id" value="<?= intval($docente['id']) ?>">

                <div class="campo">
                    <label for="password">Nueva contraseña (mínimo 8 caracteres)</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                </div>
                <div class="campo">
                    <label for="confirmacion">Confirmar nueva contraseña</label>
                    <input type="password" id="confirmacion" name="confirmacion" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primario">Guardar nueva contraseña</button>
            </form>
        </main>
    </div>
</body>

</html>