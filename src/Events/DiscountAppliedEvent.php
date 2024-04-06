<?php

declare(strict_types=1);

namespace Safemood\Discountify\Events;

class DiscountAppliedEvent
{
    public function __construct(
        public string $slug,
        public float $discount,
        public mixed $condition
    ) {
    }
}
