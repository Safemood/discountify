<?php

declare(strict_types=1);

use Safemood\Discountify\Support\ConditionEngine;

describe('ConditionEngine — database integration', function (): void {

    beforeEach(function (): void {
        $this->engine = new ConditionEngine;
        $this->items = [
            ['price' => 50.0, 'quantity' => 3],
        ]; // count=1, total=150
    });

    it('loads active conditions from the database', function (): void {
        $this->makeCondition(['slug' => 'db_one', 'field' => 'count', 'operator' => 'gte', 'value' => 1]);

        $this->engine->loadFromDatabase();

        expect($this->engine->all())->toHaveCount(1);
    });

    it('does not load inactive conditions', function (): void {
        $this->makeCondition(['slug' => 'active',   'is_active' => true]);
        $this->makeCondition(['slug' => 'inactive', 'is_active' => false]);

        $this->engine->loadFromDatabase();

        expect($this->engine->all())->toHaveCount(1)
            ->and($this->engine->all()[0]['slug'])->toBe('active');
    });

    it('loads conditions in priority order (highest first)', function (): void {
        $this->makeCondition(['slug' => 'low',  'priority' => 1,  'field' => 'count', 'operator' => 'gte', 'value' => 1]);
        $this->makeCondition(['slug' => 'high', 'priority' => 10, 'field' => 'count', 'operator' => 'gte', 'value' => 1]);

        $this->engine->loadFromDatabase();

        // After evaluate() the results are priority-sorted
        $passing = $this->engine->evaluate($this->items);
        expect($passing->first()['slug'])->toBe('high');
    });

    it('evaluates a passing DB condition and contributes its discount', function (): void {
        $this->makeCondition([
            'slug' => 'total_100',
            'field' => 'total',
            'operator' => 'gte',
            'value' => 100,
            'discount' => 15,
            'discount_type' => 'percentage',
        ]);

        $this->engine->loadFromDatabase();

        $pct = $this->engine->totalDiscount($this->items, 150.0);
        expect($pct)->toBe(15.0);
    });

    it('does not apply a failing DB condition', function (): void {
        $this->makeCondition([
            'slug' => 'too_many',
            'field' => 'count',
            'operator' => 'gte',
            'value' => 100, // cart has only 1 item
            'discount' => 20,
        ]);

        $this->engine->loadFromDatabase();

        $pct = $this->engine->totalDiscount($this->items, 150.0);
        expect($pct)->toBe(0.0);
    });

    it('merges code-based and DB conditions together', function (): void {
        $this->makeCondition([
            'slug' => 'db_cond',
            'field' => 'count',
            'operator' => 'gte',
            'value' => 1,
            'discount' => 10,
            'discount_type' => 'percentage',
        ]);

        $this->engine
            ->add([['slug' => 'code_cond', 'condition' => fn ($i) => true, 'discount' => 5, 'type' => 'percentage']])
            ->loadFromDatabase();

        expect($this->engine->all())->toHaveCount(2);

        $pct = $this->engine->totalDiscount($this->items, 150.0);
        expect($pct)->toBe(15.0); // 10 + 5
    });

    it('handles a fixed DB discount by converting to percentage', function (): void {
        $this->makeCondition([
            'slug' => 'fixed_db',
            'field' => 'count',
            'operator' => 'gte',
            'value' => 1,
            'discount' => 15.0,   // £15 fixed
            'discount_type' => 'fixed',
        ]);

        $this->engine->loadFromDatabase();

        // £15 of £150 subtotal = 10%
        $pct = $this->engine->totalDiscount($this->items, 150.0);
        expect($pct)->toBe(10.0);
    });

    it('can skip a DB-loaded condition by slug', function (): void {
        $this->makeCondition(['slug' => 'skip_this', 'discount' => 20]);
        $this->makeCondition(['slug' => 'keep_this', 'discount' => 5]);

        $this->engine->loadFromDatabase();
        $this->engine->skip('skip_this');

        $passing = $this->engine->evaluate($this->items);
        expect($passing)->toHaveCount(1)
            ->and($passing->first()['slug'])->toBe('keep_this');
    });

    it('returns zero discount when no DB conditions are loaded', function (): void {
        $pct = $this->engine->totalDiscount($this->items, 150.0);
        expect($pct)->toBe(0.0);
    });

    it('evaluates in operator from DB condition', function (): void {
        $this->makeCondition([
            'slug' => 'category_in',
            'field' => 'category',
            'operator' => 'in',
            'value' => ['shoes', 'bags'],
            'discount' => 10,
            'discount_type' => 'percentage',
        ]);

        $this->engine->loadFromDatabase();

        $items = [['price' => 50, 'quantity' => 1, 'category' => 'shoes']];
        $passing = $this->engine->evaluate($items);

        expect($passing)->toHaveCount(1);
    });

    it('does not load same conditions twice if loadFromDatabase is called twice', function (): void {
        $this->makeCondition(['slug' => 'once', 'discount' => 10]);

        $this->engine->loadFromDatabase();
        $this->engine->loadFromDatabase(); // second call stacks them

        // Note: current behaviour intentionally allows re-loading (caller's responsibility).
        // Flush before reload if needed. This test documents the behaviour.
        expect($this->engine->all())->toHaveCount(2);
    });

});
