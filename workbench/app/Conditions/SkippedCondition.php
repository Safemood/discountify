<?php

namespace Workbench\App\Conditions;

use Safemood\Discountify\Contracts\ConditionInterface;

class SkippedCondition implements ConditionInterface
{
    public bool $skip = true;

    public string $slug = 'skipped_condition';

    public int $discount = 40;

    public function __invoke(array $items): bool
    {
        return in_array('special', array_column($items, 'type'));
    }
}
