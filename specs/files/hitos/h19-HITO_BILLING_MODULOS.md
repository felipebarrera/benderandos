# BenderAnd ERP — Hito: Sistema de Módulos con Billing, Onboarding y Control de Acceso

**Fecha:** 16 de Marzo de 2026  
**Tipo:** Diseño + Implementación — sistema completo  
**Prioridad:** 🔴 CRÍTICO — sin esto el producto no tiene modelo de negocio funcional  
**Depende de:** H0 ✅ H1 ✅ H2 ✅ H5 (parcial) H7 (parcial)

---

## El Problema que Resuelve Este Hito

El sistema actualmente activa **todos los módulos para todos los tenants sin costo diferenciado**. Esto es incorrecto. Un pádel no necesita ni debe ver los módulos de médico o abogado. Un motel no necesita recetas ni delivery. Cada tenant debe:

1. **Seleccionar qué módulos quiere** durante el onboarding
2. **Ver claramente cuánto paga** por esa selección antes de confirmar
3. **Solo acceder a los módulos que pagó** — el sistema bloquea el resto
4. **Super Admin define los precios** de cada módulo
5. **Admin puede ajustar su selección** después, con precio recalculado y nuevo cobro

---

## Modelo de Precios — Definición

### Estructura del Plan

Cada tenant paga una **tarifa base + módulos adicionales**. El super admin define ambos.

```
PRECIO TOTAL MENSUAL = tarifa_base + Σ(precio de cada módulo activo seleccionado)
```

No hay planes fijos (Basic/Pro/Enterprise) — el precio es 100% configurable por módulo. El super admin puede crear presets de "paquetes" para simplificar la elección, pero el sistema subyacente es siempre por módulo.

### Tabla de Precios por Módulo (Configurable por Super Admin)

```
ID    MÓDULO                     PRECIO DEFAULT     NOTAS
─────────────────────────────────────────────────────────────────────────
M01   Venta simple               $0 (incluido)      Siempre activo, base
M02   Venta multi-operario       $9.990/mes         Por cada operario adicional
M03   Stock físico               $0 (incluido)      Base si industria lo necesita
M04   Stock fraccionado          $4.990/mes         Requiere M03
M05   Renta / Arriendo           $9.990/mes         
M06   Renta por hora             $9.990/mes         
M07   Servicios sin stock        $0 (incluido)      Base si industria lo necesita
M08   Agenda / Citas             $9.990/mes         
M09   Honorarios                 $4.990/mes         
M10   Notas cifradas             $4.990/mes         
M11   Fiado / Crédito cliente    $4.990/mes         
M12   Encargos / Reservas        $4.990/mes         
M13   Delivery / Envíos          $14.990/mes        
M14   Habitaciones / Recursos    $9.990/mes         
M15   Comandas / Cocina          $9.990/mes         
M16   Recetas / Ingredientes     $9.990/mes         
M17   Pedido remoto WhatsApp     $14.990/mes        
M18   Compras / Proveedores      $9.990/mes         
M19   Inventario avanzado        $9.990/mes         Requiere M18
M20   SII / Facturación DTE      $14.990/mes        
M21   RRHH / Asistencia          $9.990/mes         
M22   Liquidaciones              $9.990/mes         Requiere M21
M23   Reclutamiento              $9.990/mes         Requiere M21
M24   Marketing QR               $9.990/mes         
M25   Portal cliente web         $9.990/mes         
M26   Descuento por volumen      $4.990/mes         
M27   Multi-sucursal             $19.990/mes        
M28   Órdenes de trabajo         $9.990/mes         
M29   Historial por recurso      $4.990/mes         
M30   Membresías / Suscripciones $9.990/mes         
M31   Venta Software SaaS        $24.990/mes        Solo BenderAnd mismo
M32   CRM Modular                $9.990/mes         

TARIFA BASE (tenant existente, módulos M01+M03 o M01+M07)  $19.990/mes
─────────────────────────────────────────────────────────────────────────
```

> Todos los precios son editables por super admin en cualquier momento. Los cambios aplican en el siguiente ciclo de cobro del tenant afectado (no retroactivo).

---

## Flujo Completo: Onboarding con Selección de Módulos y Precio

### Fase 1 — Identificación y Rubro (ya existe en bot WA)

```
Bot → tenant nuevo:

"¡Hola! Soy el asistente de BenderAnd. Voy a configurar tu empresa.
¿Cuál es el nombre de tu negocio?"

[nombre] → [RUT empresa] → [ciudad]

"¿A qué rubro pertenece tu negocio?"
[lista de industrias]

→ Tenant selecciona: "Canchas deportivas"
```

### Fase 2 — Confirmación de Módulos del Preset + Precio

```
Bot: "Para *Canchas deportivas* sugiero estos módulos:

     ✅ INCLUIDOS EN TARIFA BASE ($19.990/mes)
     • Venta simple (POS)
     • Stock físico (accesorios, bebidas)

     📦 MÓDULOS RECOMENDADOS PARA TU RUBRO
     ┌─────────────────────────────────────┬──────────────┐
     │ Módulo                              │ Precio/mes   │
     ├─────────────────────────────────────┼──────────────┤
     │ 🎾 Renta por hora (canchas/salas)   │ + $9.990     │
     │ 📅 Agenda / Citas (reservas)        │ + $9.990     │
     │ 🏟️ Habitaciones / Recursos (mapa)   │ + $9.990     │
     │ 🎫 Membresías / Suscripciones       │ + $9.990     │
     │ 📱 Marketing QR                     │ + $9.990     │
     │ 📄 SII / Facturación electrónica    │ + $14.990    │
     │ 💬 Pedido remoto WhatsApp           │ + $14.990    │
     └─────────────────────────────────────┴──────────────┘

     *TODOS ACTIVOS:*  Tarifa base $19.990 + módulos $79.940 = *$99.930/mes*

     ¿Quieres activarlos todos, o prefieres elegir cuáles?"

[Opciones: 1. Activar todos  2. Elegir uno a uno  3. Ver más módulos]
```

### Fase 3 — Selección Uno a Uno (si elige opción 2)

```
Bot: "Te muestro cada módulo. Responde SÍ o NO.

     🎾 *Renta por hora* — gestiona canchas con timer,
        extensión, checkout. (+$9.990/mes)
        ¿Lo activas? (sí/no)"

Tenant: "sí"

Bot: "✅ Agregado.

     📅 *Agenda / Citas* — calendario de reservas, estados
        (confirmada, llegó, cancelada), recordatorios WA. (+$9.990/mes)
        ¿Lo activas? (sí/no)"

Tenant: "sí"

Bot: "✅ Agregado.

     🏟️ *Mapa visual de recursos* — ve tus canchas en tiempo
        real: libre / reservada / ocupada. (+$9.990/mes)
        ¿Lo activas? (sí/no)"

Tenant: "sí"

Bot: "✅ Agregado.

     🎫 *Membresías* — planes mensuales por socio,
        sesiones incluidas, renovación automática. (+$9.990/mes)
        ¿Lo activas? (sí/no)"

Tenant: "no, por ahora no"

Bot: "Entendido. Lo puedes activar en cualquier momento desde Config.

     📱 *Marketing QR* — crea QR con descuentos, mide escaneos,
        segmenta clientes. (+$9.990/mes)
        ¿Lo activas? (sí/no)"

Tenant: "no"

Bot: "📄 *SII / Facturación electrónica* — emite boletas
     y facturas automáticas con LibreDTE. (+$14.990/mes)
     ¿Lo activas? (sí/no)"

Tenant: "sí"

Bot: "💬 *Pedido remoto WhatsApp* — clientes reservan cancha
     directamente por WhatsApp. (+$14.990/mes)
     ¿Lo activas? (sí/no)"

Tenant: "sí"
```

### Fase 4 — Resumen y Precio Final (CRÍTICO)

```
Bot: "✅ *Resumen de tu configuración:*

     🏢 Canchas deportivas — Club Pádel Norte
     ─────────────────────────────────────────
     Tarifa base (POS + Stock)          $19.990
     ✅ Renta por hora                  + $9.990
     ✅ Agenda / Citas                  + $9.990
     ✅ Mapa de recursos                + $9.990
     ✅ SII / Facturación DTE           +$14.990
     ✅ Pedido remoto WhatsApp          +$14.990
     ─────────────────────────────────────────
     *TOTAL MENSUAL:                    $79.940*

     ⏱ Los primeros *30 días son gratis* (trial).
     Primer cobro: 16 de Abril de 2026.

     ¿Confirmas esta configuración? (sí/no)"

Tenant: "sí"

Bot: "🎉 ¡Listo! Tu cuenta está creada.

     🌐 Panel: https://club-padel-norte.benderand.cl
     👤 Usuario: [username creado]
     💰 Trial activo hasta: 16/04/2026

     Entra a tu panel y crea tus canchas.
     Cuando termine el trial te llegará el link de pago."
```

### Fase 5 — Pago y Activación Real

```
Sistema al día 30:
  → Envía link de pago por WhatsApp al admin del tenant
  → Link apunta a PagoController con WebPay / transferencia

Bot: "⏰ Tu trial de 30 días terminó.

     Para seguir usando BenderAnd, paga tu primera mensualidad:
     *$79.940/mes*

     👉 Pagar ahora: [link WebPay]
     📱 O transfiere a: [cuenta BenderAnd] ref: CLUB-PADEL-NORTE

     Tienes 3 días de gracia antes de que el acceso se restrinja."

Tenant paga → Sistema registra pago → Suscripción activa → Acceso total
Tenant NO paga → Day 33 → Acceso bloqueado (solo lectura de datos, no operación)
```

---

## Control de Acceso por Módulo — Sistema de Gates

### Middleware de Acceso (`CheckModuleAccess`)

Cada endpoint y vista está protegida por su módulo correspondiente. Si el tenant no tiene el módulo activo y pago al día → acceso denegado con mensaje específico.

```php
// app/Http/Middleware/CheckModuleAccess.php

class CheckModuleAccess
{
    // Mapa endpoint → módulo requerido
    const MODULE_GATES = [
        'rentas.*'          => 'M05',
        'rentas.hora.*'     => 'M06',
        'agenda.*'          => 'M08',
        'honorarios.*'      => 'M09',
        'notas.*'           => 'M10',
        'deudas.*'          => 'M11',
        'encargos.*'        => 'M12',
        'delivery.*'        => 'M13',
        'recursos.*'        => 'M14',
        'comandas.*'        => 'M15',
        'recetas.*'         => 'M16',
        'pedidos-wa.*'      => 'M17',
        'compras.*'         => 'M18',
        'inventario-adv.*'  => 'M19',
        'dte.*'             => 'M20',
        'rrhh.*'            => 'M21',
        'liquidaciones.*'   => 'M22',
        'reclutamiento.*'   => 'M23',
        'marketing-qr.*'    => 'M24',
        'portal-cliente.*'  => 'M25',
        'membresias.*'      => 'M30',
    ];

    public function handle(Request $request, Closure $next, string $modulo)
    {
        $config = RubrosConfig::current(); // config del tenant activo
        $suscripcion = Subscription::current();

        // 1. Verificar que el módulo está en los activos del tenant
        if (!in_array($modulo, $config->modulos_activos)) {
            return response()->json([
                'error'   => 'modulo_no_activo',
                'message' => 'Este módulo no está activo en tu plan.',
                'modulo'  => $modulo,
                'accion'  => 'activar_desde_config'  // UI muestra CTA para activarlo
            ], 403);
        }

        // 2. Verificar que la suscripción está al día (o en trial)
        if (!$suscripcion->puedeOperar()) {
            return response()->json([
                'error'   => 'suscripcion_vencida',
                'message' => 'Tu suscripción requiere pago para continuar.',
                'dias_gracia_restantes' => $suscripcion->diasGraciaRestantes(),
                'link_pago' => $suscripcion->linkPago()
            ], 402);
        }

        return $next($request);
    }
}
```

### Estados de Suscripción

```php
// Subscription::puedeOperar() retorna TRUE en estos estados:
//   'trial'          → dentro de los 30 días gratis
//   'activo'         → pago al día
//   'gracia'         → hasta 3 días después del vencimiento sin pago

// Retorna FALSE (acceso bloqueado) en:
//   'vencido'        → más de 3 días sin pago
//   'suspendido'     → super admin suspendió manualmente
//   'cancelado'      → tenant canceló el servicio

// Modo bloqueado: puede iniciar sesión y VER sus datos históricos
// (ventas, clientes, etc.) pero NO puede operar (crear ventas, etc.)
```

### Frontend: UI cuando módulo no está activo

```javascript
// Si el backend responde con error 'modulo_no_activo',
// el frontend muestra un banner en lugar del contenido:

function renderModuloLocked(modulo, nombre, precio) {
  return `
    <div class="modulo-locked">
      <div class="lock-icon">🔒</div>
      <div class="lock-title">${nombre} no está activo</div>
      <div class="lock-sub">Agrega este módulo a tu plan por +$${precio}/mes</div>
      <button onclick="abrirConfigModulos()">Activar módulo →</button>
    </div>
  `;
}

// Si es error 'suscripcion_vencida':
function renderSuscripcionVencida(diasGracia, linkPago) {
  return `
    <div class="suscripcion-vencida">
      <div class="warn-icon">⚠️</div>
      <div class="warn-title">Suscripción vencida</div>
      <div class="warn-sub">${diasGracia > 0
        ? `Tienes ${diasGracia} días de gracia para pagar.`
        : 'El acceso operativo está bloqueado. Tus datos están seguros.'
      }</div>
      <a href="${linkPago}" class="pagar-btn">Pagar ahora →</a>
    </div>
  `;
}
```

---

## Panel Super Admin — Control Total de Precios y Módulos

### Vista: `superadmin.html` → Tab "Planes & Módulos"

El super admin tiene control total sobre:
1. **Precio de cada módulo** (editable, con historial de cambios)
2. **Tarifa base** por tipo de industria (puede diferenciar: motel paga más que abarrotes)
3. **Ver exactamente cuánto paga cada tenant** y por qué módulos
4. **Forzar activación/desactivación** de módulos en cualquier tenant
5. **Dar descuentos** o períodos gratuitos a tenants específicos
6. **Ver el impacto de un cambio de precio** antes de aplicarlo (cuántos tenants afectados, variación de MRR)

#### Tabla de Módulos con Precios (Super Admin)

```
┌──────┬─────────────────────────────┬──────────────┬───────────┬────────────┐
│  ID  │ Módulo                      │ Precio/mes   │ Tenants   │ MRR aporte │
├──────┼─────────────────────────────┼──────────────┼───────────┼────────────┤
│ M08  │ Agenda / Citas              │ $9.990  [✏️] │ 34 activo │ $339.660   │
│ M20  │ SII / DTE                   │ $14.990 [✏️] │ 52 activo │ $779.480   │
│ M17  │ Pedido WA                   │ $14.990 [✏️] │ 28 activo │ $419.720   │
│ M21  │ RRHH                        │ $9.990  [✏️] │ 19 activo │ $189.810   │
│ M06  │ Renta por hora              │ $9.990  [✏️] │ 12 activo │ $119.880   │
│ ...  │ ...                         │ ...          │ ...       │ ...        │
├──────┴─────────────────────────────┴──────────────┴───────────┴────────────┤
│                                   MRR TOTAL MÓDULOS:         $2.341.890    │
│                                   TARIFA BASE (X tenants):  $1.419.290    │
│                                   MRR TOTAL:                $3.761.180    │
└──────────────────────────────────────────────────────────────────────────┘

[Simulador de cambio de precio] [Exportar CSV] [Historial de cambios]
```

#### Simulador de Impacto de Cambio de Precio (Super Admin)

Antes de guardar un cambio de precio, el super admin ve:

```
Estás cambiando M20 SII/DTE de $14.990 a $19.990

📊 IMPACTO DEL CAMBIO:
  Tenants afectados:     52
  MRR actual (M20):      $779.480
  MRR nuevo (M20):       $1.039.480
  Diferencia MRR:        + $260.000/mes
  
⚠️ Este cambio aplica en el próximo ciclo de cada tenant.
   Los 52 tenants serán notificados por WhatsApp.

[Ver lista de tenants afectados]  [Cancelar]  [Confirmar cambio]
```

---

## Panel Admin (Tenant) — Control de Módulos y Precio Propio

### Vista: `admin_dashboard_v2.html` → Config → Módulos y Precios

El admin del tenant puede:
1. **Ver qué módulos tiene activos** y cuánto paga por cada uno
2. **Activar módulos nuevos** (con vista previa del nuevo precio total)
3. **Desactivar módulos** que no usa (con aviso de que baja el precio desde el siguiente mes)
4. **Ver historial de su suscripción** y pagos

#### Vista: "Mi Plan" (Admin del Tenant)

```
┌─────────────────────────────────────────────────────────────────┐
│  MI PLAN — Club Pádel Norte                                     │
│  Estado: ✅ ACTIVO — Próximo cobro: 16/04/2026                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  MÓDULOS ACTIVOS                                         Precio │
│  ─────────────────────────────────────────────────────────────  │
│  ✅ Tarifa base (POS + Stock)                           $19.990  │
│  ✅ Renta por hora              [Desactivar]            + $9.990 │
│  ✅ Agenda / Citas              [Desactivar]            + $9.990 │
│  ✅ Mapa de recursos            [Desactivar]            + $9.990 │
│  ✅ SII / Facturación DTE       [Desactivar]           +$14.990  │
│  ✅ Pedido remoto WhatsApp      [Desactivar]           +$14.990  │
│  ─────────────────────────────────────────────────────────────  │
│  TOTAL MENSUAL:                                         $79.940  │
│                                                                 │
│  MÓDULOS DISPONIBLES (no activos)                               │
│  ─────────────────────────────────────────────────────────────  │
│  ❌ Membresías / Suscripciones   [Activar +$9.990]              │
│  ❌ Marketing QR                 [Activar +$9.990]              │
│  ❌ Multi-sucursal               [Activar +$19.990]             │
│  ❌ Portal cliente web           [Activar +$9.990]              │
│  ❌ RRHH / Asistencia            [Activar +$9.990]              │
│  + ver 12 módulos más...                                        │
└─────────────────────────────────────────────────────────────────┘
```

#### Activar un módulo nuevo desde el panel admin

```
Admin hace clic en "Activar +$9.990" para Membresías

→ Modal:

┌────────────────────────────────────────────────┐
│  Activar: Membresías / Suscripciones           │
│  ─────────────────────────────────────────────  │
│  Permite crear planes mensuales por socio,     │
│  gestionar sesiones disponibles y renovar      │
│  automáticamente con cobro por WA.             │
│                                                │
│  Precio: + $9.990/mes                          │
│                                                │
│  Tu nuevo total mensual: $89.930/mes           │
│  Aplica desde el próximo ciclo (16/04/2026)    │
│                                                │
│  [Cancelar]  [Activar módulo]                  │
└────────────────────────────────────────────────┘
```

---

## Schema de Base de Datos — Billing por Módulo

### Tabla Central: `plan_modulos` (schema public)

```sql
-- Define el precio de cada módulo — editable por super admin
CREATE TABLE plan_modulos (
    id              BIGSERIAL PRIMARY KEY,
    modulo_id       VARCHAR(10) NOT NULL UNIQUE,  -- 'M01', 'M08', etc.
    nombre          VARCHAR(100) NOT NULL,
    descripcion     TEXT,
    precio_mensual  INTEGER NOT NULL DEFAULT 0,    -- en CLP, sin decimales
    es_base         BOOLEAN DEFAULT FALSE,         -- TRUE = incluido en tarifa base
    requiere        VARCHAR(10)[],                 -- módulos que deben estar activos primero
    activo          BOOLEAN DEFAULT TRUE,          -- el super admin puede ocultar módulos
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Historial de cambios de precio (auditoría)
CREATE TABLE plan_modulos_historial (
    id              BIGSERIAL PRIMARY KEY,
    modulo_id       VARCHAR(10) REFERENCES plan_modulos(modulo_id),
    precio_anterior INTEGER,
    precio_nuevo    INTEGER,
    cambiado_por    BIGINT,       -- super_admin user_id
    aplica_desde    DATE,         -- próximo ciclo de cobro
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### Tabla: `subscriptions` (schema public — ajuste del existente)

```sql
-- Ampliar la tabla subscriptions existente con módulos
ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS
    modulos_activos     TEXT[] NOT NULL DEFAULT '{"M01"}',
    precio_calculado    INTEGER,         -- Σ(módulos) + tarifa_base al momento de la sub
    trial_termina       DATE,
    estado              VARCHAR(20) DEFAULT 'trial',
    -- 'trial' | 'activo' | 'gracia' | 'vencido' | 'suspendido' | 'cancelado'
    dias_gracia         INTEGER DEFAULT 3,
    descuento_pct       INTEGER DEFAULT 0,  -- 0-100, aplicado por super admin
    descuento_motivo    TEXT,
    proximo_cobro       DATE,
    link_pago           VARCHAR(500);
```

### Tabla Tenant: `modulos_activos` (schema tenant_{uuid})

```sql
-- Estado actual de módulos en el tenant — sincronizado desde subscriptions
CREATE TABLE modulos_activos (
    modulo_id       VARCHAR(10) PRIMARY KEY,
    activo          BOOLEAN DEFAULT FALSE,
    activado_en     TIMESTAMPTZ,
    activado_por    VARCHAR(100),  -- 'onboarding' | 'admin' | 'superadmin'
    precio_al_activar INTEGER      -- precio que tenía el módulo cuando se activó
);
```

---

## Endpoints API Nuevos

### Super Admin

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/central/plan/modulos` | Listado de módulos con precio y MRR por módulo |
| PUT | `/central/plan/modulos/{id}` | Actualizar precio de un módulo |
| GET | `/central/plan/modulos/{id}/impacto` | Simular impacto de cambio de precio |
| GET | `/central/tenants/{id}/modulos` | Ver módulos activos de un tenant específico |
| POST | `/central/tenants/{id}/modulos/forzar` | Activar/desactivar módulo en tenant (override) |
| POST | `/central/tenants/{id}/descuento` | Aplicar descuento % o meses gratis |
| GET | `/central/mrr/detalle` | MRR desglosado por módulo |

### Tenant Admin

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/api/config/mi-plan` | Plan actual: módulos activos + precio total |
| GET | `/api/config/modulos-disponibles` | Módulos no activos con precio |
| POST | `/api/config/modulos/{id}/activar` | Activar módulo (con preview de precio) |
| POST | `/api/config/modulos/{id}/desactivar` | Desactivar módulo |
| GET | `/api/config/mi-plan/preview` | Preview de precio si activa/desactiva módulo X |
| GET | `/api/suscripcion/estado` | Estado de suscripción + link de pago si vencida |

---

## Flujo de Activación de Módulo Post-Onboarding (Admin)

```
Admin ve módulo bloqueado (Membresías) en el sidebar
→ Click "🔒 Activar módulo"
→ GET /api/config/modulos-disponibles → devuelve Membresías: precio $9.990
→ Modal: descripción + precio + nuevo total
→ Admin confirma
→ POST /api/config/modulos/M30/activar
→ Backend:
   1. Verifica suscripción activa
   2. Agrega M30 a subscription.modulos_activos
   3. Recalcula precio (suma $9.990 al total)
   4. Agenda cobro del diferencial en el próximo ciclo
   5. Actualiza modulos_activos en schema tenant
   6. Retorna: { modulo: 'M30', precio_nuevo_total: 89930, mensaje: 'Módulo activo' }
→ Frontend: remueve 🔒 de Membresías, muestra en sidebar, actualiza precio visible
→ Bot WA al admin: "✅ Módulo Membresías activado. Tu nuevo total: $89.930/mes desde el 16/04."
```

---

## Flujo de Bloqueo por Falta de Pago

```
Día 30 (fin de trial):
→ Job CheckTrialsExpirando detecta tenant en trial
→ Envía WA con link de pago
→ Estado: 'gracia' (puede seguir operando 3 días)

Día 33 (fin de gracia):
→ Job ProcesarCobrosMensuales detecta pago no recibido
→ Estado: 'vencido'
→ Todos los endpoints de operación devuelven 402 con link de pago
→ El tenant puede LOGIN y VER datos pero NO puede:
   - Crear ventas
   - Registrar compras
   - Gestionar rentas activas (las existentes quedan como están)
   - Usar cualquier módulo activo

→ Bot WA diariamente por 7 días más: recordatorio de pago
→ Día 40: Bot avisa que si no paga en 48h los datos serán archivados

Si paga:
→ PagoController registra pago
→ Estado: 'activo'
→ Acceso restaurado inmediatamente
→ Bot WA: "✅ Pago recibido. Bienvenido de vuelta."
```

---

## UI Nueva a Crear (Vistas)

### 1. Onboarding Web (alternativa al bot WA)

Página web en `{tenant}.benderand.cl/onboarding` — para tenants que prefieren configurar desde el browser.

Secciones:
- Step 1: Nombre, RUT, ciudad
- Step 2: Selector de industria (cards visuales con logo por rubro)
- Step 3: Lista de módulos con toggle + precio dinámico en sidebar derecho
- Step 4: Resumen y precio total + input de usuario/contraseña
- Step 5: Confirmación + link de acceso al panel

El sidebar de precio se actualiza en tiempo real mientras el tenant togglea módulos:

```
RESUMEN DE TU PLAN
─────────────────────
✅ Tarifa base         $19.990
✅ Renta por hora      + $9.990
✅ Agenda              + $9.990
✅ Mapa de recursos    + $9.990
✅ SII / DTE          +$14.990
─────────────────────
TOTAL/MES:            $63.960
🎁 Trial 30 días gratis
Primer cobro: 16/04/2026

[Confirmar y crear cuenta]
```

### 2. Vista "Mi Plan" en Admin Dashboard

Agregar tab "Mi Plan" en `admin_dashboard_v2.html → Config`. Ver diseño en la sección "Panel Admin (Tenant)" de este documento.

### 3. Vista "Planes & Módulos" en Super Admin

Agregar tab "Módulos & Precios" en `superadmin.html`. Ver diseño en la sección "Panel Super Admin" de este documento.

### 4. Banner de Suscripción Vencida (global)

Componente persistente que aparece en TODO el admin si `suscripcion.estado !== 'activo' || 'trial'`:

```html
<!-- Aparece sobre el nav, no debajo (no ocupa espacio del contenido) -->
<div class="sub-banner vencida">
  ⚠️ Tu suscripción está vencida. 
  <strong>2 días de gracia restantes.</strong>
  <a href="/pagar">Pagar ahora →</a>
</div>
```

---

## Tareas de Implementación Ordenadas

| # | Tarea | Esfuerzo | Tipo |
|---|---|---|---|
| 1 | Migración `plan_modulos` (central) + seeder con 32 módulos y precios | 2h | Backend |
| 2 | Ajustar `subscriptions` con `modulos_activos[]` y `estado` extendido | 2h | Backend |
| 3 | Migración `modulos_activos` (tenant) | 1h | Backend |
| 4 | `CheckModuleAccess` middleware + registro en kernel | 3h | Backend |
| 5 | Anotar todas las rutas tenant con `middleware(['module:MXX'])` | 2h | Backend |
| 6 | Endpoints super admin (precios, impacto, override, MRR detalle) | 4h | Backend |
| 7 | Endpoints admin (mi-plan, activar, desactivar, preview precio) | 3h | Backend |
| 8 | `Subscription::puedeOperar()` + `diasGraciaRestantes()` | 1h | Backend |
| 9 | Job `CheckTrialsExpirando` → WA + cambio estado | 2h | Backend |
| 10 | Job `ProcesarCobrosMensuales` → cobrar + bloquear si no paga | 3h | Backend |
| 11 | Flujo bot WA: onboarding con selección de módulos + precio | 4h | Backend/WA |
| 12 | UI: Tab "Mi Plan" en admin_dashboard_v2.html | 3h | Frontend |
| 13 | UI: Tab "Módulos & Precios" en superadmin.html | 3h | Frontend |
| 14 | UI: Banner suscripción vencida (global) | 1h | Frontend |
| 15 | UI: Modal "Activar módulo" con preview de precio | 2h | Frontend |
| 16 | UI: Componente `modulo-locked` con CTA para activar | 1h | Frontend |
| 17 | UI: Página onboarding web (alternativa al bot) | 6h | Frontend |
| 18 | Sidebar admin: construir desde `modulos_activos` del tenant | 2h | Frontend |
| 19 | Test: activar módulo → aparece en sidebar → funciona | 2h | QA |
| 20 | Test: desactivar → sidebar lo oculta → endpoint devuelve 403 | 2h | QA |
| 21 | Test: trial vence → estado gracia → día 3 → bloqueo | 2h | QA |
| 22 | Test: pago → estado activo → acceso restaurado | 1h | QA |

**Esfuerzo total estimado:** ~7–9 días de desarrollo

---

## Presets por Industria — Módulos Incluidos y Precio Base Calculado

| Industria | Módulos del Preset | Precio/mes |
|---|---|---|
| Retail / Abarrotes | M01 M02 M03 M11 M17 M18 M20 M24 M25 | $99.890 |
| Ferretería / Mayorista | M01 M02 M03 M04 M11 M17 M18 M19 M20 M26 | $109.880 |
| Restaurante | M01 M02 M03 M15 M16 M17 M18 M20 M24 | $109.880 |
| Motel / Horas | M01 M03 M06 M14 M17 M20 | $69.940 |
| Canchas / Deportes | M01 M03 M06 M08 M14 M17 M20 M24 M30 | $109.880 |
| Médico / Clínica | M01 M07 M08 M09 M10 M20 M21 M22 M23 | $84.890 |
| Abogados | M01 M07 M08 M09 M10 M20 M21 | $64.910 |
| Taller Mecánico | M01 M03 M07 M18 M20 M21 M28 M29 | $79.920 |
| Farmacia | M01 M03 M04 M11 M18 M19 M20 | $69.930 |
| Salón de Belleza / Spa | M01 M07 M08 M17 M20 M24 M30 | $84.920 |

> Todos los precios son el resultado de la tarifa base + suma de módulos del preset a los precios default. El super admin puede modificarlos individualmente.

---

## Resumen del Principio de Diseño

```
SUPER ADMIN define:
  └─ Precio de cada módulo (editable, con historial)
  └─ Tarifa base general o por rubro
  └─ Presets de industria (qué módulos recomienda para cada rubro)
  └─ Descuentos individuales por tenant

ONBOARDING define:
  └─ Qué módulos quiere el tenant (preset + ajuste manual)
  └─ Precio total visible ANTES de confirmar
  └─ Trial 30 días → pago → suscripción activa

SUSCRIPCIÓN controla:
  └─ Qué módulos puede usar el tenant (los que pagó)
  └─ Si puede operar (trial / activo / gracia / bloqueado)
  └─ Recalculo automático si activa módulos nuevos

ADMIN TENANT puede:
  └─ Ver su plan y cuánto paga por cada módulo
  └─ Activar módulos nuevos (con preview de precio)
  └─ Desactivar módulos (efectivo desde el próximo ciclo)
  └─ Pagar desde el panel si está vencido

SISTEMA bloquea:
  └─ Endpoints de módulos no activos → 403
  └─ Toda operación si suscripción vencida → 402
  └─ Solo permite login + lectura de datos históricos en modo bloqueado
```

---

*BenderAnd ERP — Hito Billing por Módulo*  
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Sanctum · Redis*
