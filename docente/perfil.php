<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirDocente();

$db    = getDB();
$uid   = $_SESSION['uid'];
$error = '';
$exito = '';

$u = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$u->execute([$uid]);
$u = $u->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
    $error = 'Token inválido.';
  } else {
    $grado     = trim($_POST['grado'] ?? '');
    $espec     = trim($_POST['especialidad'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');

    // ── Cambio de contraseña opcional ──────
    $nueva_pass = $_POST['nueva_pass'] ?? '';
    $confirm    = $_POST['confirm_pass'] ?? '';
    $pass_hash  = $u['password'];

    if ($nueva_pass) {
      if (strlen($nueva_pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
      } elseif ($nueva_pass !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
      } else {
        $pass_hash = password_hash($nueva_pass, PASSWORD_BCRYPT, ['cost' => 12]);
      }
    }

    // ── Foto de perfil ─────────────────────
    $foto = $u['foto_perfil'];
    if (!empty($_FILES['foto']['name']) && !$error) {
      $res = subirArchivo($_FILES['foto'], 'perfiles');
      if ($res['ok']) {
        if ($foto && file_exists(UPLOAD_PATH . $foto)) @unlink(UPLOAD_PATH . $foto);
        $foto = $res['nombre_guardado'];
      } else {
        $error = $res['msg'];
      }
    }

    if (!$error) {
      $db->prepare(
        "UPDATE usuarios SET nombre=?, apellidos=?, grado=?, especialidad=?, bio=?, foto_perfil=?, password=?
                 WHERE id=?"
      )->execute([$nombre, $apellidos, $grado, $espec, $bio, $foto, $pass_hash, $uid]);
      $_SESSION['nombre'] = $nombre;
      $exito = 'Perfil actualizado correctamente.';
      $u = $db->prepare("SELECT * FROM usuarios WHERE id = ?")->execute([$uid]) ? $db->prepare("SELECT * FROM usuarios WHERE id = ?")->execute([$uid]) : $u;
      // Recargar datos
      $u2 = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
      $u2->execute([$uid]);
      $u = $u2->fetch();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi perfil · TESCI LGAC</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body>
  <div class="panel-layout">
    <aside class="panel-sidebar">
      <div class="logo"><strong>TESCI · LGAC</strong><span>Panel Docente</span><span class="panel-user-name">Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Docente') ?></span></div>
      <ul class="sidebar-nav">
        <li><a href="panel.php">📋 Mis publicaciones</a></li>
        <li><a href="nueva_pub.php">➕ Nueva publicación</a></li>
        <li><a href="perfil.php" class="activo">👤 Mi perfil</a></li>
        <li><a href="logout.php" style="margin-top:2rem;opacity:.7;">🚪 Cerrar sesión</a></li>
      </ul>
    </aside>

    <main class="panel-main">
      <div class="panel-header">
        <h1>Mi perfil</h1>
      </div>

      <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($exito): ?>
        <div class="alerta alerta-exito"><?= htmlspecialchars($exito) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" style="max-width:600px;">
        <?= campoCSRF() ?>

        <div style="margin-bottom:1.5rem;text-align:center;">
          <?php if ($u['foto_perfil'] && file_exists(UPLOAD_PATH . $u['foto_perfil'])): ?>
            <img src="<?= UPLOAD_URL . htmlspecialchars($u['foto_perfil']) ?>"
              style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin:0 auto .75rem;" alt="Foto">
          <?php else: ?>
            <div style="width:100px;height:100px;border-radius:50%;background:var(--azul-medio);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto .75rem;">
              <?= mb_strtoupper(mb_substr($u['nombre'], 0, 1)) ?>
            </div>
          <?php endif; ?>
          <div class="campo" style="text-align:left;">
            <label for="foto">Cambiar foto de perfil</label>
            <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="campo">
            <label for="nombre">Nombre(s) *</label>
            <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($u['nombre']) ?>">
          </div>
          <div class="campo">
            <label for="apellidos">Apellidos *</label>
            <input type="text" id="apellidos" name="apellidos" required value="<?= htmlspecialchars($u['apellidos']) ?>">
          </div>
        </div>

        <div class="campo">
          <label for="grado">Grado académico (ej. Dr., M.C., Ing.)</label>
          <input type="text" id="grado" name="grado" value="<?= htmlspecialchars($u['grado'] ?? '') ?>" maxlength="80">
        </div>

        <div class="campo">
          <label for="especialidad">Área de especialidad</label>
          <input type="text" id="especialidad" name="especialidad" value="<?= htmlspecialchars($u['especialidad'] ?? '') ?>" maxlength="200">
        </div>

        <div class="campo">
          <label for="bio">Semblanza / descripción breve</label>
          <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($u['bio'] ?? '') ?></textarea>
        </div>

        <hr style="margin:1.5rem 0;border-color:var(--arena-oscura);">
        <p style="font-size:.85rem;font-weight:600;margin-bottom:.75rem;color:var(--azul-oscuro);">Cambiar contraseña (opcional)</p>

        <div class="campo">
          <label for="nueva_pass">Nueva contraseña</label>
          <input type="password" id="nueva_pass" name="nueva_pass" autocomplete="new-password" minlength="8">
        </div>
        <div class="campo">
          <label for="confirm_pass">Confirmar nueva contraseña</label>
          <input type="password" id="confirm_pass" name="confirm_pass" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primario">Guardar perfil</button>
      </form>
    </main>
  </div>
</body>

</html>