<?php

declare(strict_types=1);

namespace Safemood\Discountify\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Safemood\Discountify\Contracts\CouponInterface;
use Safemood\Discountify\Enums\DiscountType;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property DiscountType $discount_type
 * @property float $discount_value
 * @property float|null $min_order_value
 * @property float|null $max_discount
 * @property int|null $max_usages
 * @property int|null $max_usages_per_user
 * @property int|null $user_id
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 *
 * @method static Builder<Coupon> active()
 * @method static Builder<Coupon> byCode(string $code)
 * @method static Builder<Coupon> query()
 */
class Coupon extends Model implements CouponInterface
{
    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'float',
        'min_order_value' => 'float',
        'max_discount' => 'float',
        'max_usages' => 'integer',
        'max_usages_per_user' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'discount_type' => DiscountType::class,
    ];

    #[\Override]
    public function getTable(): string
    {
        return config('discountify.tables.coupons', 'discountify_coupons');
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    /**
     * @return HasMany<CouponUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'coupon_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    // ── CouponInterface ───────────────────────────────────────────────────────

    #[\Override]
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at?->gt($now)) {
            return false;
        }

        if ($this->expires_at?->lt($now)) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function hasUsagesLeft(): bool
    {
        return $this->max_usages === null
            || $this->usages()->count() < $this->max_usages;
    }

    #[\Override]
    public function canBeUsedByUser(int|string|null $userId): bool
    {
        if ($this->user_id !== null && $this->user_id != $userId) {
            return false;
        }

        if ($this->max_usages_per_user !== null && $userId !== null) {
            $used = $this->usages()->where('user_id', $userId)->count();
            if ($used >= $this->max_usages_per_user) {
                return false;
            }
        }

        return true;
    }

    // ── Business logic ────────────────────────────────────────────────────────

    public function calculateDiscount(float $orderTotal): float
    {
        return $this->discount_type->calculate(
            value: $this->discount_value,
            orderTotal: $orderTotal,
            maxDiscount: $this->max_discount,
        );
    }

    public function recordUsage(int|string|null $userId = null, ?float $discountAmount = null): CouponUsage
    {
        /** @var CouponUsage */
        return $this->usages()->create([
            'user_id' => $userId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);
    }
}
