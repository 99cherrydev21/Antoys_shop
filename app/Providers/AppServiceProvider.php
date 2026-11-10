<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Definición de permisos (Gates)
        Gate::define('manage-products', fn (User $user) => $user->isJefe());
        Gate::define('manage-users', fn (User $user) => $user->isJefe());
        Gate::define('view-reports', fn (User $user) => $user->isJefe() || $user->isInventarista());

        // Acceso al panel de mantenimiento
        Gate::define(
            'manage-maintenance',
            fn (User $user) => $user->isJefe() || $user->isTecnico()
        );

        // Autorizar solicitudes
        Gate::define(
            'approve-maintenance',
            fn (User $user) => $user->isJefe()
        );

        // Generar respaldos y ejecutar mantenimiento
        Gate::define(
            'execute-maintenance',
            fn (User $user) => $user->isTecnico()
        );
    }
}
