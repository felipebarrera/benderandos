/* BenderAnd POS — Global JS · Hito 7 */

/* ── Toast ───────────────────────────────────────────────── */
window.toast = function(msg, type = 'ok', duration = 3500) {
  const icons = {
    ok:   '<svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
    err:  '<svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
    warn: '<svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
    info: '<svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
  };
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `${icons[type] || ''}<span>${msg}</span>`;
  container.appendChild(t);
  setTimeout(() => t.style.opacity = '0', duration);
  setTimeout(() => t.remove(), duration + 400);
};

/* ── Modals ──────────────────────────────────────────────── */
window.openModal = function(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
  document.body.style.overflow = 'hidden';
};
window.closeModal = function(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
  document.body.style.overflow = '';
};
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open')
      .forEach(m => { m.classList.remove('open'); document.body.style.overflow = ''; });
  }
});

/* ── Mobile Drawer ───────────────────────────────────────── */
window.openDrawer = function() {
  document.getElementById('sidebar')?.classList.add('drawer-open');
  document.getElementById('drawer-overlay')?.classList.add('open');
  document.body.style.overflow = 'hidden';
};
window.closeDrawer = function() {
  document.getElementById('sidebar')?.classList.remove('drawer-open');
  document.getElementById('drawer-overlay')?.classList.remove('open');
  document.body.style.overflow = '';
};
document.getElementById('drawer-overlay')?.addEventListener('click', closeDrawer);
document.querySelectorAll('.hamburger')?.forEach(b => b.addEventListener('click', openDrawer));

/* ── Format ──────────────────────────────────────────────── */
window.fmt = function(n) {
  return '$' + Number(n).toLocaleString('es-CL');
};
window.fmtDate = function(d) {
  return new Date(d).toLocaleDateString('es-CL', { day:'2-digit', month:'short', year:'numeric' });
};

/* ── API Helper ──────────────────────────────────────────── */
const token = localStorage.getItem('ba_token') || '';
window.api = async function(method, path, body) {
  const res = await fetch(path, {
    method,
    credentials: 'same-origin', // Clave para que Sanctum SPA Auth funcione
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message || `Error ${res.status}`);
  }
  return res.json().catch(() => ({}));
};

/* ── Auto DOMContentLoaded ───────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Close modals via [data-close-modal]
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
  });
  // Open modals via [data-open-modal]
  document.querySelectorAll('[data-open-modal]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.openModal));
  });
  // Active nav links
  const current = window.location.pathname;
  document.querySelectorAll('.nav-link[href]').forEach(l => {
    if (current.startsWith(l.getAttribute('href')) && l.getAttribute('href') !== '/') {
      l.classList.add('active');
    }
  });
  document.querySelectorAll('.bottom-nav-item[href]').forEach(l => {
    if (current.startsWith(l.getAttribute('href'))) l.classList.add('active');
  });

  // Drawer hamburger
  document.querySelector('.hamburger')?.addEventListener('click', openDrawer);
});
