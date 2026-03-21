@extends('tenant.layout')

@section('content')
<div class="h-full flex flex-col bg-gray-950" x-data="cLevelDashboard()">
    
    <!-- Topbar Executive -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 bg-gray-900/50">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                <svg fill="none" stroke="currentColor" stroke-width="2" class="w-7 h-7 text-indigo-500" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard Ejecutivo
            </h1>
            <p class="text-sm text-gray-400 mt-1">Visión consolidada de todas las áreas del negocio</p>
        </div>
        <div class="flex items-center gap-3">
            <template x-if="data && data.kpis.alertas_sii.dtes_rechazados > 0">
                <div class="animate-pulse bg-red-500/20 text-red-400 border border-red-500/50 px-3 py-1.5 rounded-full text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span x-text="data.kpis.alertas_sii.dtes_rechazados + ' DTEs Rechazados SII'"></span>
                </div>
            </template>
            <button @click="loadData()" class="btn btn-ghost" :class="(loading) ? 'opacity-50' : ''" :disabled="loading">
                <svg fill="none" stroke="currentColor" :class="(loading) ? 'animate-spin' : ''" class="w-5 h-5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
            <button class="btn btn-primary" onclick="window.print()">
                Exportar Reporte
            </button>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6">
        
        <template x-if="data">
            <div>
                <!-- 1. BLOQUE VENTAS (Financiero) -->
                <div class="mb-6">
                    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-widest mb-4">Métricas Financieras</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 shadow-sm">
                            <div class="text-gray-400 text-sm mb-1">Ventas Hoy</div>
                            <div class="text-3xl font-bold text-white mb-1" x-text="formatMoney(data.kpis.ventas.hoy)"></div>
                            <div class="text-xs text-green-400 flex items-center gap-1">
                                <span x-text="data.kpis.ventas.count_hoy + ' tickets emitidos hoy'"></span>
                            </div>
                        </div>
                        <div class="bg-gray-900 border border-indigo-900/40 rounded-xl p-5 shadow-sm relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-transparent"></div>
                            <div class="relative">
                                <div class="text-indigo-300 text-sm mb-1">Total Mes Actual</div>
                                <div class="text-3xl font-bold text-indigo-100 mb-1" x-text="formatMoney(data.kpis.ventas.mes)"></div>
                                <div class="text-xs text-indigo-400">Acumulado mensual MTD</div>
                            </div>
                        </div>
                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 shadow-sm">
                            <div class="text-gray-400 text-sm mb-1">Tícket Promedio (Mes)</div>
                            <div class="text-3xl font-bold text-white mb-1" x-text="formatMoney(data.kpis.ventas.ticket_promedio)"></div>
                            <div class="text-xs text-gray-500">Valor medio por transacción</div>
                        </div>
                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 shadow-sm">
                            <div class="text-gray-400 text-sm mb-1">Cuentas por Cobrar</div>
                            <div class="text-3xl font-bold mb-1" :class="data.kpis.operaciones.deudas_total > 0 ? 'text-red-400' : 'text-green-400'" x-text="formatMoney(data.kpis.operaciones.deudas_total)"></div>
                            <div class="text-xs text-gray-500">Deuda activa en Libros</div>
                        </div>
                    </div>
                </div>

                <!-- 2. BLOQUES MULTIDISCIPLINARIOS (Operaciones, RRHH, Logística) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Operaciones / Negocio -->
                    <div class="bg-gray-900 border border-gray-800 rounded-xl flex flex-col">
                        <div class="p-4 border-b border-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            <h3 class="font-medium text-white">Catálogo & Stock</h3>
                        </div>
                        <div class="p-5 flex-1 space-y-4">
                            <div class="flex justify-between items-center bg-gray-950 p-3 rounded-lg border border-gray-800">
                                <span class="text-gray-400">Total Productos</span>
                                <span class="font-bold text-white text-xl font-mono" x-text="data.kpis.operaciones.productos_count"></span>
                            </div>
                            <div class="flex justify-between items-center bg-orange-900/10 p-3 rounded-lg border border-orange-900/30">
                                <span class="text-orange-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Alertas Stock Crítico
                                </span>
                                <span class="font-bold text-orange-400 text-xl font-mono" x-text="data.kpis.operaciones.stock_bajo_alertas"></span>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-950/50 border-t border-gray-800 text-center rounded-b-xl">
                            <a href="/admin/productos" class="text-sm text-indigo-400 hover:text-indigo-300">Ir a inventario &rarr;</a>
                        </div>
                    </div>

                    <!-- Recursos Humanos -->
                    <div class="bg-gray-900 border border-gray-800 rounded-xl flex flex-col">
                        <div class="p-4 border-b border-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <h3 class="font-medium text-white">Fuerza Laboral (RRHH)</h3>
                        </div>
                        <div class="p-5 flex-1 space-y-4">
                            <div class="flex justify-between items-center bg-gray-950 p-3 rounded-lg border border-gray-800">
                                <span class="text-gray-400">Personal en Nómina</span>
                                <span class="font-bold text-white text-xl font-mono" x-text="data.kpis.rrhh.empleados_activos"></span>
                            </div>
                            <div class="flex flex-col gap-2 bg-gray-950 p-3 rounded-lg border border-gray-800">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Presentes Hoy</span>
                                    <span class="font-bold text-blue-400 text-xl font-mono" x-text="data.kpis.rrhh.presentes_hoy + '/' + data.kpis.rrhh.empleados_activos"></span>
                                </div>
                                <!-- Barra % -->
                                <div class="w-full bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-blue-500 h-full" :style="'width: ' + (data.kpis.rrhh.empleados_activos > 0 ? (data.kpis.rrhh.presentes_hoy / data.kpis.rrhh.empleados_activos)*100 : 0) + '%'"></div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-950/50 border-t border-gray-800 text-center rounded-b-xl">
                            <a href="/admin/rrhh/asistencia" class="text-sm text-indigo-400 hover:text-indigo-300">Ver Asistencias &rarr;</a>
                        </div>
                    </div>

                    <!-- Logística / Delivery -->
                    <div class="bg-gray-900 border border-gray-800 rounded-xl flex flex-col">
                        <div class="p-4 border-b border-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                            <h3 class="font-medium text-white">Envíos (Logística)</h3>
                        </div>
                        <div class="p-5 flex-1 space-y-4">
                            <div class="flex justify-between items-center bg-indigo-900/10 p-3 rounded-lg border border-indigo-900/30">
                                <span class="text-indigo-400">Paquetes en Ruta / Pendientes</span>
                                <span class="font-bold text-indigo-400 text-xl font-mono" x-text="data.kpis.logistica.pendientes"></span>
                            </div>
                            <div class="flex justify-between items-center bg-green-900/10 p-3 rounded-lg border border-green-900/30">
                                <span class="text-green-400 border-b border-green-400/20 border-dashed pb-0.5">Entregados este Mes</span>
                                <span class="font-bold text-green-400 text-xl font-mono" x-text="data.kpis.logistica.entregados_mes"></span>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-950/50 border-t border-gray-800 text-center rounded-b-xl">
                            <a href="/admin/delivery" class="text-sm text-indigo-400 hover:text-indigo-300">Centro de Endpoints &rarr;</a>
                        </div>
                    </div>

                </div>
            </div>
        </template>
        
        <div x-show="loading" class="flex justify-center items-center py-20">
            <svg class="animate-spin h-8 w-8 text-indigo-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cLevelDashboard', () => ({
        data: null,
        loading: true,

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const res = await fetch('/api/dashboard');
                if (res.ok) {
                    this.data = await res.json();
                }
            } catch (e) {
                console.error("Dashboard error", e);
            } finally {
                this.loading = false;
            }
        },

        formatMoney(amount) {
            if (amount === null || amount === undefined) return '$0';
            return '$' + new Intl.NumberFormat('es-CL').format(amount);
        }
    }));
});
</script>

<style>
@media print {
    .bg-gray-950 { background-color: #fff !important; }
    .bg-gray-900 { border: 1px solid #ccc !important; box-shadow: none !important; }
    .text-white { color: #000 !important; }
    .text-gray-400, .text-gray-500 { color: #555 !important; }
    .btn-ghost, .btn-primary { display: none !important; }
}
</style>
@endsection
