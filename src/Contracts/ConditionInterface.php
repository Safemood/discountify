<?php

namespace Safemood\Discountify\Contracts;

interface ConditionInterface
{
    /**
     * Invoke the condition logic.
     */
    public function __invoke(array $items): bool;
}
