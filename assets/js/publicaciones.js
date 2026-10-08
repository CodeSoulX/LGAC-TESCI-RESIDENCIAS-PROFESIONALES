const publicationCards = [...document.querySelectorAll('.publication-card[data-category]')];
const searchInput = document.querySelector('#publication-search');
const categoryFilters = [...document.querySelectorAll('.publication-category-filter')];
const subcategoryFilters = [...document.querySelectorAll('.publication-subcategory-filter')];
const subcategoryLabels = [...document.querySelectorAll('#publication-subcategory-filters > label')];
const resultCount = document.querySelector('#publication-result-count');
const noResults = document.querySelector('#publication-no-results');
const previousButton = document.querySelector('#publication-previous');
const nextButton = document.querySelector('#publication-next');
const currentPageLabel = document.querySelector('#publication-current-page');
const totalPagesLabel = document.querySelector('#publication-total-pages');
const pageSize = 6;
let currentPage = 0;

function getSelectedCategories() {
  return categoryFilters.filter(filter => filter.checked).map(filter => filter.value);
}

function updateSubcategoryFilters() {
  const selectedCategories = getSelectedCategories();
  subcategoryLabels.forEach(label => {
    const categoryMatches = selectedCategories.length === 0 || selectedCategories.includes(label.dataset.category);
    label.hidden = !categoryMatches;
    if (!categoryMatches) label.querySelector('input').checked = false;
  });
}

function normalizeSearchText(value) {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
}

function getFilteredPublications() {
  const queryWords = normalizeSearchText(searchInput.value).trim().split(/\s+/).filter(Boolean);
  const selectedCategories = getSelectedCategories();
  const selectedSubcategories = subcategoryFilters
    .filter(filter => filter.checked)
    .map(filter => ({
      category: filter.closest('label').dataset.category,
      name: filter.value
    }));

  return publicationCards.filter(card => {
    const matchesCategory = selectedCategories.length === 0 || selectedCategories.includes(card.dataset.category);
    const matchesSubcategory = selectedSubcategories.length === 0 || selectedSubcategories.some(filter =>
          filter.category === card.dataset.category && normalizeSearchText(filter.name) === normalizeSearchText(card.dataset.subcategory));
    const searchableText = normalizeSearchText(card.dataset.search);
    const matchesSearch = queryWords.every(word => searchableText.includes(word));
    return matchesCategory && matchesSubcategory && matchesSearch;
  });
}

function renderPublications() {
  const filteredPublications = getFilteredPublications();
  const totalPages = Math.max(1, Math.ceil(filteredPublications.length / pageSize));
  currentPage = Math.min(currentPage, totalPages - 1);
  const firstIndex = currentPage * pageSize;
  const visibleCards = filteredPublications.slice(firstIndex, firstIndex + pageSize);

  publicationCards.forEach(card => {
    card.hidden = !visibleCards.includes(card);
  });
  noResults.hidden = filteredPublications.length > 0;
  resultCount.textContent = filteredPublications.length ?
    `Mostrando ${firstIndex + 1}–${firstIndex + visibleCards.length} de ${filteredPublications.length} resultados` :
    '0 resultados';
  currentPageLabel.textContent = String(currentPage + 1);
  totalPagesLabel.textContent = String(totalPages);
  previousButton.disabled = currentPage === 0;
  nextButton.disabled = currentPage >= totalPages - 1;
}

function applyPublicationFilters() {
  currentPage = 0;
  updateSubcategoryFilters();
  renderPublications();
}

categoryFilters.forEach(filter => filter.addEventListener('change', applyPublicationFilters));
subcategoryFilters.forEach(filter => filter.addEventListener('change', applyPublicationFilters));
searchInput.addEventListener('input', applyPublicationFilters);
previousButton.addEventListener('click', () => {
  currentPage--;
  renderPublications();
});
nextButton.addEventListener('click', () => {
  currentPage++;
  renderPublications();
});
document.querySelector('#publication-reset').addEventListener('click', () => {
  categoryFilters.forEach(filter => filter.checked = false);
  subcategoryFilters.forEach(filter => filter.checked = false);
  searchInput.value = '';
  applyPublicationFilters();
});
updateSubcategoryFilters();
renderPublications();
