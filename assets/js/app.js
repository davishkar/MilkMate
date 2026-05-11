/* MilkMate - Core JS */
'use strict';

// ── Toast System ──────────────────────────────────────────
const Toast = {
  container: null,
  init() {
    if (!document.getElementById('toast-container')) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      document.body.appendChild(this.container);
    } else {
      this.container = document.getElementById('toast-container');
    }
  },
  show(title, msg = '', type = 'info', duration = 4000) {
    if (!this.container) this.init();
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `
      <span class="toast-icon">${icons[type] || icons.info}</span>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        ${msg ? `<div class="toast-msg">${msg}</div>` : ''}
      </div>
      <button class="toast-close" onclick="Toast.remove(this.closest('.toast'))">✕</button>
    `;
    this.container.appendChild(t);
    if (duration > 0) setTimeout(() => this.remove(t), duration);
    return t;
  },
  remove(el) {
    if (!el || el.classList.contains('removing')) return;
    el.classList.add('removing');
    el.addEventListener('animationend', () => el.remove(), { once: true });
  },
  success(title, msg, dur) { return this.show(title, msg, 'success', dur); },
  error(title, msg, dur) { return this.show(title, msg, 'error', dur); },
  warning(title, msg, dur) { return this.show(title, msg, 'warning', dur); },
  info(title, msg, dur) { return this.show(title, msg, 'info', dur); }
};

// ── AJAX Helper ───────────────────────────────────────────
const Ajax = {
  request(url, data = {}, method = 'POST') {
    const body = data instanceof FormData ? data : (() => {
      const f = new FormData();
      Object.entries(data).forEach(([k, v]) => f.append(k, v));
      return f;
    })();
    return fetch(url, { method, body })
      .then(r => r.json())
      .catch(e => ({ success: false, message: 'Network error: ' + e.message }));
  },
  post(url, data) { return this.request(url, data, 'POST'); },
  get(url) { return fetch(url).then(r => r.json()); }
};

// ── Modal System ──────────────────────────────────────────
const Modal = {
  open(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
  },
  close(id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('hidden'); document.body.style.overflow = ''; }
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.add('hidden'));
    document.body.style.overflow = '';
  }
};

// ── Form Helpers ──────────────────────────────────────────
const Form = {
  serialize(formEl) {
    const fd = new FormData(formEl);
    const obj = {};
    fd.forEach((v, k) => obj[k] = v);
    return obj;
  },
  setLoading(btn, loading) {
    if (loading) {
      btn.dataset.origText = btn.innerHTML;
      btn.innerHTML = `<span class="spinner"></span> Processing...`;
      btn.disabled = true;
    } else {
      btn.innerHTML = btn.dataset.origText || 'Submit';
      btn.disabled = false;
    }
  },
  clearErrors(formEl) {
    formEl.querySelectorAll('.form-error').forEach(e => e.remove());
    formEl.querySelectorAll('.form-control').forEach(e => e.classList.remove('error'));
  },
  showError(formEl, field, msg) {
    const input = formEl.querySelector(`[name="${field}"]`);
    if (!input) return;
    input.classList.add('error');
    const err = document.createElement('div');
    err.className = 'form-error';
    err.textContent = msg;
    input.parentNode.appendChild(err);
  }
};

// ── Sidebar Toggle ────────────────────────────────────────
const Sidebar = {
  el: null,
  overlay: null,
  init() {
    this.el = document.querySelector('.sidebar');
    this.overlay = document.querySelector('.sidebar-overlay');
    if (this.overlay) {
      this.overlay.addEventListener('click', () => this.closeMobile());
    }
    const w = window.innerWidth;
    if (w < 769 && this.el) {
      this.el.classList.remove('mobile-open');
    }
    // Restore collapsed state
    if (localStorage.getItem('sidebar_collapsed') === '1' && w > 768) {
      this.el?.classList.add('collapsed');
    }
  },
  toggle() {
    if (window.innerWidth <= 768) {
      this.el?.classList.toggle('mobile-open');
      this.overlay?.classList.toggle('active');
      // Show overlay
      if (this.el?.classList.contains('mobile-open')) {
        this.overlay.style.display = 'block';
      } else {
        this.overlay.style.display = 'none';
      }
    } else {
      this.el?.classList.toggle('collapsed');
      localStorage.setItem('sidebar_collapsed', this.el?.classList.contains('collapsed') ? '1' : '0');
    }
  },
  closeMobile() {
    this.el?.classList.remove('mobile-open');
    if (this.overlay) this.overlay.style.display = 'none';
  }
};

// ── Number Formatter ──────────────────────────────────────
function formatINR(n) {
  return '₹' + parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatNum(n, dec = 2) {
  return parseFloat(n || 0).toFixed(dec);
}

// ── Confirmation Dialog ───────────────────────────────────
function confirmAction(msg, callback) {
  if (confirm(msg)) callback();
}

// ── Delete Record ─────────────────────────────────────────
function deleteRecord(url, id, row, entityName = 'record') {
  if (!confirm(`Are you sure you want to delete this ${entityName}? This cannot be undone.`)) return;
  Ajax.post(url, { id })
    .then(res => {
      if (res.success) {
        if (row) row.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => { if (row) row.remove(); }, 300);
        Toast.success('Deleted!', `${entityName} removed successfully.`);
      } else {
        Toast.error('Failed', res.message || 'Could not delete.');
      }
    });
}

// ── Search/Filter Table ───────────────────────────────────
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ── Date utilities ────────────────────────────────────────
function today() {
  return new Date().toISOString().split('T')[0];
}

// ── Init on DOM ready ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  Toast.init();
  Sidebar.init();

  // Sidebar toggle button
  const toggleBtn = document.getElementById('sidebar-toggle');
  if (toggleBtn) toggleBtn.addEventListener('click', () => Sidebar.toggle());

  // Close modals on overlay click
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) Modal.closeAll();
    });
  });

  // Auto-hide flash messages from PHP
  document.querySelectorAll('[data-toast]').forEach(el => {
    Toast[el.dataset.toastType || 'info'](el.dataset.toastTitle || 'Notice', el.dataset.toast);
    el.remove();
  });

  // Mark active nav item
  const path = window.location.pathname;
  document.querySelectorAll('.nav-item[data-path]').forEach(item => {
    if (path.includes(item.dataset.path)) item.classList.add('active');
  });

  // Page loader
  const loader = document.getElementById('page-loader');
  if (loader) {
    loader.classList.add('hidden');
    setTimeout(() => loader.remove(), 400);
  }
});
