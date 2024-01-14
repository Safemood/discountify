<?php

namespace Safemood\Discountify\Contracts;

use Safemood\Discountify\ConditionManager;
use Safemood\Discountify\Discountify;

interface DiscountifyInterface
{
    public function getConditionManager(): ConditionManager;

    public function getConditions(): array;

    public function getGlobalDiscount(): int;

    public function getGlobalTaxRate(): float;

    public function getItems(): array;

    public function setConditionManager(ConditionManager $conditionManager): Discountify;

    public function setGlobalDiscount(int $globalDiscount): Discountify;

    public function setGlobalTaxRate(float $globalTaxRate): Discountify;

    public function setItems(array $items): Discountify;
}
