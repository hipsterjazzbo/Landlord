<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery as m;

class TenantScopedModelTraitTest extends PHPUnit_Framework_TestCase
{
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

    /**
     * @expectedException \HipsterJazzbo\Landlord\Exceptions\TenantModelNotFoundException
     */
    public function testFindOrFailThrowsTenantException()
    {
        TenantScopedModelStub::findOrFail(1, []);
    }
}

class TenantScopedModelStub extends ParentModel
{
    use \HipsterJazzbo\Landlord\BelongsToTenant;

    public function getTable()
    {
        return 'table';
    }
}

class ParentModel
{
    public static function findOrFail($id, $columns)
    {
        throw new ModelNotFoundException();
    }

    public static function query()
    {
        throw new ModelNotFoundException();
    }
}
