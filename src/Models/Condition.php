<?php

declare(strict_types=1);

namespace Safemood\Discountify\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Safemood\Discountify\Enums\ConditionOperator;
use Safemood\Discountify\Enums\DiscountType;

/**
 * DB-managed condition.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $field 'count' | 'total' | 'subtotal' | any item key
 * @property ConditionOperator $operator
 * @property mixed $value
 * @property float $discount
 * @property DiscountType $discount_type
 * @property int $priority
 * @property bool $is_active
 *
 * @method static Builder<Condition> active()
 * @method static Builder<Condition> ordered()
 * @method static Builder<Condition> query()
 */
class Condition extends Model
{
    protected $guarded = [];

    /** PHP 8.4 + Laravel 12 — native enum casting, no custom cast class needed. */
    protected $casts = [
        'value' => 'json',
        'discount' => 'float',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'discount_type' => DiscountType::class,
        'operator' => ConditionOperator::class,
    ];

    #[\Override]
    public function getTable(): string
    {
        return config('discountify.tables.conditions', 'discountify_conditions');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Condition>  $query
     * @return Builder<Condition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Condition>  $query
     * @return Builder<Condition>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        /** @var Builder<Condition> $ordered */
        $ordered = $query->orderByDesc('priority');

        return $ordered;
    }

    // ── Evaluation ────────────────────────────────────────────────────────────

    /**
     * Evaluate this condition against cart items.
     * Returns true when the rule is satisfied and the discount should apply.
     */
    public function evaluate(array $items): bool
    {
        $actual = $this->resolveField($items);

        return $this->operator->evaluate($actual, $this->value);
    }

    private function resolveField(array $items): mixed
    {
        return match ($this->field) {
            'count' => count($items),
            'total', 'subtotal' => collect($items)->sum(
                fn (array $i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1)
            ),
            default => collect($items)->pluck($this->field)->first(),
        };
    }
}
