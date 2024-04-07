<?php

declare(strict_types=1);

namespace Safemood\Discountify\Concerns;

use Safemood\Discountify\Discountify;

/**
 * Trait HasConditions
 *
 * This trait provides methods for managing conditions within Discountify.
 */
trait HasConditions
{
    /**
     * Define multiple conditions at once.
     *
     * @param  array  $conditions  An array of conditions to add.
     * @return Discountify The Discountify instance.
     */
    public function add(array $conditions): Discountify
    {
        $this->conditions()->add($conditions);

        return $this;
    }

    /**
     * Define a condition with a callback and a discount percentage.
     *
     * @param  string  $slug  The unique identifier for the condition.
     * @param  callable  $condition  The callback function to evaluate the condition.
     * @param  float  $discount  The discount percentage to apply if the condition is met.
     * @param  bool  $skip  Optional. Whether to skip applying the discount if the condition is met. Defaults to false.
     * @return Discountify The Discountify instance.
     */
    public function define(string $slug, callable $condition, float $discount, bool $skip = false): Discountify
    {
        $this->conditions()->define($slug, $condition, $discount, $skip);

        return $this;
    }

    /**
     * Define a condition based on a boolean value and a discount percentage.
     *
     * @param  string  $slug  The unique identifier for the condition.
     * @param  bool  $isAcceptable  The boolean value indicating if the condition is met.
     * @param  float  $discount  The discount percentage to apply if the condition is met.
     * @return Discountify The Discountify instance.
     */
    public function defineIf(string $slug, bool $isAcceptable, float $discount): Discountify
    {
        $this->conditions()->defineIf($slug, $isAcceptable, $discount);

        return $this;
    }

    /**
     * Get the applied conditions.
     *
     * @return array An array containing the applied conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions()->getConditions();
    }
}
