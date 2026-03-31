{{-- Administración --}}
@if(in_array($rol, ['admin', 'super_admin']))
<div class="nav-section-lbl">Administración</div>

{{-- Onboarding Progress Widget --}}
<div id="sidebarOnboardingWidget" onclick="openOnboarding()" style="display:none; margin:0 8px 12px; padding:10px 12px; border-radius:10px; background:#111115; border:1px solid #252530; cursor:pointer; transition: all 0.2s;">
    <div style="display:flex; align-items:center; gap:10px;">
        <div style="position:relative; width:32px; height:32px; flex-shrink:0;">
            <svg width="32" height="32" viewBox="0 0 32 32" style="transform:rotate(-90deg);">
                <circle cx="16" cy="16" r="12" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="3"/>
                <circle cx="16" cy="16" r="12" fill="none" stroke="#00e5a0" stroke-width="3"
                    stroke-dasharray="75.4" stroke-dashoffset="75.4" stroke-linecap="round"
                    id="sidebarRingCircle"/>
            </svg>
            <div id="sidebarRingText" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-family:'IBM Plex Mono',monospace; font-size:9px; font-weight:700; color:#fff;">0/7</div>
        </div>
        <div>
            <div style="font-size:11px; font-weight:600; color:#e8e8f0;">Primeros pasos</div>
            <div id="sidebarOnboardingPct" style="font-size:10px; color:#7878a0; font-family:'IBM Plex Mono',monospace;">0%</div>
        </div>
    </div>
</div>
<script>
(function() {
    function updateSidebarWidget(data) {
        const widget = document.getElementById('sidebarOnboardingWidget');
        if (!widget) return;
        if (data.onboarding_completo) { widget.style.display = 'none'; return; }
        widget.style.display = 'block';
        const circle = document.getElementById('sidebarRingCircle');
        const text = document.getElementById('sidebarRingText');
        const pct = document.getElementById('sidebarOnboardingPct');
        const ratio = data.completados / data.total;
        if (circle) circle.style.strokeDashoffset = 75.4 - (75.4 * ratio);
        if (text) text.textContent = `${data.completados}/${data.total}`;
        if (pct) pct.textContent = `${Math.round(ratio * 100)}%`;
    }
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const data = await window.api('GET', '/api/universal/onboarding/progress');
            updateSidebarWidget(data);
        } catch(e) {}
    });
})();
</script>

<a href="/admin/dashboard" class="nav-link-item {{ request()->is('admin/dashboard') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    Dashboard
</a>

@if(in_array('M03', $rubroConfig->modulos_activos))
<a href="/admin/productos" class="nav-link-item {{ request()->is('admin/productos*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    {{ $rubroConfig->label_producto ?? 'Productos' }}s
</a>
@endif

<a href="/admin/clientes" class="nav-link-item {{ request()->is('admin/clientes*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    {{ $rubroConfig->label_cliente ?? 'Cliente' }}s
</a>

@if(in_array('M18', $rubroConfig->modulos_activos))
<a href="/admin/compras" class="nav-link-item {{ request()->is('admin/compras') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    Compras
</a>
@endif

<a href="/admin/usuarios" class="nav-link-item {{ request()->is('admin/usuarios*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    Usuarios
</a>
<a href="/admin/reportes" class="nav-link-item {{ request()->is('admin/reportes*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    Reportes
</a>
<a href="/admin/config" class="nav-link-item {{ request()->is('admin/config*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
    Configuración
</a>

{{-- Agenda Config --}}
@if(in_array('M08', $rubroConfig->modulos_activos ?? []))
<a href="/admin/agenda" class="nav-link-item {{ request()->is('admin/agenda') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"/></svg>
    Config. Agenda
</a>
<a href="/admin/agenda/citas" class="nav-link-item {{ request()->is('admin/agenda/citas') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    Agenda Citas
</a>
@endif

@if(in_array(auth()->user()->rol ?? '', ['admin', 'super_admin', 'cajero', 'recepcionista']))
<a href="/recepcion" class="nav-link-item {{ request()->is('recepcion') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"/>
    </svg>
    {{ $rubroConfig->is_clinica ? 'Agenda / Recepción' : ($rubroConfig->label_cita ?? 'Reservas') }}
</a>
@endif

{{-- Módulos Adicionales --}}
@if(in_array('M17', $rubroConfig->modulos_activos))
<a href="/admin/whatsapp" class="nav-link-item {{ request()->is('admin/whatsapp*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
    WhatsApp Bot
</a>
@endif
@if(in_array('M20', $rubroConfig->modulos_activos))
<a href="/admin/sii" class="nav-link-item {{ request()->is('admin/sii*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
    Facturación SII
</a>
@endif
@endif

{{-- Operación General --}}
@if(in_array($rol, ['admin', 'super_admin', 'cajero', 'ejecutivo']))
<div class="nav-section-lbl">Operación</div>
@if(in_array('M31', $rubroConfig->modulos_activos ?? []))
<a href="/pos/saas/tenants" class="nav-link-item {{ request()->is('pos/saas/tenants') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    Tenants CRM
</a>
<a href="/pos/saas/pipeline" class="nav-link-item {{ request()->is('pos/saas/pipeline') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
    Pipeline Ventas
</a>
@endif

{{-- Agenda POS (excepto Cajero, según solicitud H26) --}}
@if(in_array('M08', $rubroConfig->modulos_activos ?? []) && $rol !== 'cajero')
<a href="/pos/agenda" class="nav-link-item {{ request()->is('pos/agenda*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    {{ $rubroConfig->is_clinica ? 'Agenda General' : ($rubroConfig->label_cita ?? 'Agenda de Ventas') }}
</a>
@endif

<a href="/pos" class="nav-link-item {{ request()->is('pos') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
    POS / Caja
</a>
<a href="/pos/historial" class="nav-link-item {{ request()->is('pos/historial') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    Historial Ventas
</a>
@endif

@php
    $modulosActivos = $rubroConfig->modulos_activos ?? [];
    $tieneM08 = in_array('M08', $modulosActivos);
    $tieneRecurso = false;

    if ($tieneM08) {
        $tieneRecurso = \App\Models\Tenant\AgendaRecurso::where('usuario_id', auth()->id())
            ->where('activo', true)->exists();
    }
@endphp

{{-- MI AGENDA (Profesional/Operario) --}}
@if($tieneM08 && $tieneRecurso)
<div class="nav-section-lbl">Mi Agenda</div>
<a href="/profesional" class="nav-link-item {{ request()->is('profesional*') ? 'nav-active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    Mi Agenda
</a>
@endif
