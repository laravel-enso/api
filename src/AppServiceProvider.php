<?php

namespace LaravelEnso\Api;

use Illuminate\Support\ServiceProvider;
use LaravelEnso\Api\Http\Middleware\ApiLogger;
use LaravelEnso\Core\Http\Middleware\VerifyActiveState;
use LaravelEnso\Localisation\Http\Middleware\SetLanguage;
use LaravelEnso\Permissions\Http\Middleware\VerifyRouteAccess;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api.php', 'enso.api');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    public function register()
    {
        $this->app['router']->aliasMiddleware('api-action-logger', ApiLogger::class);

        $this->app['router']->middlewareGroup('core-api', [
            VerifyActiveState::class,
            ApiLogger::class,
            VerifyRouteAccess::class,
            SetLanguage::class,
        ]);
    }
}
