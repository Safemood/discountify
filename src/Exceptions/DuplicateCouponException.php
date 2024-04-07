<?php

declare(strict_types=1);

namespace Safemood\Discountify\Exceptions;

use InvalidArgumentException;

class DuplicateCouponException extends InvalidArgumentException
{
    public function __construct(string $code)
    {
        parent::__construct("Coupon with code '{$code}' already exists.");
    }
}
