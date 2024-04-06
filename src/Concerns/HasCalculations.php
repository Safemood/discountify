<?php

declare(strict_types=1);

namespace Safemood\Discountify\Concerns;

use Safemood\Discountify\Exceptions\ZeroQuantityException;

/**
 * Trait HasCalculations
 */
trait HasCalculations
{
    /**
     * Calculate the global discount amount.
     */
    public function calculateGlobalDiscount(): float
    {

        return $this->calculateSubtotal() * ($this->getGlobalDiscount() / 100);
    }

    /**
     * Calculate total with global tax rate.
     */
    public function calculateTotalWithTaxes(?float $globalTaxRate = null): float
    {
        $this->globalTaxRate = $globalTaxRate ?? $this->getGlobalTaxRate();

        $total = $this->calculateSubtotal();
        $tax = $this->calculateGlobalTax();

        return $total + $tax;
    }

    public function discountRate(?float $globalDiscount = null): float
    {
        $globalDiscount = $globalDiscount ?? $this->getGlobalDiscount();

        $couponDiscount = $this->couponManager->couponDiscount();

        $conditionDiscount = $this->conditionDiscount();

        $totalDiscountRate = $globalDiscount + $conditionDiscount + $couponDiscount;

        return min($totalDiscountRate, 100);
    }

    /**
     * Calculate total with global tax rate.
     */
    public function calculateGlobalTax(?float $globalTaxRate = null): float
    {
        $taxRate = $globalTaxRate ?? $this->getGlobalTaxRate();

        $total = $this->calculateSubtotal();

        return $total * ($taxRate / 100);
    }

    /**
     * Get the subtotal amount.
     */
    public function calculateSubtotal(): float
    {
        if (! is_array($this->items)) {
            return 0;
        }

        return array_reduce(
            $this->items,
            function ($total, $item) {

                $quantity = $this->getField($item, 'quantity');
                $price = $this->getField($item, 'price');

                if ($quantity === 0) {
                    throw new ZeroQuantityException();
                }

                return $total + ($quantity * $price);
            },
            0
        );
    }

    /**
     * Get the tax amount.
     */
    public function calculateTaxAmount(bool $afterDiscount = false): float
    {
        $subTotal = $afterDiscount ? $this->calculateTotalAfterDiscount() : $this->calculateSubtotal();

        return $subTotal * ($this->getGlobalTaxRate() / 100);
    }

    public function calculateSavings(?float $globalDiscount = null): float
    {
        return $this->calculateTotalWithTaxes() * ($this->discountRate($globalDiscount) / 100);
    }

    public function calculateTotalAfterDiscount(?float $globalDiscount = null)
    {
        $subTotal = $this->calculateSubtotal();

        return $subTotal - ($subTotal * ($this->discountRate($globalDiscount) / 100));
    }

    /**
     * Get the total amount (after discounts and taxes).
     */
    public function calculateFinalTotal(): float
    {
        $total = $this->calculateTotalWithTaxes();

        $discountRate = $this->discountRate();

        $discountedTotal = $total * (1 - $discountRate / 100);

        return max(0, $discountedTotal);
    }

    /**
     * Calculate and return various details related to the final total.
     */
    public function calculateFinalTotalDetails(): array
    {
        return [
            'total' => round($this->calculateFinalTotal(), 3),
            'subtotal' => $this->calculateSubtotal(),
            'tax_amount' => $this->calculateTaxAmount(),
            //'total_after_tax' => $this->calculateTotalWithTaxes(),
            'total_after_discount' => $this->calculateTotalAfterDiscount(),
            'savings' => round($this->calculateSavings(), 3),
            'tax_rate' => $this->getGlobalTaxRate(),
            'discount_rate' => $this->discountRate(),
        ];
    }
}
