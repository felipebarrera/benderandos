# Fix: Menú lateral en superadmin + Spider en superadmin

## Contexto del problema

El fix anterior para el error SQL `"relation usuarios does not exist"` movió las rutas de superadmin a un grupo de dominio central en `routes/web.php`. Ese cambio fue correcto para el SQL, pero tuvo dos efectos colaterales en la UI:

1. **`superadmin` perdió su menú lateral** — quedó en el layout de `central`
2. **Spider quedó desconectado** — debe vivir dentro de `superadmin` con su propio menú

## Estado actual (post-fix)

| Panel | URL | Menú lateral | Estado |
|---|---|---|---|
| `central` | `localhost/central/*` | ✅ Tiene menú lateral | OK |
| `superadmin` | `localhost/superadmin/*` | ❌ Sin menú lateral | **ROTO** |
| Spider | dentro de superadmin | ❌ Sin menú | **ROTO** |

## Lo que hay que reparar

### 1. Restaurar menú lateral en superadmin

El layout de superadmin (`resources/views/layouts/superadmin.blade.php` o equivalente) debe tener su propio menú lateral, **separado** del layout de central. Si durante el fix se fusionaron los layouts o uno heredó del otro incorrectamente, hay que separarlos.

El menú lateral de superadmin debe incluir como mínimo los ítems que tenía antes:
- Dashboard / Métricas
- Tenants
- Billing
- Logs / Auditoría
- **Spider** (nuevo ítem que se agrega aquí)

### 2. Mover Spider al menú de superadmin

Spider (`/superadmin/spider`) debe aparecer como ítem en el menú lateral de `superadmin`, **no** en central. Verificar que:

- La ruta `superadmin/spider` esté registrada dentro del grupo de dominio central (ya está por el fix anterior — esto es correcto para evitar el SQL error)
- El controller de Spider use el guard `super_admin`
- El layout que renderiza Spider sea el de `superadmin` (con menú lateral), no el de `central`

### 3. Central no debe mostrar Spider en su menú

El menú lateral de `central` debe mantener solo sus ítems propios. Spider no pertenece ahí.

## Archivos probables a revisar

```
resources/views/layouts/superadmin.blade.php   ← restaurar menú lateral
resources/views/layouts/central.blade.php      ← verificar que no tiene ítems de superadmin
resources/views/superadmin/spider/             ← verificar que usa layout superadmin
routes/web.php                                 ← verificar que spider está en el grupo correcto
```

## Criterio de aceptación

- `localhost/superadmin/` carga con menú lateral visible
- `localhost/superadmin/spider` carga con menú lateral de superadmin (Spider visible como ítem activo)
- `localhost/central/` carga con su menú lateral sin Spider
- El error SQL no regresa al acceder desde `localhost`
