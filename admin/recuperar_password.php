<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_recovery.php';

iniciarSesionSegura();
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$db = getDB();
$mensaje = '';
$error = '';
$correoConfigurado = correoRecuperacionConfigurado() &&
    filter_var(ADMIN_RECOVERY_EMAIL, FILTER_VALIDATE_EMAIL) !== false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !verificarCSRF($csrfToken)) {
        $error = 'La solicitud expiró. Recarga la página e inténtalo otra vez.';
    } elseif (!$correoConfigurado) {
        $error = 'La recuperación por correo aún no está configurada. Contacta al responsable del sistema.';
    } else {
        $correo = strtolower(trim(ADMIN_RECOVERY_EMAIL));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $puedeSolicitar = registrarIntentoRecuperacion($db, $correo, $ip);

        if (!$puedeSolicitar) {
            $error = 'Se alcanzó el límite de 5 intentos de recuperación por hora. Espera una hora desde el último intento y vuelve a intentarlo.';
            error_log('Admin password recovery blocked by the hourly request limit.');
        } else {
            $consulta = $db->prepare(
                "SELECT id, nombre FROM usuarios WHERE rol = 'admin' AND activo = 1"
            );
            $consulta->execute();
            $administradores = $consulta->fetchAll();

            if (count($administradores) !== 1) {
                $error = 'No se pudo localizar una cuenta administradora activa. Contacta al responsable del sistema.';
                if (count($administradores) > 1) {
                    error_log('Admin password recovery requires exactly one active admin account.');
                }
            } else {
                $usuario = $administradores[0];
                $correoDestino = ADMIN_RECOVERY_EMAIL;
                $codigo = (string) random_int(10000000, 99999999);
                $codigoHash = password_hash($codigo, PASSWORD_BCRYPT, ['cost' => 12]);
                $codigoCreado = false;

                $db->beginTransaction();
                try {
                    $db->prepare('DELETE FROM recuperacion_tokens WHERE usuario_id = ?')
                        ->execute([$usuario['id']]);
                    $db->prepare(
                        'INSERT INTO recuperacion_tokens (usuario_id, token_hash, expires_at)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
                    )->execute([$usuario['id'], $codigoHash]);
                    $db->commit();
                    $codigoCreado = true;
                } catch (Throwable $exception) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log('Password recovery code creation failed: ' . $exception->getMessage());
                    $error = 'No se pudo crear el código de recuperación. Inténtalo más tarde.';
                }

                if ($codigoCreado) {
                    $errorEnvio = null;
                    if (enviarCorreoRecuperacion($correoDestino, $usuario['nombre'], $codigo, $errorEnvio)) {
                        $mensaje = 'El código fue enviado a ' . $correoDestino . '. Revisa también Spam y Promociones.';
                    } else {
                        error_log('A password recovery email could not be delivered. Check Google SMTP settings.');
                        $error = $errorEnvio ?? 'No se pudo enviar el correo. Revisa la configuración SMTP.';
                        $db->prepare(
                            'DELETE FROM recuperacion_tokens WHERE usuario_id = ? AND token_hash = ?'
                        )->execute([$usuario['id'], $codigoHash]);
                    }
                }
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
    <title>Recuperar contraseña · CCAI LGAC</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body class="login-page">
    <div class="login-caja">
        <h1>Recuperar contraseña</h1>
        <p>El código se enviará al buzón de recuperación configurado para la cuenta administradora. No necesitas escribir el correo usado para iniciar sesión en el portal.</p>

        <?php if (!$correoConfigurado): ?>
            <div class="alerta alerta-info">El envío SMTP aún no está listo. En XAMPP, configura una contraseña de aplicación de Gmail en <code>includes/mail.local.php</code>; no uses la contraseña normal de Google.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($mensaje): ?>
            <div class="alerta alerta-info" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= campoCSRF() ?>
            <button type="submit" class="btn btn-primario btn-bloque" <?= $correoConfigurado ? '' : 'disabled' ?>>Enviar código de recuperación</button>
        </form>
        <p style="text-align:center;margin-top:1rem;font-size:.9rem;">
            <a href="confirmar_recuperacion.php">Ya tengo mi código</a>
        </p>
        <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;">
            <a href="login.php">← Volver al inicio de sesión</a>
        </p>
    </div>
</body>

</html>
