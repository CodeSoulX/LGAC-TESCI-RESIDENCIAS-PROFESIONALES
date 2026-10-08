<?php
require_once __DIR__ . '/includes/config.php';

$db = getDB();

// ── Publicaciones públicas ────────────────────
$pubs = $db->query(
  "SELECT p.id, p.titulo, p.descripcion, p.anio, p.subcategoria, p.destacado, p.created_at,
            c.id AS categoria_id, c.nombre AS categoria, c.icono,
            u.nombre, u.apellidos, u.grado, u.foto_perfil
     FROM publicaciones p
     JOIN categorias c ON c.id = p.categoria_id
     JOIN usuarios   u ON u.id = p.usuario_id
     WHERE p.visible = 1 AND u.activo = 1
    ORDER BY p.created_at DESC"
)->fetchAll();

$categorias = $db->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();
$subcategorias = $db->query("SELECT categoria_id, nombre FROM subcategorias ORDER BY nombre")->fetchAll();
$subcategoriasPorCategoria = [];
foreach ($subcategorias as $subcategoria) {
  $subcategoriasPorCategoria[$subcategoria['categoria_id']][$subcategoria['nombre']] = true;
}

// ── Archivos de cada publicación ──────────────
$pub_ids = array_column($pubs, 'id');
$archivos_map = [];
if ($pub_ids) {
  $in = implode(',', array_fill(0, count($pub_ids), '?'));
  $stmt = $db->prepare(
    "SELECT publicacion_id, nombre_original, nombre_guardado, tipo
         FROM archivos WHERE publicacion_id IN ($in)"
  );
  $stmt->execute($pub_ids);
  foreach ($stmt->fetchAll() as $a) {
    $archivos_map[$a['publicacion_id']][] = $a;
  }
}

function iconoHtml(string $icono): string
{
  $mapa = [
    'flask-conical' => '🔬',
    'graduation-cap' => '🎓',
    'book-open' => '📖',
    'file-text' => '📄',
    'calendar' => '📅',
    'award' => '🏆',
    'image' => '🖼️',
    'paperclip' => '📎',
  ];
  return $mapa[$icono] ?? '📁';
}

$imagenes = [];
foreach ($archivos_map as $adjuntos) {
  foreach ($adjuntos as $adjunto) {
    if ($adjunto['tipo'] === 'imagen') {
      $imagenes[] = $adjunto;
    }
  }
}

$mediosAdminGaleria = $db->query(
  "SELECT id, tipo, titulo, url, nombre_guardado
   FROM galeria_medios
   ORDER BY created_at DESC, id DESC"
)->fetchAll();

$elementosGaleria = [];
foreach ($mediosAdminGaleria as $medio) {
  $elementosGaleria[] = $medio;
}
foreach ($imagenes as $imagen) {
  $elementosGaleria[] = [
    'tipo' => 'imagen',
    'titulo' => $imagen['nombre_original'],
    'url' => null,
    'nombre_guardado' => $imagen['nombre_guardado'],
  ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cuerpo Académico LGAC · TESCI</title>
  <meta name="description" content="Proyectos, publicaciones y actividades del Cuerpo Académico LGAC del TESCI.">
  <link rel="icon" type="image/png" href="assets/logoRino.jpg">
  <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">

</head>

<body class="portal-home">
  <?php require __DIR__ . '/includes/portal_header.php'; ?>

  <main id="inicio">
    <section class="portal-section portal-section--soft" id="publicaciones">
      <div class="portal-wrap publication-layout">
        <aside class="publication-filters" aria-label="Filtros de publicaciones">
          <div class="publication-filter-heading">
            <h3>Filtros</h3>
            <button id="publication-reset" type="button">Limpiar</button>
          </div>
          <div class="publication-filter-group">
            <h4>Categorías</h4>
            <div class="publication-filter-list">
              <?php foreach ($categorias as $categoria): ?>
                <label class="publication-filter-option">
                  <input class="publication-category-filter" type="checkbox" value="<?= intval($categoria['id']) ?>">
                  <span><?= htmlspecialchars($categoria['nombre']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="publication-filter-group">
            <h4>Subcategorías</h4>
            <div class="publication-filter-list" id="publication-subcategory-filters">
              <?php foreach ($subcategoriasPorCategoria as $categoriaId => $subcategorias): ?>
                <?php foreach (array_keys($subcategorias) as $subcategoria): ?>
                  <label class="publication-filter-option" data-category="<?= intval($categoriaId) ?>">
                    <input class="publication-subcategory-filter" type="checkbox" value="<?= htmlspecialchars($subcategoria, ENT_QUOTES, 'UTF-8') ?>">
                    <span><?= htmlspecialchars($subcategoria) ?></span>
                  </label>
                <?php endforeach; ?>
              <?php endforeach; ?>
              <?php if (!$subcategoriasPorCategoria): ?>
                <p class="publication-filter-empty">Aún no hay subcategorías registradas.</p>
              <?php endif; ?>
            </div>
          </div>
        </aside>

        <div class="publication-results">
          <label class="publication-searchbar" for="publication-search">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="10.8" cy="10.8" r="6.8"></circle>
              <path d="m16 16 5 5"></path>
            </svg>
            <input id="publication-search" type="search" placeholder="Buscar publicaciones..." autocomplete="off">
          </label>
          <div class="portal-section-heading">
            <span class="portal-kicker">Actividad académica</span>
            <h2>Publicaciones y productos académicos</h2>
            <p>Explora los proyectos, productos y actividades compartidos por el cuerpo académico.</p>
          </div>
          <div class="publication-result-toolbar">
            <p id="publication-result-count" aria-live="polite"></p>
            <p class="publication-page-status">Página <span id="publication-current-page">1</span> de <span id="publication-total-pages">1</span></p>
          </div>
          <div class="publication-carousel" aria-label="Carrusel de publicaciones">
            <div class="publication-feed" id="publication-feed">
              <?php foreach ($pubs as $p): ?>
                <?php
                $portada = null;
                foreach ($archivos_map[$p['id']] ?? [] as $archivo) {
                  if ($archivo['tipo'] === 'imagen') {
                    $portada = $archivo;
                    break;
                  }
                }
                $textoBusqueda = mb_strtolower($p['titulo'] . ' ' . ($p['descripcion'] ?? '') . ' ' . $p['categoria'] . ' ' . ($p['subcategoria'] ?? '') . ' ' . ($p['grado'] ?? '') . ' ' . $p['nombre'] . ' ' . $p['apellidos'], 'UTF-8');
                $fechaPublicacion = strtotime($p['created_at']);
                ?>
                <a class="publication-card" href="publicacion.php?id=<?= intval($p['id']) ?>"
                  data-category="<?= intval($p['categoria_id']) ?>"
                  data-subcategory="<?= htmlspecialchars($p['subcategoria'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-search="<?= htmlspecialchars($textoBusqueda, ENT_QUOTES, 'UTF-8') ?>">
                  <time class="publication-card-date" datetime="<?= date('Y-m-d', $fechaPublicacion) ?>"><?= date('j/m/Y', $fechaPublicacion) ?></time>
                  <div class="publication-card-media">
                    <?php if ($portada): ?>
                      <img src="<?= UPLOAD_URL . str_replace('%2F', '/', rawurlencode($portada['nombre_guardado'])) ?>" alt="Imagen de <?= htmlspecialchars($p['titulo']) ?>" loading="lazy">
                    <?php else: ?>
                      <span class="publication-card-placeholder" aria-hidden="true"><?= iconoHtml($p['icono']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="publication-card-copy">
                    <span class="publication-card-category"><?= htmlspecialchars($p['categoria']) ?></span>
                    <h3><?= htmlspecialchars($p['titulo']) ?></h3>
                    <p class="publication-card-summary"><?= htmlspecialchars($p['descripcion'] ?? '') ?></p>
                    <p class="publication-card-summary"><?= htmlspecialchars(trim(($p['grado'] ?? '') . ' ' . $p['nombre'] . ' ' . $p['apellidos'])) ?><?= $p['anio'] ? ' · ' . htmlspecialchars($p['anio']) : '' ?></p>
                    <span class="publication-read-more">Leer Más</span>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
            <p class="publication-empty" id="publication-no-results" hidden>No hay publicaciones que coincidan con los filtros seleccionados.</p>
            <div class="publication-carousel-controls">
              <div class="publication-carousel-buttons">
                <button class="publication-carousel-button" id="publication-previous" type="button" aria-label="Página anterior">‹</button>
                <button class="publication-carousel-button" id="publication-next" type="button" aria-label="Página siguiente">›</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="portal-section portal-section--soft" id="galeria">
      <div class="portal-wrap">
        <div class="portal-section-heading">
          <span class="portal-kicker">Registro visual</span>
          <h2>Galería multimedia</h2>
          <p>Fotografías y videos de actividades académicas del cuerpo académico.</p>
        </div>
        <div class="gallery-grid">
          <?php foreach (array_slice($elementosGaleria, 0, 6) as $medio): ?>
            <?php if ($medio['tipo'] === 'imagen'): ?>
              <?php $imageUrl = UPLOAD_URL . str_replace('%2F', '/', rawurlencode($medio['nombre_guardado'])); ?>
              <a class="gallery-item" href="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($medio['titulo']) ?>" loading="lazy">
              </a>
            <?php else: ?>
              <a class="gallery-item gallery-video" href="<?= htmlspecialchars($medio['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                <span class="gallery-video-icon" aria-hidden="true">▶</span>
                <strong><?= htmlspecialchars($medio['titulo']) ?></strong>
                <span>Ver video ↗</span>
              </a>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if (!$elementosGaleria): ?>
            <div class="gallery-item">
              <div class="gallery-empty">Las imágenes y videos agregados por administración, junto con imágenes de publicaciones públicas, aparecerán aquí.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

  </main>

  <?php require __DIR__ . '/includes/portal_footer.php'; ?>
  <script src="assets/js/publicaciones.js?v=<?= filemtime(__DIR__ . '/assets/js/publicaciones.js') ?>" defer></script>
</body>

</html>