@extends('tenant.layout')
@section('hide_sidebar', true)
@section('title', 'Recepción — Agenda')

@section('content')
<div class="prof-shell">
    {{-- Sidebar Nav --}}
    <nav class="prof-nav" id="profNav">
        <div class="p-5 border-b" style="border-color:#1e1e28;flex-shrink:0;">
            <div style="font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:20px; color:#e8e8f0;">
                B<span style="color:var(--accent,#00e5a0)">&</span>
            </div>
            <div style="font-size:13px; font-weight:600; color:#e8e8f0; margin-top:4px;">{{ tenancy()->tenant->nombre ?? 'BenderAnd' }}</div>
            <div style="font-size:11px; color:#f5c518; font-family:'IBM Plex Mono',monospace; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">Recepción</div>
        </div>

        <div class="prof-nav-items" style="padding-top:16px;">
            <div class="nav-section-lbl" style="margin-top:16px;">Navegación</div>
            @if(in_array(auth()->user()->rol ?? '', ['admin', 'super_admin']))
            <a href="/admin/dashboard" class="pni">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Panel Admin
            </a>
            @endif
            <a href="/pos" class="pni">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                POS / Caja
            </a>
        </div>

        <div class="p-3 border-t" style="border-color:#1e1e28; flex-shrink:0;">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background:rgba(245,197,24,.15); color:#f5c518; border:1px solid rgba(245,197,24,.3);">
                    {{ strtoupper(substr(auth()->user()->nombre ?? 'R', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold truncate" style="color:#e8e8f0;">{{ auth()->user()->nombre ?? 'Usuario' }}</div>
                    <div class="text-xs capitalize" style="color:#7878a0;">{{ auth()->user()->rol ?? 'recepcionista' }}</div>
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

    {{-- Main body --}}
    <div class="prof-body pb-0 pl-0 pr-0 pt-0" style="padding:0; flex-direction:column; background:var(--bg,#08080a);">
        <div class="prof-topbar" style="border-bottom:none; height:54px; display:flex; align-items:center; padding:0 20px; flex-shrink:0;">
            <button onclick="cambiarFecha(-1)" class="btn-sm-ghost" style="padding:5px 10px;">←</button>
            <span class="prof-titulo" style="flex:1;text-align:center;" id="agendaTituloFecha">...</span>
            <button onclick="cambiarFecha(1)" class="btn-sm-ghost" style="padding:5px 10px;">→</button>
            <button onclick="irAHoy()" class="btn-sm-ghost" style="font-size:10px;">Hoy</button>
            <button onclick="ag_abrirModalNuevaCita()" style="padding:6px 14px;border-radius:8px;border:none;background:#00e5a0;color:#000;font-size:12px;font-weight:700;cursor:pointer;margin-left:8px;">+ Nueva cita</button>
            <input type="date" id="fechaSelector" class="hidden" style="display:none;" />
        </div>
        
        <div style="flex:1; display:flex; min-height:0; width:100%; overflow:hidden;">
            @include('tenant.partials.agenda_grilla')
        </div>
    </div>
</div>

<style>
.prof-shell { display: flex; height: calc(100vh - 56px); background: #08080a; overflow: hidden; position: fixed; top: 56px; left: 0; right: 0; bottom: 0; }
.prof-nav { width: 240px; min-width: 240px; background: #0d0d11; border-right: 1px solid #1e1e28; display: flex; flex-direction: column; flex-shrink: 0; height: 100%; }
@media (max-width: 767px) { .prof-nav { display: none; } .prof-shell { position: relative; top: 0; height: calc(100vh - 56px); } }
.prof-nav-items { padding: 8px 0; flex: 1; overflow-y: auto; min-height: 0; }
.nav-section-lbl { font-family: 'IBM Plex Mono', monospace; font-size: 9px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #3a3a55; padding: 0 14px; margin: 12px 0 4px; }
.pni { display: flex; align-items: center; gap: 10px; padding: 9px 14px; font-size: 12px; font-weight: 500; color: #7878a0; cursor: pointer; transition: all .12s; text-decoration: none; position: relative; margin: 0 4px; border-radius: 6px; }
.pni:hover { background: #18181e; color: #e8e8f0; }
.pni svg { width: 16px; height: 16px; flex-shrink: 0; }
.prof-body { flex: 1; display: flex; overflow: hidden; }
.prof-topbar { display: flex; align-items: center; gap: 10px; background: #111115; border-bottom: 1px solid #1e1e28; flex-shrink: 0; flex-wrap: wrap; }
.prof-titulo { font-family: 'IBM Plex Mono', monospace; font-weight: 700; font-size: 13px; color: #e8e8f0; letter-spacing: 0.5px; }
.btn-sm-ghost { background: #18181e; border: 1px solid #2a2a3a; border-radius: 7px; color: #7878a0; font-size: 11px; padding: 5px 10px; cursor: pointer; }

/* Override de grilla calc() para que use 100% de la altura restante */
.ag-wrap { height: 100% !important; border-top: 1px solid #252530; }
</style>
@endpush
@endsection
