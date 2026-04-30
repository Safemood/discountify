<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Safemood\Discountify\Discountify;
use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Events\PromoAppliedEvent;
use Safemood\Discountify\Exceptions\CouponException;
use Safemood\Discountify\Models\CouponUsage;
use Safemood\Discountify\Models\PromoUsage;
use Safemood\Discountify\Support\ConditionEngine;
use Safemood\Discountify\Support\CouponEngine;
use Safemood\Discountify\Support\PromoEngine;

// ── Factory helper ─────────────────────────────────────────────────────────────

function freshDiscountify(): Discountify
{
    return new Discountify(
        conditionEngine: new ConditionEngine,
        couponEngine: new CouponEngine,
        promoEngine: new PromoEngine,
    );
}

// ── subtotal() ─────────────────────────────────────────────────────────────────

describe('subtotal()', function (): void {

    it('calculates subtotal from price × quantity', function (): void {
        $d = freshDiscountify()->setItems([
            ['price' => 30.0, 'quantity' => 2],
            ['price' => 40.0, 'quantity' => 1],
        ]);

        expect($d->subtotal())->toBe(100.0);
    });

    it('defaults quantity to 1 when missing', function (): void {
        $d = freshDiscountify()->setItems([['price' => 50.0]]);

        expect($d->subtotal())->toBe(50.0);
    });

    it('returns 0 for an empty cart', function (): void {
        expect(freshDiscountify()->setItems([])->subtotal())->toBe(0.0);
    });

    it('respects custom field names via setFields()', function (): void {
        $d = freshDiscountify()
            ->setFields(price: 'unit_price', quantity: 'qty')
            ->setItems([['unit_price' => 25.0, 'qty' => 4]]);

        expect($d->subtotal())->toBe(100.0);
    });

    it('items are publicly readable', function (): void {
        $d = freshDiscountify()->setItems([['price' => 10.0, 'quantity' => 1]]);

        expect($d->items)->toHaveCount(1);
    });

});

// ── totalDiscount() ────────────────────────────────────────────────────────────

describe('totalDiscount()', function (): void {

    it('returns 0 when no discounts are configured', function (): void {
        $d = freshDiscountify()->setItems([['price' => 100.0, 'quantity' => 1]]);

        expect($d->totalDiscount())->toBe(0.0);
    });

    it('applies global discount', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 200.0, 'quantity' => 1]])
            ->setGlobalDiscount(10);

        expect($d->totalDiscount())->toBe(20.0);
    });

    it('applies a percentage inline condition discount', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->define([[
                'slug' => 'pct_cond',
                'condition' => fn ($i) => true,
                'discount' => 10,
                'type' => 'percentage',
            ]]);

        expect($d->totalDiscount())->toBe(10.0);
    });

    it('applies a fixed inline condition discount', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->define([[
                'slug' => 'fixed_cond',
                'condition' => fn ($i) => true,
                'discount' => 10,
                'type' => 'fixed',
            ]]);

        // £10 off £100 → 10% → £10
        expect($d->totalDiscount())->toBe(10.0);
    });

    it('stacks global and condition discounts', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(5)
            ->define([[
                'slug' => 'extra',
                'condition' => fn ($i) => true,
                'discount' => 5,
                'type' => 'percentage',
            ]]);

        expect($d->totalDiscount())->toBe(10.0);
    });

    it('applies promo discount automatically', function (): void {
        $this->makePromo(['discount_type' => 'percentage', 'discount_value' => 15]);

        $d = freshDiscountify()->setItems([['price' => 100.0, 'quantity' => 1]]);

        expect($d->totalDiscount())->toBe(15.0);
    });

    it('applies a coupon discount', function (): void {
        $this->makeCoupon(['code' => 'CPN20', 'discount_type' => 'percentage', 'discount_value' => 20]);

        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('CPN20');

        expect($d->totalDiscount())->toBe(20.0);
    });

    it('applies coupon on the remaining balance after other discounts', function (): void {
        $this->makeCoupon(['code' => 'FLAT10', 'discount_type' => 'fixed', 'discount_value' => 10]);

        // global 10% off £100 = £10 → remaining = £90; coupon £10 → total = £20
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(10)
            ->applyCoupon('FLAT10');

        expect($d->totalDiscount())->toBe(20.0);
    });

    it('silently ignores an invalid coupon during preview (no exception)', function (): void {
        $this->makeCoupon(['code' => 'DEAD', 'is_active' => false]);

        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('DEAD');

        expect($d->totalDiscount())->toBe(0.0);
    });

    it('does not record any usage during a preview call', function (): void {
        $this->makeCoupon(['code' => 'PREVIEW', 'discount_type' => 'fixed', 'discount_value' => 5]);
        $this->makePromo(['discount_type' => 'fixed', 'discount_value' => 5]);

        freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('PREVIEW')
            ->totalDiscount();

        expect(CouponUsage::count())->toBe(0)
            ->and(PromoUsage::count())->toBe(0);
    });

});

// ── total() / tax() / totalWithTax() ──────────────────────────────────────────

describe('total() / tax() / totalWithTax()', function (): void {

    it('total = subtotal − total discount', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(20);

        expect($d->total())->toBe(80.0);
    });

    it('total never goes below zero', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 10.0, 'quantity' => 1]])
            ->setGlobalDiscount(100)
            ->define([[
                'slug' => 'extra',
                'condition' => fn ($i) => true,
                'discount' => 50,
                'type' => 'percentage',
            ]]);

        expect($d->total())->toBe(0.0);
    });

    it('calculates tax on the discounted total', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(20)  // total = 80
            ->setTaxRate(10);        // tax   = 8

        expect($d->tax())->toBe(8.0);
    });

    it('calculates totalWithTax correctly', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(20)  // total = 80
            ->setTaxRate(25);        // tax   = 20 → final = 100

        expect($d->totalWithTax())->toBe(100.0);
    });

    it('tax is 0 when tax rate is 0', function (): void {
        expect(freshDiscountify()->setItems([['price' => 100.0, 'quantity' => 1]])->tax())->toBe(0.0);
    });

});

// ── checkout() ─────────────────────────────────────────────────────────────────

describe('checkout()', function (): void {

    it('returns the correct result keys', function (): void {
        $result = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->checkout();

        expect($result)->toBeValidCheckoutResult();
    });

    it('produces correct amounts with no discounts', function (): void {
        $r = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setTaxRate(10)
            ->checkout();

        expect($r['subtotal'])->toBe(100.0)
            ->and($r['total_discount'])->toBe(0.0)
            ->and($r['total'])->toBe(100.0)
            ->and($r['tax'])->toBe(10.0)
            ->and($r['total_with_tax'])->toBe(110.0);
    });

    it('breaks down each discount source correctly', function (): void {
        $this->makeCoupon(['code' => 'C5', 'discount_type' => 'fixed', 'discount_value' => 5]);
        $this->makePromo(['discount_type' => 'fixed', 'discount_value' => 10]);

        $r = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(5)
            ->define([[
                'slug' => 'cond10',
                'condition' => fn ($i) => true,
                'discount' => 10,
                'type' => 'percentage',
            ]])
            ->applyCoupon('C5')
            ->checkout();

        expect($r['global_discount'])->toBe(5.0)
            ->and($r['condition_discount'])->toBe(10.0)
            ->and($r['promo_discount'])->toBe(10.0)
            ->and($r['coupon_discount'])->toBe(5.0)
            ->and($r['total_discount'])->toBe(30.0)
            ->and($r['total'])->toBe(70.0);
    });

    it('records coupon usage on checkout', function (): void {
        $this->makeCoupon(['code' => 'USEONCE', 'discount_type' => 'fixed', 'discount_value' => 5]);

        freshDiscountify()
            ->setItems([['price' => 50.0, 'quantity' => 1]])
            ->applyCoupon('USEONCE')
            ->forUser(userId: 1)
            ->checkout();

        expect(CouponUsage::count())->toBe(1)
            ->and(CouponUsage::first()->user_id)->toBe(1);
    });

    it('records promo usage on checkout', function (): void {
        $this->makePromo(['discount_type' => 'fixed', 'discount_value' => 5]);

        freshDiscountify()
            ->setItems([['price' => 50.0, 'quantity' => 1]])
            ->forUser(userId: 2)
            ->checkout();

        expect(PromoUsage::count())->toBe(1)
            ->and(PromoUsage::first()->user_id)->toBe(2);
    });

    it('throws CouponException on checkout with an invalid coupon', function (): void {
        $this->makeCoupon(['code' => 'NOPE', 'is_active' => false]);

        expect(fn () => freshDiscountify()
            ->setItems([['price' => 50.0, 'quantity' => 1]])
            ->applyCoupon('NOPE')
            ->checkout()
        )->toThrow(CouponException::class);
    });

    it('applies coupon discount on the remaining balance after other discounts', function (): void {
        $this->makeCoupon(['code' => 'PCT10', 'discount_type' => 'percentage', 'discount_value' => 10]);

        // 20% global off £100 → remaining = £80; coupon 10% of £80 = £8
        $r = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(20)
            ->applyCoupon('PCT10')
            ->checkout();

        expect($r['global_discount'])->toBe(20.0)
            ->and($r['coupon_discount'])->toBe(8.0)
            ->and($r['total'])->toBe(72.0);
    });

    it('applies both promo and coupon together', function (): void {
        $this->makePromo(['discount_type' => 'fixed', 'discount_value' => 20]);
        $this->makeCoupon(['code' => 'TEN', 'discount_type' => 'percentage', 'discount_value' => 10]);

        // promo £20 → remaining £80; coupon 10% of £80 = £8 → total = £72
        $r = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('TEN')
            ->checkout();

        expect($r['promo_discount'])->toBe(20.0)
            ->and($r['coupon_discount'])->toBe(8.0)
            ->and($r['total'])->toBe(72.0);
    });

});

// ── Events ─────────────────────────────────────────────────────────────────────

describe('events', function (): void {

    it('fires DiscountAppliedEvent when a condition produces a discount', function (): void {
        Event::fake();

        freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->define([[
                'slug' => 'event_cond',
                'condition' => fn ($i) => true,
                'discount' => 10,
                'type' => 'percentage',
            ]])
            ->checkout();

        Event::assertDispatched(DiscountAppliedEvent::class, fn ($e): bool => $e->discountAmount === 10.0);
    });

    it('does not fire DiscountAppliedEvent when no condition discount applies', function (): void {
        Event::fake();

        freshDiscountify()->setItems([['price' => 100.0, 'quantity' => 1]])->checkout();

        Event::assertNotDispatched(DiscountAppliedEvent::class);
    });

    it('fires CouponAppliedEvent on checkout with a valid coupon', function (): void {
        Event::fake();

        $this->makeCoupon(['code' => 'EV10', 'discount_type' => 'percentage', 'discount_value' => 10]);

        freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('EV10')
            ->checkout();

        Event::assertDispatched(CouponAppliedEvent::class, fn ($e): bool => $e->coupon->code === 'EV10' && $e->discount === 10.0
        );
    });

    it('fires PromoAppliedEvent when a promo is applied', function (): void {
        Event::fake();

        $this->makePromo(['name' => 'Flash', 'discount_type' => 'fixed', 'discount_value' => 15]);

        freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->checkout();

        Event::assertDispatched(PromoAppliedEvent::class, fn ($e): bool => $e->discount === 15.0);
    });

    it('suppresses all events when fire_events config is false', function (): void {
        Event::fake();
        config(['discountify.fire_events' => false]);

        $this->makeCoupon(['code' => 'NOEVENT', 'discount_type' => 'fixed', 'discount_value' => 5]);
        $this->makePromo(['discount_type' => 'fixed', 'discount_value' => 5]);

        freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->define([[
                'slug' => 'noevent_cond',
                'condition' => fn ($i) => true,
                'discount' => 10,
                'type' => 'percentage',
            ]])
            ->applyCoupon('NOEVENT')
            ->checkout();

        Event::assertNotDispatched(DiscountAppliedEvent::class);
        Event::assertNotDispatched(CouponAppliedEvent::class);
        Event::assertNotDispatched(PromoAppliedEvent::class);
    });

});

// ── Fluent API ─────────────────────────────────────────────────────────────────

describe('fluent API', function (): void {

    it('setItems() returns the same instance', function (): void {
        $d = freshDiscountify();
        expect($d->setItems([]))->toBe($d);
    });

    it('setGlobalDiscount() returns the same instance', function (): void {
        $d = freshDiscountify();
        expect($d->setGlobalDiscount(10))->toBe($d);
    });

    it('setTaxRate() returns the same instance', function (): void {
        $d = freshDiscountify();
        expect($d->setTaxRate(20))->toBe($d);
    });

    it('applyCoupon() returns the same instance', function (): void {
        $d = freshDiscountify();
        expect($d->applyCoupon('X'))->toBe($d);
    });

    it('removeCoupon() clears the active coupon', function (): void {
        $d = freshDiscountify()->applyCoupon('X')->removeCoupon();
        expect($d->coupons()->hasCoupon())->toBeFalse();
    });

    it('skipCondition() prevents a named condition from contributing', function (): void {
        $d = freshDiscountify()
            ->setItems([['price' => 100.0, 'quantity' => 1]])
            ->define([[
                'slug' => 'skip_me',
                'condition' => fn ($i) => true,
                'discount' => 50,
                'type' => 'percentage',
            ]])
            ->skipCondition('skip_me');

        expect($d->totalDiscount())->toBe(0.0);
    });

    it('conditions() returns ConditionEngine', function (): void {
        expect(freshDiscountify()->conditions())->toBeInstanceOf(ConditionEngine::class);
    });

    it('coupons() returns CouponEngine', function (): void {
        expect(freshDiscountify()->coupons())->toBeInstanceOf(CouponEngine::class);
    });

    it('promos() returns PromoEngine', function (): void {
        expect(freshDiscountify()->promos())->toBeInstanceOf(PromoEngine::class);
    });

});
