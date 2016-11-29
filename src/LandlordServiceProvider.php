<?php

namespace HipsterJazzbo\Landlord;

use Illuminate\Support\ServiceProvider;

class LandlordServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (function_exists('config_path')) {
            $this->publishes([
                realpath(__DIR__.'/../config/landlord.php') => config_path('landlord.php'),
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TenantManager::class, function () {
            return new TenantManager();
        });
    }
}
