<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Safemood\Discountify\Discountify;
use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Events\PromoAppliedEvent;
use Safemood\Discountify\Exceptions\CouponException;
use Safemood\Discountify\Models\Coupon;
use Safemood\Discountify\Models\CouponUsage;
use Safemood\Discountify\Models\Promo;
use Safemood\Discountify\Models\PromoUsage;
use Safemood\Discountify\Support\ConditionEngine;
use Safemood\Discountify\Support\CouponEngine;
use Safemood\Discountify\Support\PromoEngine;

/**
 * End-to-end tests simulating realistic checkout scenarios.
 * Each test exercises the full stack: cart → discount engines → DB → events.
 */
describe('End-to-end checkout scenarios', function (): void {

    // ── Scenario 1: Simple coupon-only checkout ────────────────────────────────

    it('Scenario: customer applies a 10% coupon on a £200 cart', function (): void {
        $this->makeCoupon([
            'code' => 'WELCOME10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_usages' => 100,
        ]);

        $cart = $this->makeItems(count: 2, price: 100.0);

        $result = app(Discountify::class)
            ->setItems($cart)
            ->setTaxRate(20)
            ->applyCoupon('WELCOME10')
            ->forUser(userId: 1)
            ->checkout();

        expect($result['subtotal'])->toBe(200.0)
            ->and($result['coupon_discount'])->toBe(20.0)
            ->and($result['total'])->toBe(180.0)
            ->and($result['tax'])->toBe(36.0)
            ->and($result['total_with_tax'])->toBe(216.0)
            ->and(CouponUsage::count())->toBe(1);
    });

    // ── Scenario 2: Auto-applied promo + no coupon ─────────────────────────────

    it('Scenario: buy 3 or more items and get 15% off automatically', function (): void {
        $this->makePromo([
            'name' => 'Buy 3 Save 15',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'is_stackable' => true,
            'conditions' => [['field' => 'count', 'operator' => 'gte', 'value' => 3]],
        ]);

        // 3 items at £50 each = £150
        $cart = $this->makeItems(count: 3, price: 50.0);

        $result = app(Discountify::class)
            ->setItems($cart)
            ->checkout();

        expect($result['subtotal'])->toBe(150.0)
            ->and($result['promo_discount'])->toBe(22.50)
            ->and($result['total'])->toBe(127.50)
            ->and(PromoUsage::count())->toBe(1);
    });

    // ── Scenario 3: Code condition + promo + coupon all stack ──────────────────

    it('Scenario: VIP user gets condition + promo + coupon all combined', function (): void {
        Event::fake();

        // 10% promo auto-applied
        $this->makePromo([
            'name' => 'Loyalty Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_stackable' => true,
        ]);

        // £10 fixed coupon
        $this->makeCoupon([
            'code' => 'VIP10',
            'discount_type' => 'fixed',
            'discount_value' => 10,
        ]);

        $cart = $this->makeItems(count: 2, price: 100.0); // subtotal = £200

        $result = app(Discountify::class)
            ->setItems($cart)
            ->setGlobalDiscount(5)            // £10 global
            ->define([[                        // £20 code condition (10% of 200)
                'slug' => 'vip_cond',
                'condition' => fn ($i) => true,
                'discount' => 10,
                'type' => 'percentage',
            ]])
            ->applyCoupon('VIP10')
            ->forUser(userId: 42)
            ->checkout();

        // global  = £10
        // condition = £20
        // promo   = 10% of £200 = £20
        // remaining after above = £200 - £10 - £20 - £20 = £150
        // coupon  = £10 fixed off £150 = £10
        // total discount = £60 → total = £140
        expect($result['global_discount'])->toBe(10.0)
            ->and($result['condition_discount'])->toBe(20.0)
            ->and($result['promo_discount'])->toBe(20.0)
            ->and($result['coupon_discount'])->toBe(10.0)
            ->and($result['total_discount'])->toBe(60.0)
            ->and($result['total'])->toBe(140.0);

        Event::assertDispatched(DiscountAppliedEvent::class);
        Event::assertDispatched(PromoAppliedEvent::class);
        Event::assertDispatched(CouponAppliedEvent::class);
    });

    // ── Scenario 4: Exhausted coupon after 1 use ───────────────────────────────

    it('Scenario: coupon is exhausted after its single allowed use', function (): void {
        $this->makeCoupon([
            'code' => 'ONESHOT',
            'discount_type' => 'fixed',
            'discount_value' => 5,
            'max_usages' => 1,
        ]);

        $cart = $this->makeItems(count: 1, price: 50.0);

        // First use — succeeds
        app(Discountify::class)
            ->setItems($cart)
            ->applyCoupon('ONESHOT')
            ->forUser(userId: 1)
            ->checkout();

        expect(CouponUsage::count())->toBe(1);

        // Second use — should throw
        expect(fn () => app(Discountify::class)
            ->setItems($cart)
            ->applyCoupon('ONESHOT')
            ->forUser(userId: 2)
            ->checkout()
        )->toThrow(CouponException::class, 'maximum usage');
    });

    // ── Scenario 5: Per-user coupon limit ─────────────────────────────────────

    it('Scenario: per-user coupon limit blocks second use by same user', function (): void {
        $this->makeCoupon([
            'code' => 'PERUSER',
            'discount_type' => 'fixed',
            'discount_value' => 5,
            'max_usages_per_user' => 1,
        ]);

        $cart = $this->makeItems(count: 1, price: 50.0);

        // User 10 — first use
        app(Discountify::class)
            ->setItems($cart)
            ->applyCoupon('PERUSER')
            ->forUser(userId: 10)
            ->checkout();

        // User 10 — second use — should fail
        expect(fn () => app(Discountify::class)
            ->setItems($cart)
            ->applyCoupon('PERUSER')
            ->forUser(userId: 10)
            ->checkout()
        )->toThrow(CouponException::class);

        // User 11 — should still work
        $result = app(Discountify::class)
            ->setItems($cart)
            ->applyCoupon('PERUSER')
            ->forUser(userId: 11)
            ->checkout();

        expect($result['coupon_discount'])->toBe(5.0);
    });

    // ── Scenario 6: Non-stackable promo blocks lower priority promo ────────────

    it('Scenario: non-stackable promo prevents lower-priority promos from applying', function (): void {
        $this->makePromo([
            'name' => 'Big Deal',
            'discount_type' => 'percentage',
            'discount_value' => 30,
            'is_stackable' => false,   // ← stops further stacking
            'priority' => 100,
        ]);

        $this->makePromo([
            'name' => 'Small Bonus',
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'is_stackable' => true,
            'priority' => 10,
        ]);

        $result = app(Discountify::class)
            ->setItems($this->makeItems(count: 1, price: 100.0))
            ->checkout();

        // Only Big Deal (30%) should be applied — Small Bonus is blocked
        expect($result['promo_discount'])->toBe(30.0)
            ->and($result['promos'])->toHaveCount(1)
            ->and($result['promos'][0]['name'])->toBe('Big Deal');
    });

    // ── Scenario 7: Promo with min_order_value not met ─────────────────────────

    it('Scenario: promo does not apply when cart is below minimum order value', function (): void {
        $this->makePromo([
            'name' => 'Century Deal',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'min_order_value' => 100.0,
        ]);

        // Cart total = £50, below min £100
        $result = app(Discountify::class)
            ->setItems($this->makeItems(count: 1, price: 50.0))
            ->checkout();

        expect($result['promo_discount'])->toBe(0.0);
    });

    // ── Scenario 8: DB condition drives discount ───────────────────────────────

    it('Scenario: DB condition triggers on high-value cart', function (): void {
        $this->makeCondition([
            'name' => 'Big spender',
            'slug' => 'big_spender',
            'field' => 'total',
            'operator' => 'gte',
            'value' => 300,
            'discount' => 10,
            'discount_type' => 'percentage',
            'is_active' => true,
        ]);

        $engine = new ConditionEngine;
        $engine->loadFromDatabase();

        $discountify = new Discountify(
            conditionEngine: $engine,
            couponEngine: new CouponEngine,
            promoEngine: new PromoEngine,
        );

        // £400 cart — condition passes
        $cart = $this->makeItems(count: 4, price: 100.0);
        $result = $discountify->setItems($cart)->checkout();

        expect($result['condition_discount'])->toBe(40.0)
            ->and($result['total'])->toBe(360.0);
    });

    // ── Scenario 9: Expired promo is silently skipped ──────────────────────────

    it('Scenario: expired promo is silently ignored', function (): void {
        $this->makePromo([
            'name' => 'Old Deal',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'ends_at' => now()->subDay(),
        ]);

        $result = app(Discountify::class)
            ->setItems($this->makeItems(count: 1, price: 100.0))
            ->checkout();

        expect($result['promo_discount'])->toBe(0.0);
    });

    // ── Scenario 10: Total never goes negative ─────────────────────────────────

    it('Scenario: massive discounts cannot push total below zero', function (): void {
        $this->makeCoupon([
            'code' => 'MEGADEAL',
            'discount_type' => 'fixed',
            'discount_value' => 999,
        ]);

        $result = app(Discountify::class)
            ->setItems($this->makeItems(count: 1, price: 10.0))
            ->setGlobalDiscount(50)
            ->applyCoupon('MEGADEAL')
            ->checkout();

        expect($result['total'])->toBe(0.0)
            ->and($result['total_with_tax'])->toBeGreaterThanOrEqual(0.0);
    });

});
