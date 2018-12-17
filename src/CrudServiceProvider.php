<?php

namespace Ibex\CrudGenerator;

use Ibex\CrudGenerator\Commands\CrudGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * Class CrudServiceProvider
 * @package Ibex\CrudGenerator
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
