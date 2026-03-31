# M08 Agenda — Full Rewrite Implementation Plan

The user's comprehensive spec supersedes the existing M08 implementation. This plan rewrites **all 23 existing files** to match the new schema and patterns.

## Key Schema Changes vs Current

| Aspect | Current Code | New Spec |
|---|---|---|
| Date/time | `fecha_inicio`/`fecha_fin` (TIMESTAMPTZ) | `fecha` (DATE) + `hora_inicio`/`hora_fin` (TIME) |
| Patient fields | `cliente_nombre`, `cliente_telefono` | `paciente_nombre`, `paciente_rut`, `paciente_telefono`, `paciente_email` |
| FK names | `recurso_id`, `servicio_id` | `agenda_recurso_id`, `agenda_servicio_id` |
| Servicios scope | Global (no FK to recurso) | Per-recurso (`agenda_recurso_id` FK) |
| Recursos fields | No `especialidad` | Has `especialidad`, `usuario_id` FK |
| Horarios | `duracion_slot_min` per horario | `duracion_slot_min` per horario row |
| Config | `duracion_slot_min`, `anticipo_min_min` etc | `titulo_landing`, `color_primario`, `recordatorio_horas_antes` etc |
| States | `pendiente→confirmada→llegó→en-curso→completada` | `pendiente→confirmada→en_curso→completada` (underscores) |
| Views | Tailwind + Alpine.js | Vanilla JS + existing CSS vars |

## Proposed Changes

### Migrations (DROP + RECREATE)

> [!IMPORTANT]
> Since there is no production data, all 6 migrations will be **overwritten** in place to match the new DDL exactly.

#### [MODIFY] [2026_03_26_144300_create_agenda_recursos_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_144300_create_agenda_recursos_table.php)
Add `especialidad`, `usuario_id` FK to `users`.

#### [MODIFY] [2026_03_26_144301_create_agenda_horarios_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_144301_create_agenda_horarios_table.php)
FK → `agenda_recurso_id`, add `duracion_slot_min`.

#### [MODIFY] [2026_03_26_144302_create_agenda_bloqueos_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_144302_create_agenda_bloqueos_table.php)
FK → `agenda_recurso_id`, split into `fecha_inicio` DATE + optional TIME.

#### [MODIFY] [2026_03_26_144303_create_agenda_config_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_144303_create_agenda_config_table.php)
New fields: `titulo_landing`, `descripcion_landing`, `landing_publico_activo`, `confirmacion_wa_activa`, `recordatorio_horas_antes`, `color_primario`, `logo_url`.

#### [MODIFY] [2026_03_26_144304_create_agenda_servicios_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_144304_create_agenda_servicios_table.php)
Add `agenda_recurso_id` FK. Remove `recursos_permitidos` array column.

#### [MODIFY] [2026_03_26_144305_create_agenda_citas_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_144305_create_agenda_citas_table.php)
`fecha` DATE, `hora_inicio`/`hora_fin` TIME, `paciente_*` fields, `agenda_recurso_id`/`agenda_servicio_id` FKs.

---

### Models

All 6 models rewritten with correct FKs, `$fillable`, casts, and relationships per spec.

#### [MODIFY] [AgendaRecurso.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaRecurso.php)
#### [MODIFY] [AgendaCita.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaCita.php)
#### [MODIFY] [AgendaServicio.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaServicio.php)
#### [MODIFY] [AgendaHorario.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaHorario.php)
#### [MODIFY] [AgendaBloqueo.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaBloqueo.php)
#### [MODIFY] [AgendaConfig.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaConfig.php)

---

### Service

#### [MODIFY] [AgendaService.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Services/AgendaService.php)
Complete rewrite with: `getSlotsDisponibles()`, `getAgendaDia()`, `crearCita()`, `cambiarEstado()`, `proximosSlotsDisponibles()`.

---

### Controller

#### [MODIFY] [AgendaController.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Http/Controllers/Tenant/AgendaController.php)
Complete rewrite with all methods from the spec: `posIndex`, `adminIndex`, `landing`, `getDia`, `getSlots`, `crearCita`, `cambiarEstado`, `actualizarCita`, `cancelarCita`, `sugerencia`, `iniciarConsulta`, `completarCita`, `getRecursos`, `crearRecurso`, `actualizarHorarios`, `publicSlots`, `publicCrearCita`.

---

### Routes

#### [MODIFY] [tenant.php](file:///home/master/trabajo/proyectos/src/benderandos/routes/tenant.php)
Replace the M08 route block (lines 453-491) with new route structure matching the spec.

---

### Views

#### [MODIFY] [agenda.blade.php (POS)](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/tenant/pos/agenda.blade.php)
Full rewrite: `@extends('tenant.layout')`, vanilla JS, multi-column calendar, side panel, modals for new appointment and payment.

#### [NEW] [agenda.blade.php (Public)](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/public/agenda.blade.php)
Standalone public landing page (no layout extends), 4-step wizard.

#### [MODIFY] [index.blade.php (Admin)](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/tenant/admin/agenda/index.blade.php)
Admin config panel for resources, services, and landing settings.

---

### Layout Nav

#### [MODIFY] [layout.blade.php](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/tenant/layout.blade.php)
Add M08 Agenda nav link in sidebar + mobile bottom nav.

---

### Seeder

#### [MODIFY] [AgendaDemoSeeder.php](file:///home/master/trabajo/proyectos/src/benderandos/database/seeders/Tenant/AgendaDemoSeeder.php)
Rewrite with industry-specific seeding (medico, padel, legal) per spec.

---

### Jobs

#### [MODIFY] [RecordatorioCitaJob.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Jobs/RecordatorioCitaJob.php)
Align with new model structure.

---

### Scheduler

#### [MODIFY] [app.php](file:///home/master/trabajo/proyectos/src/benderandos/bootstrap/app.php)
Update agenda reminder schedule to use new model fields.

## Verification Plan

### Manual Verification
- Verify migrations run cleanly: `php artisan tenants:migrate`
- Verify seeds run: `php artisan tenants:seed --class=AgendaDemoSeeder`
- Verify routes: `php artisan route:list --path=agenda`
- Load `/pos/agenda` as admin user and confirm multi-column view renders
- Load `/agenda` public landing and confirm booking wizard works
- Load `/admin/agenda` and confirm resource management interface
