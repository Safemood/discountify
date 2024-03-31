<?php

namespace Safemood\Discountify;

/**
 * Class CouponManager
 */
class CouponManager
{
    protected array $coupons = [];

    /**
     * Add a coupon to the manager.
     *
     * @return $this
     */
    public function add(array $coupon): self
    {
        $this->coupons[$coupon['code']] = $coupon;

        return $this;
    }

    /**
     * Remove a coupon from the manager.
     *
     * @return $this
     */
    public function remove(string $code): self
    {
        unset($this->coupons[$code]);

        return $this;
    }

    /**
     * Update a coupon in the manager.
     *
     * @return $this
     */
    public function update(string $code, array $updatedCoupon): self
    {
        $this->coupons[$code] = $updatedCoupon;

        return $this;
    }

    /**
     * Get all the coupons.
     */
    public function getCoupons(): array
    {
        return $this->coupons;
    }

    /**
     * Get the total discount applied by coupons.
     */
    public function couponDiscount(): int
    {
        return collect($this->appliedCoupons())
            ->sum(fn ($coupon) => $coupon['discount']);
    }

    /**
     * Get a coupon by code.
     */
    public function get(string $code): ?array
    {
        return $this->coupons[$code] ?? null;
    }

    /**
     * Apply a coupon to an order.
     */
    public function apply(string $code, int|string|null $userId = null): bool
    {
        if (!$this->verify($code, $userId)) {
            return false;
        }

        $coupon = $this->get($code);

        $coupon['applied'] = true;

        $this->update($code, $coupon);

        if ($userId) {
            $this->trackUserForCoupon($coupon, $userId);
        }

        $this->decrementUsageLimit($coupon);

        return true;
    }

    /**
     * Check if a coupon is expired.
     */
    public function isCouponExpired(string $code): bool
    {
        return isset($this->coupons[$code]['endDate']) && strtotime($this->coupons[$code]['endDate']) < strtotime('now');
    }

    /**
     * Get the list of all coupons.
     */
    public function all(): array
    {
        return $this->coupons;
    }

    /**
     * Verify if a coupon is valid for use.
     */
    public function verify(string $code, int|string|null $userId = null): bool
    {
        $coupon = $this->get($code);

        if ($coupon === null) {
            return false;
        }

        if ($this->isCouponExpired($code)) {
            return false;
        }

        if ($this->isCouponLimitedToUsers($coupon) && $userId === null) {
            return false;
        }

        if (
            $this->isCouponLimitedToUsers($coupon)
            && !$this->isUserAllowedToUseCoupon($coupon, $userId)
        ) {
            return false;
        }

        if (
            $this->isCouponSingleUse($coupon)
            && isset($coupon['applied'])
            && $coupon['applied']
        ) {
            return false;
        }

        if (!$this->checkUsageLimit($coupon)) {
            $this->remove($code);
            return false;
        }

        return true;
    }

    /**
     * Remove an applied coupon from the list of applied coupons.
     */
    public function removeAppliedCoupons(): self
    {
        $this->coupons = collect($this->coupons)
            ->reject(function ($coupon) {
                return $coupon['applied'] ?? false;
            })->toArray();

        return $this;
    }

    /**
     * Clear all applied coupons.
     */
    public function clearAppliedCoupons(): self
    {
        $this->coupons = collect($this->coupons)->reject(function ($coupon) {
            return $coupon['applied'] ?? false;
        })->toArray();

        return $this;
    }

    /**
     * Get an array of applied coupons.
     */
    public function appliedCoupons(): array
    {
        return collect($this->coupons)->filter(function ($coupon) {
            return $coupon['applied'] ?? false;
        })->values()->all();
    }

    /**
     * Clear all coupons.
     */
    public function clear(): self
    {
        $this->coupons = [];

        return $this;
    }

    /**
     * Decrement the usage limit of a coupon if applicable.
     */
    protected function decrementUsageLimit(array $coupon): void
    {
        if (isset($coupon['usageLimit']) && $coupon['usageLimit'] > 0) {

            $coupon['usageLimit']--;

            $this->update($coupon['code'], $coupon);
        }
    }

    /**
     * Check if the coupon usage limit has been reached.
     */
    protected function checkUsageLimit(array $coupon): bool
    {
        return !isset($coupon['usageLimit']) || $coupon['usageLimit'] > 0;
    }

    /**
     * Track the user who used the coupon.
     */
    protected function trackUserForCoupon(array $coupon, int $userId): void
    {
        $coupon['usedBy'][] = $userId;
        $this->update($coupon['code'], $coupon);
    }

    protected function isCouponSingleUse(array $coupon): bool
    {
        return isset($coupon['singleUse']) && $coupon['singleUse'] === true;
    }

    protected function isCouponAlreadyUsedByUser(array $coupon, int|string $userId): bool
    {
        return isset($coupon['usedBy']) && in_array($userId, $coupon['usedBy'], true);
    }

    protected function isCouponLimitedToUsers(array $coupon): bool
    {
        return isset($coupon['userIds']);
    }

    protected function isUserAllowedToUseCoupon(array $coupon, int|string $userId): bool
    {
        return isset($coupon['userIds']) && in_array($userId, $coupon['userIds'], true);
    }
}
