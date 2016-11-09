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
     * @var string 'one|'many'
     */
    protected $type = self::BELONGS_TO_TENANT_TYPE_TO_ONE;

    private $possibleTypes = [self::BELONGS_TO_TENANT_TYPE_TO_ONE, self::BELONGS_TO_TENANT_TYPE_TO_MANY];

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

        $key = $this->getTenantKey($tenant);

        $this->tenants->put($key, $id);
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
     * Set type of relation (one|many)
     *
     * @param $type
     */
    public function setType($type)
    {
        if (! in_array($type, $this->possibleTypes)) {
            throw new \InvalidArgumentException('$type must be "'.self::BELONGS_TO_TENANT_TYPE_TO_ONE.'" or "'.self::BELONGS_TO_TENANT_TYPE_TO_MANY.'"');
        }

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isRelatedByMany()
    {
        return $this->getType() == self::BELONGS_TO_TENANT_TYPE_TO_MANY;
    }

    /**
     * @return bool
     */
    public function isRelatedByOne()
    {
        return $this->getType() == self::BELONGS_TO_TENANT_TYPE_TO_ONE;
    }

    /**
     * Applies applicable tenant scopes to a model.
     *
     * @param Model  $model
     * @param string $type
     */
    public function applyTenantScopes(Model $model)
    {
        if (!$this->enabled) {
            return;
        }

        switch ($this->type) {
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
        if (!$this->enabled || $this->type == self::BELONGS_TO_TENANT_TYPE_TO_MANY) {
            return;
        }

        $this->modelTenants($model)->each(function ($tenantId, $tenantColumn) use ($model) {
            if (!isset($model->{$tenantColumn})) {
                $model->setAttribute($tenantColumn, $tenantId);
            }
        });
    }

    /**
     * Add model polymorphic relation to tenants.
     *
     * @param Model $model
     */
    public function newModelRelatedToManyTenants($model)
    {
        $this->modelTenants($model)->each(function($tenantId) use ($model) {
            $tenant = ($model->getTenantModel())::find($tenantId);

            $model->morphToMany(
                get_class($model->getTenantModel()),
                $model->getTenantRelationsModel()->getTable()
            )->save($tenant);
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
     * @return string
     * @throws TenantColumnUnknownException
     */
    protected function getTenantKey($tenant)
    {
        $key = clone $tenant;
        if ($tenant instanceof Model) {
            $key = $tenant->getForeignKey();
            if ($this->isRelatedByMany()) {
                $key .= "_{$tenant->getKey()}";
            }
        }

        if (!is_string($key)) {
            throw new TenantColumnUnknownException(
                '$key must be a string key or an instance of \Illuminate\Database\Eloquent\Model'
            );
        }

        return $key;
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
