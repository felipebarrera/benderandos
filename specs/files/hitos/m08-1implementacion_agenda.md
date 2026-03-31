# M08 Agenda / Citas Implementation Plan

This plan outlines the steps to implement the Agenda and Appointments module (M08) for the ERP.

## Proposed Changes

### Database Schema

#### [NEW] [create_agenda_recursos_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_000001_create_agenda_recursos_table.php)
#### [NEW] [create_agenda_horarios_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_000002_create_agenda_horarios_table.php)
#### [NEW] [create_agenda_bloqueos_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_000003_create_agenda_bloqueos_table.php)
#### [NEW] [create_agenda_config_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_000004_create_agenda_config_table.php)
#### [NEW] [create_agenda_servicios_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_000005_create_agenda_servicios_table.php)
#### [NEW] [create_agenda_citas_table.php](file:///home/master/trabajo/proyectos/src/benderandos/database/migrations/tenant/2026_03_26_000006_create_agenda_citas_table.php)

### Backend Models & Service

#### [NEW] [AgendaRecurso.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaRecurso.php)
#### [NEW] [AgendaHorario.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaHorario.php)
#### [NEW] [AgendaBloqueo.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaBloqueo.php)
#### [NEW] [AgendaConfig.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaConfig.php)
#### [NEW] [AgendaServicio.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaServicio.php)
#### [NEW] [AgendaCita.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Models/Tenant/AgendaCita.php)
#### [NEW] [AgendaService.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Services/AgendaService.php)

### Controllers & Routes

#### [NEW] [AgendaController.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Http/Controllers/Tenant/AgendaController.php)
#### [MODIFY] [tenant.php](file:///home/master/trabajo/proyectos/src/benderandos/routes/tenant.php)
- Add M08 routes under `auth:sanctum` and public routes for landing.

### Notifications & Background Jobs

#### [NEW] [RecordatorioCitaJob.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Jobs/RecordatorioCitaJob.php)
#### [NEW] [NotificarNuevaCitaJob.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Jobs/NotificarNuevaCitaJob.php)
#### [MODIFY] [app.php](file:///home/master/trabajo/proyectos/src/benderandos/bootstrap/app.php)
- Register the scheduled job for hourly reminders.

### Frontend Views

#### [NEW] [pos/agenda.blade.php](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/pos/agenda.blade.php)
#### [NEW] [admin/agenda/index.blade.php](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/admin/agenda/index.blade.php)
#### [NEW] [public/agenda.blade.php](file:///home/master/trabajo/proyectos/src/benderandos/resources/views/public/agenda.blade.php)

### Demo & Configuration

#### [NEW] [AgendaDemoSeeder.php](file:///home/master/trabajo/proyectos/src/benderandos/database/seeders/AgendaDemoSeeder.php)
#### [MODIFY] [ConfigRubroService.php](file:///home/master/trabajo/proyectos/src/benderandos/app/Services/ConfigRubroService.php)
- Add automatic configuration when M08 is activated.

## Verification Plan

### Automated Tests
- Verification of slots availability endpoint.
- Creation of appointments with conflict validation.
- State machine transition validation.
- Public landing booking flow.

### Manual Verification
- Deploying to `demo-medico` and `demo-padel`.
- Checking visual colors and columns in POS view.
- Testing WA notification simulation (job execution).
