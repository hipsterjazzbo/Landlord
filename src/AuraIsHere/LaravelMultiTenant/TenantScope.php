<?php namespace AuraIsHere\LaravelMultiTenant;

use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ScopeInterface;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantNotSetException;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantColumnUnknownException;
use AuraIsHere\LaravelMultiTenant\Exceptions\TenantBadFormatException;

class TenantScope implements ScopeInterface
{
    protected $tenants = [];

    /**
     * return tenants
     *
     * @return array
     */
    public function getTenants()
    {
        return $this->tenants;
    }

    /**
     * Add $attribute => $id to the current tenants array
     *
     * @param  string $attribute
     * @param  mixed $id
     *
     * @return void
     */
    public function addTenant($attribute, $id)
    {
        $this->tenants[$attribute] = $id;
    }

    /**
     * Remove $attribute => $id from the current tenants array
     *
     * @param  string $attribute
     * @param  mixed $id
     *
     * @return boolean
     */
    public function removeTenant($attribute)
    {
        if ($this->hasTenant($attribute)) {
            unset($this->tenants[$attribute]);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Test is current tenant includes a given attribute
     *
     * @param  string $attribute
     *
     * @return boolean
     */
    public function hasTenant($attribute)
    {
        return isset($this->tenants[$attribute]);
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
        foreach ($this->getModelTenants($model) as $attribute => $id) {
            $builder->whereRaw($this->getTenantWhereClause($model, $attribute, $id));
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

        foreach ($this->getModelTenants($model) as $attribute => $id) {
            foreach ((array) $query->wheres as $key => $where) {
                // If the where clause is a tenant constraint, we will remove it from the query
                // and reset the keys on the wheres. This allows this developer to include
                // the tenant model in a relationship result set that is lazy loaded.
                if ($this->isTenantConstraint($where, $model, $attribute, $id)) {
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
     * Return which attribute=>id are really in used for this model.
     *
     * @param  ScopedByTenant $model
     *
     * @return array
     */
    public function getModelTenants($model)
    {
        if (isset($model->tenantAttribute)) {
            if (is_array($model->tenantAttribute)) {
                $tenants = [];
                foreach ($model->tenantAttribute as $key => $attribute) {
                    $tenants[$attribute] = $this->getTenantId($attribute, $model);
                }
                return $tenants;
            } else {
                $attribute = $model->tenantAttribute;
                return [$attribute => $this->getTenantId($attribute, $model)];
            }
        } else {
            throw new TenantNotSetException(
                'You MUST define a "tenants" variable in "'.get_class($model).'" to define which attribute(s) will be used as tenant'
            );
        }
    }

    protected function getTenantId($attribute, $model)
    {
        if (is_string($attribute)) {
            if (isset($this->tenants[$attribute])) {
                return $this->tenants[$attribute];
            } else {
                throw new TenantColumnUnknownException(
                    get_class($model).': tenant attribute "'.$attribute.'" NOT found in tenants scope "'.json_encode($this->tenants).'"'
                );
            }
        } else {
            throw new TenantBadFormatException(
                get_class($model).': "tenantAttribute" variable in "'.get_class($model).'" MUST be a string or an array of strings'
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
    protected function isTenantConstraint(array $where, $model, $attribute, $id)
    {
        return $where['type'] == 'raw' && $where['sql'] == $this->getTenantWhereClause($model, $attribute, $id);
    }

    /**
     * Prepare a raw where clause. Do it this way instead of using where()
     * to avoid issues with bindings when removing.
     *
     * @return string
     */
    protected function getTenantWhereClause($model, $attribute, $id)
    {
        $tenantAttribute = $model->getTable() . '.' . $attribute;
        $tenantId     = $id;

        return "{$tenantAttribute} = '{$tenantId}'";
    }
}
