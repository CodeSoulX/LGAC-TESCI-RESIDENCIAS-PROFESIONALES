<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

iniciarSesionSegura();
if (esAdmin()) {
  header('Location: ' . BASE_URL . '/admin/panel.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
    $error = 'Token inválido.';
  } else {
    $resultado = login(trim($_POST['correo'] ?? ''), $_POST['password'] ?? '');
    if ($resultado['ok'] && $resultado['rol'] === 'admin') {
      header('Location: ' . BASE_URL . '/admin/panel.php');
      exit;
    }
    $error = 'Acceso denegado o credenciales incorrectas.';
    logout(); // limpiar si era docente
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administrador · TESCI LGAC</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body class="login-page">
  <div class="login-caja">
    <h1>Administrador</h1>
    <p>Acceso restringido · Portal TESCI LGAC</p>
    <?php if ($error): ?>
      <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" novalidate>
      <?= campoCSRF() ?>
      <div class="campo">
        <label for="correo">Correo</label>
        <input type="email" id="correo" name="correo" required autocomplete="email">
      </div>
      <div class="campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primario btn-bloque">Ingresar como administrador</button>
    </form>
    <p style="text-align:center;margin-top:1rem;font-size:.82rem;">
      <a href="../docente/login.php">Soy docente →</a>
    </p>
  </div>
</body>

</html>