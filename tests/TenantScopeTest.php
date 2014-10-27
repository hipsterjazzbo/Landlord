<?php

use AuraIsHere\LaravelMultiTenant\TenantScope;
use Mockery as m;

class TenantScopeTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testAccessors()
	{
		$tenantScope = new TenantScope();

		$tenantScope->addTenant('column', 1);

		$tenants = $tenantScope->getTenants();
		$this->assertEquals(['column' => 1], $tenants);

		$this->assertTrue($tenantScope->hasTenant('column'));

		$tenantScope->removeTenant('column');

		$tenants = $tenantScope->getTenants();
		$this->assertEquals([], $tenants);
	}

	public function testApply()
	{
		$scope   = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$model   = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getModelTenants')->once()->with($model)->andReturn(['column' => 1]);

		$builder->shouldReceive('getModel')->andReturn($model);
		$builder->shouldReceive('whereRaw')->once()->with("table.column = '1'");

		$model->shouldReceive('getTenantWhereClause')->once()->with('column', 1)->andReturn("table.column = '1'");

		$scope->apply($builder);
	}

	public function testRemove()
	{
		$scope   = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$model   = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getModelTenants')->once()->with($model)->andReturn(['column' => 1]);

		$builder->shouldReceive('getModel')->andReturn($model);
		$builder->shouldReceive('getQuery')->andReturn($query = m::mock('StdClass'));

		$model->shouldReceive('getTenantWhereClause')->once()->with('column', 1)->andReturn("table.column = '1'");

		$query->wheres = [['type' => 'Null', 'column' => 'foo'], ['type' => 'raw', 'sql' => "table.column = '1'"]];

		$scope->remove($builder);

		$this->assertEquals($query->wheres, [['type' => 'Null', 'column' => 'foo']]);
	}

	public function testCreating()
	{
		$scope = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$model = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getModelTenants')->with($model)->andReturn(['column' => 1]);

		$model->shouldDeferMissing();
		$model->shouldReceive('hasGlobalScope')->andReturn(true);

		$scope->creating($model);

		$this->assertEquals(1, $model->column);
	}

	public function testGetModelTenants()
	{
		$scope = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$model = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getTenantId')->once()->andReturn(1);

		$model->shouldReceive('getTenantColumns')->once()->andReturn(['column']);

		$modelTenants = $scope->getModelTenants($model);

		$this->assertEquals(['column' => 1], $modelTenants);
	}

	/**
	 * @expectedException \AuraIsHere\LaravelMultiTenant\Exceptions\TenantColumnUnknownException
	 */
	public function testGetTenantIdThrowsException()
	{
		$scope = new TenantScope;

		$scope->getTenantId('column');
	}

	public function testIsTenantConstraint()
	{
		$scope        = new TenantScope;
		$model        = m::mock('Illuminate\Database\Eloquent\Model');
		$tenantColumn = 'column';
		$tenantId     = 1;

		$model->shouldReceive('getTenantWhereClause')->with($tenantColumn, $tenantId)->andReturn("table.column = '1'");

		$where = ['type' => 'raw', 'sql' => "table.column = '1'"];
		$this->assertTrue($scope->isTenantConstraint($model, $where, $tenantColumn, $tenantId));

		$where = ['type' => 'raw', 'sql' => "table.column = '2'"];
		$this->assertFalse($scope->isTenantConstraint($model, $where, $tenantColumn, $tenantId));
	}

	public function testDisable()
	{
		$scope   = m::mock('AuraIsHere\LaravelMultiTenant\TenantScope');
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$model   = m::mock('Illuminate\Database\Eloquent\Model');

		$scope->shouldDeferMissing();
		$scope->shouldReceive('getModelTenants')->with($model)->andReturn(['column' => 1])->never();

		$builder->shouldReceive('getModel')->andReturn($model)->never();
		$builder->shouldReceive('whereRaw')->with("table.column = '1'")->never();

		$model->shouldReceive('getTenantWhereClause')->with('column', 1)->andReturn("table.column = '1'")->never();

		$scope->disable();
		$scope->apply($builder);
	}
}
 