<?php

declare(strict_types=1);

namespace Safemood\Discountify\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'discountify_settings';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'value' => 'string',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }
}
