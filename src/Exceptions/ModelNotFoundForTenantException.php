<?php

namespace HipsterJazzbo\Landlord\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelNotFoundForTenantException extends ModelNotFoundException implements TenantExceptionInterface
{
    /**
     * @param string $model
     *
     * @return $this
     */
    public function setModel($model)
    {
        parent::setModel($model);

        $this->message = "No query results for model [{$model}] when scoped by tenant.";

        return $this;
    }
}
