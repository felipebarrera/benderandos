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
        <button id="hamburger" class="p-1.5 rounded-lg" style="background:#18181e; border:1px solid #2a2a3a; color:#7878a0;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span style="font-family:'IBM Plex Mono',monospace; font-weight:700; font-size:17px;">B<span style="color:var(--accent)">&</span></span>
        <span class="rubro-badge">{{ $rubroConfig->industria_nombre ?? 'POS' }}</span>
    </div>
    <span style="font-size:11px; color:#7878a0; text-transform:capitalize;">{{ $rol }}</span>
</header>

<!-- Main layout -->
<div class="flex flex-1 overflow-hidden">

    <!-- Sidebar overlay (mobile) -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black/60 hidden md:hidden"></div>

    <!-- Sidebar -->
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

            @if(in_array($rol, ['admin', 'super_admin']))
            <div class="nav-section-lbl">Administración</div>

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
            @if(in_array('M18', $rubroConfig->modulos_activos) || in_array('M19', $rubroConfig->modulos_activos))
            <a href="/admin/compras-avanzadas" class="nav-link-item {{ request()->is('admin/compras-avanzadas*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                Compras Avanzadas
            </a>
            @endif
            @if(in_array('M13', $rubroConfig->modulos_activos))
            <a href="/admin/delivery" class="nav-link-item {{ request()->is('admin/delivery*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                Delivery
            </a>
            @endif
            @if(in_array('M16', $rubroConfig->modulos_activos))
            <a href="/admin/recetas" class="nav-link-item {{ request()->is('admin/recetas*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                Recetas
            </a>
            @endif
            @if(in_array('M21', $rubroConfig->modulos_activos) || in_array('M22', $rubroConfig->modulos_activos))
            <a href="/admin/rrhh" class="nav-link-item {{ request()->is('admin/rrhh*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                RRHH
            </a>
            @endif
            @if(in_array('M23', $rubroConfig->modulos_activos))
            <a href="/admin/reclutamiento" class="nav-link-item {{ request()->is('admin/reclutamiento*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Reclutamiento
            </a>
            @endif
            @if(in_array('M24', $rubroConfig->modulos_activos))
            <a href="/admin/marketing" class="nav-link-item {{ request()->is('admin/marketing*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                Marketing QR
            </a>
            @endif
            @if(in_array('M31', $rubroConfig->modulos_activos))
            <a href="/admin/saas/dashboard" class="nav-link-item {{ request()->is('admin/saas*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                SaaS Central
            </a>
            @endif
            @endif

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
            <a href="/pos" class="nav-link-item {{ request()->is('pos') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                POS / Caja
            </a>
            <a href="/pos/historial" class="nav-link-item {{ request()->is('pos/historial') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Historial Ventas
            </a>
            @endif

            @if(in_array($rol, ['operario', 'bodega']))
            <div class="nav-section-lbl">Mi Panel</div>
            <a href="/operario" class="nav-link-item {{ request()->is('operario') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Stock &amp; Ventas
            </a>
            @endif

            @if(in_array('M05', $rubroConfig->modulos_activos) || in_array('M06', $rubroConfig->modulos_activos) || in_array('M14', $rubroConfig->modulos_activos))
            <div class="nav-section-lbl">Recursos</div>
            <a href="/rentas" class="nav-link-item {{ request()->is('rentas*') ? 'nav-active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Panel {{ $rubroConfig->label_recurso ?? 'Recurso' }}s
            </a>
            @endif

        </nav>

        <!-- User footer -->
        <div class="p-3 border-t" style="border-color:#1e1e28;">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background:rgba(0,229,160,.15); color:var(--accent); border:1px solid rgba(0,229,160,.3);">
                    {{ strtoupper(substr(auth()->user()->nombre ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold truncate">{{ auth()->user()->nombre ?? 'Usuario' }}</div>
                    <div class="text-xs capitalize" style="color:#7878a0;">{{ $rol }}</div>
                </div>
            </div>
            <form action="{{ route('web.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost w-full" style="justify-content:flex-start; font-size:12px; color:var(--t2);">
                    ⏻ Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto main-content" style="min-width:0;">
        @yield('content')
    </main>

</div>

<!-- Mobile bottom nav -->
<nav class="mobile-nav">
    @if(in_array($rol, ['cajero', 'admin', 'super_admin']))
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
</body>
</html>
