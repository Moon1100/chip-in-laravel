<?php

namespace Aliff\ChipIn;

use Illuminate\Support\ServiceProvider;

class ChipInServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        // Optional: load views for success/failed pages
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'chipin');

        // Optional: publish config
        $this->publishes([
            __DIR__.'/config/chipin.php' => config_path('chipin.php'),
        ], 'chipin-config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/chipin.php', 'chipin');
    }
}
