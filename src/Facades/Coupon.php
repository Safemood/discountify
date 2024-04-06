<?php

declare(strict_types=1);

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Coupon
 *
 * @method static \Safemood\Discountify\CouponManager add(array $coupon) Add a coupon to the manager.
 * @method static \Safemood\Discountify\CouponManager remove(string $code) Remove a coupon from the manager.
 * @method static \Safemood\Discountify\CouponManager update(string $code, array $updatedCoupon) Update a coupon in the manager.
 * @method static array all() Get the list of all coupons.
 * @method static int couponDiscount() Get the total discount applied by coupons.
 * @method static array|null get(string $code) Get a coupon by code.
 * @method static void apply(string $code, int|string $userId = null) Apply a coupon to an order.
 * @method static bool isCouponExpired(string $code) Check if a coupon is expired.
 * @method static bool verify(string $code, int|string $userId = null) Verify if a coupon is valid for use.
 * @method static \Safemood\Discountify\CouponManager removeAppliedCoupons() Remove applied coupons.
 * @method static \Safemood\Discountify\CouponManager clearAppliedCoupons() Clear all applied coupons.
 * @method static array appliedCoupons() Get an array of applied coupons.
 *
 * @see \Safemood\Discountify\CouponManager
 */
class Coupon extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Safemood\Discountify\CouponManager::class;
    }
}
