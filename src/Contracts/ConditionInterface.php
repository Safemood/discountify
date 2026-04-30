<?php

declare(strict_types=1);

namespace Safemood\Discountify\Contracts;

/**
 * Contract for all code-defined condition classes.
 *
 * Implementations live in app/Conditions/ and are auto-discovered.
 * Generate one with: php artisan discountify:condition MyCondition
 */
interface ConditionInterface
{
    /**
     * Evaluate whether this condition is satisfied by the cart items.
     * Return true → discount is applied.
     */
    public function __invoke(array $items): bool;
}
