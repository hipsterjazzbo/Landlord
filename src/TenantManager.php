<?php

namespace HipsterJazzbo\Landlord;

use HipsterJazzbo\Landlord\Exceptions\TenantColumnUnknownException;
use HipsterJazzbo\Landlord\Scopes\BelongsToManyTenants;
use HipsterJazzbo\Landlord\Scopes\BelongsToOneTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TenantManager
{
    const BELONGS_TO_TENANT_TYPE_TO_ONE = 'one';
    const BELONGS_TO_TENANT_TYPE_TO_MANY = 'many';

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var Collection
     */
    protected $tenants;

    /**
     * Landlord constructor.
     */
    public function __construct()
    {
        $this->tenants = collect();
    }

    /**
     * Enable scoping by tenantColumns.
     *
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Disable scoping by tenantColumns.
     *
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Add a tenant to scope by.
     *
     * @param string|Model $tenant
     * @param mixed|null   $id
     */
    public function addTenant($tenant, $id = null)
    {
        if (func_num_args() == 1) {
            $id = $tenant->getKey();
        }

        $this->tenants->put($this->getTenantKey($tenant), $id);
    }

    /**
     * Remove a tenant so that queries are no longer scoped by it.
     *
     * @param string|Model $tenant
     */
    public function removeTenant($tenant)
    {
        $this->tenants->pull($this->getTenantKey($tenant));
    }

    /**
     * Whether a tenant is currently being scoped.
     *
     * @param string|Model $tenant
     *
     * @return bool
     */
    public function hasTenant($tenant)
    {
        return $this->tenants->has($this->getTenantKey($tenant));
    }

    /**
     * @return Collection
     */
    public function getTenants()
    {
        return $this->tenants;
    }

    /**
     * Applies applicable tenant scopes to a model.
     *
     * @param Model  $model
     * @param string $type
     */
    public function applyTenantScopes(Model $model, $type = self::BELONGS_TO_TENANT_TYPE_TO_ONE)
    {
        if (!$this->enabled) {
            return;
        }

        switch ($type) {
            case self::BELONGS_TO_TENANT_TYPE_TO_ONE:
                $this->modelTenants($model)->each(function ($tenantId, $tenantColumn) use ($model) {
                    $model->addGlobalScope(new BelongsToOneTenant($tenantColumn, $tenantId));
                });
                break;

            case self::BELONGS_TO_TENANT_TYPE_TO_MANY:
                $model->addGlobalScope(new BelongsToManyTenants($this));
                break;
        }
    }

    /**
     * Add tenant columns as needed to a new model instance before it is created.
     *
     * @param Model $model
     */
    public function newModel(Model $model)
    {
        if (!$this->enabled) {
            return;
        }

        $this->modelTenants($model)->each(function ($tenantId, $tenantColumn) use ($model) {
            if (!isset($model->{$tenantColumn})) {
                $model->setAttribute($tenantColumn, $tenantId);
            }
        });
    }

    /**
     * Get a new Eloquent Builder instance without any of the tenant scopes applied.
     *
     * @param Model $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutTenants(Model $model)
    {
        return $model->newQuery()->withoutGlobalScopes($this->tenants->keys()->toArray());
    }

    /**
     * Get the key for a tenant, either form a Model instance or a string.
     *
     * @param string|Model $tenant
     *
     * @throws TenantColumnUnknownException
     *
     * @return string
     */
    protected function getTenantKey($tenant)
    {
        if ($tenant instanceof Model) {
            $tenant = $tenant->getForeignKey();
        }

        if (!is_string($tenant)) {
            throw new TenantColumnUnknownException(
                '$tenant must be a string key or an instance of \Illuminate\Database\Eloquent\Model'
            );
        }

        return $tenant;
    }

    /**
     * Get the tenantColumns that are actually applicable to the given
     * model, in case they've been manually specified.
     *
     * @param Model|BelongsToTenants $model
     *
     * @return Collection
     */
    protected function modelTenants(Model $model)
    {
        return isset($this->belongsToTenantType) && $this->belongsToTenantType == TenantManager::BELONGS_TO_TENANT_TYPE_TO_ONE
            ? $this->tenants->only($model->getTenantColumns()) : $this->tenants;
    }
}
