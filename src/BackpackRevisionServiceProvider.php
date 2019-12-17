<?php

namespace Bozboz\BackpackRevisions;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

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
        }

        $this->loadViewsFrom(realpath(__DIR__.'/resources/views'), 'backpack-revisions');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'backpack-revisions');

        Blueprint::macro('revisionable', function () {
            $table->uuid('uuid')->index();
            $table->boolean('is_published');
            $table->boolean('is_current');
            $table->integer('user_id');
        });

        Blueprint::macro('dropRevisionable', function () {
            $table->dropColumn('uuid');
            $table->dropColumn('is_published');
            $table->dropColumn('is_current');
            $table->integer('user_id');
        });
    }
}
