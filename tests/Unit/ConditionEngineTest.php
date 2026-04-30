<?php

declare(strict_types=1);

use Safemood\Discountify\Enums\DiscountType;
use Safemood\Discountify\Support\ConditionEngine;

describe('ConditionEngine', function (): void {

    beforeEach(function (): void {
        $this->engine = new ConditionEngine;
        $this->items = [
            ['price' => 100.0, 'quantity' => 2],
            ['price' => 50.0,  'quantity' => 1],
        ];
    });

    // ── Registration ──────────────────────────────────────────────────────────

    it('registers inline conditions via add()', function (): void {
        $this->engine->add([
            ['slug' => 'test', 'condition' => fn ($i) => true, 'discount' => 10, 'type' => 'percentage'],
        ]);

        expect($this->engine->all())->toHaveCount(1);
    });

    it('registers multiple conditions at once', function (): void {
        $this->engine->add([
            ['slug' => 'a', 'condition' => fn ($i) => true, 'discount' => 5, 'type' => 'percentage'],
            ['slug' => 'b', 'condition' => fn ($i) => true, 'discount' => 10, 'type' => 'percentage'],
        ]);

        expect($this->engine->all())->toHaveCount(2);
    });

    it('registers a condition class via addClassCondition()', function (): void {
        $class = new class
        {
            public string $slug = 'my_class';

            public float $discount = 15.0;

            public string $type = 'percentage';

            public bool $skip = false;

            public int $priority = 0;

            public function __invoke(array $items): bool
            {
                return true;
            }
        };

        $this->engine->addClassCondition($class);

        expect($this->engine->all())->toHaveCount(1)
            ->and($this->engine->all()[0]['slug'])->toBe('my_class');
    });

    it('flushes all conditions', function (): void {
        $this->engine->add([
            ['slug' => 'x', 'condition' => fn ($i) => true, 'discount' => 5, 'type' => 'percentage'],
        ]);

        $this->engine->flush();

        expect($this->engine->all())->toBeEmpty();
    });

    // ── Skip ─────────────────────────────────────────────────────────────────

    it('skips a condition by slug', function (): void {
        $this->engine->add([
            ['slug' => 'skip_me', 'condition' => fn ($i) => true, 'discount' => 10, 'type' => 'percentage'],
            ['slug' => 'keep_me', 'condition' => fn ($i) => true, 'discount' => 5,  'type' => 'percentage'],
        ]);

        $this->engine->skip('skip_me');

        $passing = $this->engine->evaluate($this->items);

        expect($passing)->toHaveCount(1)
            ->and($passing->first()['slug'])->toBe('keep_me');
    });

    // ── Evaluation ────────────────────────────────────────────────────────────

    it('returns only passing conditions', function (): void {
        $this->engine->add([
            ['slug' => 'pass', 'condition' => fn ($i) => true,  'discount' => 10, 'type' => 'percentage'],
            ['slug' => 'fail', 'condition' => fn ($i) => false, 'discount' => 5,  'type' => 'percentage'],
        ]);

        $passing = $this->engine->evaluate($this->items);

        expect($passing)->toHaveCount(1)
            ->and($passing->first()['slug'])->toBe('pass');
    });

    it('evaluates in priority order (highest first)', function (): void {
        $this->engine->add([
            ['slug' => 'low',  'condition' => fn ($i) => true, 'discount' => 5,  'type' => 'percentage', 'priority' => 1],
            ['slug' => 'high', 'condition' => fn ($i) => true, 'discount' => 10, 'type' => 'percentage', 'priority' => 10],
        ]);

        $passing = $this->engine->evaluate($this->items);

        expect($passing->first()['slug'])->toBe('high');
    });

    it('returns empty collection when no conditions pass', function (): void {
        $this->engine->add([
            ['slug' => 'fail', 'condition' => fn ($i) => false, 'discount' => 10, 'type' => 'percentage'],
        ]);

        expect($this->engine->evaluate($this->items))->toBeEmpty();
    });

    // ── totalDiscount ─────────────────────────────────────────────────────────

    it('sums percentage discounts correctly', function (): void {
        $this->engine->add([
            ['slug' => 'a', 'condition' => fn ($i) => true, 'discount' => 10, 'type' => 'percentage'],
            ['slug' => 'b', 'condition' => fn ($i) => true, 'discount' => 5,  'type' => 'percentage'],
        ]);

        // 10 + 5 = 15%
        expect($this->engine->totalDiscount($this->items, 250.0))->toBe(15.0);
    });

    it('converts fixed discount to percentage when summing', function (): void {
        $this->engine->add([
            // £25 fixed off a £250 subtotal = 10%
            ['slug' => 'fixed', 'condition' => fn ($i) => true, 'discount' => 25, 'type' => 'fixed'],
        ]);

        expect($this->engine->totalDiscount($this->items, 250.0))->toBe(10.0);
    });

    it('returns 0 when no conditions pass', function (): void {
        $this->engine->add([
            ['slug' => 'no', 'condition' => fn ($i) => false, 'discount' => 20, 'type' => 'percentage'],
        ]);

        expect($this->engine->totalDiscount($this->items, 250.0))->toBe(0.0);
    });

    // ── Normalisation ─────────────────────────────────────────────────────────

    it('normalises type string to DiscountType enum', function (): void {
        $this->engine->add([
            ['slug' => 'e', 'condition' => fn ($i) => true, 'discount' => 5, 'type' => 'percentage'],
        ]);

        expect($this->engine->all()[0]['type'])
            ->toBeInstanceOf(DiscountType::class);
    });

    it('defaults skip to false', function (): void {
        $this->engine->add([
            ['slug' => 'x', 'condition' => fn ($i) => true, 'discount' => 5, 'type' => 'percentage'],
        ]);

        expect($this->engine->all()[0]['skip'])->toBeFalse();
    });

    it('defaults priority to 0', function (): void {
        $this->engine->add([
            ['slug' => 'x', 'condition' => fn ($i) => true, 'discount' => 5, 'type' => 'percentage'],
        ]);

        expect($this->engine->all()[0]['priority'])->toBe(0);
    });

});
