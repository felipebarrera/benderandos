<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\InternalBotAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api') // Usamos middleware api para que resuelva dependencias como json response
                 ->group(base_path('routes/webhook.php'));

            Route::middleware('api')
                 ->prefix('api')
                 ->group(base_path('routes/central.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\LogUnauthorizedRequests::class);

        // Sanctum SPA: make stateful middleware available in web group
        $middleware->statefulApi();

        // Exclude tenant API-style routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'auth/*',
            'api/*',
            'webhook/*',
            'portal/login',
            'portal/logout',
            'portal/pedido',
            'portal/pedido/*',
            'central/spider/token',
        ]);

        $middleware->alias([
            'auth.bot'   => \App\Http\Middleware\InternalBotAuth::class,
            'jwt.bridge' => \App\Http\Middleware\JwtBridgeMiddleware::class,
            'module'     => \App\Http\Middleware\CheckModuleAccess::class,
        ]);
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->call(function () {
             // Procesar cada tenant para enviar recordatorios
             \App\Models\Central\Tenant::all()->runForEach(function () {
                $config = \App\Models\Tenant\AgendaConfig::first();
                if (!$config || !$config->confirmacion_wa_activa) return;

                $horas = $config->recordatorio_horas_antes ?? 24;
                $target = now()->addHours($horas);

                $citas = \App\Models\Tenant\AgendaCita::where('estado', 'confirmada')
                    ->where('recordatorio_enviado', false)
                    ->where('fecha', '<=', $target->toDateString())
                    ->get()
                    ->filter(function($cita) use ($target) {
                        $citaDateTime = \Carbon\Carbon::parse($cita->fecha->format('Y-m-d') . ' ' . $cita->hora_inicio);
                        return $citaDateTime->isPast() === false && $citaDateTime->lessThanOrEqualTo($target);
                    });

                foreach ($citas as $cita) {
                    dispatch(new \App\Jobs\RecordatorioCitaJob($cita->id));
                }
             });
        })->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();