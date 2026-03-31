<?php

namespace App\Providers;

use App\Models\Tenant\RubroConfig;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Cita;
use App\Models\Tenant\NotaClinica;
use App\Policies\Tenant\CitaPolicy;
use App\Policies\Tenant\NotaClinicaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Cita::class, CitaPolicy::class);
        Gate::policy(NotaClinica::class, NotaClinicaPolicy::class);

        // Solo registrar observers dentro de contexto tenant
        if (app()->bound(\Stancl\Tenancy\Contracts\Tenant::class)) {
            \App\Models\Tenant\Usuario::observe(\App\Observers\Tenant\UsuarioAgendaObserver::class);
            \App\Models\Tenant\Producto::observe(\App\Observers\Tenant\ProductoAgendaObserver::class);
        }
        // --- Gates Hito 2: Permisos granulares ---
        Gate::define('agregar-item-venta', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin', 'cajero', 'operario']);
        });

        Gate::define('confirmar-venta', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin', 'cajero']);
        });

        Gate::define('ver-dashboard', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin']);
        });

        Gate::define('gestionar-productos', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin', 'bodega']);
        });

        Gate::define('gestionar-usuarios', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin']);
        });

        Gate::define('gestionar-clientes', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin', 'cajero']);
        });

        Gate::define('gestionar-compras', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin', 'bodega']);
        });

        Gate::define('anular-ventas', function (Usuario $user) {
            return in_array($user->rol, ['admin', 'super_admin']);
        });

        // --- Hito 7: Compartir RubroConfig con todas las vistas ---
        View::composer('tenant.*', function ($view) {
            // Guard: solo operar si la conexión está configurada e inicializada
            if (tenancy()->initialized && config('database.connections.tenant')) {
                try {
                    // Solo intentar si la tabla existe para evitar errores en migraciones
                    if (Schema::connection('tenant')->hasTable('rubros_config')) {
                        $rubroConfig = RubroConfig::first();
                        
                        // Si no existe, crear uno por defecto para no romper la UI
                        if (!$rubroConfig) {
                            $rubroConfig = RubroConfig::create([
                                'industria_preset' => 'retail',
                                'industria_nombre' => 'Retail / Abarrotes',
                                'modulos_activos' => ['M01', 'M02', 'M03', 'M04', 'M11', 'M12', 'M17', 'M18', 'M20', 'M24', 'M25', 'M32'],
                            ]);
                        }
                        
                        $view->with('rubroConfig', $rubroConfig);
                    }
                } catch (\Exception $e) {
                    // Ignorar errores de conexión/DB en etapas tempranas
                }
            }
        });
    }
}
