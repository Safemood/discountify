<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Safemood\Discountify\CouponManager;

use function Orchestra\Testbench\workbench_path;

beforeEach(function () {
    $this->stateFilePath = workbench_path('app/test_state.json');
    config(['discountify.state_file_path' => $this->stateFilePath]);
    $this->couponManager = new CouponManager;
});

afterEach(function () {
    if (File::exists($this->stateFilePath)) {
        File::delete($this->stateFilePath);
    }
});

it('saves and loads coupons from the state file correctly', function () {

    $this->couponManager->add([
        'code' => 'PERIODLIMITED51',
        'discount' => 50,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $coupons = $this->couponManager->all();

    $this->couponManager = new CouponManager;

    expect($this->couponManager->all())
        ->toEqual($coupons);
});
