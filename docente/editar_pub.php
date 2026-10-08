<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/publication_taxonomy.php';
requerirDocente();

$db    = getDB();
$uid   = $_SESSION['uid'];
$id    = intval($_GET['id'] ?? 0);
$error = '';

// ── Verificar que la pub pertenece al docente ──
$pub = $db->prepare("SELECT * FROM publicaciones WHERE id = ? AND usuario_id = ?");
$pub->execute([$id, $uid]);
$pub = $pub->fetch();
if (!$pub) {
  header('Location: panel.php');
  exit;
}

$cats    = $db->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();
$subcategoriasDisponibles = $db->query("SELECT DISTINCT nombre FROM subcategorias ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
$archivos = $db->prepare("SELECT * FROM archivos WHERE publicacion_id = ?");
$archivos->execute([$id]);
$archivos = $archivos->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
    $error = 'Token inválido.';
  } else {
    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $subcategoria = trim($_POST['subcategoria'] ?? '');
    $anio        = intval($_POST['anio'] ?? 0) ?: null;
    $categoriaSeleccion = trim($_POST['categoria_id'] ?? '');
    $nuevaCategoria = trim($_POST['nueva_categoria'] ?? '');
    $visible     = isset($_POST['visible']) ? 1 : 0;
    $destacado   = isset($_POST['destacado']) ? 1 : 0;

    if (!$titulo || !$categoriaSeleccion) {
      $error = 'El título y la categoría son obligatorios.';
    } elseif (mb_strlen($subcategoria, 'UTF-8') > 120) {
      $error = 'La subcategoría no debe superar 120 caracteres.';
    } else {
      try {
        $db->beginTransaction();
        $cat_id = resolverCategoriaPublicacion($db, $categoriaSeleccion, $nuevaCategoria);
        $subcategoria = registrarSubcategoriaPublicacion($db, $cat_id, $subcategoria);
        $db->prepare(
          "UPDATE publicaciones
                 SET titulo=?, descripcion=?, anio=?, categoria_id=?, subcategoria=?, visible=?, destacado=?
                 WHERE id=? AND usuario_id=?"
        )->execute([$titulo, $descripcion, $anio, $cat_id, $subcategoria ?: null, $visible, $destacado, $id, $uid]);
        $db->commit();
      } catch (InvalidArgumentException $validationError) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $validationError->getMessage();
      } catch (Throwable $exception) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('Update publication failed: ' . $exception->getMessage());
        $error = 'No se pudo guardar la publicación. Inténtalo de nuevo.';
      }

      if (!$error) {
        // ── Eliminar archivos seleccionados ────
        if (!empty($_POST['eliminar_arch'])) {
          foreach ($_POST['eliminar_arch'] as $aid) {
            $arch = $db->prepare("SELECT nombre_guardado FROM archivos WHERE id=? AND publicacion_id=?");
            $arch->execute([intval($aid), $id]);
            $arch = $arch->fetch();
            if ($arch) {
              @unlink(UPLOAD_PATH . $arch['nombre_guardado']);
              $db->prepare("DELETE FROM archivos WHERE id=?")->execute([intval($aid)]);
            }
          }
        }

        // ── Nuevos archivos ────────────────────
        if (!empty($_FILES['archivos']['name'][0])) {
          $total = count($_FILES['archivos']['name']);
          for ($i = 0; $i < $total; $i++) {
            $archivo = [
              'name'     => $_FILES['archivos']['name'][$i],
              'type'     => $_FILES['archivos']['type'][$i],
              'tmp_name' => $_FILES['archivos']['tmp_name'][$i],
              'error'    => $_FILES['archivos']['error'][$i],
              'size'     => $_FILES['archivos']['size'][$i],
            ];
            if ($archivo['error'] === UPLOAD_ERR_NO_FILE) continue;
            $res = subirArchivo($archivo, 'pub_' . $id);
            if ($res['ok']) {
              $db->prepare(
                "INSERT INTO archivos
                             (publicacion_id, nombre_original, nombre_guardado, tipo_mime, tamanio, tipo)
                             VALUES (?, ?, ?, ?, ?, ?)"
              )->execute([
                $id,
                $res['nombre_original'],
                $res['nombre_guardado'],
                $res['tipo_mime'],
                $res['tamanio'],
                $res['tipo']
              ]);
            }
          }
        }

        header('Location: panel.php?msg=editada');
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
  <title>Editar publicación · TESCI LGAC</title>
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
        <li><a href="perfil.php">👤 Mi perfil</a></li>
        <li><a href="logout.php" style="margin-top:2rem;opacity:.7;">🚪 Cerrar sesión</a></li>
      </ul>
    </aside>

    <main class="panel-main">
      <div class="panel-header">
        <h1>Editar publicación</h1>
        <a href="panel.php" class="btn btn-secundario btn-sm">← Regresar</a>
      </div>

      <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" data-publication-form style="max-width:680px;">
        <?= campoCSRF() ?>

        <div class="campo">
          <label for="titulo">Título *</label>
          <input type="text" id="titulo" name="titulo" required maxlength="255"
            value="<?= htmlspecialchars($_POST['titulo'] ?? $pub['titulo']) ?>">
        </div>

        <div class="campo">
          <label for="categoria_id">Categoría *</label>
          <select id="categoria_id" name="categoria_id" required>
            <?php foreach ($cats as $c): ?>
              <option value="<?= $c['id'] ?>"
                <?= (($_POST['categoria_id'] ?? $pub['categoria_id']) == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
            <option value="nueva" <?= (($_POST['categoria_id'] ?? '') === 'nueva') ? 'selected' : '' ?>>+ Agregar nueva categoría</option>
          </select>
        </div>

        <div class="campo" data-new-category-field <?= (($_POST['categoria_id'] ?? '') === 'nueva') ? '' : 'hidden' ?>>
          <label for="nueva_categoria">Nombre de categoría nueva (si seleccionaste agregar nueva)</label>
          <input type="text" id="nueva_categoria" name="nueva_categoria" maxlength="80"
            <?= (($_POST['categoria_id'] ?? '') === 'nueva') ? 'required' : '' ?>
            value="<?= htmlspecialchars($_POST['nueva_categoria'] ?? '') ?>">
        </div>

        <div class="campo">
          <label for="subcategoria">Subcategoría (opcional)</label>
          <input type="text" id="subcategoria" name="subcategoria" list="subcategorias-disponibles" maxlength="120"
            value="<?= htmlspecialchars($_POST['subcategoria'] ?? $pub['subcategoria'] ?? '') ?>">
          <datalist id="subcategorias-disponibles">
            <?php foreach ($subcategoriasDisponibles as $subcategoriaDisponible): ?>
              <option value="<?= htmlspecialchars($subcategoriaDisponible, ENT_QUOTES, 'UTF-8') ?>">
              <?php endforeach; ?>
          </datalist>
          <small>Si escribes una nueva, quedará disponible en los filtros para futuras publicaciones.</small>
        </div>

        <div class="campo">
          <label for="descripcion">Descripción</label>
          <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($_POST['descripcion'] ?? $pub['descripcion']) ?></textarea>
        </div>

        <div class="campo">
          <label for="anio">Año</label>
          <input type="number" id="anio" name="anio" min="1980" max="2099" style="max-width:160px;"
            value="<?= htmlspecialchars($_POST['anio'] ?? $pub['anio']) ?>">
        </div>

        <?php if ($archivos): ?>
          <div class="campo">
            <label>Archivos actuales</label>
            <?php foreach ($archivos as $a): ?>
              <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
                <label style="cursor:pointer;font-size:.88rem;display:flex;align-items:center;gap:.4rem;">
                  <input type="checkbox" name="eliminar_arch[]" value="<?= $a['id'] ?>">
                  Eliminar
                </label>
                <span style="font-size:.88rem;">
                  <?= $a['tipo'] === 'imagen' ? '🖼️' : '📄' ?> <?= htmlspecialchars($a['nombre_original']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="campo">
          <label for="archivos">Agregar archivos (PDF, Word, imágenes)</label>
          <input type="file" id="archivos" name="archivos[]" multiple
            accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.gif">
        </div>

        <div class="campo" style="display:flex;gap:1.5rem;">
          <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
            <input type="checkbox" name="visible" value="1"
              <?= (!isset($_POST['visible']) && $pub['visible']) || isset($_POST['visible']) ? 'checked' : '' ?>>
            Visible en el portal
          </label>
          <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
            <input type="checkbox" name="destacado" value="1"
              <?= (!isset($_POST['destacado']) && $pub['destacado']) || isset($_POST['destacado']) ? 'checked' : '' ?>>
            Destacado
          </label>
        </div>

        <button type="submit" class="btn btn-primario">Guardar cambios</button>
      </form>
    </main>
  </div>
  <script src="../assets/js/publication-taxonomy.js?v=<?= filemtime(__DIR__ . '/../assets/js/publication-taxonomy.js') ?>" defer></script>
</body>

</html>