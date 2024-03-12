// =============================================================================
// IMPORT CSV
// =============================================================================

document.querySelectorAll('.fcnen-input-wrap._file').forEach(element => {
  element.querySelector('input[type="file"]').addEventListener('change', event => {
    element.querySelector('.fcnen-input-wrap__file-field').textContent = event.currentTarget.files[0].name
  })
});

// =============================================================================
// TEMPLATE SELECT
// =============================================================================

/**
 * Remove query args from URL.
 *
 * @since 0.1.0
 *
 * @param {string} id - The ID of the template container to reveal.
 */

function fcnen_revealTemplate(id) {
  document.querySelectorAll('.fcnen-codemirror').forEach(textarea => {
    textarea.closest('.fcnen-box__row').classList.add('hidden');
  });

  if (!id) {
    return;
  }

  const templateWrapper = document.getElementById(id);

  if (templateWrapper) {
    templateWrapper.classList.remove('hidden');

    if (!templateWrapper.querySelector('.CodeMirror')) {
      wp.codeEditor.initialize(templateWrapper.querySelector('textarea'), cm_settings);
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('fcnen-select-template')?.addEventListener('change', event => {
    fcnen_revealTemplate(event.currentTarget.value);
  });
});
