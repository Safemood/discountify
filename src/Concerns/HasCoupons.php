<?php

declare(strict_types=1);

namespace Safemood\Discountify\Concerns;

/**
 * Trait HasCoupons
 *
 * This trait provides methods for managing coupons within Discountify.
 */
trait HasCoupons
{
    /**
     * Add a coupon to the manager.
     *
     * @param  array  $coupon  The coupon data.
     * @return $this
     */
    public function addCoupon(array $coupon): self
    {
        $this->coupons()->add($coupon);

        return $this;
    }

    /**
     * Remove a coupon from the manager.
     *
     * @param  string  $code  The code of the coupon to remove.
     * @return $this
     */
    public function removeCoupon(string $code): self
    {
        $this->coupons()->remove($code);

        return $this;
    }

    /**
     * Apply a coupon to an order.
     *
     * @param  string  $code  The code of the coupon to apply.
     * @param  int|string|null  $userId  Optional. The ID of the user to whom the coupon applies.
     * @return $this
     */
    public function applyCoupon(string $code, int|string|null $userId = null): self
    {
        $this->coupons()->apply($code, $userId);

        return $this;
    }

    /**
     * Get a coupon by its code.
     *
     * @param  string  $code  The code of the coupon.
     * @return array|null The coupon data, or null if not found.
     */
    public function getCoupon(string $code): ?array
    {
        return $this->coupons()->get($code);
    }

    /**
     * Get the total discount applied by coupons.
     *
     * @return float The total discount applied by coupons.
     */
    public function getCouponDiscount(): float
    {
        return $this->coupons()->couponDiscount();
    }

    /**
     * Remove an applied coupon from the list of applied coupons.
     *
     * @return $this
     */
    public function removeAppliedCoupons(): self
    {
        $this->coupons()->removeAppliedCoupons();

        return $this;
    }

    /**
     * Clear all applied coupons.
     *
     * @return $this
     */
    public function clearAppliedCoupons(): self
    {
        $this->coupons()->clearAppliedCoupons();

        return $this;
    }

    /**
     * Get an array of applied coupons.
     *
     * @return array An array of applied coupons.
     */
    public function getAppliedCoupons(): array
    {
        return $this->coupons()->appliedCoupons();
    }

    /**
     * Clear all coupons.
     *
     * @return $this
     */
    public function clearCoupons(): self
    {
        $this->coupons()->clear();

        return $this;
    }
}
