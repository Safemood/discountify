<?php

namespace Safemood\Discountify\Contracts;

use Safemood\Discountify\ConditionManager;

interface ConditionManagerInterface
{
    public function add(array $conditions): ConditionManager;

    public function define(string $slug, callable $condition, float $discountPercentage): ConditionManager;

    public function defineIf(string $slug, bool $condition, float $discountPercentage): ConditionManager;

    public function getConditions(): array;
}
