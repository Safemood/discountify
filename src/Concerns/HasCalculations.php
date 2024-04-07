<?php

declare(strict_types=1);

namespace Safemood\Discountify\Concerns;

use Safemood\Discountify\Exceptions\ZeroQuantityException;

/**
 * Trait HasCalculations
 *
 * This trait provides methods for performing various calculations within Discountify.
 */
trait HasCalculations
{
    /**
     * Calculate the global discount amount.
     *
     * @return float The global discount amount.
     */
    public function calculateGlobalDiscount(): float
    {
        return $this->calculateSubtotal() * ($this->getGlobalDiscount() / 100);
    }

    /**
     * Calculate total with global tax rate.
     *
     * @param  float|null  $globalTaxRate  Optional. The global tax rate. Defaults to null.
     * @return float The total with taxes.
     */
    public function calculateTotalWithTaxes(?float $globalTaxRate = null): float
    {
        $this->globalTaxRate = $globalTaxRate ?? $this->getGlobalTaxRate();

        $total = $this->calculateSubtotal();
        $tax = $this->calculateGlobalTax();

        return $total + $tax;
    }

    /**
     * Calculate the total discount rate.
     *
     * @param  float|null  $globalDiscount  Optional. The global discount rate. Defaults to null.
     * @return float The total discount rate.
     */
    public function discountRate(?float $globalDiscount = null): float
    {
        $globalDiscount = $globalDiscount ?? $this->getGlobalDiscount();

        $couponDiscount = $this->couponManager->couponDiscount();
        $conditionDiscount = $this->conditionDiscount();

        $totalDiscountRate = $globalDiscount + $conditionDiscount + $couponDiscount;

        return min($totalDiscountRate, 100);
    }

    /**
     * Calculate the global tax amount.
     *
     * @param  float|null  $globalTaxRate  Optional. The global tax rate. Defaults to null.
     * @return float The global tax amount.
     */
    public function calculateGlobalTax(?float $globalTaxRate = null): float
    {
        $taxRate = $globalTaxRate ?? $this->getGlobalTaxRate();

        $total = $this->calculateSubtotal();

        return $total * ($taxRate / 100);
    }

    /**
     * Calculate the subtotal amount.
     *
     * @return float The subtotal amount.
     *
     * @throws ZeroQuantityException If any item in the cart has a quantity of zero.
     */
    public function calculateSubtotal(): float
    {

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
     * Calculate the tax amount.
     *
     * @param  bool  $afterDiscount  Optional. Whether to calculate tax after applying discounts. Defaults to false.
     * @return float The tax amount.
     */
    public function calculateTaxAmount(?float $globalTaxRate = null, bool $afterDiscount = false): float
    {
        $subTotal = $afterDiscount ? $this->calculateTotalAfterDiscount() : $this->calculateSubtotal();

        return $subTotal * ($globalTaxRate ?? $this->getGlobalTaxRate() / 100);
    }

    /**
     * Calculate the savings amount.
     *
     * @param  float|null  $globalDiscount  Optional. The global discount rate. Defaults to null.
     * @return float The savings amount.
     */
    public function calculateSavings(?float $globalDiscount = null): float
    {
        return $this->calculateTotalWithTaxes() * ($this->discountRate($globalDiscount) / 100);
    }

    /**
     * Calculate the total amount after applying discounts.
     *
     * @param  float|null  $globalDiscount  Optional. The global discount rate. Defaults to null.
     * @return float The total after applying discounts.
     */
    public function calculateTotalAfterDiscount(?float $globalDiscount = null): float
    {
        $subTotal = $this->calculateSubtotal();

        return $subTotal - ($subTotal * ($this->discountRate($globalDiscount) / 100));
    }

    /**
     * Calculate the final total amount (after discounts and taxes).
     *
     * @return float The final total amount.
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
     *
     * @return array An array containing various details related to the final total.
     */
    public function calculateFinalTotalDetails(): array
    {
        return [
            'total' => round($this->calculateFinalTotal(), 3),
            'subtotal' => $this->calculateSubtotal(),
            'tax_amount' => $this->calculateTaxAmount(),
            'total_after_discount' => $this->calculateTotalAfterDiscount(),
            'savings' => round($this->calculateSavings(), 3),
            'tax_rate' => $this->getGlobalTaxRate(),
            'discount_rate' => $this->discountRate(),
        ];
    }
}
