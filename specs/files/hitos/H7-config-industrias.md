# H7 — Config Dinámica por Industria + rubros_config
**BenderAnd POS · Laravel 11 + PostgreSQL 16**
*Estado: ⬜ Pendiente · Duración estimada: 2 semanas · Requiere: H2 completo*

> Alcance: El sistema se configura por módulos atómicos (M01–M31).
> Cada empresa ve exactamente los menús y pantallas de su rubro.
> La configuración se ejecuta vía bot de WhatsApp.
> Ver `BENDERAND_CONFIG_INDUSTRIAS.md` para especificación completa de módulos.

---

## Entregables

- [ ] Tabla `rubros_config` con columna `modulos_activos TEXT[]`
- [ ] 19 presets de industria precargados como seeds
- [ ] API `GET /config/rubro` → devuelve config actual con módulos y etiquetas
- [ ] API `PUT /config/rubro` → actualiza módulos activos y etiquetas
- [ ] API `POST /config/modulos/{id}/toggle` → activar/desactivar un módulo
- [ ] API `POST /config/aplicar-preset/{industria}` → aplica preset completo
- [ ] Frontend: menú lateral se construye desde `modulos_activos` (ver `menu-builder.js` en CONFIG_INDUSTRIAS)
- [ ] Webhook bot `POST /webhook/wa/config` → recibe reconfiguración
- [ ] Tabla `bot_config` para personalidad, horario e intenciones del bot WA

---

## Migración rubros_config

```bash
php artisan make:migration create_rubros_config_table --path=database/migrations/tenant
php artisan make:migration create_bot_config_table --path=database/migrations/tenant
```

```sql
CREATE TABLE rubros_config (
    id                    BIGSERIAL PRIMARY KEY,
    industria_preset      VARCHAR(50) NOT NULL,
    industria_nombre      VARCHAR(255),
    modulos_activos       TEXT[] NOT NULL DEFAULT '{"M01"}',
    label_operario        VARCHAR(100) DEFAULT 'Vendedor',
    label_cliente         VARCHAR(100) DEFAULT 'Cliente',
    label_cajero          VARCHAR(100) DEFAULT 'Cajero',
    label_producto        VARCHAR(100) DEFAULT 'Producto',
    label_recurso         VARCHAR(100) DEFAULT 'Recurso',
    label_nota            VARCHAR(100),
    documento_default     VARCHAR(50) DEFAULT 'boleta',
    requiere_rut          BOOLEAN DEFAULT FALSE,
    boleta_sin_detalle    BOOLEAN DEFAULT FALSE,
    tiene_stock_fisico    BOOLEAN DEFAULT TRUE,
    tiene_renta           BOOLEAN DEFAULT FALSE,
    tiene_renta_hora      BOOLEAN DEFAULT FALSE,
    tiene_servicios       BOOLEAN DEFAULT FALSE,
    tiene_agenda          BOOLEAN DEFAULT FALSE,
    tiene_delivery        BOOLEAN DEFAULT FALSE,
    tiene_comandas        BOOLEAN DEFAULT FALSE,
    tiene_ot              BOOLEAN DEFAULT FALSE,
    tiene_membresias      BOOLEAN DEFAULT FALSE,
    tiene_notas_cifradas  BOOLEAN DEFAULT FALSE,
    tiene_fiado           BOOLEAN DEFAULT FALSE,
    tiene_fraccionado     BOOLEAN DEFAULT FALSE,
    tiene_descuento_vol   BOOLEAN DEFAULT FALSE,
    recurso_estados       TEXT[] DEFAULT ARRAY['libre','ocupado'],
    alerta_vencimiento_min INT DEFAULT 15,
    log_acceso_notas      BOOLEAN DEFAULT FALSE,
    cifrado_notas         BOOLEAN DEFAULT FALSE,
    accent_color          VARCHAR(7) DEFAULT '#3b82f6',
    recurso_historial     VARCHAR(50),
    created_at            TIMESTAMPTZ DEFAULT NOW(),
    updated_at            TIMESTAMPTZ DEFAULT NOW()
);
```

---

## Seed presets de industria

```bash
php artisan make:seeder IndustriaPresetsSeeder
```

```php
// Los 19 presets definidos en BENDERAND_CONFIG_INDUSTRIAS.md
// Ej: retail → ['M01','M02','M03','M04','M11','M12','M17','M18','M20','M24','M25']
// Ej: medico → ['M01','M07','M08','M09','M10','M20','M21','M22','M23']
```

---

## Checklist H7

- [ ] `GET /config/rubro` devuelve `modulos_activos` y todas las etiquetas
- [ ] Frontend filtra menú admin y POS según módulos activos (sin hardcode por industria)
- [ ] Un tenant con preset `medico` no ve en su menú: Compras, Inventario, Comandas, Delivery
- [ ] Un tenant con preset `motel` no ve en su menú: RRHH, Agenda, Reclutamiento
- [ ] Webhook bot puede cambiar config de un tenant sin redeployar
- [ ] `bot_config` guarda horario de atención, intenciones activas y FAQ
