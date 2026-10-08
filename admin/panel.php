<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirAdmin();

$db  = getDB();
$msg = $_GET['msg'] ?? '';

// ── Lista de docentes (no admins) ──────────────
$docentes = $db->query(
  "SELECT u.id, u.nombre, u.apellidos, u.correo, u.grado, u.activo,
            COUNT(p.id) AS total_pubs
     FROM usuarios u
     LEFT JOIN publicaciones p ON p.usuario_id = u.id
     WHERE u.rol = 'docente'
     GROUP BY u.id
     ORDER BY u.apellidos"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel Admin · TESCI LGAC</title>
  <link rel="stylesheet" href="../assets/css/main.css?v=<?= filemtime(__DIR__ . '/../assets/css/main.css') ?>">
  <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body>
  <div class="panel-layout">
    <aside class="panel-sidebar">
      <div class="logo"><strong>TESCI · LGAC</strong><span>Administrador</span></div>
      <ul class="sidebar-nav">
        <li><a href="panel.php" class="activo">👥 Docentes</a></li>
        <li><a href="nuevo_docente.php">➕ Nuevo docente</a></li>
        <li><a href="galeria.php">▧ Galería multimedia</a></li>
        <li><a href="../index.php" target="_blank">🌐 Ver portal</a></li>
        <li><a href="logout.php" style="margin-top:2rem;opacity:.7;">🚪 Cerrar sesión</a></li>
      </ul>
    </aside>

    <main class="panel-main">
      <div class="panel-header">
        <h1>Gestión de docentes</h1>
        <a href="nuevo_docente.php" class="btn btn-primario btn-sm">+ Nuevo docente</a>
      </div>

      <?php if ($msg === 'creado'): ?>
        <div class="alerta alerta-exito">Docente registrado correctamente.</div>
      <?php elseif ($msg === 'activado'): ?>
        <div class="alerta alerta-exito">Acceso activado.</div>
      <?php elseif ($msg === 'desactivado'): ?>
        <div class="alerta alerta-info">Acceso desactivado. El docente ya no puede iniciar sesión.</div>
      <?php elseif ($msg === 'eliminado'): ?>
        <div class="alerta alerta-exito">El integrante y sus publicaciones fueron eliminados.</div>
      <?php elseif ($msg === 'password_restablecida'): ?>
        <div class="alerta alerta-exito">La contraseña del docente se restableció correctamente.</div>
      <?php elseif ($msg === 'error'): ?>
        <div class="alerta alerta-error">No se pudo completar la acción. Recarga la página e inténtalo de nuevo.</div>
      <?php endif; ?>

      <div class="alerta alerta-info">
        ℹ️ El administrador <strong>no puede</strong> ver ni editar las publicaciones privadas de los docentes.
        Solo gestiona el acceso al sistema.
      </div>

      <div class="tabla-contenedor">
        <table class="tabla tabla-docentes">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Grado</th>
              <th>Publicaciones</th>
              <th>Estado</th>
              <th class="acciones-columna">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($docentes as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['nombre'] . ' ' . $d['apellidos']) ?></td>
                <td><?= htmlspecialchars($d['correo']) ?></td>
                <td><?= htmlspecialchars($d['grado'] ?? '—') ?></td>
                <td style="text-align:center;"><?= intval($d['total_pubs']) ?></td>
                <td><?= $d['activo'] ? '<span style="color:#27ae60;font-weight:600;">Activo</span>' : '<span style="color:#c0392b;">Inactivo</span>' ?></td>
                <td class="acciones-columna">
                  <div class="acciones-botones">
                    <a href="restablecer_password.php?id=<?= intval($d['id']) ?>"
                      class="btn btn-restablecer btn-sm" title="Restablecer contraseña"
                      aria-label="Restablecer contraseña de <?= htmlspecialchars($d['nombre'] . ' ' . $d['apellidos']) ?>">Restablecer</a>
                    <?php if ($d['activo']): ?>
                      <a href="toggle_docente.php?id=<?= $d['id'] ?>&accion=desactivar"
                        class="btn btn-desactivar btn-sm"
                        onclick="return confirm('¿Desactivar acceso a este docente?')">Desactivar</a>
                    <?php else: ?>
                      <a href="toggle_docente.php?id=<?= $d['id'] ?>&accion=activar"
                        class="btn btn-primario btn-sm">Activar</a>
                    <?php endif; ?>
                    <form method="POST" action="eliminar_docente.php" class="acciones-form"
                      onsubmit="return confirm('¿Eliminar definitivamente a <?= htmlspecialchars(addslashes($d['nombre'] . ' ' . $d['apellidos']), ENT_QUOTES) ?>? También se borrarán sus publicaciones y archivos.');">
                      <?= campoCSRF() ?>
                      <input type="hidden" name="id" value="<?= intval($d['id']) ?>">
                      <button type="submit" class="btn btn-peligro btn-sm">Eliminar</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$docentes): ?>
              <tr>
                <td colspan="6" style="text-align:center;color:var(--texto-suave);">
                  No hay docentes registrados. <a href="nuevo_docente.php">Registra el primero</a>.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>

</html>