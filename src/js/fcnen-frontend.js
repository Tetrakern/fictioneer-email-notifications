// =============================================================================
// SETUP
// =============================================================================

const fcnen_modal = document.getElementById('fcnen-subscription-modal');
const fcnen_targetContainer = fcnen_modal?.querySelector('[data-target="fcnen-modal-loader"]');
const fcnen_url_params = Object.fromEntries(new URLSearchParams(window.location.search).entries());

if (fcnen_modal) {
  // Initial loading of modal content
  document.querySelectorAll('[data-click-action*="fcnen-load-modal-form"]').forEach(button => {
    button.addEventListener('click', () => {
      fcnen_getModalForm();
    }, { once: true });
  });

  // Auto-open modal in edit mode
  if (fcnen_url_params['fcnen-email'] && fcnen_url_params['fcnen-code']) {
    document.addEventListener('DOMContentLoaded', () => {
      setTimeout(() => {
        fcnen_modal.showModal();
        fcnen_getModalForm();
      }, 100); // Delay to make sure utilities are loaded
    });
  }
}

// =============================================================================
// CLEAN UP URL
// =============================================================================

/**
 * Remove query args from URL.
 *
 * @since 0.1.0
 */

(() => {
  history.replaceState && history.replaceState(null, '', location.pathname + location.search.replace(/[?&](fcnen-email|fcnen-code|fcnen-action|fcnen|)=[^&]+/g, '').replace(/^[?&]/, '?') + location.hash);
})();

// =============================================================================
// MODAL FORM
// =============================================================================

/**
 * Add EventListeners.
 *
 * @since 0.1.0
 */

function fcnen_addEventListeners() {
  // Toggle auth mode
  document.querySelector('[data-click-action="auth-mode"]')?.addEventListener('click', () => {
    fcnen_modal.querySelector('[data-target="auth-mode"]').hidden = false;
    fcnen_modal.querySelector('[data-target="submit-mode"]').hidden = true;
  });

  // Toggle submit mode
  document.querySelector('[data-click-action="submit-mode"]')?.addEventListener('click', () => {
    fcnen_modal.querySelector('[data-target="auth-mode"]').hidden = true;
    fcnen_modal.querySelector('[data-target="submit-mode"]').hidden = false;
  });

  // Submit button
  document.getElementById('fcnen-modal-submit-button')?.addEventListener('click', event => {
    fcnen_subscribe_or_update(event.currentTarget);
  });

  // Edit button
  document.getElementById('fcnen-modal-auth-button')?.addEventListener('click', () => {
    fcnen_getModalForm('edit');
  });

  // Delete button
  document.querySelector('[data-click-action="fcnen-delete-subscription"]')?.addEventListener('click', () => {
    fcnen_unsubscribe();
  });

  // Everything checkbox
  document.getElementById('fcnen-modal-checkbox-scope-everything')?.addEventListener('change', event => {
    fcnen_modal.querySelector('form').classList.toggle('_everything', event.currentTarget.checked);
  });
}

/**
 * Toggles ajax-in-progress class.
 *
 * @since 0.1.0
 *
 * @param {string} [force=] - Whether to add or remove. Default true.
 */

function fcnen_toggleInProgress(force = true) {
  fcnen_targetContainer.classList.toggle('ajax-in-progress', force);
}

/**
 * Returns form data by extracting field values and joining arrays.
 *
 * @since 0.1.0
 *
 * @param {HTMLFormElement} form - The form element.
 *
 * @return {Object} Prepared form data with field values extracted and arrays joined.
 */

function fcnen_getPreparedFormData(form) {
  // Setup
  let formData = new FormData(form);
  let formFields = {};

  // Process form data
  for (let [key, value] of formData.entries()) {
    let modifiedKey = key.replace(/\[\]/g, '');

    if (formFields.modifiedKey) {
      formFields[modifiedKey].push(value);
    } else {
      formFields[modifiedKey] = [value];
    }
  }

  // Join array values
  for (let prop in formFields) {
    if (Array.isArray(formFields[prop])) {
      formFields[prop] = formFields[prop].join(',');
    }
  }

  // Return
  return formFields;
}

/**
 * AJAX: Get the modal form.
 *
 * @since 0.1.0
 *
 * @param {string} [context=] - The context in which the modal form is loaded. Default 'new'.
 */

function fcnen_getModalForm(context = 'new') {
  // Already loaded?
  if (context == 'new' && document.getElementById('fcnen-subscription-form')) {
    return;
  }

  // Setup
  const email = fcnen_url_params['fcnen-email'] ?? document.getElementById('fcnen-modal-auth-email')?.value ?? 0;
  const code = fcnen_url_params['fcnen-code'] ?? document.getElementById('fcnen-modal-auth-code')?.value ?? 0;

  // Edit?
  if (context == 'edit' && !(email || code)) {
    return;
  }

  // Indicate progress
  if (context == 'edit') {
    fcnen_toggleInProgress();
  }

  // Prepare payload
  const payload = {
    'action': 'fcnen_ajax_get_form_content',
    'auth-email': email,
    'auth-code': code
  };

  // Request
  fcn_ajaxPost(payload)
  .then(response => {
    if (response.success) {
      fcnen_targetContainer.innerHTML = response.data.html;
    }
  })
  .then(() => {
    fcnen_toggleInProgress(false);
    fcnen_addEventListeners();
  });
}

/**
 * AJAX: Create new or update subscription.
 *
 * @since 0.1.0
 *
 * @param {HTMLButtonElement} button - The submit button element.
 */

function fcnen_subscribe_or_update(button) {
  // Setup
  const form = button.closest('form');
  const email = document.getElementById('fcnen-modal-submit-email')?.value;
  const formData = fcnen_getPreparedFormData(form);

  // Validate
  if (!email) {
    form.reportValidity();
    return;
  }

  // Indicate progress
  fcnen_toggleInProgress();

  // Prepare payload
  let payload = {
    'action': 'fcnen_ajax_subscribe_or_update',
    'email': email, // Manually added since disabled fields are not in formData
    'nonce': fcnen_modal.querySelector('input[name="nonce"]')?.value ?? ''
  };

  payload = {...payload, ...formData};

  // Request
  fcn_ajaxPost(payload)
  .then(response => {
    fcnen_targetContainer.innerHTML = `<div class="fcnen-dialog-modal__notice"><p>${response.data.notice}</p></div>`;
    fcnen_toggleInProgress(false);
  });
}

/**
 * AJAX: Unsubscribe.
 *
 * @since 0.1.0
 */

function fcnen_unsubscribe() {
  // Indicate progress
  fcnen_toggleInProgress();

  // Prepare payload
  const payload = {
    'action': 'fcnen_ajax_subscribe',
    'email': document.getElementById('fcnen-modal-submit-email')?.value ?? '',
    'code': document.getElementById('fcnen-modal-submit-code')?.value ?? 0,
    'nonce': fcnen_modal.querySelector('input[name="nonce"]')?.value ?? ''
  };

  // Request
  fcn_ajaxPost(payload)
  .then(response => {
    fcnen_targetContainer.innerHTML = `<div class="fcnen-dialog-modal__notice"><span>${response.data.notice}</span></div>`;
    fcnen_toggleInProgress(false);
  });
}
