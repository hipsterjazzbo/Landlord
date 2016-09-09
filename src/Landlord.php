<?php

namespace HipsterJazzbo\Landlord;

use HipsterJazzbo\Landlord\Exceptions\TenantColumnUnknownException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class Landlord implements Scope
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
     * Return tenants.
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
        }

        return false;
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
     * @param Builder                                    $builder
     * @param Model|\HipsterJazzbo\Landlord\BelongsToTenant $model
     */
    public function apply(Builder $builder, Model $model)
    {
        if (! $this->enabled) {
            return;
        }

        foreach ($this->getModelTenants($model) as $tenantColumn => $tenantId) {
            if (config('landlord.query_with_table_name')) {
                $builder->where($model->getTable() . '.' . $tenantColumn, '=', $tenantId);
            } else {
                $builder->where($tenantColumn, '=', $tenantId);
            }
        }
    }

    /**
     * @param Model|\HipsterJazzbo\Landlord\BelongsToTenant $model
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
     * @param Model|\HipsterJazzbo\Landlord\BelongsToTenant $model
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
     * Gets the Tenant ID
     *
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

    /**
     * Disables the scoping of tenants
     *
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Enables the scoping of tenants
     *
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;
    }
}
