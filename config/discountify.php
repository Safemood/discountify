<?php

declare(strict_types=1);

return [
    'condition_namespace' => 'App\\Conditions',
    'condition_path' => app_path('Conditions'),
    'fields' => [
        'price' => 'price',
        'quantity' => 'quantity',
    ],
    'global_discount' => 0,
    'global_tax_rate' => 0,
    'fire_events' => env('DISCOUNTIFY_FIRE_EVENTS', true),
    'state_file_path' => env('DISCOUNTIFY_STATE_FILE_PATH', storage_path('app/discountify/coupons.json')),
];
