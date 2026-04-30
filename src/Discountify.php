<?php

declare(strict_types=1);

namespace Safemood\Discountify;

use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Events\PromoAppliedEvent;
use Safemood\Discountify\Exceptions\CouponException;
use Safemood\Discountify\Support\ConditionEngine;
use Safemood\Discountify\Support\CouponEngine;
use Safemood\Discountify\Support\PromoEngine;

/**
 * Discountify v2 — main service.
 *
 * Usage:
 *   Discountify::setItems($cart)
 *       ->setGlobalDiscount(5)
 *       ->setTaxRate(20)
 *       ->applyCoupon('SUMMER10')
 *       ->forUser(auth()->id())
 *       ->checkout();
 */
class Discountify
{
    // PHP 8.4 — asymmetric visibility for items (readable outside, writable only here)
    public private(set) array $items = [];

    private string $priceField = 'price';

    private string $quantityField = 'quantity';

    public function __construct(
        private readonly ConditionEngine $conditionEngine,
        private readonly CouponEngine $couponEngine,
        private readonly PromoEngine $promoEngine,
        private float $globalDiscount = 0.0,
        private float $taxRate = 0.0,
    ) {}

    // ── Cart setup ────────────────────────────────────────────────────────────

    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function setGlobalDiscount(float $discount): static
    {
        $this->globalDiscount = $discount;

        return $this;
    }

    public function setTaxRate(float $rate): static
    {
        $this->taxRate = $rate;

        return $this;
    }

    public function forUser(int|string|null $userId): static
    {
        $this->couponEngine->forUser($userId);
        $this->promoEngine->forUser($userId);

        return $this;
    }

    public function setFields(string $price = 'price', string $quantity = 'quantity'): static
    {
        $this->priceField = $price;
        $this->quantityField = $quantity;

        return $this;
    }

    // ── Condition passthrough ─────────────────────────────────────────────────

    public function define(array $conditions): static
    {
        $this->conditionEngine->add($conditions);

        return $this;
    }

    public function skipCondition(string $slug): static
    {
        $_ = $this->conditionEngine->skip($slug);

        return $this;
    }

    // ── Coupon passthrough ────────────────────────────────────────────────────

    public function applyCoupon(string $code): static
    {
        $this->couponEngine->apply($code);

        return $this;
    }

    public function removeCoupon(): static
    {
        $this->couponEngine->clear();

        return $this;
    }

    // ── Calculations (preview — no side-effects) ──────────────────────────────

    public function subtotal(): float
    {
        return round(
            collect($this->items)->sum(
                fn (array $item): float => ($item[$this->priceField] ?? 0) * ($item[$this->quantityField] ?? 1)
            ),
            2
        );
    }

    /**
     * Total discount amount from all sources combined.
     * Safe to call multiple times — does NOT record usages.
     */
    public function totalDiscount(): float
    {
        $subtotal = $this->subtotal();

        $globalAmount = round($subtotal * ($this->globalDiscount / 100), 2);
        $conditionPct = $this->conditionEngine->totalDiscount($this->items, $subtotal);
        $conditionAmount = round($subtotal * ($conditionPct / 100), 2);
        $promoAmount = $this->promoEngine->apply($this->items, $subtotal)['discount'];

        $couponAmount = 0.0;
        if ($this->couponEngine->hasCoupon()) {
            try {
                $remaining = max(0.0, $subtotal - $globalAmount - $conditionAmount - $promoAmount);
                $couponAmount = $this->couponEngine->calculateDiscount($remaining);
            } catch (CouponException) {
                $couponAmount = 0.0;
            }
        }

        return round($globalAmount + $conditionAmount + $promoAmount + $couponAmount, 2);
    }

    public function total(): float
    {
        return round(max(0.0, $this->subtotal() - $this->totalDiscount()), 2);
    }

    public function tax(): float
    {
        return round($this->total() * ($this->taxRate / 100), 2);
    }

    public function totalWithTax(): float
    {
        return round($this->total() + $this->tax(), 2);
    }

    // ── Checkout (side-effects: records usages, fires events) ─────────────────

    /**
     * Apply all discounts, record usages in DB, fire events.
     * Call exactly once at order placement — not for price previews.
     *
     * @throws CouponException
     */
    public function checkout(): array
    {
        $subtotal = $this->subtotal();
        $fireEvents = config('discountify.fire_events', true);

        // 1 — Global
        $globalAmount = round($subtotal * ($this->globalDiscount / 100), 2);

        // 2 — Conditions
        $conditionPct = $this->conditionEngine->totalDiscount($this->items, $subtotal);
        $conditionAmount = round($subtotal * ($conditionPct / 100), 2);

        if ($conditionAmount > 0 && $fireEvents) {
            DiscountAppliedEvent::dispatch(
                items: $this->items,
                discountAmount: $conditionAmount,
                conditions: $this->conditionEngine->evaluate($this->items)->toArray(),
            );
        }

        // 3 — Promos (records usages)
        $promoResult = $this->promoEngine->redeem($this->items, $subtotal);
        if (! empty($promoResult['promos']) && $fireEvents) {
            PromoAppliedEvent::dispatch(
                items: $this->items,
                promos: $promoResult['promos'],
                discount: $promoResult['discount'],
            );
        }

        // 4 — Coupon (records usage)
        $couponResult = null;
        if ($this->couponEngine->hasCoupon()) {
            $remaining = max(0.0, $subtotal - $globalAmount - $conditionAmount - $promoResult['discount']);
            $couponResult = $this->couponEngine->redeem($remaining);

            if ($fireEvents) {
                CouponAppliedEvent::dispatch(
                    items: $this->items,
                    coupon: $couponResult['coupon'],
                    discount: $couponResult['discount'],
                );
            }
        }

        $totalDiscount = round(
            $globalAmount + $conditionAmount + $promoResult['discount'] + ($couponResult['discount'] ?? 0),
            2
        );

        $total = round(max(0.0, $subtotal - $totalDiscount), 2);
        $tax = round($total * ($this->taxRate / 100), 2);
        $totalWithTax = round($total + $tax, 2);

        return [
            'subtotal' => $subtotal,
            'global_discount' => $globalAmount,
            'condition_discount' => $conditionAmount,
            'promo_discount' => $promoResult['discount'],
            'coupon_discount' => round($couponResult['discount'] ?? 0, 2),
            'total_discount' => $totalDiscount,
            'total' => $total,
            'tax' => $tax,
            'total_with_tax' => $totalWithTax,
            'coupon' => $couponResult,
            'promos' => $promoResult['promos'],
        ];
    }

    // ── Engine accessors ──────────────────────────────────────────────────────

    public function conditions(): ConditionEngine
    {
        return $this->conditionEngine;
    }

    public function coupons(): CouponEngine
    {
        return $this->couponEngine;
    }

    public function promos(): PromoEngine
    {
        return $this->promoEngine;
    }

    // ── API Routes ────────────────────────────────────────────────────────────

    /**
     * Register the Discountify API routes.
     *
     * Call this in your routes file to enable the API:
     *
     *   Route::middleware(['auth:sanctum'])->group(function () {
     *       Discountify::routes();
     *   });
     */
    public static function routes(): void
    {
        require __DIR__.'/../routes/api.php';
    }
}
