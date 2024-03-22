// =============================================================================
// MODALS
// =============================================================================

document.querySelectorAll('[data-fcnen-open-modal]').forEach(button => {
  button.addEventListener('click', event => {
    document.getElementById(event.currentTarget.dataset.fcnenOpenModal)?.showModal();
  });
});

// Close dialog modal on click outside
document.querySelectorAll('.fcnen-modal').forEach(element => {
  element.addEventListener('mousedown', event => {
    if (event.target.tagName.toLowerCase() === 'dialog') {
      event.preventDefault();
      event.target.close();
    }
  });
});

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
 * Reveals template editor.
 *
 * @since 0.1.0
 *
 * @param {string} id - The ID of the template container to reveal.
 */

function fcnen_revealTemplate(id) {
  document.querySelectorAll('.fcnen-codemirror').forEach(textarea => {
    textarea.closest('.fcnen-template-wrapper').classList.add('hidden');
  });

  if (!id) {
    fcnen_templatePreview('');
    document.getElementById('fcnen-preview').classList.add('hidden');

    return;
  }

  const templateWrapper = document.getElementById(id);

  document.getElementById('fcnen-preview').classList.remove('hidden');

  if (templateWrapper) {
    templateWrapper.classList.remove('hidden');

    if (!templateWrapper.querySelector('.CodeMirror')) {
      wp.codeEditor.initialize(templateWrapper.querySelector('textarea'), cm_settings);

      const editor = document.querySelector('.fcnen-template-wrapper:not(.hidden) .CodeMirror').CodeMirror;

      fcnen_templatePreview(editor.getValue());
      editor.on( 'change', editor => { fcnen_templatePreview(editor.doc.getValue()); } );
    } else {
      const editor = document.querySelector('.fcnen-template-wrapper:not(.hidden) .CodeMirror').CodeMirror;

      fcnen_templatePreview(editor.getValue());
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('fcnen-select-template')?.addEventListener('change', event => {
    fcnen_revealTemplate(event.currentTarget.value);
  });
});

// =============================================================================
// TEMPLATE PREVIEW
// =============================================================================

/**
 * Updates template preview.
 *
 * @since 0.1.0
 *
 * @param {string} html - The template HTML from the CodeMirror editor.
 * @param {number} [height=] - Height for the iframe. Default null.
 */

function fcnen_templatePreview(html, height = null) {
  // Setup
  const iframe = document.getElementById('fcnen-preview-iframe');
  const doc = iframe.contentWindow.document;

  // Remove replacement tokens for preview
  const regex = /\{\{[#/^]?[^{}]+\}\}/g;

  Object.entries(fcnen_preview_replacements).forEach(([token, replacement]) => {
    html = html.replaceAll(token, replacement);
  });

  html = html.replace(regex, '');

  // Write HTML to iframe
  doc.open();
  doc.write(html);
  doc.close();

  // Set iframe height
  iframe.style.height = 0;

  const h = height ?? Math.max(doc.body.scrollHeight, doc.body.offsetHeight, doc.documentElement.clientHeight, doc.documentElement.scrollHeight, doc.documentElement.offsetHeight);

  iframe.style.height = h + 'px';
}
