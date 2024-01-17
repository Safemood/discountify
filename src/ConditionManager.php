<?php

namespace Safemood\Discountify;

use InvalidArgumentException;
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
    public function add(array $conditions): self
    {
        $this->conditions = array_merge($this->conditions, $conditions);

        return $this;
    }

    /**
     * Define a condition with a callback and a discount percentage.
     *
     * @return $this
     */
    public function define(string $slug, callable $condition, float $discountPercentage): self
    {
        if (empty($slug)) {
            throw new InvalidArgumentException('Slug must be provided.');
        }

        $this->conditions[] = compact('slug', 'condition', 'discountPercentage');

        return $this;
    }

    /**
     * Define a condition based on a boolean value and a discount percentage.
     *
     * @return $this
     */
    public function defineIf(string $slug, bool $isAcceptable, float $discountPercentage): self
    {
        return $this->define($slug, fn () => $isAcceptable, $discountPercentage);
    }

    /**
     * Get all defined conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}
