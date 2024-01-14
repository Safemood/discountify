<?php

namespace Safemood\Discountify;

use Safemood\Discountify\Concerns\HasCalculations;
use Safemood\Discountify\Contracts\DiscountifyInterface;

/**
 * Class Discountify
 *
 * @method array getConditions()
 * @method ConditionManager getConditionManager()
 * @method float getGlobalTaxRate()
 * @method int getGlobalDiscount()
 * @method array getItems()
 * @method float subtotal()
 * @method float tax()
 * @method float taxAmount(?float $globalTaxRate = null)
 * @method float total()
 * @method float totalWithDiscount(?float $globalDiscount = null)
 * @method self discount(float $globalDiscount)
 */
class Discountify implements DiscountifyInterface
{
    use HasCalculations;

    protected array $items;

    protected int $globalDiscount;

    protected float $globalTaxRate;

    /**
     * @var ConditionManager
     */
    protected $conditionManager;

    /**
     * Discountify constructor.
     */
    public function __construct(
        ConditionManager $conditionManager
    ) {
        $this->setGlobalDiscount(config('discountify.global_discount'));
        $this->setGlobalTaxRate(config('discountify.global_tax_rate'));
        $this->setConditionManager($conditionManager);
    }

    /**
     * Set the global discount.
     *
     * @param  int  $globalDiscount
     */
    public function discount(float $globalDiscount): self
    {
        $this->globalDiscount = $globalDiscount;

        return $this;
    }

    /**
     * Evaluate conditions and calculate the total discount.
     */
    public function evaluateConditions(): float
    {
        return array_reduce(
            $this->conditionManager->getConditions(),
            function ($discount, $condition) {
                $result = is_callable($condition['condition']) ? $condition['condition']($this->items) : $condition['condition'];

                return $discount + match (true) {
                    $result === true => $this->calculateSubtotal() * ($condition['discount'] / 100),
                    default => $condition['discount'],
                };
            },
            0
        );
    }

    /**
     * Get the applied conditions.
     */
    public function getConditions(): array
    {
        return $this->conditionManager->getConditions();
    }

    /**
     * Get the ConditionManager instance.
     */
    public function getConditionManager(): ConditionManager
    {
        return $this->conditionManager;
    }

    /**
     * Get the global discount percentage.
     */
    public function getGlobalDiscount(): int
    {
        return $this->globalDiscount;
    }

    /**
     * Get the global tax rate.
     */
    public function getGlobalTaxRate(): float
    {
        return $this->globalTaxRate;
    }

    /**
     * Get the items in the cart.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Set the ConditionManager instance.
     */
    public function setConditionManager(ConditionManager $conditionManager): self
    {
        $this->conditionManager = $conditionManager;

        return $this;
    }

    /**
     * Set the global discount.
     */
    public function setGlobalDiscount(int $globalDiscount): self
    {
        $this->globalDiscount = $globalDiscount ?? 0;

        return $this;
    }

    /**
     * Set the global tax rate.
     */
    public function setGlobalTaxRate(float $globalTaxRate): self
    {
        $this->globalTaxRate = $globalTaxRate ?? 0;

        return $this;
    }

    /**
     * Set the items in the cart.
     */
    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Calculate the subtotal of the cart.
     */
    public function subtotal(): float
    {
        return $this->calculateSubtotal();
    }

    /**
     * Calculate the total tax of the cart.
     */
    public function tax(?float $globalTaxRate = null): float
    {
        return $this->calculateTotalWithTaxes($globalTaxRate);
    }

    /**
     * Calculate the total tax amount of the cart.
     */
    public function taxAmount(?float $globalTaxRate = null): float
    {
        return $this->calculateGlobalTax($globalTaxRate);
    }

    /**
     * Calculate the final total of the cart.
     */
    public function total(): float
    {
        return $this->calculateFinalTotal();
    }

    /**
     * Calculate the total with applied discount.
     */
    public function totalWithDiscount(?float $globalDiscount = null): float
    {
        return $this->calculateDiscount($globalDiscount);
    }
}
