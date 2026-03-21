@extends('tenant.layout')

@section('content')
<div class="h-full flex flex-col bg-gray-950">
    
    <!-- Topbar API Docs -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-900/50">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                <svg fill="none" stroke="currentColor" stroke-width="2" class="w-7 h-7 text-green-500" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                API REST Pública (v1)
            </h1>
            <p class="text-sm text-gray-400 mt-1">Documentación de integración para desarrolladores. Requiere Bearer Token.</p>
        </div>
        <div class="flex items-center gap-3">
            <button class="btn-primary" onclick="alert('Funcionalidad de Generar Master API Key a validar con el Super Administrador')">
                Generar API Key
            </button>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 overflow-y-auto p-6 space-y-8 max-w-5xl mx-auto w-full">
        
        <!-- Auth Section -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <h2 class="text-xl font-bold text-white mb-2">Autenticación (Bearer Token)</h2>
            <p class="text-gray-400 text-sm mb-4">Todas las peticiones a <code class="bg-gray-800 text-green-400 px-1.5 py-0.5 rounded">/api/v1/public/*</code> deben incluir el header de autorización.</p>
            <div class="bg-gray-950 border border-gray-800 p-4 rounded-lg font-mono text-sm">
                <div class="text-gray-500 mb-1">Authorization: Bearer <span class="text-indigo-400">&lt;tu_token_sanctum&gt;</span></div>
                <div class="text-gray-500">Accept: application/json</div>
            </div>
        </div>

        <!-- Endpoints List -->
        <h2 class="text-xl font-bold text-white mt-8 mb-4">Endpoints Disponibles</h2>

        <!-- GET /productos -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="flex items-center gap-4 bg-gray-900/80 p-4 border-b border-gray-800">
                <span class="bg-blue-500/20 text-blue-400 font-bold px-3 py-1 rounded text-sm">GET</span>
                <code class="text-white font-mono text-lg">/api/v1/public/productos</code>
            </div>
            <div class="p-6">
                <p class="text-gray-400 text-sm mb-4">Obtiene el catálogo interactivo de productos. Soporta paginación y búsqueda.</p>
                <div class="mb-4">
                    <h4 class="text-gray-300 text-sm font-bold mb-2">Query Parameters</h4>
                    <table class="w-full text-left text-sm text-gray-400 border-collapse">
                        <tr class="border-b border-gray-800"><td class="py-2"><code class="text-gray-300">search</code></td><td>(Opcional) Filtrar por SKU o nombre.</td></tr>
                        <tr class="border-b border-gray-800"><td class="py-2"><code class="text-gray-300">per_page</code></td><td>(Opcional) Límite de resultados (default 50).</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- GET /stock/{sku} -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="flex items-center gap-4 bg-gray-900/80 p-4 border-b border-gray-800">
                <span class="bg-blue-500/20 text-blue-400 font-bold px-3 py-1 rounded text-sm">GET</span>
                <code class="text-white font-mono text-lg">/api/v1/public/stock/{sku}</code>
            </div>
            <div class="p-6">
                <p class="text-gray-400 text-sm mb-4">Obtiene en tiempo real el stock consolidado de un producto por su SKU exacto.</p>
                <div class="bg-gray-950 p-4 rounded-lg">
                    <pre class="text-sm font-mono text-green-400">
{
  "sku": "CHEL-01",
  "nombre": "Cerveza Artesanal",
  "stock_actual": 145,
  "unidad_medida": "unidad",
  "precio_venta": 4500
}
                    </pre>
                </div>
            </div>
        </div>

        <!-- POST /ventas -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="flex items-center gap-4 bg-gray-900/80 p-4 border-b border-green-800/50">
                <span class="bg-green-500/20 text-green-400 font-bold px-3 py-1 rounded text-sm">POST</span>
                <code class="text-white font-mono text-lg">/api/v1/public/ventas</code>
            </div>
            <div class="p-6">
                <p class="text-gray-400 text-sm mb-4">Inserta una nueva venta / orden desde un sistema e-commerce o plataforma externa hacia BenderAnd.</p>
                <h4 class="text-gray-300 text-sm font-bold mb-2">Request Body (application/json)</h4>
                <div class="bg-gray-950 p-4 rounded-lg mb-4">
                    <pre class="text-sm font-mono text-gray-300">
{
  "cliente_id": 12,           // (opcional)
  "monto_total": 45000,
  "estado": "completada",     // o "pendiente"
  "metodo_pago": "webpay",
  "origen": "tienda_online",
  "items": [
    {
      "producto_id": 5,
      "cantidad": 2,
      "precio_unitario": 22500
    }
  ]
}
                    </pre>
                </div>
            </div>
        </div>
        
    </div>
</div>
@endsection
