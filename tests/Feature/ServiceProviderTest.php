<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Safemood\Discountify\Discountify;
use Safemood\Discountify\Support\ConditionEngine;
use Safemood\Discountify\Support\CouponEngine;
use Safemood\Discountify\Support\PromoEngine;

describe('DiscountifyServiceProvider', function (): void {

    it('binds ConditionEngine as a singleton', function (): void {
        $a = app(ConditionEngine::class);
        $b = app(ConditionEngine::class);

        expect($a)->toBe($b);
    });

    it('binds CouponEngine as a singleton', function (): void {
        $a = app(CouponEngine::class);
        $b = app(CouponEngine::class);

        expect($a)->toBe($b);
    });

    it('binds PromoEngine as a singleton', function (): void {
        $a = app(PromoEngine::class);
        $b = app(PromoEngine::class);

        expect($a)->toBe($b);
    });

    it('binds Discountify', function (): void {
        $d = app(Discountify::class);
        expect($d)->toBeInstanceOf(Discountify::class);
    });

    it('Discountify instances share the same engine singletons', function (): void {
        $d1 = app(Discountify::class);
        $d2 = app(Discountify::class);

        expect($d1->conditions())->toBe($d2->conditions())
            ->and($d1->coupons())->toBe($d2->coupons())
            ->and($d1->promos())->toBe($d2->promos());
    });

    it('publishes config to the correct path', function (): void {
        $this->artisan('vendor:publish', ['--tag' => 'discountify-config', '--force' => true]);

        expect(file_exists(config_path('discountify.php')))->toBeTrue();
    });

    it('config has expected keys', function (): void {
        expect(config('discountify'))->toHaveKeys([
            'condition_namespace',
            'condition_path',
            'database_driver',
            'fields',
            'global_discount',
            'global_tax_rate',
            'fire_events',
            'tables',
        ]);
    });

    it('config tables has all six table keys', function (): void {
        expect(config('discountify.tables'))->toHaveKeys([
            'conditions',
            'coupons',
            'promos',
            'coupon_usages',
            'promo_usages',
            'settings',
        ]);
    });

    it('migrations create all six tables', function (): void {
        $tables = [
            config('discountify.tables.conditions'),
            config('discountify.tables.coupons'),
            config('discountify.tables.promos'),
            config('discountify.tables.coupon_usages'),
            config('discountify.tables.promo_usages'),
            config('discountify.tables.settings'),
        ];

        foreach ($tables as $table) {
            expect(Schema::hasTable($table))
                ->toBeTrue("Table [{$table}] does not exist");
        }
    });

});
