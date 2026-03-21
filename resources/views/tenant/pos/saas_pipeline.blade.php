@extends('tenant.layout')

@section('content')
<div class="h-full flex flex-col" x-data="saasPipeline()">
    <!-- Topbar POS Style -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-950">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="w-6 h-6 text-indigo-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Pipeline Ventas (CRM)
            </h1>
            <p class="text-sm text-gray-400 mt-1">Gestión Kanban de prospectos y agendamiento comercial.</p>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-ghost flex items-center gap-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Sincronizar WA
            </button>
            <button class="btn btn-primary flex items-center gap-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Lead
            </button>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="flex-1 overflow-x-auto bg-gray-950 p-4">
        <div class="flex h-full gap-4 items-start pb-4" style="min-width: 1200px">
            
            <!-- Nuevos -->
            <div class="w-72 bg-gray-900 border border-gray-800 rounded-xl flex flex-col max-h-full">
                <div class="p-3 border-b border-gray-800 bg-gray-900/80 rounded-t-xl sticky top-0 flex items-center justify-between">
                    <h3 class="font-medium text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> Nuevo
                    </h3>
                    <span class="text-xs bg-gray-800 text-gray-400 px-2 py-0.5 rounded-full" x-text="columnas.nuevo.length"></span>
                </div>
                <div class="px-2 py-4 space-y-3 overflow-y-auto flex-1 custom-scrollbar min-h-[150px]">
                    <template x-for="p in columnas.nuevo" :key="p.id">
                        <div class="bg-gray-800 hover:bg-gray-750 p-3 rounded-lg border border-gray-700 cursor-grab shadow-sm transition">
                            <div class="text-sm font-medium text-white mb-1" x-text="p.razon_social"></div>
                            <div class="text-xs text-gray-400 mb-2" x-text="p.industria"></div>
                            <div class="flex justify-between items-end mt-2">
                                <span class="text-xs text-indigo-400" x-text="p.plan ? p.plan.nombre : ''"></span>
                                <button class="text-xs text-gray-500 hover:text-white" @click="abrir(p)">Ver</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Contactado -->
            <div class="w-72 bg-gray-900 border border-gray-800 rounded-xl flex flex-col max-h-full">
                <div class="p-3 border-b border-gray-800 bg-gray-900/80 rounded-t-xl sticky top-0 flex items-center justify-between">
                    <h3 class="font-medium text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span> Contactado
                    </h3>
                    <span class="text-xs bg-gray-800 text-gray-400 px-2 py-0.5 rounded-full" x-text="columnas.contactado.length"></span>
                </div>
                <div class="px-2 py-4 space-y-3 overflow-y-auto flex-1 custom-scrollbar min-h-[150px]">
                    <template x-for="p in columnas.contactado" :key="p.id">
                        <div class="bg-gray-800 hover:bg-gray-750 p-3 rounded-lg border border-gray-700 cursor-grab shadow-sm transition">
                            <div class="text-sm font-medium text-white mb-1" x-text="p.razon_social"></div>
                            <div class="text-xs text-gray-400 mb-2" x-text="p.industria"></div>
                            <div class="flex justify-between items-end mt-2">
                                <button @click="moverAdemo(p.id)" class="text-[10px] bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white px-2 py-1 rounded transition">Agendar Demo <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Demo -->
            <div class="w-72 bg-gray-900 border border-gray-800 rounded-xl flex flex-col max-h-full">
                <div class="p-3 border-b border-gray-800 bg-gray-900/80 rounded-t-xl sticky top-0 flex items-center justify-between">
                    <h3 class="font-medium text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span> Demo Agendada
                    </h3>
                    <span class="text-xs bg-gray-800 text-gray-400 px-2 py-0.5 rounded-full" x-text="columnas.demo_agendada.length"></span>
                </div>
                <div class="px-2 py-4 space-y-3 overflow-y-auto flex-1 custom-scrollbar min-h-[150px]">
                    <template x-for="p in columnas.demo_agendada" :key="p.id">
                        <div class="bg-gray-800 border-orange-500/30 border p-3 rounded-lg shadow-sm transition">
                            <div class="text-sm font-medium text-white mb-1" x-text="p.razon_social"></div>
                            <div class="flex items-center gap-1 text-xs text-orange-400 mb-2 font-mono">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Pendiente a realizar
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Negociacion -->
            <div class="w-72 bg-gray-900 border border-gray-800 rounded-xl flex flex-col max-h-full">
                <div class="p-3 border-b border-gray-800 bg-gray-900/80 rounded-t-xl sticky top-0 flex items-center justify-between">
                    <h3 class="font-medium text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span> Negociación
                    </h3>
                    <span class="text-xs bg-gray-800 text-gray-400 px-2 py-0.5 rounded-full" x-text="columnas.negociacion.length"></span>
                </div>
                <div class="px-2 py-4 space-y-3 overflow-y-auto flex-1 custom-scrollbar min-h-[150px]">
                    <template x-for="p in columnas.negociacion" :key="p.id">
                        <div class="bg-gray-800 hover:bg-gray-750 p-3 rounded-lg border border-gray-700 shadow-sm transition">
                            <div class="text-sm font-medium text-white mb-1" x-text="p.razon_social"></div>
                            <div class="text-xs text-purple-400 flex items-center justify-between mt-2">
                                <span>$189.000 / mes</span>
                                <button class="btn btn-primary btn-sm" @click="ganar(p)">Cerrar 🎉</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('saasPipeline', () => ({
        columnas: {
            nuevo: [],
            contactado: [],
            demo_agendada: [],
            demo_hecha: [],
            propuesta: [],
            negociacion: []
        },

        init() {
            this.cargar();
        },

        async cargar() {
            try {
                const res = await fetch('/api/saas/pipeline?per_page=200');
                if (res.ok) {
                    const data = await res.json();
                    
                    // Reset
                    Object.keys(this.columnas).forEach(k => this.columnas[k] = []);
                    
                    // Group
                    data.data.forEach(p => {
                        if (this.columnas[p.etapa] !== undefined) {
                            this.columnas[p.etapa].push(p);
                        }
                    });
                }
            } catch (e) {
                console.error(e);
            }
        },

        async moverAdemo(id) {
            if(confirm("Simulación: Esto movería la tarjeta a Demo y abriría el modal de agendamiento.")) {
                try {
                    await fetch(`/api/saas/pipeline/${id}/etapa`, {
                        method: 'PUT',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                        },
                        body: JSON.stringify({ etapa: 'demo_agendada' })
                    });
                    this.cargar();
                } catch(e) {}
            }
        },

        abrir(p) {
            alert(`Ficha del prospecto ${p.razon_social}`);
        },
        
        async ganar(p) {
            if(confirm("¡Genial! Este lead se convierte en Tenant formal ahora. Se creará su ambiente interno (Simulación API)")) {
                try {
                    await fetch(`/api/saas/pipeline/${p.id}/etapa`, {
                        method: 'PUT',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                        },
                        body: JSON.stringify({ etapa: 'ganado' })
                    });
                    
                    // Automatically trigger tenant creation step here internally
                    await fetch('/api/saas/clientes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                        },
                        body: JSON.stringify({
                            razon_social: p.razon_social,
                            industria: p.industria,
                            contacto_nombre: p.contacto_nombre,
                            contacto_whatsapp: p.contacto_whatsapp,
                            plan_id: p.plan_interes || 2, // default PRO
                            ejecutivo_id: p.ejecutivo_id
                        })
                    });
                    
                    alert("Tenant creado exitosamente. Búscalo en la vista 'Tenants'.");
                    this.cargar();
                } catch(e) {
                    console.error("Error cerrado prospecto", e);
                }
            }
        }
    }));
});
</script>
@endsection
