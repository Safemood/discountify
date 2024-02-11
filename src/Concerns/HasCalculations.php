<?php

namespace Safemood\Discountify\Concerns;

/**
 * Trait HasCalculations
 */
trait HasCalculations
{
    /**
     * Calculate the global discount amount.
     */
    private function calculateGlobalDiscount(): float
    {
        return $this->calculateTotal() * ($this->globalDiscount / 100);
    }

    /**
     * Calculate total with global tax rate.
     */
    public function calculateTotalWithTaxes(?float $globalTaxRate = null): float
    {
        $this->globalTaxRate = $globalTaxRate ?? $this->globalTaxRate;

        $total = $this->calculateTotal();
        $tax = $this->calculateGlobalTax();

        return $total + $tax;
    }

    /**
     * Calculate total with the discount.
     */
    public function calculateDiscount(?int $globalDiscount = null): float
    {
        $this->globalDiscount = $globalDiscount ?? $this->globalDiscount;

        $total = $this->calculateTotal();
        $globalDiscount = $this->calculateGlobalDiscount();
        $couponDiscount = ($this->couponManager->couponDiscount() / 100) * $total;
        $discount = $globalDiscount + $couponDiscount + $this->evaluateConditions();

        return max(0, $total - $discount);
    }

    /**
     * Calculate total with the discount after tax.
     */
    public function calculateDiscountAfterTax(?int $globalDiscount = null): float
    {

        $total = $this->calculateTotal() + $this->calculateGlobalTax();
        $globalDiscount = $this->calculateGlobalDiscount();
        $couponDiscount = ($this->couponManager->couponDiscount() / 100) * $total;
        $discount = $globalDiscount + $couponDiscount + $this->evaluateConditions();

        return max(0, $total - $discount);
    }

    /**
     * Calculate total with the discount before tax.
     */
    public function calculateDiscountBeforeTax(?int $globalDiscount = null): float
    {
        return $this->calculateDiscount($globalDiscount) + $this->calculateGlobalTax();
    }

    /**
     * Calculate total with global tax rate.
     */
    public function calculateGlobalTax(?float $globalTaxRate = null): float
    {
        $this->globalTaxRate = $globalTaxRate ?? $this->globalTaxRate;

        $total = $this->calculateTotal();

        return $total * ($this->globalTaxRate / 100);
    }

    /**
     * Calculate total amount based on items.
     */
    private function calculateTotal(): float
    {
        if (! is_array($this->items)) {
            return 0;
        }

        return array_reduce(
            $this->items,
            function ($total, $item) {
                return $total + ($this->getField($item, 'quantity') * $this->getField($item, 'price'));
            },
            0
        );
    }

    /**
     * Get the subtotal amount.
     */
    public function calculateSubtotal(): float
    {
        return $this->calculateTotal();
    }

    /**
     * Get the tax amount.
     */
    public function taxAmout(): float
    {
        return $this->calculateGlobalTax();
    }

    /**
     * Get the total amount (after discounts and taxes).
     */
    public function calculateFinalTotal(bool $beforeTax = true): float
    {

        return $beforeTax ?
            $this->calculateDiscountBeforeTax()
            : $this->calculateDiscountAfterTax();
    }
}
