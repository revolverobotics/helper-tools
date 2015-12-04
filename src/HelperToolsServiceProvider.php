<?php

namespace Revolverobotics\HelperTools;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class HelperToolsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHelperTools($this->app);
    }

    protected function registerHelperTools(Application $app)
    {
        $app->singleton('helpertools', function ($app) {
            return new HelperTools();
        });

        $app->alias('helpertools', HelperTools::class);
    }

    public function provides()
    {
        return [
            'helpertools',
        ];
    }
}
