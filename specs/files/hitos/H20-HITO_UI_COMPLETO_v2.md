# BenderAnd ERP — Hito UI Completa (v2)

**Fecha de actualización:** 16 de Marzo de 2026  
**Basado en:** `analysis_report.md` (rev. 16/03), `ui_plan_completo.html`, `ui_modulos_completo.html`  
**Tipo:** Hito de UI — prototipos HTML estáticos + tareas de integración pendientes  
**Contexto antigravity:** Este hito documenta el estado real post-H1/H2/H17 y las tareas concretas a ejecutar.

---

## Estado del Proyecto al Iniciar Esta Conversación

```
✅ H0  — Infraestructura (Docker, PostgreSQL 16, Laravel 11 + stancl/tenancy v3 + Sanctum)
✅ H1  — POS Venta Minorista (Auth 401 resuelto · pos_v3.html conectado al backend real)
✅ H2  — Multi-Operario + Roles (Gates/Policies implementadas · pendiente QA multi-operario)
🟡 H3  — Rentas + Fraccionados (~60%): backend parcial, sin UI de panel visual, sin timer
🟡 H4  — WhatsApp Onboarding (~50%): services creados, notificación parcial, sin test e2e
🟡 H5  — Super Admin + Billing (~50%): superadmin.html con datos mock, sin test MRR
🟡 H6  — Portal Cliente (~40%): controller + rutas, sin HTML dedicado, Transbank sin SDK
🟡 H7  — Config Dinámica por Industria (~20%): solo documentado, rubros_config sin DB
❌ H8  — ERP ↔ WhatsApp Bot: sin backend
❌ H9  — SII / LibreDTE: sin backend
❌ H10 — Compras y Proveedores avanzado: solo compras básicas, sin OC/proveedores
❌ H11 — Delivery y Logística: sin modelos ni controllers
❌ H12 — Restaurante: Recetas: sin modelos ni controllers
❌ H13 — RRHH completo: sin modelos ni controllers
❌ H14 — Reclutamiento: sin modelos ni controllers
❌ H15 — Marketing QR: sin modelos ni controllers
❌ H16 — M31: Venta Software SaaS: sin modelos ni controllers
✅ H17 — Dashboard Ejecutivo + API Pública (PublicApiController + SaasDashboardController)
❌ H18 — Testing + Deploy: futuro

✅ UI_PLAN_COMPLETO  — 7 industrias × 5 tabs (abogado, pádel, motel, abarrotes, ferretería, médico, SaaS)
✅ UI_MODULOS_COMPLETO — 9 módulos × 4+ tabs (admin, POS avanzado, delivery, restaurante, RRHH, reclutamiento, QR, SII, WhatsApp bot)
```

---

## Lo Que Se Completó en Este Hito (UI)

Se produjeron dos archivos HTML que constituyen el **sistema visual completo de BenderAnd ERP**. Son la referencia definitiva de diseño para todos los hitos de backend pendientes (H3–H16).

### `ui_plan_completo.html` — Plan UI por Industria

7 industrias con accent color único, cada una con hasta 5 tabs:

| Industria | Accent | Tabs |
|---|---|---|
| ⚖️ Abogado / Legal | `#5b8dee` | POS Caja · Agenda · CRM/Expediente · Admin · Roles & Config |
| 🎾 Pádel / Deportes | `#00e5a0` | POS Caja · Canchas · Reservas/Historial · Admin · Roles & Config |
| 🏨 Motel / Hospedaje | `#ff6b35` | Panel Habitaciones · POS Caja · Folio/Historial · Admin · Config |
| 🛒 Abarrotes / Retail | `#f5c518` | POS Caja · Deudas/Fiados · Stock · Admin · Roles & Config |
| 🔧 Ferretería | `#c084fc` | POS Fraccionados · Stock Crítico · Órdenes de Compra · Admin · Config |
| 🏥 Médico / Clínica | `#3dd9eb` | POS Caja · Agenda · CRM Paciente · Admin · Roles & Config |
| 💻 SaaS / M31 | `#e040fb` | Pipeline Tenants · Ficha Tenant · MRR Dashboard · Roles & Config |

Cada tab incluye flujo completo: cómo opera cada rol (admin, operario/cajero, cliente) y qué ve la UI según `rubros_config`.

### `ui_modulos_completo.html` — Módulos Funcionales Completos

9 módulos con sus flujos operativos:

| Módulo | Accent | Hito Backend | Sub-vistas |
|---|---|---|---|
| 🏢 Admin Panel | `#e040fb` | H1–H7 | Dashboard · Ventas · Productos · Clientes · Compras · Config |
| 💳 POS Avanzado | `#c084fc` | H1·H2·H3 | POS Mobile · Fraccionados · Rentas · Portal Cliente |
| 🚚 Delivery | `#00c4ff` | H11 | Mapa pedidos · Repartidores · Zonas/tarifas · Tracking |
| 🍽️ Restaurante | `#ff6b35` | H12 | Recetas · Control insumos · Mermas · Menú digital |
| 👥 RRHH | `#3dd9eb` | H13 | Empleados · Turnos/Asistencia · Liquidaciones · Vacaciones |
| 💼 Reclutamiento | `#f5c518` | H14 | Kanban postulaciones · Evaluaciones · Ofertas · Onboarding |
| 📱 Marketing QR | `#00e5a0` | H15 | Campañas · Analytics · Segmentos · Automatización WA |
| 📄 SII / DTE | `#5b8dee` | H9 | Emisión DTE · Libro ventas · Libro compras · Config LibreDTE |
| 💬 WhatsApp Bot | `#25d366` | H8 | Conversaciones · Auto-respuestas · Pedidos bot · Config bot |

---

## Tareas Pendientes — Ordenadas por Prioridad

### 🔴 BLOQUE 1 — Conectar Admin Dashboard al Backend Real
**Esfuerzo estimado:** 2–3 días  
**Impacto:** Panel admin funcional con datos reales

H1 y H2 están completados en backend. `admin_dashboard_v2.html` todavía usa stubs JS. La conexión es directa — los endpoints ya existen.

**Tareas específicas:**

| # | Tarea | Endpoint | Archivo a editar |
|---|---|---|---|
| 1.1 | Reemplazar stub de ventas del día | `GET /api/ventas?fecha=hoy` | `admin_dashboard_v2.html` |
| 1.2 | Reemplazar stub de KPIs dashboard | `GET /api/dashboard` | `admin_dashboard_v2.html` |
| 1.3 | Reemplazar stub de productos | `GET /api/productos` | `admin_dashboard_v2.html` |
| 1.4 | Reemplazar stub de clientes + deudas | `GET /api/clientes` | `admin_dashboard_v2.html` |
| 1.5 | Reemplazar stub de compras básicas | `GET /api/compras` | `admin_dashboard_v2.html` |
| 1.6 | Conectar `superadmin.html` a endpoints centrales | `GET /central/tenants`, `GET /central/metrics` | `superadmin.html` |
| 1.7 | Integrar `benderand-debug.js` en admin y superadmin | — | Ambos HTML, antes de `</body>` |

---

### 🟡 BLOQUE 2 — Completar H3: Rentas + Fraccionados
**Esfuerzo estimado:** 2–3 días  
**Impacto:** Motel, pádel y equipos operativos  
**UI de referencia:** `ui_plan_completo.html` → secciones Pádel y Motel · `ui_modulos_completo.html` → tab POS Avanzado → Rentas

**Tareas específicas:**

| # | Tarea | Tipo | Notas |
|---|---|---|---|
| 2.1 | `VentaService`: rama fraccionados (precio por unidad mínima) | Backend | Pendiente en `VentaService.php` |
| 2.2 | Panel visual habitaciones/canchas con estados | UI | Ver `ui_plan_completo.html §Motel` y `§Pádel` como referencia exacta |
| 2.3 | Timer countdown por renta activa | UI (JS) | Componente `.sched`/`.slot` + interval JS |
| 2.4 | Acciones inline: Extender, Devolver, Limpiar | UI + Backend | `PUT /rentas/{id}/extender`, `PUT /rentas/{id}/devolver` |
| 2.5 | Integrar panel rentas en `admin_dashboard_v2.html` | UI | Nueva tab "Rentas" en sidebar admin |
| 2.6 | `compras_proveedores.html` → integrar en admin dashboard | UI | Reemplazar tab "Compras" básico con módulo completo |

**Componentes UI a reutilizar de `ui_plan_completo.html`:**
```html
<!-- Grid de habitaciones/canchas -->
<div class="rooms" id="motel-rooms"></div>
<!-- Slot de hora (pádel) -->
<div class="sched"><div class="slot libre|ocupado|sel">...</div></div>
<!-- Timer activo -->
<div class="timer-countdown" data-end="..."></div>
```

---

### 🟡 BLOQUE 3 — Completar H6: Portal Cliente Web
**Esfuerzo estimado:** 2 días  
**Impacto:** Self-service cliente en `{tenant}.benderand.cl/mi/`  
**UI de referencia:** `ui_modulos_completo.html` → tab POS Avanzado → Portal Cliente

**Tareas específicas:**

| # | Tarea | Tipo |
|---|---|---|
| 3.1 | Crear `portal_cliente.html` (SPA separada) | UI nueva |
| 3.2 | Implementar Transbank WebPay SDK (tarjeta/débito) | Backend |
| 3.3 | Conectar `GET /mi/historial` → historial de compras/rentas | Frontend |
| 3.4 | Conectar `GET /mi/deudas` → deudas pendientes + botón pagar | Frontend |
| 3.5 | Conectar `POST /mi/pedido` → crear pedido remoto (ya existe) | Frontend |
| 3.6 | Vista de renta activa con tiempo restante | UI |

**Estructura del HTML a crear** (basada en `ui_modulos_completo.html §POS Avanzado → Portal`):
```
portal_cliente.html
  ├── Tab: Mis compras (historial)
  ├── Tab: Mis rentas activas (timer)
  ├── Tab: Mis deudas (pagar con WebPay)
  └── Tab: Hacer un pedido (formulario)
```

---

### 🟡 BLOQUE 4 — Completar H7: Config Dinámica por Industria
**Esfuerzo estimado:** 1 semana  
**Impacto:** Sistema multi-industria real — sidebar admin construido desde DB  
**UI de referencia:** `ui_modulos_completo.html` → Admin Panel → nota sobre `rubros_config.modulos_activos`

**Tareas específicas:**

| # | Tarea | Tipo |
|---|---|---|
| 4.1 | Migración `rubros_config` en schema tenant | Backend |
| 4.2 | Seeder con presets: retail, legal, motel, padel, ferreteria, medico, saas | Backend |
| 4.3 | Endpoint `GET /config/rubro` → devuelve config activa del tenant | Backend |
| 4.4 | Admin sidebar: construir menú desde `rubros_config.modulos_activos` | Frontend JS |
| 4.5 | Tab "Config" en admin → toggles de módulos activos | Frontend |
| 4.6 | Accent color dinámico desde `rubros_config.color_acento` | Frontend CSS var |

**Schema propuesto para `rubros_config`:**
```sql
CREATE TABLE rubros_config (
  id BIGSERIAL PRIMARY KEY,
  rubro VARCHAR(50),                    -- 'legal', 'motel', 'retail', etc.
  color_acento VARCHAR(10),             -- '#5b8dee'
  etiqueta_cliente VARCHAR(50),         -- 'Paciente', 'Caso', 'Cliente'
  etiqueta_operario VARCHAR(50),        -- 'Médico', 'Abogado', 'Cajero'
  tiene_stock_fisico BOOLEAN,
  tiene_servicios BOOLEAN,
  tiene_agenda BOOLEAN,
  tiene_rentas BOOLEAN,
  notas_cifradas BOOLEAN,
  modulos_activos JSONB,                -- ['M01','M07','M08',...]
  config_extra JSONB,                   -- datos adicionales por rubro
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

### 🟠 BLOQUE 5 — Completar H4: WhatsApp Onboarding
**Esfuerzo estimado:** 2 días  
**Impacto:** Notificaciones automáticas funcionando (comprobante, deuda, trial)

**Tareas específicas:**

| # | Tarea | Estado actual |
|---|---|---|
| 5.1 | Notificación comprobante al confirmar venta | ⚠️ Parcial en `VentaService` |
| 5.2 | Test onboarding end-to-end (tenant nuevo → WA bot → onboarding completo) | ❌ Pendiente |
| 5.3 | Conectar `CheckDeudasPendientes` job a envío WA real | ✅ Job existe, verificar disparo |
| 5.4 | Verificar `CheckTrialsExpirando` → notifica super_admin + tenant | ✅ Job existe, verificar |

---

### 🟠 BLOQUE 6 — Completar H5: Super Admin + Billing
**Esfuerzo estimado:** 1–2 días  
**Impacto:** MRR real, cobros automáticos, panel superadmin con datos reales

**Tareas específicas:**

| # | Tarea | Estado actual |
|---|---|---|
| 6.1 | Test MRR correcto (verificar cálculo en `MetricsService`) | ❌ Sin test |
| 6.2 | Conectar `superadmin.html` → `GET /central/tenants` con paginación | ⚠️ Stubs JS |
| 6.3 | Conectar `superadmin.html` → `GET /central/metrics` (MRR, churn, ARR) | ⚠️ Stubs JS |
| 6.4 | Test cron `ProcesarCobrosMensuales` en entorno staging | ❌ Pendiente |

---

### 🔵 BLOQUE 7 — Módulos Nuevos: H8–H16
**Esfuerzo estimado:** 3+ meses  
**Referencia UI completa:** `ui_modulos_completo.html`

Para cada módulo, el flujo de implementación es:

```
1. Migraciones Laravel (php artisan make:migration)
2. Modelos Eloquent
3. Controllers + Routes en tenant.php
4. Services si tiene lógica compleja
5. Conectar al HTML correspondiente de ui_modulos_completo.html
6. QA + tests
```

**Orden recomendado por valor de negocio:**

| Orden | Hito | Módulo | UI de referencia | Esfuerzo |
|---|---|---|---|---|
| 1 | H10 | Compras y Proveedores avanzado (OC, recepción, proveedores) | `ui_modulos_completo.html §Admin → Compras` | 1–2 semanas |
| 2 | H9 | SII / LibreDTE (emisión DTE, libros, CAF) | `ui_modulos_completo.html §SII/DTE` | 2 semanas |
| 3 | H8 | ERP ↔ WhatsApp Bot (conversaciones, auto-respuestas, pedidos) | `ui_modulos_completo.html §WhatsApp Bot` | 1–2 semanas |
| 4 | H13 | RRHH (empleados, turnos, liquidaciones) | `ui_modulos_completo.html §RRHH` | 2–3 semanas |
| 5 | H11 | Delivery y Logística | `ui_modulos_completo.html §Delivery` | 2 semanas |
| 6 | H12 | Restaurante / Recetas | `ui_modulos_completo.html §Restaurante` | 1–2 semanas |
| 7 | H14 | Reclutamiento | `ui_modulos_completo.html §Reclutamiento` | 1 semana |
| 8 | H15 | Marketing QR | `ui_modulos_completo.html §Marketing QR` | 1 semana |
| 9 | H16 | M31: Venta Software SaaS | `ui_plan_completo.html §SaaS` | 1 semana |

---

### 🔵 BLOQUE 8 — UI Nueva: Vistas Pendientes de Crear

Estas vistas están **diseñadas en los HTML de referencia** pero no existen como archivos propios aún:

| Vista | Basada en | Prioridad |
|---|---|---|
| `portal_cliente.html` | `ui_modulos_completo.html §POS Avanzado → Portal Cliente` | Alta (H6) |
| `rentas_panel.html` | `ui_plan_completo.html §Motel` + `§Pádel` + `ui_modulos_completo.html §POS Avanzado → Rentas` | Alta (H3) |
| `compras_v2.html` (integrar en admin) | `ui_modulos_completo.html §Admin → Compras` | Media (H10) |
| `sii_dte.html` | `ui_modulos_completo.html §SII/DTE` | Media (H9) |
| `whatsapp_bot.html` | `ui_modulos_completo.html §WhatsApp Bot` | Media (H8) |
| `rrhh.html` | `ui_modulos_completo.html §RRHH` | Media (H13) |
| `delivery.html` | `ui_modulos_completo.html §Delivery` | Baja (H11) |
| `restaurante.html` | `ui_modulos_completo.html §Restaurante` | Baja (H12) |
| `reclutamiento.html` | `ui_modulos_completo.html §Reclutamiento` | Baja (H14) |
| `marketing_qr.html` | `ui_modulos_completo.html §Marketing QR` | Baja (H15) |

---

## QA Pendiente (Independiente de Hitos)

| # | Tarea | Corresponde a |
|---|---|---|
| Q1 | Test multi-operario simultáneo (2 usuarios en misma venta) | H2 |
| Q2 | Test MRR correcto (`MetricsService`) | H5 |
| Q3 | Test WhatsApp end-to-end (tenant nuevo → bot → activación) | H4 |
| Q4 | Verificar `pos_v3.html` en mobile (viewport 375px) | H1 |
| Q5 | Test cron `ProcesarCobrosMensuales` en staging | H5 |
| Q6 | Test `CheckRentasVencidas` → alerta + extensión automática | H3 |

---

## Design System — Referencia Rápida

```css
/* Tipografía */
IBM Plex Mono  → precios, códigos, IDs, timestamps
IBM Plex Sans  → interfaz general

/* Tokens base */
--bg:  #08080a   --s1:  #111115   --s2:  #18181e   --s3:  #1e1e26
--b1:  #252530   --b2:  #32323f
--tx:  #e8e8f0   --t2:  #8888a0   --t3:  #4a4a60
--ok:  #00e5a0   --warn:#f5c518   --err: #ff3f5b   --info:#4488ff

/* Accents por industria (ui_plan_completo.html) */
--ac-legal:#5b8dee  --ac-padel:#00e5a0  --ac-motel:#ff6b35
--ac-abar:#f5c518   --ac-ferr:#c084fc   --ac-medico:#3dd9eb
--ac-saas:#e040fb

/* Accents por módulo (ui_modulos_completo.html) */
--ac-admin:#e040fb  --ac-delivery:#00c4ff  --ac-rest:#ff6b35
--ac-rrhh:#3dd9eb   --ac-rec:#f5c518       --ac-qr:#00e5a0
--ac-sii:#5b8dee    --ac-wa:#25d366        --ac-pos:#c084fc

/* Layout */
[NAV 52px sticky] → [TABS 38px sticky top:52px] → [content max-w:1160px]
Responsive: ≤700px → sidebar oculto, grids a 1 columna
```

---

## Checklist de Completitud de Este Hito (UI)

### `ui_plan_completo.html`
- [x] 7 industrias con accent único
- [x] POS funcional con variaciones por industria
- [x] Agenda / Habitaciones / Canchas / Licencias según rubro
- [x] CRM / Expediente / Ficha Paciente / Pipeline según rubro
- [x] Panel admin con KPIs por rubro
- [x] Tab Roles & Config con `rubros_config` documentado por industria
- [x] Design responsive (≤700px)
- [x] Navegación Vanilla JS sin recarga

### `ui_modulos_completo.html`
- [x] Admin Panel con sidebar dinámico y alertas por módulo activo
- [x] POS Avanzado (fraccionados + rentas + portal cliente)
- [x] Delivery (mapa, repartidores, zonas, tracking)
- [x] Restaurante (recetas, insumos, mermas, menú digital)
- [x] RRHH (empleados, turnos, liquidaciones, vacaciones)
- [x] Reclutamiento (Kanban, evaluaciones, ofertas, onboarding)
- [x] Marketing QR (campañas, analytics, segmentos, automatización)
- [x] SII/DTE (emisión, libros, LibreDTE config, CAF)
- [x] WhatsApp Bot (conversaciones, auto-respuestas, pedidos, config)
- [x] Design responsive (≤700px)

---

## Resumen Ejecutivo para Próxima Sesión

**Lo que está listo para usar hoy:**
- POS (`pos_v3.html`) conectado al backend real — H1 ✅
- Roles y multi-operario — H2 ✅
- Dashboard ejecutivo + API pública — H17 ✅
- Todo el diseño visual de todos los módulos — este hito ✅

**Lo que se puede atacar inmediatamente (backend ya existe):**
1. Conectar `admin_dashboard_v2.html` al API → BLOQUE 1 (2–3 días)
2. `superadmin.html` al API central → BLOQUE 1.6 (1 día)
3. Integrar `benderand-debug.js` en todos los HTML → BLOQUE 1.7 (1 hora)

**Lo que requiere desarrollo nuevo:**
- Rentas: panel visual + timer (H3) → BLOQUE 2
- Portal cliente HTML (H6) → BLOQUE 3
- `rubros_config` en DB (H7) → BLOQUE 4
- Todos los módulos nuevos H8–H16 → BLOQUE 7

**Archivos de referencia a tener siempre abiertos:**
```
ui_plan_completo.html      → flujos por industria
ui_modulos_completo.html   → flujos por módulo  
analysis_report.md         → estado real del proyecto
BENDERAND_DOCS_INDEX.md    → índice de todos los documentos
```

---

*BenderAnd ERP — Hito UI Completa v2*  
*Stack: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Sanctum · Redis*  
*Design System: IBM Plex Mono + Sans · Dark #08080a · Accents por industria/módulo*
