<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requerirAdmin();

$db = getDB();
$error = '';
$message = $_GET['msg'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token inválido. Recarga la página.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload_images') {
            $title = trim($_POST['title'] ?? '');
            $files = $_FILES['images'] ?? null;
            $uploaded = 0;

            if (!$files || empty($files['name'][0]) || $files['error'][0] === UPLOAD_ERR_NO_FILE) {
                $error = 'Selecciona al menos una imagen.';
            } elseif (mb_strlen($title, 'UTF-8') > 255) {
                $error = 'El título no debe superar 255 caracteres.';
            } else {
                $insert = $db->prepare(
                    "INSERT INTO galeria_medios (tipo, titulo, nombre_original, nombre_guardado, creado_por)
           VALUES ('imagen', ?, ?, ?, ?)"
                );

                foreach ($files['name'] as $index => $originalName) {
                    if ($files['error'][$index] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                        $error = 'Una o más imágenes no se pudieron subir.';
                        continue;
                    }

                    $mime = mime_content_type($files['tmp_name'][$index]);
                    if (!in_array($mime, ALLOWED_IMAGES, true)) {
                        $error = 'Solo se permiten imágenes JPG, PNG, WEBP o GIF.';
                        continue;
                    }

                    $file = [
                        'name' => $originalName,
                        'type' => $files['type'][$index],
                        'tmp_name' => $files['tmp_name'][$index],
                        'error' => $files['error'][$index],
                        'size' => $files['size'][$index],
                    ];
                    $upload = subirArchivo($file, 'galeria');
                    if (!$upload['ok']) {
                        $error = $upload['msg'];
                        continue;
                    }

                    $caption = $title !== '' ? $title : pathinfo($originalName, PATHINFO_FILENAME);
                    try {
                        $insert->execute([
                            $caption,
                            $originalName,
                            $upload['nombre_guardado'],
                            $_SESSION['uid'],
                        ]);
                        $uploaded++;
                    } catch (Throwable $exception) {
                        @unlink(UPLOAD_PATH . $upload['nombre_guardado']);
                        error_log('Gallery image insert failed: ' . $exception->getMessage());
                        $error = 'No se pudo guardar una imagen en la galería.';
                    }
                }

                if ($uploaded > 0 && $error === '') {
                    header('Location: galeria.php?msg=imagenes_subidas');
                    exit;
                }
            }
        } elseif ($action === 'add_video') {
            $title = trim($_POST['title'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $urlParts = filter_var($url, FILTER_VALIDATE_URL) ? parse_url($url) : false;

            if ($title === '' || mb_strlen($title, 'UTF-8') > 255) {
                $error = 'Escribe un título de hasta 255 caracteres.';
            } elseif (!$urlParts || !in_array(strtolower($urlParts['scheme'] ?? ''), ['http', 'https'], true) || empty($urlParts['host'])) {
                $error = 'Escribe una URL válida que comience con http:// o https://.';
            } elseif (strlen($url) > 2048) {
                $error = 'La URL supera la longitud permitida.';
            } else {
                $db->prepare(
                    "INSERT INTO galeria_medios (tipo, titulo, url, creado_por)
           VALUES ('video', ?, ?, ?)"
                )->execute([$title, $url, $_SESSION['uid']]);
                header('Location: galeria.php?msg=video_agregado');
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $query = $db->prepare('SELECT id, tipo, nombre_guardado FROM galeria_medios WHERE id = ? LIMIT 1');
            $query->execute([$id]);
            $media = $query->fetch();

            if (!$media) {
                $error = 'El medio seleccionado ya no existe.';
            } else {
                $db->prepare('DELETE FROM galeria_medios WHERE id = ?')->execute([$id]);

                if ($media['tipo'] === 'imagen' && $media['nombre_guardado']) {
                    $uploadRoot = realpath(UPLOAD_PATH);
                    if ($uploadRoot !== false) {
                        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $media['nombre_guardado']);
                        $absolutePath = realpath($uploadRoot . DIRECTORY_SEPARATOR . $relativePath);
                        if ($absolutePath !== false && str_starts_with($absolutePath, $uploadRoot . DIRECTORY_SEPARATOR) && is_file($absolutePath)) {
                            @unlink($absolutePath);
                        }
                    }
                }

                header('Location: galeria.php?msg=eliminado');
                exit;
            }
        }
    }
}

$medios = $db->query(
    'SELECT id, tipo, titulo, url, nombre_original, nombre_guardado, created_at
   FROM galeria_medios
   ORDER BY created_at DESC, id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Galería multimedia · Admin TESCI</title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?= filemtime(__DIR__ . '/../assets/css/main.css') ?>">
    <link rel="icon" type="image/png" href="../assets/logo.png">
</head>

<body>
    <div class="panel-layout">
        <aside class="panel-sidebar">
            <div class="logo"><strong>TESCI · LGAC</strong><span>Administrador</span></div>
            <ul class="sidebar-nav">
                <li><a href="panel.php">👥 Docentes</a></li>
                <li><a href="nuevo_docente.php">➕ Nuevo docente</a></li>
                <li><a href="galeria.php" class="activo">▧ Galería multimedia</a></li>
                <li><a href="../index.php">🌐 Ver portal</a></li>
                <li><a href="logout.php" class="sidebar-logout">🚪 Cerrar sesión</a></li>
            </ul>
        </aside>

        <main class="panel-main">
            <div class="panel-header">
                <h1>Galería multimedia</h1>
                <a href="panel.php" class="btn btn-secundario btn-sm">← Regresar</a>
            </div>

            <?php if ($message === 'imagenes_subidas'): ?>
                <div class="alerta alerta-exito">Imágenes agregadas a la galería.</div>
            <?php elseif ($message === 'video_agregado'): ?>
                <div class="alerta alerta-exito">Video agregado a la galería.</div>
            <?php elseif ($message === 'eliminado'): ?>
                <div class="alerta alerta-exito">Medio eliminado de la galería.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="gallery-admin-forms">
                <form class="gallery-admin-form" method="POST" enctype="multipart/form-data">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="action" value="upload_images">
                    <h2>Subir imágenes</h2>
                    <div class="campo">
                        <label for="image-title">Título común (opcional)</label>
                        <input type="text" id="image-title" name="title" maxlength="255">
                    </div>
                    <div class="campo">
                        <label for="images">Archivos de imagen</label>
                        <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                        <small>JPG, PNG, WEBP o GIF. Máximo <?= (int) (MAX_FILE_SIZE / 1024 / 1024) ?> MB por imagen.</small>
                    </div>
                    <button type="submit" class="btn btn-restablecer">Subir imágenes</button>
                </form>

                <form class="gallery-admin-form" method="POST">
                    <?= campoCSRF() ?>
                    <input type="hidden" name="action" value="add_video">
                    <h2>Agregar video por enlace</h2>
                    <div class="campo">
                        <label for="video-title">Título</label>
                        <input type="text" id="video-title" name="title" maxlength="255" required>
                    </div>
                    <div class="campo">
                        <label for="video-url">URL del video</label>
                        <input type="url" id="video-url" name="url" placeholder="https://..." maxlength="2048" required>
                    </div>
                    <button type="submit" class="btn btn-primario">Agregar video</button>
                </form>
            </div>

            <h2 class="gallery-admin-list-title">Medios publicados</h2>
            <?php if ($medios): ?>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Vista</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Archivo o enlace</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medios as $medio): ?>
                                <tr>
                                    <td>
                                        <?php if ($medio['tipo'] === 'imagen'): ?>
                                            <img class="gallery-admin-thumb" src="<?= UPLOAD_URL . str_replace('%2F', '/', rawurlencode($medio['nombre_guardado'])) ?>" alt="<?= htmlspecialchars($medio['titulo']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <span>Video</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $medio['tipo'] === 'imagen' ? 'Imagen' : 'Video' ?></td>
                                    <td><?= htmlspecialchars($medio['titulo']) ?></td>
                                    <td>
                                        <?php if ($medio['tipo'] === 'imagen'): ?>
                                            <?= htmlspecialchars($medio['nombre_original'] ?? '') ?>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($medio['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Abrir video</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($medio['created_at']) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('¿Eliminar este medio de la galería?')">
                                            <?= campoCSRF() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= intval($medio['id']) ?>">
                                            <button type="submit" class="btn btn-peligro btn-sm">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Todavía no hay medios agregados por administración.</p>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>