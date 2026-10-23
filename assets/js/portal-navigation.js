const menuToggle = document.querySelector('.portal-menu-toggle');
const institutionalHeader = document.querySelector('.institutional-header');

if (menuToggle && institutionalHeader) {
  const closeMenu = () => {
    institutionalHeader.classList.remove('is-menu-open');
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.querySelector('.sr-only').textContent = 'Abrir menú de navegación';
  };

  menuToggle.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
    institutionalHeader.classList.toggle('is-menu-open', !isOpen);
    menuToggle.setAttribute('aria-expanded', String(!isOpen));
    menuToggle.querySelector('.sr-only').textContent = isOpen ?
      'Abrir menú de navegación' :
      'Cerrar menú de navegación';
  });

  institutionalHeader.querySelectorAll('.portal-shortcut').forEach(link => {
    link.addEventListener('click', closeMenu);
  });

  institutionalHeader.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeMenu();
      menuToggle.focus();
    }
  });
}
