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

  // Search
  fcnen_initializeSearch();
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

    if (modifiedKey in formFields) {
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
    'action': 'fcnen_ajax_unsubscribe',
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

// =============================================================================
// SEARCH & SELECT
// =============================================================================

var fcncn_searchTimer;

function fcnen_initializeSearch() {
  document.querySelector('[data-input-target="fcnen-search"]')?.addEventListener('input', () => {
    // Clear previous timer (if any)
    clearTimeout(fcncn_searchTimer);

    // Trigger search after delay
    fcncn_searchTimer = setTimeout(() => {
      fcnen_search();
    }, 800);
  });

  document.getElementById('fcnen-modal-search-select')?.addEventListener('change', () => {
    fcnen_search();
  });

  fcnen_modal.querySelector('[data-target="fcnen-sources"]').addEventListener('click', event => {
    const item = event.target.closest('[data-click-action="fcnen-add"]');

    if (item && !item.classList.contains('_disabled')) {
      fcnen_addSelection(item);
    }
  });
}

function fcnen_search() {
  // Get elements and values
  const search = document.querySelector('[data-input-target="fcnen-search"]');
  const wrapper = search.closest('.fcnen-dialog-modal__advanced');
  const sourceList = wrapper.querySelector('[data-target="fcnen-sources"]');
  const type = document.getElementById('fcnen-modal-search-select');

  // Clear source list and add spinner
  sourceList.innerHTML = '';
  sourceList.appendChild(document.querySelector('[data-target="fcnen-spinner-template"]').content.cloneNode(true));

  // Crop search input
  if (search.value > 200) {
    search.value = search.value.slice(0, 200);
  }

  // Search empty?
  if (search.value == '') {
    sourceList.innerHTML = '';
    sourceList.appendChild(document.querySelector('[data-target="fcnen-no-matches"]').content.cloneNode(true));
    return;
  }

  // Prepare payload
  const payload = {
    'action': 'fcnen_ajax_search_content',
    'search': search.value,
    'type': type?.value ?? 'fcn_story',
    'nonce': fcnen_modal.querySelector('input[name="nonce"]')?.value ?? ''
  };

  // Request
  fcnen_searchContent(payload, sourceList);
}

function fcnen_searchContent(payload, sourceList) {
  fcn_ajaxPost(payload)
  .then(response => {
    if (response.success) {
      sourceList.innerHTML = response.data.html;
    }
  });
}

/**
 * Add selection item.
 *
 * @since 0.1.0
 * @param {HTMLElement} source - The source item.
 * @param {HTMLElement} destination - The destination container.
 */

function fcnen_addSelection(source) {
  // Setup
  const destination = fcnen_modal.querySelector('[data-target="fcnen-selection"]');

  // Check if already added
  if (destination.querySelector(`[data-compare="${source.dataset.compare}"]`)) {
    return;
  }

  // Clone template
  const clone = fcnen_modal.querySelector('[data-target="fcnen-selection-item"]').content.cloneNode(true);

  // Fill data
  clone.querySelector('li').setAttribute('data-id', source.dataset.id);
  clone.querySelector('li').setAttribute('data-compare', source.dataset.compare);
  clone.querySelector('li').setAttribute('data-type', source.dataset.type);
  clone.querySelector('span').innerHTML = source.querySelector('span').innerHTML;
  clone.querySelector('input[type="hidden"]').value = source.dataset.id;
  clone.querySelector('input[type="hidden"]').name = source.dataset.name;

  // Append to destination
  destination.appendChild(clone);

  // Disable source item
  source.classList.add('_disabled');
}
