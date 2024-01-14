<?php

use Safemood\Discountify\ConditionManager;
use Safemood\Discountify\Discountify;
use Safemood\Discountify\Facades\Condition;
use Safemood\Discountify\Facades\Discountify as DiscountifyFacade;

beforeEach(function () {
    $this->items = [
        ['id' => '1', 'quantity' => 2, 'price' => 50],
        ['id' => '2', 'quantity' => 1, 'price' => 100, 'type' => 'special'],
    ];

    $this->conditionManager = new ConditionManager();
    $this->discountify = new Discountify($this->conditionManager);
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

    $this->discountify->setGlobalTaxRate(5.0);

    expect($this->discountify->getGlobalTaxRate())->toBe(5.0);
});

it('can set and get condition manager', function () {

    expect($this->discountify->getConditionManager())->toBeInstanceOf(ConditionManager::class);
});

it('can calculate total with discount', function () {

    $this->discountify->setItems($this->items);

    $totalWithDiscount = $this->discountify->totalWithDiscount(10);

    expect($totalWithDiscount)->toBe(floatval(180));
});

it('can calculate total with taxes', function () {

    $this->discountify->setItems($this->items);

    $totalWithTaxes = $this->discountify->tax(19);

    expect($totalWithTaxes)->toBe(floatval(238));
});

it('can get total', function () {

    $this->discountify->setItems($this->items);

    $total = $this->discountify->setGlobalTaxRate(19)
        ->discount(10)
        ->total();

    expect($total)->toBe(floatval(218));
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
        ->define(fn (array $items) => count($items) > 2, 20)
        ->add([
            [
                'condition' => fn ($items) => count($items) > 3,
                'discount' => 15,
            ],
            [
                'condition' => fn ($items) => in_array('special', array_column($items, 'type')),
                'discount' => 10,
            ],
        ])
        ->defineIf(true, 10);

    $conditions = $conditionManager->getConditions();

    expect($conditions)->toHaveCount(4);
});

it('can use Condition facade', function () {

    Condition::define(fn (array $items) => count($items) > 2, 20)
        ->add([
            [
                'condition' => fn ($items) => count($items) > 3,
                'discount' => 15,
            ],
            [
                'condition' => fn ($items) => in_array('special', array_column($items, 'type')),
                'discount' => 10,
            ],
        ])
        ->defineIf(true, 10);

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
    $taxRate = DiscountifyFacade::taxAmout(19);

    expect($total)->toBe(floatval(208));
    expect($totalWithDiscount)->toBe(floatval(170));
    expect($totalWithTaxes)->toBe(floatval(238));
    expect($taxRate)->toBe(floatval(38));
});
