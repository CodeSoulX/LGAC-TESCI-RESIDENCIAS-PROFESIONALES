<?php
require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="es">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acerca del Cuerpo Académico · TESCI</title>
    <link rel="icon" type="image/png" href="assets/logoRino.jpg">
    <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime(__DIR__ . '/assets/css/main.css') ?>">
</head>

<body class="portal-home">
    <?php require __DIR__ . '/includes/portal_header.php'; ?>
    <main id="inicio">
        <section class="portal-section" id="acerca">
            <div class="portal-wrap about-layout">
                <div>
                    <div class="portal-section-heading">
                        <span class="portal-kicker">Investigación y colaboración</span>
                        <h2>¿Qué es este Cuerpo Académico?</h2>
                        <p>Espacio institucional para presentar líneas de investigación, proyectos, productos y actividades con información seleccionada para difusión pública por profesores investigadores del Tecnológico de Estudios Cuautitlan Izcalli enfocados en el desarrollo de soluciones inovadoras mediante la aplicación de las Tecnologías de la Información y la Comunicación en la Ingenería.</p>
                    </div>
                    <div class="about-cards">
                        <article class="about-card"><span class="about-card-mark" aria-hidden="true">◎</span>
                            <h3>Objetivo</h3>
                            <p>Fortalecer la investigación, colaboración y difusión de resultados.</p>
                        </article>
                        <article class="about-card"><span class="about-card-mark" aria-hidden="true">▱</span>
                            <h3>LGAC: Ingenería de Software y Sistemas mecatrónicos.</h3><br>
                            <h3>Areas de Trabajo</h3>
                            <ul>
                                <li>Desarrollo de software</li>
                                <li>Comunicación y Redes</li>
                                <li>Integración de la IA en Sistemas</li>
                                <li>Robótica y automatización</li>
                                <li>Materiales: propiedades mecánicas e ingeniería de superficies</li>
                                <li>Electromovilidad</li>
                                <li>Modelado matemático</li>
                            </ul>
                        </article>
                        <article class="about-card"><span class="about-card-mark" aria-hidden="true">⌘</span>
                            <h3>Colaboración</h3>
                            <p>Impulsar el trabajo académico entre integrantes e instituciones.</p>
                        </article>
                    </div>
                </div>
                <aside class="contact-panel" id="contacto">
                    <p class="portal-kicker">Información institucional</p>
                    <h3>Tecnológico de Estudios Superiores de Cuautitlán Izcalli</h3>
                    <p>Dirección: Fracción la Coyotera del Ejido, Av. Nopaltepec, Av San Antonio s/n, Industrial Cuamatla, 54748 Cuautitlán Izcalli, Méx.</p>
                    <hr>
                    <p>Portal de consulta pública del Cuerpo Académico TESCI · LGAC.</p>
                </aside>
            </div>
        </section>
    </main>
    <?php require __DIR__ . '/includes/portal_footer.php'; ?>
</body>

</html>