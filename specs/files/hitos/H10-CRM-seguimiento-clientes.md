# H-CRM — Módulo CRM: Seguimiento de Clientes
**BenderAnd ERP · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ Pendiente · Duración estimada: 4 semanas · Requiere: H1 + H4 + H7*

> **Módulo M32 — CRM Modular**
>
> CRM que se adapta a cada industria. Un almacén no necesita pipeline de ventas.
> Un abogado no necesita frecuencia de compra. Un motel no necesita historial clínico.
> Cada empresa ve exactamente las herramientas de seguimiento de su rubro.
> Accesible desde el POS, el panel admin y el bot de WhatsApp.

---

## PARTE A: HERRAMIENTAS ATÓMICAS DEL CRM (C01–C15)

Igual que los módulos M01–M31, el CRM se compone de herramientas independientes
que se activan según la industria. Ninguna empresa ve lo que no necesita.

| ID | Herramienta | Contexto | Descripción |
|---|---|---|---|
| `C01` | **Ficha cliente base** | pos + admin | RUT, nombre, contacto, historial de compras. Siempre activo. |
| `C02` | **Historial de compras** | pos + admin | Timeline de ventas con ítems, totales y documentos |
| `C03` | **Deudas y crédito** | pos + admin | Fiado activo, historial de pagos, límite de crédito |
| `C04` | **Notas internas** | admin | Notas libres del equipo sobre el cliente (no cifradas) |
| `C05` | **Notas cifradas** | pos + admin | Expediente protegido por rol (médico, legal, psicólogo) |
| `C06` | **Pipeline de oportunidades** | admin | Etapas de venta: prospecto → contactado → propuesta → ganado/perdido |
| `C07` | **Seguimiento de actividades** | admin | Log de llamadas, reuniones, emails, WhatsApp por cliente |
| `C08` | **Recordatorios y tareas** | admin + bot | Tarea asignada a usuario con fecha límite y alerta WA |
| `C09` | **Segmentación y etiquetas** | admin | Tags libres + segmentos automáticos por comportamiento |
| `C10` | **Frecuencia y recencia** | admin | RFM: recencia, frecuencia, valor monetario por cliente |
| `C11` | **Historial por recurso** | pos + admin | Historial vinculado a patente, mascota, habitación, expediente |
| `C12` | **Encargos y reservas** | pos + admin | Pedidos especiales con seguimiento y notificaciones |
| `C13` | **Membresías activas** | pos + admin | Plan vigente, sesiones disponibles, vencimiento |
| `C14` | **Satisfacción y NPS** | bot + admin | Encuesta post-servicio automática, score por cliente |
| `C15` | **Campañas dirigidas** | admin + bot | Envío masivo por segmento con mensaje personalizado via WA |

---

## PARTE B: CRM POR INDUSTRIA

### B.1 Mapa industria → herramientas CRM activas

```
INDUSTRIA               HERRAMIENTAS CRM ACTIVAS
─────────────────────────────────────────────────────────────────────
RETAIL / ABARROTES      C01 C02 C03 C04 C09 C10 C12 C15
MAYORISTA / FERRETERÍA  C01 C02 C03 C04 C06 C07 C08 C09 C10 C12 C15 C26
RESTAURANTE             C01 C02 C04 C09 C10 C14 C15
MOTEL / HOSPEDAJE HORA  C01 C02 C04 C11
HOTEL / ALOJAMIENTO     C01 C02 C03 C04 C07 C08 C11 C14
CANCHAS / DEPORTES      C01 C02 C04 C08 C09 C12 C13 C14 C15
MÉDICO / CLÍNICA        C01 C02 C04 C05 C08 C09 C11 C12 C14
DENTISTA                C01 C02 C04 C05 C08 C11 C12 C14
ABOGADOS / LEGAL        C01 C02 C04 C05 C06 C07 C08 C09 C11
TALLER MECÁNICO         C01 C02 C03 C04 C07 C08 C09 C11 C12
SALÓN / SPA             C01 C02 C04 C08 C09 C12 C13 C14 C15
VETERINARIA             C01 C02 C04 C05 C08 C09 C11 C12 C14
FARMACIA                C01 C02 C03 C04 C09 C10 C15
GIMNASIO / FITNESS      C01 C02 C04 C08 C09 C13 C14 C15
INMOBILIARIA            C01 C02 C04 C06 C07 C08 C09 C14 C15
SOFTWARE SAAS (M31)     C01 C02 C04 C06 C07 C08 C09 C14 C15
─────────────────────────────────────────────────────────────────────
```

### B.2 Etiquetas por industria

Las etiquetas del CRM se adaptan a la terminología del rubro:

| Industria | `label_cliente` | `label_recurso` | `label_expediente` | `label_pipeline` |
|---|---|---|---|---|
| Retail / Almacén | Cliente | — | — | — |
| Ferretería | Cliente / Empresa | — | — | Cotización |
| Abogado | Caso / Cliente | Expediente | Expediente | Caso |
| Médico / Dentista | Paciente | Historia clínica | Historia clínica | — |
| Psicólogo | Paciente | Sesión | Ficha clínica | — |
| Taller mecánico | Cliente | Vehículo | OT | Presupuesto |
| Veterinaria | Tutor | Mascota | Ficha veterinaria | — |
| Inmobiliaria | Interesado | Propiedad | Expediente | Oportunidad |
| SaaS (BenderAnd) | Tenant / Empresa | Cuenta | — | Pipeline |

---

## PARTE C: MODELO DE DATOS

### C.1 Tabla: `crm_contactos` (schema tenant)

> Extiende la tabla `clientes` existente. No la reemplaza — se une via `cliente_id`.
> Clientes simples (sin CRM activo) siguen en `clientes` sin cambios.

```sql
CREATE TABLE crm_contactos (
    id                  BIGSERIAL PRIMARY KEY,
    cliente_id          BIGINT UNIQUE REFERENCES clientes(id) ON DELETE CASCADE,

    -- Datos extendidos (opcionales)
    empresa             VARCHAR(255),
    cargo               VARCHAR(100),
    cumpleanos          DATE,
    origen              VARCHAR(50) DEFAULT 'presencial',
        -- presencial|whatsapp|referido|web|qr|campana

    -- Segmentación
    tags                TEXT[] DEFAULT '{}',            -- ['vip','mayorista','recurrente']
    segmento            VARCHAR(50),                     -- calculado automáticamente
        -- 'champion'|'leal'|'potencial'|'riesgo'|'perdido' (RFM)
    valor_cliente       VARCHAR(20),                     -- 'alto'|'medio'|'bajo' (calculado)

    -- Estado relación
    estado_relacion     VARCHAR(30) DEFAULT 'activo',    -- activo|inactivo|vip|bloqueado
    ultimo_contacto     TIMESTAMPTZ,
    proximo_contacto    DATE,
    ejecutivo_id        BIGINT REFERENCES usuarios(id),  -- quién lo atiende

    -- Métricas RFM (recalculadas por job diario)
    rfm_recencia_dias   INT,                             -- días desde última compra
    rfm_frecuencia      INT,                             -- cantidad de compras
    rfm_valor_total     BIGINT,                          -- gasto total histórico CLP
    rfm_ticket_promedio BIGINT,                          -- ticket promedio CLP
    rfm_calculado_at    TIMESTAMPTZ,

    -- Satisfacción
    nps_score           SMALLINT CHECK (nps_score BETWEEN 0 AND 10),
    nps_comentario      TEXT,
    nps_fecha           TIMESTAMPTZ,

    metadata            JSONB DEFAULT '{}',
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_crm_tags ON crm_contactos USING GIN(tags);
CREATE INDEX idx_crm_segmento ON crm_contactos(segmento);
CREATE INDEX idx_crm_ejecutivo ON crm_contactos(ejecutivo_id);
```

### C.2 Tabla: `crm_notas` (schema tenant)

```sql
CREATE TABLE crm_notas (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id),
    usuario_id      BIGINT NOT NULL REFERENCES usuarios(id),
    tipo            VARCHAR(20) DEFAULT 'nota',
        -- nota|llamada|reunion|email|whatsapp|visita|alerta
    contenido       TEXT NOT NULL,
    contenido_cifrado BYTEA,       -- AES-256, NULL si no requiere cifrado
    cifrada         BOOLEAN DEFAULT FALSE,
    privada         BOOLEAN DEFAULT FALSE,  -- solo visible para quien la creó
    referencia_id   BIGINT,         -- venta_id, ot_id, etc. (FK flexible)
    referencia_tipo VARCHAR(30),    -- 'venta'|'ot'|'renta'|'expediente'
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_notas_cliente ON crm_notas(cliente_id);
CREATE INDEX idx_notas_fecha ON crm_notas(created_at DESC);
```

### C.3 Tabla: `crm_actividades` (schema tenant)

```sql
CREATE TABLE crm_actividades (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id),
    usuario_id      BIGINT NOT NULL REFERENCES usuarios(id),
    tipo            VARCHAR(30) NOT NULL,
        -- llamada_saliente|llamada_entrante|whatsapp|email|reunion|visita|demo|cotizacion
    descripcion     TEXT,
    resultado       TEXT,
    duracion_min    INT,
    fecha           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    proxima_accion  TEXT,
    fecha_proxima   DATE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_act_cliente ON crm_actividades(cliente_id);
CREATE INDEX idx_act_fecha ON crm_actividades(fecha DESC);
```

### C.4 Tabla: `crm_tareas` (schema tenant)

```sql
CREATE TABLE crm_tareas (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT REFERENCES clientes(id),
    asignado_a      BIGINT NOT NULL REFERENCES usuarios(id),
    creado_por      BIGINT NOT NULL REFERENCES usuarios(id),
    titulo          VARCHAR(255) NOT NULL,
    descripcion     TEXT,
    tipo            VARCHAR(30) DEFAULT 'seguimiento',
        -- seguimiento|llamar|cotizar|visita|renovacion|cobro|recordatorio
    prioridad       VARCHAR(10) DEFAULT 'media',     -- alta|media|baja
    estado          VARCHAR(20) DEFAULT 'pendiente', -- pendiente|en_proceso|completada|cancelada
    fecha_limite    DATE,
    completada_at   TIMESTAMPTZ,
    alerta_wa       BOOLEAN DEFAULT FALSE,  -- enviar WA al asignado cuando venza
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_tareas_asignado ON crm_tareas(asignado_a, estado);
CREATE INDEX idx_tareas_fecha ON crm_tareas(fecha_limite) WHERE estado = 'pendiente';
```

### C.5 Tabla: `crm_pipeline` (schema tenant) — Solo industrias C06

```sql
CREATE TABLE crm_pipeline (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id),
    titulo          VARCHAR(255) NOT NULL,    -- "Cotización ferretería XYZ"
    etapa           VARCHAR(30) DEFAULT 'prospecto',
        -- prospecto|contactado|calificado|propuesta|negociacion|ganado|perdido
    valor_estimado  BIGINT DEFAULT 0,         -- CLP
    probabilidad    SMALLINT DEFAULT 20,      -- %
    ejecutivo_id    BIGINT REFERENCES usuarios(id),
    fecha_cierre_est DATE,
    motivo_perdida  TEXT,
    notas           TEXT,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_pipeline_etapa ON crm_pipeline(etapa);
CREATE INDEX idx_pipeline_ejecutivo ON crm_pipeline(ejecutivo_id);
```

### C.6 Tabla: `crm_recursos` (schema tenant) — Solo industrias C11

> Vincula clientes a recursos físicos: vehículo, mascota, habitación, expediente.

```sql
CREATE TABLE crm_recursos (
    id              BIGSERIAL PRIMARY KEY,
    cliente_id      BIGINT NOT NULL REFERENCES clientes(id),
    tipo            VARCHAR(30) NOT NULL,
        -- vehiculo|mascota|expediente|propiedad|habitacion
    identificador   VARCHAR(100) NOT NULL,   -- patente, nombre mascota, N° expediente
    descripcion     TEXT,
    metadata        JSONB DEFAULT '{}',
        -- vehiculo: {marca, modelo, año, color}
        -- mascota: {especie, raza, edad, chip}
        -- expediente: {numero, materia, tribunal}
    activo          BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_recursos_cliente ON crm_recursos(cliente_id);
CREATE INDEX idx_recursos_tipo ON crm_recursos(tipo);
```

### C.7 Tabla: `crm_campanas_wa` (schema tenant) — Solo C15

```sql
CREATE TABLE crm_campanas_wa (
    id              BIGSERIAL PRIMARY KEY,
    nombre          VARCHAR(255) NOT NULL,
    segmento        VARCHAR(50),             -- segmento destino (o NULL = todos)
    tags_filtro     TEXT[],                  -- filtrar por tags
    mensaje_plantilla TEXT NOT NULL,         -- con placeholders {nombre}, {deuda}, {dias}
    estado          VARCHAR(20) DEFAULT 'borrador',  -- borrador|programada|enviando|completada
    fecha_envio     TIMESTAMPTZ,
    total_destino   INT DEFAULT 0,
    total_enviado   INT DEFAULT 0,
    total_error     INT DEFAULT 0,
    creado_por      BIGINT REFERENCES usuarios(id),
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

---

## PARTE D: API ENDPOINTS

### D.1 Ficha CRM y contacto

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/crm/clientes` | Lista clientes con filtros CRM (segmento, tag, ejecutivo) | admin |
| GET | `/api/crm/clientes/{id}` | Ficha completa: datos + notas + actividades + tareas + métricas | admin, cajero |
| POST | `/api/crm/clientes/{id}/contacto` | Crear o actualizar datos extendidos del contacto CRM | admin |
| PUT | `/api/crm/clientes/{id}/tags` | Agregar o quitar tags | admin |
| PUT | `/api/crm/clientes/{id}/ejecutivo` | Asignar ejecutivo | admin |

### D.2 Notas

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/crm/clientes/{id}/notas` | Listado de notas (cifradas solo si tiene permiso) | admin, operario |
| POST | `/api/crm/clientes/{id}/notas` | Crear nota (texto o cifrada) | admin, operario, cajero |
| DELETE | `/api/crm/notas/{nota_id}` | Eliminar nota propia | admin, creador |

### D.3 Actividades y tareas

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/crm/clientes/{id}/actividades` | Timeline de actividades | admin |
| POST | `/api/crm/clientes/{id}/actividades` | Registrar actividad | admin |
| GET | `/api/crm/tareas` | Mis tareas pendientes (del usuario autenticado) | admin |
| POST | `/api/crm/tareas` | Crear tarea (puede ser sin cliente) | admin |
| PUT | `/api/crm/tareas/{id}/completar` | Marcar como completada | admin |

### D.4 Pipeline (C06)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/crm/pipeline` | Kanban de oportunidades por etapa | admin |
| POST | `/api/crm/pipeline` | Nueva oportunidad | admin |
| PUT | `/api/crm/pipeline/{id}/etapa` | Avanzar o retroceder etapa | admin |
| GET | `/api/crm/pipeline/metricas` | Valor total por etapa, tasa de cierre | admin |

### D.5 Recursos (C11)

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/crm/clientes/{id}/recursos` | Lista recursos del cliente (vehículos, mascotas, etc.) | admin, operario |
| POST | `/api/crm/clientes/{id}/recursos` | Crear recurso | admin, operario |
| GET | `/api/crm/recursos/{id}/historial` | Historial de ventas/OT vinculadas a este recurso | admin |

### D.6 Métricas y campañas

| Método | Endpoint | Descripción | Rol |
|---|---|---|---|
| GET | `/api/crm/metricas/rfm` | Distribución de clientes por segmento RFM | admin |
| GET | `/api/crm/metricas/nps` | Score NPS promedio y distribución | admin |
| POST | `/api/crm/campanas` | Crear campaña WA | admin |
| POST | `/api/crm/campanas/{id}/enviar` | Despachar campaña a segmento | admin |

### D.7 Endpoints del bot WhatsApp

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/webhook/crm/cliente?tel={numero}` | Bot consulta datos del cliente por teléfono |
| POST | `/webhook/crm/nota` | Bot agrega nota a cliente desde conversación |
| GET | `/webhook/crm/tareas-hoy` | Bot consulta tareas pendientes del día (para el admin) |
| POST | `/webhook/crm/nps` | Bot registra respuesta de encuesta NPS |

---

## PARTE E: UI — VISTAS POR CONTEXTO

### E.1 Vista POS — Chip y ficha rápida

Al identificar cliente en el POS, el chip muestra indicadores CRM:

```
┌────────────────────────────────────────────────────┐
│  👤 María González   #31   ⭐ VIP                  │
│  Deuda: $23.450   Compras: 47   Último: hace 3 días │
│  [📝 Nota rápida]  [📋 Ver ficha]                  │
└────────────────────────────────────────────────────┘
```

**Nota rápida desde el POS** (un campo, sin abandonar la caja):
```
┌──────────────────────────────────────────────────┐
│  📝 NOTA — María González                    [✕] │
│  ┌──────────────────────────────────────────┐    │
│  │ "Prefiere pago en efectivo, avisar      │    │
│  │  cuando llegue la Leche Colún..."        │    │
│  └──────────────────────────────────────────┘    │
│  Tipo: ● Nota  ○ Alerta  ○ Tarea             [💾] │
└──────────────────────────────────────────────────┘
```

**Indicadores visuales en el POS** (según herramientas activas):

| Herramienta | Qué muestra en el chip |
|---|---|
| C03 Deudas | Monto deuda en rojo si > 0 |
| C09 Segmento | Badge: ⭐ VIP · ⚡ Nuevo · ⚠️ En riesgo |
| C13 Membresías | Badge plan + sesiones restantes |
| C11 Recurso | Nombre del recurso (mascota, vehículo) activo |
| C08 Tareas | 🔔 si hay tarea vencida para este cliente |

---

### E.2 Vista Admin — Ficha CRM completa

```
┌──────────────────────────────────────────────────────────────┐
│  👤 María González                          [Editar] [WA ➚]  │
│  RUT: 15.432.987-6   #31   ⭐ VIP   Ejecutivo: Juan Pérez    │
├────────────────────┬─────────────────────────────────────────┤
│  MÉTRICAS          │  RESUMEN                                 │
│  Compras: 47       │  Primer contacto: 12/01/2024             │
│  Total gastado:    │  Último contacto: 11/03/2026             │
│  $2.345.670        │  Próxima acción: 18/03/2026 (llamar)     │
│  Ticket prom:      │  Frecuencia: cada 8 días                 │
│  $49.909           │  Segmento: 🏆 Champion                    │
│  NPS: 9/10         │  Tags: [vip] [mayorista] [credito]       │
├────────────────────┴─────────────────────────────────────────┤
│  TABS: [📋 Historial] [📝 Notas] [✅ Tareas] [📊 Pipeline]   │
│         [🚗 Recursos] [📅 Actividades] [📱 Campañas]          │
├──────────────────────────────────────────────────────────────┤
│  [TAB ACTIVO: Historial de compras]                          │
│                                                              │
│  14/03/2026 · Venta #1042 · $45.670    [Ver detalles →]     │
│  Cable THHN 47.5m + Servicio corte                           │
│                                                              │
│  07/03/2026 · Venta #1038 · $23.400    [Ver detalles →]     │
│  Tornillos x200 + Pintura 2L                                 │
│                                                              │
│  28/02/2026 · Venta #1021 · $89.500    [Ver detalles →]     │
│  Cerradura Schlage + Cable THHN 100m                         │
└──────────────────────────────────────────────────────────────┘
```

**Tab Notas** (incluye notas cifradas si el rol lo permite):
```
┌──────────────────────────────────────────────────────────────┐
│  [+ Nueva nota]  Filtrar: [Todas] [Notas] [Llamadas] [WA]    │
├──────────────────────────────────────────────────────────────┤
│  📝 11/03/2026 · Juan Pérez                                   │
│  "Preguntó por descuento en compras > $500k mensual.         │
│   Revisar condiciones y llamar antes del 18/03."             │
│                                                              │
│  📞 07/03/2026 · Juan Pérez · Duración: 12 min               │
│  "Confirmó pedido de tornillería. Quiere factura mensual     │
│   consolidada. Agendar reunión para firmar contrato."        │
│                                                              │
│  💬 WA · 01/03/2026 · Sistema                                │
│  "Recordatorio deuda $23.450 enviado. Respondió 'esta semana'"|
└──────────────────────────────────────────────────────────────┘
```

**Tab Tareas:**
```
┌──────────────────────────────────────────────────────────────┐
│  [+ Nueva tarea]                                             │
├──────────────────────────────────────────────────────────────┤
│  🔴 VENCIDA — 15/03/2026                                      │
│  Llamar para revisar descuento > $500k                       │
│  Asignado: Juan Pérez   Tipo: Llamar                        │
│  [✅ Completar]  [📅 Reprogramar]                             │
│                                                              │
│  🟡 18/03/2026                                                │
│  Enviar cotización tornillería 2026                          │
│  Asignado: Juan Pérez   Tipo: Cotizar                       │
│  [✅ Completar]  [📅 Reprogramar]                             │
└──────────────────────────────────────────────────────────────┘
```

---

### E.3 Vista Admin — Dashboard CRM

```
┌──────────────────────────────────────────────────────────────┐
│  📊 CRM — SEGUIMIENTO DE CLIENTES          [Marzo 2026 ▼]    │
├──────────────────────────────────────────────────────────────┤
│  ┌──────────┬──────────┬──────────┬────────────────────────┐ │
│  │ Clientes │ Activos  │ Tareas   │ NPS promedio           │ │
│  │ 347      │ 89 (30d) │ 12 hoy   │ 8.4 / 10               │ │
│  └──────────┴──────────┴──────────┴────────────────────────┘ │
│                                                              │
│  SEGMENTOS RFM                                               │
│  🏆 Champions (46): alta frecuencia + alto valor             │
│  💎 Leales (89): frecuencia alta, valor medio                │
│  🌱 Potenciales (72): valor alto, baja frecuencia            │
│  ⚠️ En riesgo (38): no compran hace 30+ días                 │
│  😴 Perdidos (102): no compran hace 90+ días                 │
│                                                              │
│  MIS TAREAS HOY (Juan Pérez)                                 │
│  🔴 Llamar a Constructora Andes — vencida ayer               │
│  🟡 Enviar cotización a Ferretería Sur — hoy                  │
│  🟢 Revisar propuesta Empresa ABC — mañana                    │
│                                                              │
│  CLIENTES EN RIESGO (sin compra 30+ días)                    │
│  Pedro Soto #31 · $234.500 historial · último 42 días        │
│  Constructora Pérez · $1.2M historial · último 38 días       │
│  [📱 Enviar WA a todos]  [Ver todos →]                        │
└──────────────────────────────────────────────────────────────┘
```

---

### E.4 Vista Admin — Pipeline Kanban (C06)

```
┌──────────────────────────────────────────────────────────────┐
│  📈 PIPELINE COMERCIAL              [+ Nueva oportunidad]    │
├────────────────┬──────────────┬──────────────┬──────────────┤
│ PROSPECTO (4)  │ CONTACTADO(3)│ PROPUESTA (2)│ NEGOC. (1)   │
│ $345k          │ $890k        │ $1.23M       │ $500k        │
├────────────────┼──────────────┼──────────────┼──────────────┤
│ Constr. Norte  │ Empresa ABC  │ Ferret. Sur  │ Corp. XYZ    │
│ $120k · 30%    │ $340k · 60%  │ $780k · 75%  │ $500k · 85%  │
│ Juan P. · 25/3 │ Ana L. · 20/3│ Juan P. · 15/3│ Ana L.·18/3 │
│ [→][Notas]     │ [→][Notas]   │ [→][Notas]   │ [→][Notas]   │
│                │              │              │              │
│ Taller ABC     │ Electr. Del  │ Fabric. Paz  │              │
│ $85k · 20%     │ $280k · 50%  │ $450k · 70%  │              │
│ María S.       │ Juan P.      │ María S.     │              │
└────────────────┴──────────────┴──────────────┴──────────────┘
```

---

### E.5 Vista Admin — Lista de clientes con filtros CRM

```
┌──────────────────────────────────────────────────────────────┐
│  👥 CLIENTES CRM                   [🔍 Buscar]  [+ Nuevo]    │
│  Filtros: [Todos] [Champions] [En riesgo] [Con tareas]       │
│           [Tag: vip ×] [Ejecutivo: Juan ×] [Limpiar]         │
├────────────────────────────────────────────────────────────────┤
│ Cliente             Segmento    Última compra  Deuda   Tareas │
│ ─────────────────────────────────────────────────────────── │
│ María González #31  🏆 Champion  hace 3 días   $0       0    │
│   [Ver ficha] [📱 WA] [✅ Tarea]                               │
│ Constructora Andes  💎 Leal      hace 12 días  $0       1 🔴  │
│   [Ver ficha] [📱 WA] [✅ Tarea]                               │
│ Pedro Soto #42      ⚠️ En riesgo  hace 42 días  $23.450  0    │
│   [Ver ficha] [📱 WA] [✅ Tarea]                               │
│ Ferretería Sur      😴 Perdido   hace 95 días  $0       0    │
│   [Ver ficha] [📱 WA] [✅ Tarea]                               │
└────────────────────────────────────────────────────────────────┘
```

---

### E.6 Vista Admin — Vista CRM específica por industria

#### MÉDICO / DENTISTA — Ficha paciente

```
┌──────────────────────────────────────────────────────────────┐
│  🏥 Ana López — Paciente               #89   Dra. Pérez      │
├──────────────────────────────────────────────────────────────┤
│  RUT: 14.567.890-1   Tel: +56 9 8765 4321   Fecha nac: 15/05/1985 │
│  Próxima cita: 22/03/2026 10:00 — Limpieza dental            │
├──────────────────────────────────────────────────────────────┤
│  TABS: [📋 Prestaciones] [🔒 Historia clínica] [📅 Citas]     │
│         [📝 Notas] [✅ Recordatorios]                          │
├──────────────────────────────────────────────────────────────┤
│  [TAB: Historia clínica 🔒 — solo Dra. Pérez]                │
│                                                              │
│  14/03/2026 — Dra. Pérez                                     │
│  🔒 "Alergia a la amoxicilina confirmada. Usar azitromicina   │
│  en tratamientos futuros. Caries en pieza 16 requiere       │
│  obturación en próxima consulta."                            │
│                                                              │
│  [+ Agregar nota clínica]                                    │
└──────────────────────────────────────────────────────────────┘
```

#### TALLER MECÁNICO — Ficha vehículo

```
┌──────────────────────────────────────────────────────────────┐
│  🔧 Pedro Soto — Cliente   Toyota Corolla ABCD12 2019        │
├──────────────────────────────────────────────────────────────┤
│  Tel: +56 9 1234 5678                                        │
│  Recurso: 🚗 Toyota Corolla 2019 · Plata · ABCD12            │
│  Km último servicio: 87.500                                  │
├──────────────────────────────────────────────────────────────┤
│  HISTORIAL DEL VEHÍCULO                                      │
│  14/03/2026 · OT-045 · Pastillas freno + diagnóstico        │
│  $73.000 · Completada                                        │
│                                                              │
│  12/01/2026 · OT-038 · Aceite 5W30 + filtro                 │
│  $28.500 · Completada                                        │
│                                                              │
│  03/10/2025 · OT-019 · Revisión pre-viaje                   │
│  $15.000 · Completada                                        │
│                                                              │
│  📅 Próximo servicio estimado: ~87.500 + 5.000 km           │
│  [📱 Avisar al cliente]  [+ Nueva OT]                        │
└──────────────────────────────────────────────────────────────┘
```

#### VETERINARIA — Ficha mascota

```
┌──────────────────────────────────────────────────────────────┐
│  🐾 María López — Tutora   🐕 Max (Golden Retriever)         │
├──────────────────────────────────────────────────────────────┤
│  Max · 5 años · Macho castrado · Chip: 985112345678          │
│  Vacunas al día: Sí ✅   Próxima vacuna: Rabias 15/08/2026   │
├──────────────────────────────────────────────────────────────┤
│  HISTORIAL DE MAX                                            │
│  14/03/2026 · Consulta anual + vacuna múltiple · $45.000    │
│  12/01/2026 · Control post-castración · $22.000             │
│  03/10/2025 · Castración · $180.000                         │
│                                                              │
│  🔒 FICHA CLÍNICA (solo Dr. García)                          │
│  "Alergia al pollo detectada. Dieta libre de pollo."         │
│                                                              │
│  [📅 Agendar cita]  [📱 Recordatorio vacuna]  [+ Nota]       │
└──────────────────────────────────────────────────────────────┘
```

---

### E.7 Vista Bot WhatsApp — CRM desde el chat

#### Consulta del admin (comandos del ejecutivo)

```
Admin WA: "crm tareas hoy"

Bot: "📋 *Tus tareas de hoy — Juan Pérez*

     🔴 VENCIDA: Llamar a Constructora Andes
        Cliente desde hace 12 días sin compra
     
     🟡 HOY: Enviar cotización Ferretería Sur
        Oportunidad $780k en etapa Propuesta
     
     🟢 MAÑANA: Revisar propuesta Corp. XYZ
        Cierre estimado 18/03

     Responde el número para ver detalles:
     1️⃣ Constructora Andes
     2️⃣ Ferretería Sur
     3️⃣ Corp. XYZ"
```

```
Admin WA: "crm nota Pedro Soto — Llamó interesado en descuento mensual"

Bot: "✅ Nota guardada en ficha de Pedro Soto (#42)
     Tipo: nota  
     Fecha: 14/03/2026 15:23
     
     ¿Crear una tarea de seguimiento? (sí/no)"

Admin: "sí, llamar en 3 días"

Bot: "✅ Tarea creada: Llamar a Pedro Soto
     Fecha: 17/03/2026
     Te aviso ese día a las 9:00 AM"
```

#### Encuesta NPS automática al cliente (post-compra)

```
Bot → Cliente WA (3 días después de la venta):
"Hola María 👋 ¿Cómo estuvo tu última visita a Ferretería Don Pedro?
Del 0 al 10, ¿nos recomendarías a un amigo? (responde con el número)"

Cliente: "9"

Bot: "¡Gracias María! 🙌 ¿Quieres dejarnos un comentario?"

Cliente: "Muy buena atención de Juan, rápido y con buen precio"

Bot: "Anotado ✅ Gracias por tu tiempo"

→ Sistema: registra NPS=9, comentario en crm_contactos, recalcula segmento
```

#### Campaña dirigida desde admin

```
Admin WA: "crm campaña"

Bot: "📢 *Nueva campaña WhatsApp*

     ¿A qué segmento?
     1️⃣ Todos los clientes (347)
     2️⃣ En riesgo — sin compra 30+ días (38)
     3️⃣ Champions — top clientes (46)
     4️⃣ Con deuda pendiente (12)
     5️⃣ Por tag: escribe el tag"

Admin: "2"

Bot: "Segmento: *En riesgo* (38 clientes)
     Escribe el mensaje. Puedes usar:
     {nombre} → nombre del cliente
     {dias} → días sin compra
     {deuda} → deuda pendiente (si aplica)"

Admin: "Hola {nombre} 👋 Hace {dias} días que no te vemos por aquí.
Tenemos novedades en stock que te pueden interesar.
¿Cuándo nos visitas?"

Bot: "Vista previa:
     *Hola Pedro Soto 👋 Hace 42 días que no te vemos por aquí...*
     
     ¿Enviar a los 38 clientes del segmento? (sí/no)"

Admin: "sí"

Bot: "✅ Campaña programada para envío inmediato a 38 clientes.
     Te aviso cuando termine."
```

---

## PARTE F: JOBS AUTOMÁTICOS

| Job | Frecuencia | Acción |
|---|---|---|
| `RecalcularRFM` | Diario 02:00 | Recalcula `rfm_recencia_dias`, `rfm_frecuencia`, `rfm_valor_total` y actualiza `segmento` en `crm_contactos` para todos los clientes |
| `AlertaTareasVencidas` | Diario 09:00 | Envía WA al ejecutivo por cada tarea vencida con `alerta_wa=true` |
| `DetectarClientesRiesgo` | Semanal Lun 08:00 | Mueve a segmento `en_riesgo` clientes sin compra en 30 días. Notifica al admin. |
| `NPSPostCompra` | Cada 3h | Busca ventas confirmadas hace 3 días sin NPS → envía encuesta WA si cliente tiene teléfono |
| `ResumenCRMSemanal` | Viernes 17:00 | Envía WA al admin: nuevos clientes, tareas completadas, NPS promedio, clientes en riesgo |

---

## PARTE G: MODELO DE DATOS — EXTENSIÓN CLIENTES

Se agrega columna `crm_activo` a la tabla `clientes` existente para indicar si el cliente
tiene ficha CRM extendida (evita joins innecesarios para clientes simples):

```sql
ALTER TABLE clientes ADD COLUMN crm_activo BOOLEAN DEFAULT FALSE;
ALTER TABLE clientes ADD COLUMN codigo_rapido INTEGER UNIQUE;
```

El `crm_contacto` se crea automáticamente cuando:
- El admin abre la ficha de un cliente desde el CRM
- Una venta supera un umbral configurable (ej: segunda compra)
- El bot identifica al cliente y el admin lo convierte

---

## PARTE H: IMPLEMENTACIÓN LARAVEL

### H.1 Comandos artisan

```bash
# Migraciones
php artisan make:migration create_crm_contactos_table --path=database/migrations/tenant
php artisan make:migration create_crm_notas_table --path=database/migrations/tenant
php artisan make:migration create_crm_actividades_table --path=database/migrations/tenant
php artisan make:migration create_crm_tareas_table --path=database/migrations/tenant
php artisan make:migration create_crm_pipeline_table --path=database/migrations/tenant
php artisan make:migration create_crm_recursos_table --path=database/migrations/tenant
php artisan make:migration create_crm_campanas_wa_table --path=database/migrations/tenant

# Modelos
php artisan make:model Tenant/CrmContacto
php artisan make:model Tenant/CrmNota
php artisan make:model Tenant/CrmActividad
php artisan make:model Tenant/CrmTarea
php artisan make:model Tenant/CrmPipeline
php artisan make:model Tenant/CrmRecurso
php artisan make:model Tenant/CrmCampanaWa

# Controllers
php artisan make:controller Api/Tenant/CrmController --api
php artisan make:controller Api/Tenant/CrmNotaController --api
php artisan make:controller Api/Tenant/CrmTareaController --api
php artisan make:controller Api/Tenant/CrmPipelineController --api
php artisan make:controller Api/Tenant/CrmRecursoController --api
php artisan make:controller Api/Tenant/CrmCampanaController --api

# Services
php artisan make:service CrmService        # lógica de segmentación RFM
php artisan make:service NpsService        # encuestas y scoring
php artisan make:service CampanaWaService  # envío masivo por segmento

# Jobs
php artisan make:job RecalcularRFM
php artisan make:job AlertaTareasVencidas
php artisan make:job DetectarClientesRiesgo
php artisan make:job NPSPostCompra
php artisan make:job ResumenCRMSemanal
```

### H.2 RFM Service

```php
// app/Services/CrmService.php
public function recalcularRFM(CrmContacto $contacto): void
{
    $cliente = $contacto->cliente;

    // Recencia: días desde última compra
    $ultimaVenta = Venta::where('cliente_id', $cliente->id)
        ->where('estado', 'pagada')
        ->latest('pagado_at')
        ->first();
    $recencia = $ultimaVenta
        ? now()->diffInDays($ultimaVenta->pagado_at)
        : 9999;

    // Frecuencia: compras en últimos 365 días
    $frecuencia = Venta::where('cliente_id', $cliente->id)
        ->where('estado', 'pagada')
        ->where('pagado_at', '>=', now()->subYear())
        ->count();

    // Valor: total gastado histórico
    $valorTotal = (int) Venta::where('cliente_id', $cliente->id)
        ->where('estado', 'pagada')
        ->sum('total');

    $ticketPromedio = $frecuencia > 0
        ? (int) ($valorTotal / $frecuencia)
        : 0;

    // Clasificar segmento
    $segmento = $this->clasificarSegmento($recencia, $frecuencia, $valorTotal);

    $contacto->update([
        'rfm_recencia_dias'   => $recencia,
        'rfm_frecuencia'      => $frecuencia,
        'rfm_valor_total'     => $valorTotal,
        'rfm_ticket_promedio' => $ticketPromedio,
        'segmento'            => $segmento,
        'rfm_calculado_at'    => now(),
    ]);
}

private function clasificarSegmento(int $recencia, int $frecuencia, int $valor): string
{
    if ($recencia <= 30 && $frecuencia >= 5 && $valor >= 200000) return 'champion';
    if ($recencia <= 60 && $frecuencia >= 3)                     return 'leal';
    if ($recencia <= 90 && $valor >= 100000)                     return 'potencial';
    if ($recencia > 30 && $recencia <= 90 && $frecuencia >= 2)   return 'riesgo';
    if ($recencia > 90)                                           return 'perdido';
    return 'nuevo';
}
```

### H.3 Notas cifradas

```php
// app/Models/Tenant/CrmNota.php
protected function contenidoCifrado(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $value
            ? Crypt::decryptString($value)
            : null,
        set: fn ($value) => $value
            ? Crypt::encryptString($value)
            : null,
    );
}

// Al crear nota cifrada:
CrmNota::create([
    'cliente_id'         => $clienteId,
    'usuario_id'         => auth()->id(),
    'tipo'               => 'nota',
    'cifrada'            => true,
    'contenido_cifrado'  => $request->contenido, // se cifra automáticamente
]);

// Al leer: solo si el rol tiene permiso
public function index(Cliente $cliente): JsonResponse
{
    $query = CrmNota::where('cliente_id', $cliente->id);

    if (!Gate::allows('ver-notas-cifradas')) {
        $query->where('cifrada', false);
    }

    return response()->json($query->latest()->get());
}
```

### H.4 Rutas CRM

```php
// routes/tenant.php
Route::prefix('crm')->middleware(['auth:sanctum', 'tenancy.initialize'])->group(function () {

    // Dashboard y métricas
    Route::get('/dashboard', [CrmController::class, 'dashboard']);
    Route::get('/metricas/rfm', [CrmController::class, 'rfm']);
    Route::get('/metricas/nps', [CrmController::class, 'nps']);

    // Clientes CRM
    Route::get('/clientes', [CrmController::class, 'index']);
    Route::get('/clientes/{cliente}', [CrmController::class, 'show']);
    Route::post('/clientes/{cliente}/contacto', [CrmController::class, 'upsertContacto']);
    Route::put('/clientes/{cliente}/tags', [CrmController::class, 'actualizarTags']);

    // Notas
    Route::get('/clientes/{cliente}/notas', [CrmNotaController::class, 'index']);
    Route::post('/clientes/{cliente}/notas', [CrmNotaController::class, 'store']);
    Route::delete('/notas/{nota}', [CrmNotaController::class, 'destroy']);

    // Actividades
    Route::get('/clientes/{cliente}/actividades', [CrmActividadController::class, 'index']);
    Route::post('/clientes/{cliente}/actividades', [CrmActividadController::class, 'store']);

    // Tareas
    Route::get('/tareas', [CrmTareaController::class, 'mis_tareas']);
    Route::post('/tareas', [CrmTareaController::class, 'store']);
    Route::put('/tareas/{tarea}/completar', [CrmTareaController::class, 'completar']);

    // Pipeline (C06 — solo si activo)
    Route::middleware('crm.feature:C06')->group(function () {
        Route::get('/pipeline', [CrmPipelineController::class, 'kanban']);
        Route::post('/pipeline', [CrmPipelineController::class, 'store']);
        Route::put('/pipeline/{oport}/etapa', [CrmPipelineController::class, 'avanzarEtapa']);
    });

    // Recursos (C11 — solo si activo)
    Route::middleware('crm.feature:C11')->group(function () {
        Route::get('/clientes/{cliente}/recursos', [CrmRecursoController::class, 'index']);
        Route::post('/clientes/{cliente}/recursos', [CrmRecursoController::class, 'store']);
        Route::get('/recursos/{recurso}/historial', [CrmRecursoController::class, 'historial']);
    });

    // Campañas WA (C15 — solo si activo)
    Route::middleware('crm.feature:C15')->group(function () {
        Route::get('/campanas', [CrmCampanaController::class, 'index']);
        Route::post('/campanas', [CrmCampanaController::class, 'store']);
        Route::post('/campanas/{campana}/enviar', [CrmCampanaController::class, 'enviar']);
    });
});
```

---

## PARTE I: ACTUALIZACIÓN DE MÓDULOS ATÓMICOS Y PRESETS

### Agregar M32 al catálogo de módulos

```
M32 — CRM Modular (pos + admin + bot)
Seguimiento de clientes con herramientas específicas por industria:
notas, actividades, tareas, pipeline, recursos, RFM, NPS, campañas WA.
Las herramientas activas (C01–C15) se configuran por rubro.
```

### Actualizar presets en `BENDERAND_CONFIG_INDUSTRIAS.md`

```
RETAIL / ABARROTES      + M32 (C01 C02 C03 C04 C09 C10 C12 C15)
MAYORISTA / FERRETERÍA  + M32 (C01 C02 C03 C04 C06 C07 C08 C09 C10 C12 C15)
MÉDICO / CLÍNICA        + M32 (C01 C02 C04 C05 C08 C09 C11 C12 C14)
ABOGADOS / LEGAL        + M32 (C01 C02 C04 C05 C06 C07 C08 C09 C11)
TALLER MECÁNICO         + M32 (C01 C02 C03 C04 C07 C08 C09 C11 C12)
VETERINARIA             + M32 (C01 C02 C04 C05 C08 C09 C11 C12 C14)
... (ver tabla completa Parte B.1)
```

---

## PARTE J: CHECKLIST DE VERIFICACIÓN

**Base de datos:**
- [ ] 7 migraciones tenant creadas y corriendo sin error
- [ ] Índices GIN en `tags` de `crm_contactos` funcionando
- [ ] Cifrado AES-256 en `crm_notas.contenido_cifrado` via `Crypt::encryptString()`
- [ ] `ALTER TABLE clientes ADD COLUMN crm_activo` sin romper H1

**API:**
- [ ] `GET /crm/clientes/{id}` devuelve ficha completa con notas, actividades, tareas y métricas RFM
- [ ] Notas cifradas solo visibles para roles con `ver-notas-cifradas` Gate
- [ ] `POST /crm/tareas` con `alerta_wa=true` → WA al ejecutivo en la fecha límite
- [ ] Endpoints C06 (pipeline) y C11 (recursos) retornan 404 si herramienta inactiva
- [ ] `POST /crm/campanas/{id}/enviar` despacha jobs en queue, no bloquea el request

**POS:**
- [ ] Chip de cliente en POS muestra segmento, deuda y membresía activa
- [ ] Nota rápida desde POS se guarda sin abandonar la pantalla de venta
- [ ] Badge de tarea vencida visible en chip del cliente

**Admin:**
- [ ] Dashboard CRM muestra métricas RFM y tareas del día
- [ ] Ficha cliente tiene todos los tabs activos según herramientas del rubro
- [ ] Pipeline kanban funciona con drag o botones de avance de etapa
- [ ] Exportar lista de clientes por segmento a CSV

**WhatsApp Bot:**
- [ ] Admin puede consultar tareas con "crm tareas hoy"
- [ ] Admin puede agregar nota con "crm nota {cliente} — {texto}"
- [ ] Encuesta NPS se envía 3 días post-compra si cliente tiene teléfono
- [ ] Respuesta NPS se registra en `crm_contactos.nps_score`
- [ ] Campaña dirigida por segmento funciona end-to-end

**Jobs:**
- [ ] `RecalcularRFM` actualiza todos los contactos diariamente sin timeout
- [ ] `AlertaTareasVencidas` envía WA solo si `alerta_wa=true`
- [ ] `NPSPostCompra` no envía duplicados (verificar que cliente no tenga NPS reciente)
- [ ] Segmentación RFM es coherente: cliente con 50 compras → `champion`

**Multi-tenant:**
- [ ] CRM de un tenant no accesible desde otro (aislamiento de schema)
- [ ] `crm.feature:C06` middleware retorna 403 si pipeline no activo en el rubro
- [ ] Cifrado de notas usa la key de la app, no del tenant (correcto)

---

*H-CRM — Módulo CRM Modular · BenderAnd ERP*
*M32 activo en todos los rubros, herramientas C01–C15 según preset de industria*
*Accesible desde POS (chip + nota rápida) · Admin (ficha completa) · Bot WA (comandos + NPS + campañas)*
