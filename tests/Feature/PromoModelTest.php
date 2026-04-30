<?php

declare(strict_types=1);

use Safemood\Discountify\Models\Promo;

describe('Promo model', function (): void {

    // ── Scopes ────────────────────────────────────────────────────────────────

    it('scopeActive filters inactive promos', function (): void {
        $this->makePromo(['name' => 'Active', 'is_active' => true]);
        $this->makePromo(['name' => 'Inactive', 'is_active' => false]);

        expect(Promo::active()->count())->toBe(1);
    });

    it('scopeOrdered sorts by priority desc', function (): void {
        $this->makePromo(['name' => 'Low',  'priority' => 1]);
        $this->makePromo(['name' => 'High', 'priority' => 20]);

        expect(Promo::ordered()->first()->name)->toBe('High');
    });

    it('scopeCurrentlyRunning filters by date window', function (): void {
        $this->makePromo(['name' => 'Running', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $this->makePromo(['name' => 'Future',  'starts_at' => now()->addDay(), 'ends_at' => now()->addWeek()]);
        $this->makePromo(['name' => 'Past',    'starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()]);
        $this->makePromo(['name' => 'No dates']); // always running

        $running = Promo::currentlyRunning()->pluck('name');
        expect($running)->toContain('Running')
            ->and($running)->toContain('No dates')
            ->and($running)->not->toContain('Future')
            ->and($running)->not->toContain('Past');
    });

    // ── conditionsMet() ───────────────────────────────────────────────────────

    it('returns true when conditions are empty', function (): void {
        $p = $this->makePromo(['conditions' => null]);
        expect($p->conditionsMet([]))->toBeTrue();
    });

    it('passes when count gte condition is met', function (): void {
        $p = $this->makePromo(['conditions' => [
            ['field' => 'count', 'operator' => 'gte', 'value' => 2],
        ]]);

        $items = [['price' => 10, 'quantity' => 1], ['price' => 20, 'quantity' => 1]];
        expect($p->conditionsMet($items))->toBeTrue();
    });

    it('fails when count gte condition is not met', function (): void {
        $p = $this->makePromo(['conditions' => [
            ['field' => 'count', 'operator' => 'gte', 'value' => 5],
        ]]);

        $items = [['price' => 10, 'quantity' => 1]];
        expect($p->conditionsMet($items))->toBeFalse();
    });

    it('passes when total gte condition is met', function (): void {
        $p = $this->makePromo(['conditions' => [
            ['field' => 'total', 'operator' => 'gte', 'value' => 100],
        ]]);

        $items = [['price' => 60.0, 'quantity' => 2]]; // 120
        expect($p->conditionsMet($items))->toBeTrue();
    });

    it('passes all conditions in the rule set', function (): void {
        $p = $this->makePromo(['conditions' => [
            ['field' => 'count', 'operator' => 'gte', 'value' => 2],
            ['field' => 'total', 'operator' => 'gte', 'value' => 100],
        ]]);

        // 3 items, total = 150
        $items = array_fill(0, 3, ['price' => 50.0, 'quantity' => 1]);
        expect($p->conditionsMet($items))->toBeTrue();
    });

    it('fails if any condition in the rule set fails', function (): void {
        $p = $this->makePromo(['conditions' => [
            ['field' => 'count', 'operator' => 'gte', 'value' => 2],
            ['field' => 'total', 'operator' => 'gte', 'value' => 500],
        ]]);

        $items = [['price' => 50.0, 'quantity' => 1], ['price' => 50.0, 'quantity' => 1]]; // total = 100
        expect($p->conditionsMet($items))->toBeFalse();
    });

    // ── minOrderMet() ─────────────────────────────────────────────────────────

    it('passes when no min_order_value is set', function (): void {
        $p = $this->makePromo(['min_order_value' => null]);
        expect($p->minOrderMet(0))->toBeTrue();
    });

    it('passes when order total meets minimum', function (): void {
        $p = $this->makePromo(['min_order_value' => 50.0]);
        expect($p->minOrderMet(50.0))->toBeTrue()
            ->and($p->minOrderMet(100.0))->toBeTrue();
    });

    it('fails when order total is below minimum', function (): void {
        $p = $this->makePromo(['min_order_value' => 100.0]);
        expect($p->minOrderMet(99.99))->toBeFalse();
    });

    // ── calculateDiscount() ───────────────────────────────────────────────────

    it('calculates percentage promo discount', function (): void {
        $p = $this->makePromo(['discount_type' => 'percentage', 'discount_value' => 20]);
        expect($p->calculateDiscount(200.0))->toBe(40.0);
    });

    it('calculates fixed promo discount', function (): void {
        $p = $this->makePromo(['discount_type' => 'fixed', 'discount_value' => 10]);
        expect($p->calculateDiscount(200.0))->toBe(10.0);
    });

    it('records usage', function (): void {
        $p = $this->makePromo();
        $usage = $p->recordUsage(userId: 3, discountAmount: 5.0);

        expect($usage->promo_id)->toBe($p->id)
            ->and($p->usages()->count())->toBe(1);
    });

});
