/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  BENDERAND DEBUG LOGGER v1.0                                 ║
 * ║  Inyectar al final de <body> en cada HTML del proyecto       ║
 * ║  Compatible con: pos_v4, admin_dashboard_v2, compras,        ║
 * ║  superadmin, login, ticket, compras_proveedores              ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * INSTRUCCIONES DE USO:
 *
 * 1. Agregar antes de </body> en cada HTML:
 *    <script src="benderand-debug.js"></script>
 *
 * 2. O copiar el bloque completo directamente como <script> inline.
 *
 * 3. Activar panel: Ctrl+Shift+D (desktop) o botón flotante 🐛 (mobile)
 *
 * 4. Para enviar a Antigravity: copiar el JSON del panel → pegarlo
 *    con el prompt: "Revisa este log y repara todos los errores:"
 *
 * CAPTURA AUTOMÁTICA:
 *  - window.onerror (errores JS no capturados)
 *  - unhandledrejection (promesas sin catch)
 *  - fetch() interceptado (errores de API / red)
 *  - console.error / console.warn (todo lo que ya logueas)
 *  - Errores de renderizado DOM (MutationObserver)
 *  - Errores de eventos onclick/oninput
 *  - Contexto completo: archivo · línea · columna · stack · hora · página activa · datos
 */

;(function() {
  'use strict';

  // ─── CONFIGURACIÓN ────────────────────────────────────────────
  const CFG = {
    maxLogs:    200,       // máximo de entradas en memoria
    storageKey: 'ba_debug_log',
    panelId:    '__ba_debug_panel',
    btnId:      '__ba_debug_btn',
    version:    '1.0.0',
    project:    'BenderAnd ERP',
  };

  // ─── ESTADO ───────────────────────────────────────────────────
  const STATE = {
    logs:      [],
    panelOpen: false,
    filter:    'all',   // all | error | warn | info | network | event
    paused:    false,
  };

  // ─── UTILS ────────────────────────────────────────────────────
  function ts() {
    return new Date().toISOString().replace('T',' ').substring(0,23);
  }

  function currentPage() {
    // detecta la página activa en el SPA del admin dashboard
    try {
      const active = document.querySelector('.page.active, .view.active, [class*="pg-"].active');
      if (active) return active.id || active.className.split(' ')[0];
      return document.title || window.location.pathname;
    } catch(e) { return window.location.pathname; }
  }

  function shortStack(stack) {
    if (!stack) return null;
    return stack.split('\n').slice(0, 6).map(l => l.trim()).join(' | ');
  }

  function domContext() {
    // captura el estado relevante del DOM para debuggear
    try {
      return {
        activePage:  currentPage(),
        url:         window.location.href,
        viewport:    `${window.innerWidth}x${window.innerHeight}`,
        userAgent:   navigator.userAgent.substring(0, 80),
      };
    } catch(e) { return {}; }
  }

  // ─── CORE: AGREGAR LOG ────────────────────────────────────────
  function addLog(level, category, message, detail = {}) {
    if (STATE.paused) return;

    const entry = {
      id:        Date.now() + Math.random().toString(36).slice(2,6),
      ts:        ts(),
      level,                  // error | warn | info | network | event | debug
      category,               // JS | API | UI | Promise | Console | DOM
      message:   String(message).substring(0, 500),
      detail:    sanitize(detail),
      ctx:       domContext(),
    };

    STATE.logs.unshift(entry);           // más reciente primero
    if (STATE.logs.length > CFG.maxLogs) STATE.logs.pop();

    persistLog(entry);
    updatePanel();
    updateBadge();

    // errors → parpadeo en el botón flotante
    if (level === 'error') flashBtn();
  }

  function sanitize(obj) {
    try {
      const str = JSON.stringify(obj, null, 0);
      return JSON.parse(str.substring(0, 2000));
    } catch(e) {
      return { raw: String(obj).substring(0, 500) };
    }
  }

  // ─── PERSISTENCIA (sessionStorage) ───────────────────────────
  function persistLog(entry) {
    try {
      const stored = JSON.parse(sessionStorage.getItem(CFG.storageKey) || '[]');
      stored.unshift(entry);
      if (stored.length > CFG.maxLogs) stored.pop();
      sessionStorage.setItem(CFG.storageKey, JSON.stringify(stored));
    } catch(e) { /* storage full — ignorar */ }
  }

  function loadPersistedLogs() {
    try {
      const stored = JSON.parse(sessionStorage.getItem(CFG.storageKey) || '[]');
      STATE.logs = stored;
    } catch(e) {}
  }

  // ─── INTERCEPTORES ────────────────────────────────────────────

  // 1. window.onerror — errores JS sin capturar
  window.addEventListener('error', function(e) {
    addLog('error', 'JS', e.message || 'Script error', {
      file:   e.filename,
      line:   e.lineno,
      col:    e.colno,
      stack:  shortStack(e.error?.stack),
      type:   'window.onerror',
    });
  }, true);

  // 2. Promesas sin catch
  window.addEventListener('unhandledrejection', function(e) {
    const msg = e.reason?.message || String(e.reason) || 'Unhandled Promise rejection';
    addLog('error', 'Promise', msg, {
      stack: shortStack(e.reason?.stack),
      type:  'unhandledrejection',
    });
  });

  // 3. fetch() — interceptar todas las llamadas de red
  const _origFetch = window.fetch;
  window.fetch = async function(...args) {
    const url     = typeof args[0] === 'string' ? args[0] : args[0]?.url || '?';
    const method  = args[1]?.method || 'GET';
    const t0      = performance.now();

    addLog('info', 'Network', `→ ${method} ${url}`, {
      type: 'fetch_start', method, url,
      body: args[1]?.body ? String(args[1].body).substring(0, 300) : null,
    });

    try {
      const res  = await _origFetch.apply(this, args);
      const ms   = Math.round(performance.now() - t0);
      const level = res.ok ? 'info' : 'error';

      addLog(level, 'Network', `${res.ok ? '✓' : '✗'} ${method} ${url} [${res.status}] ${ms}ms`, {
        type:   'fetch_response',
        status: res.status,
        ok:     res.ok,
        ms,
        url,
      });

      return res;
    } catch(err) {
      const ms = Math.round(performance.now() - t0);
      addLog('error', 'Network', `✗ FAILED ${method} ${url} — ${err.message}`, {
        type:  'fetch_error',
        error: err.message,
        ms,
        url,
      });
      throw err;
    }
  };

  // 4. console.error / console.warn — capturar los existentes
  const _origError = console.error.bind(console);
  const _origWarn  = console.warn.bind(console);
  const _origLog   = console.log.bind(console);

  console.error = function(...args) {
    _origError(...args);
    addLog('error', 'Console', args.map(a => String(a)).join(' ').substring(0, 400), {
      type: 'console.error',
    });
  };

  console.warn = function(...args) {
    _origWarn(...args);
    addLog('warn', 'Console', args.map(a => String(a)).join(' ').substring(0, 400), {
      type: 'console.warn',
    });
  };

  // ba.log() — API pública para log manual desde el código del proyecto
  window.ba = window.ba || {};
  window.ba.log = function(msg, data = {}) {
    _origLog('[BA]', msg, data);
    addLog('debug', 'App', msg, data);
  };
  window.ba.error = function(msg, data = {}) {
    addLog('error', 'App', msg, data);
  };
  window.ba.event = function(action, data = {}) {
    addLog('event', 'UI', action, data);
  };

  // 5. Errores en event listeners onclick / oninput
  const _origAddEvent = EventTarget.prototype.addEventListener;
  EventTarget.prototype.addEventListener = function(type, handler, opts) {
    if (typeof handler !== 'function') return _origAddEvent.call(this, type, handler, opts);
    const wrapped = function(e) {
      try {
        return handler.call(this, e);
      } catch(err) {
        const el  = e.target;
        const tag = el?.tagName || '?';
        const id  = el?.id ? `#${el.id}` : '';
        const cls = el?.className ? `.${String(el.className).split(' ')[0]}` : '';
        addLog('error', 'UI', `Error en ${type} → ${tag}${id}${cls}: ${err.message}`, {
          type:    'event_error',
          event:   type,
          element: `${tag}${id}${cls}`,
          stack:   shortStack(err.stack),
        });
        throw err;
      }
    };
    return _origAddEvent.call(this, type, wrapped, opts);
  };

  // 6. Errores de renderizado (mutations que generan errores)
  // Se capturan via window.onerror, no necesita MutationObserver extra

  // ─── PANEL UI ─────────────────────────────────────────────────
  const LEVEL_COLORS = {
    error:   { bg: '#3d1a1a', border: '#ff3f5b', txt: '#ff8099', icon: '✗' },
    warn:    { bg: '#2d2410', border: '#f5c518', txt: '#f5c518', icon: '⚠' },
    info:    { bg: '#0d1a2d', border: '#4488ff', txt: '#6fa8ff', icon: '→' },
    network: { bg: '#0d1a2d', border: '#4488ff', txt: '#6fa8ff', icon: '⇄' },
    event:   { bg: '#1a1a2d', border: '#aa66ff', txt: '#cc99ff', icon: '◎' },
    debug:   { bg: '#101010', border: '#2a2a3a', txt: '#7878a0', icon: '·' },
  };

  function buildPanel() {
    if (document.getElementById(CFG.panelId)) return;

    const panel = document.createElement('div');
    panel.id = CFG.panelId;
    panel.innerHTML = `
      <div id="__ba_hdr" style="
        display:flex;align-items:center;gap:8px;padding:10px 14px;
        background:#0e0e14;border-bottom:1px solid #2a2a3a;
        cursor:move;user-select:none;flex-shrink:0;
      ">
        <span style="font-size:14px;">🐛</span>
        <span style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:#e8e8f0;font-weight:600;">
          BENDERAND DEBUG
        </span>
        <span id="__ba_badge" style="
          background:#ff3f5b;color:#fff;font-size:9px;padding:1px 5px;
          border-radius:10px;font-family:monospace;font-weight:700;display:none;
        ">0</span>
        <div style="margin-left:auto;display:flex;gap:6px;align-items:center;">
          <span id="__ba_pause" title="Pausar captura" style="
            cursor:pointer;font-size:11px;color:#7878a0;padding:2px 6px;
            border-radius:4px;border:1px solid #2a2a3a;font-family:monospace;
          ">⏸</span>
          <span id="__ba_copy" title="Copiar JSON para Antigravity" style="
            cursor:pointer;font-size:11px;color:#7878a0;padding:2px 6px;
            border-radius:4px;border:1px solid #2a2a3a;font-family:monospace;
          ">📋</span>
          <span id="__ba_clear" title="Limpiar logs" style="
            cursor:pointer;font-size:11px;color:#7878a0;padding:2px 6px;
            border-radius:4px;border:1px solid #2a2a3a;font-family:monospace;
          ">🗑</span>
          <span id="__ba_close" title="Cerrar" style="
            cursor:pointer;font-size:14px;color:#7878a0;padding:2px 6px;
          ">✕</span>
        </div>
      </div>

      <!-- Filtros -->
      <div style="
        display:flex;gap:4px;padding:8px 10px;
        background:#0a0a0f;border-bottom:1px solid #1e1e28;
        flex-shrink:0;flex-wrap:wrap;
      ">
        ${['all','error','warn','info','network','event','debug'].map(f => `
          <span class="__ba_filter" data-f="${f}" style="
            cursor:pointer;font-size:9px;padding:3px 8px;border-radius:20px;
            border:1px solid #2a2a3a;background:#111115;color:#7878a0;
            font-family:'IBM Plex Mono',monospace;letter-spacing:.04em;
            text-transform:uppercase;transition:all .12s;
          ">${f}</span>
        `).join('')}
        <span style="margin-left:auto;font-family:monospace;font-size:9px;color:#3a3a55;align-self:center;" id="__ba_count"></span>
      </div>

      <!-- Log list -->
      <div id="__ba_list" style="
        flex:1;overflow-y:auto;padding:4px 0;
        font-family:'IBM Plex Mono',monospace;font-size:11px;
      "></div>

      <!-- Footer: prompt para Antigravity -->
      <div style="
        padding:10px 14px;background:#0a0a0f;border-top:1px solid #1e1e28;
        flex-shrink:0;
      ">
        <div style="font-size:9px;color:#3a3a55;margin-bottom:6px;font-family:monospace;">
          PROMPT PARA ANTIGRAVITY
        </div>
        <div style="
          background:#0e0e14;border:1px solid #2a2a3a;border-radius:6px;
          padding:8px 10px;font-size:10px;color:#6868a0;font-family:monospace;
          line-height:1.5;cursor:pointer;
        " id="__ba_prompt" title="Click para copiar prompt completo">
          Revisa este log de errores de BenderAnd ERP y repara todos los bugs encontrados.<br>
          El log está en tu portapapeles. Identifica cada error, su causa raíz y aplica el fix.
        </div>
      </div>
    `;

    panel.style.cssText = `
      position:fixed;
      bottom:70px;right:16px;
      width:580px;max-width:calc(100vw - 32px);
      height:440px;max-height:calc(100vh - 100px);
      background:#09090f;
      border:1px solid #2a2a3a;
      border-radius:12px;
      box-shadow:0 24px 80px rgba(0,0,0,0.8),0 0 0 1px rgba(255,255,255,0.03);
      z-index:2147483640;
      display:none;
      flex-direction:column;
      overflow:hidden;
      font-family:'IBM Plex Mono',monospace;
      resize:both;
    `;

    document.body.appendChild(panel);

    // Evento: cerrar
    document.getElementById('__ba_close').onclick = () => togglePanel(false);

    // Evento: limpiar
    document.getElementById('__ba_clear').onclick = () => {
      STATE.logs = [];
      sessionStorage.removeItem(CFG.storageKey);
      updatePanel();
      updateBadge();
    };

    // Evento: copiar JSON
    document.getElementById('__ba_copy').onclick = copyLogs;

    // Evento: prompt completo
    document.getElementById('__ba_prompt').onclick = copyFullPrompt;

    // Evento: pausar
    document.getElementById('__ba_pause').onclick = function() {
      STATE.paused = !STATE.paused;
      this.textContent = STATE.paused ? '▶' : '⏸';
      this.style.color = STATE.paused ? '#f5c518' : '#7878a0';
    };

    // Filtros
    panel.querySelectorAll('.__ba_filter').forEach(el => {
      el.onclick = function() {
        STATE.filter = this.dataset.f;
        panel.querySelectorAll('.__ba_filter').forEach(x => {
          x.style.background = '#111115';
          x.style.color      = '#7878a0';
          x.style.borderColor = '#2a2a3a';
        });
        this.style.background  = 'rgba(68,136,255,0.12)';
        this.style.color       = '#6fa8ff';
        this.style.borderColor = 'rgba(68,136,255,0.3)';
        updatePanel();
      };
    });

    // Drag to move
    makeDraggable(panel, document.getElementById('__ba_hdr'));
  }

  function buildBtn() {
    if (document.getElementById(CFG.btnId)) return;
    const btn = document.createElement('div');
    btn.id = CFG.btnId;
    btn.innerHTML = '🐛';
    btn.title = 'BenderAnd Debug Log (Ctrl+Shift+D)';
    btn.style.cssText = `
      position:fixed;bottom:16px;right:16px;
      width:44px;height:44px;
      background:#09090f;
      border:1px solid #2a2a3a;
      border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      font-size:20px;cursor:pointer;
      box-shadow:0 4px 20px rgba(0,0,0,0.6);
      z-index:2147483641;
      transition:all .15s;
      user-select:none;
    `;
    btn.onclick = () => togglePanel();
    btn.onmouseenter = () => btn.style.borderColor = '#4488ff';
    btn.onmouseleave = () => !STATE.panelOpen && (btn.style.borderColor = '#2a2a3a');
    document.body.appendChild(btn);

    // Badge de errores sobre el botón
    const badge = document.createElement('div');
    badge.id = '__ba_btn_badge';
    badge.style.cssText = `
      position:fixed;bottom:44px;right:12px;
      background:#ff3f5b;color:#fff;
      font-size:9px;font-weight:700;
      padding:1px 5px;border-radius:10px;
      font-family:monospace;
      z-index:2147483642;
      display:none;
      pointer-events:none;
    `;
    document.body.appendChild(badge);
  }

  function togglePanel(force) {
    buildPanel();
    const panel = document.getElementById(CFG.panelId);
    const btn   = document.getElementById(CFG.btnId);
    STATE.panelOpen = force !== undefined ? force : !STATE.panelOpen;
    panel.style.display = STATE.panelOpen ? 'flex' : 'none';
    btn.style.borderColor = STATE.panelOpen ? '#4488ff' : '#2a2a3a';
    if (STATE.panelOpen) updatePanel();
  }

  function updatePanel() {
    const list = document.getElementById('__ba_list');
    const cnt  = document.getElementById('__ba_count');
    if (!list) return;

    const filtered = STATE.logs.filter(l =>
      STATE.filter === 'all' ||
      l.level === STATE.filter ||
      l.category.toLowerCase() === STATE.filter
    );

    if (cnt) cnt.textContent = `${filtered.length} / ${STATE.logs.length}`;

    if (!filtered.length) {
      list.innerHTML = `<div style="text-align:center;padding:30px;color:#3a3a55;font-size:11px;">
        Sin logs capturados aún.<br>
        <span style="font-size:9px;opacity:.5;">Los errores aparecerán aquí automáticamente.</span>
      </div>`;
      return;
    }

    list.innerHTML = filtered.map(l => {
      const c = LEVEL_COLORS[l.level] || LEVEL_COLORS.debug;
      const detailStr = Object.keys(l.detail || {}).length
        ? JSON.stringify(l.detail, null, 0).substring(0, 200)
        : '';

      return `
        <div style="
          padding:8px 12px;border-bottom:1px solid #111118;
          background:${c.bg};
          border-left:2px solid ${c.border};
          cursor:pointer;
        " onclick="this.querySelector('.__ba_detail').style.display === 'none'
           ? this.querySelector('.__ba_detail').style.display='block'
           : this.querySelector('.__ba_detail').style.display='none'">

          <div style="display:flex;align-items:baseline;gap:6px;">
            <span style="color:${c.txt};font-weight:700;flex-shrink:0;">${c.icon}</span>
            <span style="color:#3a3a55;font-size:9px;flex-shrink:0;">${l.ts.substring(11)}</span>
            <span style="
              background:rgba(255,255,255,0.05);color:${c.txt};
              font-size:8px;padding:1px 5px;border-radius:3px;flex-shrink:0;
            ">${l.category}</span>
            <span style="color:${c.txt};flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${escHtml(l.message)}
            </span>
          </div>

          ${l.ctx?.activePage ? `
          <div style="color:#3a3a55;font-size:9px;margin-top:3px;padding-left:16px;">
            📍 ${escHtml(l.ctx.activePage)}
          </div>` : ''}

          <div class="__ba_detail" style="display:none;margin-top:6px;padding:6px 8px;
            background:rgba(0,0,0,0.3);border-radius:4px;color:#6868a0;font-size:9px;
            word-break:break-all;line-height:1.6;
          ">
            ${escHtml(detailStr || '—')}
            ${l.ctx ? `<br><span style="color:#2a2a3a;">ctx: ${escHtml(JSON.stringify(l.ctx))}</span>` : ''}
          </div>
        </div>
      `;
    }).join('');
  }

  function updateBadge() {
    const errCount = STATE.logs.filter(l => l.level === 'error').length;
    const badge    = document.getElementById('__ba_btn_badge');
    const hdrBadge = document.getElementById('__ba_badge');

    if (badge) {
      if (errCount > 0) {
        badge.style.display = 'block';
        badge.textContent = errCount > 99 ? '99+' : String(errCount);
      } else {
        badge.style.display = 'none';
      }
    }
    if (hdrBadge) {
      if (errCount > 0) {
        hdrBadge.style.display = 'inline-block';
        hdrBadge.textContent = String(errCount);
      } else {
        hdrBadge.style.display = 'none';
      }
    }
  }

  function flashBtn() {
    const btn = document.getElementById(CFG.btnId);
    if (!btn) return;
    btn.style.borderColor  = '#ff3f5b';
    btn.style.boxShadow    = '0 0 20px rgba(255,63,91,0.5)';
    setTimeout(() => {
      if (!STATE.panelOpen) {
        btn.style.borderColor = '#2a2a3a';
        btn.style.boxShadow   = '0 4px 20px rgba(0,0,0,0.6)';
      }
    }, 1500);
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;');
  }

  // ─── COPIAR PARA ANTIGRAVITY ──────────────────────────────────
  function copyLogs() {
    const errors = STATE.logs.filter(l => l.level === 'error' || l.level === 'warn');
    const payload = {
      meta: {
        project:    CFG.project,
        version:    CFG.version,
        ts:         ts(),
        url:        window.location.href,
        userAgent:  navigator.userAgent,
        totalLogs:  STATE.logs.length,
        errors:     STATE.logs.filter(l => l.level === 'error').length,
        warnings:   STATE.logs.filter(l => l.level === 'warn').length,
      },
      errors_and_warnings: errors.slice(0, 50),
      all_logs:            STATE.logs.slice(0, 100),
    };

    copyToClipboard(JSON.stringify(payload, null, 2));
    flashCopied('__ba_copy', '✓ JSON copiado');
  }

  function copyFullPrompt() {
    const errors = STATE.logs.filter(l => l.level === 'error' || l.level === 'warn');
    const payload = {
      meta: {
        project:   CFG.project,
        ts:        ts(),
        url:       window.location.href,
        errors:    STATE.logs.filter(l => l.level === 'error').length,
        warnings:  STATE.logs.filter(l => l.level === 'warn').length,
      },
      logs: errors.slice(0, 50),
    };

    const prompt = `Revisa este log de errores de BenderAnd ERP y repara todos los bugs encontrados.
Analiza cada error, identifica la causa raíz en el código HTML/JS del proyecto,
y entrega el fix completo listo para aplicar (función corregida o bloque de código).
Prioriza errores críticos primero.

LOG:
${JSON.stringify(payload, null, 2)}`;

    copyToClipboard(prompt);
    flashCopied('__ba_prompt', '✓ Prompt copiado — pégalo en el chat');
  }

  function copyToClipboard(text) {
    try {
      navigator.clipboard.writeText(text).catch(() => legacyCopy(text));
    } catch(e) {
      legacyCopy(text);
    }
  }

  function legacyCopy(text) {
    const el = document.createElement('textarea');
    el.value = text;
    el.style.cssText = 'position:fixed;left:-9999px;top:-9999px;';
    document.body.appendChild(el);
    el.select();
    try { document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(el);
  }

  function flashCopied(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    const orig = el.innerHTML;
    el.innerHTML = msg;
    el.style.color = '#00e5a0';
    setTimeout(() => { el.innerHTML = orig; el.style.color = '#7878a0'; }, 2000);
  }

  // ─── DRAGGABLE PANEL ─────────────────────────────────────────
  function makeDraggable(el, handle) {
    let x0, y0, lx, ly;
    handle.addEventListener('mousedown', function(e) {
      x0 = e.clientX; y0 = e.clientY;
      const r = el.getBoundingClientRect();
      lx = r.left; ly = r.top;
      el.style.right = 'auto';
      el.style.bottom = 'auto';

      function move(e) {
        el.style.left = Math.max(0, lx + e.clientX - x0) + 'px';
        el.style.top  = Math.max(0, ly + e.clientY - y0) + 'px';
      }
      function up() {
        document.removeEventListener('mousemove', move);
        document.removeEventListener('mouseup', up);
      }
      document.addEventListener('mousemove', move);
      document.addEventListener('mouseup', up);
    });
  }

  // ─── TECLADO: Ctrl+Shift+D ────────────────────────────────────
  document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'D') {
      e.preventDefault();
      togglePanel();
    }
  });

  // ─── INIT ─────────────────────────────────────────────────────
  function init() {
    loadPersistedLogs();
    buildBtn();

    // Log de inicio
    addLog('info', 'App', `BenderAnd Debug Logger v${CFG.version} iniciado`, {
      page: document.title,
      url:  window.location.href,
      logs_previos: STATE.logs.length - 1,
    });

    // Exponer API global para uso manual
    window.baDebug = {
      open:    () => togglePanel(true),
      close:   () => togglePanel(false),
      toggle:  () => togglePanel(),
      clear:   () => { STATE.logs = []; sessionStorage.removeItem(CFG.storageKey); updatePanel(); updateBadge(); },
      logs:    () => STATE.logs,
      errors:  () => STATE.logs.filter(l => l.level === 'error'),
      copy:    copyLogs,
      prompt:  copyFullPrompt,
      log:     (m, d) => addLog('debug', 'Manual', m, d || {}),
      // Simular error para testear el sistema
      test:    () => { throw new Error('Test error BenderAnd Debug'); },
    };

    // Instrucciones en consola
    _origLog(
      '%c🐛 BenderAnd Debug Logger activo',
      'color:#00e5a0;font-weight:bold;font-family:monospace;font-size:13px;'
    );
    _origLog(
      '%c  Ctrl+Shift+D → panel | baDebug.open() | baDebug.copy() → copiar para Antigravity',
      'color:#7878a0;font-family:monospace;font-size:11px;'
    );
  }

  // Esperar DOM listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
