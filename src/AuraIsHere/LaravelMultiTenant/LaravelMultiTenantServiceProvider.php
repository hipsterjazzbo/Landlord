<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Support\ServiceProvider;

class LaravelMultiTenantServiceProvider extends ServiceProvider {

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
		$this->package('aura-is-here/laravel-multi-tenant');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register our tenant scope instance
		$this->app->bindshared('AuraIsHere\LaravelMultiTenant\TenantScope', function ($app) {
			return new TenantScope();
		});

		// Define alias 'TenantScope'
		$this->app->booting(function()
		{
		  $loader = \Illuminate\Foundation\AliasLoader::getInstance();
		  $loader->alias('TenantScope', 'AuraIsHere\LaravelMultiTenant\TenantScopeFacade');
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
