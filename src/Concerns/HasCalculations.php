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
    public function calculateDiscount(?float $globalDiscount = null): float
    {
        $this->globalDiscount = $globalDiscount ?? $this->globalDiscount;

        $total = $this->calculateTotal();
        $discount = $this->calculateGlobalDiscount() + $this->evaluateConditions();

        return max(0, $total - $discount);
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
                return $total + ($item['quantity'] * $item['price']);
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
    public function calculateFinalTotal(): float
    {
        return $this->calculateDiscount() + $this->calculateGlobalTax();
    }
}
