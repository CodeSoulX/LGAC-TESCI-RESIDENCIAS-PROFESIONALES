<?php
require_once __DIR__ . '/includes/config.php';

$db = getDB();

// ── Docentes activos ──────────────────────────
$docentes = $db->query(
  "SELECT id, nombre, apellidos, grado, especialidad, foto_perfil
     FROM usuarios
     WHERE rol = 'docente' AND activo = 1
     ORDER BY apellidos"
)->fetchAll();

// ── Publicaciones recientes (último mes) ──────
$pubs = $db->query(
  "SELECT p.id, p.titulo, p.descripcion, p.anio, p.destacado, p.created_at,
            c.nombre AS categoria, c.icono,
            u.nombre, u.apellidos, u.grado, u.foto_perfil
     FROM publicaciones p
     JOIN categorias c ON c.id = p.categoria_id
     JOIN usuarios   u ON u.id = p.usuario_id
     WHERE p.visible = 1 AND u.activo = 1
       AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
     ORDER BY p.created_at DESC
     LIMIT 60"
)->fetchAll();

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

$anios = array_values(array_unique(array_filter(array_column($pubs, 'anio'))));
$imagenes = [];
foreach ($archivos_map as $adjuntos) {
  foreach ($adjuntos as $adjunto) {
    if ($adjunto['tipo'] === 'imagen') {
      $imagenes[] = $adjunto;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cuerpo Académico LGAC · TESCI</title>
  <meta name="description" content="Proyectos, publicaciones y actividades del Cuerpo Académico LGAC del TESCI.">
  <link rel="icon" type="image/png" href="assets/logo.png">
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    .portal-home {
      --portal-blue: #194dbb;
      --portal-navy: #242424;
      --portal-red: #e10b0b;
      --portal-ink: #203148;
      --portal-muted: #627286;
      --portal-line: #e1e8f0;
      --portal-paper: #f4f7fb;
      color: var(--portal-ink);
      background: #fff;
      font-family: 'Inter', sans-serif;
    }

    .portal-home h1,
    .portal-home h2,
    .portal-home h3 {
      color: var(--portal-ink);
      line-height: 1.2;
    }

    .portal-wrap {
      width: min(1180px, calc(100% - 48px));
      margin-inline: auto;
    }

    .institutional-header {
      color: #fff;
      background: linear-gradient(105deg, #194dbb, #2865cf 62%, #1643a4);
    }

    .institutional-top {
      min-height: 132px;
      display: grid;
      grid-template-columns: 150px 1fr auto;
      align-items: center;
      gap: 30px;
      padding-block: 16px;
    }

    .institutional-logo {
      height: 132px;
      width: 132px;
      object-fit: contain;
      background: #fff;
      padding: 8px;
    }

    .institutional-name {
      text-align: center;
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    .institutional-name strong {
      display: block;
      font-size: 1.55rem;
      font-weight: 500;
    }

    .institutional-name span {
      display: block;
      margin-top: 8px;
      font-size: .78rem;
      letter-spacing: .4em;
    }

    .institutional-program {
      max-width: 210px;
      text-align: right;
      font-size: .95rem;
      line-height: 1.45;
    }

    .institutional-nav {
      background: var(--portal-red);
    }

    .institutional-nav .portal-wrap {
      min-height: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: clamp(16px, 4vw, 56px);
    }

    .institutional-nav a {
      color: #fff;
      text-transform: uppercase;
      font-size: .76rem;
      font-weight: 600;
      letter-spacing: .06em;
      white-space: nowrap;
    }

    .institutional-nav a:hover {
      color: var(--portal-navy);
    }

    .portal-intro {
      padding-block: 48px;
      border-bottom: 1px solid #eef1f5;
    }

    .portal-intro-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 28px;
    }

    .portal-kicker {
      color: var(--portal-blue);
      text-transform: uppercase;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .12em;
    }

    .portal-intro h1 {
      margin-top: 8px;
      font-size: clamp(1.55rem, 3vw, 2rem);
    }

    .portal-intro p {
      margin-top: 8px;
      color: var(--portal-muted);
    }

    .portal-link-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 42px;
      padding: 0 16px;
      border: 1px solid var(--portal-line);
      border-radius: 5px;
      color: var(--portal-navy);
      background: #fff;
      font-size: .88rem;
      white-space: nowrap;
    }

    .portal-link-button:hover {
      border-color: var(--portal-blue);
      color: var(--portal-blue);
    }

    .portal-section {
      padding-block: 62px;
    }

    .portal-section--soft {
      background: var(--portal-paper);
    }

    .portal-section-heading {
      margin-bottom: 24px;
    }

    .portal-section-heading h2 {
      font-size: clamp(1.35rem, 2.6vw, 1.75rem);
    }

    .portal-section-heading p {
      margin-top: 7px;
      color: var(--portal-muted);
    }

    .publication-layout {
      display: block;
    }

    .publication-tools {
      display: grid;
      grid-template-columns: minmax(180px, 1fr) minmax(150px, .8fr) minmax(120px, .55fr);
      gap: 8px;
      padding: 12px;
      border: 1px solid var(--portal-line);
      border-radius: 8px;
      background: #fff;
      box-shadow: 0 12px 30px rgba(29, 56, 92, .06);
    }

    .publication-tools input,
    .publication-tools select {
      width: 100%;
      min-width: 0;
      height: 42px;
      padding: 0 12px;
      border: 1px solid var(--portal-line);
      border-radius: 5px;
      background: #fff;
      color: var(--portal-ink);
      font: inherit;
      font-size: .87rem;
    }

    .publication-layout {
      display: block;
    }

    .publication-carousel {
      position: relative;
      margin-top: 25px;
    }

    .publication-feed {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }

    .portal-home [hidden] {
      display: none !important;
    }

    .publication-card {
      display: flex;
      min-width: 0;
      min-height: 330px;
      flex-direction: column;
      overflow: hidden;
      border: 1px solid #e6e9ed;
      border-top: 6px solid var(--portal-red);
      background: #fff;
      color: var(--portal-ink);
      text-decoration: none;
      transition: transform .18s ease, box-shadow .18s ease;
    }

    .publication-card:hover {
      color: var(--portal-ink);
      transform: translateY(-3px);
      box-shadow: 0 10px 24px rgba(32, 49, 72, .13);
    }

    .publication-card-date {
      padding: 12px 14px 4px;
      color: #56813a;
      font-size: .78rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .publication-card-media {
      display: grid;
      width: 142px;
      height: 142px;
      flex: 0 0 auto;
      place-items: center;
      overflow: hidden;
      align-self: center;
      margin: 8px auto 10px;
      border-radius: 50%;
      background: linear-gradient(140deg, #1c4779, #6c99bf);
      color: #fff;
    }

    .publication-card-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .publication-card-placeholder {
      font-size: 2.7rem;
    }

    .publication-card-copy {
      display: flex;
      flex: 1;
      flex-direction: column;
      align-items: flex-start;
      padding: 0 14px 14px;
    }

    .publication-card-category {
      color: var(--portal-muted);
      font-size: .68rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .publication-card h3 {
      margin-top: 5px;
      font-size: .98rem;
      line-height: 1.4;
    }

    .publication-card-summary {
      display: -webkit-box;
      overflow: hidden;
      margin-top: 5px;
      color: var(--portal-muted);
      font-size: .8rem;
      line-height: 1.45;
      -webkit-box-orient: vertical;
      line-clamp: 2;
      -webkit-line-clamp: 2;
    }

    .publication-read-more {
      margin-top: auto;
      padding: 7px 10px;
      background: #363b3e;
      color: #fff;
      font-size: .7rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .publication-carousel-controls {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-top: 16px;
    }

    .publication-carousel-status {
      color: var(--portal-muted);
      font-size: .8rem;
    }

    .publication-carousel-buttons {
      display: flex;
      gap: 7px;
    }

    .publication-carousel-button {
      display: grid;
      width: 40px;
      height: 40px;
      place-items: center;
      border: 1px solid var(--portal-line);
      border-radius: 4px;
      background: #fff;
      color: var(--portal-navy);
      font-size: 1.2rem;
      cursor: pointer;
    }

    .publication-carousel-button:hover:not(:disabled) {
      border-color: var(--portal-blue);
      color: var(--portal-blue);
    }

    .publication-carousel-button:disabled {
      opacity: .42;
      cursor: not-allowed;
    }

    .publication-empty {
      padding: 22px;
      border: 1px dashed #c9d4e1;
      border-radius: 6px;
      color: var(--portal-muted);
      background: rgba(255, 255, 255, .65);
    }

    .empty-state {
      padding: 22px;
      border: 1px dashed #c9d4e1;
      border-radius: 6px;
      color: var(--portal-muted);
      background: rgba(255, 255, 255, .65);
    }

    .about-layout {
      display: grid;
      grid-template-columns: minmax(0, 1.7fr) minmax(240px, .8fr);
      gap: 30px;
      align-items: stretch;
    }

    .about-cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-top: 22px;
    }

    .about-card,
    .contact-panel {
      padding: 20px;
      border: 1px solid var(--portal-line);
      border-radius: 7px;
      background: #fff;
    }

    .about-card-mark {
      display: grid;
      width: 36px;
      height: 36px;
      place-items: center;
      margin-bottom: 13px;
      border-radius: 6px;
      color: var(--portal-blue);
      background: #e5edf8;
    }

    .about-card h3 {
      font-size: .95rem;
    }

    .about-card p,
    .contact-panel p {
      margin-top: 7px;
      color: var(--portal-muted);
      font-size: .83rem;
    }

    .contact-panel h3 {
      margin-top: 10px;
      font-size: 1rem;
    }

    .contact-panel hr {
      margin-block: 18px;
      border: 0;
      border-top: 1px solid var(--portal-line);
    }

    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }

    .gallery-item {
      min-height: 190px;
      overflow: hidden;
      border-radius: 7px;
      background: #e5edf8;
    }

    .gallery-item img {
      width: 100%;
      height: 190px;
      object-fit: cover;
    }

    .gallery-empty {
      display: grid;
      min-height: 190px;
      place-items: center;
      padding: 20px;
      color: var(--portal-muted);
      text-align: center;
    }

    .people-heading {
      display: flex;
      align-items: end;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 22px;
    }

    .people-count {
      padding: 6px 12px;
      border-radius: 20px;
      color: var(--portal-navy);
      background: #e5edf8;
      font-size: .75rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .portal-home .grid-docentes {
      grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
      gap: 14px;
    }

    .portal-home .tarjeta-docente {
      border: 1px solid var(--portal-line);
      border-radius: 7px;
      box-shadow: 0 8px 24px rgba(29, 56, 92, .06);
    }

    .portal-home .tarjeta-docente .nombre {
      overflow-wrap: anywhere;
    }

    .people-avatar {
      display: grid;
      width: 80px;
      height: 80px;
      place-items: center;
      margin: 0 auto .85rem;
      border-radius: 50%;
      color: #fff;
      background: var(--portal-blue);
      font-size: 1.6rem;
      font-weight: 700;
    }

    .portal-footer {
      margin: 0 !important;
      padding-block: 30px !important;
      text-align: left !important;
      background: var(--portal-navy) !important;
    }

    .portal-footer-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
    }

    .portal-footer-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .portal-footer-brand img {
      width: 46px;
      height: 46px;
      object-fit: contain;
      padding: 3px;
      border-radius: 5px;
      background: #fff;
    }

    .portal-footer p {
      margin-top: 3px;
    }

    .portal-footer-links {
      display: flex;
      gap: 18px;
    }

    .portal-footer-links a {
      color: #fff;
      font-size: .8rem;
    }

    @media (max-width: 760px) {
      .portal-wrap {
        width: min(100% - 32px, 600px);
      }

      .institutional-top {
        grid-template-columns: 78px 1fr;
        min-height: 100px;
        gap: 14px;
      }

      .institutional-logo {
        width: 76px;
        height: 76px;
        padding: 4px;
      }

      .institutional-name {
        text-align: left;
      }

      .institutional-name strong {
        font-size: 1.15rem;
      }

      .institutional-name span {
        font-size: .62rem;
        letter-spacing: .18em;
      }

      .institutional-program {
        grid-column: 2;
        margin-top: -12px;
        text-align: left;
        font-size: .76rem;
      }

      .institutional-nav .portal-wrap {
        justify-content: flex-start;
        gap: 22px;
        min-height: 44px;
        overflow-x: auto;
      }

      .institutional-nav a {
        font-size: .68rem;
      }

      .portal-intro {
        padding-block: 34px;
      }

      .portal-intro-row {
        align-items: flex-start;
        flex-direction: column;
        gap: 17px;
      }

      .portal-section {
        padding-block: 42px;
      }

      .publication-layout,
      .about-layout {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .publication-feed {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .publication-tools {
        grid-template-columns: 1fr 1fr;
      }

      .publication-tools input {
        grid-column: 1 / -1;
      }

      .about-cards {
        grid-template-columns: 1fr;
      }

      .gallery-grid {
        grid-template-columns: 1fr 1fr;
      }

      .people-heading {
        align-items: flex-start;
        flex-direction: column;
      }

      .portal-footer-row {
        align-items: flex-start;
        flex-direction: column;
      }
    }

    @media (max-width: 420px) {
      .publication-tools {
        grid-template-columns: 1fr;
      }

      .publication-tools input {
        grid-column: auto;
      }

      .publication-feed {
        grid-template-columns: 1fr;
      }

      .gallery-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body class="portal-home">
  <header class="institutional-header">
    <div class="portal-wrap institutional-top">
      <img class="institutional-logo" src="assets/logo.png" alt="Logotipo del Tecnológico de Estudios Superiores de Cuautitlán Izcalli">
      <div class="institutional-name">
        <strong>Posgrado en Ciencias de la Información</strong>
        <span>Investigación · Innovación · Desarrollo</span>
      </div>
      <div class="institutional-program">Cuerpo Académico<br><strong>TESCI · LGAC</strong></div>
    </div>
  </header>
  <nav class="institutional-nav" aria-label="Navegación principal">
    <div class="portal-wrap">
      <a href="#inicio">Inicio</a>
      <a href="#publicaciones">Publicaciones</a>
      <a href="#acerca">Cuerpo académico</a>
      <a href="#galeria">Galería</a>
      <a href="#integrantes">Integrantes</a>
    </div>
  </nav>

  <main id="inicio">
    <section class="portal-intro">
      <div class="portal-wrap portal-intro-row">
        <div>
          <span class="portal-kicker">TESCI · Cuerpo Académico</span>
          <h1>Proyectos y actividades publicados</h1>
          <p>Una vitrina institucional para compartir investigación, conocimiento y actividades académicas.</p>
        </div>
        <a class="portal-link-button" href="#publicaciones">Ver publicaciones <span aria-hidden="true">→</span></a>
      </div>
    </section>

    <section class="portal-section portal-section--soft" id="publicaciones">
      <div class="portal-wrap publication-layout">
        <div>
          <div class="portal-section-heading">
            <span class="portal-kicker">Actividad académica</span>
            <h2>Línea del tiempo · Publicaciones</h2>
            <p>Consulta las publicaciones compartidas durante los últimos 30 días.</p>
          </div>
          <div class="publication-carousel" aria-label="Carrusel de publicaciones recientes">
            <?php if ($pubs): ?>
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
                  $fechaPublicacion = strtotime($p['created_at']);
                  $mesesEspanol = [1 => 'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
                  ?>
                  <a class="publication-card" href="publicacion.php?id=<?= intval($p['id']) ?>" target="_blank" rel="noopener">
                    <time class="publication-card-date" datetime="<?= date('Y-m-d', $fechaPublicacion) ?>"><?= date('j', $fechaPublicacion) ?> <?= $mesesEspanol[(int) date('n', $fechaPublicacion)] ?> <?= date('Y', $fechaPublicacion) ?></time>
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
                      <span class="publication-read-more">Leer más</span>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
              <div class="publication-carousel-controls">
                <p class="publication-carousel-status" id="publication-carousel-status" aria-live="polite"></p>
                <div class="publication-carousel-buttons">
                  <button class="publication-carousel-button" id="publication-previous" type="button" aria-label="Publicaciones anteriores">‹</button>
                  <button class="publication-carousel-button" id="publication-next" type="button" aria-label="Publicaciones siguientes">›</button>
                </div>
              </div>
            <?php else: ?>
              <p class="publication-empty">No hay publicaciones públicas de los últimos 30 días.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="portal-section" id="acerca">
      <div class="portal-wrap about-layout">
        <div>
          <div class="portal-section-heading">
            <span class="portal-kicker">Investigación y colaboración</span>
            <h2>¿Qué es este Cuerpo Académico?</h2>
            <p>Espacio institucional para presentar integrantes, líneas de investigación, proyectos, productos y actividades con información seleccionada para difusión pública.</p>
          </div>
          <div class="about-cards">
            <article class="about-card"><span class="about-card-mark" aria-hidden="true">◎</span>
              <h3>Objetivo</h3>
              <p>Fortalecer la investigación, colaboración y difusión de resultados.</p>
            </article>
            <article class="about-card"><span class="about-card-mark" aria-hidden="true">▱</span>
              <h3>LGAC</h3>
              <p>Consultar las líneas de generación y aplicación del conocimiento.</p>
            </article>
            <article class="about-card"><span class="about-card-mark" aria-hidden="true">⌘</span>
              <h3>Colaboración</h3>
              <p>Impulsar el trabajo académico entre integrantes e instituciones.</p>
            </article>
          </div>
        </div>
        <aside class="contact-panel">
          <p class="portal-kicker">Información institucional</p>
          <h3>Tecnológico de Estudios Superiores de Cuautitlán Izcalli</h3>
          <p>Cuautitlán Izcalli · Estado de México</p>
          <hr>
          <p>Portal de consulta pública del Cuerpo Académico TESCI · LGAC.</p>
        </aside>
      </div>
    </section>

    <section class="portal-section portal-section--soft" id="galeria">
      <div class="portal-wrap">
        <div class="portal-section-heading">
          <span class="portal-kicker">Registro visual</span>
          <h2>Galería multimedia</h2>
          <p>Imágenes adjuntas a las publicaciones públicas del cuerpo académico.</p>
        </div>
        <div class="gallery-grid">
          <?php foreach (array_slice($imagenes, 0, 6) as $imagen): ?>
            <a class="gallery-item" href="<?= UPLOAD_URL . rawurlencode($imagen['nombre_guardado']) ?>" target="_blank" rel="noopener"><img src="<?= UPLOAD_URL . rawurlencode($imagen['nombre_guardado']) ?>" alt="<?= htmlspecialchars($imagen['nombre_original']) ?>" loading="lazy"></a>
          <?php endforeach; ?>
          <?php if (!$imagenes): ?><div class="gallery-item">
              <div class="gallery-empty">Las fotografías de actividades se mostrarán aquí cuando se adjunten a una publicación pública.</div>
            </div><?php endif; ?>
          <div class="gallery-item">
            <div class="gallery-empty"><strong>Videos y recursos<br>en publicaciones</strong></div>
          </div>
          <div class="gallery-item">
            <div class="gallery-empty"><strong>Proyectos y productos<br>académicos</strong></div>
          </div>
        </div>
      </div>
    </section>

    <section class="portal-section" id="integrantes">
      <div class="portal-wrap">
        <div class="people-heading">
          <div class="portal-section-heading" style="margin:0">
            <span class="portal-kicker">Comunidad académica</span>
            <h2>Integrantes</h2>
            <p>Cada investigador controla su información pública y decide qué datos mostrar.</p>
          </div>
          <span class="people-count"><?= count($docentes) ?> perfiles públicos</span>
        </div>
        <?php if ($docentes): ?>
          <div class="grid-docentes">
            <?php foreach ($docentes as $d): ?>
              <article class="tarjeta-docente">
                <?php if ($d['foto_perfil'] && file_exists(UPLOAD_PATH . $d['foto_perfil'])): ?>
                  <img src="<?= UPLOAD_URL . rawurlencode($d['foto_perfil']) ?>" alt="Foto de <?= htmlspecialchars($d['nombre']) ?>" loading="lazy">
                <?php else: ?>
                  <div class="people-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($d['nombre'], 0, 1))) ?></div>
                <?php endif; ?>
                <p class="grado"><?= htmlspecialchars($d['grado'] ?? '') ?></p>
                <p class="nombre"><?= htmlspecialchars($d['nombre'] . ' ' . $d['apellidos']) ?></p>
                <p class="espec"><?= htmlspecialchars($d['especialidad'] ?? 'Docente investigador') ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="empty-state">Próximamente se presentará la información pública de los integrantes.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="portal-footer">
    <div class="portal-wrap portal-footer-row">
      <div class="portal-footer-brand">
        <img src="assets/logo.png" alt="">
        <div><strong>TESCI · Cuerpo Académico LGAC</strong>
          <p>Tecnológico de Estudios Superiores de Cuautitlán Izcalli · <?= date('Y') ?></p>
        </div>
      </div>
      <nav class="portal-footer-links" aria-label="Accesos institucionales"><a href="admin/login.php">Administración</a><a href="docente/login.php">Acceso docente</a></nav>
    </div>
  </footer>
  <script>
    const publicationCards = [...document.querySelectorAll('.publication-card')];
    const previousButton = document.querySelector('#publication-previous');
    const nextButton = document.querySelector('#publication-next');
    const carouselStatus = document.querySelector('#publication-carousel-status');
    let firstVisiblePublication = 0;

    function getCarouselPageSize() {
      if (window.matchMedia('(max-width: 560px)').matches) return 1;
      if (window.matchMedia('(max-width: 900px)').matches) return 2;
      return 3;
    }

    function renderPublicationPage() {
      const pageSize = getCarouselPageSize();
      firstVisiblePublication = Math.floor(firstVisiblePublication / pageSize) * pageSize;
      publicationCards.forEach((card, index) => {
        card.hidden = index < firstVisiblePublication || index >= firstVisiblePublication + pageSize;
      });

      const firstNumber = publicationCards.length ? firstVisiblePublication + 1 : 0;
      const lastNumber = Math.min(firstVisiblePublication + pageSize, publicationCards.length);
      carouselStatus.textContent = `Mostrando ${firstNumber}–${lastNumber} de ${publicationCards.length} publicaciones recientes`;
      previousButton.disabled = firstVisiblePublication === 0;
      nextButton.disabled = firstVisiblePublication + pageSize >= publicationCards.length;
    }

    previousButton.addEventListener('click', () => {
      firstVisiblePublication = Math.max(0, firstVisiblePublication - getCarouselPageSize());
      renderPublicationPage();
    });
    nextButton.addEventListener('click', () => {
      firstVisiblePublication += getCarouselPageSize();
      renderPublicationPage();
    });
    window.addEventListener('resize', renderPublicationPage);
    renderPublicationPage();
  </script>
</body>

</html>