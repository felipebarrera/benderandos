# BENDERANDOS BOT — MILESTONE 2: CAPTACIÓN AUTOMÁTICA
**Antigravity · 20 Marzo 2026**

---

## CONTEXTO

El bot ya corre. WhatsApp webhook activo, Ollama procesando, BullMQ en cola,
handover Telegram funcionando, JWT Bridge con ERP Laravel operativo.
El ERP tiene `POST /webhook/whatsapp/onboarding` que crea tenants automáticamente.

El Milestone 2 agrega un módulo independiente `src/captacion/` que intercepta
mensajes de prospectos (números no asociados a ningún tenant) y los convierte
en tenants registrados sin intervención humana.

---

## BASE EXISTENTE

```
✅ WhatsApp webhook recibiendo mensajes
✅ Ollama (llama3.1:8b) procesando intenciones
✅ BullMQ + Redis procesando colas
✅ Handover Telegram al admin
✅ Bot retoma con /bot
✅ Prisma + PostgreSQL bot corriendo en :5434
✅ JWT Bridge con ERP (INTERNAL_API_SECRET)
✅ ERP: POST /webhook/whatsapp/onboarding → crea tenant + devuelve credenciales
✅ Telegram bot token real (webhook sin registrar aún)
```

---

## SEPARACIÓN CENTRAL — DOS FLUJOS EN UN NÚMERO

El webhook principal clasifica antes de encolar:

```javascript
// src/workers/message.worker.js — MODIFICAR
const tenant = await db.tenant.findFirst({
  where: { whatsapp_phone_id: phoneId }
});

if (tenant && await db.conversation.findFirst({ where: { from: userId, tenantId: tenant.id }})) {
  // cliente conocido → flujo de servicio existente
  await serviceQueue.add('message', payload);
} else {
  // número desconocido → prospecto
  await captacionQueue.add('prospecto', { canal: 'whatsapp', userId, texto, metadata });
}
```

Un prospecto que convierte → próximo mensaje ya va al flujo de servicio.

---

## ESTRUCTURA DEL MÓDULO

```
src/captacion/
  handler.js           ← orquestador: (canal, userId, texto, metadata) → respuesta
  perfilDetector.js    ← LLM clasifica A/B/C/D en ≤2 mensajes
  calculadora.js       ← determinista: profesión + horas → semana/mes/año en CLP
  onboarding.js        ← HTTP POST al ERP → tenant creado → devuelve URL + creds
  referidos.js         ← genera links únicos, valida ref_, acredita meses
  prompts.js           ← prompts por perfil (A/B/C/D)
  flujos/
    profesional.js     ← Perfil A: 4 fases, ~8 mensajes
    negocio.js         ← Perfil B: 4 fases, ~10 mensajes
    emprendedor.js     ← Perfil C: orientación → deriva a A o B
    referido.js        ← Perfil D: flujo corto, 45 días gratis

adapters/
  whatsapp.adapter.js  ← existente
  telegram.captacion.js ← NUEVO: mismo handler, distinto transport

workers/
  captacion.worker.js  ← NUEVO: BullMQ consumer para prospectos

api/
  captacion.js         ← NUEVO: /api/captacion/* para Super Admin
```

---

## MIGRACIONES PRISMA (bot DB — schema público)

```prisma
model Prospecto {
  id              Int       @id @default(autoincrement())
  canal           String    // 'whatsapp' | 'telegram'
  canalId         String    // phone o telegram_id
  nombre          String?
  perfil          String?   // 'profesional' | 'negocio' | 'emprendedor' | 'referido'
  rubroDetectado  String?
  estado          String    @default("nuevo")
  // nuevo | calculadora | demo | conversion | convertido | perdido
  refTenantSlug   String?   // slug del tenant que lo refirió
  datosJson       Json?     // respuestas del flujo
  mrrCalculado    Int?      // ingreso estimado en CLP
  createdAt       DateTime  @default(now())
  ultimoMensaje   DateTime?
  convertidoAt    DateTime?
  tenantSlug      String?   // si ya convirtió
  conversaciones  ConversacionCaptacion[]
}

model ConversacionCaptacion {
  id           Int       @id @default(autoincrement())
  prospectoId  Int
  prospecto    Prospecto @relation(fields: [prospectoId], references: [id])
  canal        String
  rol          String    // 'bot' | 'user'
  contenido    String
  intencion    String?
  timestamp    DateTime  @default(now())
}

model Referido {
  id             Int       @id @default(autoincrement())
  referidorSlug  String    // tenant que refirió
  prospectoId    Int
  estado         String    @default("pendiente")
  // pendiente | convertido | expirado
  mesesGanados   Int       @default(0)
  createdAt      DateTime  @default(now())
  convertidoAt   DateTime?
}

model TenantNivel {
  id              Int       @id @default(autoincrement())
  tenantSlug      String    @unique
  nivelActual     Int       @default(1)
  metaVentasMes   Int       // CLP, calculado por rubro al onboardear
  ventasMes1      Int       @default(0)
  ventasMes2      Int       @default(0)
  ventasMes3      Int       @default(0)
  mesesEnMeta     Int       @default(0)
  logrosJson      Json      @default("[]")
  updatedAt       DateTime  @updatedAt
}
```

---

## CALCULADORA DE INGRESOS (determinista — sin LLM)

```javascript
// src/captacion/calculadora.js
const TARIFAS = {
  psicologo:     { sesion: 35000, porHora: 1,   label: 'sesión' },
  medico:        { sesion: 45000, porHora: 1,   label: 'consulta' },
  dentista:      { sesion: 55000, porHora: 1,   label: 'atención' },
  abogado:       { sesion: 85000, porHora: 1,   label: 'hora profesional' },
  gasfiter:      { sesion: 35000, porHora: 0.5, label: 'trabajo' },
  mecanico:      { sesion: 45000, porHora: 0.3, label: 'servicio' },
  profesor:      { sesion: 20000, porHora: 1,   label: 'clase' },
  nutricionista: { sesion: 30000, porHora: 1,   label: 'consulta' },
  kinesiologo:   { sesion: 30000, porHora: 1,   label: 'sesión' },
  fonoaudiologo: { sesion: 30000, porHora: 1,   label: 'sesión' },
};

export function calcular(profesion, horasLibres) {
  const t = TARIFAS[profesion];
  if (!t) return null;
  const sesiones = Math.floor(horasLibres * t.porHora);
  const semana   = sesiones * t.sesion;
  return {
    sesiones,
    semana,
    mes:  semana * 4,
    anio: semana * 48,
    label: t.label,
  };
}
```

---

## FLUJO A — PROFESIONAL INDEPENDIENTE (~8 mensajes)

```
FASE 1 — ENGANCHE
  Bot: "Hola 👋 Una pregunta rápida — ¿a qué te dedicas?"
  Bot: "¿Trabajas de forma independiente o para alguien más ahora mismo?"

FASE 2 — CALCULADORA
  Bot detecta profesión de la respuesta libre (LLM extrae keyword)
  Bot: "¿Cuántas horas libres tienes esta semana fuera de ese trabajo?"
  Bot: "Con X horas, cobrando $Y por {label}:
        Esta semana:  $Z
        Este mes:     $W
        ¿Eso cambiaría algo en tu situación?"

FASE 3 — DEMO DEL MÓDULO (máx 4 líneas)
  Bot: "¿Cuál es tu mayor problema hoy?
        1️⃣ Conseguir clientes
        2️⃣ Cobrar y emitir boletas
        3️⃣ Organizar mi agenda
        4️⃣ Todo lo anterior"
  Bot responde con beneficio concreto según elección.
  Sin mencionar features — solo resultados.

FASE 4 — CONVERSIÓN
  Bot: "Para crear tu cuenta necesito 3 datos:
        nombre completo · RUT · especialidad"
  Recibe uno por uno → llama onboarding.js → tenant creado en ERP
  Bot: "¡Listo {nombre}! Tu cuenta está lista.
        Entra en: {url}
        Usuario: {email} · Clave temporal: {clave}
        ¿Conoces a otro profesional en la misma situación?
        Por cada uno que se registre, ganas 1 mes gratis."
```

---

## FLUJO B — DUEÑO DE NEGOCIO PEQUEÑO (~10 mensajes)

```
FASE 1 — IDENTIFICACIÓN
  Bot: "Hola 👋 ¿Tienes o estás pensando en poner un negocio?"
  Bot: "¿Cuántos años lleva funcionando?"
  Bot: "¿Cómo llevas el control de ventas y stock?
        1️⃣ Cuaderno/Excel  2️⃣ Algún programa  3️⃣ De memoria  4️⃣ Alguien lo hace"

FASE 2 — HOOK ECONÓMICO
  Si cuaderno/memoria:
  Bot: "Un negocio sin control digital pierde entre 15% y 25%
        de sus ventas potenciales.
        Con $3.000.000/mes eso son $450.000-$750.000 que se van.
        ¿Ese rango se parece a tu negocio?"

FASE 3 — DEMO DEL RUBRO
  Bot despliega 4-5 líneas con los módulos exactos del rubro.
  Sin jerga técnica. "Tus clientes piden por WhatsApp — el bot
  responde si tienes el producto, crea la venta solo."

FASE 4 — CONVERSIÓN
  Igual que Flujo A: nombre + RUT + nombre del negocio → onboarding
  Último mensaje: pregunta por referidos.
```

---

## FLUJO D — REFERIDO (flujo corto)

```
Link de entrada:
  WhatsApp: wa.me/{phone}?text=ref_{tenantSlug}
  Telegram: t.me/{botname}?start=ref_{tenantSlug}

Al detectar ref_:
  Bot: "Hola {nombre}! {nombreReferidor} te recomendó BenderAnd.
        Por venir referido/a tienes 45 días gratis (no 30).
        ¿Eres profesional independiente o tienes un negocio?"
  → Salta directamente a FASE 3 del flujo correspondiente.

Al convertir:
  referidos.estado = 'convertido'
  Llamada al ERP: extender trial del referidor +30 días
  Notificación WA/TG al referidor: "{nombre} que referiste se unió 🎉"
```

---

## ADAPTER TELEGRAM PARA CAPTACIÓN

```javascript
// src/adapters/telegram.captacion.js
// El handler.js es canal-agnóstico. Solo cambia cómo enviar/recibir.

import TelegramBot from 'node-telegram-bot-api';
import { handleCaptacion } from '../captacion/handler.js';

export function initTelegramCaptacion(token) {
  const bot = new TelegramBot(token, { polling: false });

  // webhook POST /telegram/captacion
  return async (req, res) => {
    const { message } = req.body;
    if (!message?.text) return res.sendStatus(200);

    const userId   = String(message.chat.id);
    const texto    = message.text;
    const metadata = { start: message.text.startsWith('/start') 
                        ? message.text.split(' ')[1] : null };

    const respuesta = await handleCaptacion('telegram', userId, texto, metadata);
    await bot.sendMessage(userId, respuesta);
    res.sendStatus(200);
  };
}
```

Distinguir admin vs prospecto en Telegram:
```javascript
// Si el chat_id está en la tabla de tenants como telegram_chat_id → admin → flujo existente
// Si no está → prospecto → captacion/handler.js
```

---

## ONBOARDING.JS — INTEGRACIÓN CON ERP

```javascript
// src/captacion/onboarding.js
import { config } from '../config/index.js';

export async function crearTenant({ nombre, rut, industria, whatsapp, canal }) {
  const res = await fetch(`${config.erpBaseUrl}/webhook/whatsapp/onboarding`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Bot-Token': config.internalSecret,
    },
    body: JSON.stringify({ nombre, rut, industria, whatsapp, canal }),
  });

  if (!res.ok) throw new Error(`ERP onboarding falló: ${res.status}`);
  // ERP devuelve: { tenant_slug, url, email, clave_temporal, modulos_activos }
  return res.json();
}
```

---

## PROMPT BASE DEL BOT CAPTADOR

```
Eres el asistente de BenderAnd, plataforma para pequeños negocios y
profesionales independientes.

OBJETIVO: Convertir al prospecto en un tenant registrado en el menor
número de mensajes posible.

REGLAS ABSOLUTAS:
- Nunca digas "app" o "software". Di "sistema" o "plataforma".
- Ancla siempre en dinero concreto, nunca en features.
- Cada mensaje máximo 4 líneas.
- Si duda → vuelve al número económico calculado.
- El cierre siempre es pedir nombre + RUT + especialidad/negocio.
- El último mensaje SIEMPRE pregunta por referidos.
- Nunca prometas lo que el sistema no tiene.
- Cada negocio es la primera sucursal de una cadena futura.

PERFIL: {perfil}
RUBRO: {rubro}
INGRESO CALCULADO: {mrrCalculado} CLP/mes
REFERIDOR: {referidorNombre | "ninguno"}
FASE ACTUAL: {fase}
HISTORIAL: {historial}
```

---

## METAS DE NIVEL POR RUBRO

```javascript
// src/captacion/onboarding.js — al crear TenantNivel
const METAS_NIVEL2 = {
  abarrotes:    3_000_000,
  ferreteria:   5_000_000,
  medico:       2_000_000,
  dentista:     2_500_000,
  gasfiter:     1_500_000,
  motel:        4_000_000,
  padel:        2_500_000,
  restaurante:  3_500_000,
  legal:        2_000_000,
  default:      2_000_000,
};
```

---

## API ENDPOINTS NUEVOS (bot Node.js)

```
GET  /api/captacion/pipeline       → kanban de prospectos por estado
GET  /api/captacion/metricas       → tasa conversión, tiempo promedio, por canal/rubro
GET  /api/captacion/referidos      → red activa de referidos
GET  /api/captacion/prospectos/:id → historial de conversación completo
POST /api/captacion/prospectos/:id/convertir → conversión manual si falla el auto
POST /telegram/captacion           → webhook Telegram para prospectos
```

Autenticación: `X-Bot-Token: {INTERNAL_API_SECRET}` (igual que endpoints existentes)

---

## VARIABLES DE ENTORNO NUEVAS

```env
# benderandbotagentic/.env
ERP_BASE_URL=http://host.docker.internal:8000
TELEGRAM_WEBHOOK_URL=https://{ngrok_o_dominio}/telegram/captacion
CAPTACION_THROTTLE_MAX=10          # mensajes por número por hora
CAPTACION_REACTIVACION_DIAS=7      # reactivar prospecto en demo sin convertir
```

---

## REGISTRO DE ESTADO POR PROSPECTO

```
nuevo         → llegó primer mensaje, perfil sin detectar
calculadora   → perfil detectado, calculadora mostrada
demo          → número mostrado, ahora viendo el módulo
conversion    → pidiendo los 3 datos de registro
convertido    → tenant creado, credenciales enviadas
perdido       → dijo que no, registrado con motivo
```

Transiciones guardadas en `Prospecto.estado` + timestamp en `ConversacionCaptacion`.
Reactivación automática: prospecto en estado `demo` por más de `CAPTACION_REACTIVACION_DIAS`
→ job diario envía mensaje de seguimiento y vuelve a `nuevo`.

---

## THROTTLE Y PROTECCIONES

```javascript
// Máximo CAPTACION_THROTTLE_MAX mensajes de captación por número por hora
// Si supera → respuesta genérica "Hablamos en un momento"
// Prospecto que dice variantes de "no me interesa" → estado = 'perdido', guardar motivo
// No reactivar prospectos marcados como perdidos en < 30 días
```

---

## MÉTRICAS DE ÉXITO

```
Semana 1 post-lanzamiento:
  Tasa conversión prospecto → cuenta: > 25%
  Tiempo promedio hasta conversión: < 8 mensajes
  Cero intervención manual de BenderAnd

Mes 1:
  10 tenants nuevos captados por el bot
  3 vía Telegram (valida canal)
  1 cadena de referidos activa (A → B → C)

Mes 3:
  40% de nuevos tenants vienen por referido
  Telegram > 30% del canal de captación
  2 tenants subieron de Nivel 1 a Nivel 2
```

---

## LO QUE NO ES ESTE MILESTONE

```
VPS con dominio real              → infraestructura, separado
Token permanente Meta producción  → ya resuelto en dev
SII / LibreDTE en ERP             → Hito 9 del ERP
RRHH / Liquidaciones              → Hito 13 del ERP
```

---

## CHECKPOINT DE VERIFICACIÓN

Antes de dar el milestone por completo, estos escenarios deben funcionar
de principio a fin sin intervención:

1. Psicóloga escribe al bot → detecta perfil A → ve calculadora → da sus datos → recibe URL del ERP
2. Dueño de ferretería escribe → perfil B → ve módulos de ferretería → da datos → recibe URL
3. Alguien llega con `ref_demo-ferreteria` → recibe 45 días → convierte → ferretería recibe notificación
4. Prospect en estado `demo` por 7 días → recibe mensaje de reactivación automático
5. Número que ya tiene tenant → va al flujo de servicio, no al de captación

---

*BenderAndos Bot · Milestone 2 Captación · Antigravity · 20 Marzo 2026*
