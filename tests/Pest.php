<?php

declare(strict_types=1);

use Safemood\Discountify\Enums\DiscountType;
use Safemood\Discountify\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| All tests use TestCase for Laravel setup.
*/
pest()->extend(TestCase::class);

/*
|--------------------------------------------------------------------------
| Custom Expectations — Pest 4
|--------------------------------------------------------------------------
*/
expect()->extend('toBeValidDiscountType', function () {
    return $this->toBeInstanceOf(DiscountType::class);
});

expect()->extend('toBeValidCheckoutResult', function () {
    return $this->toHaveKeys([
        'subtotal', 'global_discount', 'condition_discount',
        'promo_discount', 'coupon_discount', 'total_discount',
        'total', 'tax', 'total_with_tax', 'coupon', 'promos',
    ]);
});
