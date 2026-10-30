<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_recovery.php';

iniciarSesionSegura();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: no-referrer');
$db = getDB();
$error = '';
$completado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $codigoIngresado = $_POST['codigo'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmacion = $_POST['confirmacion'] ?? '';

    if (!is_string($csrfToken) || !verificarCSRF($csrfToken)) {
        $error = 'La solicitud expiró. Recarga la página e inténtalo otra vez.';
    } elseif (!is_string($codigoIngresado) || preg_match('/\A[0-9]{8}\z/', $codigoIngresado) !== 1) {
        $error = 'Escribe el código de ocho dígitos que recibiste.';
    } elseif (!is_string($password) || !is_string($confirmacion)) {
        $error = 'Escribe una contraseña válida.';
    } elseif (strlen($password) < 12) {
        $error = 'La contraseña debe tener al menos 12 caracteres.';
    } elseif (strlen($password) > 72) {
        $error = 'La contraseña no debe superar 72 bytes.';
    } elseif ($password !== $confirmacion) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $correo = strtolower(trim(ADMIN_RECOVERY_EMAIL));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!registrarIntentoRecuperacion($db, $correo, $ip)) {
            $error = 'No se pudo validar el código. Solicita uno nuevo o inténtalo más tarde.';
        } else {
            $db->beginTransaction();
            try {
                $consulta = $db->prepare(
                    "SELECT rt.id, rt.usuario_id, rt.token_hash, u.rol
                     FROM recuperacion_tokens rt
                     JOIN usuarios u ON u.id = rt.usuario_id
                     WHERE u.rol = 'admin' AND u.activo = 1
                       AND rt.used_at IS NULL AND rt.expires_at > NOW()
                     LIMIT 1 FOR UPDATE"
                );
                $consulta->execute();
                $registro = $consulta->fetch();

                if (!$registro || !password_verify($codigoIngresado, $registro['token_hash'])) {
                    $db->rollBack();
                    $error = 'El código no es válido o ya expiró. Solicita uno nuevo.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $actualizar = $db->prepare(
                        "UPDATE usuarios SET password = ?
                         WHERE id = ? AND rol = 'admin' AND activo = 1"
                    );
                    $actualizar->execute([$hash, $registro['usuario_id']]);

                    if ($actualizar->rowCount() !== 1) {
                        $db->rollBack();
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
    <title>Nueva contraseña · CCAI LGAC</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body class="login-page">
    <div class="login-caja">
        <h1>Nueva contraseña</h1>

        <?php if ($completado): ?>
            <div class="alerta alerta-exito" role="status">Tu contraseña se actualizó correctamente.</div>
            <p style="text-align:center;margin-top:1.25rem;">
                <a class="btn btn-primario btn-bloque" href="login.php">Iniciar sesión</a>
            </p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alerta alerta-error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <p>Escribe el código enviado al buzón de recuperación del administrador y elige una contraseña nueva de al menos 12 caracteres.</p>
            <form method="POST" novalidate>
                <?= campoCSRF() ?>
                <div class="campo">
                    <label for="codigo">Código de recuperación</label>
                    <input type="text" id="codigo" name="codigo" required inputmode="numeric" pattern="[0-9]{8}" maxlength="8" autocomplete="one-time-code">
                </div>
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
            <p style="text-align:center;margin-top:1.25rem;">
                <a href="recuperar_password.php">Solicitar otro código</a>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>
