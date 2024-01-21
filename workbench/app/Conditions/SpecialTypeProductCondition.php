<?php

namespace Workbench\App\Conditions;

use Safemood\Discountify\Contracts\ConditionInterface;

class SpecialTypeProductCondition implements ConditionInterface
{
    public string $slug = 'special_type_product_40';

    public int $discount = 40;

    public function __invoke(array $items): bool
    {
        return in_array('special', array_column($items, 'type'));
    }
}
