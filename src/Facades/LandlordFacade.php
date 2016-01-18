<?php

namespace HipsterJazzbo\Landlord\Facades;

use HipsterJazzbo\Landlord\Landlord;
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
        return Landlord::class;
    }
}
