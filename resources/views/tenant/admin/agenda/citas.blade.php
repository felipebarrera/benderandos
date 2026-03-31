@extends('tenant.layout')

@section('content')
<div style="display:flex; flex-direction:column; height: calc(100vh - 56px); width:100%; overflow: hidden;">
    {{-- Header idéntico al shell del profesional pero adaptado para admin --}}
    <div style="display:flex; align-items:center; justify-content:space-between; padding: 12px 20px; background:#111115; border-bottom:1px solid #1e1e28; flex-shrink:0;">
        <div style="font-family:'IBM Plex Mono', monospace; font-weight:700; font-size:14px; text-transform:uppercase; letter-spacing:1px; color:#e8e8f0;">
            Agenda Clínica
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <button onclick="cambiarFecha(-1)" style="padding:4px 10px; border-radius:6px; border:1px solid #252530; background:transparent; color:#7878a0; cursor:pointer;">&larr;</button>
            <button onclick="irAHoy()" style="padding:4px 12px; border-radius:6px; border:1px solid #252530; background:transparent; color:#e8e8f0; font-size:12px; font-weight:700; cursor:pointer;">Hoy</button>
            <input type="date" id="fechaSelector" style="background:#18181e; border:1px solid #2a2a3a; border-radius:6px; padding:4px 8px; color:#e8e8f0; font-size:12px; outline:none;" />
            <button onclick="cambiarFecha(1)" style="padding:4px 10px; border-radius:6px; border:1px solid #252530; background:transparent; color:#7878a0; cursor:pointer;">&rarr;</button>
            <button onclick="ag_abrirModalNuevaCita()" style="margin-left:8px; padding:6px 14px; border-radius:6px; border:none; background:#00e5a0; color:#000; font-size:12px; font-weight:700; cursor:pointer;">+ Nueva Cita</button>
        </div>
    </div>

    {{-- Grilla Fullscreen --}}
    <div style="flex:1; display:flex; min-height:0; overflow:hidden;">
        @include('tenant.partials.agenda_grilla')
    </div>
</div>
@endsection
