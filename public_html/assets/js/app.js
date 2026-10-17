/**
 * Personal Finance Manager Client-Side Core
 */

window.PFM = {
  // Theme Management
  initTheme() {
    const savedTheme = localStorage.getItem('pfm_theme') || 'system';
    this.applyTheme(savedTheme);

    // Listen for OS theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (localStorage.getItem('pfm_theme') === 'system') {
        this.applyTheme('system');
      }
    });
  },

  applyTheme(theme) {
    let effectiveTheme = theme;
    if (theme === 'system') {
      effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    document.documentElement.setAttribute('data-theme', effectiveTheme);
    localStorage.setItem('pfm_theme', theme);

    // Notify backend if user is authenticated
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && window.PFM_USER_LOGGED_IN) {
      fetch('/settings/theme', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': meta.content,
        },
        body: JSON.stringify({ theme }),
      }).catch(() => {});
    }
  },

  toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    this.applyTheme(next);
  },

  // Toast Notification System
  toast(message, type = 'info', options = {}) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let undoHtml = '';
    if (options.undoCallback) {
      undoHtml = `<button class="btn btn-ghost" style="padding: 4px 8px; font-size: 0.8125rem; font-weight: 700; color: var(--color-primary);" id="toast-undo-btn">Undo</button>`;
    }

    toast.innerHTML = `
      <div style="display: flex; align-items: center; gap: 8px;">
        <span>${message}</span>
      </div>
      <div style="display: flex; align-items: center; gap: 8px;">
        ${undoHtml}
        <button style="background: none; border: none; cursor: pointer; color: var(--ink-muted);" onclick="this.closest('.toast').remove()">✕</button>
      </div>
    `;

    container.appendChild(toast);

    if (options.undoCallback) {
      const undoBtn = toast.querySelector('#toast-undo-btn');
      undoBtn.addEventListener('click', () => {
        options.undoCallback();
        toast.remove();
      });
    }

    const duration = options.duration || 5000;
    setTimeout(() => {
      if (toast.parentNode) {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 200ms ease';
        setTimeout(() => toast.remove(), 200);
      }
    }, duration);
  },

  // Global API Fetch Wrapper with CSRF
  async fetch(url, options = {}) {
    options.headers = options.headers || {};
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta && !options.headers['X-CSRF-TOKEN']) {
      options.headers['X-CSRF-TOKEN'] = csrfMeta.content;
    }
    if (!options.headers['Accept']) {
      options.headers['Accept'] = 'application/json';
    }

    const response = await window.fetch(url, options);
    return response;
  }
};

// Initialize Theme on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
  window.PFM.initTheme();

  // Global keyboard shortcuts (e.g., 'N' for quick add)
  document.addEventListener('keydown', (e) => {
    if (e.key === 'n' || e.key === 'N') {
      const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
      if (!['input', 'textarea', 'select'].includes(activeTag)) {
        const quickAddBtn = document.getElementById('btn-quick-add');
        if (quickAddBtn) {
          e.preventDefault();
          quickAddBtn.click();
        }
      }
    }
  });
});
