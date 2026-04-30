<?php

declare(strict_types=1);

use Safemood\Discountify\Models\Condition;
use Safemood\Discountify\Models\Coupon;

it('calculates discounts through the API', function (): void {
    $items = [
        ['price' => 30.0, 'quantity' => 2],
        ['price' => 80.0, 'quantity' => 1],
    ];

    $response = $this->postJson('/discountify/calculate', [
        'items' => $items,
        'global_discount' => 5,
        'tax_rate' => 10,
    ]);

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('data.subtotal', 140);
    $response->assertJsonPath('data.total_discount', 7);
    $response->assertJsonPath('data.tax', 13.3);
    $response->assertJsonPath('data.total', 133);
    $response->assertJsonPath('data.total_with_tax', 146.3);
});

it('applies a coupon through the API', function (): void {
    Coupon::create([
        'name' => 'Test Coupon',
        'code' => 'SAVE10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
    ]);

    $response = $this->postJson('/discountify/apply-coupon', [
        'code' => 'SAVE10',
        'items' => [['price' => 100.0, 'quantity' => 1]],
    ]);

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('data.subtotal', 100);
    $response->assertJsonPath('data.total_discount', 10);
    $response->assertJsonPath('data.total', 90);
    $response->assertJsonStructure(['data' => ['coupon']]);
});

it('returns an error for invalid coupons via the API', function (): void {
    $response = $this->postJson('/discountify/apply-coupon', [
        'code' => 'INVALID',
        'items' => [['price' => 50.0, 'quantity' => 1]],
    ]);

    $response->assertStatus(400);
    $response->assertJsonPath('success', false);
    $response->assertJsonStructure(['error']);
});

it('lists DB conditions through the API', function (): void {
    Condition::create([
        'name' => 'API condition',
        'slug' => 'api_condition',
        'field' => 'count',
        'operator' => 'gte',
        'value' => 1,
        'discount' => 5,
        'discount_type' => 'percentage',
        'is_active' => true,
    ]);

    $response = $this->getJson('/discountify/conditions');

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.slug', 'api_condition');
});

it('creates a DB condition through the API', function (): void {
    $response = $this->postJson('/discountify/conditions', [
        'name' => 'Bulk discount',
        'slug' => 'bulk_discount',
        'field' => 'count',
        'operator' => 'gte',
        'value' => 3,
        'discount' => 10,
        'discount_type' => 'percentage',
        'priority' => 5,
        'is_active' => true,
    ]);

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $this->assertDatabaseHas('discountify_conditions', ['slug' => 'bulk_discount', 'discount' => 10]);
});
