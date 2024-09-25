<?php

namespace Larapay;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class LarapayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            // Publish larapay config
            __DIR__.'/../config/larapay.php' => config_path('larapay.php'),
        ], 'larapay-config');

        $this->publishes([

            // Publish atheer views
            __DIR__.'/../resources/views/vendor/atheer' => resource_path('views/vendor/atheer'),
            
            // Publish atheer lang
            __DIR__.'/../lang' => base_path('lang'),

            // Publish atheer routes
            __DIR__.'/../routes' => base_path('routes'),

        ]);

        $this->loadViewsFrom(__DIR__.'/../resources/views/vendor/larapay', 'larapay');

        $this->loadRoutes();
        $this->loadConfig();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    private function loadRoutes()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/larapay.php');
    }

    private function loadConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/larapay.php', 'larapay'
        );
    }
}
