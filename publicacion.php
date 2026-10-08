<?php
require_once __DIR__ . '/includes/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$db = getDB();
$stmt = $db->prepare(
    "SELECT p.id, p.titulo, p.descripcion, p.anio, p.subcategoria, p.created_at,
            c.nombre AS categoria,
            u.nombre, u.apellidos, u.grado, u.especialidad
     FROM publicaciones p
     JOIN categorias c ON c.id = p.categoria_id
     JOIN usuarios u ON u.id = p.usuario_id
     WHERE p.id = ? AND p.visible = 1 AND u.activo = 1
     LIMIT 1"
);
$stmt->execute([$id ?: 0]);
$publicacion = $stmt->fetch();

if (!$publicacion) {
    http_response_code(404);
}

$archivos = [];
$imagenPortada = null;
if ($publicacion) {
    $fileQuery = $db->prepare(
        'SELECT nombre_original, nombre_guardado, tipo, tipo_mime
         FROM archivos WHERE publicacion_id = ? ORDER BY id'
    );
    $fileQuery->execute([$publicacion['id']]);
    $archivos = $fileQuery->fetchAll();
    foreach ($archivos as $archivo) {
        if ($archivo['tipo'] === 'imagen') {
            $imagenPortada = $archivo;
            break;
        }
    }
}

$meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fecha = $publicacion ? strtotime($publicacion['created_at']) : time();
$fechaTexto = date('j', $fecha) . ' de ' . $meses[(int) date('n', $fecha)] . ' de ' . date('Y', $fecha);
$primerArchivo = $archivos[0] ?? null;
$escaparRuta = static fn(string $ruta): string => str_replace('%2F', '/', rawurlencode($ruta));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $publicacion ? htmlspecialchars($publicacion['titulo']) . ' · TESCI LGAC' : 'Publicación no encontrada · TESCI LGAC' ?></title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
    <link rel="stylesheet" href="assets/css/publicacion.css?v=<?= filemtime(__DIR__ . '/assets/css/publicacion.css') ?>">
</head>

<body class="portal-home news-page">
    <?php require __DIR__ . '/includes/portal_header.php'; ?>

    <?php if (!$publicacion): ?>
        <main class="news-not-found">
            <p class="news-category">TESCI · LGAC</p>
            <h1>Publicación no encontrada</h1>
            <p>Es posible que haya sido retirada o que ya no esté disponible públicamente.</p>
            <a class="news-back" href="index.php#publicaciones">← Volver a publicaciones</a>
        </main>
    <?php else: ?>
        <main class="news-content">
            <nav class="news-breadcrumbs" aria-label="Ruta de navegación"><a href="index.php">Inicio</a> &gt; <a href="index.php#publicaciones">Publicaciones</a> &gt; <?= htmlspecialchars($publicacion['categoria']) ?></nav>
            <div class="news-layout">
                <article class="news-main-card">
                    <header class="news-article-header">
                        <p class="news-category"><?= htmlspecialchars($publicacion['categoria']) ?></p>
                        <h1 class="news-title"><?= htmlspecialchars($publicacion['titulo']) ?></h1>
                    </header>

                    <?php if ($imagenPortada): ?>
                        <img class="news-cover" src="<?= UPLOAD_URL . $escaparRuta($imagenPortada['nombre_guardado']) ?>" alt="<?= htmlspecialchars($imagenPortada['nombre_original']) ?>">
                    <?php endif; ?>

                    <section class="news-description-section" aria-labelledby="description-heading">
                        <h2 id="description-heading"><span aria-hidden="true">●</span> Descripción</h2>
                        <p class="news-description"><?= nl2br(htmlspecialchars($publicacion['descripcion'] ?? 'Esta publicación no tiene una descripción adicional.')) ?></p>
                    </section>

                    <section class="news-responsible" aria-labelledby="responsible-heading">
                        <h2 id="responsible-heading"><span aria-hidden="true">♟</span> Docente responsable</h2>
                        <p><?= htmlspecialchars(trim(($publicacion['grado'] ?? '') . ' ' . $publicacion['nombre'] . ' ' . $publicacion['apellidos'])) ?></p>
                    </section>

                    <?php if ($archivos): ?>
                        <section class="news-attachments" aria-labelledby="attachments-heading">
                            <h2 id="attachments-heading">Archivos y recursos</h2>
                            <div class="news-attachment-list">
                                <?php foreach ($archivos as $archivo): ?>
                                    <a class="news-attachment" href="<?= UPLOAD_URL . $escaparRuta($archivo['nombre_guardado']) ?>" target="_blank" rel="noopener noreferrer">
                                        <span class="news-attachment-name"><?= htmlspecialchars($archivo['nombre_original']) ?></span>
                                        <span><?= $archivo['tipo'] === 'imagen' ? 'Ver imagen ↗' : 'Abrir archivo ↗' ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <div class="news-actions">
                        <?php if ($primerArchivo): ?>
                            <a class="news-action-primary" href="<?= UPLOAD_URL . $escaparRuta($primerArchivo['nombre_guardado']) ?>" target="_blank" rel="noopener noreferrer">↗ Abrir material</a>
                        <?php endif; ?>
                        <button class="news-action-share" id="share-publication" type="button">↗ Compartir</button>
                        <span id="share-status" class="news-share-status" aria-live="polite"></span>
                    </div>
                </article>

                <aside class="news-sidebar" aria-labelledby="resource-info-heading">
                    <h2 id="resource-info-heading">Información del recurso</h2>
                    <dl class="news-details-list">
                        <div class="news-detail-item">
                            <dt><span aria-hidden="true">▰</span> Categoría</dt>
                            <dd><?= htmlspecialchars($publicacion['categoria']) ?></dd>
                        </div>
                        <?php if ($publicacion['subcategoria']): ?>
                            <div class="news-detail-item">
                                <dt><span aria-hidden="true">▰</span> Subcategoría</dt>
                                <dd><?= htmlspecialchars($publicacion['subcategoria']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <div class="news-detail-item">
                            <dt><span aria-hidden="true">♟</span> Responsable</dt>
                            <dd><?= htmlspecialchars(trim(($publicacion['grado'] ?? '') . ' ' . $publicacion['nombre'] . ' ' . $publicacion['apellidos'])) ?></dd>
                        </div>
                        <div class="news-detail-item">
                            <dt><span aria-hidden="true">▦</span> Fecha de creación</dt>
                            <dd><time datetime="<?= date('Y-m-d', $fecha) ?>"><?= htmlspecialchars($fechaTexto) ?></time></dd>
                        </div>
                        <?php if ($publicacion['anio']): ?>
                            <div class="news-detail-item">
                                <dt><span aria-hidden="true">▦</span> Año académico</dt>
                                <dd><?= intval($publicacion['anio']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <div class="news-detail-item">
                            <dt><span aria-hidden="true">▤</span> Recursos adjuntos</dt>
                            <dd><?= count($archivos) ?></dd>
                        </div>
                    </dl>
                </aside>
            </div>
            <a class="news-back" href="index.php#publicaciones">← Volver a publicaciones</a>
        </main>
    <?php endif; ?>

    <footer class="news-footer"><strong>TESCI · Cuerpo Académico LGAC</strong><br>Tecnológico de Estudios Superiores de Cuautitlán Izcalli · <?= date('Y') ?></footer>
    <?php if ($publicacion): ?>
        <script src="assets/js/publicacion.js?v=<?= filemtime(__DIR__ . '/assets/js/publicacion.js') ?>" defer></script>
    <?php endif; ?>
</body>

</html>