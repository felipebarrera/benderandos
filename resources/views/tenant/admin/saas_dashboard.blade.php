@extends('tenant.layout')

@section('content')
<div class="h-full flex flex-col" x-data="saasDashboard()">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-900/50">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="w-6 h-6 text-indigo-400"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                Dashboard SaaS
            </h1>
            <p class="text-sm text-gray-400 mt-1">Métricas de crecimiento y recurrencia (MRR)</p>
        </div>
        <div>
            <button @click="generarSnapshot" class="btn-primary text-sm flex items-center gap-2" :disabled="loading">
                <svg x-show="!loading" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="w-4 h-4"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <svg x-show="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-show="!loading">Forzar Snapshot Diario</span>
                <span x-show="loading" style="display: none">Calculando...</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto p-6 bg-gray-950">
        <template x-if="data">
            <div class="space-y-6">
                
                <!-- KPIs Top (MRR, ARR, Churn, ARPU) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                        <div class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-2">MRR (Ingreso Recurrente)</div>
                        <div class="text-3xl font-bold text-white mb-2" x-text="formatMoney(data.kpis.mrr)"></div>
                        <div class="text-xs text-green-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            Sano
                        </div>
                    </div>
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                        <div class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-2">ARR (Proyectado Anual)</div>
                        <div class="text-3xl font-bold text-white mb-2" x-text="formatMoney(data.kpis.arr)"></div>
                        <div class="text-xs text-gray-400 font-mono" x-text="'ARPU: ' + formatMoney(data.kpis.arpu)"></div>
                    </div>
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                        <div class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-2">Churn Rate</div>
                        <div class="text-3xl font-bold mb-2" :class="data.kpis.churn_rate > 5 ? 'text-red-400' : 'text-white'" x-text="(data.kpis.churn_rate || 0) + '%'"></div>
                        <div class="text-xs text-gray-500 line-clamp-1">Meta: < 5% / Mes</div>
                    </div>
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                        <div class="text-gray-400 text-xs font-medium uppercase tracking-wider mb-2">LTV Estimado</div>
                        <div class="text-3xl font-bold text-white mb-2" x-text="formatMoney(data.kpis.ltv_promedio)"></div>
                        <div class="text-xs text-gray-500">Valor de vida del cliente</div>
                    </div>
                </div>

                <!-- Segunda fila de KPIs -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 col-span-2">
                        <h3 class="text-white font-medium mb-4">Evolución MRR (Últimos 6 meses)</h3>
                        <div class="h-64 flex items-end gap-2">
                            <template x-for="mes in data.grafico_mrr">
                                <div class="flex-1 flex flex-col items-center gap-2">
                                    <div class="w-full bg-indigo-500/20 border border-indigo-500/50 rounded-t-sm relative group" :style="'height: ' + Math.max(10, (mes.mrr / data.kpis.mrr) * 100) + '%'">
                                        <!-- Tooltip -->
                                        <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-800 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition shadow whitespace-nowrap z-10" x-text="formatMoney(mes.mrr)"></div>
                                    </div>
                                    <div class="text-xs text-gray-400" x-text="formatDateMonth(mes.fecha)"></div>
                                </div>
                            </template>
                            <div x-show="!data.grafico_mrr || data.grafico_mrr.length === 0" class="w-full h-full flex items-center justify-center text-gray-500">
                                Sin datos históricos suficientes.
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-6">
                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 flex-1">
                            <h3 class="text-white font-medium mb-4">Estado del Portfolio</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-gray-300">🏢 Tenants Activos</span>
                                    <span class="font-mono text-white text-lg" x-text="data.kpis.tenants_activos"></span>
                                </div>
                                <div class="flex justify-between items-center bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-gray-300">🚀 Tenants en Trial</span>
                                    <span class="font-mono text-blue-400 text-lg" x-text="data.kpis.tenants_trial"></span>
                                </div>
                                <div class="flex justify-between items-center bg-red-900/20 border border-red-900/30 p-3 rounded-lg">
                                    <span class="text-red-400">⚠️ Morosos</span>
                                    <span class="font-mono text-red-400 text-lg font-bold" x-text="data.kpis.tenants_morosos"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 mt-4">
                                    <div class="bg-gray-800/30 p-2 rounded text-center">
                                        <div class="text-xs text-gray-500">Nuevos (Mes)</div>
                                        <div class="font-mono text-green-400" x-text="'+' + (data.kpis.nuevos_mes || 0)"></div>
                                    </div>
                                    <div class="bg-gray-800/30 p-2 rounded text-center">
                                        <div class="text-xs text-gray-500">Cancelados</div>
                                        <div class="font-mono text-red-400" x-text="'-' + (data.kpis.cancelados_mes || 0)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tercera Fila: Desgloses -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                        <h3 class="text-white font-medium mb-4">Distribución por Planes</h3>
                        <div class="space-y-3">
                            <template x-for="plan in data.distribucion_planes">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300" x-text="plan.nombre + ' (' + plan.cantidad + ')'"></span>
                                        <span class="text-white font-mono" x-text="formatMoney(plan.mrr)"></span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full" :style="'width: ' + ((plan.mrr / data.kpis.mrr) * 100) + '%'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
                        <h3 class="text-white font-medium mb-4">Top Industrias Atendidas</h3>
                        <div class="space-y-3">
                            <template x-for="rubro in data.distribucion_rubros">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300 capitalize" x-text="rubro.industria"></span>
                                        <span class="text-gray-400" x-text="rubro.cantidad + ' tenants'"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!data.distribucion_rubros || data.distribucion_rubros.length === 0" class="text-gray-500 text-sm italic">
                                Aún no hay industrias categorizadas.
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </template>
        
        <!-- Loading overlay initial -->
        <div x-show="!data && loading" class="flex flex-col items-center justify-center h-64">
            <svg class="animate-spin h-8 w-8 text-indigo-500 mb-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <p class="text-gray-400">Cargando métricas de SaaS...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('saasDashboard', () => ({
        data: null,
        loading: true,

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const res = await fetch('/api/saas/dashboard');
                if (res.ok) {
                    this.data = await res.json();
                }
            } catch (e) {
                console.error("Error loading saas dashboard", e);
            } finally {
                this.loading = false;
            }
        },

        async generarSnapshot() {
            if(confirm('¿Forzar el cálculo de métricas al día de hoy? (Esta tarea se ejecuta automáticamente todos los días a media noche).')) {
                this.loading = true;
                try {
                    const res = await fetch('/api/saas/generar-snapshot', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    if (res.ok) {
                        alert('Métricas actualizadas exitosamente');
                        this.loadData();
                    }
                } catch (e) {
                    console.error("Error snapshot", e);
                    this.loading = false;
                }
            }
        },

        formatMoney(amount) {
            if (amount === null || amount === undefined) return '$0';
            return '$' + new Intl.NumberFormat('es-CL').format(amount);
        },

        formatDateMonth(dateString) {
            if(!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('es-CL', { month: 'short', year: 'numeric' });
        }
    }));
});
</script>
@endsection
