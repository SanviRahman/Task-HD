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
    sidebar.inert = window.innerWidth <= 900;
    if (restoreFocus && lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  if (sidebar && toggle && backdrop) {
    sidebar.inert = window.innerWidth <= 900;
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
      if (window.innerWidth > 900 && sidebar.classList.contains('is-open')) {
        closeSidebar(false);
      } else if (!sidebar.classList.contains('is-open')) {
        sidebar.inert = window.innerWidth <= 900;
      }
    });
  }

  // Theme is stored per browser; no database changes required.
  const themeButton = document.querySelector('[data-theme-toggle]');
  const root = document.documentElement;
  function syncThemeButton() {
    if (!themeButton) return;
    const isDark = root.dataset.theme === 'dark';
    themeButton.setAttribute('aria-pressed', String(isDark));
    themeButton.setAttribute('aria-label', isDark ? 'Light mode চালু করুন' : 'Dark mode চালু করুন');
    const symbol = themeButton.querySelector('use');
    if (symbol) symbol.setAttribute('href', isDark ? '#jv-sun' : '#jv-moon');
  }
  syncThemeButton();
  if (themeButton) {
    themeButton.addEventListener('click', () => {
      const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
      root.dataset.theme = next;
      try { localStorage.setItem('jv-ui-theme', next); } catch (e) { /* privacy mode */ }
      syncThemeButton();
    });
  }

  const fullscreenButton = document.querySelector('[data-fullscreen-toggle]');
  if (fullscreenButton) {
    if (!document.documentElement.requestFullscreen || !document.exitFullscreen) {
      fullscreenButton.hidden = true;
    } else {
      fullscreenButton.addEventListener('click', async () => {
        try {
          if (document.fullscreenElement) await document.exitFullscreen();
          else await document.documentElement.requestFullscreen();
        } catch (e) { /* unavailable in this context */ }
      });
      document.addEventListener('fullscreenchange', () => {
        const active = !!document.fullscreenElement;
        fullscreenButton.setAttribute('aria-label', active ? 'Full screen বন্ধ করুন' : 'Full screen চালু করুন');
        fullscreenButton.setAttribute('aria-pressed', String(active));
      });
    }
  }

  // Close account popover after clicking outside or pressing Escape.
  const accountMenu = document.querySelector('.account-menu');
  if (accountMenu) {
    document.addEventListener('click', (event) => {
      if (accountMenu.open && !accountMenu.contains(event.target)) accountMenu.open = false;
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && accountMenu.open) {
        accountMenu.open = false;
        accountMenu.querySelector('summary')?.focus();
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
