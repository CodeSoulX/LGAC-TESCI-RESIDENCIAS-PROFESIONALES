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
$correoConfigurado = is_file(__DIR__ . '/../vendor/autoload.php')
    && SMTP_HOST === 'smtp.gmail.com'
    && filter_var(MAIL_FROM_EMAIL, FILTER_VALIDATE_EMAIL) !== false
    && filter_var(SMTP_USERNAME, FILTER_VALIDATE_EMAIL) !== false
    && SMTP_PASSWORD !== ''
    && filter_var(ADMIN_RECOVERY_EMAIL, FILTER_VALIDATE_EMAIL) !== false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || !verificarCSRF($csrfToken)) {
        $error = 'La solicitud expiró. Recarga la página e inténtalo otra vez.';
    } else {
        $correoIngresado = $_POST['correo'] ?? '';
        $correo = is_string($correoIngresado) ? strtolower(trim($correoIngresado)) : '';

        if (strlen($correo) > 180 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Escribe un correo institucional válido.';
        } elseif (!$correoConfigurado) {
            $error = 'La recuperación por correo aún no está configurada. Contacta al responsable del sistema.';
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $puedeSolicitar = registrarSolicitudRecuperacion($db, $correo, $ip);

            if ($puedeSolicitar) {
                $consulta = $db->prepare(
                    "SELECT id, nombre, correo, rol FROM usuarios
                     WHERE correo = ? AND rol IN ('docente', 'admin') AND activo = 1
                     LIMIT 1"
                );
                $consulta->execute([$correo]);
                $docente = $consulta->fetch();

                if ($docente) {
                    $correoDestino = obtenerDestinoRecuperacion($docente);
                    if ($correoDestino === null) {
                        error_log('Password recovery email not sent: no valid recovery destination is configured for the account.');
                    } else {
                        $token = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $token);
                        $tokenCreado = false;
                        $db->beginTransaction();
                        try {
                            $db->prepare('DELETE FROM recuperacion_tokens WHERE usuario_id = ?')
                                ->execute([$docente['id']]);
                            $db->prepare(
                                'INSERT INTO recuperacion_tokens (usuario_id, token_hash, expires_at)
                                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
                            )->execute([$docente['id'], $tokenHash]);
                            $db->commit();
                            $tokenCreado = true;
                        } catch (Throwable $exception) {
                            if ($db->inTransaction()) {
                                $db->rollBack();
                            }
                            error_log('Password recovery token creation failed: ' . $exception->getMessage());
                        }

                        if ($tokenCreado) {
                            $url = BASE_URL . '/docente/restablecer_password.php?token=' . $token;
                            if (!enviarCorreoRecuperacion($correoDestino, $docente['nombre'], $url)) {
                                error_log('A password recovery email could not be delivered. Check Google SMTP settings.');
                            }
                        }
                    }
                }
            }

            if ($error === '' && $mensaje === '') {
                $mensaje = 'Solicitud recibida. Por seguridad no confirmamos si el correo está registrado y este sitio no puede confirmar la entrega. Si la cuenta está activa y el correo está configurado, revisa tu bandeja y spam; si no llega, contacta al responsable del sistema.';
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
    <title>Recuperar contraseña · TESCI LGAC</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body class="login-page">
    <div class="login-caja">
        <h1>Recuperar contraseña</h1>
        <p>Escribe el correo institucional asociado a tu cuenta.</p>

        <?php if (!$correoConfigurado): ?>
            <div class="alerta alerta-info">La recuperación por correo aún no está configurada en este servidor. Contacta al responsable del sistema para habilitarla.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($mensaje): ?>
            <div class="alerta alerta-info" role="status"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= campoCSRF() ?>
            <div class="campo">
                <label for="correo">Correo institucional</label>
                <input type="email" id="correo" name="correo" required maxlength="180" autocomplete="email">
            </div>
            <button type="submit" class="btn btn-primario btn-bloque" <?= $correoConfigurado ? '' : 'disabled' ?>>Enviar enlace de recuperación</button>
        </form>
        <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;">
            <a href="login.php">← Volver al inicio de sesión</a>
        </p>
    </div>
</body>

</html>
