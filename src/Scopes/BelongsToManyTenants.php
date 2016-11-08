<?php
namespace HipsterJazzbo\Landlord\Scopes;

use HipsterJazzbo\Landlord\TenantManager;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BelongsToManyTenants implements Scope
{
    /** @var TenantManager $manager */
    private $manager;

    public function __construct(TenantManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $tenant_model = $model->getTenantModel();
        $tenant_relations_model = $model->getTenantRelationsModel();

        $query = $model->morphToMany(get_class($tenant_model), $tenant_relations_model->getTable());
        $query->wherePivotIn(
            "{$tenant_relations_model->getTable()}.{$tenant_relations_model->getForeignKey()}",
            $this->manager->getTenants()->values()->toArray()
        );
        $builder->mergeBindings($query->getQuery());
    }
}