<?php

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Safemood\Discountify\Discountify
 */
class Discountify extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Safemood\Discountify\Discountify::class;
    }
}
