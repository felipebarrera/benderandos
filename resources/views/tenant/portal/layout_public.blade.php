<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ tenant('nombre') }} - Portal Público</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body { font-family: 'Outfit', sans-serif; }
        :root {
            --primary-color: {{ $config->portal_color_primario ?? '#00e5a0' }};
        }
        .bg-primary { background-color: var(--primary-color); }
        .text-primary { color: var(--primary-color); }
        .border-primary { border-color: var(--primary-color); }
        
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

    <!-- Header -->
    <header class="sticky top-0 z-50 glass shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                @if($config->portal_logo_url)
                    <img src="{{ $config->portal_logo_url }}" alt="{{ tenant('nombre') }}" class="h-10 w-auto rounded">
                @endif
                <h1 class="text-xl font-bold tracking-tight text-gray-900 leading-tight">
                    {{ tenant('nombre') }}
                </h1>
            </div>
            
            <nav class="hidden md:flex space-x-8">
                <a href="{{ route('public.portal.index') }}" class="text-sm font-medium hover:text-primary transition-colors">Inicio</a>
                <a href="{{ route('public.portal.catalogo') }}" class="text-sm font-medium hover:text-primary transition-colors">Catálogo</a>
            </nav>

            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-xs text-gray-400 hover:text-gray-600">Acceso Staff</a>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-20 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h3 class="font-bold text-lg mb-4">{{ tenant('nombre') }}</h3>
            <p class="text-gray-500 text-sm max-w-md mx-auto mb-8">
                {{ $config->portal_descripcion ?? 'Tu solución de confianza.' }}
            </p>
            <div class="flex justify-center gap-6 mb-8 text-gray-400">
                @if($config->portal_telefono)
                    <span class="flex items-center gap-1 text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> 
                        {{ $config->portal_telefono }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ tenant('nombre') }}. Potenciado por benderAndBot.
            </p>
        </div>
    </footer>

</body>
</html>
