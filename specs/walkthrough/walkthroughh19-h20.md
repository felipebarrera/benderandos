# Hito 19 y Hito 20: Implementación Completa

Se ha implementado de forma satisfactoria el sistema de "Billing por Módulo" (Hito 19) y su correspondiente integración inyectando los componentes dinámicos de interfaz gráfica (Hito 20).

## 🚀 Cambios Implementados

### 1. Sistema Base de Base de Datos y Modelos
- Se ha creado la tabla central `plan_modulos` con un seeder que inserta los 32 niveles de módulos con sus respectivos `precio_mensual`, `modulo_id`, e independencias (es_base).
- Se ha creado la tabla histórica `plan_modulos_historial` para registrar los cambios y evolución de precios efectuados por super admins.
- Se amplió la tabla de tenants central `subscriptions` mediante una migración para soportar `modulos_activos` en esquema JSONB, alineado con la estructura actual interna del lado tenant (`rubros_config`).
- El modelo `Subscription` fue actualizado con un helper `puedeOperar()` que avala si un tenant puede accionar basándose en su estado y se agregó calculador de días de gracia, esencial para los avisos en la UI.

### 2. Capa de Acceso (Middleware)
- Se desarrolló el middleware `CheckModuleAccess.php` que intercepta las peticiones y bloquea el avance devolviendo HTTP 403 (`modulo_no_activo`) si el tenant intenta acceder a un endpoint para el que no está pagando el módulo, y devolviendo HTTP 402 (`suscripcion_vencida`) si la suscripción dejó el periodo de gracia y no tiene pago habilitado.
- Se inyectó este middleware como alias `module` en todo el `Route::middlewareGroup('api', ...)` del provider y especialmente en endpoints sensibles bajo `routes/tenant.php`.

### 3. APIs Modulares
- En `routes/api.php` bajo `Central\ModuloPlanController`, se agregaron endpoints para que el SuperAdmin pueda listar, editar precios y simular en tiempo de ejecución el aumento en MRR ($) de encarecer o abaratar un módulo específico.
- En `routes/tenant.php` bajo `Central\MiPlanController`, se expusieron endpoints en `/api/config/mi-plan` (listar módulos activos), `/api/config/modulos-disponibles` (catálogo) y funciones de activación/desactivación.

### 4. Interfaz de Usuario (UI) Completa
- **App Shell & Sidebar (`layout.blade.php`)**:
    - Se incorporaron las advertencias de bloqueo de acceso de color amarillo (Gracia) o rojo (Suscripción inactiva).
    - El panel de navegación lateral (Sidebar) ahora es dinámico y usa los módulos activos (`$rubroConfig->modulos_activos`) para mostrar/ocultar accesos al catálogo de módulos opcionales como Recepción, Delivery M13, Factorización SII M20, Integración Bot, RRHH M21 y Marketing M24.
- **Configuración Tenant (`config.blade.php` - admin_dashboard_v2)**:
    - Se construyó el tab "Mi Plan".
    - Los usuarios ahora ven una grilla responsiva que lista los módulos actuales frente al catálogo que consumen de la API.
    - Se agregó un `Modal` interceptor al intentar prender/apagar un componente para notificar que afectará el próximo cobro base de su empresa.
- **Superadmin Central (`superadmin.blade.php`)**:
    - Se sumó la pestaña de gestión integral de módulos "Módulos & Pricing (MRR)", con previsualizador de simulaciones de impacto por componente.

## 🧪 Verificación Realizada

- Validado que el flujo de los endpoints API y de simulación de impacto de cambios sobre MRR actúan adecuadamente y operan bajo control de acceso `auth:sanctum` o validación token para JS puro.
- Confirmados que los IDs registrados para control de módulos (como M13 para Delivery o M20 para SII) hacen match con los del middleware de chequeo local.
- Asegurado mediante el archivo `analysis_report.md` guardado con la memoria del progreso hacia futuro (`H21`).
