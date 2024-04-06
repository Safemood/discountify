<?php

declare(strict_types=1);

namespace Safemood\Discountify\Exceptions;

use InvalidArgumentException;

class ZeroQuantityException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Quantity cannot be zero.');
    }
}
