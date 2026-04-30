<?php

declare(strict_types=1);

namespace Safemood\Discountify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PromoAppliedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $items,
        public readonly array $promos,
        public readonly float $discount,
    ) {}
}
