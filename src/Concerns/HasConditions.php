<?php

namespace Safemood\Discountify\Concerns;

use Safemood\Discountify\Discountify;

/**
 * Trait HasConditions
 */
trait HasConditions
{
    /**
     * Define multiple conditions at once.
     */
    public function add(array $conditions): Discountify
    {
        $this->conditions()->add($conditions);

        return $this;
    }

    /**
     * Define a condition with a callback and a discount percentage.
     */
    public function define(string $slug, callable $condition, float $discount, bool $skip = false): Discountify
    {
        $this->conditions()->define($slug, $condition, $discount, $skip);

        return $this;
    }

    /**
     * Define a condition based on a boolean value and a discount percentage.
     */
    public function defineIf(string $slug, bool $isAcceptable, float $discount): Discountify
    {
        $this->conditions()->defineIf($slug, $isAcceptable, $discount);

        return $this;
    }

    /**
     * Get the applied conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions()->getConditions();
    }
}
