<?php

declare(strict_types=1);

namespace Workbench\App\Conditions;

use Safemood\Discountify\Contracts\ConditionInterface;

class MoreThan1ProductsCondition implements ConditionInterface
{
    public string $slug = 'more_than_1_products_10';

    public int $discount = 10;

    public function __invoke(array $items): bool
    {
        return count($items) > 1;
    }
}
