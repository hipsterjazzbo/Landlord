<?php

namespace AuraIsHere\LaravelMultiTenant;

use AuraIsHere\LaravelMultiTenant\Facades\TenantScopeFacade;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class LaravelMultiTenantServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
        $this->setupTenantScope();
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

            $loader->alias('TenantScope', TenantScopeFacade::class);
        });
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/laravel-multi-tenant.php');

        $this->publishes([$source => config_path('laravel-multi-tenant.php')]);

        $this->mergeConfigFrom($source, 'laravel-multi-tenant');
    }

    /**
     * Setup the tenant scope instance.
     *
     * @return void
     */
    protected function setupTenantScope()
    {
        $this->app->singleton(TenantScope::class, function () {
            return new TenantScope();
        });
    }
}
