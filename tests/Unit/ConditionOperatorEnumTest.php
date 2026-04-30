<?php

declare(strict_types=1);

use Safemood\Discountify\Enums\ConditionOperator;

describe('ConditionOperator enum', function (): void {

    it('has correct backing values', function (): void {
        expect(ConditionOperator::GreaterThan->value)->toBe('gt')
            ->and(ConditionOperator::GreaterThanOrEqual->value)->toBe('gte')
            ->and(ConditionOperator::LessThan->value)->toBe('lt')
            ->and(ConditionOperator::LessThanOrEqual->value)->toBe('lte')
            ->and(ConditionOperator::Equal->value)->toBe('eq')
            ->and(ConditionOperator::NotEqual->value)->toBe('neq')
            ->and(ConditionOperator::In->value)->toBe('in')
            ->and(ConditionOperator::NotIn->value)->toBe('nin');
    });

    it('evaluates gt correctly', function (): void {
        expect(ConditionOperator::GreaterThan->evaluate(5, 3))->toBeTrue()
            ->and(ConditionOperator::GreaterThan->evaluate(3, 5))->toBeFalse()
            ->and(ConditionOperator::GreaterThan->evaluate(3, 3))->toBeFalse();
    });

    it('evaluates gte correctly', function (): void {
        expect(ConditionOperator::GreaterThanOrEqual->evaluate(3, 3))->toBeTrue()
            ->and(ConditionOperator::GreaterThanOrEqual->evaluate(4, 3))->toBeTrue()
            ->and(ConditionOperator::GreaterThanOrEqual->evaluate(2, 3))->toBeFalse();
    });

    it('evaluates lt correctly', function (): void {
        expect(ConditionOperator::LessThan->evaluate(2, 5))->toBeTrue()
            ->and(ConditionOperator::LessThan->evaluate(5, 2))->toBeFalse();
    });

    it('evaluates lte correctly', function (): void {
        expect(ConditionOperator::LessThanOrEqual->evaluate(3, 3))->toBeTrue()
            ->and(ConditionOperator::LessThanOrEqual->evaluate(2, 3))->toBeTrue()
            ->and(ConditionOperator::LessThanOrEqual->evaluate(4, 3))->toBeFalse();
    });

    it('evaluates eq correctly', function (): void {
        expect(ConditionOperator::Equal->evaluate(5, 5))->toBeTrue()
            ->and(ConditionOperator::Equal->evaluate(5, 6))->toBeFalse();
    });

    it('evaluates neq correctly', function (): void {
        expect(ConditionOperator::NotEqual->evaluate(5, 6))->toBeTrue()
            ->and(ConditionOperator::NotEqual->evaluate(5, 5))->toBeFalse();
    });

    it('evaluates in correctly', function (): void {
        expect(ConditionOperator::In->evaluate('shoes', ['shoes', 'bags']))->toBeTrue()
            ->and(ConditionOperator::In->evaluate('hats', ['shoes', 'bags']))->toBeFalse();
    });

    it('evaluates nin correctly', function (): void {
        expect(ConditionOperator::NotIn->evaluate('hats', ['shoes', 'bags']))->toBeTrue()
            ->and(ConditionOperator::NotIn->evaluate('shoes', ['shoes', 'bags']))->toBeFalse();
    });

    it('can be created from string', function (): void {
        expect(ConditionOperator::from('gte'))->toBe(ConditionOperator::GreaterThanOrEqual);
    });

    it('returns labels', function (): void {
        expect(ConditionOperator::In->label())->toBe('In list');
    });

});
