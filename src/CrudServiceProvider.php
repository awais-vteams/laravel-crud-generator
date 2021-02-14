<?php

namespace Dscheff\CrudGenerator;

use Dscheff\CrudGenerator\Commands\CrudGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * Class CrudServiceProvider.
 */
class CrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerator::class,
            ]);
        }
        $this->publishConfig();
        $this->publishPublic();
    }

    private function publishConfig()
    {
        $this->publishes([
            __DIR__.'/config/crud.php' => config_path('crud.php'),
        ], 'crud');
    }

    private function publishPublic()
    {
        $this->publishes([
            __DIR__.'/public/filters.js' => public_path('vendor/crud/js/filters.js'),
        ], 'public');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
