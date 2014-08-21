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
		static::addGlobalScope(new TenantScope);

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
		return with(new static)->newQueryWithoutScope(new TenantScope);
	}

	/**
	 * Get the name of the "tenant id" column.
	 *
	 * @return string
	 */
	public function getTenantColumn()
	{
		return Config::get('laravel-multi-tenant::tenant_column');
	}

	/**
	 * Get the fully qualified "tenant id" column.
	 *
	 * @return string
	 */
	public function getQualifiedTenantColumn()
	{
		return $this->getTable() . '.' . $this->getTenantColumn();
	}

	/**
	 * Prepare a raw where clause. Do it this way instead of using where()
	 * to avoid issues with bindings when removing.
	 *
	 * @return string
	 */
	public function getTenantWhereClause()
	{
		$tenantColumn = $this->getQualifiedTenantColumn();
		$tenantId     = TenantScope::getTenantId();

		return "{$tenantColumn} = '{$tenantId}'";
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
} 