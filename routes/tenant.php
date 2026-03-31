<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\ClientePortalController;
use App\Http\Controllers\Tenant\ProductoController;
use App\Http\Controllers\Tenant\VentaController;
use App\Http\Controllers\Tenant\ClienteController;
use App\Http\Controllers\Tenant\CompraController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\UsuarioController;
use App\Http\Controllers\Tenant\AgendaController;
use App\Http\Controllers\Tenant\AgendaUniversalController;
use App\Http\Controllers\Tenant\NotaClinicaController;
use App\Http\Controllers\Tenant\OnboardingController;
use App\Http\Controllers\Tenant\ProfesionalConfigController;
use App\Http\Controllers\Tenant\ProfesionalController;
use App\Http\Controllers\Tenant\RentaController;
use App\Http\Controllers\Tenant\WebPanelController;
use App\Http\Controllers\Tenant\PagoController;
use App\Http\Controllers\Tenant\ConfigRubroController;
use App\Http\Controllers\Tenant\ConfigBotController;


use App\Http\Controllers\Tenant\InternalBotController;

use App\Http\Controllers\Tenant\BotApiController;
use App\Http\Controllers\Tenant\SiiController;
use App\Http\Controllers\Tenant\OrdenCompraController;
use App\Http\Controllers\Tenant\DeliveryController;
use App\Http\Controllers\Tenant\RecetaController;
use App\Http\Controllers\Tenant\RrhhController;
use App\Http\Controllers\Tenant\ReclutamientoController;
use App\Http\Controllers\Tenant\MarketingController;
use App\Http\Controllers\Tenant\QrLandingController;
use App\Http\Controllers\Tenant\SaasClienteController;
use App\Http\Controllers\Tenant\SaasCobroController;
use App\Http\Controllers\Tenant\SaasDashboardController;
use App\Http\Controllers\Tenant\SaasPipelineController;
use App\Http\Controllers\Tenant\Api\PublicApiController;
use App\Http\Controllers\Tenant\Api\TokenController;
use App\Http\Controllers\Tenant\PortalPublicoController;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckTenantStatus;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    CheckTenantStatus::class,
])->group(function () {

    /* ══════════════════════════════════════════════════════════
     | WEB PANEL — Hito 7 (session-based, Blade views)
     ══════════════════════════════════════════════════════════ */

    // Auth web
    Route::redirect('/admin/login', '/auth/login/web', 301);
    Route::redirect('/login', '/auth/login/web', 301);
    Route::get('/auth/login/web', [WebPanelController::class, 'showLogin'])->name('tenant.login.web');

    
    // --- PORTAL PÚBLICO (Sin Auth) ---
    Route::get('/', [PortalPublicoController::class, 'index'])->name('public.portal.index');
    Route::get('/catalogo', [PortalPublicoController::class, 'catalogo'])->name('public.portal.catalogo');
    Route::get('/producto/{id}', [PortalPublicoController::class, 'producto'])->name('public.portal.producto');
    Route::get('/pedido/whatsapp', [PortalPublicoController::class, 'pedirPorWhatsapp'])->name('public.portal.pedido.whatsapp');

    // ── LANDING PÚBLICO AGENDA (M08) ─────────
    Route::get('/agenda', [AgendaController::class, 'landing'])->name('agenda.landing');
    Route::prefix('api/public/agenda')->group(function () {
        Route::get('/recursos',    [AgendaController::class, 'publicRecursos']);
        Route::get('/slots',       [AgendaController::class, 'publicSlots']);
        Route::post('/cita',       [AgendaController::class, 'publicCrearCita']);
    });

    Route::post('/auth/login/web', [WebPanelController::class, 'postLogin'])->name('tenant.login.web.post');
    Route::post('/web/logout',   [WebPanelController::class, 'logout'])->name('web.logout');

    // Admin panel y rutas protegidas (WEB)
    Route::middleware('auth')->group(function () {
        Route::get('/admin/dashboard', [WebPanelController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/productos', [WebPanelController::class, 'productos'])->name('admin.productos');
        Route::get('/admin/clientes',  [WebPanelController::class, 'clientes'])->name('admin.clientes');
        Route::get('/admin/compras',   [WebPanelController::class, 'compras'])->name('admin.compras');
        Route::get('/admin/usuarios',  [WebPanelController::class, 'usuarios'])->name('admin.usuarios');
        Route::get('/admin/reportes',  [WebPanelController::class, 'reportes'])->name('admin.reportes');
        Route::get('/admin/rentas',    [WebPanelController::class, 'rentas'])->name('admin.rentas');
        Route::get('/admin/config',    [WebPanelController::class, 'config'])->name('admin.config');
        Route::get('/admin/whatsapp',  function() { return view('tenant.admin.whatsapp'); })->name('admin.whatsapp');
        Route::get('/admin/sii',       function() { return view('tenant.admin.sii'); })->name('admin.sii');
        Route::get('/admin/compras-avanzadas', function() { return view('tenant.admin.compras'); })->name('admin.compras_avanzadas');
        Route::get('/admin/delivery', function() { return view('tenant.admin.delivery'); })->name('admin.delivery');
        Route::get('/admin/recetas',  function() { return view('tenant.admin.recetas'); })->name('admin.recetas');
        Route::get('/admin/rrhh',     function() { return view('tenant.admin.rrhh'); })->name('admin.rrhh');
        Route::get('/admin/reclutamiento', function() { return view('tenant.admin.reclutamiento'); })->name('admin.reclutamiento');
        Route::get('/admin/marketing', function() { return view('tenant.admin.marketing'); })->name('admin.marketing');
        Route::get('/admin/saas/dashboard', function() { return view('tenant.admin.saas_dashboard'); })->name('admin.saas.dashboard');
        Route::get('/pos/saas/tenants', function() { return view('tenant.pos.saas_tenants'); })->name('pos.saas.tenants');
        Route::get('/pos/saas/pipeline', function() { return view('tenant.pos.saas_pipeline'); })->name('pos.saas.pipeline');

        // POS
        Route::get('/pos',             [WebPanelController::class, 'pos'])->name('pos.index');

        // ── M08 AGENDA ────────────────────────────────────────────────────
        Route::middleware(['module:M08'])->group(function () {
            Route::get('/pos/agenda',   [AgendaController::class, 'posIndex'])->name('pos.agenda');
            Route::get('/admin/agenda', [AgendaController::class, 'adminIndex'])->name('admin.agenda');
            Route::get('/admin/agenda/citas', [AgendaController::class, 'adminCitasIndex'])->name('admin.agenda.citas');

            Route::prefix('api/agenda')->group(function () {
                Route::get('/dia',                              [AgendaController::class, 'getDia']);
                Route::get('/calendario',                       [AgendaController::class, 'calendario']);
                Route::get('/slots',                            [AgendaController::class, 'getSlots']);
                Route::get('/disponibilidad',                   [AgendaController::class, 'disponibilidad']);
                Route::post('/citas',                           [AgendaController::class, 'crearCita']);
                Route::get('/citas/{id}',                       [AgendaController::class, 'show']);
                Route::put('/citas/{id}',                       [AgendaController::class, 'actualizarCita']);
                Route::put('/citas/{id}/estado',                [AgendaController::class, 'cambiarEstado']);
                Route::delete('/citas/{id}',                    [AgendaController::class, 'cancelarCita']);
                Route::post('/citas/{id}/iniciar-consulta',     [AgendaController::class, 'iniciarConsulta']);
                Route::post('/citas/{id}/completar',            [AgendaController::class, 'completarCita']);
                
                Route::get('/recursos',                         [AgendaController::class, 'getRecursos']);
                Route::post('/recursos',                        [AgendaController::class, 'crearRecurso']);
                Route::delete('/recursos/{id}',                 [AgendaController::class, 'eliminarRecurso']);
                Route::put('/recursos/{id}/horarios',           [AgendaController::class, 'actualizarHorarios']);
                Route::post('/recursos/{id}/servicios',         [AgendaController::class, 'crearServicio']);
                Route::delete('/servicios/{id}',                [AgendaController::class, 'eliminarServicio']);
                
                Route::get('/config',                           [AgendaController::class, 'getConfig']);
                Route::put('/config',                           [AgendaController::class, 'updateConfig']);
                Route::get('/paciente/{clienteId}/historial',   [AgendaController::class, 'historialPaciente']);

                // ── PERSONAL (Operario) ──
                Route::get('/mi/recurso',        [AgendaController::class, 'miRecurso']);
                Route::get('/mi/dia',            [AgendaController::class, 'miDia']);
                Route::get('/mi/semana',         [AgendaController::class, 'miSemana']);
                Route::put('/mi/horarios',       [AgendaController::class, 'misHorarios']);
                Route::post('/mi/bloqueo',       [AgendaController::class, 'crearBloqueo']);
                Route::delete('/mi/bloqueo/{id}',[AgendaController::class, 'eliminarBloqueo']);
                Route::get('/mi/citas',          [AgendaController::class, 'misCitas']);
                Route::put('/mi/citas/{id}/estado', [AgendaController::class, 'cambiarEstadoMia']);
                Route::put('/mi/citas/{id}/notas',  [AgendaController::class, 'actualizarNotasMia']);
            });

            // Vista Blade personal
            Route::get('/pos/mi-agenda', [AgendaController::class, 'miAgendaIndex'])->name('pos.mi-agenda');

            // ── PROFESIONAL (Split de /operario) ──
            Route::get('/profesional', [WebPanelController::class, 'profesional'])->name('profesional');
            Route::get('/recepcion', [WebPanelController::class, 'recepcionIndex'])->name('recepcion');
            Route::prefix('api/profesional')->middleware('module:M08')->group(function () {
                Route::get('/estadisticas',                 [ProfesionalController::class, 'estadisticas']);
                Route::get('/pacientes',                    [ProfesionalController::class, 'pacientes']);
                Route::get('/pacientes/{id}',               [ProfesionalController::class, 'paciente']);
                Route::get('/pacientes/{id}/historial',     [ProfesionalController::class, 'historialPaciente']);
                Route::post('/pacientes/{id}/note',         [ProfesionalController::class, 'agregarNota']);
                Route::get('/pacientes/{id}/seguimiento',   [ProfesionalController::class, 'seguimientoPaciente']);
                Route::post('/pacientes/{id}/seguimiento',  [ProfesionalController::class, 'crearSeguimiento']);
                Route::put('/seguimiento/{id}',             [ProfesionalController::class, 'actualizarSeguimiento']);
            });

            // Config de profesionales — solo admin/super_admin
            Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
                Route::get('/profesionales-config',         [ProfesionalConfigController::class, 'index']);
                Route::post('/profesionales-config',        [ProfesionalConfigController::class, 'store']);
                Route::get('/profesionales-config/{id}',    [ProfesionalConfigController::class, 'show']);
            });
        });

        Route::get('/pos/historial',   [WebPanelController::class, 'posHistorial'])->name('pos.historial');
        Route::get('/rentas',          [WebPanelController::class, 'rentas'])->name('rentas.index');
        Route::get('/operario',        [WebPanelController::class, 'operario'])->name('operario.index');
    });

    /* ══════════════════════════════════════════════════════════
     | API ROUTES (Sanctum token-based)
     ══════════════════════════════════════════════════════════ */

    // --- PORTAL CLIENTE (Públicas) ---
    Route::group(['prefix' => 'portal'], function () {
        Route::get('/login', [ClientePortalController::class, 'showLogin'])->name('portal.login');
        Route::post('/login', [ClientePortalController::class, 'loginWeb'])->name('portal.login.submit');
        Route::post('/logout', [ClientePortalController::class, 'logoutWeb'])->name('portal.logout');
    });

    // Módulo Marketing QR - Landing de Escaneo (Público)
    Route::get('/qr/{uuid}', [QrLandingController::class, 'scan'])->name('qr.scan');

    // --- Auth (público) ---
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/api/login', [AuthController::class, 'login']); // SPIDER QA FIX

    // --- H8: JWT BRIDGE FOR WHATSAPP BOT (Node.js) ---
    Route::group(['prefix' => 'api/bot', 'middleware' => 'jwt.bridge'], function () {
        Route::get('/portal-data',            [BotApiController::class, 'portalData']);
        Route::get('/stock/{sku}',            [BotApiController::class, 'stock']);

        Route::get('/precio/{sku}',           [BotApiController::class, 'precio']);
        Route::get('/cliente/{telefono}',     [BotApiController::class, 'cliente']);
        Route::get('/agenda/disponibilidad',  [BotApiController::class, 'disponibilidad']);
        Route::post('/pedido',                [BotApiController::class, 'crearPedido']);
        Route::get('/pedido/{id}/estado',     [BotApiController::class, 'estadoPedido']);
    });


    // --- Rutas protegidas ---
    Route::middleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, 'auth:sanctum', 'module'])->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard & Docs (solo admin)
        Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
            Route::get('/api/dashboard', [DashboardController::class, 'index']);
            Route::view('/admin/api-docs', 'tenant.admin.api_docs');
        });

        // Productos
        Route::get('/api/productos', [ProductoController::class, 'index']);
        Route::get('/api/productos/buscar', [ProductoController::class, 'buscar']);
        Route::get('/api/productos/{id}', [ProductoController::class, 'show']);
        Route::middleware(CheckRole::class . ':admin,super_admin,bodega')->group(function () {
            Route::post('/api/productos', [ProductoController::class, 'store']);
            Route::put('/api/productos/{id}', [ProductoController::class, 'update']);
            Route::post('/api/productos/{id}/ajuste-stock', [ProductoController::class, 'ajusteStock']);
        });

        // ── UNIVERSAL / ONBOARDING ──
        Route::prefix('api/universal')->group(function () {
            // Notas Clínicas — Solo médicos
            Route::post('/notas',                    [NotaClinicaController::class, 'store']);
            Route::get('/notas/{id}',                [NotaClinicaController::class, 'show']);

            // Configuración Onboarding
            Route::get('/onboarding/progress',       [OnboardingController::class, 'progress']);
            Route::post('/onboarding/step/{id}',     [OnboardingController::class, 'completeStep']);
            Route::post('/onboarding/step/{id}/saltar', [OnboardingController::class, 'saltarStep']);
        });

        // Clientes
        Route::get('/api/clientes', [ClienteController::class, 'index']);
        Route::get('/api/clientes/{id}', [ClienteController::class, 'show']);
        Route::middleware(CheckRole::class . ':admin,super_admin,cajero')->group(function () {
            Route::post('/api/clientes', [ClienteController::class, 'store']);
            Route::put('/api/clientes/{id}', [ClienteController::class, 'update']);
        });

        // Ventas
        Route::get('/api/ventas', [VentaController::class, 'index']);
        Route::get('/api/ventas/por-cliente', [VentaController::class, 'porCliente']);
        Route::get('/api/ventas/{id}', [VentaController::class, 'show']);
        Route::post('/api/ventas', [VentaController::class, 'store']);
        Route::post('/api/ventas/{id}/items', [VentaController::class, 'agregarItem']);
        Route::delete('/api/ventas/{ventaId}/items/{itemId}', [VentaController::class, 'quitarItem']);

        // Solo cajero/admin pueden tomar venta y confirmar
        Route::middleware(CheckRole::class . ':admin,super_admin,cajero')->group(function () {
            Route::put('/api/ventas/{id}/estado', [VentaController::class, 'tomarVenta']);
            Route::post('/api/ventas/{id}/confirmar', [VentaController::class, 'confirmar']);
        });

        Route::middleware('can:anular-ventas')->group(function () {
            Route::post('/api/ventas/{id}/anular', [VentaController::class, 'anular']);
        });

        // Rentas
        Route::get('/api/rentas/panel', [RentaController::class, 'panel']);
        Route::post('/api/rentas/{id}/extender', [RentaController::class, 'extender']);
        Route::post('/api/rentas/{id}/devolver', [RentaController::class, 'devolver']);

        // Compras (solo admin/bodega)
        Route::middleware(CheckRole::class . ':admin,super_admin,bodega')->group(function () {
            // Dashboard y alertas
            Route::get('/api/compras/dashboard', [OrdenCompraController::class, 'dashboard']);
            Route::get('/api/compras/alertas-stock', [OrdenCompraController::class, 'alertasStock']);
            
            Route::get('/api/compras', [CompraController::class, 'index']);
            Route::post('/api/compras', [CompraController::class, 'store']);
            Route::get('/api/compras/{id}', [CompraController::class, 'show']);
        });

        // --- H10: COMPRAS AVANZADAS Y PROVEEDORES ---
        Route::middleware(CheckRole::class . ':admin,super_admin,bodega')->group(function () {
            // Proveedores
            Route::get('/api/proveedores', [OrdenCompraController::class, 'proveedoresIndex']);
            Route::get('/api/proveedores/{id}', [OrdenCompraController::class, 'proveedorShow']);
            Route::post('/api/proveedores', [OrdenCompraController::class, 'proveedorStore']);
            Route::put('/api/proveedores/{id}', [OrdenCompraController::class, 'proveedorUpdate']);
            Route::post('/api/proveedores/{id}/productos', [OrdenCompraController::class, 'vincularProducto']);

            // Órdenes de Compra
            Route::get('/api/ordenes-compra', [OrdenCompraController::class, 'ocIndex']);
            Route::get('/api/ordenes-compra/{id}', [OrdenCompraController::class, 'ocShow']);
            Route::post('/api/ordenes-compra', [OrdenCompraController::class, 'ocStore']);
            Route::post('/api/ordenes-compra/{id}/autorizar', [OrdenCompraController::class, 'ocAutorizar']);
            Route::post('/api/ordenes-compra/{id}/enviar', [OrdenCompraController::class, 'ocEnviar']);
            Route::post('/api/ordenes-compra/{id}/anular', [OrdenCompraController::class, 'ocAnular']);
            Route::post('/api/ordenes-compra/{id}/recepcion', [OrdenCompraController::class, 'registrarRecepcion']);


        });

        // --- H11: DELIVERY Y LOGÍSTICA ---
        // Tracking público (sin auth)
        Route::get('/tracking/{uuid}', [DeliveryController::class, 'trackingPublico']);
        // Endpoint móvil para repartidores (sin auth compleja, usa UUID)
        Route::put('/delivery/repartidor/{uuid}/estado', [DeliveryController::class, 'actualizarEstadoMovil']);

        Route::middleware(CheckRole::class . ':admin,super_admin,bodega')->group(function () {
            Route::get('/api/delivery/dashboard', [DeliveryController::class, 'dashboard']);
            Route::get('/api/delivery/entregas', [DeliveryController::class, 'index']);
            Route::get('/api/delivery/entregas/{id}', [DeliveryController::class, 'show']);
            Route::post('/api/delivery/entregas/{id}/asignar', [DeliveryController::class, 'asignar']);
            Route::post('/api/delivery/entregas/{id}/estado', [DeliveryController::class, 'cambiarEstado']);

            // Repartidores
            Route::get('/api/delivery/repartidores', [DeliveryController::class, 'repartidoresIndex']);
            Route::post('/api/delivery/repartidores', [DeliveryController::class, 'repartidorStore']);
            Route::put('/api/delivery/repartidores/{id}', [DeliveryController::class, 'repartidorUpdate']);

            // Zonas de envío
            Route::get('/api/delivery/zonas', [DeliveryController::class, 'zonasIndex']);
            Route::post('/api/delivery/zonas', [DeliveryController::class, 'zonaStore']);
            Route::put('/api/delivery/zonas/{id}', [DeliveryController::class, 'zonaUpdate']);
        });

        // --- H12: RECETAS E INGREDIENTES ---
        Route::middleware(CheckRole::class . ':admin,super_admin,bodega')->group(function () {
            Route::get('/api/recetas/dashboard', [RecetaController::class, 'dashboard']);
            Route::get('/api/recetas', [RecetaController::class, 'index']);
            Route::get('/api/recetas/{id}', [RecetaController::class, 'show']);
            Route::post('/api/recetas', [RecetaController::class, 'store']);
            Route::put('/api/recetas/{id}', [RecetaController::class, 'update']);
            Route::post('/api/recetas/{id}/recalcular', [RecetaController::class, 'recalcularCostos']);
            Route::post('/api/recetas/{id}/verificar-stock', [RecetaController::class, 'verificarStock']);
            Route::post('/api/recetas/{id}/producir', [RecetaController::class, 'producir']);
            Route::get('/api/producciones', [RecetaController::class, 'historialProducciones']);
            Route::get('/api/recetas-reporte/costos', [RecetaController::class, 'reporteCostos']);
        });

        // --- H13: RRHH ---
        Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
            Route::get('/api/rrhh/dashboard', [RrhhController::class, 'dashboard']);

            // Empleados
            Route::get('/api/rrhh/empleados', [RrhhController::class, 'empleadosIndex']);
            Route::post('/api/rrhh/empleados', [RrhhController::class, 'empleadoStore']);
            Route::put('/api/rrhh/empleados/{id}', [RrhhController::class, 'empleadoUpdate']);

            // Asistencia
            Route::post('/api/rrhh/asistencia/entrada', [RrhhController::class, 'marcarEntrada']);
            Route::post('/api/rrhh/asistencia/salida', [RrhhController::class, 'marcarSalida']);
            Route::get('/api/rrhh/asistencia/hoy', [RrhhController::class, 'asistenciaHoy']);

            // Vacaciones
            Route::get('/api/rrhh/vacaciones', [RrhhController::class, 'vacacionesIndex']);
            Route::post('/api/rrhh/vacaciones', [RrhhController::class, 'solicitarVacacion']);
            Route::post('/api/rrhh/vacaciones/{id}/resolver', [RrhhController::class, 'resolverVacacion']);

            // Permisos
            Route::post('/api/rrhh/permisos', [RrhhController::class, 'solicitarPermiso']);
            Route::post('/api/rrhh/permisos/{id}/resolver', [RrhhController::class, 'resolverPermiso']);

            // Liquidaciones
            Route::get('/api/rrhh/liquidaciones', [RrhhController::class, 'liquidacionesIndex']);
            Route::post('/api/rrhh/liquidaciones/generar', [RrhhController::class, 'generarLiquidacion']);
            Route::post('/api/rrhh/liquidaciones/masivo', [RrhhController::class, 'generarMasivo']);
        });

        // --- H14: RECLUTAMIENTO ---
        // Endpoints públicos
        Route::get('/api/empleo/ofertas', [ReclutamientoController::class, 'publicOfertas']);
        Route::get('/api/empleo/ofertas/{slug}', [ReclutamientoController::class, 'publicOfertaBySlug']);
        Route::post('/api/empleo/ofertas/{slug}/postular', [ReclutamientoController::class, 'postular']);

        // Endpoints admin
        Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
            Route::get('/api/reclutamiento/dashboard', [ReclutamientoController::class, 'dashboard']);
            Route::get('/api/reclutamiento/ofertas', [ReclutamientoController::class, 'ofertasIndex']);
            Route::post('/api/reclutamiento/ofertas', [ReclutamientoController::class, 'ofertaStore']);
            Route::put('/api/reclutamiento/ofertas/{id}', [ReclutamientoController::class, 'ofertaUpdate']);
            Route::get('/api/reclutamiento/postulaciones', [ReclutamientoController::class, 'postulacionesIndex']);
            Route::get('/api/reclutamiento/postulaciones/{id}', [ReclutamientoController::class, 'postulacionShow']);
            Route::post('/api/reclutamiento/postulaciones/{id}/mover', [ReclutamientoController::class, 'moverPipeline']);
            Route::post('/api/reclutamiento/postulaciones/{id}/entrevista', [ReclutamientoController::class, 'programarEntrevista']);
        });

        // --- H15: MARKETING QR ---
        Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
            Route::get('/api/marketing/dashboard', [MarketingController::class, 'dashboard']);
            Route::get('/api/marketing/campanas', [MarketingController::class, 'campanasIndex']);
            Route::post('/api/marketing/campanas', [MarketingController::class, 'campanaStore']);
            Route::get('/api/marketing/campanas/{id}', [MarketingController::class, 'campanaShow']);
            Route::post('/api/marketing/campanas/{id}/qrs', [MarketingController::class, 'generarQr']);
            Route::get('/api/marketing/escaneos', [MarketingController::class, 'metricasEscaneos']);
        });

        // --- H16: MÓDULO SAAS BILING / CRM ---
        Route::middleware(CheckRole::class . ':admin,super_admin,ejecutivo')->group(function () {
            // Dashboard
            Route::get('/api/saas/dashboard', [SaasDashboardController::class, 'index']);
            Route::post('/api/saas/generar-snapshot', [SaasDashboardController::class, 'generarSnapshot']);
            
            // CRM Clientes / Tenants
            Route::apiResource('api/saas/clientes', SaasClienteController::class)->except(['destroy']);
            
            // Cobros (Billing)
            Route::get('/api/saas/cobros', [SaasCobroController::class, 'index']);
            Route::post('/api/saas/cobros/generar-mes', [SaasCobroController::class, 'generarFacturacionMes']);
            Route::post('/api/saas/cobros/vencimientos', [SaasCobroController::class, 'procesarVencimientos']);
            Route::post('/api/saas/cobros/{id}/pago', [SaasCobroController::class, 'registrarPago']);
            
            // Pipeline de Ventas
            Route::get('/api/saas/pipeline', [SaasPipelineController::class, 'index']);
            Route::post('/api/saas/pipeline', [SaasPipelineController::class, 'store']);
            Route::put('/api/saas/pipeline/{id}/etapa', [SaasPipelineController::class, 'moverEtapa']);
            Route::post('/api/saas/pipeline/{id}/demo', [SaasPipelineController::class, 'agendarDemo']);
            Route::post('/api/saas/pipeline/{id}/actividad', [SaasPipelineController::class, 'registrarActividad']);
        });

        // Gestión de Usuarios (solo admin)
        Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
            Route::get('/api/usuarios', [UsuarioController::class, 'index']);
            Route::get('/api/usuarios/{id}', [UsuarioController::class, 'show']);
            Route::post('/api/usuarios', [UsuarioController::class, 'store']);
            Route::put('/api/usuarios/{id}', [UsuarioController::class, 'update']);
            Route::get('/api/roles', [UsuarioController::class, 'roles']);
        });

        // Configuración de Rubro / Industrias / Plan de Módulos (Hito 19)
        Route::group(['prefix' => 'api/config'], function () {
            // General rubro config
            Route::get('/rubro', [ConfigRubroController::class, 'index']);
            
            // Mi Plan y Módulos
            Route::get('/mi-plan', [\App\Http\Controllers\Tenant\Api\MiPlanController::class, 'index']);
            Route::get('/modulos-disponibles', [\App\Http\Controllers\Tenant\Api\MiPlanController::class, 'disponibles']);
            
            Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
                // Rubro Update
                Route::put('/rubro', [ConfigRubroController::class, 'update']);
                Route::post('/modulos-rubro/{id}/toggle', [ConfigRubroController::class, 'toggleModulo']);
                Route::post('/aplicar-preset/{industria}', [ConfigRubroController::class, 'aplicarPreset']);
                
                // Portal Público
                Route::put('/portal', [ConfigRubroController::class, 'updatePortal']);

                
                // Activar/Desactivar Módulos con costo
                Route::get('/modulos/{id}/preview', [\App\Http\Controllers\Tenant\Api\MiPlanController::class, 'preview']);
                Route::post('/modulos/{id}/activar', [\App\Http\Controllers\Tenant\Api\MiPlanController::class, 'activar']);
                Route::post('/modulos/{id}/desactivar', [\App\Http\Controllers\Tenant\Api\MiPlanController::class, 'desactivar']);
            });
        });

        // Configuración de Bot y Webhooks
        Route::group(['prefix' => 'api/bot'], function () {
            Route::get('/config',           [ConfigBotController::class, 'getBotConfig']);
            Route::get('/logs',             [ConfigBotController::class, 'getLogs']);
            Route::get('/test-connection',  [ConfigBotController::class, 'testConnection']);
            Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
                Route::put('/config', [ConfigBotController::class, 'updateBotConfig']);
            });
        });

        // Webhook (Público o con token específico)
        Route::post('/webhook/wa/config', [ConfigBotController::class, 'webhookConfig']);

        // --- FACTURACIÓN SII / LibreDTE ---
        Route::group(['prefix' => 'api/sii'], function () {
            Route::get('/dashboard', [SiiController::class, 'dashboard']);
            Route::get('/dtes', [SiiController::class, 'index']);
            Route::get('/dtes/{id}', [SiiController::class, 'show']);
            Route::get('/libro-ventas', [SiiController::class, 'libroVentas']);
            Route::get('/config', [SiiController::class, 'getConfig']);
            Route::middleware(CheckRole::class . ':admin,super_admin')->group(function () {
                Route::post('/emitir/{ventaId}', [SiiController::class, 'emitir']);
                Route::post('/nota-credito/{dteId}', [SiiController::class, 'notaCredito']);
                Route::post('/consultar-estado/{dteId}', [SiiController::class, 'consultarEstado']);
                Route::put('/config', [SiiController::class, 'saveConfig']);
            });
        });

        // --- ENDPOINTS INTERNOS PARA EL BOT (Node.js) ---
        Route::group(['prefix' => 'api/internal', 'middleware' => 'auth.bot'], function () {
            Route::get('/productos/stock', [InternalBotController::class, 'getStock']);
            Route::get('/clientes/buscar', [InternalBotController::class, 'buscarCliente']);
            Route::post('/ventas/remota', [InternalBotController::class, 'crearVentaRemota']);
        });

        // --- PORTAL CLIENTE ---
        Route::group(['prefix' => 'portal'], function () {
                // Las rutas públicas de portal login ya están definidas arriba


            // Rutas protegidas para Clientes
            Route::middleware('auth:sanctum')->group(function () {
                Route::get('/catalogo', [ClientePortalController::class, 'catalogoWeb'])->name('portal.catalogo')->middleware('ability:ver-catalogo');
                Route::get('/historial', [ClientePortalController::class, 'historialWeb'])->name('portal.historial')->middleware('ability:ver-historial');
                Route::post('/pedido', [ClientePortalController::class, 'crearPedido'])->middleware('ability:crear-pedido');
                Route::get('/deudas', [ClientePortalController::class, 'deudasWeb'])->name('portal.deudas')->middleware('ability:ver-deudas');

            // Pagos
                Route::post('/pedido/{venta}/pagar', [PagoController::class, 'iniciarPago'])->name('portal.pago.iniciar');
            });
            
            // Retorno de pago (Transbank redirige aquí vía POST o GET)
            Route::match(['get', 'post'], '/pago/retorno/{venta}', [PagoController::class, 'retornoPago'])->name('portal.pago.retorno');
        });


        // --- H17: API PÚBLICA PROTEGIDA POR SANCTUM ---
        // (Ya estamos dentro de un grupo auth:sanctum)
        
        // Token Management
        Route::get('/api/user/tokens', [TokenController::class, 'index']);
        Route::post('/api/user/tokens', [TokenController::class, 'store']);
        Route::delete('/api/user/tokens/{id}', [TokenController::class, 'destroy']);

        // Public Data Endpoints
        Route::prefix('api/v1/public')->group(function () {
            Route::get('/productos', [PublicApiController::class, 'productos']);
            Route::get('/clientes', [PublicApiController::class, 'clientes']);
            Route::get('/stock/{sku}', [PublicApiController::class, 'stock']);
            Route::post('/ventas', [PublicApiController::class, 'storeVenta']);
        });

    });
});
