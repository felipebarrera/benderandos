# H8 UI — Refinamiento: Roles · Config · Super Admin
**BenderAnd POS · Pantallas HTML (Vanilla JS)**
*Estado: ✅ Pantallas creadas · Backend: ⬜ Pendiente (requiere H2)*

> Este hito produjo refinamientos de las pantallas HTML existentes.
> No es un hito de backend. Corresponde al archivo `HITO8_PLAN.md`.

---

## Qué se hizo

| # | Área | Cambio | Archivo |
|---|---|---|---|
| 1 | POS | Cliente persiste en `sessionStorage` por turno | `pos_v4.html` |
| 2 | POS | Sin menú de inventario — solo caja | `pos_v4.html` |
| 3 | Admin | No accede al POS — panel propio | `admin_dashboard_v2.html` |
| 4 | Admin | Navegación separada: Dashboard · Ventas · Inventario · CRM · Config | `admin_dashboard_v2.html` |
| 5 | Super Admin | Panel completo: empresas · cobros · métricas MRR | `superadmin.html` |
| 6 | Config | Vista de configuración de empresa (tabs) | `admin_dashboard_v2.html` |
| 7 | Login | Redirección por rol al autenticar | `login.html` |

---

## Separación de rutas por rol (frontend)

```
/login → detecta rol → redirige

super_admin → /superadmin/dashboard
admin       → /admin/dashboard
cajero      → /pos
operario    → /operario
cliente     → /cliente
```

**Admin NO accede a /pos.** Ventas → solo lectura en /admin/ventas.

---

## sessionStorage POS — cliente persistente

```javascript
// Guardar al identificar cliente
sessionStorage.setItem('pos_cliente', JSON.stringify(cliente));

// Restaurar al cargar el POS
const saved = sessionStorage.getItem('pos_cliente');
if (saved) setCliente(JSON.parse(saved));

// Limpiar al hacer logout / cerrar turno
sessionStorage.removeItem('pos_cliente');
```

---

## Configuración empresa — tabs

```
/admin/config
  ├── General     → nombre, logo, dirección, teléfono, email
  ├── Rubro       → preset activo (cambia accent + módulos visibles)
  ├── Pagos       → habilitar/deshabilitar métodos de pago
  ├── Ticket      → preview en vivo del ticket térmico
  ├── Usuarios    → crear/editar usuarios del tenant
  └── WhatsApp    → estado del bot, token, mensaje de bienvenida
```

---

## Pendiente para conectar al backend (H2 + H5)

- Redirección por rol requiere `rol` en el payload del token Sanctum (H2)
- `sessionStorage` funciona hoy, pero al implementar H1 el cliente viene de la API
- Config empresa se guarda en `rubros_config` del tenant (H7)
- Super admin UI requiere endpoints `/central/tenants` (H5)
