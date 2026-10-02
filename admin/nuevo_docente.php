<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirAdmin();

$db    = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
    $error = 'Token inválido.';
  } else {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $password  = $_POST['password'] ?? '';
    $grado     = trim($_POST['grado'] ?? '');
    $espec     = trim($_POST['especialidad'] ?? '');

    if (!$nombre || !$apellidos || !$correo || !$password) {
      $error = 'Nombre, apellidos, correo y contraseña son obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      $error = 'El correo no tiene formato válido.';
    } elseif (strlen($password) < 8) {
      $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
      // Verificar correo duplicado
      $existe = $db->prepare("SELECT id FROM usuarios WHERE correo = ?");
      $existe->execute([$correo]);
      if ($existe->fetch()) {
        $error = 'Ya existe un usuario con ese correo.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare(
          "INSERT INTO usuarios (nombre, apellidos, correo, password, rol, grado, especialidad)
                     VALUES (?, ?, ?, ?, 'docente', ?, ?)"
        )->execute([$nombre, $apellidos, $correo, $hash, $grado, $espec]);
        header('Location: panel.php?msg=creado');
        exit;
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
  <title>Nuevo docente · Admin TESCI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body>
  <div class="panel-layout">
    <aside class="panel-sidebar">
      <div class="logo"><strong>TESCI · LGAC</strong><span>Administrador</span></div>
      <ul class="sidebar-nav">
        <li><a href="panel.php">👥 Docentes</a></li>
        <li><a href="nuevo_docente.php" class="activo">➕ Nuevo docente</a></li>
        <li><a href="../index.php" target="_blank">🌐 Ver portal</a></li>
        <li><a href="logout.php" style="margin-top:2rem;opacity:.7;">🚪 Cerrar sesión</a></li>
      </ul>
    </aside>

    <main class="panel-main">
      <div class="panel-header">
        <h1>Registrar nuevo docente</h1>
        <a href="panel.php" class="btn btn-secundario btn-sm">← Regresar</a>
      </div>

      <div class="alerta alerta-info">
        Se creará una cuenta para que el docente pueda ingresar al portal privado y gestionar sus propias publicaciones.
      </div>

      <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" style="max-width:560px;">
        <?= campoCSRF() ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="campo">
            <label for="nombre">Nombre(s) *</label>
            <input type="text" id="nombre" name="nombre" required
              value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
          </div>
          <div class="campo">
            <label for="apellidos">Apellidos *</label>
            <input type="text" id="apellidos" name="apellidos" required
              value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>">
          </div>
        </div>

        <div class="campo">
          <label for="correo">Correo institucional *</label>
          <input type="email" id="correo" name="correo" required
            value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        </div>

        <div class="campo">
          <label for="password">Contraseña temporal * (mín. 8 caracteres)</label>
          <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
          <p style="font-size:.8rem;color:var(--texto-suave);margin-top:.3rem;">
            El docente podrá cambiarla desde su perfil.
          </p>
        </div>

        <div class="campo">
          <label for="grado">Grado académico</label>
          <input type="text" id="grado" name="grado" placeholder="Dr., M.C., Ing., Lic."
            value="<?= htmlspecialchars($_POST['grado'] ?? '') ?>" maxlength="80">
        </div>

        <div class="campo">
          <label for="especialidad">Área de especialidad</label>
          <input type="text" id="especialidad" name="especialidad"
            value="<?= htmlspecialchars($_POST['especialidad'] ?? '') ?>" maxlength="200">
        </div>

        <button type="submit" class="btn btn-primario">Registrar docente</button>
      </form>
    </main>
  </div>
</body>

</html>