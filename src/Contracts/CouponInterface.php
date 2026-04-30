<?php

declare(strict_types=1);

namespace Safemood\Discountify\Contracts;

interface CouponInterface
{
    public function isValid(): bool;

    public function hasUsagesLeft(): bool;

    public function canBeUsedByUser(int|string|null $userId): bool;
}
