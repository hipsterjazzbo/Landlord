<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ScopeInterface;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantNotSetException;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantColumnUnknownException;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantBadFormatException;

class TenantScope implements ScopeInterface
{
    protected $tenants;

    /**
     * Define the array of column=>id to use as tenant filters.
     *
     * @param  array $tenants
     *
     * @return void
     */
    public function setTenants(array $tenants)
    {
        $this->tenants = $tenants;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     *
     * @throws Exceptions\TenantNotSetException
     * @throws Exceptions\TenantColumnUnknownException
     * @return void
     */
    public function apply(Builder $builder)
    {
        /** @var \Illuminate\Database\Eloquent\Model|ScopedByTenant $model */
        $model = $builder->getModel();

        // Use whereRaw instead of where to avoid issues with bindings when removing
        foreach ($this->getModelTenants($model) as $column => $id) {
            $builder->whereRaw($this->getTenantWhereClause($model, $column, $id));
        }
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     *
     * @return void
     */
    public function remove(Builder $builder)
    {
        /** @var \Illuminate\Database\Eloquent\Model|ScopedByTenant $model */
        $model = $builder->getModel();

        $query = $builder->getQuery();

        foreach ($this->getModelTenants($model) as $column => $id) {
            foreach ((array) $query->wheres as $key => $where) {
                // If the where clause is a tenant constraint, we will remove it from the query
                // and reset the keys on the wheres. This allows this developer to include
                // the tenant model in a relationship result set that is lazy loaded.
                if ($this->isTenantConstraint($where, $model, $column, $id)) {
                    unset($query->wheres[$key]);
    
                    $query->wheres = array_values($query->wheres);
    
                    // Just bail after this first one in case the user has manually specified
                    // the same where clause for some weird reason or by some nutso coincidence.
                    break;
                }
            }
        }
    }

    /**
     * Determine which tenants column=>id are really in used for this model.
     *
     * @param  ScopedByTenant $model
     *
     * @return array
     */
    public function getModelTenants($model)
    {
        if (isset($model->tenants)) {
            $tenants = [];
            if (is_array($model->tenants)) {
                foreach ($model->tenants as $key => $column) {
                    $tenants[$column] = $this->getTenantId($column);
                }
                return $tenants;
            } else {
                $column = $model->tenants;
                $id = $this->getTenantId($column);
                return [$column => $id];
            }
        } else {
            throw new TenantNotSetException(
                'You MUST define a "tenants" variable in "'.get_class($model).'" to define which column(s) will be used as tenant'
            );
        }
    }

    protected function getTenantId($column)
    {
        if (is_string($column)) {
            if (isset($this->tenants[$column])) {
                return $this->tenants[$column];
            } else {
                throw new TenantColumnUnknownException(
                    'Unknown column "'.$column.'" in tenants scope "'.var_dump($this->tenants).'"'
                );
            }
        } else {
            throw new TenantBadFormatException(
                '"tenants" variable in "'.get_class($model).'" MUST be a string or an array of strings'
            );
        }
    }

    /**
     * Determine if the given where clause is a tenant constraint.
     *
     * @param  array          $where
     * @param  ScopedByTenant $model
     *
     * @return bool
     */
    protected function isTenantConstraint(array $where, $model, $column, $id)
    {
        return $where['type'] == 'raw' && $where['sql'] == $this->getTenantWhereClause($model, $column, $id);
    }

    /**
     * Prepare a raw where clause. Do it this way instead of using where()
     * to avoid issues with bindings when removing.
     *
     * @return string
     */
    protected function getTenantWhereClause($model, $column, $id)
    {
        $tenantColumn = $model->getTable() . '.' . $column;
        $tenantId     = $id;

        return "{$tenantColumn} = '{$tenantId}'";
    }
}
