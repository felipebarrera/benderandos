# BENDERAND ERP — ÍNDICE COMPLETO DE DOCUMENTOS GENERADOS
**Registro de todo lo producido · Estado real · Sin contradicciones**
*Actualizado: Marzo 2026*

---

## ADVERTENCIA DE STACK

> **Stack definitivo del proyecto:**
> **Laravel 11 + PostgreSQL 16 + stancl/tenancy v3 + Sanctum + Redis**
>
> Varios documentos anteriores mencionan **CodeIgniter 4 y MariaDB** — esos son documentos
> de versiones previas descartadas. No implementar con CI4 ni MariaDB.
>
> La fuente de verdad del stack son: `ROADMAP.md`, `STACK.md`, `README.md`, `GENERADO.md` (subidos).
> Todos dicen: **Laravel 11 + PostgreSQL 16**.

---

## 1. DOCUMENTOS DEL PROYECTO (fuentes de verdad)

### 1.1 Specs Laravel (stack correcto ✅)

| Archivo | Contenido | Stack |
|---|---|---|
| `README.md` | Arranque rápido, estructura del repo | Laravel 11 + PG16 ✅ |
| `ROADMAP.md` | Arquitectura, hitos H0–H6, decisiones técnicas | Laravel 11 + PG16 ✅ |
| `STACK.md` | Instalación VPS, PostgreSQL, Redis, Nginx, Supervisor | Laravel 11 + PG16 ✅ |
| `DATABASE.md` | Modelo de datos completo PostgreSQL (schema public + tenant) | PG16 ✅ |
| `ROLES.md` | Permisos por rol, Gates, RoleMiddleware, payload login | Laravel 11 ✅ |
| `API.md` | Contratos de todos los endpoints con ejemplos JSON | Laravel 11 ✅ |
| `WHATSAPP.md` | Contrato HTTP ERP ↔ Bot (webhooks, mensajes automáticos) | Laravel 11 ✅ |
| `GENERADO.md` | Registro histórico de archivos generados (2026-03-11) | Laravel 11 ✅ |

### 1.2 Documentos de diseño UI (stack incorrecto en header, diseño válido)

> Estos documentos mencionan CI4/MariaDB en el header pero el **diseño de pantallas y flujos es válido**.
> Aplicar el diseño, ignorar las referencias de stack.

| Archivo | Contenido | Nota |
|---|---|---|
| `UI_PLAN_HITO7_BenderAnd.md` | Sistema de diseño completo: tokens, tipografía, layouts por rol, componentes, adaptación por rubro | Diseño válido ✅, CI4 en header ⚠️ |
| `benderand-pos-roadmap.md` | Hitos 2, 3, 5, 6 con código de implementación detallado | Diseño válido ✅, CI4 en código ⚠️ |
| `HITO8_PLAN.md` | Separación de roles, configuración empresa, super admin UI | Diseño válido ✅, CI4 en código ⚠️ |
| `HITO9_PLAN.md` | Módulo compras/proveedores integrado en admin dashboard | Diseño válido ✅, CI4 en código ⚠️ |
| `HITO9_DEBUG.md` | Sistema de debug logger JS (`benderand-debug.js`) para todos los HTML | Vanilla JS ✅ válido |

### 1.3 Documentos históricos (obsoletos, no usar)

| Archivo | Por qué obsoleto |
|---|---|
| `benderand_spec_v1.docx` | CI4 + MariaDB — reemplazado por ROADMAP.md + DATABASE.md |
| `admin_benderand.sql` | PHP 7.4 + MariaDB — modelo de datos anterior, sin multi-tenant |

---

## 2. PANTALLAS HTML CONSTRUIDAS

Todos los archivos HTML son **prototipos funcionales con datos stubbed** (Vanilla JS).
No tienen backend real conectado aún — los stubs están marcados como `// TODO: API`.

| Archivo | Vista | Corresponde a | Estado UI |
|---|---|---|---|
| `login.html` | Login + redirección por rol | H1, H2 | ✅ Completo |
| `pos_v4.html` | POS cajero: búsqueda, carrito, cobro, cliente persistente en sessionStorage | H1, H2, H8 | ✅ Completo |
| `admin_dashboard_v2.html` | Panel admin SPA: dashboard, ventas, productos, compras básico, clientes, deudas, config | H1–H8 | ✅ Completo (compras básico) |
| `superadmin.html` | Super admin: empresas, billing, métricas MRR, wizard nueva empresa | H5, H8 | ✅ Completo |
| `compra.html` | Registro de compra de stock | H1 | ✅ Completo |
| `ticket.html` | Ticket térmico post-venta con print | H1 | ✅ Completo |

### Pendiente de crear (identificado en HITO9_PLAN.md)
- `compras_proveedores.html` → módulo compras completo (mencionado como creado en H8, pendiente de integrar en admin_dashboard)

---

## 3. SISTEMA DE DISEÑO (UI_PLAN_HITO7_BenderAnd.md)

El documento define el **design system completo** del proyecto. Válido para todos los hitos.

### Tokens de color
```css
--bg: #08080a     /* fondo raíz */
--s1: #111115     /* nav, sidebars */
--s2: #18181e     /* cards, inputs */
--s3: #1e1e26     /* hover, active */
--b1: #252530     /* borde sutil */
--b2: #32323f     /* borde medio */
--text: #e8e8f0   /* primario */
--t2: #8888a0     /* secundario */
--t3: #4a4a60     /* deshabilitado */

/* Accents por rubro */
--accent-retail:   #00e5a0
--accent-legal:    #7c6af7
--accent-padel:    #00c4ff
--accent-motel:    #ff6b35
--accent-medical:  #3dd9eb
--accent-hardware: #f5c518
--accent-admin:    #e040fb

/* Semánticos */
--ok:  #00e5a0
--warn: #f5c518
--err: #ff3f5b
--info: #00c4ff
```

### Tipografía
- **Precios / códigos / IDs**: IBM Plex Mono
- **Interfaz general**: IBM Plex Sans
- **Títulos de sección**: IBM Plex Sans 700

### Layout raíz
```
[NAV TOP — 54px sticky]
[CONTENIDO — flex o grid según vista]
[BOTTOM BAR — 60px en móvil, solo cajero/operario]
```

---

## 4. SISTEMA DE DEBUG (HITO9_DEBUG.md)

El documento define `benderand-debug.js` — un logger JS que se agrega a todos los HTML.

### Qué captura
- `window.onerror` — errores JS no capturados
- `unhandledrejection` — promesas sin catch
- `fetch()` interceptado — todas las llamadas de red
- `console.error/warn` — logs de consola
- Event listeners — errores en click handlers

### Activación
- `Ctrl + Shift + D` → abre panel de debug
- Botón 🐛 en esquina inferior derecha

### API manual
```javascript
ba.log('descripción', { datos: 'opcionales' });
ba.error('descripción', { contexto: 'info' });
ba.event('nombre_acción', { dato: valor });
baDebug.open()    // abrir panel
baDebug.copy()    // copiar JSON al portapapeles
baDebug.prompt()  // copiar prompt para Antigravity
baDebug.test()    // lanzar error de prueba
```

### Archivos que necesitan integración
```html
<script src="benderand-debug.js"></script>
<!-- Agregar antes de </body> en: -->
<!-- pos_v4.html ✅ -->
<!-- admin_dashboard_v2.html ✅ -->
<!-- superadmin.html ✅ -->
<!-- compra.html ✅ -->
<!-- login.html ✅ -->
<!-- ticket.html ✅ -->
<!-- cualquier HTML nuevo ✅ -->
```

---

## 5. HITOS — ESTADO REAL Y CORRESPONDENCIA DE DOCUMENTOS

### Hitos del roadmap original (H0–H6) — Stack: Laravel 11 + PostgreSQL

| Hito | Nombre | Documento de spec | Pantallas HTML | Estado backend |
|---|---|---|---|---|
| H0 | Infra dev + CI/CD | `STACK.md`, `README.md` | — | ✅ Completado |
| H1 | Venta minorista | `H1-venta.md` (nuevo) | `pos_v4.html`, `compra.html`, `ticket.html` | ⬜ En progreso |
| H2 | Multi-operario + Roles | `H2-roles.md` (nuevo), `benderand-pos-roadmap.md` §H2 | `pos_v4.html` (stub) | ⬜ Pendiente |
| H3 | Renta + Servicios | `H3-renta.md` (nuevo), `benderand-pos-roadmap.md` §H3 | `pos_v4.html` (stub) | ⬜ Pendiente |
| H4 | WhatsApp Onboarding | `H4-whatsapp.md` (nuevo), `WHATSAPP.md` | — | ⬜ Pendiente |
| H5 | Super Admin + Billing | `H5-superadmin.md` (nuevo), `benderand-pos-roadmap.md` §H5 | `superadmin.html` (stub) | ⬜ Pendiente |
| H6 | Portal Cliente Web | `H6-cliente.md` (nuevo), `benderand-pos-roadmap.md` §H6 | — | ⬜ Pendiente |

### Hitos de UI/Refinamiento (generados para prototipos HTML)

> Estos hitos se ejecutaron como refinamiento de UI antes de que el backend esté conectado.
> Producen pantallas HTML estáticas con datos mock. No son hitos de backend Laravel.

| Hito UI | Documento | Qué produjo | Estado |
|---|---|---|---|
| **H7 UI** | `UI_PLAN_HITO7_BenderAnd.md` | Design system completo, specs de todas las vistas por rol | ✅ Documentado |
| **H8 UI** | `HITO8_PLAN.md` | `pos_v4.html`, `admin_dashboard_v2.html`, `superadmin.html` | ✅ Pantallas HTML creadas |
| **H9 UI** | `HITO9_PLAN.md` | Plan integración módulo compras/proveedores en admin dashboard | ⬜ Pendiente integración |

### Hitos de módulos nuevos (v2.0) — Stack: Laravel 11 + PostgreSQL

| Hito | Nombre | Documento | Estado |
|---|---|---|---|
| H7 | Config dinámica por industria | `H7-config-industrias.md`, `BENDERAND_CONFIG_INDUSTRIAS.md` | ⬜ Pendiente |
| H8 | Integración ERP ↔ WhatsApp Bot | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H9 | SII / LibreDTE | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H10 | Compras y Proveedores | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H11 | Delivery y Logística | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H12 | Restaurante: Recetas | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H13 | RRHH completo | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H14 | Reclutamiento | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H15 | Marketing QR | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H16 | M31: Venta Software SaaS | `H8-H18-modulos-nuevos.md`, `BENDERAND_CONFIG_INDUSTRIAS.md §G` | ⬜ Pendiente |
| H17 | Dashboard ejecutivo + API pública | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |
| H18 | Testing + Deploy | `H8-H18-modulos-nuevos.md` | ⬜ Pendiente |

---

## 6. GUÍA DE LECTURA — POR QUÉ LEER CADA DOCUMENTO

### Para empezar a desarrollar ahora (H1):
1. `ROADMAP.md` — arquitectura y stack definitivo
2. `STACK.md` — instalar el entorno
3. `DATABASE.md` — modelo de datos PostgreSQL
4. `specs/hitos/H1-venta.md` — tareas, comandos artisan, checklist

### Para entender el diseño de pantallas:
1. `UI_PLAN_HITO7_BenderAnd.md` — design system, layouts, componentes
2. Abrir los HTML existentes como referencia visual
3. `HITO9_DEBUG.md` — integrar debug logger en cada HTML nuevo

### Para implementar el módulo compras (H9 UI / H10 backend):
1. `HITO9_PLAN.md` — diagnóstico de lo que existe vs lo que falta en la UI
2. `specs/hitos/H8-H18-modulos-nuevos.md` — spec del backend Laravel H10
3. `BENDERAND_CONFIG_INDUSTRIAS.md` — cómo afectan los módulos a la UI

### Para entender la arquitectura multi-tenant:
1. `ROADMAP.md §Arquitectura Multi-Tenant`
2. `DATABASE.md §Schema public` + `§Schema tenant_{uuid}`
3. `STACK.md §5. Configurar stancl/tenancy`

### Para el sistema de configuración por industria:
1. `BENDERAND_CONFIG_INDUSTRIAS.md` — todos los módulos M01–M31
2. `HITO8_PLAN.md` — cómo la UI se adapta por rol (separación roles)
3. `UI_PLAN_HITO7_BenderAnd.md §8` — adaptaciones por rubro (theming)

---

## 7. INCONSISTENCIAS CONOCIDAS Y CÓMO RESOLVERLAS

| Inconsistencia | Dónde aparece | Cómo resolver |
|---|---|---|
| CI4 mencionado en `HITO8_PLAN.md`, `HITO9_PLAN.md`, `UI_PLAN_HITO7_BenderAnd.md`, `benderand-pos-roadmap.md` | Header + código PHP | Ignorar referencias a CI4. Traducir código PHP al equivalente Laravel 11 (mismo concepto, distinta sintaxis) |
| MariaDB mencionado en docs de UI | `UI_PLAN_HITO7_BenderAnd.md` header | Ignorar. Usar PostgreSQL 16 |
| "Hito 8" tiene dos significados | `HITO8_PLAN.md` = refinamiento UI. `H8` en plan v2 = Integración WA-ERP | El `HITO8_PLAN.md` es un hito de UI, no de backend. El H8 del plan v2 es un hito de backend diferente |
| "Hito 9" tiene dos significados | `HITO9_PLAN.md` = módulo compras en admin UI. `H9` en plan v2 = SII/LibreDTE | El `HITO9_PLAN.md` describe la UI del módulo compras (que en el plan v2 corresponde al backend H10) |
| Numeración de hitos desalineada | Docs UI: H7, H8, H9. Plan v2: H1–H18 | Ver tabla §5 arriba. Los hitos UI son refinamientos de pantallas, no hitos de backend |
| Estado "H1–H8 completados" en versión anterior del plan | `BENDERAND_ERP_COMPLETE_PLAN_v2.md` (versión anterior) | Corregido: H0 ✅ completado, H1 ⬜ en progreso. Pantallas HTML existen pero sin backend |

---

## 8. PRÓXIMA ACCIÓN RECOMENDADA

El sistema está en este punto exacto:

```
✅ Infraestructura: Docker dev configurado, PostgreSQL conectado, Laravel instalado
✅ Pantallas HTML: pos_v4.html, admin_dashboard_v2.html, superadmin.html, login.html, ticket.html
⬜ Backend Laravel: sin endpoints reales aún (todo son stubs JS en los HTML)
⬜ Conexión HTML → API: los fetch() en los HTML están apuntando a endpoints que no existen
```

**Prioridad 1:** Implementar los endpoints de H1 en Laravel para que los HTML existentes funcionen con datos reales.

Orden específico:
1. `POST /auth/login` + token Sanctum
2. `GET /productos/buscar?q=` (conecta el buscador del POS)
3. `POST /ventas` + `POST /ventas/{id}/items` + `POST /ventas/{id}/confirmar`
4. `GET /dashboard` (conecta el admin panel)
5. `POST /compras` (conecta compra.html)

Ver `specs/hitos/H1-venta.md` para los comandos artisan exactos y el checklist completo.

---

*BenderAnd ERP — Índice de documentos generados*
*Stack definitivo: Laravel 11 · PostgreSQL 16 · stancl/tenancy v3 · Sanctum · Redis*
