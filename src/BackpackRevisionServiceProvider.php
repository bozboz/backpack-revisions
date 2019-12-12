<?php

namespace Bozboz\BackpackRevisions;

use Illuminate\Support\ServiceProvider;

class BackpackRevisionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('backpack-revisions.php'),
            ], 'config');

            /*
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'backpack-revisions');

            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/backpack-revisions'),
            ], 'views');
            */
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'backpack-revisions');
    }
}
