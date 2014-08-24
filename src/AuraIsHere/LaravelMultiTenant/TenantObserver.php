<?php namespace AuraIsHere\LaravelMultiTenant;

use TenantScope;
use Illuminate\Database\Eloquent\Model;

class TenantObserver {

	/**
	 * Sets the tenant id automatically when creating models
	 *
	 * @param Model|ScopedByTenant $model
	 */
	public function creating(Model $model)
	{
		// If the model has had the global scope removed, bail
		if (! $model->hasGlobalScope(TenantScopeFacade::getFacadeRoot())) return;

		// Otherwise, scope the new model
		foreach (TenantScope::getModelTenants($model) as $column => $id) {
			$model->{$column} = $id;
		}
	}
} 