<?php

namespace Safemood\Discountify\Events;

class DiscountAppliedEvent
{
    public function __construct(
        public string $slug,
        public int $discount,
        public mixed $condition
    ) {
    }
}
