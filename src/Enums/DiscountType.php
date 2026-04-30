<?php

declare(strict_types=1);

namespace Safemood\Discountify\Enums;

/**
 * Backed enum for discount types.
 * Used in Condition, Coupon and Promo models so the type is always validated.
 */
enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentage (%)',
            self::Fixed => 'Fixed amount',
        };
    }

    /**
     * Compute the concrete discount amount.
     * PHP 8.4 — enum methods replace static helper classes.
     */
    public function calculate(float $value, float $orderTotal, ?float $maxDiscount = null): float
    {
        $amount = match ($this) {
            self::Percentage => $orderTotal * ($value / 100),
            self::Fixed => min($value, $orderTotal),
        };

        return round(
            $maxDiscount !== null ? min($amount, $maxDiscount) : $amount,
            2
        );
    }

    /** Convert to a percentage of the given subtotal (used when stacking). */
    public function toPercentage(float $value, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        return match ($this) {
            self::Percentage => $value,
            self::Fixed => ($value / $subtotal) * 100,
        };
    }
}
