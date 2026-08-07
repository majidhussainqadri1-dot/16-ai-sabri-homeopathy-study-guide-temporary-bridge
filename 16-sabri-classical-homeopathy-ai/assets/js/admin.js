(() => {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.scha-inline-form button').forEach((button) => {
      if (button.textContent.toLowerCase().includes('suspend')) {
        button.addEventListener('click', (event) => {
          if (!window.confirm('Suspend this source and remove it from future retrieval?')) {
            event.preventDefault();
          }
        });
      }
    });
  });
})();
