<?php

namespace Aliff\ChipIn;

use Illuminate\Support\ServiceProvider;

class ChipInServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/Config/chipin.php', 'chipin');

        $this->app->singleton(Client::class, function ($app) {
            return new Http\Client();
        });

        $this->app->singleton(Endpoints\Purchase::class, function ($app) {
            return new Endpoints\Purchase($app->make(Client::class));
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/chipin.php' => config_path('chipin.php'),
            ], 'config');
        }
    }
}
