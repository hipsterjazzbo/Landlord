<?php

namespace AuraIsHere\LaravelMultiTenant\Facades;

use Illuminate\Support\Facades\Facade;

class TenantScopeFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'AuraIsHere\LaravelMultiTenant\TenantScope';
    }
}
