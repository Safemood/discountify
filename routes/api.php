<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Safemood\Discountify\Http\Controllers\Api\DiscountifyController;

Route::prefix('discountify')->group(function () {
    Route::post('/calculate', [DiscountifyController::class, 'calculate']);
    Route::post('/apply-coupon', [DiscountifyController::class, 'applyCoupon']);
    Route::get('/conditions', [DiscountifyController::class, 'conditions']);
    Route::post('/conditions', [DiscountifyController::class, 'addCondition']);
});
