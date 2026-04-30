<?php

declare(strict_types=1);

use Safemood\Discountify\Enums\DiscountType;

describe('DiscountType enum', function (): void {

    it('has correct backing values', function (): void {
        expect(DiscountType::Percentage->value)->toBe('percentage')
            ->and(DiscountType::Fixed->value)->toBe('fixed');
    });

    it('returns correct labels', function (): void {
        expect(DiscountType::Percentage->label())->toBe('Percentage (%)')
            ->and(DiscountType::Fixed->label())->toBe('Fixed amount');
    });

    it('calculates percentage discount correctly', function (): void {
        $result = DiscountType::Percentage->calculate(value: 10, orderTotal: 200.0);
        expect($result)->toBe(20.0);
    });

    it('calculates fixed discount correctly', function (): void {
        $result = DiscountType::Fixed->calculate(value: 15, orderTotal: 200.0);
        expect($result)->toBe(15.0);
    });

    it('caps fixed discount at order total', function (): void {
        $result = DiscountType::Fixed->calculate(value: 500, orderTotal: 100.0);
        expect($result)->toBe(100.0);
    });

    it('applies maxDiscount cap on percentage', function (): void {
        // 50% of 200 = 100, but capped at 30
        $result = DiscountType::Percentage->calculate(value: 50, orderTotal: 200.0, maxDiscount: 30.0);
        expect($result)->toBe(30.0);
    });

    it('does not apply cap when discount is below max', function (): void {
        $result = DiscountType::Percentage->calculate(value: 10, orderTotal: 100.0, maxDiscount: 50.0);
        expect($result)->toBe(10.0);
    });

    it('converts percentage to percentage', function (): void {
        expect(DiscountType::Percentage->toPercentage(15, 200.0))->toBe(15.0);
    });

    it('converts fixed to percentage of subtotal', function (): void {
        // £20 off a £200 order = 10%
        expect(DiscountType::Fixed->toPercentage(20, 200.0))->toBe(10.0);
    });

    it('returns 0 when subtotal is zero for toPercentage', function (): void {
        expect(DiscountType::Fixed->toPercentage(20, 0.0))->toBe(0.0);
    });

    it('can be created from string via from()', function (): void {
        expect(DiscountType::from('percentage'))->toBe(DiscountType::Percentage)
            ->and(DiscountType::from('fixed'))->toBe(DiscountType::Fixed);
    });

    it('throws on invalid value', function (): void {
        expect(fn () => DiscountType::from('invalid'))->toThrow(ValueError::class);
    });

});
