<?php

namespace AuraIsHere\LaravelMultiTenant;

use AuraIsHere\LaravelMultiTenant\Exceptions\TenantColumnUnknownException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * @var bool
     */
    private $enabled = true;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    private $model;

    /**
     * @var array The tenant scopes currently set
     */
    protected $tenants = [];

    /**
     * return tenants.
     *
     * @return array
     */
    public function getTenants()
    {
        return $this->tenants;
    }

    /**
     * Add $tenantColumn => $tenantId to the current tenants array.
     *
     * @param string $tenantColumn
     * @param mixed  $tenantId
     */
    public function addTenant($tenantColumn, $tenantId)
    {
        $this->enable();

        $this->tenants[$tenantColumn] = $tenantId;
    }

    /**
     * Remove $tenantColumn => $id from the current tenants array.
     *
     * @param string $tenantColumn
     *
     * @return boolean
     */
    public function removeTenant($tenantColumn)
    {
        if ($this->hasTenant($tenantColumn)) {
            unset($this->tenants[$tenantColumn]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Test whether current tenants include a given tenant.
     *
     * @param string $tenantColumn
     *
     * @return boolean
     */
    public function hasTenant($tenantColumn)
    {
        return isset($this->tenants[$tenantColumn]);
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder                             $builder
     * @param Model|Traits\TenantScopedModelTrait $model
     */
    public function apply(Builder $builder, Model $model)
    {
        if (! $this->enabled) {
            return;
        }

        foreach ($this->getModelTenants($model) as $tenantColumn => $tenantId) {
            $builder->where($model->getTable() . '.' . $tenantColumn, '=', $tenantId);
        }
    }

    /**
     * @param Model|Traits\TenantScopedModelTrait $model
     */
    public function creating(Model $model)
    {
        // If the model has had the global scope removed, bail
        if (! $model->hasGlobalScope($this)) {
            return;
        }

        // Otherwise, scope the new model
        foreach ($this->getModelTenants($model) as $tenantColumn => $tenantId) {
            $model->{$tenantColumn} = $tenantId;
        }
    }

    /**
     * Return which tenantColumn => tenantId are really in use for this model.
     *
     * @param Model|Traits\TenantScopedModelTrait $model
     *
     * @throws TenantColumnUnknownException
     *
     * @return array
     */
    public function getModelTenants(Model $model)
    {
        $modelTenantColumns = (array)$model->getTenantColumns();
        $modelTenants       = [];

        foreach ($modelTenantColumns as $tenantColumn) {
            if ($this->hasTenant($tenantColumn)) {
                $modelTenants[$tenantColumn] = $this->getTenantId($tenantColumn);
            }
        }

        return $modelTenants;
    }

    /**
     * @param $tenantColumn
     *
     * @throws TenantColumnUnknownException
     *
     * @return mixed The id of the tenant
     */
    public function getTenantId($tenantColumn)
    {
        if (! $this->hasTenant($tenantColumn)) {
            throw new TenantColumnUnknownException(
                get_class($this->model) . ': tenant column "' . $tenantColumn . '" NOT found in tenants scope "' . json_encode($this->tenants) . '"'
            );
        }

        return $this->tenants[$tenantColumn];
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function enable()
    {
        $this->enabled = true;
    }
}
