const fcncn_modal = document.getElementById('fcncn-subscription-modal');

if (fcncn_modal) {
  // Open modal
  document.querySelectorAll('[data-click-action="fcncn-open-modal"]').forEach(element => {
    element.addEventListener('click', () => {
      fcncn_modal.showModal();
    });
  });

  // Close modal
  document.querySelectorAll('[data-click-action="fcncn-close-modal"]').forEach(element => {
    element.addEventListener('click', () => {
      fcncn_modal.close();
    });
  });
}
