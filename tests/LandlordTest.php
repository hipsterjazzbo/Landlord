<?php

use HipsterJazzbo\Landlord\BelongsToTenants;
use HipsterJazzbo\Landlord\Facades\Landlord;
use HipsterJazzbo\Landlord\TenantManager;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class LandlordTest extends TestCase
{
    public function testTenantsWithStrings()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);

        $this->assertEquals(['tenant_a_id' => 1], $landlord->getTenants()->toArray());

        $landlord->addTenant('tenant_b_id', 2);

        $this->assertEquals(['tenant_a_id' => 1, 'tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $landlord->removeTenant('tenant_a_id');

        $this->assertEquals(['tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $this->assertTrue($landlord->hasTenant('tenant_b_id'));

        $this->assertFalse($landlord->hasTenant('tenant_a_id'));
    }

    public function testTenantsWithModels()
    {
        Landlord::shouldReceive('applyTenantScopes');

        $tenantA = new TenantA();

        $tenantA->id = 1;

        $tenantB = new TenantB();

        $tenantB->id = 2;

        $landlord = new TenantManager();

        $landlord->addTenant($tenantA);

        $this->assertEquals(['tenant_a_id' => 1], $landlord->getTenants()->toArray());

        $landlord->addTenant($tenantB);

        $this->assertEquals(['tenant_a_id' => 1, 'tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $landlord->removeTenant($tenantA);

        $this->assertEquals(['tenant_b_id' => 2], $landlord->getTenants()->toArray());

        $this->assertTrue($landlord->hasTenant('tenant_b_id'));

        $this->assertFalse($landlord->hasTenant('tenant_a_id'));
    }

    public function testApplyTenantScopes()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);

        $landlord->addTenant('tenant_b_id', 2);

        Landlord::shouldReceive('applyTenantScopes');

        $model = new ModelStub();

        $landlord->applyTenantScopes($model);

        $this->assertArrayHasKey('tenant_a_id', $model->getGlobalScopes());

        $this->assertArrayNotHasKey('tenant_b_id', $model->getGlobalScopes());
    }

    public function testApplyTenantScopesToDeferredModels()
    {
        $landlord = new TenantManager();

        $model = new ModelStub();
        $landlord->newModel($model);

        $landlord->addTenant('tenant_a_id', 1);
        $this->assertNull($model->tenant_a_id);

        $landlord->applyTenantScopesToDeferredModels();

        $this->assertEquals(1, $model->tenant_a_id);
    }

    public function testNewModel()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);

        $landlord->addTenant('tenant_b_id', 2);

        Landlord::shouldReceive('applyTenantScopes');

        $model = new ModelStub();

        $landlord->newModel($model);

        $this->assertEquals(1, $model->tenant_a_id);

        $this->assertNull($model->tenant_b_id);
    }

    public function testGetTenantId()
    {
        $landlord = new TenantManager();

        $landlord->addTenant('tenant_a_id', 1);

        $tenantId = $landlord->getTenantId('tenant_a_id');

        $this->assertEquals(1, $tenantId);
    }
}

class ModelStub extends Model
{
    use BelongsToTenants;

    public $tenantColumns = ['tenant_a_id'];
}

class TenantA extends Model
{
    //
}

class TenantB extends Model
{
    //
}
