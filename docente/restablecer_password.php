<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

iniciarSesionSegura();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: no-referrer');
$db = getDB();
$token = $_GET['token'] ?? '';
$tokenValido = is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
$tokenHash = $tokenValido ? hash('sha256', $token) : '';
$error = '';
$completado = false;

if ($tokenValido) {
    $consulta = $db->prepare(
        "SELECT rt.id, u.rol FROM recuperacion_tokens rt
         JOIN usuarios u ON u.id = rt.usuario_id
         WHERE rt.token_hash = ? AND rt.used_at IS NULL AND rt.expires_at > NOW()
           AND u.activo = 1 AND u.rol IN ('docente', 'admin')
         LIMIT 1"
    );
    $consulta->execute([$tokenHash]);
    $tokenUsuario = $consulta->fetch();
    $tokenValido = (bool) $tokenUsuario;
    $rolRestablecido = $tokenUsuario['rol'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !verificarCSRF($csrfToken)) {
        $error = 'La solicitud expiró. Recarga la página e inténtalo otra vez.';
    } elseif (!$tokenValido) {
        $error = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmacion = $_POST['confirmacion'] ?? '';

        if (!is_string($password) || !is_string($confirmacion)) {
            $error = 'Escribe una contraseña válida.';
        } elseif (strlen($password) < 12) {
            $error = 'La contraseña debe tener al menos 12 caracteres.';
        } elseif (strlen($password) > 72) {
            $error = 'La contraseña no debe superar 72 bytes.';
        } elseif ($password !== $confirmacion) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->beginTransaction();
            try {
                $consulta = $db->prepare(
                    "SELECT rt.id, rt.usuario_id, u.rol FROM recuperacion_tokens rt
                     JOIN usuarios u ON u.id = rt.usuario_id
                     WHERE rt.token_hash = ? AND rt.used_at IS NULL AND rt.expires_at > NOW()
                       AND u.activo = 1 AND u.rol IN ('docente', 'admin')
                     LIMIT 1 FOR UPDATE"
                );
                $consulta->execute([$tokenHash]);
                $registro = $consulta->fetch();

                if (!$registro) {
                    $db->rollBack();
                    $tokenValido = false;
                    $error = 'El enlace no es válido o ya expiró. Solicita uno nuevo.';
                } else {
                    $actualizar = $db->prepare(
                        "UPDATE usuarios SET password = ?
                         WHERE id = ? AND rol IN ('docente', 'admin') AND activo = 1"
                    );
                    $actualizar->execute([$hash, $registro['usuario_id']]);

                    if ($actualizar->rowCount() !== 1) {
                        $db->rollBack();
                        $tokenValido = false;
                        $error = 'No se pudo recuperar esta cuenta. Contacta al administrador.';
                    } else {
                        $db->prepare('UPDATE recuperacion_tokens SET used_at = NOW() WHERE id = ?')
                            ->execute([$registro['id']]);
                        $db->prepare('DELETE FROM recuperacion_tokens WHERE usuario_id = ? AND id <> ?')
                            ->execute([$registro['usuario_id'], $registro['id']]);
                        $db->prepare('DELETE FROM sesiones WHERE usuario_id = ?')
                            ->execute([$registro['usuario_id']]);
                        $db->commit();
                        $completado = true;
                        $rolRestablecido = $registro['rol'];
                    }
                }
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Password recovery update failed: ' . $exception->getMessage());
                $error = 'No se pudo actualizar la contraseña. Inténtalo más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="Cache-Control" content="no-store">
    <title>Nueva contraseña · TESCI LGAC</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body class="login-page">
    <div class="login-caja">
        <h1>Nueva contraseña</h1>

        <?php if ($completado): ?>
            <div class="alerta alerta-exito" role="status">Tu contraseña se actualizó correctamente.</div>
            <p style="text-align:center;margin-top:1.25rem;">
                <a class="btn btn-primario btn-bloque" href="<?= $rolRestablecido === 'admin' ? '../admin/login.php' : 'login.php' ?>">Iniciar sesión</a>
            </p>
        <?php elseif (!$tokenValido): ?>
            <div class="alerta alerta-error"><?= htmlspecialchars($error ?: 'El enlace no es válido o ya expiró. Solicita uno nuevo.') ?></div>
            <p style="text-align:center;margin-top:1.25rem;">
                <a href="recuperar_password.php">Solicitar otro enlace</a>
            </p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <p>Elige una contraseña nueva de al menos 12 caracteres.</p>
            <form method="POST">
                <?= campoCSRF() ?>
                <div class="campo">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password">
                </div>
                <div class="campo">
                    <label for="confirmacion">Confirmar contraseña</label>
                    <input type="password" id="confirmacion" name="confirmacion" required minlength="12" maxlength="72" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primario btn-bloque">Guardar contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
