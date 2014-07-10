<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelNotFoundForTenantException extends ModelNotFoundException {

	/**
	 * @param string $model
	 *
	 * @return $this
	 */
	public function setModel($model)
	{
		$this->model = $model;

		$this->message = "No query results for model [{$model}] when scoped by tenant.";

		return $this;
	}
}