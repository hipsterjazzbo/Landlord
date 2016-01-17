<?php

namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class LaravelMultiTenantServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig($this->app);
        $this->setupTenantScope($this->app);
    }

    /**
     * Setup the config.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return void
     */
    protected function setupConfig(Application $app)
    {
        $source = realpath(__DIR__.'/../config/laravel-multi-tenant.php');
        $this->publishes([$source => config_path('laravel-multi-tenant.php')]);
        $this->mergeConfigFrom($source, 'laravel-multi-tenant');
    }

    /**
     * Setup the tenant scope instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return void
     */
    protected function setupTenantScope(Application $app)
    {
        $app->singleton('AuraIsHere\LaravelMultiTenant\TenantScope', function ($app) {
            return new TenantScope();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Define alias 'TenantScope'
        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();
            $loader->alias('TenantScope', 'AuraIsHere\LaravelMultiTenant\Facades\TenantScopeFacade');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
