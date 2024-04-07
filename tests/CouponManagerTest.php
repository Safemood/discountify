<?php

declare(strict_types=1);

use Safemood\Discountify\CouponManager;
use Safemood\Discountify\Facades\Coupon;
use Safemood\Discountify\Exceptions\DuplicateCouponException;

beforeEach(function () {

    $this->couponManager = new CouponManager();
});

it('adds, retrieves, updates, and removes coupons', function () {

    $coupon = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->couponManager->add($coupon);

    expect($this->couponManager->get('WELCOME20'))->toBe($coupon);

    $updatedCoupon = [
        'code' => 'WELCOME20',
        'discount' => 25,
        'startDate' => now(),
        'endDate' => now()->addWeeks(2),
    ];

    $this->couponManager->update('WELCOME20', $updatedCoupon);
    expect($this->couponManager->get('WELCOME20'))->toBe($updatedCoupon);

    $this->couponManager->remove('WELCOME20');
    expect($this->couponManager->get('WELCOME20'))->toBeNull();
});

it('can add a coupon', function () {

    $coupon = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->couponManager->add($coupon);

    $coupons = $this->couponManager->all();

    expect(count($coupons))->toBeGreaterThan(0);

    expect($coupons['WELCOME20'])->toBe($coupon);

});

it('throws an exception when adding a coupon with a duplicate code', function () {
 
    $this->couponManager->add(['code' => 'unique_coupon', 'discount' => 10]);
    $this->couponManager->add(['code' => 'unique_coupon', 'discount' => 15]);
 
})->throws(DuplicateCouponException::class, "Coupon with code 'unique_coupon' already exists.");

it('can clear all coupons', function () {

    $couponData = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->couponManager->add($couponData);

    $this->couponManager->clear();

    $coupons = $this->couponManager->all();

    expect($coupons)->toBeEmpty();
});

it('checks if coupon is expired', function () {

    $CouponHasNoEndDate = [
        'code' => 'NO_END_DATE',
        'discount' => 10,
        'startDate' => now(),
    ];
    $this->couponManager->add($CouponHasNoEndDate);
    expect($this->couponManager->isCouponExpired('NO_END_DATE'))->toBeFalse();

    $futureCoupon = [
        'code' => 'FUTURE10',
        'discount' => 10,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $this->couponManager->add($futureCoupon);
    expect($this->couponManager->isCouponExpired('FUTURE10'))->toBeFalse();

    $pastCoupon = [
        'code' => 'PAST15',
        'discount' => 15,
        'startDate' => now(),
        'endDate' => now()->subWeek(),
    ];
    $this->couponManager->add($pastCoupon);
    expect($this->couponManager->isCouponExpired('PAST15'))->toBeTrue();
});

it('checks if a coupon is already used by a user', function () {

    $coupon = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
        'usedBy' => [1, 2, 3],
    ];

    $userId = 2;
    $reflectionMethod = new ReflectionMethod(CouponManager::class, 'isCouponAlreadyUsedByUser');
    $reflectionMethod->setAccessible(true);

    $isUsedByUser = $reflectionMethod->invoke($this->couponManager, $coupon, $userId);

    expect($isUsedByUser)->toBeTrue();
});

it('verifies coupon validity', function () {

    $coupon = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'userIds' => [123],
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->couponManager->add($coupon);

    expect($this->couponManager->verify('WELCOME20', 123))->toBeTrue();

    expect($this->couponManager->verify('WELCOME20', 456))->toBeFalse();

    $expiredCoupon = [
        'code' => 'EXPIRED10',
        'discount' => 10,
        'startDate' => now(),
        'endDate' => now()->subWeek(),
    ];
    $this->couponManager->add($expiredCoupon);
    expect($this->couponManager->verify('EXPIRED10'))->toBeFalse();

    expect($this->couponManager->verify('INVALIDE_CODE'))->toBeFalse();
});

it('applies a single-use coupon only once', function () {

    $coupon = [
        'code' => 'SINGLEUSE10',
        'discount' => 10,
        'singleUse' => true,
        'startDate' => now(),
        'endDate' => now()->addMonth(),
    ];

    $this->couponManager->add($coupon);

    expect($this->couponManager->apply('SINGLEUSE10'))->toBeTrue();
    expect($this->couponManager->apply('SINGLEUSE10'))->toBeFalse();
});

it('tracks users who used the coupon', function () {

    $coupon = [
        'code' => 'TRACKED20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addMonth(),
    ];
    $this->couponManager->add($coupon);

    $this->couponManager->apply('TRACKED20', 123);

    $this->couponManager->apply('TRACKED20', 456);

    expect($this->couponManager->get('TRACKED20')['usedBy'])->toContain(123);
    expect($this->couponManager->get('TRACKED20')['usedBy'])->toContain(456);
});

it('returns zero discount when no coupons are applied', function () {

    expect($this->couponManager->couponDiscount())->toEqual(0);
});

it('handles coupon usage limit', function () {
    $coupon = [
        'code' => 'LIMITED25',
        'discount' => 25,
        'usageLimit' => 2,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $this->couponManager->add($coupon);

    expect($this->couponManager->apply('LIMITED25'))->toBeTrue();

    expect($this->couponManager->apply('LIMITED25'))->toBeTrue();

    expect($this->couponManager->apply('LIMITED25'))->toBeFalse();
});

it('removes an applied coupon', function () {

    $coupon = [
        'code' => 'DISCOUNT10',
        'discount' => 10,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $this->couponManager->add($coupon);

    $this->couponManager->apply('DISCOUNT10');

    expect($this->couponManager->get('DISCOUNT10'))->not->toBeNull();

    $this->couponManager->removeAppliedCoupons();

    expect($this->couponManager->get('DISCOUNT10'))->toBeNull();
});

it('clears all applied coupons', function () {

    $coupons = [
        [
            'code' => 'DISCOUNT10', 'discount' => 10,
            'startDate' => now(), 'endDate' => now()->addWeek(),
        ],
        [
            'code' => 'SALE20', 'discount' => 20,
            'startDate' => now(), 'endDate' => now()->addWeek(),
        ],
    ];

    foreach ($coupons as $coupon) {
        $this->couponManager->add($coupon);
        $this->couponManager->apply($coupon['code']);
    }

    expect(count($this->couponManager->appliedCoupons()))->toBe(2);

    $this->couponManager->clearAppliedCoupons();

    expect($this->couponManager->appliedCoupons())->toBeEmpty();
});

it('applies coupon with usage limit', function () {

    $coupon = [
        'code' => 'UNLIMITED10',
        'discount' => 10,
        'usageLimit' => 2,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->couponManager->add($coupon);

    $this->couponManager->apply('UNLIMITED10');
    $this->couponManager->apply('UNLIMITED10');

    $appliedCoupon = $this->couponManager->get('UNLIMITED10');

    expect($appliedCoupon['usageLimit'])->toBe(0);

    expect($this->couponManager->apply('UNLIMITED10'))->toBeFalse();
});

it('applies coupon only to restricted users', function () {
    $coupon = [
        'code' => 'USER20',
        'discount' => 20,
        'userIds' => [1, 2], // Restricted to users with IDs 1 and 2
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $this->couponManager->add($coupon);

    expect($this->couponManager->apply('USER20'))->toBeFalse();
    expect($this->couponManager->apply('USER20', 1))->toBeTrue();
    expect($this->couponManager->apply('USER20', 2))->toBeTrue();
    expect($this->couponManager->apply('USER20', 3))->toBeFalse();
});

it('removes a coupon via the facade', function () {
    $coupon = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    Coupon::add($coupon);
    Coupon::remove('WELCOME20');
    expect(Coupon::get('WELCOME20'))->toBeNull();
});

it('checks if a coupon is expired via the facade', function () {
    $coupon = [
        'code' => 'EXPIRED10',
        'discount' => 10,
        'startDate' => now(),
        'endDate' => now()->subWeek(),
    ];

    Coupon::add($coupon);
    expect(Coupon::isCouponExpired('EXPIRED10'))->toBeTrue();
});

it('clears all applied coupons via the facade', function () {
    $coupon1 = [
        'code' => 'SALE10',
        'discount' => 10,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $coupon2 = [
        'code' => 'SALE20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    Coupon::add($coupon1);
    Coupon::add($coupon2);

    Coupon::apply('SALE10');
    Coupon::apply('SALE20');

    Coupon::clearAppliedCoupons();

    expect(Coupon::appliedCoupons())->toBeEmpty();
});

it('returns the total discount applied by coupons via the facade', function () {
    $coupon1 = [
        'code' => 'DISCOUNT10',
        'discount' => 10,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $coupon2 = [
        'code' => 'DISCOUNT20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    Coupon::add($coupon1);
    Coupon::add($coupon2);

    Coupon::apply('DISCOUNT10');
    Coupon::apply('DISCOUNT20');

    expect(Coupon::couponDiscount())->toBe(floatval(30));
});

it('verifies if a coupon is valid for use via the facade', function () {
    $coupon = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $userId = 123;

    Coupon::add($coupon);
    expect(Coupon::verify('WELCOME20', $userId))->toBe(true);
});

it('removes an applied coupon from the list of applied coupons via the facade', function () {
    $coupon = [
        'code' => 'DISCOUNT10',
        'discount' => 10,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    Coupon::add($coupon);

    Coupon::apply('DISCOUNT10');
    expect(Coupon::get('DISCOUNT10')['applied'])->toBe(true);

    Coupon::removeAppliedCoupons();
    expect(Coupon::get('DISCOUNT10'))->toBeNull();
});
