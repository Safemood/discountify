<?php

declare(strict_types=1);

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;
use Safemood\Discountify\ConditionManager;
use Safemood\Discountify\CouponManager;

/**
 * Class Discountify
 *
 * @method static \Safemood\Discountify\Discountify discount(float $globalDiscount)
 * @method static array getConditions()
 * @method static ConditionManager conditions()
 * @method static \Safemood\Discountify\CouponManager coupons()
 * @method static int getGlobalDiscount()
 * @method static int getGlobalTaxRate()
 * @method static array getItems()
 * @method static float subtotal()
 * @method static float total()
 * @method static float totalWithDiscount(?float $globalDiscount = null)
 * @method static float tax(?float $globalTaxRate = null)
 * @method static \Safemood\Discountify\Discountify setConditionManager(\Safemood\Discountify\ConditionManager $conditionManager)
 * @method static \Safemood\Discountify\Discountify setGlobalDiscount(int $globalDiscount)
 * @method static \Safemood\Discountify\Discountify setGlobalTaxRate(float $globalTaxRate)
 * @method static \Safemood\Discountify\Discountify setItems(array $items)
 * @method static \Safemood\Discountify\Discountify setUserId(int|string|null $userId)
 *
 * @see \Safemood\Discountify\Discountify
 */
class Discountify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Safemood\Discountify\Discountify::class;
    }
}
