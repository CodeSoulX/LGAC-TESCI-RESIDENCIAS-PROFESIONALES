const peopleDialog = document.querySelector('.people-dialog');

if (peopleDialog instanceof HTMLDialogElement) {
  const closeButton = peopleDialog.querySelector('.people-dialog-close');
  const photo = peopleDialog.querySelector('.people-dialog-photo');
  const avatar = peopleDialog.querySelector('.people-dialog-avatar');

  document.querySelectorAll('.tarjeta-docente').forEach((card) => {
    card.addEventListener('click', () => {
      const name = card.dataset.personName ?? '';
      const imageUrl = card.dataset.personPhoto ?? '';

      peopleDialog.querySelector('#people-dialog-name').textContent = name;
      peopleDialog.querySelector('.people-dialog-degree').textContent = card.dataset.personDegree ?? '';
      peopleDialog.querySelector('.people-dialog-specialty').textContent = card.dataset.personSpecialty ?? '';
      peopleDialog.querySelector('.people-dialog-bio p').textContent =
        card.dataset.personBio?.trim() || 'Este integrante aún no ha agregado una semblanza.';

      photo.hidden = !imageUrl;
      photo.alt = imageUrl ? `Foto de ${name}` : '';
      photo.src = imageUrl;
      avatar.hidden = Boolean(imageUrl);
      avatar.textContent = name.trim().charAt(0).toLocaleUpperCase('es');

      peopleDialog.showModal();
    });
  });

  closeButton.addEventListener('click', () => peopleDialog.close());
  peopleDialog.addEventListener('click', (event) => {
    if (event.target === peopleDialog) {
      peopleDialog.close();
    }
  });
}
