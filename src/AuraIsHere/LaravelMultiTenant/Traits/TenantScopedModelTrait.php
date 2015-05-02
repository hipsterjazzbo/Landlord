<?php

namespace AuraIsHere\LaravelMultiTenant\Traits;

use AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class TenantScopedModelTrait.
 *
 *
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\ScopeInterface $scope)
 * @method static void creating(callable $callback)
 */
trait TenantScopedModelTrait
{
    public static function bootTenantScopedModelTrait()
    {
        $tenantScope = app('AuraIsHere\LaravelMultiTenant\TenantScope');

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
        return with(new static())->newQueryWithoutScope(new TenantScope());
    }

    /**
     * Get the name of the "tenant id" column.
     *
     * @return string
     */
    public function getTenantColumns()
    {
        return isset($this->tenantColumns) ? $this->tenantColumns : config('multi-tenant.default_tenant_columns');
    }

    /**
     * Prepare a raw where clause. Do it this way instead of using where()
     * to avoid issues with bindings when removing.
     *
     * @param $tenantColumn
     * @param $tenantId
     *
     * @return string
     */
    public function getTenantWhereClause($tenantColumn, $tenantId)
    {
        return "{$this->getTable()}.{$tenantColumn} = '{$tenantId}'";
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
