const fcncn_modal = document.getElementById('fcncn-subscription-modal');
const fcncn_url_params = Object.fromEntries(new URLSearchParams(window.location.search).entries());

if (fcncn_modal) {
  document.querySelectorAll('[data-click-action*="fcncn-load-modal-form"]').forEach(button => {
    button.addEventListener('click', () => {
      fcncn_getModalForm();
    }, { once: true });
  });
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

  // Submit button
  document.getElementById('fcnes-modal-submit-button').addEventListener('click', event => {
    fcncn_subscribe(event.currentTarget);
  });
}

/**
 * AJAX: Get the modal form.
 *
 * @since 0.1.0
 */

function fcncn_getModalForm() {
  // Already loaded?
  if (document.getElementById('fcncn-subscription-form')) {
    return;
  }

  // Setup
  const email = fcncn_url_params['fcncn-email'] ?? document.getElementById('fcncn-modal-auth-email')?.value ?? 0;
  const code = fcncn_url_params['fcncn-code'] ?? document.getElementById('fcncn-modal-auth-code')?.value ?? 0;

  // Prepare payload
  const payload = {
    'action': 'fcncn_ajax_get_form_content',
    'email': email,
    'code': code
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

/**
 * AJAX: Subscribe.
 *
 * @since 0.1.0
 */

function fcncn_subscribe(button) {
  // Setup
  const form = button.closest('form');
  const email = document.getElementById('fcncn-modal-submit-email')?.value;
  const scope = document.querySelector('input[name="fcncn-scope"]:checked')?.value ?? 'everything';
  const code = document.getElementById('fcncn-modal-submit-code')?.value ?? 0;

  // Validate
  if (!email) {
    form.reportValidity();
    return;
  }

  // Prepare payload
  const payload = {
    'action': 'fcncn_ajax_subscribe_or_update',
    'email': email,
    'code': code,
    'scope': scope,
    'nonce': button.closest('.fcncn-dialog-modal').querySelector('input[name="nonce"]')?.value ?? ''
  };

  // Request
  fcn_ajaxPost(payload)
  .then(response => {
    console.log(response);
  });
}
