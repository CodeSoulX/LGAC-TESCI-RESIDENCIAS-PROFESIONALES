<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirDocente();

$db  = getDB();
$uid = $_SESSION['uid'];

// ── Datos del docente ──────────────────────────
$docente = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$docente->execute([$uid]);
$docente = $docente->fetch();

// ── Mis publicaciones ──────────────────────────
$pubs = $db->prepare(
  "SELECT p.id, p.titulo, p.anio, p.visible, p.destacado, p.created_at,
            c.nombre AS categoria
     FROM publicaciones p
     JOIN categorias c ON c.id = p.categoria_id
     WHERE p.usuario_id = ?
     ORDER BY p.created_at DESC"
);
$pubs->execute([$uid]);
$pubs = $pubs->fetchAll();

// ── Mensaje de acción ──────────────────────────
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mi panel · TESCI LGAC</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body>
  <div class="panel-layout">

    <!-- SIDEBAR -->
    <aside class="panel-sidebar">
      <div class="logo">
        <strong>TESCI · LGAC</strong>
        <span>Panel Docente</span>
      </div>
      <ul class="sidebar-nav">
        <li><a href="panel.php" class="activo">📋 Mis publicaciones</a></li>
        <li><a href="nueva_pub.php">➕ Nueva publicación</a></li>
        <li><a href="perfil.php">👤 Mi perfil</a></li>
        <li><a href="logout.php" style="margin-top:2rem;opacity:.7;">🚪 Cerrar sesión</a></li>
      </ul>
    </aside>

    <!-- CONTENIDO -->
    <main class="panel-main">
      <div class="panel-header">
        <h1>Mis publicaciones</h1>
        <a href="nueva_pub.php" class="btn btn-primario btn-sm">+ Nueva publicación</a>
      </div>

      <?php if ($msg === 'creada'): ?>
        <div class="alerta alerta-exito">Publicación creada correctamente.</div>
      <?php elseif ($msg === 'editada'): ?>
        <div class="alerta alerta-exito">Publicación actualizada.</div>
      <?php elseif ($msg === 'eliminada'): ?>
        <div class="alerta alerta-info">Publicación eliminada.</div>
      <?php endif; ?>

      <?php if ($pubs): ?>
        <div class="tabla-contenedor">
          <table class="tabla">
            <thead>
              <tr>
                <th>Título</th>
                <th>Categoría</th>
                <th>Año</th>
                <th>Visible</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pubs as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['titulo']) ?></td>
                  <td><?= htmlspecialchars($p['categoria']) ?></td>
                  <td><?= $p['anio'] ? intval($p['anio']) : '—' ?></td>
                  <td><?= $p['visible'] ? '✅ Sí' : '⛔ No' ?></td>
                  <td style="white-space:nowrap">
                    <a href="editar_pub.php?id=<?= $p['id'] ?>" class="btn btn-secundario btn-sm">Editar</a>
                    <a href="eliminar_pub.php?id=<?= $p['id'] ?>" class="btn btn-peligro btn-sm"
                      onclick="return confirm('¿Eliminar esta publicación?')">Eliminar</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p style="color:var(--texto-suave);">Aún no tienes publicaciones. <a href="nueva_pub.php">Crea la primera</a>.</p>
      <?php endif; ?>
    </main>
  </div>
</body>

</html>