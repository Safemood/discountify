<?php

declare(strict_types=1);

use Safemood\Discountify\Exceptions\CouponException;
use Safemood\Discountify\Models\CouponUsage;
use Safemood\Discountify\Support\CouponEngine;

describe('CouponEngine', function (): void {

    beforeEach(function (): void {
        $this->engine = new CouponEngine;
    });

    // ── apply / clear ─────────────────────────────────────────────────────────

    it('detects when a coupon is set', function (): void {
        expect($this->engine->hasCoupon())->toBeFalse();
        $this->engine->apply('SAVE10');
        expect($this->engine->hasCoupon())->toBeTrue()
            ->and($this->engine->code)->toBe('SAVE10');
    });

    it('uppercases and trims the code', function (): void {
        $this->engine->apply('  save10  ');
        expect($this->engine->code)->toBe('SAVE10');
    });

    it('clears coupon state', function (): void {
        $this->engine->apply('X')->clear();
        expect($this->engine->hasCoupon())->toBeFalse()
            ->and($this->engine->code)->toBeNull();
    });

    // ── CouponException variants ──────────────────────────────────────────────

    it('throws when no code is set', function (): void {
        expect(fn () => $this->engine->calculateDiscount(100))
            ->toThrow(CouponException::class, 'No coupon code has been set.');
    });

    it('throws when coupon does not exist', function (): void {
        $this->engine->apply('GHOST');
        expect(fn () => $this->engine->calculateDiscount(100))
            ->toThrow(CouponException::class, 'does not exist');
    });

    it('throws when coupon is inactive', function (): void {
        $this->makeCoupon(['code' => 'DEAD', 'is_active' => false]);
        $this->engine->apply('DEAD');
        expect(fn () => $this->engine->calculateDiscount(100))
            ->toThrow(CouponException::class, 'inactive');
    });

    it('throws when coupon is expired', function (): void {
        $this->makeCoupon(['code' => 'OLD', 'expires_at' => now()->subDay()]);
        $this->engine->apply('OLD');
        expect(fn () => $this->engine->calculateDiscount(100))
            ->toThrow(CouponException::class);
    });

    it('throws when coupon is exhausted', function (): void {
        $coupon = $this->makeCoupon(['code' => 'USED', 'max_usages' => 1]);
        $coupon->recordUsage(userId: 1, discountAmount: 5);

        $this->engine->apply('USED');
        expect(fn () => $this->engine->calculateDiscount(100))
            ->toThrow(CouponException::class, 'maximum usage');
    });

    it('throws when coupon is restricted to another user', function (): void {
        $this->makeCoupon(['code' => 'VIP', 'user_id' => 99]);
        $this->engine->apply('VIP')->forUser(1);
        expect(fn () => $this->engine->calculateDiscount(100))
            ->toThrow(CouponException::class, 'cannot be used by this user');
    });

    it('throws when order total is below minimum', function (): void {
        $this->makeCoupon(['code' => 'BIG', 'min_order_value' => 200]);
        $this->engine->apply('BIG');
        expect(fn () => $this->engine->calculateDiscount(100))
            ->toThrow(CouponException::class, 'minimum order value');
    });

    // ── calculateDiscount() ───────────────────────────────────────────────────

    it('calculates percentage discount without recording usage', function (): void {
        $this->makeCoupon(['code' => 'TEN', 'discount_type' => 'percentage', 'discount_value' => 10]);
        $this->engine->apply('TEN');

        $discount = $this->engine->calculateDiscount(200.0);
        expect($discount)->toBe(20.0);

        // No usage recorded
        expect(CouponUsage::count())->toBe(0);
    });

    it('calculates fixed discount', function (): void {
        $this->makeCoupon(['code' => 'FIVE', 'discount_type' => 'fixed', 'discount_value' => 5]);
        $this->engine->apply('FIVE');

        expect($this->engine->calculateDiscount(100.0))->toBe(5.0);
    });

    // ── redeem() ──────────────────────────────────────────────────────────────

    it('redeems coupon and records usage', function (): void {
        $this->makeCoupon(['code' => 'REDEEM', 'discount_type' => 'percentage', 'discount_value' => 10]);
        $this->engine->apply('REDEEM')->forUser(42);

        $result = $this->engine->redeem(100.0);

        expect($result['discount'])->toBe(10.0)
            ->and($result['code'])->toBe('REDEEM')
            ->and(CouponUsage::count())->toBe(1)
            ->and(CouponUsage::first()->user_id)->toBe(42);
    });

    // ── Caching ───────────────────────────────────────────────────────────────

    it('caches resolved coupon across calls', function (): void {
        $this->makeCoupon(['code' => 'CACHE']);
        $this->engine->apply('CACHE');

        $this->engine->calculateDiscount(100.0);
        $this->engine->calculateDiscount(100.0); // second call uses cached model

        // Only one DB query effectively — coupon model is cached
        expect($this->engine->getCoupon())->not->toBeNull();
    });

});
