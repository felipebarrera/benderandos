@extends('tenant.layout')
@section('hide_sidebar', true)
@section('title', 'Mi Panel — ' . ($labelOperario ?? 'Profesional'))

@section('content')
<div class="prof-shell">
    {{-- Sidebar Nav --}}
    <nav class="prof-nav" id="profNav">
        <!-- Brand -->
        <div class="p-5 border-b" style="border-color:#1e1e28;flex-shrink:0;">
            <div style="font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:20px; color:#e8e8f0;">
                B<span style="color:var(--accent,#00e5a0)">&</span>
            </div>
            <div style="font-size:13px; font-weight:600; color:#e8e8f0; margin-top:4px;">{{ tenancy()->tenant->nombre ?? 'BenderAnd' }}</div>
            <div style="font-size:11px; color:var(--accent,#00e5a0); font-family:'IBM Plex Mono',monospace; margin-top:4px; text-transform:capitalize;">{{ auth()->user()->rol ?? 'profesional' }}</div>
        </div>

        <div class="prof-nav-top">
            @if($recurso)
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <span class="prof-dot" style="background:{{ $recurso->color_hex ?? '#00e5a0' }};"></span>
                <span class="prof-nombre">{{ $usuario->nombre }}</span>
            </div>
            <div class="prof-esp">{{ $recurso->especialidad ?? ($labelOperario ?? 'Profesional') }}</div>
            @else
            <div class="prof-nombre">{{ $usuario->nombre }}</div>
            <div class="prof-esp" style="color:#f5c518;">Sin recurso de agenda</div>
            @endif
            <div class="prof-kpis" id="kpisNav" style="margin-top: 15px;">
                <div class="kpi-item"><span class="kpi-lbl">Citas Hoy</span><span class="kpi-val" id="kpiCitasHoy">—</span></div>
                <div class="kpi-item"><span class="kpi-lbl">Esta semana</span><span class="kpi-val" id="kpiCitasSem">—</span></div>
                <div class="kpi-item"><span class="kpi-lbl">{{ $labelCliente ?? 'Pacientes' }}</span><span class="kpi-val" id="kpiPacientes">—</span></div>
            </div>
        </div>

        <div class="prof-nav-items">
            <div class="nav-section-lbl">Agenda</div>
            <a onclick="cambiarTab('agenda')" id="nav-agenda" class="pni active">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Agenda Hoy
            </a>
            <a onclick="cambiarTab('pacientes')" id="nav-pacientes" class="pni">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ $labelCliente ?? 'Pacientes' }}
            </a>
            <a onclick="cambiarTab('seguimiento')" id="nav-seguimiento" class="pni">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Seguimiento
                <span class="pni-badge" id="badgeSeguimiento" style="display:none;">0</span>
            </a>
            <a onclick="cambiarTab('perfil')" id="nav-perfil" class="pni">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                Mi Configuración
            </a>

            <div class="nav-section-lbl" style="margin-top:16px;">Operación</div>
            @if(in_array(auth()->user()->rol ?? '', ['admin', 'super_admin']))
            <a href="/admin/dashboard" class="pni">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Panel Admin
            </a>
            @else
            <a href="/pos" class="pni">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a Stock (POS)
            </a>
            @endif
        </div>

        <!-- User footer -->
        <div class="p-3 border-t" style="border-color:#1e1e28; flex-shrink:0;">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background:rgba(0,229,160,.15); color:var(--accent,#00e5a0); border:1px solid rgba(0,229,160,.3);">
                    {{ strtoupper(substr(auth()->user()->nombre ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold truncate" style="color:#e8e8f0;">{{ auth()->user()->nombre ?? 'Usuario' }}</div>
                    <div class="text-xs capitalize" style="color:#7878a0;">{{ auth()->user()->rol ?? 'profesional' }}</div>
                </div>
            </div>
            <form action="{{ route('web.logout') }}" method="POST">
                @csrf
                <button type="submit" class="pni w-full" style="justify-content:flex-start; font-size:12px; color:#7878a0; background:transparent; border:none; width:100%; border-radius:6px; cursor:pointer;">
                    ⏻ Cerrar sesión
                </button>
            </form>
        </div>
    </nav>

    {{-- Cuerpo: panel central + panel detalle --}}
    <div class="prof-body pb-0 pl-0 pr-0 pt-0" style="padding:0; background:var(--bg,#08080a);">
        <div class="prof-center">
            <div id="tab-agenda" class="tab-panel flex flex-col h-full !p-0">
                <div class="prof-topbar" style="border-bottom:none; height:54px; display:flex; align-items:center; padding:0 20px; flex-shrink:0;">
                    <button onclick="cambiarFecha(-1)" class="btn-sm-ghost" style="padding:5px 10px;">←</button>
                    <span class="prof-titulo" style="flex:1;text-align:center;" id="agendaTituloFecha">...</span>
                    <button onclick="cambiarFecha(1)" class="btn-sm-ghost" style="padding:5px 10px;">→</button>
                    <button onclick="irAHoy()" class="btn-sm-ghost" style="font-size:10px;">Hoy</button>
                    @if(in_array(auth()->user()->rol ?? 'operario', ['admin', 'super_admin', 'recepcionista', 'cajero', 'medico', 'profesional', 'operario']))
                        <button onclick="ag_abrirModalNuevaCita()" style="padding:6px 14px;border-radius:8px;border:none;background:#00e5a0;color:#000;font-size:12px;font-weight:700;cursor:pointer;margin-left:8px;">+ Nueva cita</button>
                    @endif
                    <input type="date" id="fechaSelector" class="hidden" style="display:none;" />
                </div>
                <div style="flex:1; display:flex; min-height:0; width:100%; overflow:hidden;">
                    @include('tenant.partials.agenda_grilla')
                </div>
            </div>
            <div id="tab-pacientes"    class="tab-panel" style="display:none;"><div class="prof-topbar"><span class="prof-titulo">{{ $labelCliente ?? 'Pacientes' }}</span></div><div class="prof-content" id="listaPacientes"></div></div>
            <div id="tab-seguimiento"  class="tab-panel" style="display:none;"><div class="prof-topbar"><span class="prof-titulo">Seguimiento</span></div><div class="prof-content" id="listaSeguimiento"></div></div>
            <div id="tab-perfil"       class="tab-panel" style="display:none;"><div class="prof-topbar"><span class="prof-titulo">Mi Configuración</span></div><div class="prof-content" id="perfilContent"></div></div>
        </div>
        <div class="prof-detalle cerrado" id="profDetalle"></div>
    </div>
</div>

<style>
/* ══════════════════════════════════════════════════════
   SHELL PROFESIONAL
══════════════════════════════════════════════════════ */
.prof-shell {
    display: flex; height: calc(100vh - 56px); background: #08080a; overflow: hidden;
    position: fixed; top: 56px; left: 0; right: 0; bottom: 0; /* Asegurar que ocupe todo */
}
.prof-nav {
    width: 240px; min-width: 240px; background: #0d0d11; border-right: 1px solid #1e1e28;
    display: flex; flex-direction: column; flex-shrink: 0; height: 100%;
}
@media (max-width: 767px) {
    .prof-nav { display: none; }
    .prof-shell { position: relative; top: 0; height: calc(100vh - 56px); }
}
.prof-nav-top { padding: 16px 14px 12px; border-bottom: 1px solid #1e1e28; flex-shrink: 0; }
.prof-nombre { font-size: 13px; font-weight: 700; color: #e8e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.prof-esp { font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #7878a0; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
.prof-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; flex-shrink: 0; }
.prof-nav-items { padding: 8px 0; flex: 1; overflow-y: auto; min-height: 0; }
.nav-section-lbl {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 9px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: #3a3a55; padding: 0 14px; margin: 12px 0 4px;
}
.pni {
    display: flex; align-items: center; gap: 10px; padding: 9px 14px; font-size: 12px; font-weight: 500;
    color: #7878a0; cursor: pointer; transition: all .12s; text-decoration: none; position: relative; margin: 0 4px; border-radius: 6px;
}
.pni:hover { background: #18181e; color: #e8e8f0; }
.pni.active {
    background: linear-gradient(90deg, rgba(0,229,160,.15) 0%, transparent 100%);
    border-left: 3px solid #00e5a0; color: #00e5a0;
    border-radius: 0 6px 6px 0; margin-left: 0; padding-left: 17px;
}
.pni svg { width: 16px; height: 16px; flex-shrink: 0; }
.pni-badge {
    margin-left: auto; background: #ff3f5b; color: #fff; font-family: 'IBM Plex Mono', monospace;
    font-size: 9px; font-weight: 700; padding: 1px 5px; border-radius: 8px; min-width: 16px; text-align: center;
}
.kpi-item { display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; }
.kpi-lbl { color: #3a3a55; }
.kpi-val { font-family: 'IBM Plex Mono', monospace; font-weight: 700; color: #00e5a0; }

.prof-body { flex: 1; display: flex; overflow: hidden; }
.prof-center { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }
.kpi-item { display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; }
.kpi-lbl { color: #3a3a55; }
.kpi-val { font-family: 'IBM Plex Mono', monospace; font-weight: 700; color: #00e5a0; }
.prof-topbar {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px; background: #111115;
    border-bottom: 1px solid #1e1e28; flex-shrink: 0; flex-wrap: wrap;
}
.prof-titulo { font-family: 'IBM Plex Mono', monospace; font-weight: 700; font-size: 13px; color: #e8e8f0; letter-spacing: 0.5px; }
.prof-content { flex: 1; overflow-y: auto; padding: 16px; }

.prof-detalle { width: 320px; min-width: 320px; background: #111115; border-left: 1px solid #1e1e28; display: flex; flex-direction: column; transition: all .2s; }
.prof-detalle.cerrado { display: none; }

.det-head { padding: 12px 14px; border-bottom: 1px solid #1e1e28; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.det-head-titulo { font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #7878a0; }
.det-body { flex: 1; overflow-y: auto; }

/* Override de grilla calc() para que use 100% de la altura restante */
.ag-wrap { height: 100% !important; border-top: 1px solid #252530; }
.det-foot { padding: 12px 14px; border-top: 1px solid #1e1e28; flex-shrink: 0; }

/* ══════════════════════════════════════════════════════
   AGENDA / CITAS
══════════════════════════════════════════════════════ */
.timeline-wrap { display: flex; flex-direction: column; gap: 4px; }
.cita-row {
    display: flex; align-items: stretch; gap: 8px; padding: 10px 12px; border-radius: 10px;
    background: #111115; border: 1px solid #1e1e28; cursor: pointer; transition: all .15s;
}
.cita-row:hover { border-color: #2a2a3a; background: #141418; }
.cita-row.activa { border-color: rgba(0,229,160,.35); background: rgba(0,229,160,.04); }

.hora-col { font-family: 'IBM Plex Mono', monospace; font-size: 11px; font-weight: 700; color: #7878a0; width: 46px; flex-shrink: 0; padding-top: 2px; text-align: right; }
.hora-col.en_curso { color: #00c4ff; }
.barra-estado { width: 3px; border-radius: 3px; flex-shrink: 0; align-self: stretch; min-height: 40px; }
.cita-info { flex: 1; min-width: 0; }
.ci-nombre { font-size: 13px; font-weight: 600; color: #e8e8f0; margin-bottom: 2px; }
.ci-rut { font-size: 10px; color: #7878a0; font-family: 'IBM Plex Mono', monospace; }
.ci-srv { font-size: 11px; color: #7878a0; margin-top: 2px; }
.ci-notas { font-size: 10px; color: #4a4a60; margin-top: 3px; font-style: italic; }

.estado-badge { font-family: 'IBM Plex Mono', monospace; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 2px 7px; border-radius: 4px; align-self: flex-start; flex-shrink: 0; }
.sb-pendiente { background:rgba(245,197,24,.12); color:#f5c518; border:1px solid rgba(245,197,24,.2); }
.sb-confirmada { background:rgba(0,229,160,.1); color:#00e5a0; border:1px solid rgba(0,229,160,.2); }
.sb-en_curso { background:rgba(0,196,255,.12); color:#00c4ff; border:1px solid rgba(0,196,255,.2); }
.sb-completada { background:rgba(136,136,160,.1); color:#8888a0; border:1px solid rgba(136,136,160,.2); }
.sb-cancelada { background:rgba(255,63,91,.08); color:#ff3f5b; border:1px solid rgba(255,63,91,.15); opacity:.6; }

/* ══════════════════════════════════════════════════════
   PACIENTES / SEGUIMIENTO
══════════════════════════════════════════════════════ */
.pac-row { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 10px; background: #111115; border: 1px solid #1e1e28; cursor: pointer; transition: all .15s; margin-bottom: 6px; }
.pac-row:hover { border-color: #2a2a3a; }
.pac-row.sel { border-color: rgba(0,229,160,.35); background: rgba(0,229,160,.04); }
.pac-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; flex-shrink: 0; }
.pac-nombre { font-size: 13px; font-weight: 600; color: #e8e8f0; }
.pac-meta { font-size: 11px; color: #7878a0; margin-top: 1px; }

.chip { font-family: 'IBM Plex Mono', monospace; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 1px 6px; border-radius: 4px; }
.chip-ok { background:rgba(0,229,160,.1); color:#00e5a0; border:1px solid rgba(0,229,160,.2); }
.chip-warn { background:rgba(245,197,24,.1); color:#f5c518; border:1px solid rgba(245,197,24,.2); }
.chip-err { background:rgba(255,63,91,.08); color:#ff3f5b; border:1px solid rgba(255,63,91,.15); }
.chip-info { background:rgba(0,196,255,.08); color:#00c4ff; border:1px solid rgba(0,196,255,.15); }
.chip-muted { background:rgba(136,136,160,.1); color:#8888a0; border:1px solid rgba(136,136,160,.2); }

.timeline-hist { position: relative; padding-left: 20px; }
.timeline-hist::before { content: ''; position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: #1e1e28; }
.th-item { position: relative; margin-bottom: 14px; }
.th-dot { position: absolute; left: -17px; top: 3px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid #0d0d11; }
.th-fecha { font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #3a3a55; margin-bottom: 4px; }
.th-card { background: #111115; border: 1px solid #1e1e28; border-radius: 8px; padding: 10px 12px; font-size: 12px; }
.th-texto { color: #e8e8f0; line-height: 1.5; }

.seg-item { background: #111115; border: 1px solid #1e1e28; border-radius: 10px; padding: 12px 14px; margin-bottom: 8px; cursor: pointer; transition: all .15s; }
.seg-item.urgente { border-color: rgba(255,63,91,.3); }

/* Form inputs */
.nf-label { font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #3a3a55; margin-bottom: 5px; display: block; }
.nf-input, .nf-select, .nf-textarea {
    background: #18181e; border: 1.5px solid #2a2a3a; border-radius: 8px; color: #e8e8f0;
    font-size: 12px; padding: 7px 10px; outline: none; width: 100%;
}
.btn-agregar { background: #00e5a0; color: #000; border: none; border-radius: 8px; padding: 8px 18px; font-size: 12px; font-weight: 700; cursor: pointer; }
.btn-sm-ghost { background: #18181e; border: 1px solid #2a2a3a; border-radius: 7px; color: #7878a0; font-size: 11px; padding: 5px 10px; cursor: pointer; }
</style>

@push('scripts')
<script>
const RECURSO_ID    = {{ $recurso?->id ?? 'null' }};
const LABEL_CLIENTE = '{{ $labelCliente ?? "Paciente" }}';

let fechaAgenda = new Date();
let pacienteActualId = null;
let tabActual = 'agenda';
let filtroSegPendiente = false;

// ══════════════════════════════════════════════════════
// NAVIGATION & DASHBOARD
// ══════════════════════════════════════════════════════
function cambiarTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.pni').forEach(n => n.classList.remove('active'));
    document.getElementById(`tab-${tab}`).style.display = 'flex';
    document.getElementById(`tab-${tab}`).style.flexDirection = 'column';
    document.getElementById(`nav-${tab}`).classList.add('active');
    tabActual = tab;

    if (tab === 'agenda') {
        if (typeof ag_init === 'function') ag_init();
    }
    if (tab === 'pacientes')   cargarPacientes();
    if (tab === 'seguimiento') cargarSeguimientosPendientes();
    if (tab === 'perfil')      cargarPerfil();
}

async function cargarKpis() {
    try {
        // const data = await api('GET', '/api/profesional/estadisticas');
        // if (!data) return;
        // document.getElementById('kpiCitasHoy').textContent = data.citas_hoy ?? '0';
        // document.getElementById('kpiCitasSem').textContent = data.citas_semana ?? '0';
        // document.getElementById('kpiPacientes').textContent = data.pacientes_totales ?? '0';
        // const badge = document.getElementById('badgeSeguimiento');
        // if (data.seguimientos_pendientes > 0) {
        //     badge.textContent = data.seguimientos_pendientes;
        //     badge.style.display = 'inline';
        // }
    } catch(e) { console.error(e); }
}

// ══════════════════════════════════════════════════════
// AGENDA (Lógica movida a partials.agenda_grilla)
// ══════════════════════════════════════════════════════

// ══════════════════════════════════════════════════════
// PACIENTES
// ══════════════════════════════════════════════════════
async function cargarPacientes(q = '') {
    const el = document.getElementById('listaPacientes');
    try {
        let url = '/api/profesional/pacientes' + (q ? `?q=${encodeURIComponent(q)}` : '');
        const data = await api('GET', url);
        el.innerHTML = data.map(p => `
            <div class="pac-row" onclick="abrirDetallePaciente(${p.id})">
                <div class="pac-avatar">${p.nombre[0]}</div>
                <div style="flex:1;">
                    <div class="pac-nombre">${p.nombre}</div>
                    <div class="pac-meta">${p.rut ?? ''}</div>
                </div>
            </div>`).join('') || '<div style="text-align:center;padding:20px;">Sin pacientes.</div>';
    } catch(e) { el.innerHTML = `<div style="color:#ff3f5b;">${e.message}</div>`; }
}

async function abrirDetallePaciente(id) {
    pacienteActualId = id;
    document.getElementById('profDetalle').classList.remove('cerrado');
    try {
        const p = await api('GET', `/api/profesional/pacientes/${id}`);
        const hist = await api('GET', `/api/profesional/pacientes/${id}/historial`);
        document.getElementById('profDetalle').innerHTML = `
            <div class="det-head"><span class="det-head-titulo">${p.paciente.nombre}</span><button onclick="cerrarDetalle()" class="btn-sm-ghost">✕</button></div>
            <div class="det-body" style="padding:14px;">
                <div style="margin-bottom:14px;">
                    <div class="nf-label">Contenido Nota</div>
                    <textarea id="notaContenido" class="nf-textarea"></textarea>
                    <button onclick="guardarNota()" class="btn-agregar" style="margin-top:8px;">Guardar Nota</button>
                </div>
                <div class="timeline-hist">${hist.map(h => `<div class="th-item"><div class="th-fecha">${h.fecha}</div><div class="th-card">${h.contenido ?? h.servicio ?? 'Cita'}</div></div>`).join('')}</div>
            </div>`;
    } catch(e) { console.error(e); }
}

function cerrarDetalle() { document.getElementById('profDetalle').classList.add('cerrado'); }

async function guardarNota() {
    const txt = document.getElementById('notaContenido').value;
    if(!txt) return;
    try {
        await api('POST', `/api/profesional/pacientes/${pacienteActualId}/nota`, { tipo:'nota_clinica', contenido:txt });
        document.getElementById('notaContenido').value = '';
        abrirDetallePaciente(pacienteActualId);
    } catch(e) { alert(e.message); }
}

// ══════════════════════════════════════════════════════
// SEGUIMIENTO & PERFIL
// ══════════════════════════════════════════════════════
async function cargarSeguimientosPendientes() {
    const el = document.getElementById('listaSeguimiento');
    try {
        const pacs = await api('GET', '/api/profesional/pacientes?pendiente_seguimiento=1');
        el.innerHTML = pacs.map(p => `<div class="seg-item" onclick="abrirDetallePaciente(${p.id})">${p.nombre} (${p.seguimiento_pendiente})</div>`).join('') || '<div style="padding:20px;">Sin pendientes.</div>';
    } catch(e) { el.innerHTML = e.message; }
}

async function cargarPerfil() {
    const el = document.getElementById('perfilContent');
    try {
        const r = await api('GET', '/api/agenda/mi/recurso');
        el.innerHTML = `<div><div class="nf-label">Especialidad</div><div style="color:#e8e8f0;margin-bottom:12px;">${r.especialidad ?? 'No definida'}</div></div>`;
    } catch(e) { el.innerHTML = e.message; }
}

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    cargarKpis();
    if (typeof ag_init === 'function') ag_init();
});
</script>
@endpush
@endsection

