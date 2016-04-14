<?php

use HipsterJazzbo\Landlord\Landlord;
use Mockery as m;

class TenantScopeTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testAccessors()
    {
        $tenantScope = new Landlord();

        $tenantScope->addTenant('column', 1);

        $tenants = $tenantScope->getTenants();
        $this->assertEquals(['column' => [1]], $tenants);

        $this->assertTrue($tenantScope->hasTenant('column'));

        $tenantScope->removeTenant('column');

        $tenants = $tenantScope->getTenants();
        $this->assertEquals([], $tenants);
    }

    public function testApply()
    {
        $scope   = m::mock(\HipsterJazzbo\Landlord\Landlord::class);
        $builder = m::mock(\Illuminate\Database\Eloquent\Builder::class);
        $model   = m::mock(\Illuminate\Database\Eloquent\Model::class);

        $scope->shouldDeferMissing();
        $scope->shouldReceive('getModelTenants')->once()->with($model)->andReturn(['column' => [1]]);

        $builder->shouldReceive('getModel')->andReturn($model);
        $builder->shouldReceive('whereIn')->once()->with('table.column', 1);

        $model->shouldReceive('getTable')->andReturn('table');

        $scope->apply($builder, $model);
    }

    public function testCreating()
    {
        $scope = m::mock(\HipsterJazzbo\Landlord\Landlord::class);
        $model = m::mock(\Illuminate\Database\Eloquent\Model::class);

        $scope->shouldDeferMissing();
        $scope->shouldReceive('getModelTenants')->with($model)->andReturn(['column' => [1]]);

        $model->shouldDeferMissing();
        $model->shouldReceive('hasGlobalScope')->andReturn(true);

        $scope->creating($model);

        $this->assertEquals(1, $model->column);
    }

    public function testGetModelTenants()
    {
        $scope = m::mock(\HipsterJazzbo\Landlord\Landlord::class);
        $model = m::mock(\Illuminate\Database\Eloquent\Model::class);

        $scope->shouldDeferMissing();
        $scope->shouldReceive('getTenantId')->once()->andReturn(1);
        $scope->shouldReceive('hasTenant')->once()->andReturn(true);
        
        $model->shouldReceive('getTenantColumns')->once()->andReturn(['column']);

        $modelTenants = $scope->getModelTenants($model);

        $this->assertEquals(['column' => 1], $modelTenants);
    }

    /**
     * @expectedException \HipsterJazzbo\Landlord\Exceptions\TenantColumnUnknownException
     */
    public function testGetTenantIdThrowsException()
    {
        $scope = new Landlord();

        $scope->getTenantId('column');
    }

    public function testDisable()
    {
        $scope   = m::mock(\HipsterJazzbo\Landlord\Landlord::class);
        $builder = m::mock(\Illuminate\Database\Eloquent\Builder::class);
        $model   = m::mock(\Illuminate\Database\Eloquent\Model::class);

        $scope->shouldDeferMissing();
        $scope->shouldReceive('getModelTenants')->with($model)->andReturn(['column' => 1])->never();

        $builder->shouldReceive('getModel')->andReturn($model)->never();
        $builder->shouldReceive('whereRaw')->with("table.column = '1'")->never();

        $model->shouldReceive('getTenantWhereClause')->with('column', 1)->andReturn("table.column = '1'")->never();

        $scope->disable();
        $scope->apply($builder, $model);
    }
}
