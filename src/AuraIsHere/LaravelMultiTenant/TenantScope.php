<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ScopeInterface;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantIdNotSetException;

class TenantScope implements ScopeInterface {

	private static $tenantId;

	/**
	 * Apply the scope to a given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $builder
	 *
	 * @throws Exceptions\TenantIdNotSetException
	 * @return void
	 */
	public function apply(Builder $builder)
	{
		if (is_null(self::getTenantId())) throw new TenantIdNotSetException;

		/** @var \Illuminate\Database\Eloquent\Model|ScopedByTenant $model */
		$model = $builder->getModel();

		// Use whereRaw instead of where to avoid issues with bindings when removing
		$builder->whereRaw($model->getTenantWhereClause());
	}

	/**
	 * Remove the scope from the given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $builder
	 *
	 * @return void
	 */
	public function remove(Builder $builder)
	{
		/** @var \Illuminate\Database\Eloquent\Model|ScopedByTenant $model */
		$model = $builder->getModel();

		$query = $builder->getQuery();

		foreach ((array) $query->wheres as $key => $where)
		{
			// If the where clause is a tenant constraint, we will remove it from the query
			// and reset the keys on the wheres. This allows this developer to include
			// the tenant model in a relationship result set that is lazy loaded.
			if ($this->isTenantConstraint($where, $model))
			{
				unset($query->wheres[$key]);

				$query->wheres = array_values($query->wheres);

				// Just bail after this first one in case the user has manually specified
				// the same where clause for some weird reason or by some nutso coincidence.
				break;
			}
		}
	}

	/**
	 * Determine if the given where clause is a tenant constraint.
	 *
	 * @param  array          $where
	 * @param  ScopedByTenant $model
	 *
	 * @return bool
	 */
	protected function isTenantConstraint(array $where, $model)
	{
		return $where['type'] == 'raw' && $where['sql'] == $model->getTenantWhereClause();
	}

	public static function getTenantId()
	{
		return static::$tenantId;
	}

	public static function setTenantId($tenantId)
	{
		static::$tenantId = $tenantId;
	}
}