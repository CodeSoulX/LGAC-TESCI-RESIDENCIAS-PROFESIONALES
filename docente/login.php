<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

iniciarSesionSegura();
$sesionDocenteActiva = esDocente();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
    $error = 'Token inválido. Recarga la página.';
  } else {
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$correo || !$password) {
      $error = 'Completa todos los campos.';
    } else {
      $resultado = login($correo, $password);
      if ($resultado['ok']) {
        header('Location: ' . BASE_URL . '/docente/panel.php');
        exit;
      } else {
        $error = $resultado['msg'];
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
  <title>Acceso Docentes · Líneas de Generación y Aplicación del Conocimiento</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body class="login-page">
  <div class="login-caja">
    <h1>Acceso docentes</h1>
    <p>Portal privado del cuerpo académico · TESCI</p>

    <?php if ($error): ?>
      <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($sesionDocenteActiva): ?>
      <div class="alerta alerta-info">
        Sesión actual: <?= htmlspecialchars($_SESSION['nombre'] ?? 'docente') ?> en este navegador. Otros docentes pueden iniciar sesión al mismo tiempo desde sus propios navegadores o dispositivos. Si ingresas otra cuenta aquí, reemplazará la sesión de las pestañas de este navegador.
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= campoCSRF() ?>
      <div class="campo">
        <label for="correo">Correo institucional</label>
        <input type="email" id="correo" name="correo"
          value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
          required autocomplete="email">
      </div>
      <div class="campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primario btn-bloque">Ingresar</button>
    </form>
    <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;">
      <a href="../index.php">← Volver al portal público</a>
    </p>
  </div>
</body>

</html>