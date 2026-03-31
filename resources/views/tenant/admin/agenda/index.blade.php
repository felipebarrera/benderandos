@extends('tenant.layout')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold font-mono uppercase tracking-widest text-[#e8e8f0]">Configuración Agenda (M08)</h1>
            <p class="text-sm text-[#7878a0] mt-1">Gestiona recursos, horarios y servicios disponibles.</p>
        </div>
        <button onclick="abrirModalRecurso()" class="bg-[#00e5a0] text-black px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-[#00b87c] transition-all flex items-center gap-2">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
            Nuevo Recurso
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar: Recursos -->
        <div class="lg:col-span-1 space-y-4" id="recursosList">
            <!-- JS populated -->
        </div>

        <!-- Main Content: Horarios y Servicios -->
        <div id="recursoDetalle" class="lg:col-span-2 hidden">
            <div class="bg-[#111115] border border-[#1e1e28] rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-[#1e1e28] flex items-center justify-between bg-[#14141a]">
                    <div class="flex items-center gap-4">
                        <div id="detColor" class="w-10 h-10 rounded-full flex items-center justify-center text-black font-bold text-lg"></div>
                        <div>
                            <h2 id="detNombre" class="text-lg font-bold text-white"></h2>
                            <span id="detTipo" class="text-[10px] font-mono border border-[#2a2a3a] px-2 py-0.5 rounded uppercase text-[#7878a0]"></span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="eliminarRecurso()" class="text-xs text-red-500 hover:text-red-400 font-bold px-3 py-1.5 border border-red-500/20 rounded-lg hover:bg-red-500/5 transition-all">Eliminar</button>
                    </div>
                </div>

                <div class="p-6 space-y-8">
                    <!-- Horarios -->
                    <section>
                        <h3 class="text-xs font-bold font-mono text-[#3a3a55] uppercase tracking-widest mb-4">Horarios Operativos</h3>
                        <div class="grid grid-cols-1 gap-3" id="horariosGrid">
                            <!-- JS populated -->
                        </div>
                        <button onclick="guardarHorarios()" class="mt-4 bg-white/5 border border-white/10 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-white/10">
                            Guardar Cambios Horario
                        </button>
                    </section>

                    <!-- Servicios -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold font-mono text-[#3a3a55] uppercase tracking-widest">Servicios Ofrecidos</h3>
                            <button onclick="abrirModalServicio()" class="text-[#00e5a0] text-xs font-bold hover:underline">+ Agregar Servicio</button>
                        </div>
                        <div class="grid grid-cols-1 gap-2" id="serviciosGrid">
                            <!-- JS populated -->
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Recurso -->
<div id="modalRecurso" class="fixed inset-0 z-50 bg-black/80 hidden flex items-center justify-center p-4">
    <div class="bg-[#111115] border border-[#2a2a3a] rounded-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-6 font-mono">Nuevo Recurso</h3>
        <div class="space-y-4">
            <div>
                <label class="text-[10px] font-bold text-[#7878a0] uppercase mb-1 block">Nombre</label>
                <input type="text" id="rNombre" class="w-full bg-[#18181e] border border-[#2a2a3a] rounded-xl px-4 py-2.5 text-white outline-none focus:border-[#00e5a0]">
            </div>
            <div>
                <label class="text-[10px] font-bold text-[#7878a0] uppercase mb-1 block">Tipo</label>
                <select id="rTipo" class="w-full bg-[#18181e] border border-[#2a2a3a] rounded-xl px-4 py-2.5 text-white outline-none">
                    <option value="profesional">Persona / Profesional</option>
                    <option value="recurso">Espacio / Objeto</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-bold text-[#7878a0] uppercase mb-1 block">Especialidad / Descripción</label>
                <input type="text" id="rEsp" class="w-full bg-[#18181e] border border-[#2a2a3a] rounded-xl px-4 py-2.5 text-white outline-none">
            </div>
            <div>
                <label class="text-[10px] font-bold text-[#7878a0] uppercase mb-1 block">Color Identificador</label>
                <input type="color" id="rColor" value="#00e5a0" class="w-full h-10 bg-transparent border-none">
            </div>
        </div>
        <div class="flex gap-3 mt-8">
            <button onclick="cerrarModalRecurso()" class="flex-1 px-4 py-2.5 rounded-xl border border-[#2a2a3a] text-[#7878a0] font-bold text-sm">Cancelar</button>
            <button onclick="guardarRecurso()" class="flex-2 px-4 py-2.5 rounded-xl bg-[#00e5a0] text-black font-bold text-sm">Crear Recurso</button>
        </div>
    </div>
</div>

<!-- Modal Servicio -->
<div id="modalServicio" class="fixed inset-0 z-50 bg-black/80 hidden flex items-center justify-center p-4">
    <div class="bg-[#111115] border border-[#2a2a3a] rounded-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-6 font-mono">Nuevo Servicio</h3>
        <div class="space-y-4">
            <div>
                <label class="text-[10px] font-bold text-[#7878a0] uppercase mb-1 block">Nombre del Servicio</label>
                <input type="text" id="sNombre" placeholder="Ej: Consulta Médica" class="w-full bg-[#18181e] border border-[#2a2a3a] rounded-xl px-4 py-2.5 text-white outline-none">
            </div>
            <div>
                <label class="text-[10px] font-bold text-[#7878a0] uppercase mb-1 block">Duración (minutos)</label>
                <input type="number" id="sDur" value="30" class="w-full bg-[#18181e] border border-[#2a2a3a] rounded-xl px-4 py-2.5 text-white outline-none">
            </div>
            <div>
                <label class="text-[10px] font-bold text-[#7878a0] uppercase mb-1 block">Precio ($)</label>
                <input type="number" id="sPrecio" value="0" class="w-full bg-[#18181e] border border-[#2a2a3a] rounded-xl px-4 py-2.5 text-white outline-none">
            </div>
        </div>
        <div class="flex gap-3 mt-8">
            <button onclick="cerrarModalServicio()" class="flex-1 px-4 py-2.5 rounded-xl border border-[#2a2a3a] text-[#7878a0] font-bold text-sm">Cancelar</button>
            <button onclick="guardarServicio()" class="flex-2 px-4 py-2.5 rounded-xl bg-[#00e5a0] text-black font-bold text-sm">Crear Servicio</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let recursos = [];
let recursoSeleccionado = null;

async function cargarRecursos() {
    const res = await fetch('/api/agenda/recursos');
    recursos = await res.json();
    renderRecursos();
}

function renderRecursos() {
    const list = document.getElementById('recursosList');
    list.innerHTML = recursos.map(r => `
        <div onclick="seleccionarRecurso(${r.id})" class="p-4 rounded-2xl border ${recursoSeleccionado?.id === r.id ? 'border-[#00e5a0] bg-[#00e5a0]/5' : 'border-[#1e1e28] bg-[#111115]'} cursor-pointer hover:border-[#2a2a3a] transition-all flex items-center justify-between group">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-black font-bold text-xs" style="background:${r.color}">${r.nombre[0]}</div>
                <div>
                    <div class="text-sm font-bold text-white">${r.nombre}</div>
                    <div class="text-[10px] text-[#7878a0] uppercase font-mono">${r.tipo}</div>
                </div>
            </div>
            <svg class="text-[#3a3a55] group-hover:translate-x-1 transition-transform" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
        </div>
    `).join('');
}

function seleccionarRecurso(id) {
    recursoSeleccionado = recursos.find(r => r.id === id);
    renderRecursos();
    document.getElementById('recursoDetalle').classList.remove('hidden');
    document.getElementById('detNombre').textContent = recursoSeleccionado.nombre;
    document.getElementById('detTipo').textContent = recursoSeleccionado.tipo;
    document.getElementById('detColor').style.background = recursoSeleccionado.color;
    document.getElementById('detColor').textContent = recursoSeleccionado.nombre[0];
    
    renderHorarios();
    renderServicios();
}

function renderHorarios() {
    const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    const grid = document.getElementById('horariosGrid');
    const map = {};
    (recursoSeleccionado.horarios || []).forEach(h => map[h.dia_semana] = h);

    grid.innerHTML = dias.map((dia, i) => {
        const dNum = i + 1;
        const h = map[dNum] || { hora_inicio: '09:00', hora_fin: '18:00', activo: false };
        return `
            <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl border border-white/5">
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="h-act-${dNum}" ${h.activo ? 'checked' : ''} class="w-4 h-4 rounded border-[#2a2a3a] bg-[#18181e] text-[#00e5a0]">
                    <span class="text-sm font-medium text-white">${dia}</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="time" id="h-ini-${dNum}" value="${h.hora_inicio}" class="bg-black/20 border border-white/10 rounded px-2 py-1 text-xs text-white outline-none">
                    <span class="text-[#3a3a55]">to</span>
                    <input type="time" id="h-fin-${dNum}" value="${h.hora_fin}" class="bg-black/20 border border-white/10 rounded px-2 py-1 text-xs text-white outline-none">
                </div>
            </div>
        `;
    }).join('');
}

async function guardarHorarios() {
    const horarios = [];
    for(let d=1; d<=7; d++) {
        horarios.push({
            dia_semana: d,
            hora_inicio: document.getElementById(`h-ini-${d}`).value,
            hora_fin: document.getElementById(`h-fin-${d}`).value,
            activo: document.getElementById(`h-act-${d}`).checked ? 1 : 0
        });
    }

    const res = await fetch(`/api/agenda/recursos/${recursoSeleccionado.id}/horarios`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ horarios })
    });
    
    if(res.ok) {
        alert('Horarios actualizados');
        cargarRecursos();
    }
}

function renderServicios() {
    const grid = document.getElementById('serviciosGrid');
    grid.innerHTML = (recursoSeleccionado.servicios || []).map(s => `
        <div class="flex items-center justify-between p-3 border border-[#1e1e28] rounded-xl hover:bg-white/5">
            <div>
                <div class="text-sm font-bold text-white">${s.nombre}</div>
                <div class="text-[10px] text-[#7878a0] font-mono">${s.duracion_min} MIN • $${s.precio.toLocaleString()}</div>
            </div>
            <button onclick="eliminarServicio(${s.id})" class="text-[#3a3a55] hover:text-red-500">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    `).join('');
}

function abrirModalRecurso() { document.getElementById('modalRecurso').classList.remove('hidden'); }
function cerrarModalRecurso() { document.getElementById('modalRecurso').classList.add('hidden'); }

async function guardarRecurso() {
    const data = {
        nombre: document.getElementById('rNombre').value,
        tipo: document.getElementById('rTipo').value,
        especialidad: document.getElementById('rEsp').value,
        color: document.getElementById('rColor').value,
    };
    
    const res = await fetch('/api/agenda/recursos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(data)
    });
    
    if(res.ok) {
        cerrarModalRecurso();
        cargarRecursos();
    }
}

function abrirModalServicio() { document.getElementById('modalServicio').classList.remove('hidden'); }
function cerrarModalServicio() { document.getElementById('modalServicio').classList.add('hidden'); }

async function guardarServicio() {
    const data = {
        nombre: document.getElementById('sNombre').value,
        duracion_min: document.getElementById('sDur').value,
        precio: document.getElementById('sPrecio').value,
    };
    
    const res = await fetch(`/api/agenda/recursos/${recursoSeleccionado.id}/servicios`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(data)
    });
    
    if(res.ok) {
        cerrarModalServicio();
        cargarRecursos().then(() => seleccionarRecurso(recursoSeleccionado.id));
    }
}

async function eliminarServicio(id) {
    if(!confirm('¿Eliminar este servicio?')) return;
    const res = await fetch(`/api/agenda/servicios/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    if(res.ok) {
        recursos = recursos.map(r => {
            if(r.id === recursoSeleccionado.id) {
                r.servicios = r.servicios.filter(s => s.id !== id);
            }
            return r;
        });
        seleccionarRecurso(recursoSeleccionado.id);
    }
}

async function eliminarRecurso() {
    if(!recursoSeleccionado) return;
    if(!confirm('¿Eliminar este recurso y todos sus servicios/horarios?')) return;
    const res = await fetch(`/api/agenda/recursos/${recursoSeleccionado.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });
    if(res.ok) {
        document.getElementById('recursoDetalle').classList.add('hidden');
        cargarRecursos();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarRecursos();
});
</script>
@endpush
@endsection


