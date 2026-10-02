<?php
require_once __DIR__ . '/includes/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$db = getDB();
$stmt = $db->prepare(
    "SELECT p.id, p.titulo, p.descripcion, p.anio, p.created_at,
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
$escaparRuta = static fn(string $ruta): string => str_replace('%2F', '/', rawurlencode($ruta));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $publicacion ? htmlspecialchars($publicacion['titulo']) . ' · TESCI LGAC' : 'Publicación no encontrada · TESCI LGAC' ?></title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .news-page {
            --news-blue: #1769aa;
            --news-navy: #203b67;
            --news-ink: #203148;
            --news-muted: #627286;
            --news-line: #e1e8f0;
            background: #fff;
            color: var(--news-ink);
            font-family: 'Inter', sans-serif;
        }

        .news-page h1,
        .news-page h2 {
            color: var(--news-ink);
            line-height: 1.2;
        }

        .news-header {
            background: #1769aa;
            color: #fff;
        }

        .news-header-inner {
            display: flex;
            min-height: 86px;
            align-items: center;
            gap: 16px;
        }

        .news-header-logo {
            width: 58px;
            height: 58px;
            padding: 3px;
            object-fit: contain;
            background: #fff;
        }

        .news-header-name {
            font-size: .92rem;
            font-weight: 700;
        }

        .news-header-name span {
            display: block;
            margin-top: 3px;
            font-size: .76rem;
            font-weight: 400;
            opacity: .85;
        }

        .news-breadcrumbs {
            padding-block: 25px 16px;
            color: var(--news-muted);
            font-size: .8rem;
        }

        .news-breadcrumbs a {
            color: var(--news-blue);
        }

        .news-content {
            width: min(900px, calc(100% - 40px));
            margin: 0 auto;
            padding-bottom: 70px;
        }

        .news-category {
            color: var(--news-blue);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .news-title {
            margin-top: 12px;
            font-size: clamp(2rem, 5vw, 3.4rem);
        }

        .news-byline {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 20px;
            margin-top: 18px;
            color: var(--news-muted);
            font-size: .88rem;
        }

        .news-cover {
            width: 100%;
            max-height: 520px;
            margin-top: 30px;
            object-fit: cover;
            background: #edf2f7;
        }

        .news-description {
            max-width: 760px;
            margin: 30px auto 0;
            color: #303b49;
            font-size: 1.08rem;
            line-height: 1.9;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .news-attachments {
            max-width: 760px;
            margin: 38px auto 0;
            padding-top: 24px;
            border-top: 1px solid var(--news-line);
        }

        .news-attachments h2 {
            margin-bottom: 14px;
            font-size: 1.15rem;
        }

        .news-attachment-list {
            display: grid;
            gap: 9px;
        }

        .news-attachment {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 13px 15px;
            border: 1px solid var(--news-line);
            border-radius: 5px;
            overflow-wrap: anywhere;
        }

        .news-attachment span {
            color: var(--news-muted);
            font-size: .78rem;
        }

        .news-back {
            display: inline-flex;
            margin-top: 34px;
            color: var(--news-blue);
            font-weight: 600;
        }

        .news-not-found {
            width: min(700px, calc(100% - 40px));
            min-height: 55vh;
            margin: auto;
            padding-block: 100px;
        }

        .news-not-found h1 {
            font-size: 2rem;
        }

        .news-not-found p {
            margin-top: 12px;
            color: var(--news-muted);
        }

        .news-footer {
            margin-top: 0 !important;
            background: var(--news-navy) !important;
        }

        @media (max-width: 600px) {
            .news-byline {
                flex-direction: column;
                gap: 5px;
            }

            .news-cover {
                margin-top: 22px;
            }

            .news-description {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body class="news-page">
    <header class="news-header">
        <div class="portal-wrap news-header-inner">
            <img class="news-header-logo" src="assets/logo.png" alt="">
            <p class="news-header-name">TESCI <span>Cuerpo Académico · LGAC</span></p>
        </div>
    </header>

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
            <article>
                <header>
                    <p class="news-category"><?= htmlspecialchars($publicacion['categoria']) ?></p>
                    <h1 class="news-title"><?= htmlspecialchars($publicacion['titulo']) ?></h1>
                    <div class="news-byline">
                        <span>Por <?= htmlspecialchars(trim(($publicacion['grado'] ?? '') . ' ' . $publicacion['nombre'] . ' ' . $publicacion['apellidos'])) ?></span>
                        <time datetime="<?= date('Y-m-d', $fecha) ?>"><?= htmlspecialchars($fechaTexto) ?></time>
                        <?php if ($publicacion['anio']): ?><span>Año académico: <?= intval($publicacion['anio']) ?></span><?php endif; ?>
                    </div>
                </header>
                <?php if ($imagenPortada): ?>
                    <img class="news-cover" src="<?= UPLOAD_URL . $escaparRuta($imagenPortada['nombre_guardado']) ?>" alt="<?= htmlspecialchars($imagenPortada['nombre_original']) ?>">
                <?php endif; ?>
                <div class="news-description"><?= nl2br(htmlspecialchars($publicacion['descripcion'] ?? 'Esta publicación no tiene una descripción adicional.')) ?></div>
                <?php if ($archivos): ?>
                    <section class="news-attachments" aria-labelledby="attachments-heading">
                        <h2 id="attachments-heading">Archivos y recursos</h2>
                        <div class="news-attachment-list">
                            <?php foreach ($archivos as $archivo): ?>
                                <a class="news-attachment" href="<?= UPLOAD_URL . $escaparRuta($archivo['nombre_guardado']) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($archivo['nombre_original']) ?><span><?= $archivo['tipo'] === 'imagen' ? 'Ver imagen ↗' : 'Abrir archivo ↗' ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </article>
            <a class="news-back" href="index.php#publicaciones">← Volver a publicaciones</a>
        </main>
    <?php endif; ?>

    <footer class="news-footer"><strong>TESCI · Cuerpo Académico LGAC</strong><br>Tecnológico de Estudios Superiores de Cuautitlán Izcalli · <?= date('Y') ?></footer>
</body>

</html>