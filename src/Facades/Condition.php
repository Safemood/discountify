<?php

namespace Safemood\Discountify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Condition
 *
 * @method static \Safemood\Discountify\ConditionManager add(array $conditions)
 * @method static \Safemood\Discountify\ConditionManager define(callable $condition, float $discountPercentage)
 * @method static \Safemood\Discountify\ConditionManager defineIf(bool $condition, float $discountPercentage)
 * @method static array getConditions()
 * @method static array getItems()
 *
 * @see \Safemood\Discountify\ConditionManager
 */
class Condition extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Safemood\Discountify\ConditionManager::class;
    }
}
