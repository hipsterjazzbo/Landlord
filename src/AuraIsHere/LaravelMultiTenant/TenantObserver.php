<?php namespace AuraIsHere\LaravelMultiTenant;

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
		if (! $model->hasGlobalScope(new TenantScope)) return;

		// If there is no tenant set, bail
		if (is_null(TenantScope::getTenantId())) return;

		// Otherwise, scope the new model
		$model->{$model->getTenantColumn()} = TenantScope::getTenantId();
	}
} 