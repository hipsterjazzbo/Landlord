<?php

namespace AuraIsHere\LaravelMultiTenant\Traits;

use AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class TenantScopedModelTrait.
 *
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\Scope $scope)
 * @method static void creating(callable $callback)
 */
trait TenantScopedModelTrait
{
    public static function bootTenantScopedModelTrait()
    {
        $tenantScope = app(TenantScope::class);

        // Add the global scope that will handle all operations except create()
        static::addGlobalScope($tenantScope);

        // Add an observer that will automatically add the tenant id when create()-ing
        static::creating(function (Model $model) use ($tenantScope) {
            $tenantScope->creating($model);
        });
    }

    /**
     * Returns a new builder without the tenant scope applied.
     *
     *     $allUsers = User::allTenants()->get();
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function allTenants()
    {
        return with(new static())->newQueryWithoutScope(TenantScope::class);
    }

    /**
     * Get the name of the "tenant id" column.
     *
     * @return string
     */
    public function getTenantColumns()
    {
        return isset($this->tenantColumns) ? $this->tenantColumns : config('tenant.default_tenant_columns');
    }

    /**
     * Override the default findOrFail method so that we can rethrow a more useful exception.
     * Otherwise it can be very confusing why queries don't work because of tenant scoping issues.
     *
     * @param       $id
     * @param array $columns
     *
     * @throws TenantModelNotFoundException
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        try {
            return parent::query()->findOrFail($id, $columns);
        } catch (ModelNotFoundException $e) {
            throw with(new TenantModelNotFoundException())->setModel(get_called_class());
        }
    }
}
