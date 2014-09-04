<?php namespace AuraIsHere\LaravelMultiTenant\Traits;

use AuraIsHere\LaravelMultiTenant\TenantScope;
use Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\TenantObserver;
use AuraIsHere\LaravelMultiTenant\Facades\TenantScopeFacade;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException;

/**
 * Class TenantScopedModelTrait
 *
 * @package AuraIsHere\LaravelMultiTenant
 *
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\ScopeInterface $scope)
 * @method static void observe(object $class)
 */
trait TenantScopedModelTrait {

	public $tenantColumns = null;

	public static function bootTenantScopedModelTrait()
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
	 * Get the name of the "tenant id" column.
	 *
	 * @return string
	 */
	public function getTenantColumns()
	{
		return is_null($this->tenantColumns) ? Config::get('laravel-multi-tenant::default_tenant_columns') : $this->tenantColumns;
	}

	/**
	 * Prepare a raw where clause. Do it this way instead of using where()
	 * to avoid issues with bindings when removing.
	 *
	 * @param $tenantColumn
	 * @param $tenantId
	 *
	 * @return string
	 */
	public function getTenantWhereClause($tenantColumn, $tenantId)
	{
		return "{$this->getTable()}.{$tenantColumn} = '{$tenantId}'";
	}

	/**
	 * Override the default findOrFail method so that we can rethrow a more useful exception.
	 * Otherwise it can be very confusing why queries don't work because of tenant scoping issues.
	 *
	 * @param       $id
	 * @param array $columns
	 *
	 * @throws TenantModelNotFoundException
	 */
	public static function findOrFail($id, $columns = array('*'))
	{
		try
		{
			return parent::findOrFail($id, $columns);
		}

		catch (ModelNotFoundException $e)
		{
			throw with(new TenantModelNotFoundException())->setModel(get_called_class());
		}
	}

	/**
	 * Returns tenant scope for this model.
	 *
	 * @return \Illuminate\Database\Eloquent\ScopeInterface
	 */
	protected static function getTenantScope()
	{
		return TenantScopeFacade::getFacadeRoot();
	}
} 