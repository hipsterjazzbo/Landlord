<?php

namespace HipsterJazzbo\Landlord;

use HipsterJazzbo\Landlord\Exceptions\ModelNotFoundForTenantException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @mixin Model
 */
trait BelongsToTenants
{
    /**
     * @var TenantManager
     */
    protected static $landlord;

    /**
     * Boot the trait. Will apply any scopes currently set, and
     * register a listener for when new models are created.
     */
    public static function bootBelongsToTenants()
    {
        // Grab our singleton from the container
        static::$landlord = app(TenantManager::class);

        // Add a global scope for each tenant this model should be scoped by.
        static::$landlord->applyTenantScopes(new static());

        // Add tenantColumns automatically when creating models
        static::creating(function (Model $model) {
            static::$landlord->newModel($model);
        });
    }

    /**
     * Get the tenantColumns for this model.
     *
     * @return array
     */
    public function getTenantColumns()
    {
        return isset($this->tenantColumns) ? $this->tenantColumns : config('landlord.default_tenant_columns');
    }

    /**
     * Returns the qualified tenant (table.tenant). Override this if you need to
     * provide unqualified tenants, for example if you're using a noSQL Database.
     *
     * @param mixed $tenant
     *
     * @return mixed
     */
    public function getQualifiedTenant($tenant)
    {
        return $this->getTable().'.'.$tenant;
    }

    /**
     * Returns a new query builder without any of the tenant scopes applied.
     *
     *     $allUsers = User::allTenants()->get();
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function allTenants()
    {
        return static::$landlord->newQueryWithoutTenants(new static());
    }

    /**
     * Override the default findOrFail method so that we can re-throw
     * a more useful exception. Otherwise it can be very confusing
     * why queries don't work because of tenant scoping issues.
     *
     * @param mixed $id
     * @param array $columns
     *
     * @throws ModelNotFoundForTenantException
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        try {
            return static::query()->findOrFail($id, $columns);
        } catch (ModelNotFoundException $e) {
            // If it DOES exist, just not for this tenant, throw a nicer exception
            if (!is_null(static::allTenants()->find($id, $columns))) {
                throw (new ModelNotFoundForTenantException())->setModel(get_called_class());
            }

            throw $e;
        }
    }
}
