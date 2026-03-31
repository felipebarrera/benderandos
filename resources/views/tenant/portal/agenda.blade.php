<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva tu Cita | BenderAnd</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #3b82f6; }
        body { font-family: 'Outfit', sans-serif; overflow-x: hidden; }
        .font-mono { font-family: 'IBM Plex Mono', monospace; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
        .step-active { background: var(--primary); color: white; scale: 1.1; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-full flex flex-col" x-data="publicAgenda('{{ $tenantSlug }}')">

    {{-- Navbar --}}
    <nav class="glass sticky top-0 z-40 border-b border-slate-200 px-4 py-4">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <h1 class="text-xl font-bold tracking-tight text-slate-800" x-text="config.landing_titulo || 'Reserva Online'"></h1>
            <div class="text-[10px] uppercase tracking-widest font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-full">ERP BenderAnd</div>
        </div>
    </nav>

    <main class="flex-1 max-w-4xl mx-auto w-full p-4 md:py-10">
        
        {{-- Progress Steps --}}
        <div class="flex justify-between items-center mb-10 max-w-sm mx-auto">
             <template x-for="s in [1,2,3]">
                 <div class="flex items-center">
                    <div :class="paso >= s ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-slate-200 text-slate-400'"
                         class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300"
                         x-text="s"></div>
                    <div x-show="s < 3" class="w-16 h-1 bg-slate-200 mx-2 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 transition-all duration-500" :style="'width: ' + (paso > s ? '100%' : '0%')"></div>
                    </div>
                 </div>
             </template>
        </div>

        {{-- Paso 1: Selección de Servicio y Profesional --}}
        <div x-show="paso === 1" x-transition x-cloak class="space-y-8">
            <div>
                <h2 class="text-2xl font-bold mb-4">¿En qué podemos ayudarte?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <template x-for="s in servicios" :key="s.id">
                        <div @click="form.servicio_id = s.id"
                             :class="form.servicio_id === s.id ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white hover:border-blue-300'"
                             class="p-4 border-2 rounded-2xl cursor-pointer transition-all flex items-center gap-4 group">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-xl group-hover:bg-blue-100 transition-colors">✂️</div>
                            <div class="flex-1">
                                <h3 class="font-bold text-slate-800" x-text="s.nombre"></h3>
                                <p class="text-xs text-slate-500" x-text="s.duracion_min + ' minutos'"></p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono font-bold text-blue-600">$<span x-text="formatMonto(s.precio)"></span></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="form.servicio_id">
                <h2 class="text-2xl font-bold mb-4">Elige con quién</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <template x-for="r in recursos" :key="r.id">
                        <div @click="form.recurso_id = r.id; form.recurso_nombre = r.nombre"
                             :class="form.recurso_id === r.id ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-white'"
                             class="p-4 border-2 rounded-2xl cursor-pointer text-center transition-all">
                             <div class="w-16 h-16 rounded-full mx-auto mb-3 flex items-center justify-center text-3xl shadow-inner overflow-hidden" :style="'background-color: ' + r.color + '20'">
                                 <span class="opacity-80">👤</span>
                             </div>
                             <h3 class="font-bold text-sm truncate" x-text="r.nombre"></h3>
                             <p class="text-[10px] text-slate-400 uppercase tracking-widest font-mono" x-text="r.tipo"></p>
                        </div>
                    </template>
                </div>
            </div>

            <div class="text-center pt-6" x-show="form.servicio_id && form.recurso_id">
                <button @click="siguientePaso()" class="bg-blue-600 text-white px-10 py-4 rounded-2xl font-bold text-lg shadow-xl shadow-blue-200 hover:brightness-110 active:scale-95 transition-all">
                    Continuar a disponibilidad
                </button>
            </div>
        </div>

        {{-- Paso 2: Calendario y Horario --}}
        <div x-show="paso === 2" x-transition x-cloak class="space-y-8">
             <div class="flex flex-col md:flex-row gap-8">
                 <div class="flex-1">
                     <h2 class="text-2xl font-bold mb-4">Selecciona el día</h2>
                     <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm">
                         <input type="date" x-model="form.fecha" @change="cargarSlots()"
                                :min="new Date().toISOString().split('T')[0]"
                                class="w-full text-lg font-bold font-mono p-4 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-blue-500 outline-none">
                     </div>
                 </div>
                 
                 <div class="flex-1">
                     <h2 class="text-2xl font-bold mb-4">Horarios disponibles</h2>
                     <div class="grid grid-cols-3 gap-2">
                         <template x-for="slot in slots" :key="slot.inicio">
                             <div @click="form.fecha_inicio = slot.inicio; form.fecha_fin = slot.fin; slotSeleccionado = slot.label"
                                  :class="form.fecha_inicio === slot.inicio ? 'bg-blue-600 text-white scale-105 shadow-lg shadow-blue-200' : 'bg-white text-slate-700 border border-slate-200 hover:border-blue-400'"
                                  class="p-3 rounded-xl cursor-pointer text-center font-mono font-bold transition-all"
                                  x-text="slot.label"></div>
                         </template>
                         <div x-show="slots.length === 0 && !cargando" class="col-span-3 py-10 text-center text-slate-400">
                             No hay horas disponibles para este día.
                         </div>
                         <div x-show="cargando" class="col-span-3 py-10 text-center">
                             <div class="animate-spin inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="flex justify-between items-center pt-8 border-t border-slate-200">
                 <button @click="paso = 1" class="text-slate-500 font-bold hover:underline">Volver</button>
                 <button @click="siguientePaso()" x-show="form.fecha_inicio" class="bg-blue-600 text-white px-10 py-4 rounded-2xl font-bold text-lg shadow-xl shadow-blue-200 hover:brightness-110 active:scale-95 transition-all">
                    Ingresar mis datos
                 </button>
             </div>
        </div>

        {{-- Paso 3: Confirmación y Datos --}}
        <div x-show="paso === 3" x-transition x-cloak class="max-w-md mx-auto space-y-8">
            <div class="bg-white p-6 rounded-3xl border border-blue-100 shadow-xl space-y-4">
                <h2 class="text-xl font-bold border-b border-slate-50 pb-3">Resumen de tu cita</h2>
                <div class="space-y-4">
                    <div class="flex justify-between">
                         <span class="text-slate-400 text-sm">Servicio</span>
                         <span class="font-bold text-slate-700" x-text="servicios.find(s => s.id === form.servicio_id)?.nombre"></span>
                    </div>
                    <div class="flex justify-between">
                         <span class="text-slate-400 text-sm">Atiende</span>
                         <span class="font-bold text-slate-700" x-text="form.recurso_nombre"></span>
                    </div>
                    <div class="flex justify-between px-3 py-2 bg-blue-50 rounded-xl text-blue-700 border border-blue-100">
                         <span class="font-bold">Día y Hora</span>
                         <span class="font-mono font-bold" x-text="form.fecha + ' @ ' + slotSeleccionado"></span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-xl font-bold">Tus datos de contacto</h2>
                <div class="space-y-3">
                    <input type="text" placeholder="Nombre completo" x-model="form.cliente_nombre"
                           class="w-full bg-white border border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm">
                    <input type="tel" placeholder="Teléfono / WhatsApp" x-model="form.cliente_telefono"
                           class="w-full bg-white border border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm">
                    <input type="email" placeholder="Email (opcional)" x-model="form.cliente_email"
                           class="w-full bg-white border border-slate-200 rounded-2xl p-4 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm">
                    <textarea placeholder="¿Alguna nota o indicación adicional?" x-model="form.notas_cliente"
                              class="w-full bg-white border border-slate-200 rounded-2xl p-4 min-h-[100px] focus:ring-2 focus:ring-blue-500 outline-none shadow-sm"></textarea>
                </div>
            </div>

            <button @click="confirmarReserva()" :disabled="cargando"
                    class="w-full py-5 rounded-3xl bg-blue-600 text-white font-black text-xl shadow-2xl shadow-blue-300 hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-3">
                <span x-show="!cargando">✅ CONFIRMAR AGENDAMIENTO</span>
                <div x-show="cargando" class="animate-spin w-6 h-6 border-4 border-white border-t-transparent rounded-full"></div>
            </button>
            <p class="text-[10px] text-center text-slate-400 px-10">Al confirmar, el sistema validará tu hora. Si el comercio requiere confirmación previa, te avisaremos vía WhatsApp.</p>
        </div>

        {{-- Pantalla Final: Éxito --}}
        <div x-show="paso === 4" x-transition x-cloak class="text-center space-y-8 py-10">
            <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-5xl mx-auto shadow-lg shadow-green-100">✔</div>
            <div class="space-y-2">
                <h2 class="text-3xl font-black text-slate-800">¡Todo Listo!</h2>
                <p class="text-lg text-slate-500 max-w-sm mx-auto">Tu reserva ha sido registrada correctamente. Hemos notificado al local.</p>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm inline-block px-10">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest mb-1">Código de Seguimiento</p>
                <p class="font-mono text-xl font-bold text-blue-600" x-text="confirmacion.uuid?.split('-')[0].toUpperCase()"></p>
            </div>
            <div class="pt-6">
                 <a href="/" class="text-blue-600 font-bold hover:underline">Volver al inicio</a>
            </div>
        </div>

    </main>

    <footer class="p-8 text-center text-slate-400 text-xs border-t border-slate-200 mt-10">
        &copy; 2026 ERP BenderAnd &middot; Sistema de Gestión Inteligente
    </footer>

    <script>
    function publicAgenda(tenantSlug) {
        return {
            paso: 1,
            tenantSlug: tenantSlug,
            config: {},
            servicios: [],
            recursos: [],
            slots: [],
            pasoMax: 1,
            cargando: false,
            slotSeleccionado: '',
            form: {
                recurso_id: '',
                recurso_nombre: '',
                servicio_id: '',
                fecha: new Date().toISOString().split('T')[0],
                fecha_inicio: '',
                fecha_fin: '',
                cliente_nombre: '',
                cliente_telefono: '',
                cliente_email: '',
                notas_cliente: ''
            },
            confirmacion: {},

            async init() {
                // Las rutas públicas no requieren token
                this.cargando = true;
                const [cfg, res, ser] = await Promise.all([
                    fetch(`/api/agenda/config`).then(r => r.json()), // Alguna lógica en controller podría fallar si no hay session, pero asumimos pública o default
                    fetch(`/api/agenda/recursos`).then(r => r.json()), 
                    fetch(`/api/agenda/servicios`).then(r => r.json())
                ]);
                this.config = cfg;
                this.recursos = res;
                this.servicios = ser;
                this.cargando = false;
            },

            siguientePaso() {
                if (this.paso === 1 && this.form.servicio_id && this.form.recurso_id) {
                    this.paso = 2;
                    this.cargarSlots();
                } else if (this.paso === 2 && this.form.fecha_inicio) {
                    this.paso = 3;
                }
            },

            async cargarSlots() {
                this.cargando = true;
                const dur = this.servicios.find(s => s.id == this.form.servicio_id)?.duracion_min;
                const url = `/api/public/agenda/disponibilidad?recurso_id=${this.form.recurso_id}&fecha=${this.form.fecha}&servicio_id=${this.form.servicio_id}`;
                const res = await fetch(url);
                this.slots = await res.json();
                this.cargando = false;
            },

            async confirmarReserva() {
                if (!this.form.cliente_nombre || !this.form.cliente_telefono) {
                    alert("Por favor completa tu nombre y teléfono.");
                    return;
                }
                
                this.cargando = true;
                try {
                    const res = await fetch(`/api/public/agenda/reservar`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.form)
                    });
                    
                    if (res.ok) {
                        this.confirmacion = await res.json();
                        this.paso = 4;
                    } else {
                        const err = await res.json();
                        alert(err.error || "Algo salió mal.");
                    }
                } catch (e) {
                    alert("Error de conexión.");
                } finally {
                    this.cargando = false;
                }
            },

            formatMonto(n) { return new Intl.NumberFormat('es-CL').format(n); }
        }
    }
    </script>
</body>
</html>
