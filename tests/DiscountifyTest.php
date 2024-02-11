<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Safemood\Discountify\ConditionManager;
use Safemood\Discountify\CouponManager;
use Safemood\Discountify\Discountify;
use Safemood\Discountify\Events\DiscountAppliedEvent;
use Safemood\Discountify\Exceptions\DuplicateSlugException;
use Safemood\Discountify\Facades\Condition;
use Safemood\Discountify\Facades\Coupon;
use Safemood\Discountify\Facades\Discountify as DiscountifyFacade;

use function Orchestra\Testbench\workbench_path;
use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->items = [
        ['id' => '1', 'quantity' => 2, 'price' => 50],
        ['id' => '2', 'quantity' => 1, 'price' => 100, 'type' => 'special'],
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

    $this->discountify->setGlobalDiscount(10);

    expect($this->discountify->getGlobalDiscount())->toBe(10);
});

it('can set and get global tax rate', function () {

    $this->discountify->setGlobalTaxRate(5);

    expect($this->discountify->getGlobalTaxRate())->toBe(floatval(5));
});

it('can set and get condition manager', function () {

    expect($this->discountify->conditions())->toBeInstanceOf(ConditionManager::class);
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
        ->setGlobalTaxRate(10)
        ->discount(10)
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

it('can add conditions using ConditionManager', function () {
    $conditionManager = new ConditionManager();
    $conditionManager
        ->define('more_than_2_products_10', fn (array $items) => count($items) > 2, 10)
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

    $conditions = $conditionManager->getConditions();

    expect($conditions)->toHaveCount(4);
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
    $taxRate = DiscountifyFacade::taxAmount();

    expect($total)->toBe(floatval(208));
    expect($totalWithDiscount)->toBe(floatval(170));
    expect($totalWithTaxes)->toBe(floatval(238));
    expect($taxRate)->toBe(floatval(38));
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

it('throws exception when slug is not provided in define method', function () {
    expect(fn () => Condition::define('', fn ($items) => count($items) > 5, 10))
        ->toThrow(Exception::class, 'Slug must be provided.');
});

it('throws exception when slug is not provided in defineIf method', function () {
    expect(fn () => Condition::defineIf('', true, 10))
        ->toThrow(Exception::class, 'Slug must be provided.');
});

it('can auto register condition classes', function () {

    Condition::discover(
        'Workbench\\App\\Conditions',
        workbench_path('app/Conditions')
    );

    $definedConditions = Condition::getConditions();

    expect($definedConditions)
        ->toBeArray()
        ->toHaveCount(2)
        ->each(function ($item) {
            $item->toHaveKeys([
                'slug',
                'condition',
                'discount',
            ]);
            $item->slug->toBeString();
            $item->condition->toBeInstanceOf(Closure::class);
            $item->discount->toBeFloat();
        });
});

it('can work correctly with class-based conditions', function () {

    $items = [
        ['id' => '1', 'quantity' => 2, 'price' => 50],
        ['id' => '2', 'quantity' => 1, 'price' => 100, 'type' => 'special'],
    ];

    Condition::discover(
        'Workbench\\App\\Conditions',
        workbench_path('app/Conditions')
    );

    DiscountifyFacade::setItems($items)
        ->setGlobalTaxRate(19);

    $total = DiscountifyFacade::total();
    $totalWithDiscount = DiscountifyFacade::totalWithDiscount();
    $totalWithTaxes = DiscountifyFacade::tax();
    $taxRate = DiscountifyFacade::taxAmount();

    expect($total)->toBe(floatval(138));
    expect($totalWithDiscount)->toBe(floatval(100));
    expect($totalWithTaxes)->toBe(floatval(238));
    expect($taxRate)->toBe(floatval(38));
});

test('it skips conditions marked with "skip"', function () {

    $conditions = [
        ['slug' => 'condition_1', 'condition' => fn () => true, 'discount' => 10],
        ['slug' => 'condition_2', 'condition' => fn () => false, 'discount' => 20, 'skip' => true],
        ['slug' => 'condition_3', 'condition' => fn () => true, 'discount' => 30, 'skip' => false],
    ];

    Condition::add($conditions);

    $finalConditions = Condition::getConditions();

    $this->assertCount(2, $finalConditions);
});

test('it throws an exception for conditions without "slug"', function () {
    $this->expectException(InvalidArgumentException::class);

    $conditions = [
        ['condition' => fn () => true, 'discount' => 10],
    ];

    Condition::add($conditions);
});

test('it skips class-based conditions marked with "skip"', function () {

    Condition::discover(
        'Workbench\\App\\Conditions',
        workbench_path('app/Conditions')
    );

    $conditions = Condition::getConditions();

    expect($conditions)->toHaveCount(2);
});

it('ensures condition slugs are unique', function () {

    Condition::define('unique_condition', fn () => true, 10);

    $this->expectException(DuplicateSlugException::class);

    Condition::define('unique_condition', fn () => true, 15);
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

it('calculates total with both global rate and coupon discounts applied (discount after tax)', function () {

    $this->discountify->setItems($this->items)
        ->setGlobalDiscount(0)
        ->setGlobalTaxRate(10)
        ->addCoupon([
            'code' => 'HALFOFF',
            'discount' => 50,
            'startDate' => now(),
            'endDate' => now()->addWeek(),
        ])->applyCoupon('HALFOFF');

    $total = $this->discountify->total(false);

    expect($total)->toBe(floatval(110));
});

it('calculates total with both global rate and coupon discounts applied (discount before tax)', function () {

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

    expect($total)->toBe(floatval(120));
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
        'code' => 'SINGLEUSE50',
        'discount' => 50,
        'singleUse' => true,
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $discountedTotal = DiscountifyFacade::setItems($this->items)
        ->applyCoupon('SINGLEUSE50')
        ->total();

    expect($discountedTotal)->toBe(floatval(100));
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
    Coupon::add([
        'code' => 'LIMITED25',
        'discount' => 25,
        'usageLimit' => 3, // Limited to 3 uses
        'startDate' => now(),
        'endDate' => now()->addWeek(),
    ]);

    $discountedTotal = DiscountifyFacade::setItems($this->items)
        ->applyCoupon('LIMITED25')
        ->total();

    expect($discountedTotal)->toBe(floatval(150));
});
