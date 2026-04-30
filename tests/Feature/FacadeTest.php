<?php

declare(strict_types=1);

use Safemood\Discountify\Facades\Condition;
use Safemood\Discountify\Facades\Discountify;
use Safemood\Discountify\Support\ConditionEngine;

describe('Discountify facade', function (): void {

    it('resolves via facade', function (): void {
        expect(Discountify::getFacadeRoot())
            ->toBeInstanceOf(Safemood\Discountify\Discountify::class);
    });

    it('calculates subtotal via facade', function (): void {
        $result = Discountify::setItems([
            ['price' => 40.0, 'quantity' => 2],
            ['price' => 20.0, 'quantity' => 1],
        ])->subtotal();

        expect($result)->toBe(100.0);
    });

    it('calculates totalWithTax via facade', function (): void {
        $result = Discountify::setItems([['price' => 100.0, 'quantity' => 1]])
            ->setGlobalDiscount(10)
            ->setTaxRate(20)
            ->totalWithTax();

        // total = 90, tax = 18, totalWithTax = 108
        expect($result)->toBe(108.0);
    });

    it('can chain fluently via facade', function (): void {
        $result = Discountify::setItems([['price' => 50.0, 'quantity' => 2]])
            ->setGlobalDiscount(0)
            ->setTaxRate(0)
            ->total();

        expect($result)->toBe(100.0);
    });

    it('applies inline condition via facade define()', function (): void {
        $discount = Discountify::setItems([['price' => 100.0, 'quantity' => 1]])
            ->define([[
                'slug' => 'facade_cond',
                'condition' => fn ($i) => true,
                'discount' => 20,
                'type' => 'percentage',
            ]])
            ->totalDiscount();

        expect($discount)->toBe(20.0);
    });

    it('applies coupon via facade', function (): void {
        $this->makeCoupon(['code' => 'FAC10', 'discount_type' => 'percentage', 'discount_value' => 10]);

        $discount = Discountify::setItems([['price' => 100.0, 'quantity' => 1]])
            ->applyCoupon('FAC10')
            ->totalDiscount();

        expect($discount)->toBe(10.0);
    });

    it('exposes conditions() engine via facade', function (): void {
        expect(Discountify::conditions())->toBeInstanceOf(ConditionEngine::class);
    });

});

describe('Condition facade', function (): void {

    it('resolves via facade', function (): void {
        expect(Condition::getFacadeRoot())->toBeInstanceOf(ConditionEngine::class);
    });

    it('can add and evaluate conditions via facade', function (): void {
        Condition::flush()->add([[
            'slug' => 'facade_direct',
            'condition' => fn ($i) => true,
            'discount' => 10,
            'type' => 'percentage',
        ]]);

        $items = [['price' => 100.0, 'quantity' => 1]];
        $passing = Condition::evaluate($items);

        expect($passing)->toHaveCount(1);
    });

    it('can flush conditions via facade', function (): void {
        Condition::add([[
            'slug' => 'temp',
            'condition' => fn ($i) => true,
            'discount' => 5,
            'type' => 'percentage',
        ]]);

        Condition::flush();

        expect(Condition::all())->toBeEmpty();
    });

});
