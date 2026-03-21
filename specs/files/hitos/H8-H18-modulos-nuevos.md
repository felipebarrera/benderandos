# H8 — Integración ERP ↔ WhatsApp Bot
**Estado: ⬜ Pendiente · Duración: 3 semanas · Requiere: H4 + H7**

Comunicación bidireccional entre Laravel (ERP) y Node.js (Bot Moteland).
El bot consulta stock/precios/clientes en tiempo real. El ERP notifica al bot de ventas y pedidos.

## Entregables

- [ ] JWT compartido entre Laravel y Node.js (mismo `JWT_SHARED_SECRET`)
- [ ] Endpoints internos ERP para el bot:
  - `GET /internal/productos/stock?q=`
  - `GET /internal/clientes/buscar?rut=&telefono=`
  - `POST /internal/ventas/remota`
  - `GET /internal/citas/disponibilidad?profesional_id=&fecha=`
- [ ] Webhook ERP → Bot: `venta_confirmada`, `pedido_listo`, `cita_confirmada`
- [ ] `erp-integration.service.js` en Node.js que llama a Laravel
- [ ] Intenciones del bot consultando catálogo real del ERP
- [ ] Dashboard admin: pestaña WhatsApp con conversaciones activas

## Checklist H8

- [ ] Cliente WA escribe "¿tienen leche?" → bot consulta stock real del ERP
- [ ] Pedido creado en WA aparece en ERP como `remota_pendiente`
- [ ] Venta confirmada en ERP → bot envía comprobante WA al cliente
- [ ] JWT del ERP válido en endpoints Node.js y viceversa

---

# H9 — SII / LibreDTE — Facturación Electrónica
**Estado: ⬜ Pendiente · Duración: 4 semanas · Requiere: H1 completo**

Emisión automática de DTE (boleta, factura, honorarios) al confirmar venta.

## Entregables

- [ ] `composer require sasco/libredte-lib-core`
- [ ] `SiiService.php`: `emitirBoleta()`, `emitirFactura()`, `emitirHonorarios()`, `emitirNotaCredito()`
- [ ] Tabla `config_sii` con certificado digital cifrado AES-256
- [ ] Tabla `dte_emitidos` con estado de seguimiento SII
- [ ] Emisión automática al confirmar venta (según `documento_default` del rubro)
- [ ] `EmitirDteJob` async — SII puede tardar 5-30 segundos
- [ ] PDF del DTE enviado por WA al cliente
- [ ] Sistema de reenvío automático si SII rechaza
- [ ] Dashboard SII: timbres disponibles, totales del día, últimos DTEs
- [ ] Libro de ventas y compras mensual (exportable CSV)

## Migraciones

```bash
php artisan make:migration create_config_sii_table --path=database/migrations/tenant
php artisan make:migration create_dte_emitidos_table --path=database/migrations/tenant
```

## Checklist H9

- [ ] Boleta emitida automáticamente al confirmar venta tipo boleta
- [ ] DTE aparece en dashboard con estado ACE/REC/REP
- [ ] PDF generado y enviable por email y WA
- [ ] Ambiente certificación para pruebas, ambiente producción para real
- [ ] Libro de ventas muestra resumen correcto del mes

---

# H10 — Compras y Proveedores
**Estado: ⬜ Pendiente · Duración: 4 semanas · Requiere: H1 completo**

Gestión completa de proveedores, órdenes de compra y recepción de mercancía.

## Entregables

- [ ] Tabla `proveedores_globales` en schema `public` (Coca-Cola, CCU, Nestlé, etc.)
- [ ] Tabla `proveedores_tenant` con condiciones negociadas por empresa
- [ ] Tabla `productos_proveedor` con precios históricos
- [ ] Tablas `ordenes_compra`, `items_orden_compra`
- [ ] Tablas `recepciones_compra`, `items_recepcion` (control calidad, lotes)
- [ ] Seed proveedores globales base (20 más comunes Chile)
- [ ] OC automática al detectar stock bajo mínimo (`StockAlertJob` diario 07:00)
- [ ] Flujo de aprobación OC: borrador → autorizada → enviada
- [ ] Recepción parcial actualiza OC a estado `parcial`
- [ ] Dashboard compras: métricas mes, alertas stock, OC recientes

## Checklist H10

- [ ] Crear OC manual seleccionando proveedor y productos
- [ ] Sistema sugiere OC cuando producto baja de `cantidad_minima`
- [ ] Recepción registra lotes y fechas de vencimiento por ítem
- [ ] Items rechazados en recepción NO incrementan stock
- [ ] Descuentos por volumen aplicados automáticamente

---

# H11 — Delivery y Logística
**Estado: ⬜ Pendiente · Duración: 3 semanas · Requiere: H1 completo**

Gestión de repartidores, asignación de entregas y tracking en tiempo real.

## Entregables

- [ ] Tablas `repartidores`, `entregas`, `tracking_entregas`
- [ ] CRUD repartidores con zonas de cobertura (JSONB)
- [ ] Al confirmar venta con `tipo_entrega=envio` → crear entrega automáticamente
- [ ] Panel asignación manual de repartidor
- [ ] Endpoint de actualización estado por repartidor (móvil-friendly, sin auth compleja)
- [ ] Broadcasting para tracking en tiempo real en dashboard admin
- [ ] Página pública de seguimiento por UUID (sin login)
- [ ] Notificación WA: asignado, en camino, entregado
- [ ] Cálculo de costo de envío por zona/distancia (configurable)

## Checklist H11

- [ ] Venta con delivery → entrega en estado `pendiente` automáticamente
- [ ] Cliente recibe WA en cada cambio de estado
- [ ] Link público de seguimiento funciona sin login
- [ ] Repartidor puede actualizar estado desde celular (URL simple)

---

# H12 — Restaurante: Recetas e Ingredientes
**Estado: ⬜ Pendiente · Duración: 3 semanas · Requiere: H10 completo**

Recetas con costeo automático y descuento de ingredientes al producir.

## Entregables

- [ ] Tablas `recetas`, `ingredientes_receta`, `producciones`, `items_produccion`
- [ ] CRUD recetas con editor de ingredientes y porcentaje de merma
- [ ] Cálculo automático costo por porción (ingredientes + merma + mano de obra)
- [ ] Verificación de stock antes de producir (alerta de faltantes)
- [ ] Producción → descuento automático de ingredientes del inventario
- [ ] Integración: producción sugiere OC para ingredientes faltantes
- [ ] Vista comandas cocina (pantalla simple, sin login complejo)
- [ ] Reporte costo real vs precio de venta por receta

## Checklist H12

- [ ] Costo se recalcula automáticamente cuando cambia el precio de un ingrediente
- [ ] Producción de N porciones descuenta ingredientes correctamente con merma
- [ ] Alerta cuando ingrediente insuficiente para producir
- [ ] Vista cocina muestra pedidos en tiempo real (Broadcasting)

---

# H13 — RRHH: Asistencia, Vacaciones, Liquidaciones
**Estado: ⬜ Pendiente · Duración: 4 semanas · Requiere: H1 completo**

Gestión completa del personal: marcación, solicitudes, liquidaciones con descuentos legales chilenos.

## Entregables

- [ ] Tablas `empleados`, `asistencias`, `vacaciones`, `permisos`, `liquidaciones`
- [ ] CRUD empleados con datos previsionales (AFP, ISAPRE, Mutual)
- [ ] Marcación asistencia desde pantalla POS o panel web
- [ ] Cálculo automático horas trabajadas, atrasos, horas extra
- [ ] Flujo solicitud → aprobación de vacaciones y permisos (notificación WA)
- [ ] Generación liquidación mensual con descuentos legales (AFP, salud, mutual, SIS, impuesto único)
- [ ] Dashboard RRHH: asistencia hoy, solicitudes pendientes
- [ ] Reportes: ausentismo, horas extra, resumen mensual

## Checklist H13

- [ ] Empleado marca entrada/salida desde POS
- [ ] Atrasos calculados automáticamente vs horario configurado
- [ ] Liquidación genera correctamente los descuentos previsionales chilenos
- [ ] Solicitud vacaciones notifica por WA al admin

---

# H14 — Reclutamiento y Talento
**Estado: ⬜ Pendiente · Duración: 2 semanas · Requiere: H13 completo*

Portal de ofertas de empleo y pipeline de candidatos.

## Entregables

- [ ] Tablas `ofertas_empleo`, `postulaciones`, `entrevistas`
- [ ] CRUD ofertas con editor completo
- [ ] Página pública de postulación por URL amigable (`empresa.benderand.cl/empleo/oferta-123`)
- [ ] Formulario postulación con subida de CV
- [ ] Pipeline candidatos: recibida → preseleccionada → entrevista → contratada
- [ ] Al contratar: datos del postulante pasan a `empleados` automáticamente
- [ ] Notificaciones WA/email a postulantes en cada etapa

## Checklist H14

- [ ] Postulante completa formulario público sin login
- [ ] Admin ve todos los postulantes por oferta con filtros y calificación
- [ ] Al contratar, ficha de empleado se pre-llena con datos del postulante

---

# H15 — Marketing QR
**Estado: ⬜ Pendiente · Duración: 2 semanas · Requiere: H1 completo**

Generación y tracking de códigos QR para campañas de descuento.

## Entregables

- [ ] Tablas `campanas_marketing`, `qr_campanas`, `escaneos_qr`
- [ ] `QrGenerator` service usando `endroid/qr-code` con logo del tenant
- [ ] Landing page pública personalizada por QR con branding del tenant
- [ ] Tipos de acción: descuento %, descuento $, 2x1, abrir WhatsApp, encuesta
- [ ] Registro de escaneos (IP, device, timestamp)
- [ ] Tracking conversiones: escaneo → compra (código aplicado en POS)
- [ ] Dashboard métricas: escaneos, conversiones, tasa, descuentos otorgados
- [ ] Descarga QR en PNG/SVG distintos tamaños

## Checklist H15

- [ ] QR generado con logo del tenant y link correcto a landing pública
- [ ] Escaneo registra datos sin login del cliente
- [ ] Cajero puede aplicar código QR en POS para descuento automático
- [ ] Métricas muestran conversiones reales vinculadas a ventas

---

# H16 — M31: Venta de Software SaaS
**Estado: ⬜ Pendiente · Duración: 4 semanas · Requiere: H8 + H9**

BenderAnd usa BenderAnd para gestionar su propio negocio SaaS.
Ver `BENDERAND_CONFIG_INDUSTRIAS.md` Parte G para especificación completa.

## Entregables

**Semana 1 — Base de datos**
- [ ] Tablas: `saas_planes`, `saas_clientes`, `saas_pipeline`, `saas_actividades`, `saas_cobros`, `saas_metricas`, `saas_demos`
- [ ] Seeder planes: Básico $39k, Pro $89k, Enterprise $189k con módulos por plan
- [ ] Seeder: tenant de BenderAnd como primer cliente

**Semana 2 — API y lógica**
- [ ] Controllers: `SaasClienteController`, `SaasPipelineController`, `SaasMetricasController`, `SaasCobrosController`
- [ ] Services: `SaasBillingService` (genera cobros + emite DTE vía SII), `SaasMetricasService` (MRR, ARR, churn, ARPU)
- [ ] Policy: ejecutivos solo ven sus prospectos; admin ve todo

**Semana 3 — Jobs automáticos**
- [ ] `GenerarCobrosRecurrentes` — 1° de cada mes 08:00
- [ ] `AlertaTrialVencimiento` — WA al prospecto día 25
- [ ] `AlertaMorosos` — WA a tenants vencidos +5 días
- [ ] `SuspenderMorosos` — suspende tenants +30 días sin pago
- [ ] `ActualizarMetricasMRR` — diario 00:30
- [ ] `SeguimientoTrialDia7` — WA de engagement día 7
- [ ] `EmitirFacturasDelMes` — DTE por cada cobro del ciclo
- [ ] Webhook `POST /webhook/wa/saas-onboarding` → prospecto WA → crea trial automático

**Semana 4 — UI**
- [ ] POS: Panel tenants (lista, alertas, búsqueda)
- [ ] POS: Pipeline kanban con avance de etapas
- [ ] POS: Ficha CRM del tenant (uso, billing, historial)
- [ ] Admin: Dashboard MRR (gráfico 12 meses, distribución plan/rubro)
- [ ] Admin: Gestión de planes y addons
- [ ] Preset `saas_provider` en `rubros_config`

## Checklist H16

- [ ] Prospecto escribe al bot → creado en `saas_pipeline` automáticamente
- [ ] Trial creado → schema tenant + preset aplicado en < 30 segundos
- [ ] Alertas WA en día 7, 25 y vencimiento del trial
- [ ] Cobro mes 1 generado automáticamente el día 1
- [ ] Factura SII emitida por cada cobro pagado
- [ ] Tenant moroso +30 días → suspendido automáticamente (API devuelve 402)
- [ ] MRR = suma `saas_cobros` activos del mes
- [ ] Churn = cancelados / activos inicio mes × 100
- [ ] Preset `saas_provider` no muestra: stock físico, delivery, comandas, timers, recetas

---

# H17 — Dashboard Ejecutivo + API Pública
**Estado: ⬜ Pendiente · Duración: 3 semanas · Requiere: todos los módulos previos**

Vista integrada de todos los módulos. API documentada para integraciones externas.

## Entregables

- [ ] Dashboard ejecutivo con KPIs cruzados (ventas + WA + RRHH + delivery)
- [ ] Widget de alertas unificado (stock, rentas, RRHH, SII, morosos)
- [ ] Centro de notificaciones en tiempo real (Broadcasting)
- [ ] API REST documentada con OpenAPI / Swagger (`/api/docs`)
- [ ] Webhooks salientes configurables (notificar a sistemas externos)
- [ ] Reportes exportables: Excel, PDF, CSV para todos los módulos
- [ ] Optimización: índices DB revisados, queries > 200ms identificadas y corregidas

---

# H18 — Testing, Seguridad y Despliegue
**Estado: ⬜ Pendiente · Duración: 2 semanas · Requiere: H17 completo**

Verificación end-to-end antes del lanzamiento general.

## Entregables

- [ ] Tests de integración por flujo principal por rubro (retail, motel, médico, restaurante)
- [ ] Tests de carga: concurrencia WhatsApp (1000 mensajes/min)
- [ ] Auditoría de seguridad: SQL injection, XSS, CSRF, tenant isolation
- [ ] Certificación SII en ambiente producción para todos los tipos de DTE
- [ ] Runbook de despliegue actualizado (VPS + HestiaCP)
- [ ] Scripts de migración para tenants existentes
- [ ] Documentación de usuario por módulo (PDF descargable desde el panel)
- [ ] Video tutoriales por rubro (graba pantalla + narración)

## Checklist global H18

- [ ] Tenant A no puede ver ni modificar datos de Tenant B bajo ninguna circunstancia
- [ ] Token expirado devuelve 401 en todos los endpoints
- [ ] Rate limiting activo: 60/min API, 5/min login
- [ ] Todas las queries usan Eloquent ORM o Query Builder con bindings (cero SQL raw interpolado)
- [ ] Notas de clientes con `cifrado_notas=true` efectivamente cifradas en DB
- [ ] Super admin puede impersonar tenant y el log queda en `audit_logs`
- [ ] Deploy a VPS con rollback documentado
