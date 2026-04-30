# Discountify v2.0

> Laravel package for dynamic discounts — full condition engine, coupon support, promo support, and database-driven management.

[![Latest Version](https://img.shields.io/packagist/v/safemood/discountify.svg)](https://packagist.org/packages/safemood/discountify)
[![Tests](https://img.shields.io/github/actions/workflow/status/Safemood/discountify/tests.yml?label=tests)](https://github.com/Safemood/discountify/actions)
[![License](https://img.shields.io/packagist/l/safemood/discountify.svg)](LICENSE.md)

## Requirements

| Laravel | PHP       | Testbench |
|---------|-----------|-----------|
| 11.x    | 8.4       | ^9.0      |
| 12.x    | 8.4       | ^10.0     |
| 13.x    | 8.4       | ^11.0     |

> **Discountify v2 supports Laravel 11, 12, and 13 on PHP 8.4+.** Use v1.x for Laravel 10.

---

## What's new in v2

| Feature | v1 | v2 |
|---|---|---|
| Code-defined conditions | ✅ | ✅ |
| Database-driven conditions | ❌ | ✅ |
| Coupon engine | Basic JSON file | ✅ Full DB model |
| Promo engine (auto-apply) | ❌ | ✅ |
| Usage tracking | ❌ | ✅ |
| Per-user coupon restrictions | ❌ | ✅ |
| Promo stacking & priority | ❌ | ✅ |
| Min order / max discount caps | ❌ | ✅ |
| UI-ready DB schema | ❌ | ✅ |
| Events | ✅ | ✅ (expanded) |

---

## Installation

```bash
composer require safemood/discountify
```

Then run the install command:

```bash
php artisan discountify:install
```

This publishes the config, publishes & runs migrations.

## Database setup

Discountify creates 6 tables:

- `discountify_conditions` — DB-stored conditions
- `discountify_coupons` — coupon definitions
- `discountify_promos` — promo definitions
- `discountify_coupon_usages` — tracks coupon redemptions
- `discountify_promo_usages` — tracks promo applications
- `discountify_settings` — persisted global discount & tax rate

If you prefer manual control:

```bash
php artisan vendor:publish --tag=discountify-migrations
php artisan migrate
```

---

## Configuration

```php
// config/discountify.php

return [
    // Where code-based condition classes live
    'condition_namespace' => 'App\\Conditions',
    'condition_path'      => app_path('Conditions'),

    // Load DB-stored conditions automatically
    'database_driver' => true,

    // Cart item field mapping
    'fields' => ['price' => 'price', 'quantity' => 'quantity'],

    // Defaults (can be overridden by DB settings)
    'global_discount' => 0,
    'global_tax_rate' => 0,

    // Toggle event dispatching
    'fire_events' => true,
];
```

---

## Core concepts

Discountify v2 has three discount sources, applied in order:

```
1. Global discount      — a flat % off the whole cart (configurable via DB)
2. Condition engine     — code classes + DB rows, each contributing a discount
3. Promo engine         — auto-applied promos (no code required)
4. Coupon engine        — a single coupon code entered by the customer
```

All sources are combined to produce `totalDiscount()`.

Global discount and tax rate can be persisted in the `discountify_settings` table for dynamic management.

---

## Quick start

```php
use Safemood\Discountify\Facades\Discountify;

$cart = [
    ['name' => 'T-Shirt', 'price' => 30.00, 'quantity' => 2],
    ['name' => 'Jeans',   'price' => 80.00, 'quantity' => 1],
];

$result = Discountify::setItems($cart)
    ->setGlobalDiscount(5)       // 5% off everything
    ->setTaxRate(20)
    ->applyCoupon('SAVE10')
    ->forUser(auth()->id())
    ->checkout();                // records usages, fires events

// $result:
// [
//   'subtotal'           => 140.00,
//   'condition_discount' => 14.00,
//   'promo_discount'     => 0.00,
//   'coupon_discount'    => 12.60,
//   'total_discount'     => 26.60,
//   'total'              => 113.40,
//   'tax'                => 22.68,
//   'total_with_tax'     => 136.08,
//   'coupon'             => [...],
//   'promos'             => [...],
// ]
```

For previewing prices without recording usage:

```php
$subtotal = Discountify::setItems($cart)->subtotal();       // 140.00
$discount = Discountify::setItems($cart)->totalDiscount();  // calculated amount
$total    = Discountify::setItems($cart)->total();
$withTax  = Discountify::setItems($cart)->totalWithTax();
```

---

## Condition engine

### Code-based conditions

Generate a class:

```bash
php artisan discountify:condition BigCartDiscount --slug=big_cart --discount=15 --type=percentage
```

This creates `app/Conditions/BigCartDiscount.php`:

```php
<?php

namespace App\Conditions;

use Safemood\Discountify\Contracts\ConditionInterface;

class BigCartDiscount implements ConditionInterface
{
    public bool   $skip     = false;
    public string $slug     = 'big_cart';
    public float  $discount = 15;
    public string $type     = 'percentage';
    public int    $priority = 0;

    public function __invoke(array $items): bool
    {
        // Apply 15% off when cart has 5+ items
        return count($items) >= 5;
    }
}
```

All classes in `app/Conditions/` are **auto-discovered** — no registration needed.

### Inline conditions (fluent API)

```php
use Safemood\Discountify\Facades\Discountify;

Discountify::setItems($cart)->define([
    [
        'slug'      => 'vip_discount',
        'condition' => fn ($items) => auth()->user()?->is_vip,
        'discount'  => 20,
        'type'      => 'percentage',
        'priority'  => 10,    // evaluated before lower-priority conditions
    ],
    [
        'slug'      => 'fixed_loyalty',
        'condition' => fn ($items) => auth()->user()?->orders()->count() > 10,
        'discount'  => 5.00,
        'type'      => 'fixed',
    ],
]);
```

### Database conditions

Insert a row into `discountify_conditions` (or via your UI):

```php
use Safemood\Discountify\Models\Condition;

Condition::create([
    'name'          => 'High value cart',
    'slug'          => 'high_value_cart',
    'field'         => 'total',      // 'count' | 'total' | 'subtotal' | any item key
    'operator'      => 'gte',        // gt | gte | lt | lte | eq | neq | in | nin
    'value'         => 200,
    'discount'      => 10,
    'discount_type' => 'percentage',
    'priority'      => 5,
    'is_active'     => true,
]);
```

Available `field` values:

| Field | Resolved value |
|---|---|
| `count` | Number of items in the cart |
| `total` / `subtotal` | Sum of `price × quantity` across all items |
| any key | The value of that key on the first cart item |

Available operators: `gt`, `gte`, `lt`, `lte`, `eq`, `neq`, `in`, `nin`

---

## Coupon engine

Coupons are **always stored in the database** so they can be managed without code changes.

### Creating coupons

```php
use Safemood\Discountify\Models\Coupon;

// 10% off, max £30 discount, usable 100 times, expires end of month
Coupon::create([
    'name'                => 'Summer Sale',
    'code'                => 'SUMMER10',
    'discount_type'       => 'percentage',
    'discount_value'      => 10,
    'max_discount'        => 30.00,
    'max_usages'          => 100,
    'max_usages_per_user' => 1,
    'min_order_value'     => 50.00,
    'expires_at'          => now()->endOfMonth(),
    'is_active'           => true,
]);

// Fixed £5 off, only for user ID 42
Coupon::create([
    'name'           => 'VIP reward',
    'code'           => 'VIP5',
    'discount_type'  => 'fixed',
    'discount_value' => 5.00,
    'user_id'        => 42,
    'is_active'      => true,
]);
```

### Applying a coupon

```php
Discountify::setItems($cart)
    ->applyCoupon('SUMMER10')
    ->forUser(auth()->id())
    ->checkout();  // validates + records usage
```

### Handling coupon errors

```php
use Safemood\Discountify\Exceptions\CouponException;

try {
    $result = Discountify::setItems($cart)
        ->applyCoupon($request->coupon_code)
        ->forUser(auth()->id())
        ->checkout();
} catch (CouponException $e) {
    return back()->withErrors(['coupon' => $e->getMessage()]);
}
```

---

## Promo engine

Promos are **auto-applied** — no code required from the customer. The engine checks all active promos and applies those whose conditions are satisfied.

### Creating promos

```php
use Safemood\Discountify\Models\Promo;

// 15% off any cart with 3+ items, stackable
Promo::create([
    'name'           => 'Buy 3 Save 15',
    'discount_type'  => 'percentage',
    'discount_value' => 15,
    'conditions'     => [
        ['field' => 'count', 'operator' => 'gte', 'value' => 3],
    ],
    'is_stackable'   => true,
    'priority'       => 10,
    'starts_at'      => now(),
    'ends_at'        => now()->addDays(7),
    'is_active'      => true,
]);

// £10 flat off orders over £100, NOT stackable (stops other promos)
Promo::create([
    'name'            => 'Century Deal',
    'discount_type'   => 'fixed',
    'discount_value'  => 10,
    'min_order_value' => 100,
    'is_stackable'    => false,
    'priority'        => 20,
    'is_active'       => true,
]);
```

Promos run automatically on `checkout()` — no extra code needed in your controller.

### Stacking rules

- Promos are evaluated **highest priority first**.
- `is_stackable = true` → all eligible stackable promos are applied.
- `is_stackable = false` → the first non-stackable promo found stops evaluation.

---

## Settings

Persist global discount and tax rate in the database for dynamic configuration without code changes.

### Setting values

```php
use Safemood\Discountify\Models\Setting;

// Set global discount to 5%
Setting::setValue('global_discount', 5);

// Set tax rate to 8%
Setting::setValue('global_tax_rate', 8);

// Retrieve values
$discount = Setting::getValue('global_discount'); // 5.0
$tax = Setting::getValue('global_tax_rate');      // 8.0
```

### Automatic loading

The service provider automatically loads these settings into config on boot:

```php
// config('discountify.global_discount') — loaded from DB
// config('discountify.global_tax_rate') — loaded from DB
```

If no DB value exists, it falls back to the config defaults.

---

## Events

Register listeners in your `AppServiceProvider`:

```php
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\PromoAppliedEvent;

Event::listen(function (DiscountAppliedEvent $event) {
    // $event->items, $event->discountAmount, $event->conditions
});

Event::listen(function (CouponAppliedEvent $event) {
    // $event->coupon (Coupon model), $event->discount, $event->items
});

Event::listen(function (PromoAppliedEvent $event) {
    // $event->promos (array), $event->discount, $event->items
});
```

Disable events globally: `DISCOUNTIFY_FIRE_EVENTS=false` in `.env`.

---

## Building a management UI

Because all coupons, promos, conditions, and settings live in the database, you can build a standard Laravel admin panel using any UI toolkit:

```php
// Example: Filament resources
use Safemood\Discountify\Models\Coupon;
use Safemood\Discountify\Models\Promo;
use Safemood\Discountify\Models\Condition;
use Safemood\Discountify\Models\Setting;

// Standard CRUD for all models — nothing special needed.
```

All models use `$guarded = []` so mass-assignment works out of the box.

---

## API Endpoints

Discountify provides REST API endpoints that you can register in your own route files.

### Registering Routes

Add the routes to your `routes/api.php` (or any route file):

```php
use Safemood\Discountify\Facades\Discountify;

// Protect with your preferred middleware
Route::middleware(['auth:sanctum'])->group(function () {
    Discountify::routes();
});

// Or use a custom prefix
Route::prefix('api/v1')->middleware(['auth:sanctum'])->group(function () {
    Discountify::routes();
});
```

This gives you full control over authentication, prefixes, and middleware.

### Calculate Discount

Calculate the total discount for a cart without applying coupons or recording usage.

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

Apply a coupon code and calculate the discounted total.

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

Response:

```json
{
  "success": true,
  "data": {
    "subtotal": 140.00,
    "global_discount": 0.00,
    "condition_discount": 0.00,
    "promo_discount": 0.00,
    "coupon_discount": 14.00,
    "total_discount": 14.00,
    "total": 126.00,
    "tax": 25.20,
    "total_with_tax": 151.20,
    "coupon": {...}
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

### Get Conditions

Retrieve all database-backed condition records.

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
      "slug": "vip_discount",
      "field": "count",
      "operator": "gte",
      "value": 1,
      "discount": 20,
      "discount_type": "percentage",
      "is_active": true,
      "priority": 10
    }
  ]
}
```

### Add Condition

Create a database-backed condition record.

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

---

## Upgrading from v1

> Discountify v2 supports **Laravel 11, 12, and 13** on **PHP 8.4+**. If you need Laravel 10 support, stay on v1.x.

| v1 | v2 |
|---|---|
| Laravel 10 / PHP 8.1 | Not supported — use v1.x |
| `'state_file_path'` config | Removed — coupons are now in DB |
| `Coupon::define([...])` | `Discountify::applyCoupon($code)` |
| `$this->skip = true` on class | Still works ✅ |
| `php artisan discountify:condition` | Still works ✅ (new `--type` option) |
| Events | Same event names, richer payloads |

---

## Testing

```bash
composer test
```

---

## License

MIT — see [LICENSE.md](LICENSE.md).
