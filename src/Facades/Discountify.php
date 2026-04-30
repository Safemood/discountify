<?php

declare(strict_types=1);

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Safemood\Discountify\Discountify setItems(array $items)
 * @method static \Safemood\Discountify\Discountify setGlobalDiscount(float $discount)
 * @method static \Safemood\Discountify\Discountify setTaxRate(float $rate)
 * @method static \Safemood\Discountify\Discountify forUser(int|string|null $userId)
 * @method static \Safemood\Discountify\Discountify setFields(string $price, string $quantity)
 * @method static \Safemood\Discountify\Discountify define(array $conditions)
 * @method static \Safemood\Discountify\Discountify skipCondition(string $slug)
 * @method static \Safemood\Discountify\Discountify applyCoupon(string $code)
 * @method static \Safemood\Discountify\Discountify removeCoupon()
 * @method static float subtotal()
 * @method static float totalDiscount()
 * @method static float total()
 * @method static float tax()
 * @method static float totalWithTax()
 * @method static array checkout()
 * @method static \Safemood\Discountify\Support\ConditionEngine conditions()
 * @method static \Safemood\Discountify\Support\CouponEngine coupons()
 * @method static \Safemood\Discountify\Support\PromoEngine promos()
 * @method static void routes()
 *
 * @see \Safemood\Discountify\Discountify
 */
class Discountify extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \Safemood\Discountify\Discountify::class;
    }
}
