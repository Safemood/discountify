<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Safemood\Discountify\DiscountifyServiceProvider;
use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\PromoAppliedEvent;
use Safemood\Discountify\Exceptions\CouponException;
use Safemood\Discountify\Facades\Condition;
use Safemood\Discountify\Facades\Discountify as DiscountifyFacade;
use Safemood\Discountify\Models\Setting;

describe('Discountify README use cases and database support', function (): void {

    it('sets items, global discount, and tax rate then calculates totals', function (): void {
        $items = [
            ['id' => '1', 'quantity' => 2, 'price' => 50.0],
            ['id' => '2', 'quantity' => 1, 'price' => 100.0, 'type' => 'special'],
        ];

        $result = DiscountifyFacade::setItems($items)
            ->setGlobalDiscount(15)
            ->setTaxRate(19)
            ->checkout();

        expect($result['subtotal'])->toBe(200.0)
            ->and($result['global_discount'])->toBe(30.0)
            ->and($result['total'])->toBe(170.0)
            ->and($result['tax'])->toBe(32.3)
            ->and($result['total_with_tax'])->toBe(202.3);
    });

    it('supports dynamic field names through setFields()', function (): void {
        $items = [
            ['id' => 'item1', 'qty' => 2, 'amount' => 20.0],
            ['id' => 'item2', 'qty' => 1, 'amount' => 20.0],
        ];

        $result = DiscountifyFacade::setFields('amount', 'qty')
            ->setItems($items)
            ->setGlobalDiscount(10)
            ->checkout();

        expect($result['subtotal'])->toBe(60.0)
            ->and($result['global_discount'])->toBe(6.0)
            ->and($result['total'])->toBe(54.0);
    });

    it('skips a defined condition when skip is true', function (): void {
        Condition::add([
            [
                'slug' => 'always_skip',
                'condition' => fn (array $items) => true,
                'discount' => 50,
                'type' => 'percentage',
                'skip' => true,
            ],
        ]);

        $result = DiscountifyFacade::setItems([['price' => 100.0, 'quantity' => 1]])
            ->checkout();

        expect($result['condition_discount'])->toBe(0.0);
    });

    it('dispatches promo and coupon events when enabled', function (): void {
        Event::fake();

        $this->makePromo([
            'name' => 'Event Promo',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_stackable' => true,
        ]);

        $this->makeCoupon([
            'code' => 'EVENT10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addDay(),
        ]);

        DiscountifyFacade::setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('EVENT10')
            ->checkout();

        Event::assertDispatched(CouponAppliedEvent::class);
        Event::assertDispatched(PromoAppliedEvent::class);
    });

    it('applies a period-limited coupon only during its valid window', function (): void {
        $this->makeCoupon([
            'code' => 'WINDOW20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'starts_at' => Carbon::now()->subDay(),
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $result = DiscountifyFacade::setItems([['price' => 200.0, 'quantity' => 1]])
            ->applyCoupon('WINDOW20')
            ->checkout();

        expect($result['coupon_discount'])->toBe(40.0);
    });

    it('prevents reuse of a single-use coupon', function (): void {
        $this->makeCoupon([
            'code' => 'ONEUSE',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'max_usages' => 1,
            'max_usages_per_user' => 1,
        ]);

        DiscountifyFacade::setItems([['price' => 50.0, 'quantity' => 1]])
            ->applyCoupon('ONEUSE')
            ->forUser(userId: 1)
            ->checkout();

        expect(fn () => DiscountifyFacade::setItems([['price' => 50.0, 'quantity' => 1]])
            ->applyCoupon('ONEUSE')
            ->forUser(userId: 2)
            ->checkout())->toThrow(CouponException::class);
    });

    it('restricts coupon usage to a specific user id', function (): void {
        $this->makeCoupon([
            'code' => 'USER20',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'user_id' => 123,
        ]);

        expect(fn () => DiscountifyFacade::setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('USER20')
            ->forUser(userId: 789)
            ->checkout())->toThrow(CouponException::class);

        $result = DiscountifyFacade::setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('USER20')
            ->forUser(userId: 123)
            ->checkout();

        expect($result['coupon_discount'])->toBe(20.0);
    });

    it('enforces coupon max usage limits across users', function (): void {
        $this->makeCoupon([
            'code' => 'LIMITED30',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'max_usages' => 1,
        ]);

        DiscountifyFacade::setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('LIMITED30')
            ->forUser(userId: 1)
            ->checkout();

        expect(fn () => DiscountifyFacade::setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('LIMITED30')
            ->forUser(userId: 2)
            ->checkout())->toThrow(CouponException::class);
    });

    it('loads global discount and tax rate from the database via persisted settings', function (): void {
        Setting::create(['key' => 'global_discount', 'value' => '8']);
        Setting::create(['key' => 'global_tax_rate', 'value' => '10']);

        $this->app['config']->set('discountify.database_driver', true);

        $provider = new DiscountifyServiceProvider($this->app);
        $provider->boot();

        $checkout = DiscountifyFacade::setItems([['price' => 100.0, 'quantity' => 1]])
            ->checkout();

        expect($checkout['global_discount'])->toBe(8.0)
            ->and($checkout['tax'])->toBe(9.2);
    });

});
