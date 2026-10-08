document.querySelectorAll('[data-publication-form]').forEach(form => {
  const categorySelect = form.querySelector('#categoria_id');
  const newCategoryField = form.querySelector('[data-new-category-field]');
  const newCategoryInput = newCategoryField?.querySelector('input[name="nueva_categoria"]');

  if (!categorySelect || !newCategoryField || !newCategoryInput) return;

  function updateNewCategoryField() {
    const creatingCategory = categorySelect.value === 'nueva';
    newCategoryField.hidden = !creatingCategory;
    newCategoryInput.required = creatingCategory;
    if (!creatingCategory) newCategoryInput.value = '';
  }

  categorySelect.addEventListener('change', updateNewCategoryField);
  updateNewCategoryField();
});
