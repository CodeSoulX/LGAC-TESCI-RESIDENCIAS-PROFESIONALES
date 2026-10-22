<?php
require_once __DIR__ . '/includes/config.php';

$db = getDB();
$docentes = $db->query(
    "SELECT id, nombre, apellidos, grado, especialidad, bio, foto_perfil
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
    <link rel="icon" type="image/png" href="assets/logoRino.png">
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
                            <?php
                            $nombreCompleto = trim($d['nombre'] . ' ' . $d['apellidos']);
                            $fotoUrl = $d['foto_perfil'] && file_exists(UPLOAD_PATH . $d['foto_perfil'])
                                ? UPLOAD_URL . str_replace('%2F', '/', rawurlencode($d['foto_perfil']))
                                : '';
                            ?>
                            <button
                                class="tarjeta-docente"
                                type="button"
                                data-person-name="<?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') ?>"
                                data-person-degree="<?= htmlspecialchars($d['grado'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-person-specialty="<?= htmlspecialchars($d['especialidad'] ?? 'Docente investigador', ENT_QUOTES, 'UTF-8') ?>"
                                data-person-bio="<?= htmlspecialchars($d['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-person-photo="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>"
                                aria-haspopup="dialog"
                                aria-label="Ver información de <?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($d['foto_perfil'] && file_exists(UPLOAD_PATH . $d['foto_perfil'])): ?>
                                    <img src="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="people-avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($d['nombre'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <span class="grado"><?= htmlspecialchars($d['grado'] ?? '') ?></span>
                                <span class="nombre"><?= htmlspecialchars($nombreCompleto) ?></span>
                                <span class="espec"><?= htmlspecialchars($d['especialidad'] ?? 'Docente investigador') ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Próximamente se presentará la información pública de los integrantes.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <dialog class="people-dialog" aria-labelledby="people-dialog-name">
        <button class="people-dialog-close" type="button" aria-label="Cerrar ventana">&times;</button>
        <div class="people-dialog-profile">
            <img class="people-dialog-photo" alt="" hidden>
            <span class="people-dialog-avatar" aria-hidden="true"></span>
            <div>
                <p class="people-dialog-degree"></p>
                <h2 id="people-dialog-name"></h2>
                <p class="people-dialog-specialty"></p>
            </div>
        </div>
        <section class="people-dialog-bio" aria-label="Semblanza">
            <h3>Acerca de</h3>
            <p></p>
        </section>
    </dialog>
    <?php require __DIR__ . '/includes/portal_footer.php'; ?>
    <script src="assets/js/integrantes.js?v=<?= filemtime(__DIR__ . '/assets/js/integrantes.js') ?>" defer></script>
</body>

</html>