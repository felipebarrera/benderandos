# H9 UI — Módulo Compras y Proveedores (admin_dashboard)
**BenderAnd POS · Pantallas HTML (Vanilla JS)**
*Estado: ⬜ Pendiente integración · Documento: `HITO9_PLAN.md`*

> Integrar el módulo completo de compras/proveedores dentro de `admin_dashboard_v2.html`.
> El backend Laravel correspondiente está en `specs/hitos/H8-H18-modulos-nuevos.md` §H10.

---

## Diagnóstico: qué existe vs qué falta

### En `admin_dashboard_v2.html` hoy:

| Vista | Estado | Problema |
|---|---|---|
| `pg-compras` | ⚠️ Básico | Lista proveedores simple, sin wizard OC |
| `pg-nueva-compra` | ⚠️ Básico | Formulario sin steps, sin descuentos, sin IVA |
| Proveedores | ⚠️ Básico | Modal simple, sin ficha detalle, sin condiciones |
| Historial OC | ⚠️ Básico | Tabla estática, sin estados, sin acciones |
| Recepción | ❌ No existe | Sin pantalla de recepción de mercancía |
| Control calidad | ❌ No existe | Sin validación por lote, rechazos ni NC |
| Alertas stock → OC | ❌ No existe | Sin sugerencia automática de compra |
| Dashboard compras | ❌ No existe | Sin KPIs ni métricas de compras |
| Ficha proveedor | ❌ No existe | Sin vista de condiciones negociadas ni historial |
| Descuentos volumen | ❌ No existe | Sin cálculo de descuentos ni IVA en OC |

---

## Cambios al sidebar en `admin_dashboard_v2.html`

```html
<!-- Reemplazar sección compras actual por: -->
<div class="sb-section">
  <div class="sb-label">COMPRAS</div>
  <div class="ni" onclick="goTo('compras-dash')">
    <span class="ni-ic">📊</span>Dashboard compras
  </div>
  <div class="ni" onclick="goTo('proveedores')">
    <span class="ni-ic">🏢</span>Proveedores
  </div>
  <div class="ni" onclick="goTo('nueva-oc')">
    <span class="ni-ic">📝</span>Nueva OC
  </div>
  <div class="ni" onclick="goTo('recepcion')">
    <span class="ni-ic">📦</span>Recepción
    <span class="ni-bd w" id="badge-recepciones">0</span>
  </div>
  <div class="ni" onclick="goTo('historial-oc')">
    <span class="ni-ic">📋</span>Historial OC
  </div>
</div>
```

## Nuevas páginas SPA a agregar

```javascript
// En goTo() — ampliar el mapa de páginas:
const PAGES = {
  // existentes...
  'compras-dash':    'Dashboard Compras',
  'proveedores':     'Proveedores',
  'nueva-oc':        'Nueva Orden de Compra',
  'recepcion':       'Recepción de Mercancía',
  'historial-oc':    'Historial OC',
  'proveedor-detalle': 'Detalle Proveedor',
};
```

---

## Vista 1: `pg-compras-dash` — Dashboard de Compras

**KPIs:**
```javascript
const COMPRAS_KPIs = {
  totalMes: 12345678,
  ordenes: 48,
  proveedores: 24,
  ahorro: 234567
};
```

**Alertas de stock crítico (link → nueva OC):**
```javascript
const ALERTAS_STOCK = [
  { nombre: 'Cemento Melón 42.5kg',  stock: 3,  minimo: 20,  proveedor_id: 1 },
  { nombre: 'Cable THHN 12 AWG (m)', stock: 45, minimo: 100, proveedor_id: 2 },
  { nombre: 'Ladrillo 18x18x33',     stock: 80, minimo: 200, proveedor_id: 1 },
  { nombre: 'Cerradura Schlage B60', stock: 1,  minimo: 5,   proveedor_id: 3 },
];
```

---

## Vista 2: `pg-proveedores` — Catálogo

**Datos mock:**
```javascript
const PROVEEDORES = [
  {
    id: 1, tipo: 'global',
    nombre: 'Cementos Melón S.A.', rut: '96.123.456-7',
    rating: 5, plazo_pago: 30, minimo: 315000,
    desc_volumen: [{ desde: 500, pct: 3 }, { desde: 1000, pct: 5 }],
    tiempo_entrega: 3,
    ultima_compra: { fecha: '15/03/2026', monto: 315000 },
    productos_asociados: 8,
    contacto: { nombre: 'Carlos Rodríguez', tel: '+56 9 8765 4321' }
  },
  {
    id: 2, tipo: 'global',
    nombre: 'Distribuidora Eléctrica Nacional', rut: '76.234.567-8',
    rating: 4, plazo_pago: 45, minimo: 100000,
    desc_volumen: [{ desde: 200000, pct: 4 }],
    tiempo_entrega: 2,
    ultima_compra: { fecha: '14/03/2026', monto: 234560 },
    productos_asociados: 24,
    contacto: { nombre: 'Ana Morales', email: 'ventas@delec.cl' }
  },
  {
    id: 3, tipo: 'local',
    nombre: 'Ferretería Mayorista San Borja', rut: '76.345.678-9',
    rating: 5, plazo_pago: 15, minimo: 50000,
    tiempo_entrega: 1,
    ultima_compra: { fecha: '17/03/2026', monto: 567800 },
    productos_asociados: 156
  },
];
```

---

## Vista 3: `pg-nueva-oc` — Wizard 3 pasos

```
Paso 1: Seleccionar proveedor + fecha requerida + condiciones pago
Paso 2: Agregar productos (buscar por nombre/código, ingresar cantidad)
        → Calcula descuentos por volumen + IVA 19% en tiempo real
Paso 3: Resumen + confirmación → genera número correlativo OC-YYYY-NNNN
```

**Cálculo descuentos y totales:**
```javascript
function calcDescuento(subtotal, provId) {
  const prov = PROVEEDORES.find(p => p.id === provId);
  const tier = prov?.desc_volumen
    ?.filter(t => subtotal >= t.desde)
    ?.sort((a, b) => b.desde - a.desde)[0];
  return tier ? Math.round(subtotal * tier.pct / 100) : 0;
}

function calcIVA(neto) {
  return Math.round(neto * 0.19);
}

function calcTotales(items, provId) {
  const subtotal = items.reduce((s, i) => s + i.precio_unit * i.cantidad, 0);
  const descuento = calcDescuento(subtotal, provId);
  const neto = subtotal - descuento;
  const iva = calcIVA(neto);
  return { subtotal, descuento, neto, iva, total: neto + iva };
}
```

---

## Vista 4: `pg-recepcion` — Recepción de mercancía

Por cada OC pendiente de recepción:
- Campos: guía despacho, factura proveedor, fecha recepción
- Por ítem: cantidad solicitada → cantidad recibida → cantidad aceptada / rechazada
- Motivo de rechazo si aplica
- Al confirmar: stock sube en aceptados, OC cambia estado

**Estados de OC:**
```javascript
const OC_ESTADO_CONFIG = {
  'pendiente':       { label: 'Pendiente',    cls: 'b-w',  icon: '⏳' },
  'enviada':         { label: 'Enviada',      cls: 'b-i',  icon: '📤' },
  'confirmada':      { label: 'Confirmada',   cls: 'b-ok', icon: '📋' },
  'en_envio':        { label: 'En envío',     cls: 'b-w',  icon: '🚚' },
  'recibida':        { label: 'Recibida',     cls: 'b-ok', icon: '✅' },
  'parcial':         { label: 'Parcial',      cls: 'b-w',  icon: '⚠️' },
  'con_diferencias': { label: 'Diferencias',  cls: 'b-e',  icon: '❌' },
  'anulada':         { label: 'Anulada',      cls: 'b-e',  icon: '🚫' },
};
```

---

## Funciones JS nuevas

```javascript
function generarNumeroOC() {
  const year = new Date().getFullYear();
  const ultimo = ORDENES_COMPRA.length ? parseInt(ORDENES_COMPRA[0].id.split('-')[2]) : 1000;
  return `OC-${year}-${(ultimo + 1).toString().padStart(4, '0')}`;
}

function confirmarRecepcion(ocId, itemsRecibidos) {
  const oc = ORDENES_COMPRA.find(o => o.id === ocId);
  const todosRecibidos = itemsRecibidos.every(i => i.cantidad_recibida >= i.cantidad_solicitada);
  const hayDiferencias = itemsRecibidos.some(i => i.cantidad_rechazada > 0);

  // Actualizar stock en PRODUCTOS (mock)
  itemsRecibidos.forEach(item => {
    const prod = PRODUCTOS.find(p => p.id === item.producto_id);
    if (prod) prod.cantidad += item.cantidad_aceptada;
  });

  oc.estado = hayDiferencias ? 'con_diferencias' : 'recibida';
  actualizarBadgeRecepciones();
  toast(`Recepción OC ${ocId} confirmada`, 'ok');
}

function actualizarBadgeRecepciones() {
  const pendientes = ORDENES_COMPRA.filter(oc =>
    ['confirmada', 'en_envio'].includes(oc.estado)
  ).length;
  document.getElementById('badge-recepciones').textContent = pendientes;
  document.getElementById('badge-recepciones').style.display = pendientes > 0 ? 'flex' : 'none';
}

function generarSugerenciaOC() {
  // Agrupar productos críticos por proveedor preferido
  const porProveedor = {};
  ALERTAS_STOCK.forEach(prod => {
    const pId = prod.proveedor_id;
    if (!porProveedor[pId]) porProveedor[pId] = [];
    porProveedor[pId].push(prod);
  });
  // Navegar a nueva-oc con proveedor pre-seleccionado
  goTo('nueva-oc');
}
```

---

## Checklist H9 UI

- [ ] Reemplazar `pg-compras` con `pg-compras-dash` (KPIs + alertas + OC recientes)
- [ ] Crear `pg-proveedores` con cards global/local + modal proveedor completo
- [ ] Crear `pg-nueva-oc` wizard 3 pasos con cálculo descuentos + IVA
- [ ] Crear `pg-recepcion` con validación por ítem y actualización stock
- [ ] Crear `pg-historial-oc` con filtros por estado, fecha, proveedor
- [ ] Actualizar sidebar con nueva estructura sección COMPRAS
- [ ] Badge contador recepciones pendientes en sidebar
- [ ] Conectar alerta "Stock crítico" del dashboard → pre-carga OC
- [ ] Función `generarSugerenciaOC()` desde alertas de stock
- [ ] Exportar OC como PDF (`window.print()` con estilos específicos)
- [ ] Integrar `benderand-debug.js` con logs en eventos clave

---

## Notas de conexión al backend (cuando H10 esté listo)

Los stubs JS actuales se reemplazarán por `fetch()` a:
```
GET  /api/proveedores           → reemplaza PROVEEDORES mock
GET  /api/ordenes-compra        → reemplaza ORDENES_COMPRA mock
POST /api/ordenes-compra        → reemplaza generarNumeroOC() local
POST /api/recepciones           → reemplaza confirmarRecepcion() local
GET  /api/compras/alertas-stock → reemplaza ALERTAS_STOCK mock
```
