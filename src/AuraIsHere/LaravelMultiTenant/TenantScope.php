<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ScopeInterface;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantIdNotSetException;

class TenantScope implements ScopeInterface {

	protected $enabled = true;

	protected $tenantId;
	protected $tenantColumn;

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
		if (is_null(self::getTenantId()))
		{
			if ($this->enabled) throw new TenantIdNotSetException;

			return;
		}

		/** @var \Illuminate\Database\Eloquent\Model|ScopedByTenant $model */
		$model = $builder->getModel();

		// Use whereRaw instead of where to avoid issues with bindings when removing
		$builder->whereRaw($this->getTenantWhereClause($model));
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
		return $where['type'] == 'raw' && $where['sql'] == $this->getTenantWhereClause($model);
	}

	public function getTenantColumn()
	{
		return $this->tenantColumn;
	}

	public function setTenantColumn($tenantColumn)
	{
		self::enable();

		$this->tenantColumn = $tenantColumn;
	}

	public function getTenantId()
	{
		return $this->tenantId;
	}

	public function setTenantId($tenantId)
	{
		self::enable();

		$this->tenantId = $tenantId;
	}

	public function disable()
	{
		$this->enabled = false;
	}

	public function enable()
	{
		$this->enabled = true;
	}

	/**
	 * Prepare a raw where clause. Do it this way instead of using where()
	 * to avoid issues with bindings when removing.
	 *
	 * @return string
	 */
	protected function getTenantWhereClause($model)
	{
		$tenantColumn = $model->getTable() . '.' . $this->getTenantColumn();
		$tenantId     = TenantScope::getTenantId();

		return "{$tenantColumn} = '{$tenantId}'";
	}
}