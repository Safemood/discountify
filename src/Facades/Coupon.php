<?php

declare(strict_types=1);

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Coupon
 *
 * @method static CouponManager add(array $coupon)
 * @method static CouponManager remove(string $code)
 * @method static CouponManager update(string $code, array $updatedCoupon)
 * @method static float couponDiscount()
 * @method static ?array get(string $code)
 * @method static bool apply(string $code, int|string|null $userId = null)
 * @method static bool isCouponExpired(string $code)
 * @method static array all()
 * @method static bool verify(string $code, int|string|null $userId = null)
 * @method static CouponManager removeAppliedCoupons()
 * @method static CouponManager clearAppliedCoupons()
 * @method static array appliedCoupons()
 * @method static CouponManager clear()
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
