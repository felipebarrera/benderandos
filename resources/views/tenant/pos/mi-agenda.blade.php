@extends('tenant.layout')

@section('title', 'Mi Agenda Personal')

@section('content')
<div class="p-6 max-w-7xl mx-auto" id="miAgendaApp">
    <!-- Header: Perfil y Switcher de Vista -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                <span class="p-3 rounded-2xl bg-emerald-500/20 text-emerald-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
                Mi Agenda
            </h1>
            <p class="text-slate-400 mt-1 ml-14">Gestiona tu horario y citas personales.</p>
        </div>

        <div class="flex items-center bg-slate-800/50 p-1.5 rounded-xl border border-slate-700/50">
            <button @click="view = 'dia'" :class="view === 'dia' ? 'bg-emerald-500 text-white shadow-lg' : 'text-slate-400 hover:text-white'" 
                class="px-5 py-2 rounded-lg font-medium transition-all duration-200">
                Día
            </button>
            <button @click="view = 'semana'" :class="view === 'semana' ? 'bg-emerald-500 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                class="px-5 py-2 rounded-lg font-medium transition-all duration-200">
                Semana
            </button>
            <button @click="view = 'config'" :class="view === 'config' ? 'bg-emerald-500 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                class="px-5 py-2 rounded-lg font-medium transition-all duration-200">
                Ajustes
            </button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div v-cloak>
        
        <!-- VISTA DÍA -->
        <div v-if="view === 'dia'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sidebar: Hoy -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Mini Calendario / Selector -->
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
                    <input type="date" v-model="fecha" @change="fetchDia" 
                           class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>

                <!-- Bloqueos / Breaks -->
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-white">Bloqueos / Breaks</h3>
                        <button @click="abrirModalBloqueo" class="text-emerald-400 hover:text-emerald-300 text-sm font-medium">+ Agregar</button>
                    </div>
                    <div v-if="recurso && recurso.bloqueos && recurso.bloqueos.length" class="space-y-3">
                         <div v-for="b in recurso.bloqueos" :key="b.id" class="p-3 rounded-xl bg-slate-900/40 border border-slate-700 flex justify-between items-center group">
                            <div>
                                <div class="text-white text-sm font-medium">@{{ b.motivo }}</div>
                                <div class="text-slate-400 text-xs text-uppercase mt-0.5">@{{ formatHora(b.hora_inicio) }} - @{{ formatHora(b.hora_fin) }}</div>
                            </div>
                            <button @click="eliminarBloqueo(b.id)" class="opacity-0 group-hover:opacity-100 text-rose-400 hover:text-rose-300 transition-all p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" /></svg>
                            </button>
                         </div>
                    </div>
                    <p v-else class="text-slate-500 text-sm italic py-4 text-center">Sin bloqueos para hoy</p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="lg:col-span-2">
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">
                    <div class="p-4 border-b border-slate-700/50 bg-slate-900/30 flex justify-between items-center">
                         <h2 class="font-bold text-lg text-white">Cronograma: @{{ formatFechaHumana(fecha) }}</h2>
                         <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold border border-emerald-500/20">
                             @{{ citasDia.length }} CITAS
                         </span>
                    </div>

                    <div class="divide-y divide-slate-700/50">
                        <div v-if="loading" class="p-12 text-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-emerald-500 mb-4"></i>
                            <p class="text-slate-400">Cargando agenda...</p>
                        </div>
                        
                        <div v-else-if="citasDia.length === 0" class="p-12 text-center">
                            <div class="w-16 h-16 bg-slate-900/50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-700">
                                <svg class="w-8 h-8 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" />
                                </svg>
                            </div>
                            <h3 class="text-white font-medium">No hay citas para hoy</h3>
                            <p class="text-slate-500 text-sm mt-1">Tu agenda está despejada por el momento.</p>
                        </div>

                        <!-- Slots Timeline -->
                        <div v-else v-for="c in sortedCitas" :key="c.id" 
                             class="group p-5 hover:bg-slate-700/30 transition-all flex gap-6">
                            
                            <!-- Hora -->
                            <div class="w-20 pt-1 shrink-0">
                                <div class="text-white font-bold text-lg">@{{ formatHora(c.hora_inicio) }}</div>
                                <div class="text-slate-500 text-xs">@{{ c.duracion_min }} min</div>
                            </div>

                            <!-- Marker -->
                            <div class="w-1 bg-emerald-500 rounded-full shrink-0 group-hover:scale-y-110 transition-transform" 
                                 :style="{ backgroundColor: getEstadoColor(c.estado) }"></div>

                            <!-- Info -->
                            <div class="flex-grow">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="text-white font-bold text-xl group-hover:text-emerald-400 transition-colors">@{{ c.paciente_nombre }}</div>
                                        <div class="text-slate-400 text-sm font-medium">@{{ c.servicio ? c.servicio.nombre : 'Servicio general' }}</div>
                                    </div>
                                    <span :class="getEstadoBadgeClass(c.estado)" class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">
                                        @{{ c.estado }}
                                    </span>
                                </div>
                                <div class="flex gap-4 text-xs text-slate-500">
                                    <span class="flex items-center gap-1"><i class="fas fa-phone"></i> @{{ c.paciente_telefono || 'S/T' }}</span>
                                    <span v-if="c.paciente_rut" class="flex items-center gap-1"><i class="fas fa-id-card"></i> @{{ c.paciente_rut }}</span>
                                </div>
                            </div>

                            <!-- Acciones Rápidas -->
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="verDetalleCita(c)" class="p-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-400 hover:text-white hover:border-emerald-500 transition-all">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button v-if="c.estado === 'confirmada'" @click="cambiarEstado(c, 'en_curso')" class="p-2.5 rounded-xl bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white transition-all">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button v-if="c.estado === 'en_curso'" @click="cambiarEstado(c, 'completada')" class="p-2.5 rounded-xl bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-white transition-all">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VISTA SEMANA -->
        <div v-if="view === 'semana'" class="space-y-6">
             <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-4 flex items-center justify-between">
                 <div class="flex items-center gap-4">
                     <button @click="prevSemana" class="p-2 rounded-lg bg-slate-900 hover:bg-slate-700 text-white"><i class="fas fa-chevron-left"></i></button>
                     <h2 class="text-white font-bold text-lg">@{{ labelSemana }}</h2>
                     <button @click="nextSemana" class="p-2 rounded-lg bg-slate-900 hover:bg-slate-700 text-white"><i class="fas fa-chevron-right"></i></button>
                 </div>
                 <button @click="fecha = today; fetchSemana()" class="text-emerald-400 font-medium">Volver a hoy</button>
             </div>

             <div class="grid grid-cols-7 gap-4">
                 <div v-for="d in diasSemana" :key="d.fecha" 
                      class="bg-slate-800/30 border border-slate-700/30 rounded-2xl overflow-hidden flex flex-col min-h-[400px]">
                     <div :class="esHoy(d.fecha) ? 'bg-emerald-500/20 border-b border-emerald-500/30' : 'bg-slate-900/30 border-b border-slate-700/50'" class="p-3 text-center">
                         <div class="text-xs font-bold uppercase" :class="esHoy(d.fecha) ? 'text-emerald-400' : 'text-slate-500'">@{{ formatDiaNombre(d.fecha) }}</div>
                         <div class="text-lg font-black" :class="esHoy(d.fecha) ? 'text-white' : 'text-slate-300'">@{{ formatDiaNumero(d.fecha) }}</div>
                     </div>
                     <div class="flex-grow p-2 space-y-2">
                         <div v-for="c in d.citas" :key="c.id" 
                              @click="verDetalleCita(c)"
                              class="p-2 rounded-lg text-xs cursor-pointer hover:brightness-110 transition-all border border-black/10"
                              :style="{ backgroundColor: getEstadoColor(c.estado) + '33', borderLeft: '3px solid ' + getEstadoColor(c.estado), color: 'white' }">
                             <div class="font-bold">@{{ formatHora(c.hora_inicio) }}</div>
                             <div class="truncate opacity-90">@{{ c.paciente_nombre }}</div>
                         </div>
                         <div v-if="!d.citas || d.citas.length === 0" class="h-full flex items-center justify-center">
                             <span class="text-[10px] text-slate-600 font-medium tracking-tighter uppercase">Sin citas</span>
                         </div>
                     </div>
                 </div>
             </div>
        </div>

        <!-- VISTA CONFIGURACIÓN -->
        <div v-if="view === 'config'" class="max-w-4xl mx-auto space-y-8">
            <!-- Horarios Operativos -->
            <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Mi Disponibilidad</h2>
                        <p class="text-slate-400">Define los días y horas que estarás disponible para citas.</p>
                    </div>
                    <button @click="guardarHorarios" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-white rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/20">
                        Guardar Cambios
                    </button>
                </div>

                <div class="space-y-4">
                    <div v-for="h in horariosEdit" :key="h.dia_semana" 
                         class="grid grid-cols-12 items-center gap-4 p-4 rounded-xl transition-all"
                         :class="h.activo ? 'bg-slate-900/40 border border-slate-700/50' : 'bg-slate-900/10 border border-transparent opacity-50'">
                        
                        <div class="col-span-3 flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="h.activo" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                            <span class="font-bold text-white uppercase tracking-wider text-sm">@{{ getDiaNombre(h.dia_semana) }}</span>
                        </div>

                        <div class="col-span-9 flex items-center gap-4" v-if="h.activo">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 font-bold uppercase">Desde</span>
                                <input type="time" v-model="h.hora_inicio" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-white outline-none focus:border-emerald-500 transition-colors">
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 font-bold uppercase">Hasta</span>
                                <input type="time" v-model="h.hora_fin" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-white outline-none focus:border-emerald-500 transition-colors">
                            </div>
                            <div class="flex items-center gap-2 ml-auto">
                                <span class="text-xs text-slate-500 font-bold uppercase">Intervalo</span>
                                <select v-model="h.duracion_slot_min" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-white outline-none focus:border-emerald-500 transition-colors">
                                    <option :value="15">15 min</option>
                                    <option :value="20">20 min</option>
                                    <option :value="30">30 min</option>
                                    <option :value="45">45 min</option>
                                    <option :value="60">60 min</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-span-9 text-slate-600 italic text-sm py-1.5" v-else>
                            No disponible este día
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perfil / Información Pública -->
            <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-8 backdrop-blur-sm">
                <h2 class="text-2xl font-bold text-white mb-6">Mi Perfil Público</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-slate-400 text-sm font-bold mb-2 uppercase tracking-wide">Especialidad / Cargo</label>
                        <input type="text" v-model="recurso.especialidad" readonly 
                               class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-slate-300 cursor-not-allowed">
                        <p class="text-[10px] text-slate-500 mt-1 italic">Este campo es asignado por el administrador.</p>
                    </div>
                    <div>
                        <label class="block text-slate-400 text-sm font-bold mb-2 uppercase tracking-wide">Color Identificador</label>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl border border-slate-700" :style="{ backgroundColor: recurso.color }"></div>
                            <span class="text-white font-mono uppercase">@{{ recurso.color }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE CITA -->
    <div v-if="modalCita" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
        <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="relative h-32 bg-gradient-to-r from-emerald-600 to-emerald-800 p-8">
                <button @click="modalCita = null" class="absolute top-4 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-black/20 text-white hover:bg-black/40 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
                <div class="absolute -bottom-6 left-8 flex items-end gap-4">
                    <div class="w-20 h-20 bg-slate-900 rounded-2xl border-4 border-slate-900 flex items-center justify-center shadow-xl">
                        <i class="fas fa-user-injured text-3xl text-emerald-400"></i>
                    </div>
                    <div class="pb-2">
                        <h2 class="text-2xl font-black text-white">@{{ citaSel.paciente_nombre }}</h2>
                        <span :class="getEstadoBadgeClass(citaSel.estado)" class="px-3 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-white/20">@{{ citaSel.estado }}</span>
                    </div>
                </div>
            </div>

            <div class="p-8 pt-12 space-y-8">
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Información de Hora</h4>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3 text-white font-bold">
                                <i class="far fa-calendar-alt text-emerald-400"></i> @{{ formatFechaHumana(citaSel.fecha) }}
                            </div>
                            <div class="flex items-center gap-3 text-white font-bold text-xl">
                                <i class="far fa-clock text-emerald-400"></i> @{{ formatHora(citaSel.hora_inicio) }} - @{{ formatHora(citaSel.hora_fin) }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Contacto</h4>
                        <div class="space-y-2">
                            <div v-if="citaSel.paciente_telefono" class="flex items-center gap-3 text-white font-bold">
                                <i class="fas fa-phone text-emerald-400"></i> @{{ citaSel.paciente_telefono }}
                            </div>
                            <div v-if="citaSel.paciente_email" class="flex items-center gap-3 text-white font-bold">
                                <i class="fas fa-envelope text-emerald-400"></i> @{{ citaSel.paciente_email }}
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Notas Internas</h4>
                    <textarea v-model="citaSel.notas_internas" rows="4" 
                              placeholder="Escribe aquí observaciones clínicas o detalles de la atención..."
                              class="w-full bg-slate-950/50 border border-slate-700 rounded-2xl p-4 text-white outline-none focus:ring-2 focus:ring-emerald-500 transition-all font-medium"></textarea>
                    <div class="flex justify-end mt-2">
                        <button @click="guardarNotas(citaSel)" class="text-emerald-400 text-xs font-bold hover:underline">GUARDAR NOTAS</button>
                    </div>
                </div>

                <div class="flex gap-4 pt-4 border-t border-slate-700/50">
                    <button v-if="citaSel.estado === 'confirmada'" @click="cambiarEstado(citaSel, 'en_curso')" class="flex-grow py-4 bg-emerald-500 hover:bg-emerald-400 text-white rounded-2xl font-black text-sm uppercase transition-all flex items-center justify-center gap-3">
                        <i class="fas fa-play"></i> Iniciar Atención
                    </button>
                    <button v-if="citaSel.estado === 'en_curso'" @click="cambiarEstado(citaSel, 'completada')" class="flex-grow py-4 bg-blue-500 hover:bg-blue-400 text-white rounded-2xl font-black text-sm uppercase transition-all flex items-center justify-center gap-3">
                        <i class="fas fa-check"></i> Finalizar Atención
                    </button>
                    <button v-if="['pendiente','confirmada'].includes(citaSel.estado)" @click="cambiarEstado(citaSel, 'cancelada')" class="px-6 py-4 bg-slate-800 hover:bg-rose-500/20 text-rose-400 rounded-2xl font-black text-sm uppercase transition-all border border-slate-700">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO BLOQUEO -->
    <div v-if="modalBloqueo" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
        <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-3xl shadow-2xl p-8 animate-in fade-in zoom-in duration-200">
            <h2 class="text-2xl font-bold text-white mb-6">Bloquear Horario</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Motivo</label>
                    <input type="text" v-model="nuevoBloqueo.motivo" placeholder="Ej: Break, Almuerzo, Urgencia"
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Hora Inicio</label>
                        <input type="time" v-model="nuevoBloqueo.hora_inicio" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Hora Fin</label>
                        <input type="time" v-model="nuevoBloqueo.hora_fin" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white">
                    </div>
                </div>
                <div class="pt-6 flex gap-3">
                    <button @click="modalBloqueo = false" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-bold hover:bg-slate-700 transition-all">Cancelar</button>
                    <button @click="guardarBloqueo" class="flex-1 py-3 bg-emerald-500 text-white rounded-xl font-bold hover:bg-emerald-400 transition-all">Bloquear</button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts Section -->
@section('scripts')
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script>
const { createApp } = Vue;

createApp({
    data() {
        return {
            view: 'dia',
            fecha: new Date().toISOString().split('T')[0],
            today: new Date().toISOString().split('T')[0],
            loading: false,
            recurso: {
                horarios: [],
                bloqueos: []
            },
            citasDia: [],
            diasSemana: [],
            horariosEdit: [],
            // Modales
            modalCita: null,
            citaSel: {},
            modalBloqueo: false,
            nuevoBloqueo: {
                motivo: '',
                fecha_inicio: '',
                hora_inicio: '13:00',
                hora_fin: '14:00'
            }
        }
    },
    computed: {
        sortedCitas() {
            return [...this.citasDia].sort((a,b) => a.hora_inicio.localeCompare(b.hora_inicio));
        },
        labelSemana() {
            if(!this.diasSemana.length) return '';
            const f1 = this.diasSemana[0].fecha;
            const f2 = this.diasSemana[6].fecha;
            return `${this.formatFechaHumana(f1, false)} — ${this.formatFechaHumana(f2, false)}`;
        }
    },
    async mounted() {
        await this.fetchRecurso();
        this.fetchDia();
    },
    methods: {
        async fetchRecurso() {
            try {
                const res = await fetch('/api/agenda/mi/recurso');
                this.recurso = await res.json();
                this.prepareHorariosEdit();
            } catch (e) { console.error("Error fetching recurso", e); }
        },
        async fetchDia() {
            this.loading = true;
            try {
                const res = await fetch(`/api/agenda/mi/dia?fecha=${this.fecha}`);
                const data = await res.json();
                this.citasDia = data.citas || [];
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la agenda del día', 'error');
            } finally { this.loading = false; }
        },
        async fetchSemana() {
            this.loading = true;
            try {
                const res = await fetch(`/api/agenda/mi/semana?fecha=${this.fecha}`);
                this.diasSemana = await res.json();
            } catch (e) {
                Swal.fire('Error', 'No se pudo cargar la semana', 'error');
            } finally { this.loading = false; }
        },
        prepareHorariosEdit() {
            // Asegurar que hay 7 días
            const base = [];
            for (let i = 1; i <= 7; i++) {
                const h = this.recurso.horarios.find(x => x.dia_semana === i) || {
                    dia_semana: i, 
                    hora_inicio: '09:00', 
                    hora_fin: '18:00', 
                    activo: false,
                    duracion_slot_min: 30
                };
                base.push({...h});
            }
            this.horariosEdit = base;
        },
        async guardarHorarios() {
            try {
                const res = await fetch('/api/agenda/mi/horarios', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ horarios: this.horariosEdit })
                });
                if(res.ok) {
                    Swal.fire('Guardado', 'Tus horarios se actualizaron con éxito', 'success');
                    this.fetchRecurso();
                }
            } catch (e) { Swal.fire('Error', 'No se pudo guardar', 'error'); }
        },

        // Bloqueos
        abrirModalBloqueo() {
            this.nuevoBloqueo.fecha_inicio = this.fecha;
            this.modalBloqueo = true;
        },
        async guardarBloqueo() {
            try {
                const res = await fetch('/api/agenda/mi/bloqueo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.nuevoBloqueo)
                });
                if(res.ok) {
                    this.modalBloqueo = false;
                    this.fetchRecurso(); // Recargar bloqueos
                    Swal.fire('Listo', 'Horario bloqueado con éxito', 'success');
                }
            } catch (e) { Swal.fire('Error', 'No se pudo bloquear', 'error'); }
        },
        async eliminarBloqueo(id) {
            const { isConfirmed } = await Swal.fire({
                title: '¿Eliminar bloqueo?',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, eliminar'
            });
            if(!isConfirmed) return;
            try {
                await fetch(`/api/agenda/mi/bloqueo/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.fetchRecurso();
            } catch (e) { console.error(e); }
        },

        // Citas
        verDetalleCita(cita) {
            this.citaSel = { ...cita };
            this.modalCita = true;
        },
        async cambiarEstado(cita, nuevoEstado) {
            try {
                const res = await fetch(`/api/agenda/mi/citas/${cita.id}/estado`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ estado: nuevoEstado })
                });
                if(res.ok) {
                    const updated = await res.json();
                    if(this.citaSel.id === cita.id) this.citaSel.estado = updated.estado;
                    this.fetchDia();
                    if(this.view === 'semana') this.fetchSemana();
                    if(nuevoEstado === 'completada' && this.modalCita) this.modalCita = false;
                }
            } catch (e) { Swal.fire('Error', 'No se pudo cambiar el estado', 'error'); }
        },
        async guardarNotas(cita) {
            try {
                await fetch(`/api/agenda/mi/citas/${cita.id}/notas`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ notas_internas: cita.notas_internas })
                });
                Toast.fire({ icon: 'success', title: 'Notas guardadas' });
            } catch (e) { console.error(e); }
        },

        // Nav Semana
        prevSemana() {
            const d = new Date(this.fecha);
            d.setDate(d.getDate() - 7);
            this.fecha = d.toISOString().split('T')[0];
            this.fetchSemana();
        },
        nextSemana() {
            const d = new Date(this.fecha);
            d.setDate(d.getDate() + 7);
            this.fecha = d.toISOString().split('T')[0];
            this.fetchSemana();
        },

        // Helpers
        formatHora(h) { return h ? h.substring(0, 5) : ''; },
        formatFechaHumana(f, conDia = true) {
            return new Date(f + 'T00:00:00').toLocaleDateString('es-CL', {
                weekday: conDia ? 'long' : undefined,
                day: 'numeric', month: 'short', year: 'numeric'
            });
        },
        formatDiaNombre(f) { return new Date(f + 'T00:00:00').toLocaleDateString('es-CL', { weekday: 'short' }); },
        formatDiaNumero(f) { return new Date(f + 'T00:00:00').getDate(); },
        getDiaNombre(n) {
            return ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'][n-1];
        },
        esHoy(f) { return f === this.today; },
        getEstadoColor(est) {
            return {
                'pendiente': '#fbbf24',
                'confirmada': '#10b981',
                'en_curso': '#3b82f6',
                'completada': '#6366f1',
                'cancelada': '#f43f5e'
            }[est] || '#94a3b8';
        },
        getEstadoBadgeClass(est) {
            return {
                'pendiente': 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                'confirmada': 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                'en_curso': 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                'completada': 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30',
                'cancelada': 'bg-rose-500/20 text-rose-400 border-rose-500/30'
            }[est] || '';
        }
    },
    watch: {
        view(newV) {
            if(newV === 'dia') this.fetchDia();
            if(newV === 'semana') this.fetchSemana();
        }
    }
}).mount('#miAgendaApp');

const Toast = Swal.mixin({
  toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
  timerProgressBar: true, background: '#1e293b', color: '#fff'
});
</script>
@endsection
