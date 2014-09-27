<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Database\Eloquent\Model;
use AuraIsHere\LaravelMultiTenant\Facades\TenantScopeFacade;
use AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait;

class TenantObserver {

	/**
	 * Sets the tenant id automatically when creating models
	 *
	 * @param Model|TenantScopedModelTrait $model
	 */
	public function creating(Model $model)
	{
		// If the model has had the global scope removed, bail
		if (! $model->hasGlobalScope(TenantScopeFacade::getFacadeRoot())) return;

		// Otherwise, scope the new model
		foreach (TenantScopeFacade::getFacadeRoot()->getModelTenants() as $tenantColumn => $tenantId) {
			$model->{$tenantColumn} = $tenantId;
		}
	}
} 