@extends('tenant.portal.layout_public')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-white pt-24 pb-32 overflow-hidden border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h2 class="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900 mb-6 leading-tight">
                {{ tenant('nombre') }}
            </h2>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto mb-12">
                {{ $config->portal_descripcion ?? 'Tu solución inteligente impulsada por benderAndBot. Estamos aquí para ayudarte en lo que necesites.' }}
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('public.portal.pedido.whatsapp', ['mensaje' => 'Hola, me gustaría consultar sobre tus servicios/productos.']) }}" 
                   class="bg-primary hover:opacity-90 text-white font-bold py-4 px-8 rounded-full shadow-lg shadow-primary/20 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-2.135 0-4.117.833-5.632 2.346C4.883 10.034 4.05 12.016 4.05 14.151c0 2.135.833 4.117 2.346 5.632l-1.094 3.994 4.133-1.084c1.114.28 2.228.423 3.32.423 2.135 0 4.117-.833 5.632-2.346 1.513-1.513 2.346-3.496 2.346-5.631 0-2.135-.833-4.117-2.346-5.632-1.513-1.513-3.496-2.346-5.631-2.346h-.032zM12.031 18.24h-.018c-.83 0-1.636-.211-2.36-.61l-.168-.093-2.454.644.655-2.389-.101-.161c-.439-.7-.671-1.503-.671-2.33 0-2.373 1.93-4.303 4.303-4.303.83 0 1.636.211 2.36.61s1.309.957 1.636 1.636c.61.724.821 1.53.821 2.36s-.211 1.636-.61 2.36c-.439.7-1.114 1.309-1.928 1.636s-1.53.821-2.36.821v.018z"/></svg>
                    Hablar con el Bot en WhatsApp
                </a>
                
                @if($config->portal_telegram_url)
                <a href="{{ $config->portal_telegram_url }}" 
                   class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 px-8 rounded-full shadow-lg shadow-blue-500/20 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.02.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.11.02-1.93 1.23-5.46 3.62-.51.35-.98.53-1.39.52-.46-.01-1.33-.26-1.98-.48-.8-.27-1.43-.42-1.37-.89.03-.25.38-.51 1.03-.78 4.04-1.76 6.74-2.92 8.09-3.48 3.85-1.6 4.64-1.88 5.17-1.89.11 0 .37.03.54.17.14.12.18.28.2.45.02.07.02.21.01.27z"/></svg>
                    Canal de Telegram
                </a>
                @endif
            </div>
        </div>
        
        <!-- Abstract shape decorations -->
        <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-primary opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-64 h-64 bg-blue-500 opacity-10 rounded-full blur-3xl"></div>
    </section>

    <!-- Info Section -->
    <section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="glass p-8 rounded-2xl flex flex-col items-center text-center">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="font-bold mb-2">Horario</h4>
                <p class="text-sm text-gray-400">{{ $config->portal_horario ?? 'Consultar con nuestro Bot' }}</p>
            </div>
            
            <div class="glass p-8 rounded-2xl flex flex-col items-center text-center">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h4 class="font-bold mb-2">Dirección</h4>
                <p class="text-sm text-gray-400">{{ $config->portal_direccion ?? 'Atención Remota' }}</p>
            </div>
            
            <div class="glass p-8 rounded-2xl flex flex-col items-center text-center">
                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <h4 class="font-bold mb-2">Contacto</h4>
                <p class="text-sm text-gray-400">{{ $config->portal_telefono ?? 'Vía Bot' }}</p>
            </div>
        </div>
    </section>

    <!-- Featured Products Grid -->
    @if($productos->count() > 0)
    <section class="py-20 bg-gray-100/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
                <div>
                    <span class="text-primary font-bold text-xs uppercase tracking-widest mb-2 block">Lo que ofrecemos</span>
                    <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight leading-tight">Productos Destacados</h3>
                </div>
                <a href="{{ route('public.portal.catalogo') }}" class="text-primary font-semibold text-sm hover:underline flex items-center gap-1 leading-tight">
                    Ver catálogo completo
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($productos as $p)
                <div class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all border border-gray-100 flex flex-col">
                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                        @if($p->imagen_url)
                            <img src="{{ $p->imagen_url }}" alt="{{ $p->nombre }}" class="object-cover w-full h-full group-hover:scale-110 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-black/5 group-hover:bg-black/0 transition-colors"></div>
                    </div>
                    <div class="p-5 flex flex-col flex-grow">
                        <span class="text-xs text-gray-400 mb-1 leading-tight">{{ $p->categoria ?? 'General' }}</span>
                        <h5 class="font-bold text-gray-900 mb-3 group-hover:text-primary transition-colors line-clamp-2 leading-tight">{{ $p->nombre }}</h5>
                        <div class="mt-auto flex items-center justify-between gap-1">
                            <span class="text-primary font-bold text-lg leading-tight">{{ number_format($p->precio, 0, ',', '.') }}</span>
                            <a href="{{ route('public.portal.pedido.whatsapp', ['mensaje' => 'Hola, me interesa el producto: ' . $p->nombre]) }}" 
                               class="w-10 h-10 bg-gray-100 hover:bg-primary hover:text-white rounded-xl flex items-center justify-center transition-all text-gray-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-2.135 0-4.117.833-5.632 2.346C4.883 10.034 4.05 12.016 4.05 14.151c0 2.135.833 4.117 2.346 5.632l-1.094 3.994 4.133-1.084c1.114.28 2.228.423 3.32.423 2.135 0 4.117-.833 5.632-2.346 1.513-1.513 2.346-3.496 2.346-5.631 0-2.135-.833-4.117-2.346-5.632-1.513-1.513-3.496-2.346-5.631-2.346h-.032zM12.031 18.24h-.018c-.83 0-1.636-.211-2.36-.61l-.168-.093-2.454.644.655-2.389-.101-.161c-.439-.7-.671-1.503-.671-2.33 0-2.373 1.93-4.303 4.303-4.303.83 0 1.636.211 2.36.61s1.309.957 1.636 1.636c.61.724.821 1.53.821 2.36s-.211 1.636-.61 2.36c-.439.7-1.114 1.309-1.928 1.636s-1.53.821-2.36.821v.018z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

@endsection
