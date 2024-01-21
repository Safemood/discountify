<?php

namespace Safemood\Discountify\Contracts;

use Safemood\Discountify\ConditionManager;

interface ConditionManagerInterface
{
    public function add(array $conditions): ConditionManager;

    public function define(string $slug, callable $condition, float $discount): ConditionManager;

    public function defineIf(string $slug, bool $condition, float $discount): ConditionManager;

    public function getConditions(): array;
}
