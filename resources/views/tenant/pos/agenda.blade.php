@extends('tenant.layout_fullscreen')

@section('content')
<style>
/* ── AGENDA SHELL ─────────────────────────────────────── */
.agenda-shell { display:flex; flex-direction:column; height:100vh; background:#08080a; overflow:hidden; }
.agenda-topbar { display:flex; align-items:center; gap:12px; padding:10px 16px; background:#111115; border-bottom:1px solid #1e1e28; flex-shrink:0; flex-wrap:wrap; }
.agenda-titulo { font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:15px; color:#e8e8f0; }
.agenda-fecha-nav { display:flex; align-items:center; gap:6px; }
.fecha-btn { background:#18181e; border:1px solid #2a2a3a; color:#7878a0; border-radius:7px; width:30px; height:30px; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all .12s; }
.fecha-btn:hover { border-color:#00e5a0; color:#00e5a0; }
.fecha-display { font-family:'IBM Plex Mono',monospace; font-size:13px; color:#e8e8f0; font-weight:600; min-width:140px; text-align:center; }
.vista-pill { display:flex; gap:4px; }
.vpill { padding:5px 12px; border-radius:12px; font-size:10px; font-weight:700; font-family:'IBM Plex Mono',monospace; cursor:pointer; border:1px solid #2a2a3a; background:#18181e; color:#7878a0; transition:all .12s; }
.vpill.on { background:rgba(0,229,160,.1); border-color:#00e5a0; color:#00e5a0; }
.vpill-group { display:flex; background:#18181e; padding:3px; border-radius:14px; border:1px solid #2a2a3a; }
.nueva-cita-btn { margin-left:auto; background:#00e5a0; color:#000; border:none; border-radius:8px; padding:8px 16px; font-family:'IBM Plex Sans',sans-serif; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; }
.nueva-cita-btn:hover { background:#00b87c; }

/* ── COLUMNAS ──────────────────────────────────────────── */
.agenda-body { flex:1; display:flex; overflow:hidden; }
.agenda-cols { flex:1; display:flex; overflow-x:auto; overflow-y:hidden; }
.agenda-col { min-width:220px; flex:1; display:flex; flex-direction:column; border-right:1px solid #1e1e28; }
.col-header { padding:10px 12px; background:#111115; border-bottom:1px solid #1e1e28; flex-shrink:0; }
.col-nombre { font-size:13px; font-weight:700; color:#e8e8f0; }
.col-esp { font-size:10px; color:#7878a0; margin-top:2px; }
.col-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; }
.col-scroll { flex:1; overflow-y:auto; padding:6px; scrollbar-width:thin; }
.col-scroll::-webkit-scrollbar { width:3px; }
.col-scroll::-webkit-scrollbar-thumb { background:#2a2a3a; border-radius:2px; }

/* ── SLOTS Y CITAS ─────────────────────────────────────── */
.slot-libre { padding:6px 10px; margin-bottom:4px; border-radius:7px; background:#0d0d11; border:1px dashed #1e1e28; cursor:pointer; display:flex; align-items:center; gap:8px; font-size:11px; color:#3a3a55; transition:all .15s; }
.slot-libre:hover { border-color:#00e5a0; color:#00e5a0; background:rgba(0,229,160,.04); }
.slot-hora { font-family:'IBM Plex Mono',monospace; font-size:10px; font-weight:600; width:40px; flex-shrink:0; }

.cita-card { padding:10px 12px; margin-bottom:5px; border-radius:9px; border:1px solid #1e1e28; cursor:pointer; position:relative; transition:all .15s; }
.cita-card:hover { transform:translateY(-1px); box-shadow:0 4px 16px rgba(0,0,0,.3); }
.cita-hora { font-family:'IBM Plex Mono',monospace; font-size:10px; font-weight:700; margin-bottom:4px; }
.cita-nombre { font-size:12px; font-weight:600; color:#e8e8f0; margin-bottom:2px; }
.cita-rut { font-size:10px; color:#7878a0; font-family:'IBM Plex Mono',monospace; }
.cita-servicio { font-size:10px; color:#7878a0; margin-top:3px; }
.cita-badge { display:inline-flex; align-items:center; padding:2px 7px; border-radius:4px; font-size:9px; font-weight:700; font-family:'IBM Plex Mono',monospace; letter-spacing:.5px; margin-top:4px; }
/* Estado colors */
.st-pendiente   { background:rgba(245,197,24,.12);  color:#f5c518; border:1px solid rgba(245,197,24,.2); }
.st-confirmada  { background:rgba(0,229,160,.1);    color:#00e5a0; border:1px solid rgba(0,229,160,.2); }
.st-en_curso    { background:rgba(68,136,255,.12);  color:#4488ff; border:1px solid rgba(68,136,255,.2); }
.st-completada  { background:rgba(136,136,160,.1);  color:#8888a0; border:1px solid rgba(136,136,160,.2); }
.st-cancelada   { background:rgba(255,63,91,.08);   color:#ff3f5b; border:1px solid rgba(255,63,91,.15); opacity:.6; }
.st-no_asistio  { background:rgba(255,63,91,.06);   color:#ff3f5b; opacity:.5; }

/* ── PANEL LATERAL DETALLE ─────────────────────────────── */
.agenda-panel { width:360px; min-width:360px; background:#111115; border-left:1px solid #1e1e28; display:flex; flex-direction:column; transition:transform .25s; position:relative; z-index:10; }
.agenda-panel.cerrado { display:none; }
.panel-head { padding:14px 16px; border-bottom:1px solid #1e1e28; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
.panel-titulo { font-family:'IBM Plex Mono',monospace; font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#7878a0; }
.panel-close { background:none; border:none; color:#7878a0; font-size:18px; cursor:pointer; line-height:1; padding:2px 6px; border-radius:6px; }
.panel-close:hover { background:#18181e; color:#e8e8f0; }
.panel-body { flex:1; overflow-y:auto; padding:16px; }
.panel-foot { padding:12px 16px; border-top:1px solid #1e1e28; flex-shrink:0; }

/* ── DETALLE FIELDS ────────────────────────────────────── */
.det-row { display:flex; justify-content:space-between; align-items:flex-start; padding:6px 0; border-bottom:1px solid #1a1a22; font-size:12px; }
.det-row:last-child { border-bottom:none; }
.det-lbl { color:#7878a0; }
.det-val { font-weight:600; text-align:right; max-width:180px; word-break:break-word; }
.textarea-nota { width:100%; background:#18181e; border:1.5px solid #2a2a3a; border-radius:8px; color:#e8e8f0; font-family:'IBM Plex Sans',sans-serif; font-size:12px; padding:9px 11px; outline:none; resize:none; min-height:70px; transition:border-color .15s; }
.textarea-nota:focus { border-color:#00e5a0; }
.nota-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#3a3a55; margin-bottom:5px; }
.nota-privada { border-color:rgba(255,63,91,.25) !important; }
.nota-privada:focus { border-color:rgba(255,63,91,.5) !important; }

/* ── ACCIONES RÁPIDAS ──────────────────────────────────── */
.accion-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-top:12px; }
.ac-btn { padding:8px 6px; border-radius:8px; border:1.5px solid #2a2a3a; background:#18181e; color:#7878a0; font-family:'IBM Plex Sans',sans-serif; font-size:11px; font-weight:600; cursor:pointer; text-align:center; transition:all .12s; }
.ac-btn:hover { border-color:#00e5a0; color:#00e5a0; }
.ac-btn.danger:hover { border-color:#ff3f5b; color:#ff3f5b; }
.ac-btn.primary { background:#00e5a0; border-color:#00e5a0; color:#000; }
.ac-btn.primary:hover { background:#00b87c; }
.ac-btn.full { grid-column:1/-1; padding:11px; font-size:13px; }

/* ── PRÓXIMOS SLOTS ────────────────────────────────────── */
.slot-sugerido { padding:8px 10px; background:#0d0d11; border:1px solid #1e1e28; border-radius:7px; font-size:11px; margin-bottom:5px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; transition:all .12s; }
.slot-sugerido:hover { border-color:#00e5a0; }
.slot-sugerido .sf { font-family:'IBM Plex Mono',monospace; font-size:10px; color:#7878a0; }

/* ── MODAL NUEVA CITA ──────────────────────────────────── */
.modal-cita-wrap { position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; display:none; align-items:center; justify-content:center; }
.modal-cita-wrap.open { display:flex; }
.modal-cita { background:#111115; border:1px solid #2a2a3a; border-radius:14px; width:min(440px,95vw); max-height:90vh; overflow-y:auto; padding:24px; }
.mc-titulo { font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:14px; margin-bottom:18px; }
.mc-field { margin-bottom:12px; }
.mc-label { font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#7878a0; margin-bottom:5px; display:block; }
.mc-input { width:100%; background:#18181e; border:1.5px solid #2a2a3a; border-radius:8px; color:#e8e8f0; font-family:'IBM Plex Mono',monospace; font-size:13px; padding:9px 11px; outline:none; transition:border-color .15s; }
.mc-input:focus { border-color:#00e5a0; }
.mc-select { width:100%; background:#18181e; border:1.5px solid #2a2a3a; border-radius:8px; color:#e8e8f0; font-family:'IBM Plex Sans',sans-serif; font-size:13px; padding:9px 11px; outline:none; }
.mc-2col { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.mc-foot { display:flex; gap:8px; margin-top:18px; }
.btn-cancelar { flex:1; padding:10px; border-radius:8px; border:1.5px solid #2a2a3a; background:#18181e; color:#7878a0; font-size:13px; font-weight:600; cursor:pointer; }
.btn-guardar { flex:2; padding:10px; border-radius:8px; border:none; background:#00e5a0; color:#000; font-size:13px; font-weight:700; cursor:pointer; }
.btn-guardar:hover { background:#00b87c; }

/* ── HISTORIAL PACIENTE ────────────────────────────────── */
.hist-cita { padding:10px 12px; background:#0d0d11; border:1px solid #1e1e28; border-radius:8px; margin-bottom:6px; }
.hist-fecha { font-family:'IBM Plex Mono',monospace; font-size:10px; color:#7878a0; margin-bottom:4px; }
.hist-titulo { font-size:12px; font-weight:600; color:#e8e8f0; }
.hist-nota { font-size:11px; color:#7878a0; margin-top:4px; font-style:italic; }
.lock-badge { display:inline-flex; align-items:center; gap:4px; font-size:9px; color:#ff3f5b; font-family:'IBM Plex Mono',monospace; font-weight:700; letter-spacing:.5px; padding:2px 7px; background:rgba(255,63,91,.08); border:1px solid rgba(255,63,91,.15); border-radius:4px; }

@media(max-width:768px) {
  .agenda-panel { position:fixed; inset:0; z-index:100; width:100vw; }
  .agenda-col { min-width:180px; }
}
</style>

<div class="agenda-shell">

  {{-- TOPBAR --}}
  <div class="agenda-topbar">
    <div class="agenda-fecha-nav">
      <button class="fecha-btn" onclick="cambiarFecha(-1)">‹</button>
      <div class="fecha-display" id="fechaDisplay">Cargando...</div>
      <button class="fecha-btn" onclick="cambiarFecha(1)">›</button>
      <button class="fecha-btn" onclick="irHoy()" title="Hoy" style="font-size:11px;width:auto;padding:0 8px;">Hoy</button>
    </div>

    <div class="vpill-group" style="margin-left:12px;">
      <button class="vpill on" id="vpDia" onclick="setVista('dia')">Día</button>
      <button class="vpill" id="vpSem" onclick="setVista('semana')">Semana</button>
      <button class="vpill" id="vpMes" onclick="setVista('mes')">Mes</button>
    </div>

    @php 
      $miRecurso = \App\Models\Tenant\AgendaRecurso::where('usuario_id', auth()->id())->first();
      $recursos = \App\Models\Tenant\AgendaRecurso::orderBy('orden')->get();
    @endphp

    @if(!$miRecurso)
    <button class="nueva-cita-btn" onclick="abrirModalNuevaCita()">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
      </svg>
      Nueva cita
    </button>
    @endif
  </div>

  {{-- BODY --}}
  <div class="agenda-body">

    {{-- COLUMNAS --}}
    <div class="agenda-cols" id="agendaCols">
      <div style="display:flex;align-items:center;justify-content:center;flex:1;color:#3a3a55;font-family:'IBM Plex Mono',monospace;font-size:12px;">
        Cargando agenda...
      </div>
    </div>

    {{-- PANEL LATERAL --}}
    <div class="agenda-panel cerrado" id="agendaPanel">
      <div class="panel-head">
        <span class="panel-titulo" id="panelTitulo">Detalle cita</span>
        <button class="panel-close" onclick="cerrarPanel()">✕</button>
      </div>
      <div class="panel-body" id="panelBody">
        {{-- Se rellena por JS --}}
      </div>
      <div class="panel-foot" id="panelFoot"></div>
    </div>

  </div>
</div>

{{-- MODAL NUEVA CITA --}}
<div class="modal-cita-wrap" id="modalNuevaCita">
  <div class="modal-cita">
    <div class="mc-titulo">📅 Nueva cita</div>

    <div class="mc-field">
      <label class="mc-label">Profesional / Recurso</label>
      <select class="mc-select" id="mcRecurso" onchange="cargarServiciosModal()"></select>
    </div>

    <div class="mc-field">
      <label class="mc-label">Servicio</label>
      <select class="mc-select" id="mcServicio" onchange="calcularHoraFin()"></select>
    </div>

    <div class="mc-2col">
      <div class="mc-field">
        <label class="mc-label">Fecha</label>
        <input type="date" class="mc-input" id="mcFecha" onchange="cargarSlotsModal()">
      </div>
      <div class="mc-field">
        <label class="mc-label">Hora</label>
        <select class="mc-select" id="mcHora" onchange="calcularHoraFin()"></select>
      </div>
    </div>

    <div class="mc-field" style="background:rgba(245,197,24,.05);border:1px solid rgba(245,197,24,.15);border-radius:8px;padding:10px;">
      <label class="mc-label">Buscar paciente (RUT o nombre)</label>
      <div style="display:flex;gap:6px;">
        <input type="text" class="mc-input" id="mcRutBusca" placeholder="RUT o nombre..." style="flex:1;" oninput="buscarPacienteModal()">
      </div>
      <div id="mcRutResultado" style="margin-top:6px;font-size:11px;color:#7878a0;"></div>
    </div>

    <input type="hidden" id="mcClienteId">
    <div class="mc-field">
      <label class="mc-label">Nombre paciente *</label>
      <input type="text" class="mc-input" id="mcNombre" placeholder="Nombre completo">
    </div>
    <div class="mc-2col">
      <div class="mc-field">
        <label class="mc-label">RUT</label>
        <input type="text" class="mc-input" id="mcRut" placeholder="12.345.678-9">
      </div>
      <div class="mc-field">
        <label class="mc-label">Teléfono</label>
        <input type="tel" class="mc-input" id="mcTelefono" placeholder="+56 9...">
      </div>
    </div>
    <div class="mc-field">
      <label class="mc-label">Notas (visibles al paciente)</label>
      <textarea class="mc-input textarea-nota" id="mcNotas" style="min-height:50px;font-family:'IBM Plex Sans',sans-serif;font-size:12px;" placeholder="Motivo de consulta..."></textarea>
    </div>
    <div class="mc-foot">
      <button class="btn-cancelar" onclick="cerrarModalNuevaCita()">Cancelar</button>
      <button class="btn-guardar" onclick="guardarNuevaCita()">Crear cita →</button>
    </div>
  </div>
</div>

<!-- Modal Historial -->
<div id="modalHistorial" class="mc-modal-overlay">
  <div class="mc-modal-content" style="max-width:650px;">
    <div class="mc-head">
      <h3 class="mc-title">Historial Clínico: <span id="mhNombre"></span></h3>
      <button class="mc-close" onclick="cerrarModalHistorial()">×</button>
    </div>
    <div id="mhBody" style="max-height:450px;overflow-y:auto;padding:16px;background:#09090b;">
      <!-- JS -->
    </div>
  </div>
</div>

@push('scripts')
<script>
const ROL = '{{ auth()->user()->rol }}';
const ES_PROFESIONAL = {{ $miRecurso ? 'true' : 'false' }};
const MI_RECURSO_ID  = {{ $miRecurso?->id ?? 'null' }};
const TODOS_RECURSOS = @json($recursos);

let fechaActual = new Date();
let citaActual  = null;
let vistaActual = 'dia';

// ── FECHA ────────────────────────────────────────────────
function formatFecha(d) {
  if (vistaActual === 'dia') return d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
  if (vistaActual === 'semana') {
    const start = getStartOfWeek(d);
    const end = new Date(start); end.setDate(start.getDate() + 6);
    return `${start.getDate()} ${start.toLocaleDateString('es-CL',{month:'short'})} – ${end.getDate()} ${end.toLocaleDateString('es-CL',{month:'short','year':'numeric'})}`;
  }
  return d.toLocaleDateString('es-CL', { month:'long', year:'numeric' });
}
function toISO(d) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}
function cambiarFecha(delta) {
  if (vistaActual === 'dia') fechaActual.setDate(fechaActual.getDate() + delta);
  else if (vistaActual === 'semana') fechaActual.setDate(fechaActual.getDate() + (delta * 7));
  else if (vistaActual === 'mes') fechaActual.setMonth(fechaActual.getMonth() + delta);
  cargarAgenda();
}
function irHoy() {
  fechaActual = new Date();
  cargarAgenda();
}
function setVista(v) {
  vistaActual = v;
  document.getElementById('vpDia').classList.toggle('on', v === 'dia');
  document.getElementById('vpSem').classList.toggle('on', v === 'semana');
  document.getElementById('vpMes').classList.toggle('on', v === 'mes');
  cargarAgenda();
}

function getStartOfWeek(d) {
  const day = d.getDay(), diff = d.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(d.setDate(diff));
}

// ── CARGAR AGENDA ────────────────────────────────────────
async function cargarAgenda() {
  document.getElementById('fechaDisplay').textContent = formatFecha(new Date(fechaActual));
  const cols = document.getElementById('agendaCols');
  cols.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;flex:1;color:#3a3a55;font-family:\'IBM Plex Mono\',monospace;font-size:12px;">Cargando...</div>';

  try {
    if (vistaActual === 'dia') {
      let url = `/api/agenda/dia?fecha=${toISO(fechaActual)}`;
      if (ES_PROFESIONAL && MI_RECURSO_ID) url += `&recurso_id=${MI_RECURSO_ID}`;
      const res = await fetch(url);
      const data = await res.json();
      renderColumnas(data);
    } else if (vistaActual === 'semana') {
      const start = getStartOfWeek(new Date(fechaActual));
      const end = new Date(start); end.setDate(start.getDate() + 6);
      let url = `/api/agenda/calendario?start_date=${toISO(start)}&end_date=${toISO(end)}`;
      if (ES_PROFESIONAL && MI_RECURSO_ID) url += `&recurso_id=${MI_RECURSO_ID}`;
      const res = await fetch(url);
      const data = await res.json();
      renderVistaSemana(data, start);
    } else if (vistaActual === 'mes') {
      const start = new Date(fechaActual.getFullYear(), fechaActual.getMonth(), 1);
      const end = new Date(fechaActual.getFullYear(), fechaActual.getMonth() + 1, 0);
      let url = `/api/agenda/calendario?start_date=${toISO(start)}&end_date=${toISO(end)}`;
      if (ES_PROFESIONAL && MI_RECURSO_ID) url += `&recurso_id=${MI_RECURSO_ID}`;
      const res = await fetch(url);
      const data = await res.json();
      renderVistaMes(data, start, end);
    }
  } catch(e) {
    cols.innerHTML = `<div style="padding:32px;color:#ff3f5b;font-size:12px;">Error: ${e.message}</div>`;
  }
}

// ── RENDER COLUMNAS ──────────────────────────────────────
function renderColumnas(columnas) {
  const cols = document.getElementById('agendaCols');
  if (!columnas.length) {
    cols.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;flex:1;color:#3a3a55;font-size:12px;font-family:\'IBM Plex Mono\',monospace;">Sin recursos configurados.<br>Ir a Admin → Agenda.</div>';
    return;
  }

  cols.innerHTML = columnas.map(col => `
    <div class="agenda-col">
      <div class="col-header">
        <div style="display:flex;align-items:center;gap:8px;">
          <span class="col-dot" style="background:${col.recurso.color};"></span>
          <div>
            <div class="col-nombre">${col.recurso.nombre}</div>
            ${col.recurso.especialidad ? `<div class="col-esp">${col.recurso.especialidad}</div>` : ''}
          </div>
        </div>
        ${!col.tiene_horario ? '<div style="font-size:9px;color:#3a3a55;margin-top:4px;font-family:\'IBM Plex Mono\',monospace;">Sin horario este día</div>' : ''}
      </div>
      <div class="col-scroll">
        ${renderCitasYSlots(col)}
      </div>
    </div>
  `).join('');
}

function renderCitasYSlots(col) {
  if (!col.tiene_horario) return '<div style="padding:16px;font-size:11px;color:#3a3a55;text-align:center;">Día libre</div>';

  const items = [];

  // Mezclar citas y slots libres por hora
  col.citas.forEach(c => items.push({ tipo: 'cita', data: c, hora: c.hora_inicio }));
  col.slots_libres.forEach(s => {
    const ocupado = col.citas.some(c => c.hora_inicio === s.hora_inicio);
    if (!ocupado) items.push({ tipo: 'slot', data: s, hora: s.hora_inicio });
  });
  items.sort((a,b) => a.hora.localeCompare(b.hora));

  return items.map(item => {
    if (item.tipo === 'cita') return renderCitaCard(item.data, col.recurso);
    return `<div class="slot-libre" onclick="abrirModalNuevaCitaConSlot(${col.recurso.id}, '${item.data.hora_inicio}', '${item.data.hora_fin}')">
      <span class="slot-hora">${item.data.hora_inicio}</span>
      <span style="font-size:10px;">— disponible</span>
    </div>`;
  }).join('');
}

function renderCitaCard(c, recurso) {
  const stClass = `st-${c.estado}`;
  const estadoLabel = {
    pendiente:'Pendiente', confirmada:'Confirmada', en_curso:'En curso',
    completada:'Completada', cancelada:'Cancelada', no_asistio:'No asistió'
  }[c.estado] || c.estado;

  return `<div class="cita-card ${c.estado === 'cancelada' ? 'opacity-40' : ''}"
    style="border-left:3px solid ${recurso.color};"
    onclick="abrirPanel(${c.id})">
    <div class="cita-hora">${c.hora_inicio.substring(0,5)} – ${c.hora_fin.substring(0,5)}</div>
    <div class="cita-nombre">${c.paciente_nombre}</div>
    ${c.paciente_rut ? `<div class="cita-rut">${c.paciente_rut}</div>` : ''}
    ${c.servicio ? `<div class="cita-servicio">📋 ${c.servicio.nombre || c.servicio}</div>` : ''}
    <span class="cita-badge ${stClass}">${estadoLabel}</span>
  </div>`;
}

function renderVistaSemana(data, start) {
  const cols = document.getElementById('agendaCols');
  let html = '';
  for (let i = 0; i < 7; i++) {
    const d = new Date(start); d.setDate(start.getDate() + i);
    const iso = toISO(d);
    const citas = data[iso] || [];
    html += `
      <div class="agenda-col">
        <div class="col-header" style="text-align:center;">
          <div class="col-esp">${d.toLocaleDateString('es-CL',{weekday:'short'}).toUpperCase()}</div>
          <div class="col-nombre">${d.getDate()}</div>
        </div>
        <div class="col-scroll">
          ${citas.map(c => renderCitaCard(c, c.recurso)).join('')}
          ${citas.length === 0 ? '<div style="padding:15px;color:#3a3a55;font-size:10px;text-align:center;">Sin citas</div>' : ''}
        </div>
      </div>
    `;
  }
  cols.innerHTML = html;
}

function renderVistaMes(data, start, end) {
  const cols = document.getElementById('agendaCols');
  let html = `<div style="flex:1;display:grid;grid-template-columns:repeat(7, 1fr);background:#1e1e28;gap:1px;height:100%;overflow-y:auto;">`;
  
  // Headers
  ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'].forEach(h => {
    html += `<div style="background:#111115;padding:8px;text-align:center;font-size:10px;font-weight:700;color:#7878a0;text-transform:uppercase;border-bottom:1px solid #1e1e28;">${h}</div>`;
  });

  // Dias previos
  const firstDay = (start.getDay() === 0 ? 7 : start.getDay()) - 1;
  for (let i = 0; i < firstDay; i++) html += `<div style="background:#08080a;"></div>`;

  // Dias del mes
  for (let d = 1; d <= end.getDate(); d++) {
    const cur = new Date(start.getFullYear(), start.getMonth(), d);
    const iso = toISO(cur);
    const citas = data[iso] || [];
    html += `
      <div style="background:#0d0d11;min-height:100px;padding:8px;position:relative;" onclick="fechaActual=new Date('${iso}');setVista('dia');">
        <div style="font-size:11px;font-weight:700;color:${citas.length?'#00e5a0':'#3a3a55'};">${d}</div>
        <div style="margin-top:6px;">
          ${citas.slice(0,3).map(c => `
            <div style="font-size:9px;background:rgba(255,255,255,0.05);padding:2px 4px;border-radius:3px;margin-bottom:2px;color:#e8e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              ${c.hora_inicio.substring(0,5)} ${c.paciente_nombre}
            </div>
          `).join('')}
          ${citas.length > 3 ? `<div style="font-size:9px;color:#7878a0;text-align:center;">+${citas.length-3} más</div>` : ''}
        </div>
      </div>
    `;
  }
  html += `</div>`;
  cols.innerHTML = html;
}

// ── PANEL LATERAL ────────────────────────────────────────
async function abrirPanel(citaId) {
  document.getElementById('agendaPanel').classList.remove('cerrado');
  document.getElementById('panelBody').innerHTML = '<div style="padding:20px;color:#7878a0;text-align:center;">Cargando...</div>';

  try {
    const res = await fetch(`/api/agenda/citas/${citaId}`);
    const cita = await res.json();
    citaActual = cita;
    const recurso = cita.recurso;

    document.getElementById('panelTitulo').textContent = `${cita.hora_inicio.substring(0,5)} · ${cita.paciente_nombre}`;

    const esMedico = ES_PROFESIONAL && MI_RECURSO_ID === recurso.id;
    const esAdmin  = ['admin','super_admin'].includes(ROL);

    // Pago status
    let pagoHTML = '';
    if (cita.venta) {
      const v = cita.venta;
      const stColor = v.estado === 'pagada' ? '#00e5a0' : '#f5c518';
      pagoHTML = `
        <div style="margin-top:12px;padding:12px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid #1e1e28;">
          <div class="nota-label">Información de Pago</div>
          <div class="det-row"><span class="det-lbl">Estado</span><span style="color:${stColor};font-weight:700;">${v.estado.toUpperCase()}</span></div>
          <div class="det-row"><span class="det-lbl">Método</span><span class="det-val">${v.tipo_pago?.nombre || 'WebPay'}</span></div>
          <div class="det-row"><span class="det-lbl">Total</span><span class="det-val" style="font-family:'IBM Plex Mono',monospace;">$${v.total.toLocaleString('es-CL')}</span></div>
        </div>
      `;
    }

    document.getElementById('panelBody').innerHTML = `
      <div style="margin-bottom:12px;">
        <div class="det-row"><span class="det-lbl">Paciente</span><span class="det-val">${cita.paciente_nombre}</span></div>
        ${cita.paciente_rut ? `<div class="det-row"><span class="det-lbl">RUT</span><span class="det-val" style="font-family:'IBM Plex Mono',monospace;">${cita.paciente_rut}</span></div>` : ''}
        ${cita.paciente_telefono ? `<div class="det-row"><span class="det-lbl">Teléfono</span><span class="det-val">${cita.paciente_telefono}</span></div>` : ''}
        <div class="det-row"><span class="det-lbl">Horario</span><span class="det-val" style="font-family:'IBM Plex Mono',monospace;">${cita.hora_inicio.substring(0,5)} – ${cita.hora_fin.substring(0,5)}</span></div>
        <div class="det-row"><span class="det-lbl">Servicio</span><span class="det-val">${cita.servicio?.nombre || 'General'}</span></div>
        <div class="det-row"><span class="det-lbl">Estado</span><span class="cita-badge st-${cita.estado}">${cita.estado.replace('_',' ')}</span></div>
        <div class="det-row"><span class="det-lbl">Origen</span><span class="det-val" style="text-transform:capitalize;color:#7878a0;">${cita.origen || 'admin'}</span></div>
        ${cita.notas_publicas ? `<div style="margin-top:10px;"><div class="nota-label">Notas del paciente</div><div style="font-size:12px;color:#7878a0;">${cita.notas_publicas}</div></div>` : ''}
      </div>

      ${pagoHTML}

      ${(esMedico || esAdmin) ? `
      <div style="margin-top:12px;">
        <div class="nota-label">
          🔒 Notas internas
          <span class="lock-badge" style="margin-left:6px;">PRIVADO</span>
        </div>
        <textarea class="textarea-nota nota-privada" id="notaInterna" placeholder="Diagnóstico, observaciones clínicas (solo staff)..."
          onblur="guardarNotaInterna(${cita.id})">${cita.notas_internas || ''}</textarea>
        <div style="font-size:10px;color:#3a3a55;margin-top:4px;">Se guarda automáticamente al salir del campo</div>
      </div>` : ''}

      ${cita.cliente_id ? `
      <div style="margin-top:16px;">
        <button onclick="verHistorialPaciente(${cita.cliente_id}, '${cita.paciente_nombre}')"
          style="width:100%;padding:8px;background:rgba(68,136,255,.1);border:1px solid rgba(68,136,255,.2);border-radius:8px;color:#4488ff;font-size:12px;font-weight:600;cursor:pointer;">
          📋 Ver historial del paciente →
        </button>
      </div>` : ''}
    `;

    renderAccionesPanel(cita, esMedico, esAdmin);
  } catch(e) {
    document.getElementById('panelBody').innerHTML = `<div style="color:#ff3f5b;">Error al cargar detalle: ${e.message}</div>`;
  }
}

function renderAccionesPanel(cita, esMedico, esAdmin) {
  const foot = document.getElementById('panelFoot');
  const puede = esMedico || esAdmin || ROL === 'cajero';

  let acciones = '';

  if (cita.estado === 'pendiente') {
    acciones += `<button class="ac-btn" onclick="cambiarEstadoCita(${cita.id},'confirmada')">✅ Confirmar llegada</button>`;
    acciones += `<button class="ac-btn danger" onclick="cambiarEstadoCita(${cita.id},'no_asistio')">❌ No asistió</button>`;
    acciones += `<button class="ac-btn danger" onclick="cambiarEstadoCita(${cita.id},'cancelada')">🗑 Cancelar cita</button>`;
  } else if (cita.estado === 'confirmada') {
    if (esMedico || esAdmin) {
      acciones += `<button class="ac-btn primary" onclick="iniciarConsulta(${cita.id})">▶ Iniciar consulta</button>`;
    }
    acciones += `<button class="ac-btn danger" onclick="cambiarEstadoCita(${cita.id},'no_asistio')">❌ No asistió</button>`;
  } else if (cita.estado === 'en_curso') {
    acciones += `<button class="ac-btn primary full" onclick="completarYCobrar(${cita.id})">💳 Completar y cobrar →</button>`;
  }

  foot.innerHTML = acciones ? `<div class="accion-grid">${acciones}</div>` : '';
}

function cerrarPanel() {
  document.getElementById('agendaPanel').classList.add('cerrado');
  citaActual = null;
}

// ── ACCIONES ─────────────────────────────────────────────
async function cambiarEstadoCita(id, estado) {
  try {
    const res = await fetch(`/api/agenda/citas/${id}/estado`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ estado })
    });
    if(!res.ok) throw new Error('Error al actualizar');
    cargarAgenda();
    cerrarPanel();
  } catch(e) { alert(e.message); }
}

async function iniciarConsulta(id) {
  try {
    const res = await fetch(`/api/agenda/citas/${id}/iniciar-consulta`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    });
    if(!res.ok) throw new Error('Error al iniciar');
    cargarAgenda();
    cerrarPanel();
  } catch(e) { alert(e.message); }
}

async function completarYCobrar(id) {
  try {
    const res = await fetch(`/api/agenda/citas/${id}/completar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    });
    if(!res.ok) throw new Error('Error al completar');
    cargarAgenda();
    cerrarPanel();
  } catch(e) { alert(e.message); }
}

async function guardarNotaInterna(id) {
  const nota = document.getElementById('notaInterna')?.value;
  if (nota === undefined) return;
  try {
    await fetch(`/api/agenda/citas/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ notas_internas: nota })
    });
  } catch(e) { }
}

// ── MODAL NUEVA CITA ─────────────────────────────────────
function abrirModalNuevaCita(fechaPreset = null, horaPreset = null) {
  const sel = document.getElementById('mcRecurso');
  sel.innerHTML = TODOS_RECURSOS.map(r => `<option value="${r.id}" data-color="${r.color}">${r.nombre}${r.especialidad ? ' · '+r.especialidad : ''}</option>`).join('');
  if (MI_RECURSO_ID) sel.value = MI_RECURSO_ID;

  const hoyISO = toISO(fechaActual);
  document.getElementById('mcFecha').value = fechaPreset || hoyISO;

  cargarServiciosModal();
  cargarSlotsModal(horaPreset);

  document.getElementById('mcNombre').value = '';
  document.getElementById('mcRut').value = '';
  document.getElementById('mcTelefono').value = '';
  document.getElementById('mcNotas').value = '';
  document.getElementById('mcClienteId').value = '';

  document.getElementById('modalNuevaCita').classList.add('open');
}

function abrirModalNuevaCitaConSlot(recursoId, horaInicio, horaFin) {
  abrirModalNuevaCita(null, horaInicio);
  document.getElementById('mcRecurso').value = recursoId;
  cargarServiciosModal();
}

function cerrarModalNuevaCita() {
  document.getElementById('modalNuevaCita').classList.remove('open');
}

async function verHistorialPaciente(clienteId, nombre) {
  if (!clienteId) return;
  document.getElementById('mhNombre').textContent = nombre;
  document.getElementById('modalHistorial').classList.add('open');
  const body = document.getElementById('mhBody');
  body.innerHTML = '<div style="color:#7878a0;padding:20px;text-align:center;">Cargando historial...</div>';

  try {
    const res = await fetch(`/api/agenda/paciente/${clienteId}/historial`);
    const data = await res.json();
    
    if (!data.length) {
      body.innerHTML = '<div style="color:#7878a0;padding:20px;text-align:center;">No hay atenciones previas registradas.</div>';
      return;
    }

    body.innerHTML = data.map(h => `
      <div style="background:#111115;border:1px solid #1e1e28;border-radius:12px;padding:16px;margin-bottom:12px;">
        <div style="display:flex;justify-content:between;align-items:center;margin-bottom:8px;">
          <span style="font-size:11px;font-family:'IBM Plex Mono',monospace;color:#00e5a0;background:rgba(0,229,160,0.1);padding:2px 6px;border-radius:4px;">${h.fecha}</span>
          <span style="font-size:11px;color:#7878a0;font-style:italic;">Prof: ${h.profesional}</span>
        </div>
        <div style="font-size:13px;font-weight:bold;color:#e8e8f0;margin-bottom:4px;">${h.servicio}</div>
        <div style="font-size:12px;color:#a8a8c0;white-space:pre-wrap;line-height:1.4;">${h.notas || '(Cita completada sin notas adicionales)'}</div>
      </div>
    `).join('');
  } catch(e) { 
    body.innerHTML = '<div style="color:#ff5555;padding:20px;text-align:center;">Error al cargar historial</div>';
  }
}

function cerrarModalHistorial() {
  document.getElementById('modalHistorial').classList.remove('open');
}

async function buscarPacienteModal() {
  const q = document.getElementById('mcRutBusca').value;
  const resDiv = document.getElementById('mcRutResultado');
  if (q.length < 3) { resDiv.innerHTML = ''; return; }

  try {
    const res = await fetch(`/api/clientes?q=${encodeURIComponent(q)}&per_page=5`);
    const data = await res.json();
    const clientes = data.data || [];
    
    if (clientes.length === 0) {
      resDiv.innerHTML = '<div style="padding:4px;color:#ff5555;">No se encontraron resultados</div>';
      return;
    }

    resDiv.innerHTML = clientes.map(c => `
      <div onclick="seleccionarPaciente(${JSON.stringify(c).replace(/"/g, '&quot;')})" 
           style="padding:8px;border-bottom:1px solid rgba(255,255,255,0.05);cursor:pointer;hover:background:rgba(255,255,255,0.05);">
        <div style="font-weight:bold;color:#fff;">${c.nombre}</div>
        <div style="font-size:10px;color:#7878a0;">${c.rut || 'Sin RUT'} • ${c.telefono || 'Sin tel'}</div>
      </div>
    `).join('');
  } catch(e) { console.error(e); }
}

function seleccionarPaciente(c) {
  document.getElementById('mcClienteId').value = c.id;
  document.getElementById('mcNombre').value    = c.nombre;
  document.getElementById('mcRut').value       = c.rut || '';
  document.getElementById('mcTelefono').value  = c.telefono || '';
  document.getElementById('mcRutBusca').value  = '';
  document.getElementById('mcRutResultado').innerHTML = '<div style="color:#00e5a0;padding:4px;">✓ Paciente seleccionado</div>';
}

async function cargarServiciosModal() {
  const recursoId = document.getElementById('mcRecurso').value;
  if (!recursoId) return;
  try {
    const res = await fetch('/api/agenda/recursos');
    const recursos = await res.json();
    const recurso = recursos.find(r => r.id == recursoId);
    const sel = document.getElementById('mcServicio');
    sel.innerHTML = '<option value="">Sin servicio específico</option>';
    (recurso?.servicios || []).forEach(s => {
      sel.innerHTML += `<option value="${s.id}" data-duracion="${s.duracion_min}" data-precio="${s.precio}">${s.nombre} · ${s.duracion_min}min · $${s.precio.toLocaleString('es-CL')}</option>`;
    });
  } catch(e) {}
}

async function cargarSlotsModal(presetHora = null) {
  const recursoId = document.getElementById('mcRecurso').value;
  const fecha     = document.getElementById('mcFecha').value;
  if (!recursoId || !fecha) return;

  try {
    const duracion = parseInt(document.getElementById('mcServicio')?.selectedOptions[0]?.dataset.duracion) || 30;
    const res = await fetch(`/api/agenda/slots?recurso_id=${recursoId}&fecha=${fecha}&duracion=${duracion}`);
    const slots = await res.json();
    const sel = document.getElementById('mcHora');
    if (!slots.length) {
      sel.innerHTML = '<option value="">Sin horarios disponibles</option>';
      return;
    }
    sel.innerHTML = slots.map(s => `<option value="${s.hora_inicio}|${s.hora_fin}">${s.hora_inicio} – ${s.hora_fin}</option>`).join('');
    if (presetHora) {
      const opt = [...sel.options].find(o => o.value.startsWith(presetHora));
      if (opt) sel.value = opt.value;
    }
  } catch(e) {}
}

function calcularHoraFin() { cargarSlotsModal(); }

async function guardarNuevaCita() {
  const horaVal = document.getElementById('mcHora').value;
  if (!horaVal) { alert('Selecciona un horario disponible'); return; }
  const [horaInicio, horaFin] = horaVal.split('|');

  const payload = {
    agenda_recurso_id:  parseInt(document.getElementById('mcRecurso').value),
    agenda_servicio_id: document.getElementById('mcServicio').value || null,
    cliente_id:         document.getElementById('mcClienteId').value || null,
    fecha:              document.getElementById('mcFecha').value,
    hora_inicio:        horaInicio,
    hora_fin:           horaFin,
    paciente_nombre:    document.getElementById('mcNombre').value,
    paciente_rut:       document.getElementById('mcRut').value || null,
    paciente_telefono:  document.getElementById('mcTelefono').value || null,
    notas_publicas:     document.getElementById('mcNotas').value || null,
    origen:             'admin',
  };

  if (!payload.paciente_nombre) { alert('Nombre requerido'); return; }

  try {
    const res = await fetch('/api/agenda/citas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(payload)
    });
    if(!res.ok) throw new Error('Error al guardar');
    cerrarModalNuevaCita();
    cargarAgenda();
  } catch(e) { alert(e.message); }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarAgenda();
});
</script>
@endpush
@endsection
