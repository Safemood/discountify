<?php

declare(strict_types=1);

use Safemood\Discountify\Enums\ConditionOperator;
use Safemood\Discountify\Enums\DiscountType;
use Safemood\Discountify\Models\Condition;

describe('Condition model', function (): void {

    // ── Casts ─────────────────────────────────────────────────────────────────

    it('casts discount_type to DiscountType enum', function (): void {
        $c = $this->makeCondition(['discount_type' => 'percentage']);
        expect($c->fresh()->discount_type)->toBe(DiscountType::Percentage);
    });

    it('casts operator to ConditionOperator enum', function (): void {
        $c = $this->makeCondition(['operator' => 'gte']);
        expect($c->fresh()->operator)->toBe(ConditionOperator::GreaterThanOrEqual);
    });

    it('casts value as json', function (): void {
        $c = $this->makeCondition(['value' => [1, 2, 3]]);
        expect($c->fresh()->value)->toBe([1, 2, 3]);
    });

    // ── Scopes ────────────────────────────────────────────────────────────────

    it('scopeActive returns only active conditions', function (): void {
        $this->makeCondition(['slug' => 'active_one', 'is_active' => true]);
        $this->makeCondition(['slug' => 'inactive',   'is_active' => false]);

        expect(Condition::active()->count())->toBe(1);
    });

    it('scopeOrdered sorts by priority descending', function (): void {
        $this->makeCondition(['slug' => 'low',  'priority' => 1]);
        $this->makeCondition(['slug' => 'high', 'priority' => 10]);

        $first = Condition::ordered()->first();
        expect($first->slug)->toBe('high');
    });

    // ── evaluate() ────────────────────────────────────────────────────────────

    it('evaluates count gte condition — passes', function (): void {
        $c = $this->makeCondition(['field' => 'count', 'operator' => 'gte', 'value' => 2]);
        $items = [['price' => 10, 'quantity' => 1], ['price' => 20, 'quantity' => 1]];

        expect($c->evaluate($items))->toBeTrue();
    });

    it('evaluates count gte condition — fails', function (): void {
        $c = $this->makeCondition(['field' => 'count', 'operator' => 'gte', 'value' => 5]);
        $items = [['price' => 10, 'quantity' => 1]];

        expect($c->evaluate($items))->toBeFalse();
    });

    it('evaluates total gte condition', function (): void {
        $c = $this->makeCondition(['field' => 'total', 'operator' => 'gte', 'value' => 100]);
        $items = [['price' => 60.0, 'quantity' => 2]]; // total = 120

        expect($c->evaluate($items))->toBeTrue();
    });

    it('evaluates total lt condition', function (): void {
        $c = $this->makeCondition(['field' => 'total', 'operator' => 'lt', 'value' => 100]);
        $items = [['price' => 40.0, 'quantity' => 2]]; // total = 80

        expect($c->evaluate($items))->toBeTrue();
    });

    it('evaluates in operator against item field', function (): void {
        $c = $this->makeCondition([
            'field' => 'category',
            'operator' => 'in',
            'value' => ['shoes', 'bags'],
        ]);
        $items = [['price' => 50, 'quantity' => 1, 'category' => 'shoes']];

        expect($c->evaluate($items))->toBeTrue();
    });

    it('evaluates eq operator', function (): void {
        $c = $this->makeCondition(['field' => 'count', 'operator' => 'eq', 'value' => 1]);
        $items = [['price' => 10, 'quantity' => 1]];

        expect($c->evaluate($items))->toBeTrue();
    });

    it('evaluates neq operator', function (): void {
        $c = $this->makeCondition(['field' => 'count', 'operator' => 'neq', 'value' => 5]);
        $items = [['price' => 10, 'quantity' => 1]];

        expect($c->evaluate($items))->toBeTrue();
    });

});
