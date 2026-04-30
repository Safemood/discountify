<?php

declare(strict_types=1);

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;
use Safemood\Discountify\Support\ConditionEngine;

/**
 * @method static \Safemood\Discountify\Support\ConditionEngine define(string $slug, callable $condition, float $discount, bool $skip = false)
 * @method static \Safemood\Discountify\Support\ConditionEngine defineIf(string $slug, bool $isAcceptable, float $discount)
 * @method static \Safemood\Discountify\Support\ConditionEngine add(array $conditions)
 * @method static \Safemood\Discountify\Support\ConditionEngine addClassCondition(object $instance)
 * @method static \Safemood\Discountify\Support\ConditionEngine skip(string $slug)
 * @method static \Safemood\Discountify\Support\ConditionEngine flush()
 * @method static \Safemood\Discountify\Support\ConditionEngine discover()
 * @method static \Safemood\Discountify\Support\ConditionEngine loadFromDatabase()
 * @method static \Illuminate\Support\Collection<int, array{slug:string,discount:float,type:\Safemood\Discountify\Enums\DiscountType}> evaluate(array $items)
 * @method static float totalDiscount(array $items, float $subtotal)
 * @method static array all()
 * @method static array getConditions()
 *
 * @see ConditionEngine
 */
class Condition extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return ConditionEngine::class;
    }
}
