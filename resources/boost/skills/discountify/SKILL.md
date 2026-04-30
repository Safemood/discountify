---
name: discountify
description: "Discountify skill for Laravel Boost: installation, configuration, CLI commands, and usage for coupons, promos, and conditions."
license: MIT
metadata:
  author: safemood
---

# Discountify Package Guide

**DOMAIN SKILL** — Complete guide for implementing and using the Discountify Laravel package.

## Installation

```bash
composer require safemood/discountify
php artisan discountify:install
```

## Configuration

Publish and review the config:

```bash
php artisan vendor:publish --tag=discountify-config
```

Key settings in `config/discountify.php`:

```php
'condition_namespace' => 'App\\Conditions',
'condition_path' => app_path('Conditions'),
'database_driver' => true, // Enable DB conditions/coupons/promos
'fields' => ['price' => 'price', 'quantity' => 'quantity'],
'global_discount' => 0,
'global_tax_rate' => 0,
'fire_events' => true,
```

## Basic Usage

### Simple Checkout

```php
use Safemood\Discountify\Facades\Discountify;

$cart = [
    ['price' => 30.00, 'quantity' => 2],
    ['price' => 80.00, 'quantity' => 1],
];

$result = Discountify::setItems($cart)
    ->setGlobalDiscount(5)
    ->setTaxRate(20)
    ->checkout();

// Result contains: subtotal, discounts, tax, total
```

### With Coupons

```php
$result = Discountify::setItems($cart)
    ->applyCoupon('SAVE10')
    ->forUser(auth()->id())
    ->checkout(); // Records usage
```

### Preview Mode

```php
$subtotal = Discountify::setItems($cart)->subtotal();
$discount = Discountify::setItems($cart)->totalDiscount();
$total = Discountify::setItems($cart)->total();
```

## Condition Engine

### Code-based Conditions

Create a condition class:

```bash
php artisan discountify:condition BigCartDiscount --slug=big_cart --discount=15
```

The generated class in `app/Conditions/BigCartDiscount.php`:

```php
<?php
namespace App\Conditions;

use Safemood\Discountify\Contracts\ConditionInterface;

class BigCartDiscount implements ConditionInterface
{
    public bool $skip = false;
    public string $slug = 'big_cart';
    public float $discount = 15;
    public string $type = 'percentage';
    public int $priority = 0;

    public function __invoke(array $items): bool
    {
        return count($items) >= 5; // 15% off carts with 5+ items
    }
}
```

### Inline Conditions

```php
Discountify::setItems($cart)
    ->define([[
        'slug' => 'vip_discount',
        'condition' => fn($items) => auth()->user()?->is_vip,
        'discount' => 20,
        'type' => 'percentage',
        'priority' => 10,
    ]])
    ->checkout();
```

### Database Conditions

Create via model:

```php
use Safemood\Discountify\Models\Condition;

Condition::create([
    'name' => 'High value cart',
    'slug' => 'high_value_cart',
    'field' => 'total', // count|total|subtotal|item_key
    'operator' => 'gte', // gt|gte|lt|lte|eq|neq|in|nin
    'value' => 200,
    'discount' => 10,
    'discount_type' => 'percentage',
    'priority' => 5,
    'is_active' => true,
]);
```

Available fields:
- `count` — Number of items
- `total`/`subtotal` — Sum of price × quantity
- Any item array key

## Coupon Engine

### Creating Coupons

```php
use Safemood\Discountify\Models\Coupon;

Coupon::create([
    'name' => 'Summer Sale',
    'code' => 'SUMMER10',
    'discount_type' => 'percentage', // or 'fixed'
    'discount_value' => 10,
    'max_discount' => 30.00, // cap for percentage
    'max_usages' => 100,
    'max_usages_per_user' => 1,
    'min_order_value' => 50.00,
    'expires_at' => now()->endOfMonth(),
    'user_id' => null, // or specific user
    'is_active' => true,
]);
```

### Applying Coupons

```php
try {
    $result = Discountify::setItems($cart)
        ->applyCoupon('SUMMER10')
        ->forUser(auth()->id())
        ->checkout();
} catch (CouponException $e) {
    // Handle: not found, expired, exhausted, etc.
}
```

## Promo Engine

Auto-applied promotions:

```php
use Safemood\Discountify\Models\Promo;

Promo::create([
    'name' => 'Welcome bonus',
    'discount_type' => 'fixed',
    'discount_value' => 5.00,
    'min_order_value' => 25.00,
    'max_usages' => 1,
    'max_usages_per_user' => 1,
    'starts_at' => now(),
    'expires_at' => now()->addMonth(),
    'is_active' => true,
    'is_stackable' => true,
    'priority' => 10,
    'conditions' => [
        ['field' => 'count', 'operator' => 'gte', 'value' => 1]
    ],
]);

Promos apply automatically during checkout based on conditions.
```

Promos apply automatically during checkout based on conditions.

## API Endpoints

Register routes in your application:

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Discountify::routes();
});
```

### Calculate Discount

```http
POST /discountify/calculate
Content-Type: application/json

{
  "items": [
    {"price": 30.00, "quantity": 2},
    {"price": 80.00, "quantity": 1}
  ],
  "global_discount": 5,
  "tax_rate": 20
}
```

Response:

```json
{
  "success": true,
  "data": {
    "subtotal": 140.00,
    "global_discount": 7.00,
    "condition_discount": 0.00,
    "promo_discount": 0.00,
    "coupon_discount": 0.00,
    "total_discount": 7.00,
    "total": 133.00,
    "tax": 26.60,
    "total_with_tax": 159.60,
    "coupon": null,
    "promos": []
  }
}
```

### Apply Coupon

```http
POST /discountify/apply-coupon
Content-Type: application/json

{
  "code": "SAVE10",
  "items": [
    {"price": 30.00, "quantity": 2},
    {"price": 80.00, "quantity": 1}
  ]
}
```

Success response:

```json
{
  "success": true,
  "data": {
    "subtotal": 140.00,
    "coupon_discount": 14.00,
    "total": 126.00,
    "coupon": {"code": "SAVE10", "discount": 14.00}
  }
}
```

Error response:

```json
{
  "success": false,
  "error": "Coupon not found"
}
```

### List Conditions

```http
GET /discountify/conditions
```

Response:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "high_value_cart",
      "field": "total",
      "operator": "gte",
      "value": 200,
      "discount": 10,
      "discount_type": "percentage",
      "is_active": true,
      "priority": 5
    }
  ]
}
```

### Create Condition

```http
POST /discountify/conditions
Content-Type: application/json

{
  "name": "Bulk discount",
  "slug": "bulk_discount",
  "field": "count",
  "operator": "gte",
  "value": 3,
  "discount": 10,
  "discount_type": "percentage",
  "priority": 0,
  "is_active": true
}
```

Response:

```json
{
  "success": true,
  "message": "Condition added successfully"
}
```

## Database Schema

The package creates 6 tables:

- `discountify_conditions` — DB-stored conditions
- `discountify_coupons` — coupon definitions
- `discountify_promos` — promo definitions
- `discountify_coupon_usages` — tracks coupon redemptions
- `discountify_promo_usages` — tracks promo applications
- `discountify_settings` — persisted global discount & tax rate

## Events

Available events for integration:

```php
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\PromoAppliedEvent;

Event::listen(DiscountAppliedEvent::class, function ($event) {
    // $event->items, $event->discountAmount, $event->conditions
});

Event::listen(CouponAppliedEvent::class, function ($event) {
    // $event->coupon, $event->discount, $event->items
});

Event::listen(PromoAppliedEvent::class, function ($event) {
    // $event->promos, $event->discount, $event->items
});
```

## Testing

Use Pest for testing:

```php
use function Orchestra\Testbench\workbench_path;

it('calculates discounts correctly', function () {
    $cart = [['price' => 100.0, 'quantity' => 1]];

    $result = Discountify::setItems($cart)
        ->setGlobalDiscount(10)
        ->checkout();

    expect($result['total'])->toBe(90.0);
});

it('applies coupons via API', function () {
    // Create test coupon
    Coupon::create(['code' => 'TEST10', 'discount_value' => 10, 'is_active' => true]);

    $response = $this->postJson('/discountify/apply-coupon', [
        'code' => 'TEST10',
        'items' => [['price' => 100.0, 'quantity' => 1]],
    ]);

    $response->assertJsonPath('data.total', 90);
});
```

## Best Practices

- Use DB conditions for admin-managed business rules
- Use code classes for complex conditional logic
- Keep promo & coupon rules in the database for dynamic control
- Always call `checkout()` to record usage and trigger events
- Handle `CouponException` in user-facing code
- Use API endpoints for headless admin UIs or external integrations
- Protect API routes with appropriate authentication middleware

## Advanced Usage

### Custom Item Fields

```php
Discountify::setFields(price: 'unit_price', quantity: 'qty')
    ->setItems([['unit_price' => 25.0, 'qty' => 4]]);
```

### Condition Priorities

Higher priority conditions are evaluated first:

```php
Condition::create([
    'slug' => 'vip_discount',
    'priority' => 100, // Evaluated before lower priority
    'discount' => 20,
]);
```

### Promo Stacking

Promos can be stackable or non-stackable:

```php
Promo::create([
    'name' => 'Free shipping',
    'is_stackable' => false, // Stops further promo application
    'discount_type' => 'fixed',
    'discount_value' => 10.00,
]);
```

### Usage Tracking

All coupon and promo redemptions are tracked:

```php
// Check coupon usage
$coupon = Coupon::with('usages')->find($id);

// Check promo usage
$promo = Promo::with('usages')->find($id);
```

## Troubleshooting

**Discount not applying:**
- Check condition priority and evaluation order
- Verify condition callbacks return true
- Ensure DB records have `is_active` = true
- Check for conflicting conditions

**Coupon errors:**
- `CouponException` types: notFound, notValid, exhausted, notAllowedForUser, belowMinimum

**Performance:**
- DB conditions loaded once per request
- Use `skipCondition()` to temporarily disable conditions
- Cache expensive condition logic

This guide covers the complete Discountify package usage for Laravel applications.
