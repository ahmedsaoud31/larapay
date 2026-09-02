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
        ], ['larapay-config', 'larapay']);

        $this->publishes([
            // Publish JS assets to public/vendor/larapay/
            __DIR__.'/../resources/js' => public_path('vendor/larapay/js'),
        ], ['larapay-assets', 'larapay']);

        $this->publishes([
            // Publish larapay migrations
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['larapay-migrations', 'larapay']);

        $this->publishes([
            // Publish atheer views
            __DIR__.'/../resources/views/vendor/larapay' => resource_path('views/vendor/larapay'),
            
            // Publish atheer lang
            __DIR__.'/../lang' => base_path('lang'),

            // Publish atheer routes
            __DIR__.'/../routes' => base_path('routes'),
        ], 'larapay');

        $this->loadViewsFrom(__DIR__.'/../resources/views/vendor/larapay', 'larapay');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'larapay');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutes();
        $this->loadConfig();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('larapay', function ($app) {
            return new LarapayManager($app);
        });
    }

    private function loadRoutes(): void
    {
        \Illuminate\Support\Facades\Route::group(config('larapay.routes', [
            'prefix' => 'larapay',
            'middleware' => ['web'],
        ]), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/larapay.php');
        });
    }

    private function loadConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/larapay.php', 'larapay'
        );
    }
}
