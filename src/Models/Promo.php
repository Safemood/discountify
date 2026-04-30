<?php

declare(strict_types=1);

namespace Safemood\Discountify\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Safemood\Discountify\Contracts\PromoInterface;
use Safemood\Discountify\Enums\ConditionOperator;
use Safemood\Discountify\Enums\DiscountType;

/**
 * Promo — auto-applied promotional discount (no code required).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property DiscountType $discount_type
 * @property float $discount_value
 * @property float|null $min_order_value
 * @property float|null $max_discount
 * @property int $priority
 * @property bool $is_stackable
 * @property int|null $max_usages
 * @property array|null $conditions JSON rule set
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 *
 * @method static Builder<Promo> active()
 * @method static Builder<Promo> ordered()
 * @method static Builder<Promo> currentlyRunning()
 * @method static Builder<Promo> query()
 */
class Promo extends Model implements PromoInterface
{
    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'float',
        'min_order_value' => 'float',
        'max_discount' => 'float',
        'priority' => 'integer',
        'is_stackable' => 'boolean',
        'max_usages' => 'integer',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'discount_type' => DiscountType::class,
    ];

    #[\Override]
    public function getTable(): string
    {
        return config('discountify.tables.promos', 'discountify_promos');
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    /**
     * @return HasMany<PromoUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromoUsage::class, 'promo_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Promo>  $query
     * @return Builder<Promo>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Promo>  $query
     * @return Builder<Promo>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        /** @var Builder<Promo> $ordered */
        $ordered = $query->orderByDesc('priority');

        return $ordered;
    }

    /**
     * @param  Builder<Promo>  $query
     * @return Builder<Promo>
     */
    public function scopeCurrentlyRunning(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    // ── PromoInterface ────────────────────────────────────────────────────────

    #[\Override]
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check whether all inline JSON condition rules are satisfied.
     *
     * Rule format: [{"field":"count","operator":"gte","value":3}, ...]
     */
    #[\Override]
    public function conditionsMet(array $items): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $rule) {
            $operator = ConditionOperator::from($rule['operator'] ?? 'gte');
            $actual = $this->resolveField($items, $rule['field'] ?? 'count');

            if (! $operator->evaluate($actual, $rule['value'] ?? 0)) {
                return false;
            }
        }

        return true;
    }

    public function minOrderMet(float $orderTotal): bool
    {
        return $this->min_order_value === null || $orderTotal >= $this->min_order_value;
    }

    public function calculateDiscount(float $orderTotal): float
    {
        return $this->discount_type->calculate(
            value: $this->discount_value,
            orderTotal: $orderTotal,
            maxDiscount: $this->max_discount,
        );
    }

    public function recordUsage(int|string|null $userId = null, ?float $discountAmount = null): PromoUsage
    {
        /** @var PromoUsage */
        return $this->usages()->create([
            'user_id' => $userId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function resolveField(array $items, string $field): mixed
    {
        return match ($field) {
            'count' => count($items),
            'total', 'subtotal' => collect($items)->sum(
                fn (array $i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1)
            ),
            default => collect($items)->pluck($field)->first(),
        };
    }
}
