<?php

declare(strict_types=1);

namespace Safemood\Discountify\Events;

class CouponAppliedEvent
{
    public function __construct(
        public array $coupon,
    ) {
    }
}
