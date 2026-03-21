# BenderAnd POS — Set de Pruebas Manuales · Hito 7

> **Stack:** Laravel 11 · Multi-Tenant (stancl/tenancy) · PostgreSQL  
> **UI:** IBM Plex Sans/Mono · Dark Mode · Mobile-First

---

## 1. Acceso al Sistema

### Iniciar el servidor

```bash
# Desde el directorio del proyecto
docker run --rm --network benderandos_default \
  -v $(pwd):/app -w /app -p 8000:8000 \
  benderand_php php artisan serve --host=0.0.0.0 --port=8000
```

### URL y Hosts de Acceso

Para el sistema de tenancia por dominio, agregar al [/etc/hosts](file:///etc/hosts):

```
# BenderAnd Local Tenants
127.0.0.1   demo.localhost
127.0.0.1   ferreteria.localhost
127.0.0.1   motel.localhost
```

| URL | Descripción |
|-----|-------------|
| `http://demo.localhost:8000/login` | Login POS tenant demo |
| `http://demo.localhost:8000/pos` | Pantalla POS |
| `http://demo.localhost:8000/admin/dashboard` | Dashboard Admin |
| `http://demo.localhost:8000/portal/login` | Portal Cliente |

---

## 2. Credenciales de Prueba Locales

> [!NOTE]
> Los tenants y usuarios de prueba deben crearse con el seeder. Ejecutar:
> ```bash
> docker run --rm --network benderandos_default -v $(pwd):/app -w /app benderand_php php artisan tenants:seed
> ```

| Rol | Login | Contraseña | Acceso |
|-----|-------|-----------|--------|
| Admin | `admin@demo.cl` | `admin123` | `/admin/dashboard` |
| Cajero | `cajero@demo.cl` | `cajero123` | `/pos` |
| Operario | `operario@demo.cl` | `operario123` | `/operario` |
| Cliente | `cliente@demo.cl` | `cliente123` | `/portal/login` |

> [!TIP]
> También se puede usar el RUT en lugar del email: `12.345.678-9`

---

## 3. Casos de Prueba — Login

### P-001: Login Admin
1. Abrir `http://demo.localhost:8000/login`
2. Ingresar email: `admin@demo.cl` / password: `admin123`
3. **Esperar:** Redirige a `/admin/dashboard`
4. Verificar: Sidebar con todas las opciones del admin visibles

### P-002: Login Cajero
1. Login con `cajero@demo.cl` / `cajero123`
2. **Esperar:** Redirige a `/pos`
3. Verificar: Solo aparecen opciones de POS e Historial (no admin)

### P-003: Login Operario
1. Login con `operario@demo.cl` / `operario123`  
2. **Esperar:** Redirige a `/operario`
3. Verificar: Panel con tabs Vender/Stock/Mis ventas

### P-004: Credenciales Inválidas
1. Ingresar email incorrecto
2. **Esperar:** Mensaje de error "Credenciales inválidas" visible bajo el form

---

## 4. Casos de Prueba — POS Principal

### P-010: Cargar catálogo
1. Ir a `/pos`
2. **Esperar:** Grid de productos con fotos/iniciales, precio y stock
3. Verificar: Filtros de familia en la barra superior

### P-011: Buscar Producto
1. En el buscador, escribir parte del nombre
2. **Esperar:** Grid filtra en tiempo real (sin recargar)

### P-012: Agregar producto al carro
1. Click en un producto de tipo **Stock**
2. **Esperar:** Producto aparece en el panel derecho con qty 1
3. Verificar: Toast verde con nombre del producto

### P-013: Modificar cantidad
1. Con producto en carro, usar botones `+` y `−`
2. **Esperar:** Cantidad y total se actualizan instantáneamente

### P-014: Cobrar con Efectivo
1. Con items en carro, hacer click en **COBRAR**
2. Modal aparece con total calculado
3. Ingresar monto recibido
4. **Esperar:** Campo "Vuelto" se calcula automáticamente
5. Click "Confirmar Venta"
6. **Esperar:** Modal de ticket aparece con resumen

### P-015: Cambiar Método de Pago
1. En el carro, seleccionar "Débito" en las pills
2. Click COBRAR
3. **Esperar:** Modal no muestra campo de vuelto

### P-016: Venta con cliente
1. En el campo "RUT cliente" escribir RUT existente (esperar 500ms)
2. **Esperar:** Banner azul con nombre del cliente aparece
3. Completar venta
4. Verificar: En historial la venta tiene cliente asociado

### P-017: Producto tipo Renta
1. Click en producto de tipo **RENTA**
2. **Esperar:** Modal de duración abre con pills (1h, 2h, 4h, etc.)
3. Seleccionar duración
4. **Esperar:** Total calculado automáticamente
5. Click "Agregar al Carro"

### P-018: Ticket e Impresión
1. Tras confirmar venta, click "🖨 Imprimir" en modal Ticket
2. **Esperar:** Nueva ventana del navegador con preview de ticket se abre
3. Verificar: Formato tipo ticket térmico 80mm con datos de venta

---

## 5. Casos de Prueba — Admin Panel

### P-020: Dashboard KPIs
1. Ir a `/admin/dashboard`
2. **Esperar:** 4 KPIs cargan desde la API (Ventas, Ticket, Clientes, Stock crítico)
3. Verificar: Gráfico de barras de los últimos 30 días visible

### P-021: Toggle período Dashboard
1. Click en "Semana" en los tabs de período
2. **Esperar:** KPIs actualizan (aunque en fase MVP pueden no cambiar)

### P-022: Crear Producto
1. Ir a `/admin/productos`
2. Click "+ Nuevo Producto"
3. Completar: Código, Nombre, Tipo (Stock), Precio, Stock
4. Click "Guardar"
5. **Esperar:** Producto aparece en la tabla, toast "Producto creado"

### P-023: Editar Producto
1. En tabla de productos, click en ✏ de cualquier producto
2. Modificar precio
3. Click "Guardar"
4. **Esperar:** Tabla se actualiza con nuevo precio

### P-024: Ajuste de Stock
1. Click en ± del producto
2. Seleccionar motivo "Llegada mercadería"
3. Ingresar cantidad: `10`
4. Click "Aplicar Ajuste"
5. **Esperar:** Stock del producto se actualiza en tabla

### P-025: Toggle Vista Lista/Grid
1. Click en el icono de grid (esquina derecha)
2. **Esperar:** Productos aparecen en tarjetas visuales
3. Click de vuelta al icono de lista
4. **Esperar:** Tabla vuelve a aparecer

### P-026: Búsqueda Tiempo Real Productos
1. Escribir en buscador
2. **Esperar:** Tabla/grid filtra sin recargar

### P-027: Ficha de Cliente
1. Ir a `/admin/clientes`
2. Click en "Ficha" de cualquier cliente
3. **Esperar:** Modal con tabs (Compras, Deudas, Encargos)
4. Verificar: Tab Compras muestra historial

### P-028: Crear Usuario
1. Ir a `/admin/usuarios`
2. Click "+ Nuevo Usuario"
3. Completar datos, seleccionar Rol "Cajero"
4. Ingresar contraseña
5. Click "Guardar"
6. **Esperar:** Usuario aparece en tabla con rol badge azul

---

## 6. Casos de Prueba — Operario Panel

### P-030: Panel operario tabs
1. Ir a `/operario`
2. Verificar: 3 tabs visibles (Vender / Stock / Mis ventas)
3. Click en "Stock"
4. **Esperar:** Buscador de producto para ajuste de stock aparece

### P-031: Ajuste desde Operario
1. En tab Stock, buscar producto
2. Seleccionar de resultados
3. Ingresar cantidad y motivo
4. Click "Aplicar Ajuste"
5. **Esperar:** Toast confirmación, formulario limpio

---

## 7. Casos de Prueba — Rentas

### P-040: Panel de Rentas
1. Ir a `/admin/rentas` (o `/rentas`)
2. **Esperar:** Grid de unidades con colores (verde=libre, rojo=ocupado)
3. Verificar: KPIs de disponibilidad en la parte superior

### P-041: Timer en tiempo real
1. Con unidad ocupada activa
2. **Esperar:** Timer hace countdown en tiempo real (sin recargar página)
3. Verificar: Timer se pone amarillo cuando quedan <10 min

### P-042: Devolver Unidad
1. Click en unidad ocupada
2. **Esperar:** Modal con detalle (cliente, inicio, fin, monto)
3. Click "Devolver Unidad"
4. **Esperar:** Unidad cambia a verde "LIBRE" en el grid

---

## 8. Casos de Prueba — Portal Cliente

### P-050: Login Portal Cliente
1. Ir a `/portal/login`
2. Ingresar email y contraseña del cliente
3. **Esperar:** Redirige a `/portal/catalogo`

### P-051: Catálogo Online
1. Ver catálogo móvil
2. **Esperar:** Diseño Glassmorphism con productos disponibles
3. Buscar producto en tiempo real
4. Agregar al carrito

### P-052: Crear Pedido Remoto
1. Con items en carrito, click "Confirmar Pedido"
2. **Esperar:** Pedido creado con estado `remota_pendiente`
3. Ir a `/portal/historial`
4. Verificar: Pedido aparece con botón "Pagar con WebPay"

### P-053: Estado de Cuenta
1. Ir a `/portal/deudas`
2. **Esperar:** Lista de deudas pendientes con total

---

## 9. Verificaciones Responsive

### P-060: Mobile POS
1. Reducir ventana a < 600px (o usar DevTools mobile)
2. Ir a `/pos`
3. **Esperar:** Bottom navigation aparece (reemplaza sidebar)
4. Verificar: Layout de una columna (catálogo primary)

### P-061: Drawer Mobile
1. En mobile, click en ☰ (hamburger)
2. **Esperar:** Sidebar aparece como drawer lateral con overlay oscuro
3. Click afuera del drawer
4. **Esperar:** Drawer se cierra

### P-062: Tablet POS
1. Ancho 768px (tablet)
2. **Esperar:** Layout 2 columnas: catálogo | carro

---

## 10. Theming por Rubro

### P-070: Cambiar accent color
El accent se configura vía `$rubroClass` en el layout. Para probar:
1. En `WebPanelController::pos()`, cambiar `$rubroClass` a `rubro-motel`
2. **Esperar:** Accent naranja (`#ff6b35`) en toda la UI

| Clase CSS | Color | Rubro |
|-----------|-------|-------|
| `rubro-retail` | 🟢 #00e5a0 | Abarrotes |
| `rubro-hardware` | 🟡 #f5c518 | Ferretería |
| `rubro-padel` | 🔵 #00c4ff | Pádel |
| `rubro-motel` | 🟠 #ff6b35 | Motel |
| `rubro-medical` | 🩵 #3dd9eb | Médico |
| `rubro-legal` | 🟣 #7c6af7 | Abogado |

---

## 11. Bugs Conocidos / Notas

| # | Descripción | Severidad |
|---|-------------|-----------|
| B-001 | Transbank WebPay usa modo Integration (pruebas). Para producción configurar [.env](file:///home/master/trabajo/proyectos/src/benderandos/.env) | Media |
| B-002 | El `@json` en [operario/index.blade.php](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/tenant/operario/index.blade.php) muestra lint warning pero es PHP válido de Blade | Baja |
| B-003 | El Portal Cliente tiene lints de propiedades en las views Blade heredadas del Hito 6 | Baja |
| B-004 | Los timers de renta requieren F5 al primer acceso para sincronizar si hay rentas previas | Baja |

---

*Set de pruebas generado post-implementación Hito 7 · BenderAnd POS · Multi-Rubro SaaS*
