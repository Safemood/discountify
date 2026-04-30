<?php

declare(strict_types=1);

namespace Safemood\Discountify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Safemood\Discountify\Models\Coupon;

final class CouponAppliedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Coupon|array $coupon,
        public readonly float $discount = 0.0,
        public readonly array $items = [],
    ) {}
}
