<?php
namespace HipsterJazzbo\Landlord\Scopes;

use HipsterJazzbo\Landlord\TenantManager;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

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
        if ($this->manager->getTenants()->isEmpty()) {
            return;
        }

        /** @var Model $tenant_model */
        $tenant_model = $model->getTenantModel();
        /** @var Model $tenant_relations_model */
        $tenant_relations_model = $model->getTenantRelationsModel();

        $builder->getQuery()->leftJoin(
            $tenant_relations_model->getTable(),
            function(JoinClause $join) use ($tenant_model, $tenant_relations_model, $model) {
                /** @var Model $tenant_model */
                /** @var Model $tenant_relations_model */
                $join->on("{$tenant_relations_model->getTable()}.{$tenant_relations_model->getForeignKey()}", "=", "{$model->getTable()}.{$model->getKeyName()}");
                $join->whereRaw("{$tenant_relations_model->getTable()}.{$tenant_relations_model->getTable()}_type = ?", [
                    $model->getMorphClass()
                ]);
                $join->whereRaw(
                    "{$tenant_relations_model->getTable()}.{$tenant_model->getForeignKey()} IN (?)",
                    [
                        $this->manager->getTenants()->values()
                    ]
                );

                if (method_exists($tenant_relations_model, "forceDelete")) {
                    $join->whereNull("{$tenant_relations_model->getTable()}.deleted_at");
                }
            }
        )->whereNotNull("{$tenant_relations_model->getTable()}.{$tenant_relations_model->getKeyName()}");
        $builder->getQuery()->join(
            $tenant_model->getTable(),
            function(JoinClause $join) use ($tenant_model, $tenant_relations_model, $model) {
                $join->on("{$tenant_model->getTable()}.{$tenant_model->getKeyName()}", "=", "{$tenant_relations_model->getTable()}.{$tenant_model->getForeignKey()}");
                if (method_exists($tenant_model, "forceDelete")) {
                    $join->whereNull("{$tenant_model->getTable()}.deleted_at");
                }
            }
        );
    }
}
