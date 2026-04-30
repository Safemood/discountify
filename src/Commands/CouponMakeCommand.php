<?php

declare(strict_types=1);

namespace Safemood\Discountify\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Safemood\Discountify\Models\Coupon;

class CouponMakeCommand extends Command
{
    protected $signature = 'discountify:coupon
                            {name : Coupon name}
                            {--code= : Coupon code (defaults to an uppercase slug of name)}
                            {--description= : Optional description}
                            {--type=percentage : percentage|fixed}
                            {--value=0 : Discount value}
                            {--min-order= : Minimum order value}
                            {--max-discount= : Maximum discount amount}
                            {--max-usages= : Total usage limit}
                            {--max-usages-per-user= : Usage limit per user}
                            {--user-id= : Restrict coupon to a specific user}
                            {--starts-at= : Start date/time}
                            {--expires-at= : Expiration date/time}
                            {--active=1 : Set the coupon active}
                            {--force : Overwrite existing coupon}';

    protected $description = 'Create a new Discountify coupon';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $code = $this->option('code');
        if (! is_string($code) || $code === '') {
            $code = Str::upper(Str::slug($name, '_'));
        }

        $description = $this->option('description');
        $type = $this->option('type') ?? 'percentage';
        $value = $this->parseFloat($this->option('value'));
        $minOrder = $this->parseNullableFloat($this->option('min-order'));
        $maxDiscount = $this->parseNullableFloat($this->option('max-discount'));
        $maxUsages = $this->parseNullableInt($this->option('max-usages'));
        $maxUsagesPerUser = $this->parseNullableInt($this->option('max-usages-per-user'));
        $userId = $this->option('user-id');
        $startsAt = $this->parseDate($this->option('starts-at'));
        $expiresAt = $this->parseDate($this->option('expires-at'));
        $isActive = $this->parseBoolean($this->option('active'));

        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();
        if ($coupon && ! $this->option('force')) {
            $this->error("Coupon [{$code}] already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $attributes = [
            'name' => $name,
            'code' => strtoupper(trim($code)),
            'description' => is_string($description) ? $description : null,
            'discount_type' => $type,
            'discount_value' => $value,
            'min_order_value' => $minOrder,
            'max_discount' => $maxDiscount,
            'max_usages' => $maxUsages,
            'max_usages_per_user' => $maxUsagesPerUser,
            'user_id' => is_numeric($userId) ? (int) $userId : $userId,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'is_active' => $isActive,
        ];

        if ($coupon) {
            $coupon->update($attributes);
        } else {
            Coupon::create($attributes);
        }

        $this->info("Coupon [{$code}] saved successfully.");

        return self::SUCCESS;
    }

    private function parseFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function parseNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function parseNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function parseBoolean(mixed $value): bool
    {
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
