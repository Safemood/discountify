<?php

declare(strict_types=1);

namespace Safemood\Discountify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DiscountAppliedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $items,
        public readonly float $discountAmount,
        public readonly array $conditions = [],
    ) {}
}
