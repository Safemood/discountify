<?php

namespace Safemood\Discountify\Contracts;

interface ConditionManagerInterface
{
    public function add(array $conditions);

    public function define(callable $condition, float $discountPercentage);

    public function defineIf(bool $condition, float $discountPercentage);

    public function getConditions(): array;
}
