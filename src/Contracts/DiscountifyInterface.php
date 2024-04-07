<?php

declare(strict_types=1);

namespace Safemood\Discountify\Contracts;

use Safemood\Discountify\ConditionManager;
use Safemood\Discountify\CouponManager;

interface DiscountifyInterface
{
    public function discount(float $globalDiscount): self;

    public function conditionDiscount(): float;

    public function conditions(): ConditionManager;

    public function coupons(): CouponManager;

    public function getGlobalDiscount(): float;

    public function getGlobalTaxRate(): float;

    public function getItems(): array;

    public function setConditionManager(ConditionManager $conditionManager): self;

    public function setCouponManager(CouponManager $couponManager): self;

    public function setGlobalDiscount(float $globalDiscount): self;

    public function setGlobalTaxRate(float $globalTaxRate): self;

    public function setItems(array $items): self;

    public function subtotal(): float;

    public function tax(?float $globalTaxRate = null): float;

    public function taxAmount(?float $globalTaxRate = null): float;

    public function total(): float;

    public function totalWithDiscount(?float $globalDiscount = null): float;
}
