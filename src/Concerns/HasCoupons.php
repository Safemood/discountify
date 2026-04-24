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
     */
    public function addCoupon(array $coupon): self
    {
        $this->coupons()->add($coupon);

        return $this;
    }

    /**
     * Remove a coupon from the manager.
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

        if ($userId !== null) {
            $this->setUserId($userId);
        }

        return $this;
    }

    /**
     * Get a coupon by its code.
     */
    public function getCoupon(string $code): ?array
    {
        return $this->coupons()->get($code);
    }

    /**
     * Get the total discount applied by coupons.
     */
    public function getCouponDiscount(): float
    {
        return $this->coupons()->couponDiscount();
    }

    /**
     * Remove an applied coupon.
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
     * Get applied coupons.
     */
    public function getAppliedCoupons(): array
    {
        return $this->coupons()->appliedCoupons();
    }

    /**
     * Clear all coupons.
     */
    public function clearCoupons(): self
    {
        $this->coupons()->clear();

        return $this;
    }
}
