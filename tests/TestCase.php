<?php

declare(strict_types=1);

namespace Safemood\Discountify\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Safemood\Discountify\DiscountifyServiceProvider;
use Safemood\Discountify\Facades\Condition;
use Safemood\Discountify\Facades\Discountify;
use Safemood\Discountify\Models\Coupon;
use Safemood\Discountify\Models\Promo;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [DiscountifyServiceProvider::class];
    }

    #[\Override]
    protected function getPackageAliases($app): array
    {
        return [
            'Discountify' => Discountify::class,
            'Condition' => Condition::class,
        ];
    }

    #[\Override]
    protected function defineEnvironment($app): void
    {
        // Use in-memory SQLite for all tests
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('discountify.database_driver', true);
        $app['config']->set('discountify.fire_events', true);
    }

    #[\Override]
    protected function defineRoutes($router): void
    {
        // Register API routes for testing
        \Safemood\Discountify\Discountify::routes();
    }

    #[\Override]
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../src/Database/migrations');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function makeItems(int $count = 2, float $price = 50.0, int $qty = 1): array
    {
        return array_map(
            fn (int $i): array => ['name' => "Item {$i}", 'price' => $price, 'quantity' => $qty],
            range(1, $count)
        );
    }

    protected function makeCoupon(array $attrs = []): Coupon
    {
        return Coupon::create(array_merge([
            'name' => 'Test Coupon',
            'code' => 'TEST10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ], $attrs));
    }

    protected function makePromo(array $attrs = []): Promo
    {
        return Promo::create(array_merge([
            'name' => 'Test Promo',
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'is_active' => true,
            'is_stackable' => true,
        ], $attrs));
    }

    protected function makeCondition(array $attrs = []): \Safemood\Discountify\Models\Condition
    {
        return \Safemood\Discountify\Models\Condition::create(array_merge([
            'name' => 'Test Condition',
            'slug' => 'test_condition',
            'field' => 'count',
            'operator' => 'gte',
            'value' => 1,
            'discount' => 10,
            'discount_type' => 'percentage',
            'is_active' => true,
            'priority' => 0,
        ], $attrs));
    }
}
