<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'BenderAnd' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --bg: #0b0b0f;
            --bg2: #111115;
            --accent: #00e5a0;
            --accent-glow: rgba(0,229,160,0.3);
            --t1: #ffffff;
            --t2: #94a3b8;
            --border: #1e1e28;
        }
        body {
            background-color: var(--bg);
            color: var(--t1);
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow: hidden; /* Fullscreen layout handles its own scroll */
        }
        .main-shell {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        /* Scrollers */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #1e1e28; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #2a2a35; }

        .nav-link-item {
            display: flex;
            items-center;
            gap: 12px;
            padding: 10px 16px;
            color: var(--t2);
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .nav-link-item:hover {
            color: var(--t1);
            background: rgba(255,255,255,0.03);
        }
        .nav-link-item.nav-active {
            color: var(--accent);
            background: rgba(0,229,160,0.05);
            border-left-color: var(--accent);
        }
        .nav-link-item svg { width: 18px; height: 18px; }
        .nav-section-lbl {
            padding: 16px 16px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #4b4b60;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="main-shell">
        {{-- Sidebar --}}
        <aside id="sidebar" class="flex flex-col" style="background:#111115; border-right:1px solid #1e1e28; width:240px; min-width:240px; flex-shrink:0;">
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

        {{-- Main Fullscreen Content --}}
        <div class="flex-1 flex flex-col min-w-0 relative">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
