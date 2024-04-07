<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Safemood\Discountify\ConditionManager;
use Safemood\Discountify\CouponManager;
use Safemood\Discountify\Discountify;
use Safemood\Discountify\Events\CouponAppliedEvent;
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Exceptions\ZeroQuantityException;
use Safemood\Discountify\Facades\Condition;
use Safemood\Discountify\Facades\Coupon;
use Safemood\Discountify\Facades\Discountify as DiscountifyFacade;

use function Orchestra\Testbench\workbench_path;
use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->items = [
        ['id' => '1', 'quantity' => 2, 'price' => 50],
        ['id' => '2', 'quantity' => 1, 'price' => 100],
    ];

    $this->conditionManager = new ConditionManager();
    $this->couponManager = new CouponManager();
    $this->discountify = new Discountify(
        $this->conditionManager,
        $this->couponManager,
    );
});

it('can set items', function () {

    $this->discountify->setItems($this->items);

    expect($this->discountify->getItems())->toBe($this->items);
});

it('can set and get global discount', function () {

    $expextedResult = 10;

    $this->discountify->setGlobalDiscount(10);

    expect($this->discountify->getGlobalDiscount())->toEqual($expextedResult);

    $this->discountify->discount(10);

    $globalDiscount = $this->discountify->getGlobalDiscount();

    expect($globalDiscount)->toEqual($expextedResult);
});

it('sets the ConditionManager instance', function () {

    $expextedResult = new ConditionManager();
    $this->discountify->setConditionManager($expextedResult);

    $conditionManager = $this->discountify->conditions();

    expect($conditionManager)->toBe($expextedResult);
    expect($conditionManager)->toBeInstanceOf(ConditionManager::class);
});

it('sets the CouponManager instance', function () {

    $expextedResult = new CouponManager();
    $this->discountify->setCouponManager($expextedResult);

    $couponManager = $this->discountify->coupons();

    expect($couponManager)->toBe($expextedResult);
    expect($couponManager)->toBeInstanceOf(CouponManager::class);

});

it('can set and get global tax rate', function () {

    $this->discountify->setGlobalTaxRate(5);

    expect($this->discountify->getGlobalTaxRate())->toBe(floatval(5));
});

it('can set and get condition manager', function () {

    expect($this->discountify->conditions())->toBeInstanceOf(ConditionManager::class);
});

it('calculates the total discount Rate based on conditions', function () {

    $items = [
        ['id' => '1', 'quantity' => 2, 'price' => 50],
        ['id' => '2', 'quantity' => 1, 'price' => 100, 'type' => 'special'],
    ];

    $conditions = [
        [
            'slug' => 'more_than_1_products_15',
            'condition' => fn ($items) => count($items) > 1,
            'discount' => 5,
        ],
        [
            'slug' => 'special_type_product_10',
            'condition' => true,
            'discount' => 10,
        ],
    ];

    $this->discountify->setItems($items);

    $this->discountify->conditions()->add($conditions);

    $totalDiscountRate = $this->discountify->conditionDiscount();

    expect($totalDiscountRate)->toEqual(15);

});

it('can calculate total with discount', function () {

    $this->discountify->setItems($this->items);

    $totalWithDiscount = $this->discountify->totalWithDiscount(10);

    expect($totalWithDiscount)->toBe(floatval(180));
});

it('can calculate total with taxes', function () {

    $this->discountify->setItems($this->items);

    $totalWithTaxes = $this->discountify->tax(10);

    expect($totalWithTaxes)->toBe(floatval(220));
});

it('can get total', function () {

    $this->discountify->setItems($this->items);

    $total = $this->discountify
        ->total();

    expect($total)->toBe(floatval(200));
});

it('can get tax amount', function () {

    $this->discountify->setItems($this->items);

    $tax = $this->discountify->setGlobalTaxRate(19)->taxAmount();

    expect($tax)->toBe(floatval(38));
});

it('can get subtotal', function () {

    $this->discountify->setItems($this->items);

    $subtotal = $this->discountify->subtotal();

    expect($subtotal)->toBe(floatval(200));
});

it('adds multiple conditions correctly', function () {

    $conditions = [
        ['slug' => 'condition1', 'condition' => fn () => true, 'discount' => 10],
        ['slug' => 'condition2', 'condition' => fn () => false, 'discount' => 20],
    ];

    $this->discountify->add($conditions);

    expect($this->discountify->conditions()->getConditions())->toBe($conditions);
});

it('defines a condition correctly', function () {
    $expectedCondition = [
        'slug' => 'more_than_2_products_10',
        'condition' => fn (array $items) => count($items) > 2,
        'discount' => 10,
    ];

    $this->discountify->define('more_than_2_products_10', fn (array $items) => count($items) > 2, 10);

    expect($this->discountify->conditions()->getConditions()[0]['slug'])->toBe($expectedCondition['slug']);
});

it('defines a condition based on a boolean value correctly', function () {

    $expectedCondition = ['slug' => 'more_than_2_products_11', 'condition' => true, 'discount' => 10];

    $this->discountify->defineIf('more_than_2_products_11', true, 10);

    expect($this->discountify->conditions()->getConditions()[0]['slug'])
        ->toBe($expectedCondition['slug']);
});

it('can use Condition facade', function () {

    Condition::define('more_than_2_products_10', fn (array $items) => count($items) > 2, 10)
        ->add([
            [
                'slug' => 'more_than_3_products_15',
                'condition' => fn ($items) => count($items) > 3,
                'discount' => 15,
            ],
            [
                'slug' => 'special_type_product_10',
                'condition' => fn ($items) => in_array('special', array_column($items, 'type')),
                'discount' => 10,
            ],
        ])
        ->defineIf('client_has_renewal_10', true, 10);

    $conditions = DiscountifyFacade::getConditions();

    expect($conditions)->toHaveCount(4);
});

it('can use Discountify facade', function () {

    DiscountifyFacade::setItems($this->items)
        ->setGlobalDiscount(15)
        ->setGlobalTaxRate(19);

    $total = DiscountifyFacade::total();
    $totalWithDiscount = DiscountifyFacade::totalWithDiscount();
    $totalWithTaxes = DiscountifyFacade::tax();
    $taxAmout = DiscountifyFacade::taxAmount();
    $savings = DiscountifyFacade::savings();

    expect($total)->toEqual(floatval(202.30));
    expect($totalWithDiscount)->toEqual(floatval(170.00));
    expect($totalWithTaxes)->toEqual(floatval(238));
    expect($taxAmout)->toEqual(floatval(38));
    expect($savings)->toEqual(35.70);
});

it('calculates total with discount using custom field names from configuration', function () {
    Config::set('discountify.fields', [
        'price' => 'amount',
        'quantity' => 'qty',
    ]);

    $items = [
        ['id' => 'item1', 'qty' => 2, 'amount' => 20],
        ['id' => 'item2', 'qty' => 1, 'amount' => 20],
    ];

    $this->discountify->setItems($items);

    $totalWithDiscount = $this->discountify->totalWithDiscount(50);

    expect($totalWithDiscount)->toBe(floatval(30));
});

it('returns the value of a dynamic field for a given items', function () {

    $items = [
        'cost' => 10,
        'amount' => 5,
    ];

    $this->discountify->setItems($items);

    $dynamicFields = [
        'price' => 'cost',
        'quantity' => 'amount',
    ];

    $this->discountify->setFields($dynamicFields);

    $priceValue = $this->discountify->getField($items, 'price');
    $quantityValue = $this->discountify->getField($items, 'quantity');
    $unknownField = $this->discountify->getField($items, 'unknown');

    expect($priceValue)->toBe($items['cost']);
    expect($quantityValue)->toBe($items['amount']);
    expect($unknownField)->toBe(null);
});

it('returns all dynamic field mappings', function () {
    $dynamicFields = [
        'price' => 'cost',
        'quantity' => 'qte',
    ];

    $this->discountify->setFields($dynamicFields);

    $fields = $this->discountify->getFields();

    expect($fields)->toBe($dynamicFields);
});

it('calculates total with discount using dynamically set custom field names', function () {
    $items = [
        ['id' => 'item1', 'qty' => 2, 'amount' => 20],
        ['id' => 'item2', 'qty' => 1, 'amount' => 20],
    ];

    $this->discountify->setFields([
        'price' => 'amount',
        'quantity' => 'qty',
    ])->setItems($items);

    $totalWithDiscount = $this->discountify->totalWithDiscount(50);

    expect($totalWithDiscount)->toBe(floatval(30));
});

it('calculates total with discount using dynamically set custom price field name', function () {
    $items = [
        ['id' => 'item1', 'quantity' => 2, 'amount' => 20],
        ['id' => 'item2', 'quantity' => 1, 'amount' => 20],
    ];

    $this->discountify->setField('price', 'amount')->setItems($items);

    $totalWithDiscount = $this->discountify->totalWithDiscount(50);

    expect($totalWithDiscount)->toBe(floatval(30));
});

it('can work correctly with class-based conditions', function () {

    $expextedResult = [
        'total' => 214.20,
        'subtotal' => 200.0,
        'tax_amount' => 38.0,
        'total_after_discount' => 180.00,
        'savings' => 23.80,
        'tax_rate' => 19.0,
        'discount_rate' => 10.0,
    ];

    Condition::discover(
        'Workbench\\App\\Conditions',
        workbench_path('app/Conditions')
    );

    // 10% Discount is applied by MoreThan1ProductsCondition class

    DiscountifyFacade::setItems($this->items)
        ->setGlobalTaxRate(19);

    $totalDetailed = DiscountifyFacade::totalDetailed();

    expect($totalDetailed)->toBe($expextedResult);

});

it('can create a new condition class', function () {

    $testConditionsPath = __DIR__.'/../workbench/app/Conditions';

    config(['discountify.condition_namespace' => 'Workbench\\App\\Conditions']);
    config(['discountify.condition_path' => $testConditionsPath]);

    $class = 'NewConditionClass';

    $filePath = "$testConditionsPath/{$class}.php";

    artisan('discountify:condition', [
        'name' => $class,
        '--slug' => 'CustomSlug',
        '--discount' => 15,
    ]);

    $this->assertTrue(File::exists($filePath));

    File::delete($filePath);

    $this->assertFalse(File::exists($filePath));
});

it('fires the DiscountAppliedEvent when conditions are met and event firing is enabled', function () {
    Event::fake();

    Config::set('discountify.fire_events', true);

    Condition::define('test_condition', fn () => true, 10);

    DiscountifyFacade::setItems($this->items);

    DiscountifyFacade::total();

    Event::assertDispatched(DiscountAppliedEvent::class);
});

it('does not fire the DiscountAppliedEvent when event firing is disabled', function () {
    Event::fake();

    Config::set('discountify.fire_events', false);

    Condition::define('test_condition', fn () => true, 10);

    DiscountifyFacade::setItems($this->items);

    DiscountifyFacade::total();

    Event::assertNotDispatched(DiscountAppliedEvent::class);
});

it('removes a coupon from the manager', function () {

    $coupon = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->discountify->addCoupon($coupon);

    $this->discountify->removeCoupon($coupon['code']);

    expect($this->discountify->getCoupon($coupon['code']))->toBeNull();
});

it('removes applied coupons correctly', function () {

    $coupon1 = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $coupon2 = [
        'code' => 'WELCOME21',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $this->discountify->coupons()->add($coupon1);
    $this->discountify->coupons()->add($coupon2);

    $this->discountify->applyCoupon('WELCOME20');
    $this->discountify->applyCoupon('WELCOME21');

    expect($this->discountify->coupons()->appliedCoupons())->not->toBeEmpty();

    $this->discountify->removeAppliedCoupons();

    expect($this->discountify->coupons()->appliedCoupons())->toBeEmpty();
});

it('returns an array of applied coupons correctly', function () {

    $coupon1 = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $coupon2 = [
        'code' => 'WELCOME21',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->discountify->coupons()->add($coupon1);
    $this->discountify->coupons()->add($coupon2);

    $this->discountify->applyCoupon('WELCOME20');

    $appliedCoupons = $this->discountify->getAppliedCoupons();

    expect($appliedCoupons)->toHaveCount(1);
    expect($appliedCoupons[0]['code'])->toBe($coupon1['code']);
    expect($appliedCoupons[0]['applied'])->toBeTrue();
});

it('clears all coupons correctly', function () {
    $coupon1 = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $coupon2 = [
        'code' => 'WELCOME21',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->discountify->coupons()->add($coupon1);
    $this->discountify->coupons()->add($coupon2);

    $this->discountify->applyCoupon('WELCOME20');

    $this->discountify->clearCoupons();
    $coupons = $this->discountify->coupons()->all();

    expect($coupons)->toBeEmpty();
});

it('clears all applied coupons correctly', function () {

    $coupon1 = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $coupon2 = [
        'code' => 'WELCOME21',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $this->discountify->coupons()->add($coupon1);
    $this->discountify->coupons()->add($coupon2);

    $this->discountify->applyCoupon('WELCOME20');
    $this->discountify->applyCoupon('WELCOME21');

    expect($this->discountify->coupons()->appliedCoupons())->not->toBeEmpty();

    $this->discountify->clearAppliedCoupons();

    expect($this->discountify->coupons()->appliedCoupons())->toBeEmpty();
});

it('dispatches CouponAppliedEvent if fire_events configuration is true', function () {

    Event::fake();

    Config::set('discountify.fire_events', true);
    $coupon = [
        'code' => 'WELCOME30',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->discountify->coupons()->add($coupon);

    $this->discountify->applyCoupon('WELCOME30');

    Event::assertDispatched(CouponAppliedEvent::class, function ($event) use ($coupon) {
        return $event->coupon['code'] === $coupon['code'];
    });
});

it('does not dispatch CouponAppliedEvent if fire_events configuration is false', function () {

    Event::fake();

    Config::set('discountify.fire_events', false);
    $coupon = [
        'code' => 'WELCOME30',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->discountify->coupons()->add($coupon);

    $this->discountify->applyCoupon('WELCOME30');

    Event::assertNotDispatched(CouponAppliedEvent::class);
});


it('calculates the total discount applied by coupons correctly', function () {

    $coupon1 = [
        'code' => 'WELCOME20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $coupon2 = [
        'code' => 'WELCOME21',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $this->discountify->coupons()->add($coupon1);
    $this->discountify->coupons()->add($coupon2);

    $this->discountify->applyCoupon('WELCOME20');
    $this->discountify->applyCoupon('WELCOME21');

    $totalDiscount = $this->discountify->getCouponDiscount();

    expect($totalDiscount)->toEqual(40);
});

it('calculates the global discount amount correctly', function () {

    $globalDiscountPercentage = 10;

    $this->discountify->setItems($this->items)
        ->setGlobalDiscount($globalDiscountPercentage);

    $globalDiscountAmount = $this->discountify->calculateGlobalDiscount();

    $expectedGlobalDiscountAmount = $this->discountify->calculateSubtotal() * ($globalDiscountPercentage / 100);

    expect($globalDiscountAmount)->toBe($expectedGlobalDiscountAmount);
});

it('calculates the subtotal amount correctly', function () {

    $items = [
        ['price' => 10, 'quantity' => 2],
        ['price' => 5, 'quantity' => 3],
    ];

    $this->discountify->setItems($items);

    $subtotal = $this->discountify->calculateSubtotal();

    $expectedSubtotal = (10 * 2) + (5 * 3);

    expect($subtotal)->toEqual($expectedSubtotal);
});

it('calculates total with coupon discounts applied', function () {

    $this->discountify->setItems($this->items)
        ->setGlobalDiscount(0)
        ->setGlobalTaxRate(0);

    $coupon1 = [
        'code' => 'TEST20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];
    $coupon2 = [
        'code' => 'HALFOFF',
        'discount' => 50,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->discountify->addCoupon($coupon1);
    $this->discountify->addCoupon($coupon2);

    $this->discountify->applyCoupon('TEST20');
    $this->discountify->applyCoupon('HALFOFF');

    $totalWithDiscount = $this->discountify->totalWithDiscount();

    expect($totalWithDiscount)->toBe(floatval(60));
});

it('calculates total with both global discount and coupon discounts applied', function () {

    $this->discountify->setItems($this->items)
        ->setGlobalDiscount(10)
        ->setGlobalTaxRate(0);

    $coupon1 = [
        'code' => 'TEST20',
        'discount' => 20,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $coupon2 = [
        'code' => 'HALFOFF',
        'discount' => 50,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ];

    $this->discountify->addCoupon($coupon1);
    $this->discountify->addCoupon($coupon2);

    $this->discountify->applyCoupon('TEST20');
    $this->discountify->applyCoupon('HALFOFF');

    $totalWithDiscount = $this->discountify->totalWithDiscount();

    expect($totalWithDiscount)->toBe(floatval(40));
});

it('applies single-use coupons only once', function () {

    $this->discountify->setItems($this->items)
        ->setGlobalDiscount(0)
        ->setGlobalTaxRate(0);

    $singleUseCoupon = [
        'code' => 'SINGLEUSE',
        'discount' => 50,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
        'singleUse' => true, // Mark coupon as single-use
    ];

    $this->discountify->addCoupon($singleUseCoupon);

    $this->discountify->applyCoupon('SINGLEUSE');

    $totalWithDiscount = $this->discountify->totalWithDiscount();

    expect($totalWithDiscount)->toBe(floatval(100));
});

it('calculates the total without any discounts applied', function () {

    $this->discountify->setItems($this->items)
        ->setGlobalDiscount(0)
        ->setGlobalTaxRate(0);

    $total = $this->discountify->total();

    expect($total)->toBe(floatval(200));
});

it('calculates total with both global rate and coupon discounts applied', function () {

    $this->discountify->setItems($this->items)
        ->setGlobalDiscount(0)
        ->setGlobalTaxRate(10)
        ->addCoupon([
            'code' => 'HALFOFF',
            'discount' => 50,
            'startDate' => now(),
            'endDate' => now()->addWeek(),
        ])->applyCoupon('HALFOFF');

    $total = $this->discountify->total();

    expect($total)->toBe(floatval(110));
});

it('applies a time-limited coupon', function () {

    Coupon::add([
        'code' => 'TIMELIMITED50',
        'discount' => 50,
        'singleUse' => true,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $discountedTotal = DiscountifyFacade::setItems($this->items)
        ->applyCoupon('TIMELIMITED50')
        ->total();

    expect($discountedTotal)->toBe(floatval(100));

});

it('applies a single-use coupon', function () {

    Coupon::add([
        'code' => 'SINGLEUSE30',
        'discount' => 30,
        'singleUse' => true,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $discountedTotal = DiscountifyFacade::setItems($this->items)
        ->applyCoupon('SINGLEUSE30')
        ->total();

    expect($discountedTotal)->toBe(floatval(140));

});

it('applies a restricted user coupon', function () {

    Coupon::add([
        'code' => 'RESTRICTED20',
        'discount' => 20,
        'userIds' => [123, 456], // Restricted to user IDs 123 and 456
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $discountedTotal = DiscountifyFacade::setItems($this->items)
        ->applyCoupon('RESTRICTED20', 123) // Applying to user ID 123
        ->total();

    expect($discountedTotal)->toBe(floatval(160));
});

it('applies a limited usage coupon', function () {

    $expextedResult = [
        'total' => 150.00,
        'subtotal' => 200.0,
        'tax_amount' => 0.0,
        'total_after_discount' => 150.00,
        'savings' => 50.00,
        'tax_rate' => 0.0,
        'discount_rate' => 25.0,
    ];

    Coupon::add([
        'code' => 'LIMITED25',
        'discount' => 25,
        'usageLimit' => 3, // Limited to 3 uses
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $discountedTotal = DiscountifyFacade::setItems($this->items)
        ->applyCoupon('LIMITED25')
        ->totalDetailed();

    expect($discountedTotal)->toBe($expextedResult);
});

it('calculates total with discount applied during the early spring sale period', function () {

    $expextedResult = [
        'total' => 76.5,
        'subtotal' => 90.0,
        'tax_amount' => 0.0,
        'total_after_discount' => 76.5,
        'savings' => 13.5,
        'tax_rate' => 0.0,
        'discount_rate' => 15.0,
    ];

    Carbon::setTestNow(Carbon::create(2024, 3, 10)); // date within the early spring sale period

    $isInTheDate = now()->between(
        Carbon::createFromDate(2024, 3, 1),
        Carbon::createFromDate(2024, 3, 22)->addDays(7)
    );

    Condition::add([
        [
            'slug' => 'promo_early_spring_sale_2024',
            'condition' => fn ($items) => $isInTheDate,
            'discount' => 15,
        ],
    ]);

    $cart = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 5,
            'price' => 10,
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'product_name' => 'Product 2',
            'quantity' => 2,
            'price' => 20,
        ],
    ];

    $total = DiscountifyFacade::setItems($cart)->totalDetailed();

    expect($isInTheDate)->toBeTrue();
    expect($total)->toBe($expextedResult);
});

it('calculates total without discount applied outside the early spring sale period', function () {

    $expextedResult = [
        'total' => 90.0,
        'subtotal' => 90.0,
        'tax_amount' => 0.0,
        'total_after_discount' => 90.0,
        'savings' => 0.0,
        'tax_rate' => 0.0,
        'discount_rate' => 0.0,
    ];

    Carbon::setTestNow(Carbon::create(2024, 2, 15)); // date outside the early spring sale period

    $isInTheDate = now()->between(
        Carbon::createFromDate(2024, 3, 1),
        Carbon::createFromDate(2024, 3, 22)->addDays(7)
    );

    Condition::add([
        [
            'slug' => 'promo_early_spring_sale_2024',
            'condition' => fn ($items) => $isInTheDate,
            'discount' => 15,
        ],
    ]);

    $cart = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 5,
            'price' => 10,
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'product_name' => 'Product 2',
            'quantity' => 2,
            'price' => 20,
        ],
    ];

    $total = DiscountifyFacade::setItems($cart)->totalDetailed();

    expect($isInTheDate)->toBeFalse();
    expect($total)->toBe($expextedResult); // Total without any discount applied
});

it('applies limited usage coupon only once', function () {

    Coupon::add([
        'code' => 'LIMITED45',
        'discount' => 45,
        'usageLimit' => 1, // Limited to 1 uses
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $items = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 5,
            'price' => 10,
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'product_name' => 'Product 2',
            'quantity' => 2,
            'price' => 25,
        ],
    ];

    $discountedTotal1 = DiscountifyFacade::setItems($items)
        ->applyCoupon('LIMITED45')
        ->total();

    $appleied1 = DiscountifyFacade::coupons()->appliedCoupons();

    $discountedTotal2 = DiscountifyFacade::setItems($items)
        ->applyCoupon('LIMITED45')
        ->total();

    $appleied2 = DiscountifyFacade::coupons()->appliedCoupons();

    expect($discountedTotal1)->toEqual(55.00);
    expect($appleied1)->not->toBeEmpty();
    expect($appleied1[0]['code'])->toEqual('LIMITED45');

    expect($discountedTotal2)->toEqual(100); // without the discount
    expect($appleied2)->toBeEmpty();

});

it('throws exception when quantity is zero', function () {
    $items1 = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 0,
            'price' => 10,
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'product_name' => 'Product 2',
            'quantity' => 0,
            'price' => 20,
        ],
    ];

    DiscountifyFacade::setItems($items1)
        ->setGlobalDiscount(200)
        ->setGlobalTaxRate(50)
        ->totalDetailed();

})->throws(ZeroQuantityException::class, 'Quantity cannot be zero.');

it('calculates total amount correctly with large quantities and high tax rate', function () {

    $expextedResult = [
        'total' => 54_000_000.00,
        'subtotal' => 30_000_000.00,
        'tax_amount' => 30_000_000.00,
        'total_after_discount' => 27_000_000.00,
        'savings' => 6_000_000.00,
        'tax_rate' => 100.00,
        'discount_rate' => 10.0,
    ];

    $items = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 1_000_000,
            'price' => 10,
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'product_name' => 'Product 2',
            'quantity' => 1_000_000,
            'price' => 20,
        ],
    ];

    DiscountifyFacade::setItems($items)
        ->setGlobalDiscount(10)
        ->setGlobalTaxRate(100);

    expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);
});

it('calculates total amount correctly with varying item prices and discounts', function () {

    $expextedResult = [
        'total' => 60.375,
        'subtotal' => 70.0,
        'tax_amount' => 10.5,
        'total_after_discount' => 52.50,
        'savings' => 20.125,
        'tax_rate' => 15.0,
        'discount_rate' => 25.0,
    ];

    $items = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 3,
            'price' => 15,
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'product_name' => 'Product 2',
            'quantity' => 1,
            'price' => 25,
        ],
    ];

    $totalDetailed = DiscountifyFacade::setItems($items)
        ->setGlobalDiscount(25)
        ->setGlobalTaxRate(15)
        ->totalDetailed();

    expect($totalDetailed)->toBe($expextedResult);
});

it('calculates total amount correctly with single high-priced item', function () {

    $expextedResult = [
        'total' => 858.00,
        'subtotal' => 1000.0,
        'tax_amount' => 100.0,
        'total_after_discount' => 780.0,
        'savings' => 242.00,
        'tax_rate' => 10.0,
        'discount_rate' => 22.0,
    ];

    $items = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 1,
            'price' => 1000,
        ],
    ];

    DiscountifyFacade::setItems($items)
        ->setGlobalDiscount(22)
        ->setGlobalTaxRate(10);

    expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);
});

it('calculates total amount correctly with various item quantities and discounts', function () {

    $expextedResult = [
        'total' => 110.16,
        'subtotal' => 120.0,
        'tax_amount' => 9.6, // 8.16
        'total_after_discount' => 102.00,
        'savings' => 19.44,
        'tax_rate' => 8.0,
        'discount_rate' => 15.0,
    ];

    $items = [
        [
            'id' => 1,
            'product_id' => 1,
            'product_name' => 'Product 1',
            'quantity' => 3,
            'price' => 20,
        ],
        [
            'id' => 2,
            'product_id' => 2,
            'product_name' => 'Product 2',
            'quantity' => 2,
            'price' => 30,
        ],
    ];

    DiscountifyFacade::setItems($items)
        ->setGlobalDiscount(15)
        ->setGlobalTaxRate(8);

    expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);
});

// it('handles negative discount rate properly', function () {

//     $items = [
//         [
//             'id' => 1,
//             'product_id' => 1,
//             'product_name' => 'Product 1',
//             'quantity' => 3,
//             'price' => 20,
//         ],
//         [
//             'id' => 2,
//             'product_id' => 2,
//             'product_name' => 'Product 2',
//             'quantity' => 2,
//             'price' => 30,
//         ],
//     ];

//     DiscountifyFacade::setItems($items)
//         ->setGlobalDiscount(-50) // Risky value: negative discount rate
//         ->setGlobalTaxRate(8);

//     $expextedResult = [
//         'total' => 76.5,
//         'subtotal' => 90.0,
//         'tax_amount' => 0.0,
//         'total_after_discount' => 76.5,
//         'savings' => 13.5,
//         'tax_rate' => 0.0,
//         'discount_rate' => 15.0,
//     ];

//     expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);
// });

// it('handles discount rate greater than 100% properly', function () {

//     $items = [
//         [
//             'id' => 1,
//             'product_id' => 1,
//             'product_name' => 'Product 1',
//             'quantity' => 3,
//             'price' => 20,
//         ],
//         [
//             'id' => 2,
//             'product_id' => 2,
//             'product_name' => 'Product 2',
//             'quantity' => 2,
//             'price' => 30,
//         ],
//     ];

//     $discountify = DiscountifyFacade::setItems($items)
//         ->setGlobalDiscount(150) // Risky value: discount rate greater than 100%
//         ->setGlobalTaxRate(8);

//     $expextedResult = [
//         'total' => 76.5,
//         'subtotal' => 90.0,
//         'tax_amount' => 0.0,
//         'total_after_discount' => 76.5,
//         'savings' => 13.5,
//         'tax_rate' => 0.0,
//         'discount_rate' => 15.0,
//     ];

//     expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);

// });

// it('handles negative tax rate properly', function () {

//     $items = [
//         [
//             'id' => 1,
//             'product_id' => 1,
//             'product_name' => 'Product 1',
//             'quantity' => 3,
//             'price' => 20,
//         ],
//         [
//             'id' => 2,
//             'product_id' => 2,
//             'product_name' => 'Product 2',
//             'quantity' => 2,
//             'price' => 30,
//         ],
//     ];

//     DiscountifyFacade::setItems($items)
//         ->setGlobalDiscount(10)
//         ->setGlobalTaxRate(-5); // Risky value: negative tax rate

//     $expextedResult = [
//         'total' => 76.5,
//         'subtotal' => 90.0,
//         'tax_amount' => 0.0,
//         'total_after_discount' => 76.5,
//         'savings' => 13.5,
//         'tax_rate' => 0.0,
//         'discount_rate' => 15.0,
//     ];

//     expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);
// });

// it('handles tax rate greater than 100% properly', function () {

//     $items = [
//         [
//             'id' => 1,
//             'product_id' => 1,
//             'product_name' => 'Product 1',
//             'quantity' => 3,
//             'price' => 20,
//         ],
//         [
//             'id' => 2,
//             'product_id' => 2,
//             'product_name' => 'Product 2',
//             'quantity' => 2,
//             'price' => 30,
//         ],
//     ];

//     DiscountifyFacade::setItems($items)
//         ->setGlobalDiscount(10)
//         ->setGlobalTaxRate(150); // Risky value: tax rate greater than 100%

//     $expextedResult = [
//         'total' => 76.5,
//         'subtotal' => 90.0,
//         'tax_amount' => 0.0,
//         'total_after_discount' => 76.5,
//         'savings' => 13.5,
//         'tax_rate' => 0.0,
//         'discount_rate' => 15.0,
//     ];

//     expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);
// });

// it('handles extreme discount and tax rates properly', function () {

//     $items = [
//         [
//             'id' => 1,
//             'product_id' => 1,
//             'product_name' => 'Product 1',
//             'quantity' => 3,
//             'price' => 20,
//         ],
//         [
//             'id' => 2,
//             'product_id' => 2,
//             'product_name' => 'Product 2',
//             'quantity' => 2,
//             'price' => 30,
//         ],
//     ];

//     DiscountifyFacade::setItems($items)
//         ->setGlobalDiscount(500) // Risky value: extremely high discount rate
//         ->setGlobalTaxRate(-200); // Risky value: extremely low (negative) tax rate

//     $expextedResult = [
//         'total' => 76.5,
//         'subtotal' => 90.0,
//         'tax_amount' => 0.0,
//         'total_after_discount' => 76.5,
//         'savings' => 13.5,
//         'tax_rate' => 0.0,
//         'discount_rate' => 15.0,
//     ];

//     expect(DiscountifyFacade::totalDetailed())->toBe($expextedResult);
// });
