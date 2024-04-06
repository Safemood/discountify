<?php

declare(strict_types=1);

use Safemood\Discountify\ConditionManager;
use Safemood\Discountify\Exceptions\DuplicateSlugException;
use Safemood\Discountify\Facades\Condition;

use function Orchestra\Testbench\workbench_path;

beforeEach(function () {

    $this->conditionManger = new ConditionManager();
});

it('can add conditions using ConditionManager', function () {

    $this->conditionManger
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

    $conditions = $this->conditionManger->getConditions();

    expect($conditions)->toHaveCount(4);
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
