<?php

declare(strict_types=1);

return [
    'condition_namespace' => 'App\\Conditions',
    'condition_path' => app_path('Conditions'),
    'database_driver' => env('DISCOUNTIFY_DB_DRIVER', true),
    'fields' => ['price' => 'price', 'quantity' => 'quantity'],
    'global_discount' => 0,
    'global_tax_rate' => 0,
    'fire_events' => env('DISCOUNTIFY_FIRE_EVENTS', true),
    'tables' => [
        'conditions' => 'discountify_conditions',
        'coupons' => 'discountify_coupons',
        'promos' => 'discountify_promos',
        'coupon_usages' => 'discountify_coupon_usages',
        'promo_usages' => 'discountify_promo_usages',
        'settings' => 'discountify_settings',
    ],
];
