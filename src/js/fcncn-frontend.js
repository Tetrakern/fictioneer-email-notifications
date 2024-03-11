const fcncn_modal = document.getElementById('fcncn-subscription-modal');

if (fcncn_modal) {
  document.querySelector('[data-click-action*="fcncn-load-modal-form"]').addEventListener('click', () => {
    fcncn_getModalForm();
  }, { once: true });
}

/**
 * Add EventListeners.
 *
 * @since 0.1.0
 */

function fcncn_addEventListeners() {
  // Toggle auth mode
  document.querySelector('[data-click-action="auth-mode"]').addEventListener('click', () => {
    fcncn_modal.querySelector('[data-target="auth-mode"]').hidden = false;
    fcncn_modal.querySelector('[data-target="submit-mode"]').hidden = true;
  });

  // Toggle submit mode
  document.querySelector('[data-click-action="submit-mode"]').addEventListener('click', () => {
    fcncn_modal.querySelector('[data-target="auth-mode"]').hidden = true;
    fcncn_modal.querySelector('[data-target="submit-mode"]').hidden = false;
  });
}

/**
 * AJAX: Get the modal form.
 *
 * @since 0.1.0
 */

function fcncn_getModalForm() {
  // Setup
  const payload = {
    'action': 'fcncn_ajax_get_form_content'
  };

  // Request
  fcn_ajaxPost(payload)
  .then(response => {
    if (response.success) {
      fcncn_modal.querySelector('[data-target="fcncn-modal-loader"]').innerHTML = response.data.html;
    }
  })
  .then(() => {
    fcncn_addEventListeners();
  });
}
