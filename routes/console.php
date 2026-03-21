<?php

use App\Jobs\CheckRentasVencidas;
use App\Jobs\CheckDeudasPendientes;
use App\Jobs\CheckTrialsExpirando;
use App\Jobs\Central\ProcesarCobrosMensuales;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckRentasVencidas)->everyTenMinutes();

// Alertas Diarias a las 09:00 AM (Centralizadas por todos los Tenants en los Jobs correspondientes)
Schedule::job(new CheckDeudasPendientes)->dailyAt('09:00');
Schedule::job(new CheckTrialsExpirando)->dailyAt('09:00');

// Panel SaaS (SuperAdmin): Generación de cobros de tenants (1 AM)
Schedule::job(new ProcesarCobrosMensuales)->dailyAt('01:00');
