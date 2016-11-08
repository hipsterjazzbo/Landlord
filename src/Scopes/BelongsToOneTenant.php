<?php
namespace HipsterJazzbo\Landlord\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BelongsToOneTenant implements Scope
{
    /** @var string $tenantColumn */
    private $tenantColumn;
    /** @var mixed tenantId */
    private $tenantId;

    public function __construct($tenantColumn = null, $tenantId = null)
    {
        $this->tenantColumn = $tenantColumn;
        $this->tenantId = $tenantId;
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
        $builder->where("{$model->getTable()}.{$this->tenantColumn}", '=', $this->tenantId);
    }
}