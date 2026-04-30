<?php

declare(strict_types=1);

namespace Safemood\Discountify\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoUsage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'discount_amount' => 'float',
        'used_at' => 'datetime',
    ];

    #[\Override]
    public function getTable(): string
    {
        return config('discountify.tables.promo_usages', 'discountify_promo_usages');
    }

    /**
     * @return BelongsTo<Promo, $this>
     */
    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
}
