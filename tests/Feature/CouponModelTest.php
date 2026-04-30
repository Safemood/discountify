<?php

declare(strict_types=1);

use Carbon\Carbon;
use Safemood\Discountify\Enums\DiscountType;
use Safemood\Discountify\Models\Coupon;

describe('Coupon model', function (): void {

    // ── Casts ─────────────────────────────────────────────────────────────────

    it('casts discount_type to DiscountType enum', function (): void {
        $c = $this->makeCoupon(['discount_type' => 'fixed']);
        expect($c->fresh()->discount_type)->toBe(DiscountType::Fixed);
    });

    it('casts starts_at and expires_at to Carbon', function (): void {
        $c = $this->makeCoupon(['starts_at' => now()->subDay(), 'expires_at' => now()->addDay()]);
        expect($c->fresh()->starts_at)->toBeInstanceOf(Carbon::class)
            ->and($c->fresh()->expires_at)->toBeInstanceOf(Carbon::class);
    });

    // ── Scopes ────────────────────────────────────────────────────────────────

    it('scopeActive filters inactive coupons', function (): void {
        $this->makeCoupon(['code' => 'ACTIVE', 'is_active' => true]);
        $this->makeCoupon(['code' => 'DEAD',   'is_active' => false]);

        expect(Coupon::active()->count())->toBe(1);
    });

    it('scopeByCode is case-insensitive', function (): void {
        $this->makeCoupon(['code' => 'SUMMER10']);

        expect(Coupon::byCode('summer10')->exists())->toBeTrue()
            ->and(Coupon::byCode('SUMMER10')->exists())->toBeTrue();
    });

    // ── isValid() ─────────────────────────────────────────────────────────────

    it('is valid when active with no date restrictions', function (): void {
        $c = $this->makeCoupon();
        expect($c->isValid())->toBeTrue();
    });

    it('is invalid when is_active is false', function (): void {
        $c = $this->makeCoupon(['is_active' => false]);
        expect($c->isValid())->toBeFalse();
    });

    it('is invalid when not yet started', function (): void {
        $c = $this->makeCoupon(['starts_at' => now()->addHour()]);
        expect($c->isValid())->toBeFalse();
    });

    it('is invalid when expired', function (): void {
        $c = $this->makeCoupon(['expires_at' => now()->subHour()]);
        expect($c->isValid())->toBeFalse();
    });

    it('is valid within its date window', function (): void {
        $c = $this->makeCoupon(['starts_at' => now()->subDay(), 'expires_at' => now()->addDay()]);
        expect($c->isValid())->toBeTrue();
    });

    // ── hasUsagesLeft() ───────────────────────────────────────────────────────

    it('has usages left when max_usages is null', function (): void {
        $c = $this->makeCoupon(['max_usages' => null]);
        expect($c->hasUsagesLeft())->toBeTrue();
    });

    it('has usages left when under the limit', function (): void {
        $c = $this->makeCoupon(['max_usages' => 5]);
        $c->recordUsage(userId: 1, discountAmount: 10.0);

        expect($c->hasUsagesLeft())->toBeTrue();
    });

    it('has no usages left when at the limit', function (): void {
        $c = $this->makeCoupon(['max_usages' => 1]);
        $c->recordUsage(userId: 1, discountAmount: 10.0);

        expect($c->hasUsagesLeft())->toBeFalse();
    });

    // ── canBeUsedByUser() ─────────────────────────────────────────────────────

    it('can be used by any user when user_id is null', function (): void {
        $c = $this->makeCoupon(['user_id' => null]);
        expect($c->canBeUsedByUser(userId: 42))->toBeTrue();
    });

    it('can only be used by the specific user when user_id is set', function (): void {
        $c = $this->makeCoupon(['user_id' => 10]);
        expect($c->canBeUsedByUser(userId: 10))->toBeTrue()
            ->and($c->canBeUsedByUser(userId: 99))->toBeFalse();
    });

    it('enforces max_usages_per_user', function (): void {
        $c = $this->makeCoupon(['max_usages_per_user' => 1]);
        $c->recordUsage(userId: 5, discountAmount: 10.0);

        expect($c->canBeUsedByUser(userId: 5))->toBeFalse()
            ->and($c->canBeUsedByUser(userId: 6))->toBeTrue();
    });

    // ── calculateDiscount() ───────────────────────────────────────────────────

    it('calculates percentage discount', function (): void {
        $c = $this->makeCoupon(['discount_type' => 'percentage', 'discount_value' => 20]);
        expect($c->calculateDiscount(orderTotal: 100.0))->toBe(20.0);
    });

    it('calculates fixed discount', function (): void {
        $c = $this->makeCoupon(['discount_type' => 'fixed', 'discount_value' => 15]);
        expect($c->calculateDiscount(orderTotal: 100.0))->toBe(15.0);
    });

    it('applies max_discount cap on percentage', function (): void {
        $c = $this->makeCoupon(['discount_type' => 'percentage', 'discount_value' => 50, 'max_discount' => 25]);
        // 50% of 200 = 100, capped to 25
        expect($c->calculateDiscount(orderTotal: 200.0))->toBe(25.0);
    });

    it('caps fixed discount at order total', function (): void {
        $c = $this->makeCoupon(['discount_type' => 'fixed', 'discount_value' => 999]);
        expect($c->calculateDiscount(orderTotal: 50.0))->toBe(50.0);
    });

    // ── recordUsage() ─────────────────────────────────────────────────────────

    it('records a usage row', function (): void {
        $c = $this->makeCoupon();
        $usage = $c->recordUsage(userId: 7, discountAmount: 12.50);

        expect($usage->coupon_id)->toBe($c->id)
            ->and($usage->user_id)->toBe(7)
            ->and($usage->discount_amount)->toBe(12.50)
            ->and($c->usages()->count())->toBe(1);
    });

});
