# BENDERAND — H25: AGENDA OPERADOR Y RECEPCIONISTA
**Calendario completo · Citas desde landing · Layout fix · WebPay**
*Marzo 2026 · Antigravity*

---

## PROBLEMA REAL QUE SE RESUELVE

Tres síntomas en la vista `/pos/agenda` (rol cajero/operario — demo-medico):

1. **Sin calendario** — solo muestra "hoy". No hay forma de navegar a días pasados o futuros, ni vista semana o mes.
2. **Cita creada desde el landing público no aparece** en la lista del operador. El operador que tiene asignada esa cita debería verla.
3. **Layout roto** — el sidebar no ocupa el 100% de la altura visible. El botón "Cerrar sesión" queda flotando fuera del viewport en vez de pegado al fondo del sidebar.
4. **Recepcionista (cajero) no ve el pago WebPay** asociado a una cita ni los datos completos del paciente que agendó por el portal.

---

## CONTEXTO TÉCNICO

### Archivos involucrados

```
benderandos/
├── resources/views/
│   ├── tenant/pos/agenda.blade.php          ← vista del operador/recepcionista
│   └── tenant/pos/profesional.blade.php     ← vista del profesional (médico)
├── app/Http/Controllers/Tenant/
│   ├── AgendaController.php                 ← GET /api/agenda/mi/dia
│   └── ClientePortalController.php          ← crea citas desde landing público
├── routes/tenant.php
│   └── GET /api/agenda/mi/dia
│   └── GET /api/agenda/calendario           ← CREAR si no existe
│   └── GET /api/citas/{id}                  ← detalle con pago
└── app/Models/Tenant/
    ├── Cita.php (o similar — verificar nombre real)
    └── PagoSubscription.php / Pago.php
```

### Estado actual del HTML del operador

El `prof-shell` usa `display:flex; height: calc(100vh - 56px)` pero el `prof-nav` no tiene `height:100%` ni `position:sticky/fixed`. El resultado es que el sidebar se expande con el contenido y el footer de logout queda fuera del viewport. Se confirma en el HTML recibido:

```css
/* ACTUAL — roto */
.prof-nav {
    width: 240px; min-width: 240px; background: #0d0d11;
    display: flex; flex-direction: column; flex-shrink: 0;
    /* FALTA: height: 100%; overflow: hidden; */
}
/* El footer del nav tiene: */
<div class="p-3 border-t" style="border-color:#1e1e28; flex-shrink:0;">
/* flex-shrink:0 está bien PERO el nav no tiene overflow:hidden en el área de items */
```

---

## FIX 1 — LAYOUT DEL SIDEBAR (CSS)

### Diagnóstico exacto

El `.prof-nav` tiene `display:flex; flex-direction:column` pero `.prof-nav-items` no tiene `overflow-y:auto; flex:1`. Cuando el contenido de items crece, empuja el footer fuera del viewport.

### Fix en `agenda.blade.php` (o donde esté el CSS)

```css
/* REEMPLAZAR el bloque .prof-nav y .prof-nav-items */

.prof-nav {
    width: 240px;
    min-width: 240px;
    background: #0d0d11;
    border-right: 1px solid #1e1e28;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    height: 100%;          /* ← AGREGAR */
    overflow: hidden;      /* ← AGREGAR */
}

.prof-nav-items {
    padding: 8px 0;
    flex: 1;               /* ya existe, bien */
    overflow-y: auto;      /* ya existe, bien */
    min-height: 0;         /* ← AGREGAR — critical para flex overflow */
}

/* El footer de logout ya tiene flex-shrink:0, solo necesita que el nav
   tenga height:100% y overflow:hidden para que funcione */
```

### Fix en el shell

```css
.prof-shell {
    display: flex;
    height: calc(100vh - 56px);  /* 56px = topbar mobile */
    background: #08080a;
    overflow: hidden;
}

/* En desktop sin topbar mobile, ajustar: */
@media (min-width: 768px) {
    .prof-shell {
        height: 100vh;
    }
}
```

---

## FIX 2 — CALENDARIO CON VISTAS DÍA / SEMANA / MES

### Nuevo endpoint necesario

**Si no existe**, agregar en `AgendaController.php`:

```php
// GET /api/agenda/calendario?desde=2026-03-01&hasta=2026-03-31
public function calendario(Request $request)
{
    $desde = $request->input('desde', now()->startOfMonth()->toDateString());
    $hasta = $request->input('hasta', now()->endOfMonth()->toDateString());

    // El operador ve sus propias citas (recurso_id del usuario logueado)
    // El admin ve todas
    $query = Cita::with(['cliente', 'servicio', 'pago'])
        ->whereBetween('fecha', [$desde, $hasta]);

    if (!auth()->user()->hasRole('admin')) {
        $query->where('recurso_id', auth()->user()->recurso_id);
    }

    return response()->json($query->orderBy('fecha')->orderBy('hora_inicio')->get());
}
```

Agregar en `routes/tenant.php`:
```php
Route::get('/api/agenda/calendario', [AgendaController::class, 'calendario']);
```

### JS del calendario en `agenda.blade.php`

Reemplazar la sección de agenda con este sistema de vistas:

```javascript
// ══════════════════════════════════════════════════════
// ESTADO DEL CALENDARIO
// ══════════════════════════════════════════════════════
let vistaActual = 'semana'; // 'dia' | 'semana' | 'mes'
let fechaRef = new Date();   // fecha de referencia para la vista

// ══════════════════════════════════════════════════════
// TOOLBAR DEL CALENDARIO
// ══════════════════════════════════════════════════════
function renderToolbarCalendario() {
    return `
    <div style="display:flex;align-items:center;gap:8px;padding:10px 16px;background:#111115;border-bottom:1px solid #1e1e28;flex-shrink:0;flex-wrap:wrap;">
        <button onclick="navCalendario(-1)" class="btn-sm-ghost">‹ Anterior</button>
        <button onclick="irHoy()" class="btn-sm-ghost" style="font-weight:700;color:#00e5a0;border-color:rgba(0,229,160,.3);">Hoy</button>
        <button onclick="navCalendario(1)" class="btn-sm-ghost">Siguiente ›</button>
        <span id="labelFechaVista" style="font-family:'IBM Plex Mono',monospace;font-size:12px;font-weight:700;color:#e8e8f0;margin:0 auto;"></span>
        <div style="display:flex;gap:4px;">
            <button onclick="cambiarVista('dia')" id="btnVistaDia" class="btn-sm-ghost">Día</button>
            <button onclick="cambiarVista('semana')" id="btnVistaSemana" class="btn-sm-ghost" style="border-color:rgba(0,229,160,.3);color:#00e5a0;">Semana</button>
            <button onclick="cambiarVista('mes')" id="btnVistaMes" class="btn-sm-ghost">Mes</button>
        </div>
        <button onclick="abrirModalNuevaCita()" style="background:#00e5a0;color:#000;border:none;border-radius:7px;padding:5px 12px;font-size:11px;font-weight:700;cursor:pointer;">+ Cita</button>
    </div>`;
}

function cambiarVista(v) {
    vistaActual = v;
    ['dia','semana','mes'].forEach(x => {
        const btn = document.getElementById(`btnVista${x.charAt(0).toUpperCase()+x.slice(1)}`);
        if (btn) {
            btn.style.borderColor = x === v ? 'rgba(0,229,160,.3)' : '';
            btn.style.color = x === v ? '#00e5a0' : '';
        }
    });
    cargarAgendaHoy();
}

function navCalendario(dir) {
    if (vistaActual === 'dia')    fechaRef.setDate(fechaRef.getDate() + dir);
    if (vistaActual === 'semana') fechaRef.setDate(fechaRef.getDate() + (dir * 7));
    if (vistaActual === 'mes')    fechaRef.setMonth(fechaRef.getMonth() + dir);
    cargarAgendaHoy();
}

function irHoy() { fechaRef = new Date(); cargarAgendaHoy(); }

// ══════════════════════════════════════════════════════
// CARGA PRINCIPAL
// ══════════════════════════════════════════════════════
async function cargarAgendaHoy() {
    const el = document.getElementById('timelineCitas');
    el.innerHTML = `<div style="text-align:center;padding:32px;color:#3a3a55;font-size:12px;">Cargando...</div>`;

    let desde, hasta, labelFecha;

    if (vistaActual === 'dia') {
        desde = hasta = toISO(fechaRef);
        labelFecha = fmtFecha(fechaRef);
    } else if (vistaActual === 'semana') {
        const lunes = new Date(fechaRef);
        lunes.setDate(fechaRef.getDate() - ((fechaRef.getDay() + 6) % 7)); // lunes
        const domingo = new Date(lunes);
        domingo.setDate(lunes.getDate() + 6);
        desde = toISO(lunes);
        hasta = toISO(domingo);
        labelFecha = `${lunes.toLocaleDateString('es-CL',{day:'numeric',month:'short'})} – ${domingo.toLocaleDateString('es-CL',{day:'numeric',month:'short',year:'numeric'})}`;
    } else { // mes
        const primero = new Date(fechaRef.getFullYear(), fechaRef.getMonth(), 1);
        const ultimo  = new Date(fechaRef.getFullYear(), fechaRef.getMonth() + 1, 0);
        desde = toISO(primero);
        hasta = toISO(ultimo);
        labelFecha = fechaRef.toLocaleDateString('es-CL', { month:'long', year:'numeric' });
    }

    document.getElementById('agendaTituloFecha').textContent = '';
    const lblEl = document.getElementById('labelFechaVista');
    if (lblEl) lblEl.textContent = labelFecha;

    try {
        const citas = await api('GET', `/api/agenda/calendario?desde=${desde}&hasta=${hasta}`);
        if (!citas.length) {
            el.innerHTML = `<div style="text-align:center;padding:40px;color:#3a3a55;font-size:13px;">Sin citas para este período.</div>`;
            return;
        }

        if (vistaActual === 'dia')    el.innerHTML = renderVistaDia(citas);
        if (vistaActual === 'semana') el.innerHTML = renderVistaSemana(citas, desde);
        if (vistaActual === 'mes')    el.innerHTML = renderVistaMes(citas, fechaRef.getFullYear(), fechaRef.getMonth());

    } catch(e) {
        el.innerHTML = `<div style="color:#ff3f5b;padding:20px;">${e.message}</div>`;
    }
}

// ══════════════════════════════════════════════════════
// VISTA DÍA
// ══════════════════════════════════════════════════════
function renderVistaDia(citas) {
    if (!citas.length) return '<div style="text-align:center;padding:40px;color:#3a3a55;">Sin citas.</div>';
    return `<div class="timeline-wrap">${citas.map(c => renderCitaRow(c, true)).join('')}</div>`;
}

// ══════════════════════════════════════════════════════
// VISTA SEMANA
// ══════════════════════════════════════════════════════
function renderVistaSemana(citas, desdeISO) {
    const dias = [];
    const base = new Date(desdeISO + 'T12:00:00'); // evitar timezone
    for (let i = 0; i < 7; i++) {
        const d = new Date(base);
        d.setDate(base.getDate() + i);
        dias.push(d);
    }

    const hoy = toISO(new Date());
    const citasPorDia = {};
    citas.forEach(c => {
        const f = c.fecha ?? c.fecha_cita;
        if (!citasPorDia[f]) citasPorDia[f] = [];
        citasPorDia[f].push(c);
    });

    const cols = dias.map(d => {
        const iso = toISO(d);
        const esHoy = iso === hoy;
        const citasDia = citasPorDia[iso] ?? [];
        const lbl = d.toLocaleDateString('es-CL', { weekday:'short', day:'numeric' });
        return `
        <div style="flex:1;min-width:120px;border-right:1px solid #1e1e28;">
            <div style="padding:8px 10px;text-align:center;font-family:'IBM Plex Mono',monospace;font-size:10px;font-weight:700;
                color:${esHoy ? '#00e5a0' : '#7878a0'};background:${esHoy ? 'rgba(0,229,160,.06)' : '#111115'};
                border-bottom:1px solid #1e1e28;position:sticky;top:0;">
                ${lbl}${esHoy ? ' ◉' : ''}
            </div>
            <div style="padding:6px;">
                ${citasDia.length ? citasDia.map(c => renderCitaCompacta(c)).join('') : '<div style="color:#2a2a3a;font-size:10px;text-align:center;padding:8px;">—</div>'}
            </div>
        </div>`;
    }).join('');

    return `<div style="display:flex;overflow-x:auto;height:100%;align-items:flex-start;">${cols}</div>`;
}

// ══════════════════════════════════════════════════════
// VISTA MES
// ══════════════════════════════════════════════════════
function renderVistaMes(citas, year, month) {
    const primero = new Date(year, month, 1);
    const ultimo = new Date(year, month + 1, 0);
    const hoy = toISO(new Date());

    const citasPorDia = {};
    citas.forEach(c => {
        const f = c.fecha ?? c.fecha_cita;
        if (!citasPorDia[f]) citasPorDia[f] = [];
        citasPorDia[f].push(c);
    });

    // Cabecera
    let html = `<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:#1e1e28;">`;
    ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'].forEach(d => {
        html += `<div style="padding:6px;text-align:center;font-family:'IBM Plex Mono',monospace;font-size:9px;font-weight:700;color:#3a3a55;background:#111115;">${d}</div>`;
    });

    // Offset: primer día de mes (0=dom, 1=lun... → ajustar a lunes=0)
    const offset = (primero.getDay() + 6) % 7;
    for (let i = 0; i < offset; i++) {
        html += `<div style="background:#0d0d11;min-height:80px;"></div>`;
    }

    for (let d = 1; d <= ultimo.getDate(); d++) {
        const fecha = new Date(year, month, d);
        const iso = toISO(fecha);
        const esHoy = iso === hoy;
        const citasDelDia = citasPorDia[iso] ?? [];
        html += `
        <div onclick="irDia('${iso}')" style="background:${esHoy ? 'rgba(0,229,160,.05)' : '#111115'};min-height:80px;padding:4px;cursor:pointer;border:1px solid ${esHoy ? 'rgba(0,229,160,.3)' : 'transparent'};">
            <div style="font-family:'IBM Plex Mono',monospace;font-size:11px;font-weight:700;color:${esHoy ? '#00e5a0' : '#7878a0'};margin-bottom:3px;">${d}</div>
            ${citasDelDia.slice(0,3).map(c => `<div style="font-size:9px;padding:2px 4px;border-radius:3px;margin-bottom:2px;background:${colorEstado(c.estado)}22;color:${colorEstado(c.estado)};overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${c.hora_inicio} ${c.paciente_nombre ?? c.cliente?.nombre ?? '—'}</div>`).join('')}
            ${citasDelDia.length > 3 ? `<div style="font-size:9px;color:#3a3a55;">+${citasDelDia.length-3} más</div>` : ''}
        </div>`;
    }
    html += `</div>`;
    return html;
}

function irDia(iso) {
    fechaRef = new Date(iso + 'T12:00:00');
    cambiarVista('dia');
}

// ══════════════════════════════════════════════════════
// CARDS DE CITA
// ══════════════════════════════════════════════════════
function colorEstado(estado) {
    return { pendiente:'#f5c518', confirmada:'#00e5a0', en_curso:'#00c4ff', completada:'#3a3a55', cancelada:'#ff3f5b' }[estado] ?? '#3a3a55';
}

function renderCitaRow(c, mostrarFecha = false) {
    const color = colorEstado(c.estado);
    const tienePago = c.pago || c.pago_id;
    return `<div class="cita-row" onclick="abrirCita(${c.id})" style="margin-bottom:4px;">
        <div class="hora-col">${mostrarFecha && c.fecha ? c.fecha.slice(5) + '<br>' : ''}${c.hora_inicio}</div>
        <div class="barra-estado" style="background:${color};"></div>
        <div class="cita-info">
            <div class="ci-nombre">${c.paciente_nombre ?? c.cliente?.nombre ?? '—'}</div>
            <div class="ci-srv">${c.servicio?.nombre ?? c.tipo_cita ?? ''}</div>
            ${tienePago ? `<div style="font-size:10px;color:#00e5a0;margin-top:2px;">💳 WebPay ${c.pago?.estado ?? 'pagado'}</div>` : ''}
        </div>
        <span class="estado-badge sb-${c.estado}">${c.estado}</span>
    </div>`;
}

function renderCitaCompacta(c) {
    const color = colorEstado(c.estado);
    return `<div onclick="abrirCita(${c.id})" style="padding:5px 7px;border-radius:6px;background:${color}18;border:1px solid ${color}33;margin-bottom:4px;cursor:pointer;">
        <div style="font-family:'IBM Plex Mono',monospace;font-size:9px;font-weight:700;color:${color};">${c.hora_inicio}</div>
        <div style="font-size:10px;font-weight:600;color:#e8e8f0;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${c.paciente_nombre ?? c.cliente?.nombre ?? '—'}</div>
    </div>`;
}
```

### HTML del toolbar (reemplazar el `prof-topbar` de agenda)

En `agenda.blade.php`, la sección `tab-agenda` debe tener:

```html
<div id="tab-agenda" class="tab-panel">
    <!-- Toolbar con navegación de calendario -->
    <div id="toolbarCalendario"></div>
    <!-- Contenido -->
    <div class="prof-content" id="timelineCitas" style="padding:12px;"></div>
</div>
```

Y en el JS de inicialización:

```javascript
document.addEventListener('DOMContentLoaded', () => {
    // Renderizar toolbar
    document.getElementById('toolbarCalendario').innerHTML = renderToolbarCalendario();
    // Quitar el titulo estático (ahora lo maneja labelFechaVista)
    cargarKpis();
    cargarAgendaHoy();
});
```

---

## FIX 3 — CITAS DESDE LANDING PÚBLICO NO APARECEN

### Diagnóstico

El `ClientePortalController@crearCita` (o `AgendaPublicaController`) crea la cita en la tabla correcta pero probablemente no asigna `recurso_id` o lo asigna con el ID incorrecto. El endpoint `GET /api/agenda/mi/dia` filtra por `recurso_id = auth()->user()->recurso_id`, entonces si la cita tiene `recurso_id = null` o uno distinto, no aparece.

### Fix en `AgendaController.php`

```php
// En el endpoint GET /api/agenda/mi/dia (o /api/agenda/calendario)
public function calendario(Request $request)
{
    $desde = $request->input('desde', now()->toDateString());
    $hasta = $request->input('hasta', now()->toDateString());

    $user = auth()->user();

    $query = Cita::with(['cliente', 'servicio', 'pago'])
        ->whereBetween('fecha', [$desde, $hasta])  // ← verificar nombre del campo: fecha vs fecha_cita
        ->orderBy('fecha')
        ->orderBy('hora_inicio');

    // Si el usuario es admin, ve todas las citas del tenant
    if ($user->rol === 'admin' || $user->rol === 'super_admin') {
        // sin filtro de recurso
    }
    // Si es cajero/recepcionista, ve todas (para poder gestionar)
    elseif ($user->rol === 'cajero') {
        // sin filtro de recurso — la recepcionista ve todo
    }
    // Si es operario/profesional, solo las suyas
    elseif ($user->recurso_id) {
        $query->where('recurso_id', $user->recurso_id);
    }

    return response()->json($query->get()->map(function($c) {
        return [
            'id'             => $c->id,
            'fecha'          => $c->fecha ?? $c->fecha_cita,
            'hora_inicio'    => $c->hora_inicio,
            'hora_fin'       => $c->hora_fin,
            'estado'         => $c->estado,
            'paciente_nombre'=> $c->cliente?->nombre ?? $c->nombre_paciente,
            'rut_paciente'   => $c->cliente?->rut ?? $c->rut_paciente,
            'telefono'       => $c->cliente?->telefono ?? $c->telefono_paciente,
            'email'          => $c->cliente?->email ?? $c->email_paciente,
            'servicio'       => $c->servicio ? ['nombre' => $c->servicio->nombre, 'precio' => $c->servicio->valor_venta] : null,
            'tipo_cita'      => $c->tipo_cita ?? null,
            'notas'          => $c->notas ?? $c->observaciones,
            'recurso_id'     => $c->recurso_id,
            'pago'           => $c->pago ? [
                'id'          => $c->pago->id,
                'monto'       => $c->pago->monto ?? $c->pago->total,
                'estado'      => $c->pago->estado,
                'metodo'      => $c->pago->metodo ?? 'webpay',
                'pagado_en'   => $c->pago->pagado_en ?? $c->pago->created_at,
            ] : null,
        ];
    }));
}
```

### Fix en `ClientePortalController` — crear cita con recurso correcto

Verificar que al crear cita desde el landing se asigne `recurso_id`:

```php
// En el método que crea la cita desde el portal público
// (puede llamarse crearCita, agendarCita, store, etc.)

// BUSCAR este patrón y agregar recurso_id:
$cita = Cita::create([
    'fecha'       => $request->fecha,
    'hora_inicio' => $request->hora,
    'cliente_id'  => $cliente->id,
    'servicio_id' => $request->servicio_id,
    'estado'      => 'pendiente',
    'recurso_id'  => $request->recurso_id, // ← VERIFICAR que esto llega desde el form del landing
    // Si el landing no manda recurso_id, asignar el del profesional seleccionado:
    // 'recurso_id' => Recurso::where('servicio_id', $request->servicio_id)->first()?->id,
]);
```

---

## FIX 4 — PANEL DE DETALLE DE CITA (RECEPCIONISTA)

Al hacer click en una cita, `abrirCita(id)` debe mostrar el detalle completo incluyendo pago.

### Endpoint

```php
// GET /api/citas/{id}
public function show($id)
{
    $cita = Cita::with(['cliente', 'servicio', 'pago', 'recurso.usuario'])->findOrFail($id);

    return response()->json([
        'id'             => $cita->id,
        'fecha'          => $cita->fecha ?? $cita->fecha_cita,
        'hora_inicio'    => $cita->hora_inicio,
        'hora_fin'       => $cita->hora_fin,
        'estado'         => $cita->estado,
        'notas'          => $cita->notas ?? $cita->observaciones,
        'paciente'       => [
            'id'       => $cita->cliente?->id,
            'nombre'   => $cita->cliente?->nombre ?? $cita->nombre_paciente,
            'rut'      => $cita->cliente?->rut ?? $cita->rut_paciente,
            'telefono' => $cita->cliente?->telefono ?? $cita->telefono_paciente,
            'email'    => $cita->cliente?->email ?? $cita->email_paciente,
        ],
        'servicio'       => $cita->servicio ? [
            'nombre' => $cita->servicio->nombre,
            'precio' => $cita->servicio->valor_venta,
        ] : null,
        'profesional'    => $cita->recurso?->usuario?->nombre,
        'pago'           => $cita->pago ? [
            'id'       => $cita->pago->id,
            'monto'    => $cita->pago->monto ?? $cita->pago->total,
            'estado'   => $cita->pago->estado,
            'metodo'   => $cita->pago->metodo ?? 'webpay',
            'fecha'    => $cita->pago->created_at,
        ] : null,
    ]);
}
```

Agregar en routes:
```php
Route::get('/api/citas/{id}', [AgendaController::class, 'show']);
Route::put('/api/citas/{id}/estado', [AgendaController::class, 'cambiarEstado']);
```

### JS — `abrirCita(id)` completo

```javascript
async function abrirCita(id) {
    const det = document.getElementById('profDetalle');
    det.classList.remove('cerrado');
    det.innerHTML = '<div style="padding:20px;color:#3a3a55;">Cargando...</div>';

    try {
        const c = await api('GET', `/api/citas/${id}`);
        const pagoHtml = c.pago ? `
            <div style="margin-bottom:14px;">
                <div class="nf-label">Pago</div>
                <div style="background:rgba(0,229,160,.07);border:1px solid rgba(0,229,160,.2);border-radius:8px;padding:10px 12px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:12px;color:#7878a0;">Método</span>
                        <span style="font-family:'IBM Plex Mono',monospace;font-size:12px;font-weight:700;text-transform:uppercase;">${c.pago.metodo}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:12px;color:#7878a0;">Monto</span>
                        <span style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:700;color:#00e5a0;">$${Number(c.pago.monto).toLocaleString('es-CL')}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="font-size:12px;color:#7878a0;">Estado</span>
                        <span class="chip chip-ok">${c.pago.estado}</span>
                    </div>
                </div>
            </div>` : `
            <div style="margin-bottom:14px;">
                <div class="nf-label">Pago</div>
                <div style="background:#18181e;border-radius:8px;padding:10px 12px;font-size:12px;color:#3a3a55;">Sin pago registrado</div>
            </div>`;

        det.innerHTML = `
        <div class="det-head">
            <span class="det-head-titulo">Cita #${c.id}</span>
            <button onclick="cerrarDetalle()" class="btn-sm-ghost">✕</button>
        </div>
        <div class="det-body" style="padding:14px;">
            <div style="margin-bottom:14px;">
                <div class="nf-label">Paciente</div>
                <div style="font-size:13px;font-weight:700;color:#e8e8f0;">${c.paciente?.nombre ?? '—'}</div>
                <div style="font-size:11px;color:#7878a0;font-family:'IBM Plex Mono',monospace;">${c.paciente?.rut ?? ''}</div>
                ${c.paciente?.telefono ? `<div style="font-size:11px;color:#7878a0;margin-top:2px;">📱 ${c.paciente.telefono}</div>` : ''}
                ${c.paciente?.email ? `<div style="font-size:11px;color:#7878a0;">✉️ ${c.paciente.email}</div>` : ''}
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
                <div>
                    <div class="nf-label">Fecha</div>
                    <div style="font-size:12px;font-family:'IBM Plex Mono',monospace;color:#e8e8f0;">${c.fecha}</div>
                </div>
                <div>
                    <div class="nf-label">Hora</div>
                    <div style="font-size:12px;font-family:'IBM Plex Mono',monospace;color:#e8e8f0;">${c.hora_inicio}</div>
                </div>
            </div>

            ${c.servicio ? `<div style="margin-bottom:14px;">
                <div class="nf-label">Servicio</div>
                <div style="font-size:12px;color:#e8e8f0;">${c.servicio.nombre}</div>
                ${c.servicio.precio ? `<div style="font-size:11px;font-family:'IBM Plex Mono',monospace;color:#00e5a0;">$${Number(c.servicio.precio).toLocaleString('es-CL')}</div>` : ''}
            </div>` : ''}

            ${c.profesional ? `<div style="margin-bottom:14px;">
                <div class="nf-label">Profesional</div>
                <div style="font-size:12px;color:#e8e8f0;">${c.profesional}</div>
            </div>` : ''}

            ${pagoHtml}

            <div style="margin-bottom:14px;">
                <div class="nf-label">Estado</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    ${['pendiente','confirmada','en_curso','completada','cancelada'].map(est =>
                        `<button onclick="cambiarEstadoCita(${c.id},'${est}')"
                            style="padding:4px 10px;border-radius:6px;font-size:10px;font-weight:700;cursor:pointer;
                            border:1px solid ${est === c.estado ? colorEstado(est) : '#2a2a3a'};
                            background:${est === c.estado ? colorEstado(est)+'22' : '#18181e'};
                            color:${est === c.estado ? colorEstado(est) : '#7878a0'};">
                            ${est}
                        </button>`
                    ).join('')}
                </div>
            </div>

            ${c.notas ? `<div>
                <div class="nf-label">Notas</div>
                <div style="font-size:12px;color:#7878a0;font-style:italic;">${c.notas}</div>
            </div>` : ''}
        </div>
        <div class="det-foot">
            ${c.paciente?.id ? `<button onclick="abrirDetallePaciente(${c.paciente.id})" class="btn-agregar" style="width:100%;margin-bottom:6px;">Ver historial del paciente</button>` : ''}
        </div>`;
    } catch(e) {
        det.innerHTML = `<div style="padding:20px;color:#ff3f5b;">${e.message}</div>`;
    }
}

async function cambiarEstadoCita(id, estado) {
    try {
        await api('PUT', `/api/citas/${id}/estado`, { estado });
        abrirCita(id);
        cargarAgendaHoy();
    } catch(e) { alert(e.message); }
}
```

---

## RESUMEN DE ARCHIVOS A MODIFICAR

| Archivo | Acción | Prioridad |
|---|---|---|
| `resources/views/tenant/pos/agenda.blade.php` (o profesional) | CSS layout fix sidebar + toolbar calendario + JS vistas | 🔴 CRÍTICO |
| `app/Http/Controllers/Tenant/AgendaController.php` | Método `calendario()` con rango fechas y filtro por rol | 🔴 CRÍTICO |
| `routes/tenant.php` | `GET /api/agenda/calendario`, `GET /api/citas/{id}`, `PUT /api/citas/{id}/estado` | 🔴 CRÍTICO |
| `app/Http/Controllers/Tenant/ClientePortalController.php` | Verificar que cita se crea con `recurso_id` correcto | 🟡 IMPORTANTE |
| `app/Http/Controllers/Tenant/AgendaController.php` | Método `show($id)` con pago incluido | 🟡 IMPORTANTE |

---

## VERIFICACIÓN FINAL

```bash
# Desde container benderandos_app:

# 1. Verificar nombre real de la tabla de citas
docker exec benderandos_app sh -c "cd /app && php artisan tinker --execute=\"\\DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name LIKE \'%cita%\'');\""

# 2. Verificar campos reales del modelo
docker exec benderandos_app sh -c "cd /app && php artisan tinker --execute=\"\\Schema::getColumnListing('citas');\""

# 3. Verificar que hay citas en el tenant demo-medico
docker exec benderandos_app sh -c "cd /app && php artisan tenants:run demo-medico --function=\"fn() => var_dump(\\App\\Models\\Tenant\\Cita::count());\""

# 4. Test del nuevo endpoint
curl -H "Authorization: Bearer TOKEN" \
  "http://demo-medico.localhost:8000/api/agenda/calendario?desde=2026-03-01&hasta=2026-03-31"
```

---

*BenderAnd · H25 Agenda Operador Calendario · Marzo 2026 · Antigravity*
*Layout fix sidebar + Calendario día/semana/mes + Citas desde landing + Panel detalle con pago WebPay*
