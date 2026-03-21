@extends('tenant.layout')

@section('content')
<div class="h-full flex flex-col" x-data="saasTenants()">
    
    <!-- Topbar POS Style -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-950">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="w-6 h-6 text-indigo-400"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2-2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Tenants / Clientes Activos
            </h1>
            <p class="text-sm text-gray-400 mt-1">Gestión CRM de clientes SaaS y cobros mensuales.</p>
        </div>
        <div>
            <button @click="openModalNuevo" class="btn btn-primary flex items-center gap-2">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Onboarding
            </button>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Lista de Tenants -->
        <div class="w-2/3 border-r border-gray-800 bg-gray-900 overflow-y-auto w-full">
            
            <div class="p-4 flex gap-2 overflow-x-auto border-b border-gray-800">
                <button @click="filtroEstado = ''" :class="filtroEstado === '' ? 'btn-primary' : 'btn-ghost'" class="text-sm px-3 py-1 rounded-full whitespace-nowrap">Todos</button>
                <button @click="filtroEstado = 'trial'" :class="filtroEstado === 'trial' ? 'bg-blue-600 text-white' : 'btn-ghost'" class="text-sm px-3 py-1 rounded-full whitespace-nowrap">Trial</button>
                <button @click="filtroEstado = 'activo'" :class="filtroEstado === 'activo' ? 'bg-green-600 text-white' : 'btn-ghost'" class="text-sm px-3 py-1 rounded-full whitespace-nowrap">Activos (Sanos)</button>
                <button @click="filtroEstado = 'moroso'" :class="filtroEstado === 'moroso' ? 'bg-red-600 text-white' : 'btn-ghost'" class="text-sm px-3 py-1 rounded-full whitespace-nowrap">Morosos</button>
            </div>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-gray-400 text-sm uppercase tracking-wider bg-gray-950/50">
                        <th class="p-4 font-medium">Empresa</th>
                        <th class="p-4 font-medium">Plan (Cobro)</th>
                        <th class="p-4 font-medium">Estado</th>
                        <th class="p-4 font-medium text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="t in filtrados()" :key="t.id">
                        <tr class="border-b border-gray-800 hover:bg-gray-800/50 cursor-pointer transition-colors" @click="seleccionar(t)">
                            <td class="p-4">
                                <div class="font-medium text-white" x-text="t.razon_social"></div>
                                <div class="text-xs text-gray-400" x-text="'RUT: ' + (t.rut || 'N/A') + ' · ' + t.industria"></div>
                            </td>
                            <td class="p-4">
                                <div class="text-white text-sm" x-text="t.plan ? t.plan.nombre : 'Custom'"></div>
                                <div class="text-xs font-mono text-gray-400" x-text="formatMoney(t.precio_actual) + '/mes'"></div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded text-xs font-medium uppercase tracking-wider"
                                      :class="{
                                          'bg-blue-500/20 text-blue-400': t.estado === 'trial',
                                          'bg-green-500/20 text-green-400': t.estado === 'activo',
                                          'bg-red-500/20 text-red-400': t.estado === 'moroso',
                                          'bg-gray-500/20 text-gray-400': t.estado === 'suspendido' || t.estado === 'cancelado'
                                      }"
                                      x-text="t.estado"></span>
                            </td>
                            <td class="p-4 text-right">
                                <svg fill="none" class="w-5 h-5 text-gray-500 inline-block" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filtrados().length === 0">
                        <td colspan="4" class="p-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-12 h-12 mb-3 opacity-50"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                No hay tenants en esta vista
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Ficha Lateral (Detalle CRM y Billing) -->
        <div class="w-1/3 bg-gray-950 flex flex-col relative" x-show="selected" x-transition>
            <template x-if="selected">
                <div class="h-full flex flex-col">
                    <div class="p-6 border-b border-gray-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h2 class="text-xl font-bold text-white mb-1" x-text="selected.razon_social"></h2>
                                <p class="text-sm text-gray-400" x-text="'Contacto: ' + selected.contacto_nombre + ' (' + selected.contacto_whatsapp + ')'"></p>
                            </div>
                            <span class="px-2 py-1 rounded text-xs font-medium uppercase tracking-wider"
                                  :class="{
                                      'bg-blue-500/20 text-blue-400': selected.estado === 'trial',
                                      'bg-green-500/20 text-green-400': selected.estado === 'activo',
                                      'bg-red-500/20 text-red-400': selected.estado === 'moroso'
                                  }" x-text="selected.estado"></span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm bg-gray-900 rounded-lg p-3">
                            <div>
                                <div class="text-gray-500 text-xs">Plan Actual</div>
                                <div class="text-white font-medium" x-text="selected.plan ? selected.plan.nombre : 'Custom'"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs">Ciclo / Mes</div>
                                <div class="text-white font-mono" x-text="formatMoney(selected.precio_actual)"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs">Apertura</div>
                                <div class="text-white" x-text="formatDate(selected.created_at)"></div>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs">Ejecutivo Cta.</div>
                                <div class="text-indigo-400" x-text="selected.ejecutivo ? selected.ejecutivo.nombre : 'N/A'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestañas Detalles -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <h3 class="text-white font-medium border-b border-gray-800 pb-2 mb-4">Billing & Cobros (Últimos)</h3>
                        <div class="space-y-3">
                            <template x-for="cobro in selected.cobros" :key="cobro.id">
                                <div class="bg-gray-900 border border-gray-800 rounded-lg p-3 flex justify-between items-center">
                                    <div>
                                        <div class="text-white font-medium text-sm" x-text="'Cobro Mes: ' + cobro.periodo.split('-')[1] + '/' + cobro.periodo.split('-')[0]"></div>
                                        <div class="text-xs text-gray-400" x-text="'Vence: ' + formatDate(cobro.fecha_vencimiento)"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-mono text-white text-sm" x-text="formatMoney(cobro.total)"></div>
                                        <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded mt-1 inline-block"
                                              :class="{
                                                  'bg-green-500/20 text-green-400': cobro.estado === 'pagado',
                                                  'bg-yellow-500/20 text-yellow-500': cobro.estado === 'pendiente',
                                                  'bg-red-500/20 text-red-500': cobro.estado === 'vencido'
                                              }" x-text="cobro.estado"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!selected.cobros || selected.cobros.length === 0" class="text-sm text-gray-500 italic">No hay registros de facturación.</div>
                        </div>
                    </div>
                    
                    <!-- Botonera Acción Rápida -->
                    <div class="p-4 border-t border-gray-800 bg-gray-900/50 flex gap-2">
                        <a :href="'https://wa.me/' + selected.contacto_whatsapp.replace('+','')" target="_blank" class="flex-1 bg-green-600 hover:bg-green-500 text-white font-medium py-2 rounded-lg text-center flex items-center justify-center gap-2 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 0C5.385 0 0 5.385 0 12.031c0 2.128.552 4.197 1.6 6.035L.234 23.639l5.734-1.503a11.972 11.972 0 005.973 1.59h.005c6.64 0 12.029-5.384 12.029-12.034C24 5.044 18.63 0 12.031 0zm.005 21.602h-.003a9.927 9.927 0 01-5.068-1.385l-.364-.216-3.766.987.995-3.666-.237-.376A9.914 9.914 0 012.094 12c0-5.5 4.474-9.974 9.98-9.974A9.957 9.957 0 0122.04 12c0 5.5-4.475 9.974-9.975 9.974h-.029H12.03zm5.404-7.462c-.296-.148-1.758-.868-2.03-.967-.272-.1-.47-.148-.668.148-.198.297-.768.968-.941 1.166-.173.198-.346.223-.643.075-.296-.149-1.253-.462-2.39-1.475-.884-.789-1.48-1.763-1.653-2.06-.173-.298-.018-.458.13-.607.134-.134.297-.346.445-.52.149-.173.198-.297.297-.495.099-.198.05-.371-.024-.52-.074-.148-.668-1.61-.915-2.203-.242-.58-.487-.502-.668-.511-.173-.008-.371-.008-.569-.008s-.52.074-.792.371c-.272.297-1.039 1.015-1.039 2.476s1.064 2.872 1.212 3.07c.148.198 2.099 3.203 5.08 4.49.71.306 1.264.489 1.696.626.711.226 1.358.194 1.868.118.572-.086 1.758-.718 2.005-1.411.247-.693.247-1.288.173-1.412-.074-.124-.272-.198-.569-.347z"/></svg>
                            Hablar
                        </a>
                        <button class="btn btn-ghost" @click="selected = null">Cerrar</button>
                    </div>
                </div>
            </template>
        </div>
        
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('saasTenants', () => ({
        tenants: [],
        selected: null,
        filtroEstado: '',
        
        init() {
            this.cargar();
        },
        
        async cargar() {
            try {
                const res = await fetch('/api/saas/clientes?per_page=100');
                if(res.ok) {
                    const data = await res.json();
                    this.tenants = data.data;
                }
            } catch (e) {}
        },
        
        filtrados() {
            if(!this.filtroEstado) return this.tenants;
            return this.tenants.filter(t => t.estado === this.filtroEstado);
        },
        
        async seleccionar(t) {
            // Obtenemos los detalles full (incluyendo los cobros_
            try {
                const res = await fetch('/api/saas/clientes/' + t.id);
                if(res.ok) {
                    this.selected = await res.json();
                }
            } catch (e) {}
        },
        
        openModalNuevo() {
            alert("Módulo Formulario Nuevo Tenant no en scope para este script. Usar Postman o flujo BD vía API.");
        },

        formatMoney(amount) {
            if (amount === null || amount === undefined) return '$0';
            return '$' + new Intl.NumberFormat('es-CL').format(amount);
        },

        formatDate(dateString) {
            if(!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('es-CL', { month: 'short', year: 'numeric', day: 'numeric' });
        }
    }));
});
</script>
@endsection
