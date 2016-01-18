<?php

namespace HipsterJazzbo\Landlord\Facades;

use Illuminate\Support\Facades\Facade;

class LandlordFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'HipsterJazzbo\Landlord\Landlord';
    }
}
