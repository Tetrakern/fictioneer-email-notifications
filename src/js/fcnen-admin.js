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

// =============================================================================
// QUEUE
// =============================================================================

const fcnen_queueWrapper = document.querySelector('[data-target="fcnen-email-queue"]');
const fcnen_apiLimit = parseInt(fcnen_queueWrapper?.dataset.apiLimit ?? 10);
const fcnen_apiInterval = parseInt(fcnen_queueWrapper?.dataset.apiInterval ?? 60000);

var fcnen_apiStartTime = 0;
var fcnen_apiRequests = 0;

function fcnen_handleRateLimiting() {
  const elapsedTime = Date.now() - fcnen_apiStartTime;

  if (elapsedTime < fcnen_apiInterval && ++fcnen_apiRequests > fcnen_apiLimit) {
    const current = fcnen_queueWrapper.querySelector('[data-status="working"]');

    if (current) {
      current.querySelector('.fcnen-queue-batch__status').innerHTML =
        `<span>${fcnen_queueWrapper.dataset.pauseMessage}</span> <i class="fa-solid fa-spinner fa-spin" style="--fa-animation-duration: .8s;"></i>`;
    }

    return new Promise(resolve => setTimeout(() => {
      fcnen_apiStartTime = Date.now();
      fcnen_apiRequests = 0;
      resolve();
    }, fcnen_apiInterval + 1000 - elapsedTime));
  }

  if (elapsedTime > fcnen_apiInterval) {
    fcnen_apiStartTime = Date.now();
    fcnen_apiRequests = 0;
  }

  return Promise.resolve();
}

function fcnen_processQueue(index = 0, fresh = 0, count = 0) {
  // Check if the queue has finished processing
  if (index >= count && count > 0) {
    return;
  }

  // Rate limit for max. 10 requests per minute...
  fcnen_handleRateLimiting().then(() => {
    // Prepare payload
    const payload = {
      'action': 'fictioneer_ajax_fcnen_process_email_queue',
      'index': index,
      'fresh': fresh,
      'fcnen_queue_nonce': document.getElementById('fcnen_queue_nonce').value ?? ''
    };

    // Request
    fcn_ajaxPost(payload)
    .then(response => {
      if (response.success) {
        const container = document.querySelector('[data-target="fcnen-email-queue"]');

        container.innerHTML = response.data.html;

        if (!response.data.finished) {
          fcnen_processQueue(response.data.index + 1, 0, response.data.count);
        }
      } else {
        console.error('Error:', response.data.error)
      }
    })
    .catch(error => console.error('Error:', error));
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-click-target="fcnen-work-queue"]').forEach(button => {
    button.addEventListener('click', () => {
      fcnen_apiStartTime = Date.now();
      fcnen_apiRequests = 0;

      fcnen_processQueue(0, 1);
    });
  });
});
