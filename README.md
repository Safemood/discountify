# Laravel package for dynamic discounts with custom conditions.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/safemood/discountify.svg?style=flat-square)](https://packagist.org/packages/safemood/discountify)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/safemood/discountify/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/safemood/discountify/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/safemood/discountify/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/safemood/discountify/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/safemood/discountify.svg?style=flat-square)](https://packagist.org/packages/safemood/discountify)

Discountify is a Laravel package designed for managing dynamic discounts with custom conditions. It allows you to create flexible and powerful discounting strategies, easily defining conditions and applying percentage-based discounts to enhance your e-commerce application

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
return [
    'global_discount' => 0,
    'global_tax_rate' => 0,
];

```

## Define Conditions

```php
use Illuminate\Support\ServiceProvider;
use Safemood\Discountify\Facades\Condition;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Define a condition: If the number of items is greater than 2, apply a 20% discount.
        Condition::define(fn (array $items) => count($items) > 2, 20)
            // Add a condition: If the current date is within a specific date interval, apply a 15% discount.
            ->add([
                [
                    'condition' => fn ($items) => now()->between(
                        Carbon::createFromDate(2024, 3, 1), // Check if the current date is within 7 days after March 1, 2024
                        Carbon::createFromDate(2024, 3, 15)->addDays(7) // The end date is March 22, 2024
                    ),
                    'discount' => 15,
                ],
                // If 'special' type items exist in the cart, apply a 10% discount.
                [
                    'condition' => fn ($items) => in_array('special', array_column($items, 'type')),
                    'discount' => 10,
                ],
            ])
            // Define a condition based on a user attribute: If the user has a renewal, apply a 10% discount.
            ->defineIf(auth()->user()->hasRenewal(), 10);
    }
}


```

## Usage

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

// Calculate the total amount with the applied global discount

$totalWithDiscount = Discountify::totalWithDiscount();

// Calculate the total amount with taxes applied based on the set global tax rate

$totalWithTaxes = Discountify::tax();

// Calculate the total tax amount based on the given tax rate (19 in this case)

$taxAmount = Discountify::taxAmount(19);

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
