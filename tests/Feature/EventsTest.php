<?php

declare(strict_types=1);

use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Events\PromoAppliedEvent;
use Safemood\Discountify\Models\Coupon;

describe('Event classes', function (): void {

    // ── DiscountAppliedEvent ───────────────────────────────────────────────────

    describe('DiscountAppliedEvent', function (): void {

        it('stores items and discountAmount', function (): void {
            $items = [['price' => 100.0, 'quantity' => 1]];
            $e = new DiscountAppliedEvent(items: $items, discountAmount: 15.0);

            expect($e->items)->toBe($items)
                ->and($e->discountAmount)->toBe(15.0)
                ->and($e->conditions)->toBe([]);
        });

        it('stores conditions when provided', function (): void {
            $conditions = [['slug' => 'big_cart', 'discount' => 10.0]];
            $e = new DiscountAppliedEvent(
                items: [['price' => 50.0, 'quantity' => 1]],
                discountAmount: 5.0,
                conditions: $conditions,
            );

            expect($e->conditions)->toBe($conditions);
        });

        it('is final', function (): void {
            $r = new ReflectionClass(DiscountAppliedEvent::class);
            expect($r->isFinal())->toBeTrue();
        });

        it('has readonly properties', function (): void {
            $r = new ReflectionClass(DiscountAppliedEvent::class);
            foreach ($r->getConstructor()->getParameters() as $param) {
                expect($r->getProperty($param->getName())->isReadOnly())->toBeTrue();
            }
        });

    });

    // ── CouponAppliedEvent ────────────────────────────────────────────────────

    describe('CouponAppliedEvent', function (): void {

        it('stores coupon model and discount', function (): void {
            $coupon = new Coupon(['code' => 'SAVE', 'name' => 'Save']);
            $items = [['price' => 50.0, 'quantity' => 1]];

            $e = new CouponAppliedEvent(items: $items, coupon: $coupon, discount: 10.0);

            expect($e->coupon)->toBe($coupon)
                ->and($e->discount)->toBe(10.0)
                ->and($e->items)->toBe($items);
        });

        it('is final', function (): void {
            $r = new ReflectionClass(CouponAppliedEvent::class);
            expect($r->isFinal())->toBeTrue();
        });

    });

    // ── PromoAppliedEvent ─────────────────────────────────────────────────────

    describe('PromoAppliedEvent', function (): void {

        it('stores promos array and total discount', function (): void {
            $promos = [['name' => 'Summer Sale', 'discount' => 15.0]];
            $items = [['price' => 100.0, 'quantity' => 1]];

            $e = new PromoAppliedEvent(items: $items, promos: $promos, discount: 15.0);

            expect($e->promos)->toBe($promos)
                ->and($e->discount)->toBe(15.0)
                ->and($e->items)->toBe($items);
        });

        it('is final', function (): void {
            $r = new ReflectionClass(PromoAppliedEvent::class);
            expect($r->isFinal())->toBeTrue();
        });

    });

});
