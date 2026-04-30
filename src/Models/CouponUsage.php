<?php

declare(strict_types=1);

namespace Safemood\Discountify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'discount_amount' => 'float',
        'used_at' => 'datetime',
    ];

    #[\Override]
    public function getTable(): string
    {
        return config('discountify.tables.coupon_usages', 'discountify_coupon_usages');
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }
}
