const fcncn_modal = document.getElementById('fcncn-subscription-modal');
const fcncn_targetContainer = fcncn_modal?.querySelector('[data-target="fcncn-modal-loader"]');
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
  document.querySelector('[data-click-action="auth-mode"]')?.addEventListener('click', () => {
    fcncn_modal.querySelector('[data-target="auth-mode"]').hidden = false;
    fcncn_modal.querySelector('[data-target="submit-mode"]').hidden = true;
  });

  // Toggle submit mode
  document.querySelector('[data-click-action="submit-mode"]')?.addEventListener('click', () => {
    fcncn_modal.querySelector('[data-target="auth-mode"]').hidden = true;
    fcncn_modal.querySelector('[data-target="submit-mode"]').hidden = false;
  });

  // Submit button
  document.getElementById('fcnes-modal-submit-button')?.addEventListener('click', event => {
    fcncn_subscribe_or_update(event.currentTarget);
  });

  // Edit button
  document.getElementById('fcnes-modal-auth-button')?.addEventListener('click', () => {
    fcncn_getModalForm('edit');
  });

  // Delete button
  document.querySelector('[data-click-action="fcncn-delete-subscription"]')?.addEventListener('click', () => {
    fcncn_unsubscribe();
  });
}

/**
 * Toggles ajax-in-progress class.
 *
 * @since 0.1.0
 *
 * @param {string} [force=] - Whether to add or remove. Default true.
 */

function fcncn_toggleInProgress(force = true) {
  fcncn_targetContainer.classList.toggle('ajax-in-progress', force);
}

/**
 * AJAX: Get the modal form.
 *
 * @since 0.1.0
 *
 * @param {string} [context=] - The context in which the modal form is loaded. Default 'new'.
 */

function fcncn_getModalForm(context = 'new') {
  // Already loaded?
  if (context == 'new' && document.getElementById('fcncn-subscription-form')) {
    return;
  }

  // Setup
  const email = fcncn_url_params['fcncn-email'] ?? document.getElementById('fcncn-modal-auth-email')?.value ?? 0;
  const code = fcncn_url_params['fcncn-code'] ?? document.getElementById('fcncn-modal-auth-code')?.value ?? 0;

  // Edit?
  if (context == 'edit' && !(email || code)) {
    return;
  }

  // Indicate progress
  if (context == 'edit') {
    fcncn_toggleInProgress();
  }

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
      fcncn_targetContainer.innerHTML = response.data.html;
    }
  })
  .then(() => {
    fcncn_toggleInProgress(false);
    fcncn_addEventListeners();
  });
}

/**
 * AJAX: Create new or update subscription.
 *
 * @since 0.1.0
 *
 * @param {HTMLButtonElement} button - The submit button element.
 */

function fcncn_subscribe_or_update(button) {
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

  // Indicate progress
  fcncn_toggleInProgress();

  // Prepare payload
  const payload = {
    'action': 'fcncn_ajax_subscribe_or_update',
    'email': email,
    'code': code,
    'scope': scope,
    'nonce': fcncn_modal.querySelector('input[name="nonce"]')?.value ?? ''
  };

  // Request
  fcn_ajaxPost(payload)
  .then(response => {
    fcncn_targetContainer.innerHTML = `<div class="fcncn-dialog-modal__notice"><p>${response.data.notice}</p></div>`;
  })
  .then(() => {
    fcncn_toggleInProgress(false);
  });
}

/**
 * AJAX: Unsubscribe.
 *
 * @since 0.1.0
 */

function fcncn_unsubscribe() {
  // Setup
  const email = document.getElementById('fcncn-modal-submit-email')?.value;
  const code = document.getElementById('fcncn-modal-submit-code')?.value ?? 0;

  // Indicate progress
  fcncn_toggleInProgress();

  // Prepare payload
  const payload = {
    'action': 'fcncn_ajax_subscribe',
    'email': email,
    'code': code,
    'nonce': fcncn_modal.querySelector('input[name="nonce"]')?.value ?? ''
  };

  // Request
  fcn_ajaxPost(payload)
  .then(response => {
    fcncn_targetContainer.innerHTML = `<div class="fcncn-dialog-modal__notice"><span>${response.data.notice}</span></div>`;
  })
  .then(() => {
    fcncn_toggleInProgress(false);
  });
}
