<?php

declare(strict_types=1);

namespace Safemood\Discountify;

use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Exceptions\DuplicateCouponException;

/**
 * Class CouponManager
 * Manages coupons for discounts.
 */
class CouponManager
{
    protected array $coupons = [];

    /**
     * Add a coupon to the manager.
     *
     * @param  array  $coupon  The coupon details to add.
     * @return $this
     */
    public function add(array $coupon): self
    {
        $code = $coupon['code'];

        if ($this->get($code) !== null) {

            throw new DuplicateCouponException($code);
        }

        $this->coupons[$code] = $coupon;

        return $this;
    }

    /**
     * Remove a coupon from the manager.
     *
     * @param  string  $code  The code of the coupon to remove.
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
     * @param  string  $code  The code of the coupon to update.
     * @param  array  $updatedCoupon  The updated coupon details.
     * @return $this
     */
    public function update(string $code, array $updatedCoupon): self
    {
        $this->coupons[$code] = $updatedCoupon;

        return $this;
    }

    /**
     * Get the total discount applied by coupons.
     *
     * @return float The total discount applied.
     */
    public function couponDiscount(): float
    {
        return collect($this->appliedCoupons())
            ->sum(fn (array $coupon) => $coupon['discount']);
    }

    /**
     * Get a coupon by code.
     *
     * @param  string  $code  The code of the coupon to retrieve.
     * @return array|null The coupon details, or null if not found.
     */
    public function get(string $code): ?array
    {
        return $this->coupons[$code] ?? null;
    }

    /**
     * Apply a coupon to an order.
     *
     * @param  string  $code  The code of the coupon to apply.
     * @param  int|string|null  $userId  The user ID associated with the coupon application.
     * @return bool True if the coupon was successfully applied, otherwise false.
     */
    public function apply(string $code, int|string|null $userId = null): bool
    {
        if (! $this->verify($code, $userId)) {
            return false;
        }

        $coupon = $this->get($code);

        $coupon['applied'] = true;

        $this->update($code, $coupon);

        if ($userId !== null) {
            $this->trackUserForCoupon($coupon, $userId);
        }

        $this->decrementUsageLimit($coupon);

        if (config('discountify.fire_events')) {
            event(new CouponAppliedEvent($coupon));
        }

        return true;
    }

    /**
     * Check if a coupon is expired.
     *
     * @param  string  $code  The code of the coupon to check.
     * @return bool True if the coupon is expired, otherwise false.
     */
    public function isCouponExpired(string $code): bool
    {
        if (! array_key_exists('endDate', $this->coupons[$code])) {
            return false;
        }

        $endDateCarbon = $this->coupons[$code]['endDate'];

        return $endDateCarbon->isPast();
    }

    /**
     * Get the list of all coupons.
     *
     * @return array All coupons stored in the manager.
     */
    public function all(): array
    {
        return $this->coupons;
    }

    /**
     * Verify if a coupon is valid for use.
     *
     * @param  string  $code  The code of the coupon to verify.
     * @param  int|string|null  $userId  The user ID associated with the coupon verification.
     * @return bool True if the coupon is valid, otherwise false.
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
            && $userId
            && ! $this->isUserAllowedToUseCoupon($coupon, $userId)
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

        if (! $this->checkUsageLimit($coupon)) {
            $this->remove($code);

            return false;
        }

        return true;
    }

    /**
     * Remove applied coupons from the list of applied coupons.
     *
     * @return $this
     */
    public function removeAppliedCoupons(): self
    {
        $this->coupons = collect($this->coupons)
            ->reject(function (array $coupon) {
                return $coupon['applied'] ?? false;
            })->toArray();

        return $this;
    }

    /**
     * Clear all applied coupons.
     *
     * @return $this
     */
    public function clearAppliedCoupons(): self
    {
        $this->coupons = collect($this->coupons)
            ->reject(function (array $coupon) {
                return $coupon['applied'] ?? false;
            })->toArray();

        return $this;
    }

    /**
     * Get an array of applied coupons.
     *
     * @return array The list of applied coupons.
     */
    public function appliedCoupons(): array
    {
        return collect($this->coupons)
            ->filter(function (array $coupon) {
                return $coupon['applied'] ?? false;
            })->values()->all();
    }

    /**
     * Clear all coupons.
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->coupons = [];

        return $this;
    }

    /**
     * Decrement the usage limit of a coupon if applicable.
     *
     * @param  array  $coupon  The coupon to decrement the usage limit for.
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
     *
     * @param  array  $coupon  The coupon to check the usage limit for.
     * @return bool True if the usage limit has not been reached, otherwise false.
     */
    protected function checkUsageLimit(array $coupon): bool
    {
        return ! isset($coupon['usageLimit']) || $coupon['usageLimit'] > 0;
    }

    /**
     * Track the user who used the coupon.
     *
     * @param  array  $coupon  The coupon that was used.
     * @param  int  $userId  The ID of the user who used the coupon.
     */
    protected function trackUserForCoupon(array $coupon, int|string $userId): void
    {
        $coupon['usedBy'][] = $userId;
        $this->update($coupon['code'], $coupon);
    }

    /**
     * Check if the coupon is single-use.
     *
     * @param  array  $coupon  The coupon to check.
     * @return bool True if the coupon is single-use, otherwise false.
     */
    protected function isCouponSingleUse(array $coupon): bool
    {
        return isset($coupon['singleUse']) && $coupon['singleUse'] === true;
    }

    /**
     * Check if the coupon has already been used by the given user.
     *
     * @param  array  $coupon  The coupon to check.
     * @param  int|string  $userId  The ID of the user to check.
     * @return bool True if the coupon has already been used by the user, otherwise false.
     */
    protected function isCouponAlreadyUsedByUser(array $coupon, int|string $userId): bool
    {
        return isset($coupon['usedBy']) && in_array($userId, $coupon['usedBy'], true);
    }

    /**
     * Check if the coupon is limited to specific users.
     *
     * @param  array  $coupon  The coupon to check.
     * @return bool True if the coupon is limited to specific users, otherwise false.
     */
    protected function isCouponLimitedToUsers(array $coupon): bool
    {
        return isset($coupon['userIds']);
    }

    /**
     * Check if the user is allowed to use the coupon.
     *
     * @param  array  $coupon  The coupon to check.
     * @param  int|string  $userId  The ID of the user to check.
     * @return bool True if the user is allowed to use the coupon, otherwise false.
     */
    protected function isUserAllowedToUseCoupon(array $coupon, int|string $userId): bool
    {
        return isset($coupon['userIds']) && in_array($userId, $coupon['userIds'], true);
    }
}
