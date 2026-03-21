@extends('tenant.portal.layout_public')

@section('content')
    <section class="py-16 bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight leading-tight">Nuestro Catálogo</h2>
            <p class="text-gray-500 mt-2">Explora todos nuestros productos y servicios disponibles.</p>
        </div>
    </section>

    <section class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($productos->count() > 0)
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
                    </div>
                    <div class="p-5 flex flex-col flex-grow">
                        <span class="text-xs text-gray-400 mb-1 leading-tight">{{ $p->categoria ?? 'General' }}</span>
                        <h5 class="font-bold text-gray-900 mb-2 leading-tight">{{ $p->nombre }}</h5>
                        <p class="text-xs text-gray-400 mb-4 line-clamp-2 flex-grow leading-tight">{{ $p->descripcion }}</p>
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-primary font-bold text-lg leading-tight">{{ number_format($p->precio, 0, ',', '.') }}</span>
                            <a href="{{ route('public.portal.pedido.whatsapp', ['mensaje' => 'Hola, me interesa el producto: ' . $p->nombre]) }}" 
                               class="bg-gray-100 hover:bg-primary hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition-all text-gray-600">
                                Consultar
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $productos->links() }}
            </div>
        @else
            <div class="text-center py-20 bg-gray-50 rounded-3xl">
                <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                <h4 class="text-gray-400 font-medium">No hay productos visibles en el portal aún.</h4>
            </div>
        @endif
    </section>
@endsection
