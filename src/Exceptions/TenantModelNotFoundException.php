<?php

namespace HipsterJazzbo\Landlord\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class TenantModelNotFoundException extends ModelNotFoundException implements TenantExceptionInterface
{
    /**
     * @param string $model
     *
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        parent::setModel($model, $ids);

        $this->message = "No query results for model [{$model}] when scoped by tenant.";

        return $this;
    }
}
