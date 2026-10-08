const shareButton = document.querySelector('#share-publication');
const shareStatus = document.querySelector('#share-status');

if (shareButton) {
  shareButton.addEventListener('click', async () => {
    const publicationTitle = document.querySelector('.news-title')?.textContent.trim() || document.title;
    const shareData = {
      title: publicationTitle,
      text: publicationTitle,
      url: window.location.href
    };

    try {
      if (navigator.share) {
        await navigator.share(shareData);
      } else if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(shareData.url);
        shareStatus.textContent = 'Enlace copiado';
      } else {
        window.prompt('Copia el enlace de esta publicación:', shareData.url);
      }
    } catch (error) {
      if (error.name !== 'AbortError') {
        shareStatus.textContent = 'No se pudo compartir el enlace.';
      }
    }
  });
}
