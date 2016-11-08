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
        $morphRelation = array_merge(
            config('landlord.default_morph_relation'),
            isset($model->morphRelation) && ! empty($model->morphRelation)
                ? array_filter($model->morphRelation) : []
        );

        $query = $model->morphToMany('App\Tenant', $morphRelation['table']);
        $query->wherePivotIn(
            "{$morphRelation['table']}.{$morphRelation['tenant_id_column']}",
            $this->manager->getTenants()->values()->toArray()
        );
        $builder->mergeBindings($query->getQuery());
    }
}