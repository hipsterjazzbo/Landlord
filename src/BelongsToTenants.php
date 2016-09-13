<?php

namespace HipsterJazzbo\Landlord;

use HipsterJazzbo\Landlord\Exceptions\TenantModelNotFoundException;
use HipsterJazzbo\Landlord\Facades\Landlord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @mixin Model
 */
trait BelongsToTenants
{
    public static function bootBelongsToTenant()
    {
        // Add a global scope for each tenant this model should be scoped by.
        Landlord::applyTenantScopes(new static());

        // Add tenants automatically when creating models
        static::creating(function (Model $model) {
            Landlord::newModel($model);
        });
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
        return Landlord::newQueryWithoutTenants(new static());
    }

    /**
     * Get the tenants for this model.
     *
     * @return array
     */
    public function getTenants()
    {
        return isset($this->tenants) ? $this->tenants : config('landlord.default_tenants');
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
            if (! is_null(static::allTenants()->find($id, $columns))) {
                throw (new ModelNotFoundForTenantException())->setModel(get_called_class());
            }

            throw $e;
        }
    }
}
