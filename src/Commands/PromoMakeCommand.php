<?php

declare(strict_types=1);

namespace Safemood\Discountify\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Safemood\Discountify\Models\Promo;

class PromoMakeCommand extends Command
{
    protected $signature = 'discountify:promo
                            {name : Promo name}
                            {--description= : Optional description}
                            {--type=percentage : percentage|fixed}
                            {--value=0 : Discount value}
                            {--min-order= : Minimum order value}
                            {--max-discount= : Maximum discount amount}
                            {--priority=0 : Evaluation priority}
                            {--stackable=1 : Whether the promo is stackable}
                            {--max-usages= : Total usage limit}
                            {--conditions= : JSON-encoded conditions array}
                            {--starts-at= : Start date/time}
                            {--ends-at= : End date/time}
                            {--active=1 : Set the promo active}
                            {--force : Overwrite existing promo}';

    protected $description = 'Create a new Discountify promo';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $description = $this->option('description');
        $type = $this->option('type') ?? 'percentage';
        $value = $this->parseFloat($this->option('value'));
        $minOrder = $this->parseNullableFloat($this->option('min-order'));
        $maxDiscount = $this->parseNullableFloat($this->option('max-discount'));
        $priority = $this->parseInt($this->option('priority'));
        $isStackable = $this->parseBoolean($this->option('stackable'));
        $maxUsages = $this->parseNullableInt($this->option('max-usages'));
        $conditions = $this->parseConditions($this->option('conditions'));
        if ($conditions === false) {
            return self::FAILURE;
        }
        $startsAt = $this->parseDate($this->option('starts-at'));
        $endsAt = $this->parseDate($this->option('ends-at'));
        $isActive = $this->parseBoolean($this->option('active'));

        $promo = Promo::query()->where('name', $name)->first();
        if ($promo && ! $this->option('force')) {
            $this->error("Promo [{$name}] already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $attributes = [
            'name' => $name,
            'description' => is_string($description) ? $description : null,
            'discount_type' => $type,
            'discount_value' => $value,
            'min_order_value' => $minOrder,
            'max_discount' => $maxDiscount,
            'priority' => $priority,
            'is_stackable' => $isStackable,
            'max_usages' => $maxUsages,
            'conditions' => $conditions,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => $isActive,
        ];

        if ($promo) {
            $promo->update($attributes);
        } else {
            Promo::create($attributes);
        }

        $this->info("Promo [{$name}] saved successfully.");

        return self::SUCCESS;
    }

    private function parseFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function parseInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
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

    private function parseConditions(mixed $value): array|bool|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON provided for --conditions.');

                return false;
            }

            return $decoded;
        }

        if (str_contains($trimmed, ':')) {
            $parts = explode(':', $trimmed, 3);
            if (count($parts) === 3) {
                return [[
                    'field' => $parts[0],
                    'operator' => $parts[1],
                    'value' => $this->parseConditionValue($parts[2]),
                ]];
            }
        }

        $this->error('Unable to parse --conditions. Provide JSON or field:operator:value.');

        return false;
    }

    private function parseConditionValue(string $value): mixed
    {
        $value = trim($value);

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        if (strtolower($value) === 'true' || strtolower($value) === 'false') {
            return strtolower($value) === 'true';
        }

        return $value;
    }
}
