<?php

declare(strict_types=1);

namespace Safemood\Discountify\Contracts;

interface PromoInterface
{
    public function isActive(): bool;

    public function conditionsMet(array $items): bool;
}
