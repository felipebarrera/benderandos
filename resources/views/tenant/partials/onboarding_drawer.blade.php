<!-- ONBOARDING DRAWER -->
<div class="drawer-overlay" id="onboardingOverlay" onclick="closeOnboarding()"></div>
<div class="drawer" id="onboardingDrawer">

  <!-- Header con ring de progreso -->
  <div class="drawer-header" style="background: linear-gradient(135deg, #0d2028 0%, #1a2f3a 100%); padding: 18px 20px; display: flex; align-items: center; gap: 16px; border-bottom: 1px solid rgba(61,217,235,0.15); position: relative;">
    <div class="big-ring" style="position: relative; width: 64px; height: 64px; flex-shrink: 0;">
      <svg width="64" height="64" viewBox="0 0 64 64" style="transform: rotate(-90deg);">
        <circle cx="32" cy="32" r="26" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="5"/>
        <circle cx="32" cy="32" r="26" fill="none" stroke="var(--accent)" stroke-width="5"
          stroke-dasharray="163.4" stroke-dashoffset="163.4" stroke-linecap="round"
          id="bigRingCircle"/>
      </svg>
      <div class="big-ring-text" id="bigRingText" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-family: var(--mono); font-size: 13px; font-weight: 700; color: #fff;">0/7</div>
    </div>
    <div class="drawer-header-text">
      <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin:0;">Primeros pasos</h3>
      <p id="drawerSubtitle" style="font-size: 13px; color: rgba(255,255,255,0.55); margin-top: 3px;">Cargando progreso...</p>
    </div>
    <button class="drawer-close" onclick="closeOnboarding()" style="position: absolute; top: 12px; right: 12px; background: none; border: none; color: rgba(255,255,255,0.5); font-size: 24px; cursor: pointer;">×</button>
  </div>

  <!-- Steps List -->
  <div class="steps-container" id="stepsContainer" style="flex: 1; overflow-y: auto; padding: 16px; background: #08080a;">
    <!-- Rellenado por JS -->
  </div>

  <!-- Help & Discover -->
  <div class="discover-section" style="padding: 12px 16px; border-top: 1px solid #252530; background: #111115;">
    <div class="discover-title" style="font-size: 11px; color: #8888a0; margin-bottom: 10px;">Descubre más herramientas</div>
    <div style="display: flex; gap: 8px;">
      <div style="flex: 1; border: 1px solid #252530; border-radius: 10px; padding: 12px; background: #18181e; opacity: 0.8;">
        <div style="font-size: 12px; font-weight: 600;">Bot WhatsApp</div>
        <div style="font-size: 10px; color: #8888a0; margin-top:4px;">Agendamiento 24/7.</div>
      </div>
      <div style="flex: 1; border: 1px solid #252530; border-radius: 10px; padding: 12px; background: #18181e; opacity: 0.8;">
        <div style="font-size: 12px; font-weight: 600;">Portal {{ $rubroConfig->label_clientes ?? 'clientes' }}</div>
        <div style="font-size: 10px; color: #8888a0; margin-top:4px;">{{ $rubroConfig->is_clinica ? 'Reservas web.' : 'Pedidos online.' }}</div>
      </div>
    </div>
  </div>
</div>

<style>
.drawer {
  position: fixed; top: 0; right: -460px; width: 460px; height: 100vh;
  background: #08080a; border-left: 1px solid #252530; z-index: 2000;
  transition: right 0.4s cubic-bezier(0.77,0.2,0.05,1);
  display: flex; flex-direction: column;
}
.drawer.open { right: 0; }
.drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; display: none; }
.drawer-overlay.open { display: block; }

.step-item { display: flex; align-items: flex-start; gap: 14px; padding: 14px 16px; border-radius: 10px; border: 1px solid #252530; background: #111115; margin-bottom: 8px; transition: all 0.2s; }
.step-item.completado { opacity: 0.6; }
.step-circle { width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; margin-top: 1px; }
.step-circle.done { background: rgba(0,229,160,0.15); color: #00e5a0; border: 1.5px solid rgba(0,229,160,0.3); }
.step-circle.todo { background: #18181e; color: #4a4a60; border: 1.5px solid #32323f; }
.step-circle.current { background: rgba(61,217,235,0.1); color: #3dd9eb; border: 1.5px solid rgba(61,217,235,0.2); }
.step-circle.skipped { background: #18181e; color: #7878a0; border: 1.5px solid #2a2a3a; }

.step-title { font-size: 14px; font-weight: 600; color: #e8e8f0; }
.step-title.done { text-decoration: line-through; color: #8888a0; }
.step-desc { font-size: 12px; color: #8888a0; margin-top: 3px; line-height: 1.5; }
.btn-step-go { padding: 6px 14px; border-radius: 6px; border: none; background: #3dd9eb; color: #000; font-size: 12px; font-weight: 700; cursor: pointer; margin-top: 10px; }
</style>

<button id="btnOnboarding"
    onclick="openOnboarding()"
    style="position:fixed; bottom:24px; right:24px; z-index:900;
           background:#111115; border:1.5px solid #252530; border-radius:50%;
           width:48px; height:48px; display:flex; align-items:center;
           justify-content:center; cursor:pointer; box-shadow:0 4px 20px rgba(0,0,0,.4);
           display:none;">
    <svg width="20" height="20" fill="none" stroke="#00e5a0" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span id="btnOnboardingBadge" style="position:absolute; top:-4px; right:-4px; background:#ff3f5b; color:#fff; font-family:'IBM Plex Mono',monospace; font-size:9px; font-weight:700; padding:1px 5px; border-radius:8px; display:none;"></span>
</button>

<script>
async function fetchOnboardingProgress() {
    try {
        const data = await window.api('GET', '/api/universal/onboarding/progress');
        renderOnboardingSteps(data);
        updateBtnOnboarding(data);
    } catch (e) {
        console.error("Error fetching onboarding progress", e);
    }
}

function updateBtnOnboarding(data) {
    const btn = document.getElementById('btnOnboarding');
    if (!btn) return;
    if (!data.onboarding_completo) {
        btn.style.display = 'flex';
        const pendientes = data.total - data.completados;
        const badge = document.getElementById('btnOnboardingBadge');
        if (badge) {
            badge.textContent = pendientes;
            badge.style.display = pendientes > 0 ? 'inline' : 'none';
        }
    } else {
        btn.style.display = 'none';
    }
}

async function marcarCompleto(id) {
    try {
        await window.api('POST', `/api/universal/onboarding/step/${id}`);
        fetchOnboardingProgress();
    } catch(e) { console.error(e); }
}

async function saltarStep(id) {
    try {
        await window.api('POST', `/api/universal/onboarding/step/${id}/saltar`);
        fetchOnboardingProgress();
    } catch(e) { console.error(e); }
}

function renderOnboardingSteps(data) {
    const container = document.getElementById('stepsContainer');
    const { steps, total, completados } = data;
    
    // Update rings
    document.getElementById('bigRingText').textContent = `${completados}/${total}`;
    if (document.getElementById('navRingText')) {
        document.getElementById('navRingText').textContent = `${completados}/${total}`;
        const pct = Math.round((completados / total) * 100);
        document.getElementById('navPercent').textContent = `${pct}%`;
        const navCircle = document.getElementById('navRingCircle');
        if (navCircle) navCircle.style.strokeDashoffset = 69.1 - (69.1 * (completados/total));
        
        const navCircleMobile = document.getElementById('navRingCircleMobile');
        if (navCircleMobile) navCircleMobile.style.strokeDashoffset = 69.1 - (69.1 * (completados/total));
    }
    
    const bigCircle = document.getElementById('bigRingCircle');
    if (bigCircle) bigCircle.style.strokeDashoffset = 163.4 - (163.4 * (completados/total));
    
    document.getElementById('drawerSubtitle').textContent = `Has completado ${completados} de ${total} tareas`;

    const stepDefinitions = {
        'crear_cuenta': { titulo: 'Crea tu cuenta', desc: 'Tu {{ $rubroConfig->label_empresa ?? "negocio" }} ya está registrado.' },
        'agregar_profesionales': { titulo: 'Agrega tus {{ $rubroConfig->label_operarios ?? "colaboradores" }}', desc: 'Crea los usuarios que atenderán.', url: '/admin/usuarios' },
        'configurar_horarios': { titulo: 'Configura horarios', desc: 'Define disponibilidad por {{ $rubroConfig->label_operario ?? "operario" }}.', url: '/admin/agenda' },
        'crear_servicios': { titulo: 'Crea tus productos/servicios', desc: 'Agrega lo que vas a vender.', url: '/admin/productos' },
        'primera_cita': { titulo: 'Registra tu primera {{ $rubroConfig->label_cita ?? "venta" }}', desc: 'Prueba el flujo de operación.', url: '/pos' },
        'configurar_sii': { titulo: 'Configura facturación SII', desc: 'Sube tu certificado digital.', url: '/admin/sii' },
        'activar_whatsapp': { titulo: 'Activa el bot de WhatsApp', desc: 'Atención automática 24/7.', url: '/admin/whatsapp' }
    };

    container.innerHTML = steps.map((s, i) => {
        const def = stepDefinitions[s.step_id] || { titulo: s.step_id, desc: '' };
        const isDone = s.estado === 'completado';
        const isSkipped = s.estado === 'saltado';
        const isPending = s.estado === 'pendiente';
        const isCurrent = isPending && steps.findIndex(prev => prev.estado === 'pendiente') === i;
        
        let circleClass = isDone ? 'done' : isSkipped ? 'skipped' : isCurrent ? 'current' : 'todo';
        let circleIcon = isDone ? '✓' : isSkipped ? '—' : (i + 1);
        
        return `
            <div class="step-item ${isDone ? 'completado' : ''}">
                <div class="step-circle ${circleClass}">${circleIcon}</div>
                <div class="step-body">
                    <div class="step-title ${isDone ? 'done' : ''}">${def.titulo}</div>
                    <div class="step-desc">${def.desc}</div>
                    ${(isPending || isCurrent) ? `
                    <div class="step-actions" style="margin-top:10px; display:flex; gap:8px;">
                        ${def.url ? `<button class="btn-step-go" onclick="window.location='${def.url}'">Ir a configurar</button>` : ''}
                        <button class="btn-step-go" style="background:#18181e; color:#00e5a0; border:1px solid rgba(0,229,160,0.3);" onclick="marcarCompleto('${s.step_id}')">✓ Ya lo hice</button>
                        ${s.step_id !== 'agregar_profesionales' && s.step_id !== 'crear_servicios' && s.step_id !== 'configurar_horarios' ? `<button class="btn-step-go" style="background:transparent; color:#8888a0; border:none;" onclick="saltarStep('${s.step_id}')">Saltar</button>` : ''}
                    </div>` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function openOnboarding() {
    document.getElementById('onboardingOverlay').classList.add('open');
    document.getElementById('onboardingDrawer').classList.add('open');
    fetchOnboardingProgress();
}

function closeOnboarding() {
    document.getElementById('onboardingOverlay').classList.remove('open');
    document.getElementById('onboardingDrawer').classList.remove('open');
}

// Check auto-open
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const data = await window.api('GET', '/api/universal/onboarding/progress');
        renderOnboardingSteps(data);
        updateBtnOnboarding(data);
        
        if (!data.onboarding_completo && !sessionStorage.getItem('onboarding_dismissed')) {
            setTimeout(openOnboarding, 1000);
            sessionStorage.setItem('onboarding_dismissed', 'true');
        }
    } catch(e) { console.error(e); }
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOnboarding(); });
</script>
