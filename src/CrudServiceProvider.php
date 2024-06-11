<?php

namespace Ibex\CrudGenerator;

use Ibex\CrudGenerator\Commands\CrudGenerator;
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

        $this->publishes([
            __DIR__.'/config/crud.php' => config_path('crud.php'),
        ], 'crud');

        $this->publishes([
            __DIR__.'/../src/stubs' => resource_path('stubs/crud/'),
        ], 'stubs-crud');
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
