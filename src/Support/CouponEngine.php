<?php

declare(strict_types=1);

namespace Safemood\Discountify\Support;

use Safemood\Discountify\Enums\DiscountType;
use Safemood\Discountify\Exceptions\CouponException;
use Safemood\Discountify\Models\Coupon;

/**
 * CouponEngine — validates and applies a single coupon code.
 *
 * Coupons are always stored in the database so they can be managed via UI.
 * Call calculateDiscount() to preview; redeem() to record usage.
 */
final class CouponEngine
{
    private ?Coupon $coupon = null;

    // PHP 8.4 — asymmetric visibility (public read, private write)
    public private(set) ?string $code = null;

    private int|string|null $userId = null;

    // ── Public API ────────────────────────────────────────────────────────────

    public function apply(string $code): static
    {
        $this->code = strtoupper(trim($code));
        $this->coupon = null;

        return $this;
    }

    public function forUser(int|string|null $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function clear(): static
    {
        $this->code = null;
        $this->coupon = null;

        return $this;
    }

    public function hasCoupon(): bool
    {
        return $this->code !== null;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    // ── Resolution & calculation ──────────────────────────────────────────────

    /** Validate the coupon. Throws CouponException on any failure. */
    public function resolve(): Coupon
    {
        if ($this->coupon !== null) {
            return $this->coupon;
        }

        if ($this->code === null) {
            throw CouponException::noCode();
        }

        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($this->code)))
            ->first();

        match (true) {
            $coupon === null => throw CouponException::notFound($this->code),
            ! $coupon->isValid() => throw CouponException::notValid($this->code),
            ! $coupon->hasUsagesLeft() => throw CouponException::exhausted($this->code),
            ! $coupon->canBeUsedByUser($this->userId) => throw CouponException::notAllowedForUser($this->code),
            default => null,
        };

        return $this->coupon = $coupon;
    }

    /**
     * Calculate the discount without recording usage.
     *
     * @throws CouponException
     */
    public function calculateDiscount(float $orderTotal): float
    {
        $coupon = $this->resolve();

        if ($coupon->min_order_value !== null && $orderTotal < $coupon->min_order_value) {
            assert($this->code !== null);
            throw CouponException::belowMinimumOrder($this->code, $coupon->min_order_value);
        }

        return $coupon->calculateDiscount($orderTotal);
    }

    /**
     * Calculate + record usage in one step.
     *
     * @return array{coupon: Coupon, discount: float, code: string, type: DiscountType}
     *
     * @throws CouponException
     */
    public function redeem(float $orderTotal): array
    {
        $discount = $this->calculateDiscount($orderTotal);
        $coupon = $this->coupon;
        assert($coupon !== null);

        $coupon->recordUsage($this->userId, $discount);

        return [
            'coupon' => $coupon,
            'discount' => $discount,
            'code' => $coupon->code,
            'type' => $coupon->discount_type,
        ];
    }
}
