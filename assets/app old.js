/* JV Construction - progressive UI behavior. Works without any external JS library. */
(() => {
  'use strict';

  // Retain the existing confirmation behavior for destructive actions.
  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!window.confirm(element.dataset.confirm || 'Are you sure?')) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('.sidebar .nav.active').forEach((link) => {
    link.setAttribute('aria-current', 'page');
  });

  const sidebar = document.getElementById('app-sidebar');
  const toggle = document.querySelector('[data-toggle-sidebar]');
  const close = document.querySelector('[data-close-sidebar]');
  const backdrop = document.getElementById('sidebar-backdrop');
  let lastFocus = null;
  const main = document.querySelector('.main');

  function openSidebar() {
    if (!sidebar || !toggle || !backdrop) return;
    lastFocus = document.activeElement;
    sidebar.inert = false;
    sidebar.classList.add('is-open');
    if (main) main.inert = true;
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close navigation');
    backdrop.hidden = false;
    document.body.classList.add('nav-open');
    if (close) close.focus();
  }

  function closeSidebar(restoreFocus = true) {
    if (!sidebar || !toggle || !backdrop) return;
    sidebar.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open navigation');
    backdrop.hidden = true;
    document.body.classList.remove('nav-open');
    if (main) main.inert = false;
    sidebar.inert = window.innerWidth <= 650;
    if (restoreFocus && lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  if (sidebar && toggle && backdrop) {
    sidebar.inert = window.innerWidth <= 650;
    toggle.addEventListener('click', () => {
      if (sidebar.classList.contains('is-open')) closeSidebar();
      else openSidebar();
    });
    if (close) close.addEventListener('click', () => closeSidebar());
    backdrop.addEventListener('click', () => closeSidebar());
    sidebar.querySelectorAll('a[href]').forEach((link) => {
      link.addEventListener('click', () => closeSidebar(false));
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
        closeSidebar();
      }
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 650 && sidebar.classList.contains('is-open')) {
        closeSidebar(false);
      } else if (!sidebar.classList.contains('is-open')) {
        sidebar.inert = window.innerWidth <= 650;
      }
    });
  }

  // Associate existing labels and fields without changing form submission behavior.
  let generatedFieldCount = 0;
  document.querySelectorAll('.form-grid label, .auth-card label').forEach((label) => {
    if (label.htmlFor) return;
    let input = label.nextElementSibling;
    if (!input || !input.matches('input,select,textarea')) {
      input = label.parentElement.querySelector('input:not([type="hidden"]),select,textarea');
    }
    if (!input) return;
    if (!input.id) input.id = `jv-input-${++generatedFieldCount}`;
    label.htmlFor = input.id;
  });

  document.querySelectorAll('.notice').forEach((notice) => {
    notice.setAttribute('role', notice.classList.contains('error') ? 'alert' : 'status');
  });
})();
