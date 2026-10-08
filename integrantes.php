<?php
require_once __DIR__ . '/includes/config.php';

$db = getDB();
$docentes = $db->query(
    "SELECT id, nombre, apellidos, grado, especialidad, foto_perfil
     FROM usuarios
     WHERE rol = 'docente' AND activo = 1
     ORDER BY apellidos"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Integrantes · Cuerpo Académico TESCI</title>
    <link rel="icon" type="image/png" href="assets/logoRino.jpg">
    <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
</head>

<body class="portal-home">
    <?php require __DIR__ . '/includes/portal_header.php'; ?>
    <main id="inicio">
        <section class="portal-section" id="integrantes">
            <div class="portal-wrap">
                <div class="people-heading">
                    <div class="portal-section-heading people-heading-copy">
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
                                    <img src="<?= UPLOAD_URL . str_replace('%2F', '/', rawurlencode($d['foto_perfil'])) ?>" alt="Foto de <?= htmlspecialchars($d['nombre']) ?>" loading="lazy">
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
    <?php require __DIR__ . '/includes/portal_footer.php'; ?>
</body>

</html>