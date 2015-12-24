<?php

namespace AuraIsHere\LaravelMultiTenant\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class TenantModelNotFoundException extends ModelNotFoundException
{
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
