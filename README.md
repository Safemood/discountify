
# Laravel Discountify for dynamic discounts with custom conditions.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/safemood/discountify.svg?style=flat-square)](https://packagist.org/packages/safemood/discountify)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/safemood/discountify/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/safemood/discountify/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/safemood/discountify/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/safemood/discountify/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/safemood/discountify.svg?style=flat-square)](https://packagist.org/packages/safemood/discountify)

Discountify is a Laravel package designed for managing dynamic discounts with custom conditions. It allows you to create flexible and powerful discounting strategies, easily defining conditions and applying percentage-based discounts to enhance your e-commerce application.


- [Installation](#installation)
- [Usage](#usage)
  - [Define Discounts Conditions](#define-conditions)
  - [Set Items, Global Discount, and Tax Rate](#set-items-global-discount-and-tax-rate)
  - [Calculate Total Amounts](#calculate-total-amounts)
  - [Dynamic Field Names](#dynamic-field-names)
  - [Class-Based Discounts](#class-based-discounts)
  - [Skip Discounts conditions](#skip-discounts-conditions)
  - [Event Tracking](#event-tracking)
  - [Coupon Based Discounts](#coupon-based-discounts)

## Installation

You can install the package via composer:

```bash
composer require safemood/discountify
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="discountify-config"
```

This is the contents of the published config file:

```php
// config/discountify.php
return [
    'condition_namespace' => 'App\\Conditions',
    'condition_path' => app_path('Conditions'),
    'fields' => [
        'price' => 'price',
        'quantity' => 'quantity',
    ],
    'global_discount' => 0,
    'global_tax_rate' => 0,
    'fire_events' => env('DISCOUNTIFY_FIRE_EVENTS', true)
];
```

## Usage

## Define Conditions

```php
use Illuminate\Support\ServiceProvider;
use Safemood\Discountify\Facades\Condition;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // If items are more than 2, apply a 10% discount.
        Condition::define('more_than_2_products_10', fn (array $items) => count($items) > 2, 10)
            // If the date is within a 7-day interval starting March 1, 2024, apply a 15% discount.
            ->add([
                [
                    'slug' => 'promo_early_spring_sale_2024',
                    'condition' => fn ($items) => now()->between(
                        Carbon::createFromDate(2024, 3, 1),
                        Carbon::createFromDate(2024, 3, 15)->addDays(7)
                    ),
                    'discount' => 15,
                ],
                // If 'special' items are in the cart, apply a 10% discount.
                [
                    'slug' => 'special_type_product_10',
                    'condition' => fn ($items) => in_array('special', array_column($items, 'type')),
                    'discount' => 10,
                ],
            ])
            // If the user has a renewal, apply a 10% discount.
            ->defineIf('client_has_renewal_10', auth()->user()->hasRenewal(), 10);
    }
}
```

### Set Items, Global Discount, and Tax Rate
```php

$items = [
        ['id' => '1', 'quantity' => 2, 'price' => 50],
        ['id' => '2', 'quantity' => 1, 'price' => 100, 'type' => 'special'],
    ];

// Set the items in the cart
Discountify::setItems($items)

// Set a global discount for all items in the cart
    ->setGlobalDiscount(15)

// Set a global tax rate for all items in the cart
    ->setGlobalTaxRate(19);
```
### Calculate Total Amounts

```php
// Calculate the total amount considering the set conditions and discounts

$total = Discountify::total();

// Calculate total amount with detailed breakdown
// (array contains total, subtotal, tax amount, total after discount, savings, tax rate, discount rate)
$total = Discountify::totalDetailed();

// Calculate the total amount with the applied global discount

$totalWithDiscount = Discountify::totalWithDiscount();

// Calculate the total amount with taxes applied based on the set global tax rate

$totalWithTaxes = Discountify::tax();

// Calculate the total tax amount based on the given tax rate (19 in this case) (before discounts)

$taxAmount = Discountify::taxAmount(19);

// Calculate tax amount with tax applied after discounts

$taxAmount = Discountify::calculateTaxAmount(19, true);


// Calculate the amount saved
$savings = Discountify::savings();


```
### Dynamic Field Names
```php
// Set custom field names through configuration
return [
    'condition_namespace' => 'App\\Conditions',
    'condition_path' => app_path('Conditions'),
    'fields' => [
        'price' => 'price',
        'quantity' => 'quantity',
    ],
    'global_discount' => 0,
    'global_tax_rate' => 0,
    'fire_events' => env('DISCOUNTIFY_FIRE_EVENTS', true)
];

// Alternatively, set dynamic field names on the fly
$items = [
    ['id' => 'item1', 'qty' => 2, 'amount' => 20],
    ['id' => 'item2', 'qty' => 1, 'amount' => 20],
];

$discountify->setFields([
    'price' => 'amount',
    'quantity' => 'qty',
])->setItems($items);

$totalWithDiscount = $discountify->totalWithDiscount(50);

```

### Class-Based Discounts

The classes in App\Conditions will be auto-discovered by Discountify for seamless integration—no configuration is needed.

```php
// app/Conditions
<?php

namespace App\Conditions;

use Safemood\Discountify\Contracts\ConditionInterface;

class MoreThan1ProductsCondition implements ConditionInterface
{
    public string $slug = 'more_than_1_products_10';

    public int $discount = 10;

    public function __invoke(array $items): bool
    {
        return count($items) > 1;
    }
}

```

### Skip Discounts Conditions

This will allows you to exclude specific conditions based on the "skip" field.

Using Condition::define:

```php
Condition::define('condition2', fn ($items) => false, 20, true);  // Will be skipped
```

- Using Condition::add:
```php
Condition::add([
    ['slug' => 'condition1', 'condition' => fn ($items) => true, 'discount' => 10, 'skip' => false],  // Won't be skipped
    ['slug' => 'condition2', 'condition' => fn ($items) => false, 'discount' => 20, 'skip' => true], // Will be skipped
    ['slug' => 'condition3', 'condition' => fn ($items) => true, 'discount' => 30], // Will not be skipped (default skip is false)
]);
```
- Using  Class-Based Conditions:

To create a class-based condition using the `discountify:condition` artisan command, you can run the following command:
Options:

--discount (-d): Specifies the discount value for the condition. Default value is 0.<br/>
--slug (-s): Specifies the slug for the condition. If not provided, the name of the condition will be used as the slug.<br/>
--force (-f): Creates the class even if the condition class already exists. <br/>

```php
php artisan discountify:condition OrderTotalDiscount 
```

```php
php artisan discountify:condition OrderTotalDiscount --discount=10 --slug OrderTotal
```

```php
<?php

namespace App\Conditions;

use Safemood\Discountify\Contracts\ConditionInterface;

class OrderTotalDiscount implements ConditionInterface
{
    public bool $skip = true; // Set to true to skip the condition

    public string $slug = 'order_total';

    public int $discount = 10;

    public function __invoke(array $items): bool
    {
         return count($items) > 5;
    }
}
```

### Event Tracking

You can listen for the `DiscountAppliedEvent` and `CouponAppliedEvent` using Laravel's Event system. 


Ensure the following configuration in the discountify.php file:
```php
// config/discountify.php
'fire_events' => env('DISCOUNTIFY_FIRE_EVENTS', true) // Toggle event dispatching
```
 
```php
// app/Providers/EventServiceProvider.php

use Illuminate\Support\Facades\Event;
use Safemood\Discountify\Events\DiscountAppliedEvent;

public function boot(): void
{
    Event::listen(function (DiscountAppliedEvent $event) {
        // Your event handling logic here
        // Ex : Mail to costumer
        // dd($event);
    });

    Event::listen(function (CouponAppliedEvent $event) { // Added
        // Your event handling logic for CouponAppliedEvent here
        // Example: Log coupon usage
        // dd($event);
    });
}
```
Check the [Laravel Events documentation](https://laravel.com/docs/10.x/events#registering-events-and-listeners) for more details.

### Coupon Based Discounts

Coupon based discounts to easily apply and calculate discounts (Percentage) on a given coupon code.

Discountify Coupons allows you to apply various types of coupons to your cart.


#### Period-Limited Coupon

```php
use Safemood\Discountify\Facades\Coupon;

Coupon::add([
    'code' => 'PERIODLIMITED50',
    'discount' => 50,
    'startDate' => now(),
    'endDate' => now()->addWeek(),
]);

$discountedTotal = Discountify::setItems($items)
    ->applyCoupon('TIMELIMITED50')
    ->total();
```

#### Single-Use Coupon

```php
use Safemood\Discountify\Facades\Coupon;

Coupon::add([
    'code' => 'SINGLEUSE50',
    'discount' => 50,
    'singleUse' => true,
    'startDate' => now(),
    'endDate' => now()->addWeek(),
]);

$discountedTotal = Discountify::setItems($items)
    ->applyCoupon('SINGLEUSE50')
    ->total();
```

#### Restricted User Coupon

```php
use Safemood\Discountify\Facades\Coupon;

Coupon::add([
    'code' => 'RESTRICTED20',
    'discount' => 20,
    'userIds' => [123, 456], // Restricted to user IDs 123 and 456
    'startDate' => now(),
    'endDate' => now()->addWeek(),
]);

$discountedTotal = Discountify::setItems($items)
    ->applyCoupon('RESTRICTED20', 123) // Applying to user ID 123
    ->total();
```


#### Limited Usage Coupon

```php
use Safemood\Discountify\Facades\Coupon;

Coupon::add([
    'code' => 'LIMITED25',
    'discount' => 25,
    'usageLimit' => 3, // Limited to 3 uses
    'startDate' => now(),
    'endDate' => now()->addWeek(),
]);

$discountedTotal = Discountify::setItems($items)
    ->applyCoupon('LIMITED25')
    ->total();

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Khalil Bouzidi](https://github.com/Safemood)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
