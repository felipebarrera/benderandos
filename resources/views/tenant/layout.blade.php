<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'BenderAnd POS' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/benderand.css">
    <style>
        body { font-family: 'IBM Plex Sans', sans-serif; }
        :root {
            --accent: #00e5a0;
            --accent2: #00b87a;
        }
        .nav-active {
            background: linear-gradient(90deg, rgba(0,229,160,0.15) 0%, rgba(0,229,160,0) 100%);
            border-left: 3px solid var(--accent);
            color: var(--accent) !important;
        }
        .nav-link-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px;
            font-size: 13px; font-weight: 500; color: #7878a0;
            cursor: pointer; transition: all .12s; text-decoration: none;
        }
        .nav-link-item:hover { background: #18181e; color: #e8e8f0; }
        .nav-link-item svg { width: 16px; height: 16px; flex-shrink: 0; }
        .nav-section-lbl {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 9px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            color: #3a3a55; padding: 0 12px; margin: 12px 0 4px;
        }
        /* Mobile bottom nav */
        .mobile-nav {
            position: fixed; bottom: 0; left: 0; right: 0;
            height: 60px; background: #111115;
            border-top: 1px solid #1e1e28;
            display: flex; align-items: center;
            z-index: 50; padding: 0 8px;
        }
        @media (min-width: 768px) { .mobile-nav { display: none; } }
        .mobile-nav-item {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 3px;
            font-size: 10px; font-weight: 500; color: #7878a0;
            text-decoration: none; transition: color .12s;
        }
        .mobile-nav-item.active { color: var(--accent); }
        .mobile-nav-item svg { width: 20px; height: 20px; }
        /* Main content padding for mobile nav */
        @media (max-width: 767px) { .main-content { padding-bottom: 72px !important; } }
        /* Alert banners */
        .alert-suspended { background: #fee2e2; color: #b91c1c; padding: 10px 16px; font-size: 13px; font-weight: 600; border-bottom: 1px solid #f87171; text-align: center; }
        .alert-gracia { background: #fef08a; color: #854d0e; padding: 10px 16px; font-size: 13px; font-weight: 500; border-bottom: 1px solid #fde047; text-align: center; }
        /* Rubro badge */
        .rubro-badge { font-size: 11px; color: var(--accent); font-weight: 600; font-family: 'IBM Plex Mono', monospace; }
        
        /* Onboarding Styles */
        .progress-badge { transition: all 0.2s; }
        .progress-badge:hover { background: rgba(255,255,255,0.05); }
    </style>
    @stack('head')
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen flex flex-col" style="background:#08080a; color:#e8e8f0;">

@php
    $tenantModel = tenancy()->tenant;
    $subs = $tenantModel ? $tenantModel->subscription : null;
    $suspendido = $subs && !$subs->puedeOperar();
    $gracia = $subs && $subs->estado === 'gracia';
    $rol = auth()->user()->rol ?? 'cajero';
@endphp

@if($suspendido)
<div class="alert-suspended">
    ⚠️ Tu suscripción se encuentra suspendida o vencida. Por favor regulariza tu pago.
    @if($subs->linkPago())
        <a href="{{ $subs->linkPago() }}" target="_blank" style="margin-left:8px; text-decoration:underline;">Pagar Ahora</a>
    @endif
</div>
@elseif($gracia)
<div class="alert-gracia">
    ⚠️ Período de gracia ({{ $subs->diasGraciaRestantes() }} días restantes).
    @if($subs->linkPago())
        <a href="{{ $subs->linkPago() }}" target="_blank" style="margin-left:8px; text-decoration:underline;">Pagar Ahora</a>
    @endif
</div>
@endif

<!-- Mobile topbar -->
<header class="flex items-center justify-between px-4 md:hidden" style="height:52px; background:#111115; border-bottom:1px solid #1e1e28; flex-shrink:0;">
    <div class="flex items-center gap-3">
        @if(in_array($rol, ['admin', 'super_admin']))
        <div class="progress-badge flex items-center gap-2 cursor-pointer" onclick="openOnboarding()">
            <div class="progress-ring relative w-7 h-7">
                <svg width="28" height="28" viewBox="0 0 28 28" style="transform: rotate(-90deg);">
                    <circle cx="14" cy="14" r="11" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="3"/>
                    <circle cx="14" cy="14" r="11" fill="none" stroke="var(--accent)" stroke-width="3"
                        stroke-dasharray="69.1" stroke-dashoffset="69.1" stroke-linecap="round"
                        id="navRingCircleMobile"/>
                </svg>
            </div>
        </div>
        @endif
        <div class="nav-avatar w-8 h-8 rounded-full bg-[#18181e] border border-[#2a2a3a] flex items-center justify-center text-xs font-bold text-[#00e5a0]">
            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
        </div>
    </div>
</header>

<!-- Main layout -->
<div class="flex flex-1 overflow-hidden">

    <!-- Sidebar overlay (mobile) -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/60 hidden md:hidden"></div>

    <!-- Sidebar -->
    @unless(View::hasSection('hide_sidebar'))
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col transform -translate-x-full transition-transform duration-200 md:relative md:translate-x-0 md:flex" style="background:#111115; border-right:1px solid #1e1e28; width:240px; min-width:240px; flex-shrink:0;">

        <!-- Brand -->
        <div class="p-5 border-b" style="border-color:#1e1e28;">
            <div style="font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:20px;">
                B<span style="color:var(--accent)">&</span>
            </div>
            <div style="font-size:13px; font-weight:600; color:#e8e8f0; margin-top:4px;">{{ tenancy()->tenant->nombre ?? 'BenderAnd' }}</div>
            <div style="font-size:11px; color:var(--accent); font-family:'IBM Plex Mono',monospace; margin-top:4px; text-transform:capitalize;">{{ $rol }}</div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto py-3" style="scrollbar-width:none;">
            @include('tenant.partials.sidebar_admin')
        </nav>

        <!-- User footer -->
        @include('tenant.partials.sidebar_footer')
    </aside>
    @endunless

    <!-- Main content -->
    <!-- Main content container -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Desktop Header -->
        <header class="hidden md:flex items-center justify-between px-6 bg-[#111115] border-b border-[#1e1e28] flex-shrink-0" style="height:54px;">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-gray-400">Panel de Control <span class="mx-2 text-gray-700">/</span> <span class="text-gray-200">{{ $pageTitle ?? 'Inicio' }}</span></span>
            </div>
            <div class="flex items-center gap-6">
                @if(in_array($rol, ['admin', 'super_admin']))
                <div class="progress-badge flex items-center gap-3 cursor-pointer p-1.5 px-3 rounded-full border border-transparent hover:border-[#2a2a3a]" onclick="openOnboarding()">
                    <div class="progress-ring relative w-7 h-7">
                        <svg width="28" height="28" viewBox="0 0 28 28" style="transform: rotate(-90deg);">
                            <circle cx="14" cy="14" r="11" fill="none" stroke="rgba(255,255,255,0.12)" stroke-width="3"/>
                            <circle cx="14" cy="14" r="11" fill="none" stroke="var(--accent)" stroke-width="3"
                                stroke-dasharray="69.1" stroke-dashoffset="69.1" stroke-linecap="round"
                                id="navRingCircle"/>
                        </svg>
                        <div class="progress-ring-text absolute inset-0 flex items-center justify-center font-mono text-[9px] font-bold text-white" id="navRingText">0/7</div>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 leading-none">Configuración</span>
                        <span class="text-xs font-semibold text-[#00e5a0] mt-0.5" id="navPercent">0% Completo</span>
                    </div>
                </div>
                @endif
                <div class="flex items-center gap-3 pl-4 border-l border-[#1e1e28]">
                    <div class="text-right">
                        <div class="text-xs font-bold text-gray-200">{{ auth()->user()->name ?? 'Usuario' }}</div>
                        <div class="text-[10px] text-gray-500 uppercase tracking-tighter">{{ $rol }}</div>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#18181e] to-[#111115] border border-[#2a2a3a] flex items-center justify-center text-sm font-bold text-[#00e5a0]">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto main-content" style="min-width:0;">
            @yield('content')
        </main>
    </div>

</div>

<!-- Mobile bottom nav -->
<nav class="mobile-nav">
    @if(in_array($rol, ['cajero', 'admin', 'super_admin']))
    @if(in_array('M08', $rubroConfig->modulos_activos ?? []))
    <a href="/pos/agenda" class="mobile-nav-item {{ request()->is('pos/agenda*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Agenda
    </a>
    @endif
    <a href="/recepcion-directa" class="mobile-nav-item {{ request()->is('recepcion-directa*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
        Recibir
    </a>
    <a href="/pos" class="mobile-nav-item {{ request()->is('pos') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        POS
    </a>
    @endif
    @if(in_array($rol, ['admin', 'super_admin']))
    <a href="/admin/dashboard" class="mobile-nav-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Admin
    </a>
    <a href="/admin/clientes" class="mobile-nav-item {{ request()->is('admin/clientes*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Clientes
    </a>
    @endif
    @if(in_array($rol, ['operario', 'bodega']))
    @if(isset($tieneRecurso) && $tieneRecurso)
    <a href="/profesional" class="mobile-nav-item {{ request()->is('profesional*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Mi Agenda
    </a>
    @endif
    <a href="/recepcion-directa" class="mobile-nav-item {{ request()->is('recepcion-directa*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
        Recibir
    </a>
    <a href="/operario" class="mobile-nav-item {{ request()->is('operario*') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        Stock
    </a>
    @endif
    <a href="/pos/historial" class="mobile-nav-item {{ request()->is('pos/historial') ? 'active' : '' }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Historial
    </a>
</nav>

<script>
window.AppConfig = { rol: '{{ auth()->user()->rol ?? "" }}' };

// Mobile sidebar toggle
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');

hamburger?.addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
});
overlay?.addEventListener('click', () => {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
});
</script>
<script src="/js/benderand.js"></script>
<script src="/js/benderand-debug.js"></script>
@stack('scripts')
@include('tenant.partials.onboarding_drawer')
</body>
</html>
