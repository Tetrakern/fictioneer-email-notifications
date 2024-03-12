// =============================================================================
// IMPORT CSV
// =============================================================================

document.querySelectorAll('.fcnen-input-wrap._file').forEach(element => {
  element.querySelector('input[type="file"]').addEventListener('change', (event) => {
    element.querySelector('.fcnen-input-wrap__file-field').textContent = event.currentTarget.files[0].name
  })
});
