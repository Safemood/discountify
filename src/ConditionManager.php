<?php

namespace Safemood\Discountify;

use Safemood\Discountify\Contracts\ConditionManagerInterface;

/**
 * Class ConditionManager
 */
class ConditionManager implements ConditionManagerInterface
{
    protected array $conditions = [];

    /**
     * Define multiple conditions at once.
     *
     * @return $this
     */
    public function add(array $conditions)
    {
        $this->conditions = array_merge($this->conditions, $conditions);

        return $this;
    }

    /**
     * Define a condition with a callback and a discount percentage.
     *
     * @return $this
     */
    public function define(callable $condition, float $discountPercentage)
    {
        $this->conditions[] = ['condition' => $condition, 'discount' => $discountPercentage];

        return $this;
    }

    /**
     * Define a condition based on a boolean value and a discount percentage.
     *
     * @return $this
     */
    public function defineIf(bool $condition, float $discountPercentage)
    {
        $this->conditions[] = ['condition' => $condition, 'discount' => $discountPercentage];

        return $this;
    }

    /**
     * Get all defined conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}
