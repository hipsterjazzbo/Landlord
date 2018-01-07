<?php

namespace HipsterJazzbo\Landlord\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelNotFoundForTenantException extends ModelNotFoundException implements TenantExceptionInterface
{
    /**
     * @param string    $model
     * @param int|array $ids
     *
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        $this->message = "No query results for model [{$model}] when scoped by tenant.";

        return $this;
    }
}
