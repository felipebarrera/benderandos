@push('head')
<style>
/* ── AGENDA LAYOUT ─────────────────────────────────────── */
.ag-wrap {
  display: flex;
  height: calc(100vh - 54px - 73px); /* nav-h + page-header */
  min-height: 0;
  overflow: hidden;
  width: 100%;
}
@media (max-width: 767px) {
  .ag-wrap { height: calc(100vh - 54px); flex-direction: column; }
  .ag-profs-col { width: 100%; max-height: 200px; }
  .ag-pac-panel { position: absolute; inset: 0; width: 100% !important; z-index: 50; }
}

/* Columna izquierda: profesionales */
.ag-profs-col {
  width: 240px; flex-shrink: 0; border-right: 1px solid var(--b1, #252530);
  background: var(--s1, #111115); display: flex; flex-direction: column; overflow: hidden;
}
.ag-profs-header {
  padding: 10px 14px 8px; border-bottom: 1px solid var(--b1, #252530); font-size: 9px;
  font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--t3, #4a4a60);
}
.ag-profs-list { flex: 1; overflow-y: auto; padding: 8px; }
.ag-prof-item {
  display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 8px;
  cursor: pointer; transition: all 0.12s; margin-bottom: 4px; border: 1px solid transparent;
}
.ag-prof-item:hover  { background: var(--s2, #18181e); }
.ag-prof-item.active { background: rgba(61,217,235,0.1); border-color: rgba(61,217,235,0.25); }
.ag-prof-avatar {
  width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center;
  justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0;
}
.ag-prof-name  { font-size: 13px; font-weight: 600; line-height: 1.2; color:#e8e8f0; }
.ag-prof-esp   { font-size: 10px; color: var(--t2, #8888a0); }
.ag-prof-count {
  margin-left: auto; font-size: 10px; font-family: 'IBM Plex Mono', monospace;
  color: var(--t2, #8888a0); background: var(--s2, #18181e); padding: 2px 7px; border-radius: 10px;
}
.ag-resumen { padding: 12px 14px; border-top: 1px solid var(--b1, #252530); font-size: 11px; }
.ag-resumen-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; color: var(--t2, #8888a0); }
.ag-resumen-badge { font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px; }

/* Columna central: grilla de horas */
.ag-grid-col { flex: 1; overflow-y: auto; background: var(--bg, #08080a); min-width: 0; }

/* Leyenda */
.ag-leyenda {
  display: flex; gap: 14px; padding: 7px 16px; border-bottom: 1px solid var(--b1, #252530);
  background: var(--s1, #111115); flex-wrap: wrap; flex-shrink: 0;
}
.ag-ley-item { display: flex; align-items: center; gap: 5px; font-size: 10px; color: var(--t2, #8888a0); font-family: 'IBM Plex Mono', monospace; }
.ag-ley-dot { width: 8px; height: 8px; border-radius: 50%; }

/* Time rows */
.ag-time-row { display: grid; grid-template-columns: 58px 1fr; border-bottom: 1px solid var(--b1, #252530); min-height: 68px; align-items: stretch; }
.ag-time-row.ag-colacion { min-height: 44px; }
.ag-time-label {
  padding: 10px 10px 0; font-family: 'IBM Plex Mono', monospace; font-size: 11px;
  color: var(--t3, #4a4a60); text-align: right; border-right: 1px solid var(--b1, #252530);
  display: flex; align-items: flex-start;
}
.ag-time-slot { padding: 4px 8px; cursor: pointer; transition: background 0.1s; }
.ag-time-slot:hover { background: var(--s2, #18181e); }
.ag-time-slot.ag-ocupado { cursor: default; padding: 0; }

/* Slot libre */
.ag-libre-slot {
  display: flex; align-items: center; justify-content: center; height: 58px; color: var(--t3, #4a4a60);
  font-size: 11px; cursor: pointer; transition: all 0.15s; border-radius: 6px;
  margin: 4px 8px; border: 1px dashed transparent;
}
.ag-libre-slot:hover { color: var(--accent, #3dd9eb); border-color: rgba(61,217,235,0.3); background: rgba(61,217,235,0.06); }

/* Bloque de cita */
.ag-cita-block { margin: 3px; border-radius: 8px; padding: 8px 12px; cursor: pointer; transition: all 0.15s; border-left: 3px solid; position: relative; }
.ag-cita-block:hover { filter: brightness(1.12); }
.ag-cita-block.confirmada  { background: rgba(61,217,235,0.09);  border-left-color: #3dd9eb; }
.ag-cita-block.pendiente   { background: rgba(245,197,24,0.08);  border-left-color: #f5c518; }
.ag-cita-block.en_curso    { background: rgba(0,229,160,0.09);   border-left-color: #00e5a0; }
.ag-cita-block.completada  { background: var(--s2, #18181e); border-left-color: var(--t3, #4a4a60); opacity: 0.65; }
.ag-cita-block.cancelada   { background: rgba(255,63,91,0.06);   border-left-color: #ff3f5b; opacity: 0.6; }

.ag-cita-hora  { font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-bottom: 3px; color:#e8e8f0; }
.ag-cita-dot   { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.ag-cita-tag   { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
.ag-cita-pac   { font-size: 13px; font-weight: 600; line-height: 1.2; color:#e8e8f0;}
.ag-cita-srv   { font-size: 11px; color: var(--t2, #8888a0); margin-top: 2px; }
.ag-cita-btns  { position: absolute; top: 6px; right: 8px; display: flex; gap: 4px; opacity: 0; transition: opacity 0.15s; }
.ag-cita-block:hover .ag-cita-btns { opacity: 1; }
.ag-cita-btn {
  width: 24px; height: 24px; border-radius: 5px; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 11px;
  background: var(--b2, #2a2a3a); color: var(--t2, #8888a0); transition: all 0.1s;
}
.ag-cita-btn:hover { background: var(--accent, #3dd9eb); color: #000; }

/* Panel derecho paciente */
.ag-pac-panel {
  width: 390px; flex-shrink: 0; border-left: 1px solid var(--b1, #252530); background: var(--s1, #111115);
  display: flex; flex-direction: column; overflow: hidden; transition: width 0.2s;
}
.ag-pac-panel.ag-oculto { width: 0; overflow: hidden; border-left: none; }

/* Colación */
.ag-colacion .ag-time-slot { display: flex; align-items: center; gap: 8px; padding: 8px 16px; color: var(--t3, #4a4a60); font-size: 12px; font-style: italic; cursor: default; }
.ag-colacion .ag-time-slot:hover { background: none; }

/* Info rows en panel */
.ag-info-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--b1, #252530); font-size: 13px; }
.ag-info-row span:last-child { color:#e8e8f0; }
.ag-info-row:last-child { border-bottom: none; }

/* Nota cifrada */
.ag-nota-card { background: var(--s2, #18181e); border: 1px solid var(--b2, #2a2a3a); border-radius: 8px; padding: 12px; margin-bottom: 10px; }
</style>
@endpush

{{-- WRAPPER GENERAL PARA LEYENDA + COLUMNAS --}}
<div style="display:flex; flex-direction:column; height:100%; width:100%; overflow:hidden;">

  {{-- LEYENDA DE ESTADOS --}}
  <div class="ag-leyenda">
    <div class="ag-ley-item"><div class="ag-ley-dot" style="background:#3dd9eb"></div> Confirmada</div>
    <div class="ag-ley-item"><div class="ag-ley-dot" style="background:#f5c518"></div> En espera</div>
    <div class="ag-ley-item"><div class="ag-ley-dot" style="background:#00e5a0"></div> En consulta</div>
    <div class="ag-ley-item"><div class="ag-ley-dot" style="background:var(--t3, #4a4a60)"></div> Completada</div>
    <div class="ag-ley-item"><div class="ag-ley-dot" style="background:#ff3f5b"></div> Cancelada</div>
  </div>

  {{-- LAYOUT TRES COLUMNAS --}}
  <div class="ag-wrap" id="ag_wrap">

    {{-- COL IZQ: Profesionales --}}
    <div class="ag-profs-col">
      <div class="ag-profs-header">Profesionales</div>
      <div class="ag-profs-list" id="ag_profList">
        <div style="padding:16px;text-align:center;color:var(--t3,#4a4a60);font-size:12px;">Cargando…</div>
      </div>
      <div class="ag-resumen" id="ag_resumen"></div>
    </div>

    {{-- COL CENTRO: Grilla de horas --}}
    <div class="ag-grid-col">
      <div id="ag_timeGrid">
        <div style="padding:24px;text-align:center;color:var(--t3,#4a4a60);font-size:13px;">Cargando agenda…</div>
      </div>
    </div>

    {{-- COL DER: Panel detalle paciente (oculto por defecto) --}}
    <div class="ag-pac-panel ag-oculto" id="ag_pacPanel"></div>

  </div>

</div>

{{-- MODAL NUEVA CITA --}}
<div id="ag_modalNuevaCita" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:300;align-items:center;justify-content:center;">
  <div style="background:var(--s1,#111115);border:1px solid var(--b1,#252530);border-radius:14px;width:480px;max-width:95vw;max-height:90vh;overflow-y:auto;color:#e8e8f0;">
    <div style="padding:18px 20px 12px;border-bottom:1px solid var(--b1,#252530);display:flex;align-items:center;justify-content:space-between;">
      <span style="font-size:15px;font-weight:700;">📅 Nueva cita</span>
      <button onclick="ag_cerrarModalNuevaCita()" style="background:none;border:none;color:var(--t2,#8888a0);font-size:20px;cursor:pointer;padding:2px 8px;">×</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">PROFESIONAL</label>
        <select id="ag_nc_prof" onchange="ag_fillModalServicios(this.value)" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;"></select>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">SERVICIO</label>
        <select id="ag_nc_servicio" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;">
          <option value="">Sin servicio específico</option>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
        <div>
          <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">FECHA</label>
          <input type="date" id="ag_nc_fecha" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">HORA INICIO</label>
          <input type="time" id="ag_nc_hora" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;" onchange="ag_autofillHoraFin(this.value)">
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">HORA FIN</label>
          <input type="time" id="ag_nc_hora_fin" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;">
        </div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">PACIENTE</label>
        <input type="text" id="ag_nc_paciente" placeholder="Nombre completo" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">RUT</label>
            <input type="text" id="ag_nc_rut" placeholder="Ej. 12.345.678-9" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;">
        </div>
        <div>
            <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">TELÉFONO</label>
            <input type="text" id="ag_nc_tel" placeholder="Ej. +569XXXX" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;">
        </div>
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--t2,#8888a0);display:block;margin-bottom:6px;">OBSERVACIONES</label>
        <textarea id="ag_nc_obs" rows="2" placeholder="Notas internas…" style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:13px;padding:9px 12px;outline:none;resize:none;"></textarea>
      </div>
    </div>
    <div style="padding:12px 20px;border-top:1px solid var(--b1,#252530);display:flex;gap:10px;justify-content:flex-end;">
      <button style="padding:10px 16px;border-radius:8px;border:1px solid var(--b2,#2a2a3a);background:transparent;color:var(--t1,#e8e8f0);cursor:pointer;font-size:12px;font-weight:600;" onclick="ag_cerrarModalNuevaCita()">Cancelar</button>
      <button style="padding:10px 16px;border-radius:8px;border:none;background:var(--accent,#3dd9eb);color:#000;cursor:pointer;font-size:12px;font-weight:700;" onclick="ag_guardarCita()" id="ag_btn_save">✓ Confirmar cita</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

// ════════════════════════════════════════════════════════
// CONFIGURACIÓN
// ════════════════════════════════════════════════════════
const AG_ROL       = '{{ auth()->user()->rol ?? "cajero" }}';
const AG_PUEDE_EDITAR = ['admin','cajero','recepcionista'].includes(AG_ROL);
const AG_ES_PROF   = ['operario', 'profesional', 'medico'].includes(AG_ROL);

// Horas del día simuladas (ideal sacarlas del horario tenant)
const AG_HORAS = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30'];
const AG_COLACION = '14:00';

const AG_DOT_COLOR   = { confirmada:'#00e5a0', pendiente:'#f5c518', 'en_curso':'#00c4ff', completada:'var(--t3,#4a4a60)', cancelada:'#ff3f5b' };
const AG_ESTADO_LABEL= { confirmada:'CONFIRMADA', pendiente:'PENDIENTE', 'en_curso':'EN CURSO', completada:'COMPLETADA', cancelada:'CANCELADA' };
const DIAS  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// ════════════════════════════════════════════════════════
// ESTADO LOCAL
// ════════════════════════════════════════════════════════
let ag_profs     = [];
let ag_citas     = [];
let ag_profId    = null;
let ag_fecha     = typeof fechaActual !== 'undefined' ? fechaActual : (typeof fechaAgenda !== 'undefined' ? fechaAgenda.toISOString().split('T')[0] : new Date().toISOString().split('T')[0]);

// ════════════════════════════════════════════════════════
// UTILIDADES
// ════════════════════════════════════════════════════════
function ag_labelFecha(isoDate) {
  const d = new Date(isoDate + 'T12:00:00');
  return `${DIAS[d.getDay()]} ${d.getDate()} de ${MESES[d.getMonth()]}`;
}

function ag_initials(nombre) {
  return (nombre || '').split(' ').map(x => x[0]).filter(Boolean).slice(0,2).join('').toUpperCase();
}

// ════════════════════════════════════════════════════════
// API CALLS (CONECTADAS AL BACKEND REAL)
// ════════════════════════════════════════════════════════
async function ag_cargarProfesionales() {
    try {
        if (AG_ES_PROF && !['admin','super_admin','recepcionista','cajero'].includes(AG_ROL)) {
            // Operadores solo cargan su propio recurso
            const mc = await window.api('GET', '/api/agenda/mi/recurso');
            return mc && mc.id ? [mc] : [];
        }
        // Admin / Recepción
        const data = await window.api('GET', '/api/agenda/recursos');
        return Array.isArray(data) ? data : [];
    } catch(e) {
        console.error(e);
        return [];
    }
}

async function ag_cargarCitasData(fechaStr) {
    try {
        const url = (AG_ES_PROF && !['admin','super_admin','recepcionista','cajero'].includes(AG_ROL))
            ? `/api/agenda/mi/dia?fecha=${fechaStr}`
            : `/api/agenda/dia?fecha=${fechaStr}`;
            
        const data = await window.api('GET', url);
        
        let flatCitas = [];
        if (Array.isArray(data)) {
            flatCitas = data;
        } else if (data.citas) {
            flatCitas = data.citas;
        } else if (data.data) {
            flatCitas = data.data;
        } else if (typeof data === 'object') {
            // El backend devuelve { "recurso_id": { slots: [ { cita: {...} } ] } }
            for (const key in data) {
                if (data[key] && Array.isArray(data[key].slots)) {
                    data[key].slots.forEach(slot => {
                        if (slot.cita && !flatCitas.some(c => c.id === slot.cita.id)) {
                            flatCitas.push(slot.cita);
                        }
                    });
                }
            }
        }
        return flatCitas;
    } catch(e) {
        console.error(e);
        return [];
    }
}

async function ag_apiCitaEstatus(citaId, estado) {
    return await window.api('PUT', `/api/agenda/citas/${citaId}/estado`, { estado });
}

// ════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════
async function ag_init() {
  let allProfs = await ag_cargarProfesionales();
  
  // Limitar qué profesionales puede ver según rol
  if (AG_ES_PROF) {
      const miRecursoId = typeof RECURSO_ID !== 'undefined' ? RECURSO_ID : null;
      allProfs = allProfs.filter(p => p.id === miRecursoId);
  }
  ag_profs = allProfs;
  ag_profId = ag_profs[0]?.id ?? null;
  ag_citas = await ag_cargarCitasData(ag_fecha);
  
  ag_renderProfList();
  ag_renderGrid();
  ag_renderResumen();
}

// ════════════════════════════════════════════════════════
// RENDER PROFESIONALES (columna izquierda)
// ════════════════════════════════════════════════════════
function ag_renderProfList() {
  const el = document.getElementById('ag_profList');
  if (!el) return;

  if (!ag_profs.length) {
    el.innerHTML = `<div style="padding:16px;text-align:center;color:var(--t3,#4a4a60);font-size:12px;">Sin recursos configurados.</div>`;
    return;
  }

  el.innerHTML = ag_profs.map(p => {
    const n = ag_citas.filter(c => c.agenda_recurso_id == p.id).length;
    const activo = ag_profId == p.id;
    const color = p.color_hex || p.color || '#3dd9eb';
    const bg = `rgba(${parseInt(color.slice(1,3),16)},${parseInt(color.slice(3,5),16)},${parseInt(color.slice(5,7),16)},0.12)`;
    return `
      <div class="ag-prof-item ${activo ? 'active' : ''}" onclick="window.ag_selectProf(${p.id})">
        <div class="ag-prof-avatar" style="background:${bg};color:${color};">${ag_initials(p.nombre)}</div>
        <div style="min-width:0;flex:1;">
          <div class="ag-prof-name" style="${activo ? `color:${color};` : ''}">${p.nombre}</div>
          <div class="ag-prof-esp">${p.especialidad || 'Servicios'}</div>
        </div>
        <span class="ag-prof-count">${n}</span>
      </div>`;
  }).join('');
}

function ag_renderResumen() {
  const el = document.getElementById('ag_resumen');
  if (!el) return;
  const cProf = ag_citas.filter(c => c.agenda_recurso_id == ag_profId);
  const total  = cProf.length;
  const conf   = cProf.filter(c => c.estado === 'confirmada' || c.estado === 'pendiente').length;
  const curso  = cProf.filter(c => c.estado === 'en_curso').length;
  const atend  = cProf.filter(c => c.estado === 'completada').length;

  const badge = (v, bg, clr) => `<span class="ag-resumen-badge" style="background:${bg};color:${clr};">${v}</span>`;
  el.innerHTML = `
    <div style="font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--t3,#4a4a60);margin-bottom:8px;">Resumen Hoy</div>
    <div class="ag-resumen-row"><span>Total citas</span>${badge(total,'rgba(61,217,235,0.12)','#3dd9eb')}</div>
    <div class="ag-resumen-row"><span>Pendientes</span>${badge(conf,'rgba(245,197,24,0.1)','#f5c518')}</div>
    <div class="ag-resumen-row"><span>En curso</span>${badge(curso,'rgba(0,196,255,0.12)','#00c4ff')}</div>
    <div class="ag-resumen-row" style="margin-bottom:0;"><span>Completas</span>${badge(atend,'rgba(136,136,160,0.15)','#7878a0')}</div>
  `;
}

window.ag_selectProf = function(id) {
  ag_profId = id;
  ag_cerrarPanel();
  ag_renderProfList();
  ag_renderGrid();
  ag_renderResumen();
}

// ════════════════════════════════════════════════════════
// RENDER GRILLA DE HORAS (columna central)
// ════════════════════════════════════════════════════════
function ag_renderGrid() {
  const el = document.getElementById('ag_timeGrid');
  if (!el) return;

  const citasProf = ag_citas.filter(c => c.agenda_recurso_id == ag_profId);

  let notaVacia = '';
  if (!citasProf.length && AG_PUEDE_EDITAR) {
    notaVacia = `<div style="padding:6px 12px 2px;font-size:11px;color:var(--t3,#4a4a60);font-style:italic;text-align:center;">
      Sin citas — clic en cualquier hora para agendar
    </div>`;
  }

  const rows = AG_HORAS.map(h => {
    if (h === AG_COLACION) {
      return `
        <div class="ag-time-row ag-colacion">
          <div class="ag-time-label">${h}</div>
          <div class="ag-time-slot">
            🍽️ <span style="font-size:11px;color:var(--t3,#4a4a60);font-style:italic;">Colación</span>
          </div>
        </div>`;
    }

    // Buscar citas que coinciden con esta hora
    const cita = citasProf.find(c => (c.hora_inicio || '').substring(0,5) === h);

    if (cita) {
        const estKey = cita.estado || 'pendiente';
        const dc  = AG_DOT_COLOR[estKey]    || 'var(--t3,#4a4a60)';
        const lbl = AG_ESTADO_LABEL[estKey] || cita.estado;
        
        let btns = '';
        if (estKey === 'pendiente')
            btns += `<button class="ag-cita-btn" title="Confirmar" onclick="event.stopPropagation();window.ag_setEstado(${cita.id},'confirmada')">✓</button>`;
        if (estKey === 'confirmada' && AG_PUEDE_EDITAR)
            btns += `<button class="ag-cita-btn" title="Iniciar (Check-in)" style="color:#00c4ff;" onclick="event.stopPropagation();window.ag_setEstado(${cita.id},'en_curso')">➜</button>`;
        if (!['completada','cancelada'].includes(estKey) && AG_PUEDE_EDITAR)
            btns += `<button class="ag-cita-btn" title="Cancelar" style="color:#ff3f5b;" onclick="event.stopPropagation();window.ag_setEstado(${cita.id},'cancelada')">✗</button>`;
        
      return `
        <div class="ag-time-row">
          <div class="ag-time-label">${h}</div>
          <div class="ag-time-slot ag-ocupado">
            <div class="ag-cita-block ${estKey}" onclick="window.ag_abrirCita(${cita.id})">
              <div class="ag-cita-hora">
                <span class="ag-cita-dot" style="background:${dc};"></span>
                ${h} - ${(cita.hora_fin || '').substring(0,5)}
                <span class="ag-cita-tag" style="color:${dc};">${lbl}</span>
              </div>
              <div class="ag-cita-pac">${cita.paciente_nombre}</div>
              <div class="ag-cita-srv">${cita.servicio ? cita.servicio.nombre : 'Sin especificar'}</div>
              <div class="ag-cita-btns">${btns}</div>
            </div>
          </div>
        </div>`;
    }

    return `
      <div class="ag-time-row">
        <div class="ag-time-label">${h}</div>
        <div class="ag-time-slot" onclick="${AG_PUEDE_EDITAR ? `window.ag_abrirModalNuevaCita('${h}')` : ''}">
          ${AG_PUEDE_EDITAR
            ? `<div class="ag-libre-slot"><span style="font-size:16px;margin-right:6px;">+</span> Agendar a las ${h}</div>`
            : `<div style="height:58px;"></div>`}
        </div>
      </div>`;
  }).join('');

  el.innerHTML = notaVacia + rows;
}

// ════════════════════════════════════════════════════════
// PANEL DETALLE PACIENTE (columna derecha)
// ════════════════════════════════════════════════════════
window.ag_abrirCita = function(citaId) {
  const cita = ag_citas.find(c => c.id == citaId);
  if (!cita) return;
  ag_renderPanel(cita);
}

function ag_renderPanel(cita) {
  const panel = document.getElementById('ag_pacPanel');
  if (!panel) return;
  panel.classList.remove('ag-oculto');

  const prof = ag_profs.find(p => p.id == cita.agenda_recurso_id);
  const sc = AG_DOT_COLOR;
  const sl = AG_ESTADO_LABEL;
  const ini = ag_initials(cita.paciente_nombre);
  const estKey = cita.estado || 'pendiente';
  const c = sc[estKey] || 'var(--t3)'; 
  const pColor = prof?.color_hex || '#3dd9eb';

  let btns = '';
  if (estKey === 'pendiente')
      btns += `<button class="btn btn-sm-ghost" style="font-size:11px;color:#00e5a0;border:1px solid rgba(0,229,160,0.3);background:transparent;padding:6px 12px;border-radius:6px;cursor:pointer;" onclick="ag_setEstado(${cita.id},'confirmada')">Confirmar</button>`;
  if (estKey === 'confirmada')
      btns += `<button class="btn btn-sm-ghost" style="font-size:11px;color:#00c4ff;border:1px solid rgba(0,196,255,0.3);background:transparent;padding:6px 12px;border-radius:6px;cursor:pointer;" onclick="ag_setEstado(${cita.id},'en_curso')">Llegó (Check-in)</button>`;
  if (estKey === 'en_curso' && AG_ES_PROF)
      btns += `<button class="btn btn-sm-ghost" style="font-size:11px;color:var(--t3,#4a4a60);border:1px solid var(--b2,#2a2a3a);background:transparent;padding:6px 12px;border-radius:6px;cursor:pointer;" onclick="ag_setEstado(${cita.id},'completada')">Completar Consulta</button>`;
  if (!['completada','cancelada'].includes(estKey))
      btns += `<button class="btn btn-sm-ghost" style="font-size:11px;color:#ff3f5b;border:1px solid rgba(255,63,91,0.3);background:transparent;padding:6px 12px;border-radius:6px;cursor:pointer;" onclick="ag_setEstado(${cita.id},'cancelada')">Cancelar</button>`;

  const mostrarNotas = AG_ES_PROF || AG_ROL === 'admin' || AG_ROL === 'super_admin';

  panel.innerHTML = `
    <div style="padding:16px;border-bottom:1px solid var(--b1,#252530);display:flex;align-items:center;justify-content:space-between;background:var(--s1,#111115);">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:44px;height:44px;border-radius:50%;background:rgba(61,217,235,0.12);border:2px solid rgba(61,217,235,0.3);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:#3dd9eb;">${ini}</div>
        <div>
          <div style="font-size:16px;font-weight:700;color:#e8e8f0;">${cita.paciente_nombre}</div>
          <div style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:var(--t2,#8888a0);">${cita.paciente_rut ?? 'SF'} · ${cita.paciente_telefono ?? 'SF'}</div>
        </div>
      </div>
      <button onclick="window.ag_cerrarPanel()" style="background:none;border:none;color:var(--t2,#8888a0);font-size:24px;cursor:pointer;padding:0 8px;">&times;</button>
    </div>

    <div style="padding:10px 16px;border-bottom:1px solid var(--b1,#252530);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;font-family:'IBM Plex Mono',monospace;background:rgba(0,0,0,0.3);color:${c};border:1px solid ${c}40;">${sl[estKey] ?? estKey}</span>
        <span style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:var(--t2,#8888a0);">${(cita.hora_inicio||'').substring(0,5)}</span>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">${btns}</div>
    </div>

    <div style="padding:16px;overflow-y:auto;flex:1;">
      <div style="font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--t3,#4a4a60);margin-bottom:10px;">Datos Cita</div>
      <div class="ag-info-row"><span>Profesional</span><span style="color:${pColor}">${prof?.nombre ?? ''}</span></div>
      <div class="ag-info-row"><span>Servicio</span><span>${cita.servicio?.nombre ?? 'Sin servicio'}</span></div>
      <div class="ag-info-row"><span>Fecha</span><span style="font-family:'IBM Plex Mono',monospace;">${cita.fecha}</span></div>
      ${cita.notas_internas && !mostrarNotas ? `<div class="ag-info-row" style="flex-direction:column;align-items:flex-start;"><span>Obs. Recepción</span><span style="color:var(--t2,#8888a0);margin-top:4px;font-size:12px;">${cita.notas_internas}</span></div>` : ''}

      ${mostrarNotas ? `
        <div style="height:1px;background:var(--b1,#252530);margin:16px 0;"></div>
        <div style="font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--t3,#4a4a60);margin-bottom:10px;">🩺 Registro Clínico</div>
        ${cita.notas_internas ? `
        <div class="ag-nota-card">
          <div style="font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--t2,#8888a0);margin-bottom:4px;">Nota Recep. / Paciente:</div>
          <div style="font-size:11px;color:#e8e8f0;font-style:italic;">${cita.notas_internas}</div>
        </div>` : ''}
        
        <textarea id="nc_nota_prof" placeholder="Notas clínicas..." rows="4"
                  style="width:100%;background:var(--s2,#18181e);border:1.5px solid var(--b2,#2a2a3a);border-radius:8px;color:#e8e8f0;font-size:12px;padding:10px;outline:none;resize:none;margin-bottom:6px;"></textarea>
        <button style="width:100%;padding:8px;background:rgba(61,217,235,0.1);color:#3dd9eb;border:1px solid rgba(61,217,235,0.3);border-radius:8px;font-weight:600;font-size:11px;cursor:pointer;" onclick="window.ag_guardarNotaProf(${cita.cliente_id||0},${cita.id})">Guardar Nota Médica</button>
      ` : ''}
    </div>
  `;
}

window.ag_cerrarPanel = function() {
  const p = document.getElementById('ag_pacPanel');
  if (p) p.classList.add('ag-oculto');
}

// ════════════════════════════════════════════════════════
// ACCIONES
// ════════════════════════════════════════════════════════
window.ag_setEstado = async function(citaId, estado) {
  try {
    await window.api('PUT', `/api/agenda/citas/${citaId}/estado`, { estado });
    ag_citas = await ag_cargarCitasData(ag_fecha);
    ag_renderGrid();
    ag_renderProfList();
    ag_renderResumen();
    window.ag_abrirCita(citaId); // Re-render panel
    if(typeof toast === 'function') toast('Estado actualizado', 'ok');
  } catch(e) { if(typeof toast === 'function') toast(e.message, 'err'); }
}

window.ag_guardarNotaProf = async function(clienteId, citaId) {
    if(!clienteId) return;
    const txt = document.getElementById('nc_nota_prof')?.value;
    if(!txt) return;
    try {
        await window.api('POST', `/api/profesional/pacientes/${clienteId}/nota`, {
            tipo: 'anamnesis', contenido: txt, cita_id: citaId
        });
        document.getElementById('nc_nota_prof').value = '';
        if(typeof toast === 'function') toast('Nota clínica guardada', 'ok');
    } catch(e) { if(typeof toast === 'function') toast(e.message, 'err'); }
}

// ════════════════════════════════════════════════════════
// MODAL NUEVA CITA
// ════════════════════════════════════════════════════════
window.ag_abrirModalNuevaCita = function(hora) {
  const selProf = document.getElementById('ag_nc_prof');
  if (selProf) {
      selProf.innerHTML = ag_profs.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
      selProf.value = ag_profId || '';
      window.ag_fillModalServicios(ag_profId);
  }
  
  if (hora) {
      document.getElementById('ag_nc_hora').value = hora;
      window.ag_autofillHoraFin(hora);
  } else {
      document.getElementById('ag_nc_hora').value = '';
      document.getElementById('ag_nc_hora_fin').value = '';
  }
  
  document.getElementById('ag_nc_fecha').value = ag_fecha;
  document.getElementById('ag_nc_paciente').value = '';
  document.getElementById('ag_nc_rut').value = '';
  document.getElementById('ag_nc_tel').value = '';
  document.getElementById('ag_nc_obs').value = '';
  
  document.getElementById('ag_modalNuevaCita').style.display = 'flex';
}

window.ag_fillModalServicios = function(recursoId) {
    const r = ag_profs.find(x => x.id == recursoId);
    const sel = document.getElementById('ag_nc_servicio');
    if(!sel) return;
    sel.innerHTML = '<option value="">Sin servicio específico</option>' +
        (r?.servicios || []).map(s => `<option value="${s.id}">${s.nombre} (${s.duracion_min}min — $${Number(s.precio).toLocaleString()})</option>`).join('');
}

window.ag_autofillHoraFin = function(horaIni) {
    if(!horaIni) return;
    const m = horaIni.match(/^(\d{2}):(\d{2})/);
    if(m) {
        let h = parseInt(m[1]), min = parseInt(m[2]);
        min += 30; // Default gap
        if(min>=60) { min-=60; h++; }
        document.getElementById('ag_nc_hora_fin').value = `${h.toString().padStart(2,'0')}:${min.toString().padStart(2,'0')}`;
    }
}

window.ag_cerrarModalNuevaCita = function() {
  document.getElementById('ag_modalNuevaCita').style.display = 'none';
}

window.ag_guardarCita = async function() {
  const data = {
        agenda_recurso_id: document.getElementById('ag_nc_prof').value,
        agenda_servicio_id: document.getElementById('ag_nc_servicio').value || null,
        paciente_nombre: document.getElementById('ag_nc_paciente').value,
        paciente_telefono: document.getElementById('ag_nc_tel').value,
        paciente_rut: document.getElementById('ag_nc_rut').value,
        notas_internas: document.getElementById('ag_nc_obs').value,
        fecha: document.getElementById('ag_nc_fecha').value,
        hora_inicio: document.getElementById('ag_nc_hora').value,
        hora_fin: document.getElementById('ag_nc_hora_fin').value,
        estado: 'pendiente'
  };

  if (!data.agenda_recurso_id || !data.fecha || !data.hora_inicio || !data.hora_fin || !data.paciente_nombre) {
    if(typeof toast === 'function') toast('Completa paciente, recurso y horarios', 'warn');
    return;
  }

  const btn = document.getElementById('ag_btn_save');
  btn.disabled = true; btn.textContent = 'Guardando...';

  try {
    await window.api('POST', '/api/agenda/citas', data);
    ag_cerrarModalNuevaCita();
    ag_citas = await ag_cargarCitasData(ag_fecha);
    ag_renderGrid();
    ag_renderProfList();
    ag_renderResumen();
    if(typeof toast === 'function') toast('Cita guardada', 'ok');
  } catch(e) {
      if(typeof toast === 'function') toast(e.message || 'Error', 'err');
  } finally {
      btn.disabled = false; btn.textContent = '✓ Confirmar cita';
  }
}

// ════════════════════════════════════════════════════════
// NAV DATE - OVERRIDE EXISTENTES (usados en los Blades envolventes)
// ════════════════════════════════════════════════════════
window.actualizarDataGlobal = async function() {
    ag_citas = await ag_cargarCitasData(ag_fecha);
    ag_cerrarPanel();
    ag_renderGrid();
    ag_renderProfList();
    ag_renderResumen();
}
window.cambiarFecha = function(delta) {
    const d = new Date(ag_fecha + 'T12:00:00'); d.setDate(d.getDate() + delta);
    ag_fecha = d.toISOString().split('T')[0];
    const inp = document.getElementById('fechaSelector'); if(inp) inp.value = ag_fecha;
    const tit = document.getElementById('agendaTituloFecha'); if(tit) tit.textContent = ag_labelFecha(ag_fecha);
    window.actualizarDataGlobal();
}
window.irAHoy = function() {
    ag_fecha = new Date().toISOString().split('T')[0];
    const inp = document.getElementById('fechaSelector'); if(inp) inp.value = ag_fecha;
    const tit = document.getElementById('agendaTituloFecha'); if(tit) tit.textContent = ag_labelFecha(ag_fecha);
    window.actualizarDataGlobal();
}
const initFechaSelector = document.getElementById('fechaSelector');
if(initFechaSelector) {
    initFechaSelector.addEventListener('change', e => { 
        ag_fecha = e.target.value; window.actualizarDataGlobal(); 
    });
}
const tit = document.getElementById('agendaTituloFecha'); if(tit) tit.textContent = ag_labelFecha(ag_fecha);

// BOOTSTRAP
ag_init();

}); // DOMContentLoaded
</script>
@endpush
