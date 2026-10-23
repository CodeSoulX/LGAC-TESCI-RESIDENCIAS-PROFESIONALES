<header class="institutional-header">
    <div class="portal-wrap institutional-top">
        <div class="institutional-brand">
            <img class="institutional-logo" src="assets/logo.png" alt="">
            <div class="institutional-name">
                <strong>Cuerpo Académico</strong>
                <span>TIC aplicadas a la Ingeniería</span>
            </div>
        </div>
        <button class="portal-menu-toggle" type="button" aria-expanded="false" aria-controls="portal-shortcuts">
            <span class="sr-only">Abrir menú de navegación</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 6h18M3 12h18M3 18h18" />
            </svg>
        </button>
        <nav class="portal-shortcuts" id="portal-shortcuts" aria-label="Accesos principales">
            <div class="portal-wrap">
                <a class="portal-shortcut" href="<?= BASE_URL ?>/Proyectos">
                    <span class="portal-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 32 32">
                            <path d="M3 9h10l3 4h13v14H3z" />
                            <path d="M3 9V6h10l3 4" />
                        </svg></span>
                    <span><strong>Proyectos</strong><small>Trabajo en colaboración</small></span>
                </a>
                <a class="portal-shortcut" href="<?= BASE_URL ?>/Recursos">
                    <span class="portal-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 32 32">
                            <path d="M8 3h11l8 8v18H8z" />
                            <path d="M19 3v9h8M13 18h9M13 23h9" />
                        </svg></span>
                    <span><strong>Recursos</strong><small>Artículos y Imagenes</small></span>
                </a>
                <a class="portal-shortcut" href="<?= BASE_URL ?>/Nosotros">
                    <span class="portal-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 32 32">
                            <path d="M16 3v4M16 25v4M3 16h4M25 16h4M6.8 6.8l2.8 2.8M22.4 22.4l2.8 2.8M25.2 6.8l-2.8 2.8M9.6 22.4l-2.8 2.8" />
                            <circle cx="16" cy="16" r="8" />
                            <path d="M13 18a3 3 0 1 1 5 2v2h-4v-2" />
                        </svg></span>
                    <span><strong>Sobre Nosotros</strong><small>¡Conócenos!</small></span>
                </a>
               
                <a class="portal-shortcut" href="<?= BASE_URL ?>/Integrantes">
                    <span class="portal-shortcut-icon" aria-hidden="true"><svg viewBox="0 0 32 32">
                            <circle cx="16" cy="9" r="4" />
                            <circle cx="6" cy="13" r="3" />
                            <circle cx="26" cy="13" r="3" />
                            <path d="M8 26v-2a8 8 0 0 1 16 0v2zM1.5 25v-2a5 5 0 0 1 6-4.9M30.5 25v-2a5 5 0 0 0-6-4.9" />
                        </svg></span>
                    <span><strong>Integrantes</strong><small>Conoce a nuestro equipo</small></span>
                </a>
            </div>
        </nav>
    </div>
</header>
<script src="<?= BASE_URL ?>/assets/js/portal-navigation.js?v=<?= filemtime(__DIR__ . '/../assets/js/portal-navigation.js') ?>" defer></script>
<section class="portal-intro">
    <div class="portal-wrap portal-intro-row">
        <div>
            <span class="portal-kicker">Tecnológico de Estudios Superiores de Cuautitlán Izcalli</span>
            <h1>Cuerpo Académico <span>TIC'S aplicadas a la Ingeniería</span></h1>
            <p>Investigación, docencia e innovación tecnológica para un mejor futuro.</p>
        </div>
        <a class="portal-link-button" href="acerca.php">Conoce más <span aria-hidden="true">→</span></a>
    </div>
</section>