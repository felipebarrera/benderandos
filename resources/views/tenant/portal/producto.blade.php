@extends('tenant.portal.layout_public')

@section('content')
    <section class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="glass rounded-3xl overflow-hidden grid grid-cols-1 md:grid-cols-2 gap-0">
            <!-- Product Image -->
            <div class="aspect-square bg-white relative overflow-hidden flex items-center justify-center p-8">
                @if($producto->imagen_url)
                    <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" class="object-contain w-full h-full">
                @else
                    <div class="w-full h-full bg-gray-50 flex items-center justify-center text-gray-200">
                        <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif
            </div>

            <!-- Product Details -->
            <div class="p-8 md:p-12 flex flex-col justify-center">
                <nav class="flex mb-6 text-xs text-gray-400 gap-2 items-center leading-tight">
                    <a href="{{ route('public.portal.index') }}" class="hover:text-primary">Inicio</a>
                    <span>/</span>
                    <a href="{{ route('public.portal.catalogo') }}" class="hover:text-primary">Catálogo</a>
                </nav>

                <span class="inline-block px-3 py-1 bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wider rounded-full mb-4 w-fit leading-tight">
                    {{ $producto->categoria ?? 'General' }}
                </span>

                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4 tracking-tight leading-tight">{{ $producto->nombre }}</h1>
                
                <div class="text-primary text-3xl font-bold mb-8 leading-tight">
                    {{ number_format($producto->precio, 0, ',', '.') }}
                </div>

                <div class="prose prose-sm text-gray-500 mb-10 leading-relaxed">
                    {{ $producto->descripcion ?? 'Sin descripción disponible.' }}
                </div>

                <div class="flex flex-col gap-4">
                    <a href="{{ route('public.portal.pedido.whatsapp', ['mensaje' => 'Hola, me interesa comprar: ' . $producto->nombre . ' (ID: ' . $producto->id . ')']) }}" 
                       class="bg-primary hover:opacity-90 text-white font-bold py-4 px-8 rounded-2xl shadow-lg shadow-primary/20 transition-all text-center flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-2.135 0-4.117.833-5.632 2.346C4.883 10.034 4.05 12.016 4.05 14.151c0 2.135.833 4.117 2.346 5.632l-1.094 3.994 4.133-1.084c1.114.28 2.228.423 3.32.423 2.135 0 4.117-.833 5.632-2.346 1.513-1.513 2.346-3.496 2.346-5.631 0-2.135-.833-4.117-2.346-5.632-1.513-1.513-3.496-2.346-5.631-2.346h-.032zM12.031 18.24h-.018c-.83 0-1.636-.211-2.36-.61l-.168-.093-2.454.644.655-2.389-.101-.161c-.439-.7-.671-1.503-.671-2.33 0-2.373 1.93-4.303 4.303-4.303.83 0 1.636.211 2.36.61s1.309.957 1.636 1.636c.61.724.821 1.53.821 2.36s-.211 1.636-.61 2.36c-.439.7-1.114 1.309-1.928 1.636s-1.53.821-2.36.821v.018z"/></svg>
                        Pedir por WhatsApp
                    </a>
                    
                    <button onclick="window.history.back()" class="text-xs text-gray-400 hover:text-gray-600 font-medium">
                        Volver al catálogo
                    </button>
                </div>
            </div>
        </div>
    </section>
@endsection
