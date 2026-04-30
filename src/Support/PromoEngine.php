<?php

declare(strict_types=1);

namespace Safemood\Discountify\Support;

use Illuminate\Support\Collection;
use Safemood\Discountify\Enums\DiscountType;
use Safemood\Discountify\Models\Promo;

/**
 * PromoEngine — auto-applies eligible promotional discounts.
 *
 * No coupon code needed; promos fire automatically when their
 * conditions are satisfied. Stacking follows priority order.
 */
final class PromoEngine
{
    private int|string|null $userId = null;

    public function forUser(int|string|null $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Find all eligible promos for the given cart state.
     *
     * @return Collection<int, Promo>
     */
    public function eligiblePromos(array $items, float $orderTotal): Collection
    {
        return Promo::all()
            ->where('is_active', true)
            ->filter(fn (Promo $promo): bool => $promo->starts_at === null || $promo->starts_at->lte(now()))
            ->filter(fn (Promo $promo): bool => $promo->ends_at === null || $promo->ends_at->gte(now()))
            ->sortByDesc('priority')
            ->filter(fn (Promo $promo): bool => $promo->conditionsMet($items)
                && $promo->minOrderMet($orderTotal)
                && $this->hasUsagesLeft($promo)
            )
            ->values();
    }

    /**
     * Compute all applicable promo discounts (respecting stacking rules).
     * Does NOT record usage — use redeem() for that.
     *
     * @return array{promos: list<array{promo:Promo,discount:float,name:string,type:DiscountType}>, discount: float}
     */
    public function apply(array $items, float $orderTotal): array
    {
        $eligible = $this->eligiblePromos($items, $orderTotal);
        $applied = [];
        $total = 0.0;

        foreach ($eligible as $promo) {
            $discount = $promo->calculateDiscount(orderTotal: $orderTotal - $total);
            $applied[] = [
                'promo' => $promo,
                'discount' => $discount,
                'name' => $promo->name,
                'type' => $promo->discount_type,
            ];
            $total += $discount;

            if (! $promo->is_stackable) {
                break;
            }
        }

        return ['promos' => $applied, 'discount' => round($total, 2)];
    }

    /**
     * Apply and record usage for each applied promo.
     *
     * @return array{promos: list<array>, discount: float}
     */
    public function redeem(array $items, float $orderTotal): array
    {
        $result = $this->apply($items, $orderTotal);

        foreach ($result['promos'] as $payload) {
            $payload['promo']->recordUsage($this->userId, $payload['discount']);
        }

        return $result;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function hasUsagesLeft(Promo $promo): bool
    {
        return $promo->max_usages === null
            || $promo->usages()->count() < $promo->max_usages;
    }
}
