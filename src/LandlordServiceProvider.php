<?php

namespace AuraIsHere\Landlord;

use AuraIsHere\Landlord\Facades\LandlordFacade;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
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
        $this->publishes([
            realpath(__DIR__ . '/../config/landlord.php') => config_path('landlord.php')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Landlord::class, function () {
            return new Landlord();
        });

        // Define alias 'Landlord'
        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();

            $loader->alias('Landlord', LandlordFacade::class);
        });
    }
}
