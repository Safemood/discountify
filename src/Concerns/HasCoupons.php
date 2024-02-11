<?php

namespace Safemood\Discountify\Concerns;

/**
 * Trait HasCoupons
 */
trait HasCoupons
{
    /**
     * Add a coupon to the manager.
     *
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
     * @return $this
     */
    public function removeCoupon(string $code): self
    {
        $this->coupons()->remove($code);

        return $this;
    }

    /**
     * Apply a coupon to an order.
     */
    public function applyCoupon(string $code, int|string|null $userId = null): self
    {
        $this->coupons()->apply($code, $userId);

        return $this;
    }

    /**
     * Get a coupon by code.
     */
    public function getCoupon(string $code): ?array
    {
        return $this->coupons()->get($code);
    }

    /**
     * Get the total discount applied by coupons.
     */
    public function getCouponDiscount(): int
    {
        return $this->coupons()->couponDiscount();
    }

    /**
     * Remove an applied coupon from the list of applied coupons.
     */
    public function removeAppliedCoupons(): self
    {
        $this->coupons()->removeAppliedCoupons();

        return $this;
    }

    /**
     * Clear all applied coupons.
     */
    public function clearAppliedCoupons(): self
    {
        $this->coupons()->clearAppliedCoupons();

        return $this;
    }

    /**
     * Get an array of applied coupons.
     */
    public function getAppliedCoupons(): array
    {
        return $this->coupons()->appliedCoupons();
    }

    /**
     * Clear all coupons.
     */
    public function clear(): self
    {
        $this->coupons()->clear();

        return $this;
    }
}
