<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ScopeInterface;

class TenantScope implements ScopeInterface {

	/**
	 * Apply the scope to a given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder $builder
	 *
	 * @return void
	 */
	public function apply(Builder $builder)
	{
		/** @var \Illuminate\Database\Eloquent\Model|ScopedByTenant $model */
		$model = $builder->getModel();

		$builder->where($model->getQualifiedTenantColumn(), '=', $model->getTenantId());
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
			if ($this->isTenantConstraint($where, $model->getQualifiedTenantColumn()))
			{
				unset($query->wheres[$key]);

				$query->wheres = array_values($query->wheres);

				// We've also got to get rid of the binding for this where clause
				// We'll do it this long way so we don't interfere with any other
				// global scopes.
				$bindings = $builder->getBindings();

				unset($bindings[$key]);

				$builder->setBindings($bindings);
			}
		}
	}

	/**
	 * Determine if the given where clause is a tenant constraint.
	 *
	 * @param  array  $where
	 * @param  string $column
	 *
	 * @return bool
	 */
	protected function isTenantConstraint(array $where, $column)
	{
		return $where['type'] == 'Basic' && $where['column'] == $column;
	}
}