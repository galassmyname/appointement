<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer tous les services de l'application.
     */
    public function register(): void
    {
        //
    }

    /**
     * Démarrer tous les services de l'application.
     */
    public function boot()
    {
        if (env('APP_ENV') == 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }
    }
}
