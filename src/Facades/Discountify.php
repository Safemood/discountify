<?php

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;
use Safemood\Discountify\ConditionManager;

/**
 * Class Discountify
 *
 * @method static \Safemood\Discountify\Discountify discount(float $globalDiscount)
 * @method static array getConditions()
 * @method static ConditionManager conditions()
 * @method static CouponManager coupons()
 * @method static int getGlobalDiscount()
 * @method static int getGlobalTaxRate()
 * @method static array getItems()
 * @method static float subtotal()
 * @method static float total()
 * @method static float totalWithDiscount(?float $globalDiscount = null)
 * @method static float tax(?float $globalTaxRate = null)
 * @method static \Safemood\Discountify\Discountify setConditionManager(ConditionManager $conditionManager)
 * @method static \Safemood\Discountify\Discountify setGlobalDiscount(int $globalDiscount)
 * @method static \Safemood\Discountify\Discountify setGlobalTaxRate(float $globalTaxRate)
 * @method static \Safemood\Discountify\Discountify setItems(array $items)
 *
 * @see \Safemood\Discountify\Discountify
 */
class Discountify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Safemood\Discountify\Discountify::class;
    }
}
