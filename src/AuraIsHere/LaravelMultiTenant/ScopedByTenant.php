<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\Exceptions\ModelNotFoundForTenantException;

/**
 * Class ScopedByTenant
 *
 * @package AuraIsHere\LaravelMultiTenant
 *
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\ScopeInterface $scope)
 * @method static void observe(object $class)
 */
trait ScopedByTenant {

	public static function bootScopedByTenant()
	{
		// Add the global scope that will handle all operations except create()
		static::addGlobalScope(static::getTenantScope());

		// Add an observer that will automatically add the tenant id when create()-ing
		static::observe(new TenantObserver);
	}

	/**
	 * Returns a new builder without the tenant scope applied.
	 *
	 *     $allUsers = User::allTenants()->get();
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public static function allTenants()
	{
		return with(new static)->newQueryWithoutScope(static::getTenantScope());
	}

	/**
	 * Override the default findOrFail method so that we can rethrow a more useful exception.
	 * Otherwise it can be very confusing why queries don't work because of tenant scoping issues.
	 *
	 * @param       $id
	 * @param array $columns
	 *
	 * @throws ModelNotFoundForTenantException
	 */
	public static function findOrFail($id, $columns = array('*'))
	{
		try
		{
			return parent::findOrFail($id, $columns);
		}

		catch (ModelNotFoundException $e)
		{
			throw with(new ModelNotFoundForTenantException())->setModel(get_called_class());
		}
	}

	/**
	 * Returns tenant scope for this model.
	 *
	 * @return Illuminate\Database\Eloquent\ScopeInterface
	 */
	protected static function getTenantScope()
	{
		return TenantScopeFacade::getFacadeRoot();
	}	
} 