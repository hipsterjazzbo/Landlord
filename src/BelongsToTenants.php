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
        $model = new static();

        // Grab our singleton from the container
        static::$landlord = app(TenantManager::class);
        static::$landlord->setType(! is_null($model->belongsToTenantType)
            ? $model->belongsToTenantType : config('landlord.default_belongs_to_tenant_type'));

        // Add a global scope for each tenant this model should be scoped by.
        static::$landlord->applyTenantScopes($model);

        // Add tenantColumns automatically when creating models
        static::creating(function (Model $model) {
            static::$landlord->newModel($model);
        });

        if (static::$landlord->isRelatedByMany()) {
            static::created(function(Model $model){
                static::$landlord->newModelRelatedToManyTenants($model);
            });
            static::updated(function(Model $model){
                static::$landlord->newModelRelatedToManyTenants($model, true);
            });
        }
    }

    /**
     * Get the tenantColumns for this model.
     *
     * @return array
     */
    public function getTenantColumns()
    {
        return isset($this->tenantColumns)
            ? $this->tenantColumns : config('landlord.default_tenant_columns');
    }

    /**
     * Get the tenantModel for this model.
     *
     * @return Model
     */
    public function getTenantModel()
    {
        $morphRelation = $this->getTenantMorphRelationConfiguration();
        if (! class_exists($morphRelation['tenant_model'])) {
            throw new \InvalidArgumentException('$tenant_model must be an valid and existent class name');
        }

        $tenantModel = new $morphRelation['tenant_model'];
        if (! $tenantModel instanceof Model) {
            throw new \InvalidArgumentException('$tenant_model must be an instance of Illuminate\Database\Eloquent\Model');
        }

        return $tenantModel;
    }

    /**
     * Get the tenantRelationsModel for this model.
     *
     * @return Model
     */
    public function getTenantRelationsModel()
    {
        $morphRelation = $this->getTenantMorphRelationConfiguration();
        if (! class_exists($morphRelation['tenant_relations_model'])) {
            throw new \InvalidArgumentException('$tenant_relations_model must be an valid and existent class name');
        }

        $tenantRelationsModel = new $morphRelation['tenant_relations_model'];
        if (! $tenantRelationsModel instanceof Model) {
            throw new \InvalidArgumentException('$tenant_relations_model must be an instance of Illuminate\Database\Eloquent\Model');
        }

        return $tenantRelationsModel;
    }

    /**
     * Get the tenantMorphRelation configured for this model.
     *
     * @return array
     */
    private function getTenantMorphRelationConfiguration()
    {
        $morphRelation = config('landlord.default_morph_relation');
        $morphRelation+= isset($this->morphRelation) ? array_filter($this->morphRelation) : [];

        return $morphRelation;
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

    public function tenants()
    {
        if (static::$landlord->isRelatedByMany()) {
            return $this->morphToMany(
                get_class($this->getTenantModel()),
                $this->getTenantRelationsModel()->getTable()
            );
        }

        return null;
    }
}
