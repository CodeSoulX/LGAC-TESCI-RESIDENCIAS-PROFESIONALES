<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

iniciarSesionSegura();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }
    logout();
    header('Location: ' . BASE_URL . '/Docente/Login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cerrar sesión · CCAI LGAC</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body class="login-page">
  <main class="login-caja">
    <h1>¿Cerrar sesión?</h1>
    <p>Confirma para salir del panel docente.</p>
    <form method="POST">
      <?= campoCSRF() ?>
      <button type="submit" class="btn btn-primario btn-bloque">Cerrar sesión</button>
    </form>
    <p style="text-align:center;margin-top:1rem;">
      <a href="panel.php">Cancelar y volver al panel</a>
    </p>
  </main>
</body>
</html>
