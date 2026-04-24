<?php

declare(strict_types=1);

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;
use Safemood\Discountify\CouponManager;

/**
 * Class Coupon
 *
 * @method static \Safemood\Discountify\CouponManager add(array $coupon)
 * @method static \Safemood\Discountify\CouponManager remove(string $code)
 * @method static \Safemood\Discountify\CouponManager update(string $code, array $updatedCoupon)
 * @method static float couponDiscount()
 * @method static ?array get(string $code)
 * @method static bool apply(string $code, int|string|null $userId = null)
 * @method static bool isCouponExpired(string $code)
 * @method static array all()
 * @method static bool verify(string $code, int|string|null $userId = null)
 * @method static \Safemood\Discountify\CouponManager removeAppliedCoupons()
 * @method static \Safemood\Discountify\CouponManager clearAppliedCoupons()
 * @method static array appliedCoupons()
 * @method static \Safemood\Discountify\CouponManager clear()
 *
 * @see CouponManager
 */
class Coupon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CouponManager::class;
    }
}
