<?php

namespace Ztech243\CrudGenerator\Providers;

use Illuminate\Support\ServiceProvider;
use Ztech243\CrudGenerator\Commands\MakeCrudApiCommand;
use Ztech243\CrudGenerator\Commands\MakeCrudBladeCommand;
use Ztech243\CrudGenerator\Services\ModelParserService;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        // $this->app->singleton(ModelParserService::class, function ($app) {
        //     return new ModelParserService();
        // });

        // $this->commands([
        //     MakeCrudApiCommand::class,
        //     MakeCrudBladeCommand::class,
        // ]);
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../stubs' => resource_path('stubs/vendor/crud-generator'),
        ]);
    }
}
