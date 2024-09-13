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
        ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
