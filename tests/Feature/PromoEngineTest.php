<?php

declare(strict_types=1);

use Safemood\Discountify\Models\PromoUsage;
use Safemood\Discountify\Support\PromoEngine;

describe('PromoEngine', function (): void {

    beforeEach(function (): void {
        $this->engine = new PromoEngine;
        $this->items = [
            ['price' => 50.0, 'quantity' => 2],
            ['price' => 25.0, 'quantity' => 1],
        ]; // subtotal = 125
    });

    // ── eligiblePromos() ──────────────────────────────────────────────────────

    it('returns empty when no promos exist', function (): void {
        expect($this->engine->eligiblePromos($this->items, 125.0))->toBeEmpty();
    });

    it('returns active currently-running promos', function (): void {
        $this->makePromo(['name' => 'Active']);
        $this->makePromo(['name' => 'Inactive', 'is_active' => false]);

        expect($this->engine->eligiblePromos($this->items, 125.0))->toHaveCount(1);
    });

    it('excludes promos that have not yet started', function (): void {
        $this->makePromo(['name' => 'Future', 'starts_at' => now()->addDay()]);

        expect($this->engine->eligiblePromos($this->items, 125.0))->toBeEmpty();
    });

    it('excludes promos that have ended', function (): void {
        $this->makePromo(['name' => 'Past', 'ends_at' => now()->subDay()]);

        expect($this->engine->eligiblePromos($this->items, 125.0))->toBeEmpty();
    });

    it('excludes promos whose conditions are not met', function (): void {
        $this->makePromo([
            'name' => 'Big Cart',
            'conditions' => [['field' => 'count', 'operator' => 'gte', 'value' => 10]],
        ]);

        // only 2 items in cart
        expect($this->engine->eligiblePromos($this->items, 125.0))->toBeEmpty();
    });

    it('excludes promos below min_order_value', function (): void {
        $this->makePromo(['name' => 'Min500', 'min_order_value' => 500.0]);

        expect($this->engine->eligiblePromos($this->items, 125.0))->toBeEmpty();
    });

    it('excludes promos that have hit max_usages', function (): void {
        $promo = $this->makePromo(['name' => 'Limited', 'max_usages' => 1]);
        $promo->recordUsage(userId: 1, discountAmount: 5.0);

        expect($this->engine->eligiblePromos($this->items, 125.0))->toBeEmpty();
    });

    it('includes promos within their running window', function (): void {
        $this->makePromo([
            'name' => 'Running',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        expect($this->engine->eligiblePromos($this->items, 125.0))->toHaveCount(1);
    });

    it('sorts eligible promos by priority descending', function (): void {
        $this->makePromo(['name' => 'Low',  'priority' => 1]);
        $this->makePromo(['name' => 'High', 'priority' => 10]);

        $eligible = $this->engine->eligiblePromos($this->items, 125.0);

        expect($eligible->first()->name)->toBe('High');
    });

    // ── apply() — stacking ────────────────────────────────────────────────────

    it('applies a single stackable promo', function (): void {
        $this->makePromo([
            'name' => '10% Off',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_stackable' => true,
        ]);

        $result = $this->engine->apply($this->items, 125.0);

        expect($result['discount'])->toBe(12.50)
            ->and($result['promos'])->toHaveCount(1);
    });

    it('stacks multiple stackable promos', function (): void {
        $this->makePromo(['name' => '10%', 'discount_type' => 'percentage', 'discount_value' => 10, 'is_stackable' => true, 'priority' => 10]);
        $this->makePromo(['name' => '5%',  'discount_type' => 'percentage', 'discount_value' => 5,  'is_stackable' => true, 'priority' => 5]);

        $result = $this->engine->apply($this->items, 100.0);

        // 10% of 100 = 10, then 5% of (100-10) = 4.50 → total 14.50
        expect($result['promos'])->toHaveCount(2)
            ->and($result['discount'])->toBe(14.50);
    });

    it('stops stacking after a non-stackable promo', function (): void {
        $this->makePromo(['name' => 'Non-stack', 'discount_type' => 'percentage', 'discount_value' => 20, 'is_stackable' => false, 'priority' => 10]);
        $this->makePromo(['name' => 'Stack',     'discount_type' => 'percentage', 'discount_value' => 5,  'is_stackable' => true,  'priority' => 5]);

        $result = $this->engine->apply($this->items, 100.0);

        // Non-stackable is highest priority → only it is applied
        expect($result['promos'])->toHaveCount(1)
            ->and($result['promos'][0]['name'])->toBe('Non-stack')
            ->and($result['discount'])->toBe(20.0);
    });

    it('applies a fixed promo correctly', function (): void {
        $this->makePromo(['name' => 'Fixed', 'discount_type' => 'fixed', 'discount_value' => 15, 'is_stackable' => true]);

        $result = $this->engine->apply($this->items, 125.0);

        expect($result['discount'])->toBe(15.0);
    });

    it('returns zero discount when no promos are eligible', function (): void {
        $result = $this->engine->apply($this->items, 125.0);

        expect($result['discount'])->toBe(0.0)
            ->and($result['promos'])->toBeEmpty();
    });

    it('does not record usage in apply()', function (): void {
        $this->makePromo(['name' => 'NR', 'discount_type' => 'percentage', 'discount_value' => 10]);

        $this->engine->apply($this->items, 100.0);

        expect(PromoUsage::count())->toBe(0);
    });

    // ── redeem() ──────────────────────────────────────────────────────────────

    it('records usage for each applied promo on redeem()', function (): void {
        $this->makePromo(['name' => 'A', 'discount_type' => 'percentage', 'discount_value' => 10, 'is_stackable' => true]);
        $this->makePromo(['name' => 'B', 'discount_type' => 'percentage', 'discount_value' => 5,  'is_stackable' => true]);

        $this->engine->forUser(userId: 7)->redeem($this->items, 100.0);

        expect(PromoUsage::count())->toBe(2)
            ->and(PromoUsage::where('user_id', 7)->count())->toBe(2);
    });

    it('records correct discount_amount per promo usage', function (): void {
        $this->makePromo(['name' => 'P', 'discount_type' => 'fixed', 'discount_value' => 20]);

        $this->engine->redeem($this->items, 100.0);

        expect(PromoUsage::first()->discount_amount)->toBe(20.0);
    });

    it('returns same discount total from redeem as from apply', function (): void {
        $this->makePromo(['discount_type' => 'percentage', 'discount_value' => 10]);

        $preview = $this->engine->apply($this->items, 100.0)['discount'];
        $actual = $this->engine->redeem($this->items, 100.0)['discount'];

        expect($actual)->toBe($preview);
    });

    // ── forUser() ─────────────────────────────────────────────────────────────

    it('passes user id to usage records', function (): void {
        $this->makePromo(['name' => 'U', 'discount_type' => 'fixed', 'discount_value' => 5]);

        $this->engine->forUser(userId: 99)->redeem($this->items, 100.0);

        expect(PromoUsage::first()->user_id)->toBe(99);
    });

});
