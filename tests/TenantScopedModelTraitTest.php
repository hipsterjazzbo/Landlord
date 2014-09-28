<?php

use Mockery as m;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use AuraIsHere\LaravelMultiTenant\Traits\TenantScopedModelTrait;

class TenantScopedModelTraitTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testAllTenants()
	{
		// Not sure how to write this test
	}

	public function testGetTenantColumns()
	{
		// This one either
	}

	public function testGetTenantWhereClause()
	{
		$model = m::mock('TenantScopedModelStub');
		$model->shouldDeferMissing();

		$whereClause = $model->getTenantWhereClause('column', 1);

		$this->assertEquals("table.column = '1'", $whereClause);
	}

	/**
	 * @expectedException \AuraIsHere\LaravelMultiTenant\Exceptions\TenantModelNotFoundException
	 */
	public function testFindOrFailThrowsTenantException()
	{
		TenantScopedModelStub::findOrFail(1, []);
	}
}

class TenantScopedModelStub extends ParentModel {

	use TenantScopedModelTrait;

	public function getTable()
	{
		return 'table';
	}
}

class ParentModel {

	public static function findOrFail($id, $columns)
	{
		throw new ModelNotFoundException;
	}
}
