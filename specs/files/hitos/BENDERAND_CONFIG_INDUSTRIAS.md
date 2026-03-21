# BENDERAND ERP — SISTEMA DE CONFIGURACIÓN POR INDUSTRIA
**Módulos · Acciones · UI Condicional · Onboarding WhatsApp**
*v1.0 · Marzo 2026 · Confidencial*

---

## PRINCIPIO CENTRAL

> **El sistema NO muestra lo que la industria no necesita.**
> Cada empresa ve exactamente los módulos, menús y acciones de su rubro.
> La configuración se define una vez — vía bot de WhatsApp — y el sistema
> adapta la UI, los permisos, las etiquetas y los flujos automáticamente.

---

## PARTE A: MÓDULOS ATÓMICOS (Acciones Independientes)

Cada módulo es una **unidad de funcionalidad independiente** que puede activarse
o desactivarse por empresa. Los módulos se agrupan en tres contextos:
`admin`, `pos` (punto de venta) y `cliente` (portal externo).

### A.1 Catálogo Completo de Módulos

| ID | Módulo | Contexto | Descripción |
|---|---|---|---|
| `M01` | **Venta simple** | pos | Carrito + cobro. Base de todo. Siempre activo. |
| `M02` | **Venta multi-operario** | pos | Múltiples operarios agregan ítems a una venta compartida por RUT |
| `M03` | **Stock físico** | pos + admin | Productos con unidades, alertas de stock mínimo |
| `M04` | **Stock fraccionado** | pos | Venta por kg/metro/litro. Requiere M03. |
| `M05` | **Renta / Arriendo** | pos | Productos con timer, extensiones, estado rentado/libre |
| `M06` | **Renta por hora** | pos | Variante de M05 con timer horario (canchas, salas, habitaciones) |
| `M07` | **Servicios sin stock** | pos | Productos tipo servicio: consulta, hora, trabajo |
| `M08` | **Agenda / Citas** | pos + admin | Calendario de citas por profesional con estados |
| `M09` | **Honorarios** | pos | Documento fiscal tipo boleta de honorarios |
| `M10` | **Notas cifradas** | pos | Campo de notas protegido por rol (médico, legal) |
| `M11` | **Fiado / Crédito cliente** | pos | Ventas a crédito, gestión de deudas por cliente |
| `M12` | **Encargos / Reservas** | pos + admin | Productos separados para cliente, abono, fecha |
| `M13` | **Delivery / Envíos** | pos + admin | Asignación de repartidores, tracking, estados |
| `M14` | **Habitaciones / Recursos** | pos | Mapa visual de habitaciones/canchas/recursos con estado |
| `M15` | **Comandas / Cocina** | pos | Vista de pedidos por sector (cocina, bar, barra) |
| `M16` | **Recetas / Ingredientes** | admin | Costeo de platos, descuento automático de ingredientes |
| `M17` | **Pedido remoto WhatsApp** | pos + admin | Ventas iniciadas por el bot, gestionadas en POS |
| `M18` | **Compras / Proveedores** | admin | OC, recepción de mercancía, catálogo de proveedores |
| `M19` | **Inventario avanzado** | admin | Lotes, vencimientos, ubicaciones en bodega |
| `M20` | **SII / Facturación electrónica** | admin + pos | DTE automático: boleta, factura, honorarios, NC |
| `M21` | **RRHH / Asistencia** | admin | Empleados, marcación, vacaciones, permisos |
| `M22` | **Liquidaciones** | admin | Cálculo de sueldos con descuentos legales chilenos |
| `M23` | **Reclutamiento** | admin | Ofertas de empleo, postulantes, entrevistas |
| `M24` | **Marketing QR** | admin | Generación de QR con descuentos, tracking de escaneos |
| `M25` | **Portal cliente web** | cliente | Historial, pedido remoto, deudas, seguimiento |
| `M26` | **Descuento por volumen** | pos | Precio escalonado según cantidad (mayorista) |
| `M27` | **Multi-sucursal** | admin | Manejo de más de un local dentro del mismo tenant |
| `M28` | **Órdenes de trabajo** | pos + admin | OT con estado: diagnóstico → presupuesto → ejecución → cobro |
| `M29` | **Historial por recurso** | admin | Historial vinculado a vehículo, mascota, expediente, etc. |
| `M30` | **Membresías / Subscripciones** | pos + admin | Planes recurrentes por cliente (gym, spa) |
| `M31` | **Venta de Software SaaS** | pos + admin | Gestión de tenants propios, planes, billing recurrente, onboarding, métricas MRR/churn. El sistema se vende a sí mismo. |
| `M32` | **CRM Modular** | pos + admin + bot | Seguimiento de clientes con herramientas específicas por industria. Notas, actividades, tareas, pipeline, recursos, RFM, NPS, campañas WA. Herramientas C01–C15 configuradas por rubro. |

---

### A.2 Acciones por Contexto

Cada módulo expone acciones diferenciadas según el contexto. Esto define
qué ve cada rol en su pantalla.

#### Módulo M05 — Renta / Arriendo (ejemplo)

| Acción | Contexto POS | Contexto Admin |
|---|---|---|
| Iniciar renta (asignar recurso) | ✅ cajero / operario | — |
| Ver timer en tiempo real | ✅ cajero | ✅ dashboard |
| Extender tiempo | ✅ cajero | — |
| Checkout / cobrar | ✅ cajero | — |
| Ver historial de rentas | — | ✅ admin |
| Configurar precios por duración | — | ✅ admin |
| Ver ocupación semanal | — | ✅ admin |
| Cambiar estado recurso (limpieza) | ✅ operario (rol camarera/bodega) | — |

#### Módulo M08 — Agenda / Citas (ejemplo)

| Acción | Contexto POS | Contexto Admin |
|---|---|---|
| Ver agenda del día | ✅ recepcionista | ✅ admin |
| Confirmar llegada del paciente | ✅ recepcionista | — |
| Agregar servicios a consulta activa | ✅ médico/profesional | — |
| Cobrar consulta | ✅ recepcionista/cajero | — |
| Ver agenda de todos los profesionales | — | ✅ admin |
| Crear / editar cita | ✅ recepcionista | ✅ admin |
| Cancelar cita | ✅ recepcionista (con motivo) | ✅ admin |
| Configurar disponibilidad por profesional | — | ✅ admin |
| Ver métricas (citas por profesional/mes) | — | ✅ admin |

---

## PARTE B: INDUSTRIAS PREDEFINIDAS

Cada industria es un **preset de módulos + etiquetas + configuración UI**.
El admin puede ajustar dentro del preset. El bot de WhatsApp aplica el preset
durante el onboarding.

### B.1 Mapa Industrias → Módulos

```
INDUSTRIA                   MÓDULOS ACTIVOS
─────────────────────────────────────────────────────────────────────────
RETAIL / ABARROTES          M01 M02 M03 M04 M11 M12 M17 M18 M20 M24 M25 M32
MAYORISTA / FERRETERÍA      M01 M02 M03 M04 M11 M17 M18 M19 M20 M24 M26 M32
RESTAURANTE                 M01 M02 M03 M14 M15 M16 M17 M18 M20 M24 M32
DELIVERY / DARK KITCHEN     M01 M02 M03 M13 M15 M16 M17 M18 M20 M24
MOTEL / HOSPEDAJE HORAS     M01 M03 M06 M14 M17 M20
HOTEL / ALOJAMIENTO DÍAS    M01 M03 M05 M08 M13 M14 M17 M20 M27
CANCHAS / DEPORTES          M01 M03 M06 M08 M14 M17 M20 M24 M30
MÉDICO / CLÍNICA            M01 M07 M08 M09 M10 M20 M21 M22 M23 M32
DENTISTA                    M01 M07 M08 M09 M10 M20 M21 M32
ABOGADOS / ESTUDIO JURÍDICO M01 M07 M08 M09 M10 M20 M21 M32
GASFÍTER / TÉCNICO          M01 M03 M07 M28 M29 M20 M21 M32
TALLER MECÁNICO             M01 M03 M07 M18 M28 M29 M20 M21 M32
SALÓN DE BELLEZA / SPA      M01 M07 M08 M17 M20 M24 M30 M32
VETERINARIA                 M01 M03 M07 M08 M10 M20 M29 M32
FARMACIA                    M01 M03 M04 M11 M18 M19 M20 M32
GIMNASIO / FITNESS          M01 M03 M08 M17 M20 M30 M32
INMOBILIARIA                M01 M07 M08 M20 M21 M23 M32
CONSTRUCTORA / PROYECTOS    M01 M03 M07 M18 M28 M20 M21 M22
PROVEEDOR SOFTWARE / SAAS  M01 M07 M20 M21 M22 M23 M24 M25 M27 M31 M32
─────────────────────────────────────────────────────────────────────────
```

### B.2 Industrias con Doble Módulo (Casos Híbridos)

Algunas industrias combinan dos rubros naturalmente. El sistema soporta
la activación conjunta de módulos de ambas categorías.

| Industria Híbrida | Combina | Módulos Resultado |
|---|---|---|
| **Clínica + Farmacia** | Médico + Farmacia | M01 M03 M07 M08 M09 M10 M18 M19 M20 M21 |
| **Restaurante + Delivery** | Restaurante + Dark Kitchen | M01 M02 M03 M13 M14 M15 M16 M17 M18 M20 M24 |
| **Veterinaria + Pet Shop** | Veterinaria + Retail | M01 M03 M04 M07 M08 M10 M18 M20 M29 |
| **Taller + Repuestos** | Mecánico + Mayorista | M01 M03 M04 M07 M18 M19 M26 M28 M29 M20 M21 |
| **Spa + Retail cosmético** | Spa + Retail | M01 M03 M07 M08 M17 M18 M20 M24 M30 |
| **Cancha + Cafetería** | Deportes + Restaurante | M01 M03 M06 M08 M14 M15 M17 M20 M24 M30 |
| **Hospedaje + Restaurante** | Hotel + Restaurante | M01 M03 M05 M06 M08 M13 M14 M15 M16 M17 M20 M27 |
| **Estudio legal + Contabilidad** | Legal + Servicios | M01 M07 M08 M09 M10 M20 M21 M22 |
| **Médico + Psicólogo** | Clínica multi-profesional | M01 M07 M08 M09 M10 M20 M21 M23 |

---

### B.3 Fichas de Industria Detalladas

#### RETAIL / ABARROTES
```
Módulos activos:  M01 M02 M03 M04 M11 M12 M17 M18 M20 M24 M25
Etiquetas:
  operario_label:    "Vendedor"
  cliente_label:     "Cliente"
  documento_fiscal:  "Boleta"
  producto_label:    "Producto"
  nota_label:        —

POS muestra:
  [Buscar producto] [Agregar] [Carrito] [Cobrar]
  [Cliente por RUT] [Fiado] [Pedido WA]

Admin muestra:
  Dashboard · Ventas · Compras · Inventario
  Clientes + Deudas · Marketing QR · SII · Config

POS NO muestra:
  Habitaciones · Timers · Canchas · Agenda · Comandas
  Notas cifradas · Recetas · Órdenes de trabajo

Menú POS comprimido (móvil):
  💳 Venta   📦 Stock   👤 Clientes   💰 Caja
```

#### MAYORISTA / FERRETERÍA
```
Módulos activos:  M01 M02 M03 M04 M11 M17 M18 M19 M20 M24 M26
Etiquetas:
  operario_label:    "Vendedor"
  cliente_label:     "Empresa / Cliente"
  documento_fiscal:  "Factura / Boleta"
  producto_label:    "Artículo"

POS muestra:
  [Buscar por SKU] [Fraccionado] [Descuento volumen]
  [Empresa por RUT] [Vista previa DTE] [Crédito]

Admin muestra:
  Dashboard · Ventas · Compras (core)
  Inventario avanzado · Proveedores · SII · Config
  Marketing QR

POS NO muestra:
  Agenda · Timers · Habitaciones · Delivery · Comandas
  RRHH · Recetas · OT

Diferenciador UI:
  Pantalla de venta muestra columna "Descuento volumen"
  Selector tipo DTE (factura/boleta/guía) visible en caja
```

#### RESTAURANTE
```
Módulos activos:  M01 M02 M03 M14 M15 M16 M17 M18 M20 M24
Etiquetas:
  operario_label:    "Garzón / Cocinero"
  cliente_label:     "Mesa / Cliente"
  documento_fiscal:  "Boleta"
  recurso_label:     "Mesa"

POS muestra:
  [Mapa mesas] [Nueva comanda] [Pedidos en cola]
  [Cocina] [Delivery pendiente]

Admin muestra:
  Dashboard · Ventas · Compras (ingredientes)
  Recetas (core) · Proveedores · SII · Marketing QR

POS NO muestra:
  Stock manual (solo por producción de recetas)
  Agenda · Timers · Habitaciones · RRHH · Fiado
  Notas cifradas · OT

Vistas POS especiales:
  [MAPA MESAS] → estado: libre/ocupada/pendiente-pago
  [COCINA] → comandas en tiempo real, sin login extra
  [DESPACHO] → pedidos delivery listos para entregar
```

#### MOTEL / HOSPEDAJE POR HORAS
```
Módulos activos:  M01 M03 M06 M14 M17 M20
Etiquetas:
  operario_label:    "Recepcionista"
  cliente_label:     "Huésped"
  documento_fiscal:  "Boleta sin detalle"
  recurso_label:     "Habitación"

POS muestra:
  [Mapa habitaciones] [Iniciar estadía]
  [Timers activos] [Checkout] [Extender]

Admin muestra:
  Dashboard · Ventas · Inventario (minibar)
  Ocupación histórica · SII · Config

POS NO muestra:
  Agenda · Delivery · Stock fraccionado
  Proveedores · Recetas · RRHH · QR · Fiado
  Compras · Comandas

Reglas especiales:
  requiere_rut: FALSE (privacidad)
  cobro_al_ingreso: TRUE
  alerta_vencimiento: configurable (default 10 min)
  estados_recurso: libre | ocupada | limpieza | mantencion
```

#### CANCHAS / CENTROS DEPORTIVOS
```
Módulos activos:  M01 M03 M06 M08 M14 M17 M20 M24 M30
Etiquetas:
  operario_label:    "Encargado"
  cliente_label:     "Socio / Cliente"
  documento_fiscal:  "Boleta"
  recurso_label:     "Cancha / Sala"

POS muestra:
  [Calendario canchas] [Reserva activa]
  [Membresías] [Venta accesorios]
  [Check-in socio]

Admin muestra:
  Dashboard · Reservas · Membresías · Marketing QR
  Inventario (accesorios) · SII · Config

POS NO muestra:
  Fiado · Delivery · Recetas · Comandas
  Habitaciones · Notas cifradas · Proveedores

Diferenciador UI:
  Vista calendario con franjas horarias por cancha
  Semáforo: verde=libre, amarillo=reservada, rojo=ocupada
  Timer visible en reservas activas
```

#### MÉDICO / CLÍNICA
```
Módulos activos:  M01 M07 M08 M09 M10 M20 M21 M22 M23
Etiquetas:
  operario_label:    "Médico / Profesional"
  cliente_label:     "Paciente"
  documento_fiscal:  "Boleta de honorarios"
  cajero_label:      "Recepcionista"
  nota_label:        "Historia clínica (cifrada)"

POS muestra:
  [Agenda del día] [Paciente activo]
  [Agregar prestación] [Notas clínicas 🔒]
  [Cobrar consulta]

Admin muestra:
  Dashboard · Agenda (todos los profesionales)
  RRHH · Liquidaciones · Reclutamiento
  SII (honorarios) · Config

POS NO muestra:
  Stock físico · Fiado · Delivery · Recetas
  Comandas · Timers · Habitaciones · QR
  Compras · Proveedores

Seguridad especial:
  notas_cifradas: AES-256 por columna
  acceso_notas: solo rol médico / profesional
  requiere_rut: TRUE
  log_acceso: SIEMPRE (auditoría regulatoria)
```

#### ABOGADOS / ESTUDIO JURÍDICO
```
Módulos activos:  M01 M07 M08 M09 M10 M20 M21
Etiquetas:
  operario_label:    "Abogado"
  cliente_label:     "Cliente / Caso"
  documento_fiscal:  "Boleta de honorarios"
  nota_label:        "Expediente (cifrado)"

POS muestra:
  [Agenda del día] [Caso activo]
  [Agregar servicio / hora] [Notas expediente 🔒]
  [Cobrar honorarios]

Admin muestra:
  Dashboard · Agenda · Casos activos
  RRHH · SII (honorarios) · Config

POS NO muestra:
  Stock · Delivery · Recetas · Comandas
  Timers · Habitaciones · QR · Compras · Fiado
  Membresías

Reglas especiales:
  cobro_por: "hora" o "servicio_fijo"
  notas_cifradas: TRUE (secreto profesional)
  requiere_rut: TRUE
```

#### TALLER MECÁNICO / TÉCNICO
```
Módulos activos:  M01 M03 M07 M18 M28 M29 M20 M21
Etiquetas:
  operario_label:    "Técnico / Mecánico"
  cliente_label:     "Cliente"
  documento_fiscal:  "Boleta / Factura"
  recurso_label:     "Vehículo"
  ot_label:          "Orden de trabajo"

POS muestra:
  [OT activas] [Nueva OT] [Estado: diagnóstico → ppto → aprobado → en ejecución → listo]
  [Agregar repuesto (stock)] [Agregar mano de obra (servicio)]
  [Historial vehículo]

Admin muestra:
  Dashboard · OT · Repuestos (inventario)
  Compras (repuestos) · RRHH · SII · Config

POS NO muestra:
  Agenda de citas · Timers · Habitaciones
  Recetas · Comandas · Delivery · QR
  Notas cifradas · Membresías

Diferenciador UI:
  Kanban de OT: Diagnóstico | Presupuesto | Aprobada | En ejecución | Lista | Entregada
  Historial vinculado a PATENTE (no solo RUT)
```

#### SALÓN DE BELLEZA / SPA
```
Módulos activos:  M01 M07 M08 M17 M20 M24 M30
Etiquetas:
  operario_label:    "Estilista / Esteticista"
  cliente_label:     "Cliente"
  documento_fiscal:  "Boleta"
  recurso_label:     "Silla / Cabina"

POS muestra:
  [Agenda hoy] [Servicio activo]
  [Agregar servicio] [Vender producto]
  [Check-in / Check-out]

Admin muestra:
  Dashboard · Agenda · Membresías
  Marketing QR · SII · Config

POS NO muestra:
  Stock físico (solo cosméticos venta directa)
  Delivery · Recetas · Comandas · Timers
  Habitaciones · Compras · RRHH · Fiado

Diferenciador UI:
  Vista de agenda con color por estilista
  Membresías con sesiones disponibles visibles
```

#### FARMACIA
```
Módulos activos:  M01 M03 M04 M11 M18 M19 M20
Etiquetas:
  operario_label:    "Farmacéutico / Vendedor"
  cliente_label:     "Cliente"
  documento_fiscal:  "Boleta"

POS muestra:
  [Buscar medicamento (SKU/nombre/principio activo)]
  [Receta retenida] [Stock en tiempo real]
  [Fraccionado (pastillas)]

Admin muestra:
  Dashboard · Inventario (crítico: alertas vencimiento)
  Compras · Proveedores · SII · Config

POS NO muestra:
  Agenda · Timers · Habitaciones · Delivery
  Recetas (cocina) · Comandas · QR · RRHH
  Fiado · Membresías

Reglas especiales:
  alerta_vencimiento: producto (fármaco), no renta
  requiere_receta: flag por producto
  stock_minimo_critico: alerta diaria automática
```

---

## PARTE C: TABLA RUBROS_CONFIG (Modelo de Datos)

```sql
-- Schema: tenant_{uuid}
CREATE TABLE rubros_config (
    id                    BIGSERIAL PRIMARY KEY,

    -- Identificación
    industria_preset      VARCHAR(50) NOT NULL,  -- 'retail','medico','motel', etc.
    industria_nombre      VARCHAR(255),           -- nombre personalizado del admin

    -- Módulos activos (array de IDs)
    modulos_activos       TEXT[] NOT NULL,        -- ['M01','M03','M05',...] PostgreSQL array

    -- Etiquetas personalizables
    label_operario        VARCHAR(100) DEFAULT 'Vendedor',
    label_cliente         VARCHAR(100) DEFAULT 'Cliente',
    label_cajero          VARCHAR(100) DEFAULT 'Cajero',
    label_producto        VARCHAR(100) DEFAULT 'Producto',
    label_recurso         VARCHAR(100) DEFAULT 'Recurso',   -- Habitación / Cancha / Mesa
    label_nota            VARCHAR(100),                      -- NULL = módulo M10 inactivo

    -- Comportamiento fiscal
    documento_default     VARCHAR(50)  DEFAULT 'boleta',    -- 'boleta','factura','honorarios'
    requiere_rut          BOOLEAN      DEFAULT FALSE,
    boleta_sin_detalle    BOOLEAN      DEFAULT FALSE,        -- motel: TRUE

    -- Comportamiento POS
    tiene_stock_fisico    BOOLEAN      DEFAULT TRUE,
    tiene_renta           BOOLEAN      DEFAULT FALSE,
    tiene_renta_hora      BOOLEAN      DEFAULT FALSE,
    tiene_servicios       BOOLEAN      DEFAULT FALSE,
    tiene_agenda          BOOLEAN      DEFAULT FALSE,
    tiene_delivery        BOOLEAN      DEFAULT FALSE,
    tiene_comandas        BOOLEAN      DEFAULT FALSE,
    tiene_ot              BOOLEAN      DEFAULT FALSE,        -- órdenes de trabajo
    tiene_membresías      BOOLEAN      DEFAULT FALSE,
    tiene_notas_cifradas  BOOLEAN      DEFAULT FALSE,
    tiene_fiado           BOOLEAN      DEFAULT FALSE,
    tiene_fraccionado     BOOLEAN      DEFAULT FALSE,
    tiene_descuento_vol   BOOLEAN      DEFAULT FALSE,

    -- Recursos (M05/M06/M14)
    recurso_estados       TEXT[]       DEFAULT ARRAY['libre','ocupado'],
    -- motel: ['libre','ocupado','limpieza','mantencion']
    -- cancha: ['libre','reservada','ocupada']
    alerta_vencimiento_min INT         DEFAULT 15,           -- solo M06

    -- Restricciones de seguridad
    log_acceso_notas      BOOLEAN      DEFAULT FALSE,        -- médico/legal: TRUE
    cifrado_notas         BOOLEAN      DEFAULT FALSE,

    -- Color accent del rubro (afecta UI)
    accent_color          VARCHAR(7)   DEFAULT '#3b82f6',
    -- retail:#f5c518 motel:#ff6b35 medico:#34d399 legal:#818cf8 mecanico:#f97316

    -- Historial vinculado a (M29)
    recurso_historial     VARCHAR(50),             -- 'vehiculo','mascota','expediente'

    created_at            TIMESTAMPTZ  DEFAULT NOW(),
    updated_at            TIMESTAMPTZ  DEFAULT NOW()
);
```

---

## PARTE D: MENÚ DINÁMICO — LÓGICA DE RENDERIZADO

El frontend consulta `rubros_config` al cargar y construye el menú dinámicamente.
**Nunca se hardcodean menús por industria** — todo sale de la config.

### D.1 Lógica JavaScript (Vanilla)

```javascript
// frontend/js/menu-builder.js

const MENU_MAP = {
  admin: [
    { id: 'dashboard',    label: 'Dashboard',         icon: '📊', siempre: true },
    { id: 'ventas',       label: 'Ventas',             icon: '💳', siempre: true },
    { id: 'clientes',     label: cfg => cfg.label_cliente + 's', icon: '👤', siempre: true },
    { id: 'compras',      label: 'Compras',            icon: '📦', modulo: 'M18' },
    { id: 'inventario',   label: 'Inventario',         icon: '🗄️',  modulo: 'M03' },
    { id: 'proveedores',  label: 'Proveedores',        icon: '🏭', modulo: 'M18' },
    { id: 'agenda',       label: 'Agenda',             icon: '📅', modulo: 'M08' },
    { id: 'recetas',      label: 'Recetas',            icon: '🍳', modulo: 'M16' },
    { id: 'delivery',     label: 'Delivery',           icon: '🚚', modulo: 'M13' },
    { id: 'ot',           label: 'Órdenes Trabajo',    icon: '🔧', modulo: 'M28' },
    { id: 'membresias',   label: 'Membresías',         icon: '🎫', modulo: 'M30' },
    { id: 'rrhh',         label: 'RRHH',               icon: '👥', modulo: 'M21' },
    { id: 'reclutamiento',label: 'Reclutamiento',      icon: '💼', modulo: 'M23' },
    { id: 'marketing_qr', label: 'Marketing QR',       icon: '📱', modulo: 'M24' },
    { id: 'sii',          label: 'SII / DTE',          icon: '📄', modulo: 'M20' },
    { id: 'config',       label: 'Configuración',      icon: '⚙️',  siempre: true },
  ],
  pos: [
    { id: 'venta',        label: 'Venta',              icon: '💳', siempre: true },
    { id: 'habitaciones', label: cfg => cfg.label_recurso + 's', icon: '🏨', modulo: 'M14' },
    { id: 'agenda',       label: 'Agenda',             icon: '📅', modulo: 'M08' },
    { id: 'comandas',     label: 'Comandas',           icon: '🍽️',  modulo: 'M15' },
    { id: 'ot',           label: cfg => cfg.label_ot || 'OT', icon: '🔧', modulo: 'M28' },
    { id: 'delivery',     label: 'Delivery',           icon: '🚚', modulo: 'M13' },
    { id: 'encargos',     label: 'Encargos',           icon: '📋', modulo: 'M12' },
    { id: 'caja',         label: 'Caja / Turno',       icon: '💰', siempre: true },
  ]
};

function buildMenu(config, rol) {
  const context = ['admin', 'super_admin'].includes(rol) ? 'admin' : 'pos';
  const items = MENU_MAP[context];

  return items.filter(item => {
    if (item.siempre) return true;
    if (item.modulo) return config.modulos_activos.includes(item.modulo);
    return false;
  }).map(item => ({
    ...item,
    label: typeof item.label === 'function' ? item.label(config) : item.label
  }));
}
```

### D.2 Lógica de Pantalla POS Condicional

```javascript
// La pantalla de venta se adapta según módulos activos

function renderPosVenta(config, venta) {
  return {
    // Siempre presente
    buscador: true,
    carrito: true,
    cobrar: true,

    // Condicional por módulo
    cliente_por_rut:     true,                                    // siempre
    fraccionado:         config.tiene_fraccionado,                // M04
    descuento_volumen:   config.tiene_descuento_vol,              // M26
    fiado_btn:           config.tiene_fiado,                      // M11
    pedido_wa_panel:     config.modulos_activos.includes('M17'),  // M17
    encargo_btn:         config.modulos_activos.includes('M12'),  // M12
    notas_cifradas_btn:  config.tiene_notas_cifradas,             // M10
    tipo_dte_selector:   config.documento_default === 'multiple', // mayorista
    honorarios_btn:      config.modulos_activos.includes('M09'),  // M09
  };
}
```

---

## PARTE E: CONFIGURACIÓN VÍA BOT DE WHATSAPP

### E.1 Flujo de Onboarding Inicial (Nueva Empresa)

```
PASO 1 — IDENTIFICACIÓN
════════════════════════════════════════════════════════════════
Bot: "¡Hola! Soy el asistente de BenderAnd. Voy a configurar
     tu empresa en menos de 5 minutos. ¿Cuál es el nombre de
     tu negocio?"

Usuario: "Clínica Dental Pérez"

Bot: "Perfecto. ¿Cuál es el RUT de tu empresa?"
Usuario: "76.543.210-K"

Bot: "¿En qué ciudad operan?"
Usuario: "Santiago, Las Condes"


PASO 2 — SELECCIÓN DE INDUSTRIA
════════════════════════════════════════════════════════════════
Bot: "¿A qué rubro pertenece tu negocio?

     SALUD Y BIENESTAR
     1️⃣ Médico / Clínica
     2️⃣ Dentista
     3️⃣ Psicólogo / Terapeuta
     4️⃣ Salón de belleza / Spa
     5️⃣ Gimnasio / Fitness

     RETAIL Y COMERCIO
     6️⃣ Abarrotes / Almacén
     7️⃣ Ferretería / Materiales
     8️⃣ Farmacia
     9️⃣ Tienda general

     SERVICIOS
     🔟 Abogados / Estudio jurídico
     1️⃣1️⃣ Taller mecánico
     1️⃣2️⃣ Gasfíter / Técnico

     GASTRONOMÍA Y ALOJAMIENTO
     1️⃣3️⃣ Restaurante
     1️⃣4️⃣ Motel / Hospedaje horas
     1️⃣5️⃣ Hotel / Apart-hotel
     1️⃣6️⃣ Canchas deportivas

     ➡️ Otro: escribe el nombre de tu rubro"

Usuario: "2"  (Dentista)


PASO 3 — CONFIRMACIÓN DE MÓDULOS
════════════════════════════════════════════════════════════════
Bot: "Seleccionaste *Dentista*. Activaré los siguientes módulos:

     ✅ Agenda de citas por profesional
     ✅ Servicios sin stock (consulta, radiografía, limpieza)
     ✅ Boleta de honorarios automática
     ✅ Notas de paciente cifradas (historia clínica)
     ✅ Facturación electrónica SII
     ✅ WhatsApp bot para agendar horas

     ¿Tu clínica tiene farmacia o vende productos? (sí/no)"

Usuario: "sí, vendemos cepillos y pastas"

Bot: "Perfecto, también activo:
     ✅ Stock físico (cepillos, pastas, etc.)
     ✅ Venta directa en caja

     ¿Tienen más de un dentista? (sí/no)"

Usuario: "sí, somos 3"

Bot: "Activado: agenda por profesional para hasta 3 dentistas.
     ¿Cuántos son? (número)"

Usuario: "3"

Bot: "Perfecto. ¿Quieres activar RRHH para gestionar asistencia
     y vacaciones? (sí/no)"

Usuario: "no por ahora"

Bot: "Entendido, lo puedes activar después desde Config."


PASO 4 — CREDENCIALES ADMIN
════════════════════════════════════════════════════════════════
Bot: "Vamos a crear tu cuenta de administrador.
     ¿Cuál será tu usuario? (ej: claudia.perez)"

Usuario: "claudia.perez"

Bot: "Crea una contraseña (mínimo 8 caracteres, incluye un número):"
Usuario: "Dental2026!"

Bot: "✅ Contraseña válida. ¿Tu número WhatsApp para
     notificaciones? (con código país, ej: 56912345678)"

Usuario: "56912345678"


PASO 5 — SII (opcional pero recomendado)
════════════════════════════════════════════════════════════════
Bot: "¿Tienes certificado digital del SII para facturación
     electrónica? (sí/no/más tarde)"

Usuario: "más tarde"

Bot: "Sin problema. Lo configuras cuando quieras desde
     Configuración → SII en tu panel."


PASO 6 — CONFIRMACIÓN Y ACTIVACIÓN
════════════════════════════════════════════════════════════════
Bot: "✅ ¡Listo! Tu cuenta está configurada:

     🏥 *Clínica Dental Pérez*
     📋 Rubro: Dentista
     🌐 Tu panel: https://dental-perez.benderand.cl
     👤 Usuario: claudia.perez
     📱 WhatsApp bot: activo para agendar horas

     MÓDULOS ACTIVOS:
     • Agenda de citas
     • Servicios sin stock
     • Stock físico (productos)
     • Boleta de honorarios
     • SII (pendiente certificado)
     • Bot WhatsApp para pacientes

     *Entra ahora a tu panel y crea a tus 3 dentistas.*
     Si necesitas ayuda, escribe aquí o visita
     docs.benderand.cl 🚀"

Sistema: Crea schema tenant, aplica preset 'dentista',
         ejecuta migraciones, activa módulos, crea usuario admin.
```

---

### E.2 Flujo de Reconfiguración (Admin Existente)

El admin puede reconfigurar desde el bot en cualquier momento.
Los cambios se aplican inmediatamente en UI (sin redeployar).

```
RECONFIGURACIÓN VÍA WHATSAPP
════════════════════════════════════════════════════════════════
Admin: "reconfigurar"  (palabra clave)

Bot: "Hola Claudia 👋 Estás en *Clínica Dental Pérez*.
     ¿Qué quieres cambiar?

     1️⃣ Activar/desactivar módulos
     2️⃣ Cambiar etiquetas (nombre de roles)
     3️⃣ Configurar SII / Certificado digital
     4️⃣ Agregar módulo RRHH
     5️⃣ Cambiar rubro (⚠️ afecta módulos activos)
     6️⃣ Configurar bot WhatsApp
     7️⃣ Gestionar usuarios"

Admin: "1"

Bot: "Módulos actuales de tu empresa:

     ✅ Agenda de citas
     ✅ Servicios sin stock
     ✅ Stock físico
     ✅ Boleta de honorarios
     ❌ RRHH (inactivo)
     ❌ Marketing QR (inactivo)
     ❌ Delivery (inactivo)

     ¿Qué módulo quieres activar o desactivar?
     Escribe el nombre del módulo."

Admin: "activar RRHH"

Bot: "✅ RRHH activado. Ya aparece en tu menú de admin.
     ¿Quieres agregar tus empleados ahora o más tarde?"
```

---

### E.3 Flujo de Configuración del Bot WhatsApp por Rubro

```
CONFIGURAR BOT WHATSAPP
════════════════════════════════════════════════════════════════
Admin: "configurar bot"

Bot: "Configuremos el bot para tus pacientes 🤖

     PERSONALIDAD DEL BOT
     ¿Cómo quieres que responda tu bot?
     1️⃣ Formal y profesional (recomendado para salud)
     2️⃣ Cercano y amigable
     3️⃣ Técnico y preciso
     4️⃣ Personalizado (define el tono tú)"

Admin: "1"

Bot: "Perfecto. ¿Cuál es el nombre del bot?
     (por defecto: 'Asistente Dental Pérez')"

Admin: "Sofía"

Bot: "Sofía quedará como nombre del asistente.

     HORARIO DE ATENCIÓN DEL BOT
     ¿En qué horario responde Sofía automáticamente?
     (Fuera de horario puede responder igualmente
      con mensaje de 'fuera de horario')

     Lunes a viernes: ¿qué horario? (ej: 09:00-18:00)"

Admin: "09:00-20:00"

Bot: "¿Sábados?"
Admin: "09:00-14:00"

Bot: "¿Domingos?"
Admin: "cerrado"

Bot: "INTENCIONES ACTIVAS (según tu rubro)
     Para Dentista tengo estas acciones disponibles.
     Confirma las que quieres activar:

     ✅ Agendar hora con dentista
     ✅ Consultar disponibilidad
     ✅ Recordatorio de cita (automático)
     ✅ Cancelar o reagendar cita
     ✅ Consultar precios de servicios
     ✅ Preguntas frecuentes
     ❓ ¿Activar también pedidos de productos? (cepillos, etc.) sí/no"

Admin: "no"

Bot: "Perfecto. ¿Tienes preguntas frecuentes para agregar?
     (ej: ¿Aceptan Fonasa? ¿Tienen estacionamiento?)
     Escribe cada pregunta y su respuesta, o escribe 'listo' para terminar."

Admin: "¿Aceptan Fonasa? → Sí, aceptamos Fonasa libre elección"
Admin: "¿Hay estacionamiento? → Sí, frente a la clínica"
Admin: "listo"

Bot: "✅ Bot configurado. Sofía ya está lista para atender
     a tus pacientes en WhatsApp 🎉

     RESUMEN:
     • Nombre: Sofía
     • Tono: Formal y profesional
     • Horario: L-V 09-20 / S 09-14 / D cerrado
     • Acciones: Agenda · Disponibilidad · Recordatorios · Cancelación · Precios
     • FAQ: 2 preguntas configuradas

     ¿Quieres hacer una prueba ahora?"
```

---

### E.4 API Endpoints — Configuración (Laravel)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/config/rubro` | Obtener config actual del tenant | admin |
| PUT | `/api/config/rubro` | Actualizar módulos activos y etiquetas | admin |
| GET | `/api/config/modulos-disponibles` | Lista de todos los módulos con estado activo/inactivo | admin |
| POST | `/api/config/modulos/{id}/toggle` | Activar o desactivar un módulo | admin |
| GET | `/api/config/industrias-preset` | Lista de presets disponibles | admin |
| POST | `/api/config/aplicar-preset/{industria}` | Aplica un preset (⚠️ reemplaza config actual) | admin |
| PUT | `/api/config/etiquetas` | Actualizar etiquetas de roles y recursos | admin |
| PUT | `/api/config/bot-whatsapp` | Configurar personalidad, horario e intenciones del bot | admin |
| GET | `/api/config/bot-whatsapp` | Obtener config actual del bot | admin |
| POST | `/webhook/wa/config` | Endpoint que recibe comandos del bot (reconfiguración) | bot |

---

### E.5 Modelo de Datos — Bot Config

```sql
-- Schema: tenant_{uuid}
CREATE TABLE bot_config (
    id                BIGSERIAL PRIMARY KEY,
    nombre_bot        VARCHAR(100) DEFAULT 'Asistente',
    personalidad      VARCHAR(50)  DEFAULT 'formal',     -- 'formal','amigable','tecnico','custom'
    personalidad_custom TEXT,                             -- prompt personalizado
    horario           JSONB,
    -- {
    --   "lunes":    {"abre": "09:00", "cierra": "20:00"},
    --   "sabado":   {"abre": "09:00", "cierra": "14:00"},
    --   "domingo":  {"cerrado": true}
    -- }
    mensaje_fuera_horario TEXT DEFAULT '¡Hola! Estamos fuera de horario. Te contactamos pronto.',
    intenciones_activas TEXT[],
    -- ['agendar','disponibilidad','cancelar','precios','faq','stock','pedido','deuda']
    faq               JSONB,
    -- [{"pregunta": "¿Aceptan Fonasa?", "respuesta": "Sí, libre elección"}]
    handover_trigger  TEXT[],    -- palabras que activan handover humano
    -- default: ['humano','ayuda','urgente','problema']
    telegram_alert_id VARCHAR(50),  -- ID Telegram del admin para alertas handover
    activo            BOOLEAN DEFAULT TRUE,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_at        TIMESTAMPTZ DEFAULT NOW()
);
```

---

---

## PARTE G: MÓDULO M31 — VENTA DE SOFTWARE SAAS (BENDERAND SE VENDE A SÍ MISMO)

> Este módulo convierte BenderAnd ERP en su propio canal de ventas.
> La empresa que opera BenderAnd (u otro proveedor de software) usa el mismo
> sistema para gestionar sus clientes (tenants), facturar mensualmente, hacer
> seguimiento comercial y ver métricas de negocio SaaS.
> **No existe un sistema separado para esto — BenderAnd vive dentro de BenderAnd.**

---

### G.1 Ficha de Industria — PROVEEDOR SOFTWARE / SAAS

```
Módulos activos:  M01 M07 M20 M21 M22 M23 M24 M25 M27 M31
Etiquetas:
  operario_label:    "Ejecutivo de cuenta"
  cliente_label:     "Tenant / Empresa"
  cajero_label:      "Soporte / Ventas"
  documento_fiscal:  "Factura"
  producto_label:    "Plan / Módulo"
  nota_label:        "Notas de cuenta (CRM)"

POS muestra:
  [Panel de tenants] [Nueva empresa] [Renovaciones hoy]
  [Onboarding activo] [Cuenta por cobrar] [Demo agendada]

Admin muestra:
  Dashboard MRR · Tenants · Pipeline ventas
  Facturación recurrente · RRHH · Reclutamiento
  Marketing QR · SII · Multi-sucursal · Config

POS NO muestra:
  Stock físico · Fraccionado · Timers · Habitaciones
  Comandas · Recetas · Delivery · Fiado · OT
  Compras de mercancía física

Menú POS (Ejecutivo de cuenta):
  🏢 Tenants   💬 Pipeline   📅 Demos   💳 Cobros   📊 MRR
```

---

### G.2 Acciones por Contexto — M31

| Acción | Contexto POS | Contexto Admin |
|---|---|---|
| Ver panel de tenants activos | ✅ ejecutivo | ✅ admin |
| Crear nuevo tenant (onboarding) | ✅ ejecutivo | ✅ admin |
| Activar / suspender tenant | ✅ ejecutivo (con motivo) | ✅ admin |
| Ver estado de pago por tenant | ✅ ejecutivo | ✅ admin |
| Registrar cobro manual | ✅ ejecutivo | ✅ admin |
| Cambiar plan de un tenant | — | ✅ admin |
| Ver MRR, churn, LTV | — | ✅ admin |
| Pipeline de prospectos | ✅ ejecutivo | ✅ admin |
| Agendar demo | ✅ ejecutivo | ✅ admin |
| Registrar notas de cuenta (CRM) | ✅ ejecutivo | ✅ admin |
| Ver historial de conversaciones WA por prospecto | ✅ ejecutivo | ✅ admin |
| Generar factura mensual | — | ✅ admin (automático) |
| Ver métricas de uso por tenant | — | ✅ admin |
| Configurar precios de planes | — | ✅ admin |
| Configurar addons / módulos extra | — | ✅ admin |

---

### G.3 Modelo de Datos — M31 (schema: tenant del proveedor software)

```sql
-- El proveedor de software es EN SÍ MISMO un tenant en BenderAnd.
-- Su schema contiene las tablas propias de su negocio SaaS.

-- ── PLANES SAAS que vende ──────────────────────────────────────────
CREATE TABLE saas_planes (
    id               BIGSERIAL PRIMARY KEY,
    codigo           VARCHAR(50) UNIQUE NOT NULL,   -- 'basico','pro','enterprise'
    nombre           VARCHAR(255) NOT NULL,
    descripcion      TEXT,
    precio_mensual   BIGINT NOT NULL,               -- centavos CLP
    precio_anual     BIGINT,                        -- con descuento
    max_usuarios     INT DEFAULT 5,
    max_productos    INT DEFAULT 0,                 -- 0 = ilimitado
    modulos_incluidos TEXT[] NOT NULL,              -- ['M01','M03','M07',...]
    modulos_addon    TEXT[],                        -- módulos que se pueden agregar
    soporte_nivel    VARCHAR(50) DEFAULT 'email',   -- 'email','chat','dedicado'
    activo           BOOLEAN DEFAULT TRUE,
    created_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ── CLIENTES SAAS (los tenants que compra el proveedor) ───────────
CREATE TABLE saas_clientes (
    id               BIGSERIAL PRIMARY KEY,
    uuid             VARCHAR(36) UNIQUE NOT NULL,
    tenant_uuid      VARCHAR(36),                   -- UUID en benderand_master.tenants
    razon_social     VARCHAR(255) NOT NULL,
    rut              VARCHAR(20),
    industria        VARCHAR(100),                  -- 'dentista','motel','retail'...
    contacto_nombre  VARCHAR(255),
    contacto_whatsapp VARCHAR(20),
    contacto_email   VARCHAR(255),
    plan_id          BIGINT REFERENCES saas_planes(id),
    modulos_addon    TEXT[],                        -- addons activos
    estado           VARCHAR(50) DEFAULT 'trial',   -- trial|activo|moroso|suspendido|cancelado
    fecha_inicio     DATE NOT NULL,
    fecha_trial_fin  DATE,
    fecha_proximo_cobro DATE,
    ciclo_facturacion VARCHAR(20) DEFAULT 'mensual', -- 'mensual'|'anual'
    precio_actual    BIGINT NOT NULL,               -- puede ser diferente al plan (negociado)
    descuento_pct    DECIMAL(5,2) DEFAULT 0,
    ejecutivo_id     BIGINT,                        -- FK usuarios (quién lo atiende)
    notas_crm        TEXT,                          -- notas internas del ejecutivo
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ── PIPELINE DE VENTAS (prospectos) ──────────────────────────────
CREATE TABLE saas_pipeline (
    id               BIGSERIAL PRIMARY KEY,
    razon_social     VARCHAR(255),
    contacto_nombre  VARCHAR(255),
    contacto_whatsapp VARCHAR(20),
    contacto_email   VARCHAR(255),
    industria        VARCHAR(100),
    etapa            VARCHAR(50) DEFAULT 'nuevo',
    -- 'nuevo'|'contactado'|'demo_agendada'|'demo_hecha'|'propuesta'|'negociacion'|'ganado'|'perdido'
    plan_interes     BIGINT REFERENCES saas_planes(id),
    valor_estimado   BIGINT,
    probabilidad_pct INT DEFAULT 20,
    ejecutivo_id     BIGINT,
    fecha_proximo_contacto DATE,
    motivo_perdida   TEXT,                          -- si etapa = 'perdido'
    notas            TEXT,
    origen           VARCHAR(50),                   -- 'whatsapp','qr','referido','demo','web'
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ── ACTIVIDADES CRM (log de interacciones) ────────────────────────
CREATE TABLE saas_actividades (
    id               BIGSERIAL PRIMARY KEY,
    cliente_id       BIGINT REFERENCES saas_clientes(id),
    pipeline_id      BIGINT REFERENCES saas_pipeline(id),
    tipo             VARCHAR(50),                   -- 'llamada'|'wa'|'email'|'demo'|'visita'|'nota'
    descripcion      TEXT NOT NULL,
    resultado        TEXT,
    ejecutivo_id     BIGINT NOT NULL,
    fecha            TIMESTAMPTZ DEFAULT NOW()
);

-- ── COBROS / BILLING RECURRENTE ──────────────────────────────────
CREATE TABLE saas_cobros (
    id               BIGSERIAL PRIMARY KEY,
    cliente_id       BIGINT REFERENCES saas_clientes(id) NOT NULL,
    periodo          DATE NOT NULL,                 -- 2026-03-01 (primer día del mes)
    monto            BIGINT NOT NULL,
    descuento        BIGINT DEFAULT 0,
    total            BIGINT NOT NULL,
    estado           VARCHAR(50) DEFAULT 'pendiente', -- 'pendiente'|'pagado'|'vencido'|'anulado'
    fecha_vencimiento DATE NOT NULL,
    fecha_pago       DATE,
    metodo_pago      VARCHAR(50),                   -- 'transferencia'|'webpay'|'cheque'
    dte_id           BIGINT,                        -- FK dte_emitidos (factura generada)
    referencia_pago  VARCHAR(255),
    created_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ── MÉTRICAS SAAS (calculadas, actualizadas por Job diario) ──────
CREATE TABLE saas_metricas (
    id               BIGSERIAL PRIMARY KEY,
    fecha            DATE NOT NULL UNIQUE,
    mrr              BIGINT,                        -- Monthly Recurring Revenue
    arr              BIGINT,                        -- Annual Run Rate
    tenants_activos  INT,
    tenants_trial    INT,
    tenants_morosos  INT,
    nuevos_mes       INT,
    cancelados_mes   INT,
    churn_rate       DECIMAL(5,2),
    ltv_promedio     BIGINT,
    arpu             BIGINT,                        -- Average Revenue Per User
    created_at       TIMESTAMPTZ DEFAULT NOW()
);

-- ── DEMOS AGENDADAS ───────────────────────────────────────────────
CREATE TABLE saas_demos (
    id               BIGSERIAL PRIMARY KEY,
    pipeline_id      BIGINT REFERENCES saas_pipeline(id),
    fecha            DATE NOT NULL,
    hora             TIME NOT NULL,
    modalidad        VARCHAR(30) DEFAULT 'videollamada', -- 'videollamada'|'presencial'
    link_reunion     VARCHAR(500),
    ejecutivo_id     BIGINT NOT NULL,
    duracion_min     INT DEFAULT 45,
    asistio          BOOLEAN,
    notas_post_demo  TEXT,
    siguiente_paso   TEXT,
    created_at       TIMESTAMPTZ DEFAULT NOW()
);
```

---

### G.4 POS — Panel de Tenants (Vista Ejecutivo de Cuenta)

```
┌──────────────────────────────────────────────────────────┐
│  🏢 TENANTS ACTIVOS                       [Hoy 14:23]    │
├──────────────────────────────────────────────────────────┤
│  RESUMEN RÁPIDO                                          │
│  ┌──────────┬──────────┬──────────┬────────────────────┐ │
│  │ Activos  │ Trial    │ Morosos  │ Cobros este mes    │ │
│  │ 147      │ 23       │ 8        │ $12.847.000        │ │
│  └──────────┴──────────┴──────────┴────────────────────┘ │
│                                                          │
│  ⚠️ REQUIEREN ATENCIÓN HOY                               │
│  🔴 Dental Pérez   · vence trial en 2 días · [Llamar]   │
│  🔴 Ferretería Sur · 15 días morosa        · [Cobrar]   │
│  🟡 Motel Los Pinos· sin SII configurado   · [Activar]  │
│  🟡 Gym FitZone    · 1 usuario solo activo · [Revisar]  │
│                                                          │
│  RENOVACIONES HOY (3)                                    │
│  Clínica Norte    · Pro · $89.000  · [✅ Cobrado]        │
│  Abarrotes Don J. · Básico · $39.000 · [Cobrar] [WA]   │
│  Estudio Morales  · Pro · $89.000  · [Cobrar] [WA]     │
│                                                          │
│  [🔍 Buscar tenant] [➕ Nueva empresa] [📊 Ver MRR]       │
└──────────────────────────────────────────────────────────┘
```

---

### G.5 POS — Pipeline de Ventas (Vista Ejecutivo)

```
┌──────────────────────────────────────────────────────────┐
│  💬 PIPELINE COMERCIAL                    [Semana 11]    │
├──────────────────────────────────────────────────────────┤
│  NUEVO(4)    CONTACTADO(6)   DEMO(3)   PROPUESTA(2)      │
│  ─────────   ──────────────  ────────  ─────────────     │
│  Gym Activo  Clínica XYZ     Restau.   Motel Azul        │
│  Abarrotes B Ferretería M    Dental P  Cancha Norte      │
│  Motel Sol   Taller Auto     [Ver]     $178.000/mes      │
│  [+] nuevo   [+] contactado  ─────────────────────       │
│                                                          │
│  📅 DEMOS ESTA SEMANA                                    │
│  Lun 15:00 · Restaurante Costa · Zoom · Juan Pérez      │
│  Mié 10:00 · Dental Pérez · Presencial · María López    │
│  Jue 16:30 · Clínica XYZ · Zoom · Juan Pérez           │
│                                                          │
│  🎯 MI PIPELINE (Juan Pérez)                             │
│  8 prospectos · $2.340.000 potencial · Conv. est. 38%   │
│                                                          │
│  [➕ NUEVO PROSPECTO] [📅 AGENDAR DEMO] [📊 FORECAST]    │
└──────────────────────────────────────────────────────────┘
```

---

### G.6 POS — Detalle de Tenant (Ficha CRM)

```
┌──────────────────────────────────────────────────────────┐
│  🏥 CLÍNICA DENTAL PÉREZ                  [Activo ✅]    │
├──────────────────────────────────────────────────────────┤
│  RUT: 76.543.210-K   Industria: Dentista                 │
│  Contacto: Claudia Pérez · +56 9 1234 5678              │
│  Plan: PRO · $89.000/mes · Ciclo: mensual                │
│  Inicio: 01/01/2026 · Próx. cobro: 01/04/2026           │
│  Módulos: M01 M07 M08 M09 M10 M20 M21                   │
│                                                          │
│  USO DEL SISTEMA (último mes)                            │
│  Ventas registradas: 234  · Sesiones: 1.847             │
│  Usuarios activos: 4/5   · WA bot: 312 conv.            │
│  DTE emitidos: 210        · Último login: hace 2h        │
│                                                          │
│  BILLING                                                 │
│  Marzo 2026: $89.000 ✅ Pagado el 05/03                 │
│  Abril 2026: $89.000 ⏳ Vence 01/04                     │
│                                                          │
│  HISTORIAL CRM                                           │
│  12/03 · WA · "Preguntó por módulo RRHH" — Claudia L.  │
│  05/03 · Pago · Transferencia $89.000 confirmada        │
│  01/03 · Sistema · Renovación automática generada       │
│                                                          │
│  [💳 COBRAR] [📝 NOTA CRM] [⚙️ CAMBIAR PLAN]            │
│  [📞 LLAMAR] [💬 WA] [⏸️ SUSPENDER]                      │
└──────────────────────────────────────────────────────────┘
```

---

### G.7 Admin — Dashboard MRR y Métricas SaaS

```
┌──────────────────────────────────────────────────────────┐
│  📊 MÉTRICAS SAAS                         [Marzo 2026]   │
├──────────────────────────────────────────────────────────┤
│  ┌────────────┬────────────┬────────────┬──────────────┐ │
│  │ MRR        │ ARR        │ Churn      │ ARPU         │ │
│  │ $13.083.000│$156.996.000│ 2.1%       │ $89.000      │ │
│  │ ↑ 8.3% mes │            │ ↓ bueno    │              │ │
│  └────────────┴────────────┴────────────┴──────────────┘ │
│                                                          │
│  📈 MRR ÚLTIMOS 12 MESES                                 │
│  [gráfico de área / curva de crecimiento]                │
│                                                          │
│  DISTRIBUCIÓN POR PLAN             DISTRIBUCIÓN POR RUBRO│
│  Básico:  89 tenants  $3.471.000   Retail:   34 (23%)   │
│  Pro:     47 tenants  $4.183.000   Médico:   28 (19%)   │
│  Enterprise: 11 ten.  $5.429.000   Gastron.: 22 (15%)   │
│                                    Otros:    63 (43%)    │
│                                                          │
│  COHORTS (retención a 6 meses)                           │
│  Oct 2025: 89%   Nov 2025: 92%   Dic 2025: 95%          │
│  Ene 2026: 97%   Feb 2026: 95%   Mar 2026: 98% (30d)    │
│                                                          │
│  ⚠️ ACCIÓN REQUERIDA                                     │
│  8 tenants morosos · Deuda total: $712.000               │
│  23 trials · 11 vencen esta semana                       │
│  [Ver detalle] [Exportar CSV]                            │
└──────────────────────────────────────────────────────────┘
```

---

### G.8 Admin — Gestión de Planes y Precios

```
┌──────────────────────────────────────────────────────────┐
│  ⚙️ PLANES SAAS                                           │
├──────────────────────────────────────────────────────────┤
│  BÁSICO              PRO               ENTERPRISE        │
│  $39.000/mes         $89.000/mes       $189.000/mes      │
│  ────────────────    ────────────────  ──────────────    │
│  Hasta 3 usuarios    Hasta 10 usuarios Ilimitado         │
│  M01 M03 M07 M20     + M08 M13 M17    + M21 M22 M27     │
│  Email soporte       + M21 M24         + M23 M31         │
│                      Chat soporte      Soporte dedicado  │
│                                                          │
│  89 tenants activos  47 tenants        11 tenants        │
│                                                          │
│  ADDONS (precio mensual adicional)                       │
│  M16 Recetas:       +$15.000/mes  (12 activos)          │
│  M22 Liquidaciones: +$25.000/mes  (18 activos)          │
│  M27 Multi-sucursal:+$35.000/mes  (5 activos)           │
│  M31 SaaS (este):   incluido en Enterprise              │
│                                                          │
│  [✏️ EDITAR PLAN] [➕ NUEVO PLAN] [📊 ANÁLISIS PRECIOS]  │
└──────────────────────────────────────────────────────────┘
```

---

### G.9 Flujo WhatsApp — Onboarding de Nuevo Cliente SaaS

```
PROSPECTO → CLIENTE EN 5 PASOS VÍA WHATSAPP
════════════════════════════════════════════════════════════════

PASO 1 — CAPTACIÓN
Bot (BenderAnd): "¡Hola! Soy el asistente de BenderAnd.
  ¿Quieres ver una demostración del sistema? Es gratis.
  ¿Cuál es tu rubro? (ej: almacén, clínica, restaurante)"

Prospecto: "tengo un restaurante y delivery"

Bot: "Perfecto. BenderAnd tiene módulos específicos para
  restaurantes: comandas, recetas con costeo, delivery con
  tracking y pedidos por WhatsApp.
  ¿Te interesa una demo de 30 minutos?

  1️⃣ Sí, agendar demo ahora
  2️⃣ Primero quiero ver precios
  3️⃣ Quiero probarlo directamente (trial gratis)"

→ Sistema: Crea registro en saas_pipeline con etapa='nuevo',
           industria='restaurante', origen='whatsapp'

PASO 2 — AGENDAMIENTO DEMO
Prospecto: "3" (trial directo)

Bot: "Genial. En 2 minutos activo tu cuenta de prueba.
  Necesito solo:
  ¿Nombre de tu restaurante?"

Prospecto: "Donde Juan"

Bot: "¿Tu RUT de empresa? (para la cuenta)"
Prospecto: "76.111.222-3"

Bot: "¿Tu WhatsApp personal? (para notificaciones)"
→ Ya lo tenemos del número de WA actual.

Bot: "✅ Activando tu cuenta de prueba...

  🍽️ Tu restaurante *Donde Juan* está listo.
  Accede en: https://donde-juan.benderand.cl
  Usuario: admin · Clave temporal: [generada]

  Trial: 30 días gratis
  Plan activado: PRO (todos los módulos restaurante)

  Módulos activos:
  ✅ Comandas y cocina
  ✅ Recetas con costeo de ingredientes
  ✅ Delivery con tracking
  ✅ Pedidos por WhatsApp
  ✅ SII / Boletas electrónicas
  ✅ WhatsApp bot para tus clientes

  Te llamo mañana para mostrarte el sistema. ¿Te va bien?"

→ Sistema:
  1. Crea tenant en benderand_master.tenants
  2. Crea schema tenant_{uuid} con migraciones
  3. Aplica preset 'restaurante' en rubros_config
  4. Crea usuario admin con clave temporal
  5. Registra en saas_clientes con estado='trial'
  6. Agenda seguimiento en saas_pipeline (etapa='trial_activo')
  7. Job: notificación interna al ejecutivo asignado

PASO 3 — SEGUIMIENTO AUTOMÁTICO (día 7)
Bot: "Hola! Han pasado 7 días desde que activaste Donde Juan.
  ¿Cómo va la experiencia?

  Veo que han registrado:
  📊 34 comandas · 12 entregas · $234.500 en ventas

  ¿Hay algo que no entiendas o quieras mejorar?
  Te puedo conectar con un ejecutivo ahora mismo."

PASO 4 — CONVERSIÓN (día 25 del trial)
Bot: "Tu prueba gratis de BenderAnd termina en 5 días.

  Para seguir usando el sistema sin interrupciones:

  PLAN PRO: $89.000/mes (lo que tienes ahora)
  Incluye todos los módulos de restaurante.

  Para contratar, escoge cómo pagar:
  1️⃣ Transferencia bancaria
  2️⃣ Webpay (tarjeta)
  3️⃣ Hablar con un ejecutivo primero"

PASO 5 — COBRO Y ACTIVACIÓN
Prospecto: "1" (transferencia)

Bot: "Banco: Scotiabank
  Cuenta corriente: 123456789
  RUT: 76.000.000-1
  Monto: $89.000
  Glosa: BENDERAND-{uuid}

  Una vez que transfieras, sube el comprobante aquí
  o envíalo a pagos@benderand.cl.
  Activamos en menos de 1 hora hábil."

→ Sistema: al recibir confirmación de pago:
  - Cambia saas_clientes.estado = 'activo'
  - Genera cobro en saas_cobros
  - Emite factura (dte_emitidos, M20)
  - Envía factura al email del contacto
  - Envía confirmación WA con datos de acceso
```

---

### G.10 API Endpoints — M31

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/saas/tenants` | Lista clientes SaaS con filtros (estado, plan, ejecutivo) | admin, ejecutivo |
| POST | `/api/saas/tenants` | Crear nuevo tenant + activar trial | admin, ejecutivo |
| GET | `/api/saas/tenants/{id}` | Detalle de cliente: uso, billing, CRM | admin, ejecutivo |
| PUT | `/api/saas/tenants/{id}/plan` | Cambiar plan | admin |
| PUT | `/api/saas/tenants/{id}/estado` | Activar / suspender / cancelar | admin, ejecutivo |
| GET | `/api/saas/metricas` | MRR, ARR, churn, ARPU, cohorts | admin |
| GET | `/api/saas/pipeline` | Lista de prospectos con etapa | admin, ejecutivo |
| POST | `/api/saas/pipeline` | Crear prospecto | admin, ejecutivo |
| PUT | `/api/saas/pipeline/{id}/etapa` | Avanzar etapa del prospecto | ejecutivo |
| POST | `/api/saas/demos` | Agendar demo | ejecutivo |
| GET | `/api/saas/cobros` | Lista cobros pendientes / vencidos | admin |
| POST | `/api/saas/cobros/{id}/registrar-pago` | Confirmar pago manual | admin, ejecutivo |
| POST | `/api/saas/cobros/generar-ciclo` | Generar cobros del mes (Job automático) | sistema |
| GET | `/api/saas/planes` | Lista planes y addons activos | admin |
| PUT | `/api/saas/planes/{id}` | Editar precio o módulos de un plan | admin |
| POST | `/api/saas/actividades` | Registrar actividad CRM | ejecutivo |
| GET | `/api/saas/tenants/{id}/uso` | Métricas de uso real del tenant | admin |
| POST | `/webhook/wa/saas-onboarding` | Recibe evento bot → crea trial | bot |

---

### G.11 Jobs Automáticos — M31

| Job | Frecuencia | Acción |
|---|---|---|
| `GenerarCobrosRecurrentes` | 1° de cada mes, 08:00 | Crea registros en `saas_cobros` para todos los activos |
| `AlertaTrialVencimiento` | Diario 09:00 | Envía WA al prospecto a los 25 días de trial |
| `AlertaMorosos` | Diario 10:00 | Envía WA de cobro a tenants vencidos +5 días |
| `SuspenderMorosos` | Diario 11:00 | Suspende tenants con +30 días sin pagar |
| `ActualizarMetricasMRR` | Diario 00:30 | Recalcula y guarda fila en `saas_metricas` |
| `SeguimientoTrialDia7` | Diario 09:30 | Envía WA de engagement a trials activos de 7 días |
| `EmitirFacturasDelMes` | 1° de cada mes, 09:00 | Emite DTE por cada cobro del ciclo (usa M20/SII) |
| `ReporteEjecutivos` | Lunes 08:00 | Envía resumen semanal al equipo comercial vía WA |

---

## PARTE H: ACTUALIZACIÓN TABLA MÓDULOS → SISTEMA

| Módulo | Menú Admin | Menú POS | Pantalla POS | Etiqueta |
|---|---|---|---|---|
| M03 Stock | + Inventario | — | Buscar con stock visible | — |
| M05 Renta | — | — | Items tipo renta en carrito | — |
| M06 Renta hora | — | + Recursos/Mapa | Mapa + timers | `label_recurso` |
| M07 Servicios | — | — | Items sin stock en búsqueda | `label_operario` |
| M08 Agenda | + Agenda | + Agenda | Vista calendario | — |
| M09 Honorarios | + SII (honorarios) | + Honorarios btn | Botón "Boleta honorarios" | — |
| M10 Notas cifradas | — | — | Campo notas bloqueado por rol | `label_nota` |
| M11 Fiado | — | — | Botón "Fiado" en cobro | — |
| M13 Delivery | + Delivery | + Delivery | Panel de entregas | — |
| M14 Mapa recursos | — | + Recursos | Mapa visual estado | `label_recurso` |
| M15 Comandas | — | + Comandas | Vista cocina | — |
| M16 Recetas | + Recetas | — | — | — |
| M18 Compras | + Compras + Proveedores | — | — | — |
| M21 RRHH | + RRHH | — | Marcación asistencia en login | — |
| M23 Reclutamiento | + Reclutamiento | — | — | — |
| M24 QR | + Marketing QR | — | — | — |
| M28 OT | + OT | + OT | Kanban órdenes | `label_ot` |
| M30 Membresías | + Membresías | + Check-in | Botón check-in socio | — |
| **M31 SaaS** | **+ Tenants + Pipeline + MRR + Planes** | **+ Panel tenants + Pipeline + Cobros** | **Panel tenants / Ficha CRM / Pipeline Kanban** | `label_cliente="Tenant"` |

---

*BenderAnd ERP — Sistema de Configuración por Industria v1.2*
*La configuración se ejecuta vía bot de WhatsApp. Sin código. Sin redeployar.*
*Cada empresa ve exactamente lo que necesita. Nada más.*
*M31: BenderAnd vive dentro de BenderAnd — el sistema es su propio canal de ventas.*
*M32: CRM Modular con herramientas C01–C15 configuradas por industria. POS + Admin + Bot WA.*
