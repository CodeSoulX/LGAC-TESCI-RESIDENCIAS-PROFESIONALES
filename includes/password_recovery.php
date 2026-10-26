<?php
require_once __DIR__ . '/config.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

function registrarIntentoRecuperacion(PDO $db, string $correo, string $ip): bool
{
    $correoHash = hash('sha256', strtolower(trim($correo)));
    $ipHash = hash('sha256', $ip);

    $db->exec("DELETE FROM recuperacion_solicitudes WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $db->exec(
        'DELETE FROM recuperacion_tokens
         WHERE expires_at < NOW()
            OR (used_at IS NOT NULL AND used_at < DATE_SUB(NOW(), INTERVAL 1 DAY))'
    );
    $limite = $db->prepare(
        'SELECT COUNT(*) FROM recuperacion_solicitudes
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
           AND (email_hash = ? OR ip_hash = ?)'
    );
    $limite->execute([$correoHash, $ipHash]);
    $permitida = (int) $limite->fetchColumn() < 5;

    $registro = $db->prepare(
        'INSERT INTO recuperacion_solicitudes (email_hash, ip_hash) VALUES (?, ?)'
    );
    $registro->execute([$correoHash, $ipHash]);

    return $permitida;
}

function enviarCorreoRecuperacion(string $correo, string $nombre, string $codigo, ?string &$errorEnvio = null): bool
{
    if (
        !class_exists(\PHPMailer\PHPMailer\PHPMailer::class) ||
        SMTP_HOST === '' ||
        !filter_var(MAIL_FROM_EMAIL, FILTER_VALIDATE_EMAIL) ||
        !filter_var(SMTP_USERNAME, FILTER_VALIDATE_EMAIL) ||
        SMTP_PASSWORD === '' ||
        SMTP_PORT < 1 ||
        SMTP_PORT > 65535
    ) {
        $errorEnvio = 'Falta configurar el servidor de correo.';
        error_log('Password recovery email not sent: PHPMailer or SMTP settings are missing or invalid.');
        return false;
    }

    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mailer->isSMTP();
        $mailer->Host = SMTP_HOST;
        $mailer->SMTPAuth = true;
        $mailer->Username = SMTP_USERNAME;
        $mailer->Password = SMTP_PASSWORD;
        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = SMTP_PORT;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mailer->addAddress($correo, $nombre);
        $mailer->Subject = 'Recuperar contraseña · TESCI LGAC';
        $mailer->Body = "Hola {$nombre},\n\n";
        $mailer->Body .= "Recibimos una solicitud para cambiar la contraseña de tu cuenta.\n";
        $mailer->Body .= "Escribe este código en la plataforma dentro de los próximos 10 minutos:\n\n";
        $mailer->Body .= "{$codigo}\n\n";
        $mailer->Body .= "El código solo se puede usar una vez. Si no solicitaste el cambio, ignora este mensaje.\n";
        $mailer->AltBody = $mailer->Body;
        $mailer->send();
    } catch (\PHPMailer\PHPMailer\Exception $exception) {
        $errorEnvio = stripos($mailer->ErrorInfo, 'authenticate') !== false
            ? 'Gmail rechazó la autenticación SMTP. Revisa que uses una contraseña de aplicación vigente en includes/mail.local.php, no la contraseña normal de Gmail.'
            : 'El servidor de correo rechazó el envío. Revisa la configuración SMTP y el registro de errores de XAMPP.';
        error_log('Password recovery SMTP delivery failed: ' . $mailer->ErrorInfo);
        return false;
    }

    return true;
}

function correoRecuperacionConfigurado(): bool
{
    return class_exists(\PHPMailer\PHPMailer\PHPMailer::class) &&
        SMTP_HOST !== '' &&
        filter_var(MAIL_FROM_EMAIL, FILTER_VALIDATE_EMAIL) !== false &&
        filter_var(SMTP_USERNAME, FILTER_VALIDATE_EMAIL) !== false &&
        SMTP_PASSWORD !== '' &&
        SMTP_PORT >= 1 &&
        SMTP_PORT <= 65535;
}
